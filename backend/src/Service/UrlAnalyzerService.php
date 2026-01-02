<?php

namespace App\Service;

use App\Scraper\HttpEngine;
use Symfony\Component\DomCrawler\Crawler;

class UrlAnalysisResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $url = null,
        public readonly ?string $domain = null,
        public readonly ?string $productName = null,
        public readonly ?string $price = null,
        public readonly ?string $currency = null,
        public readonly ?string $imageUrl = null,
        public readonly ?string $priceSelector = null,
        public readonly string $detectionMethod = 'none',
        public readonly array $availableSelectors = [],
        public readonly ?string $error = null,
    ) {}
}

class UrlAnalyzerService
{
    public function __construct(
        private HttpEngine $httpEngine,
    ) {}

    public function analyze(string $url): UrlAnalysisResult
    {
        try {
            // Fetch the page
            $result = $this->httpEngine->fetch($url);
            
            if (!$result->success) {
                return new UrlAnalysisResult(
                    success: false,
                    error: $result->error ?? 'Failed to fetch URL'
                );
            }

            $html = $result->html;
            $domain = parse_url($url, PHP_URL_HOST);
            
            // Try JSON-LD first
            $jsonLdData = $this->extractJsonLdProduct($html);
            
            if ($jsonLdData) {
                return new UrlAnalysisResult(
                    success: true,
                    url: $url,
                    domain: $domain,
                    productName: $jsonLdData['name'],
                    price: $jsonLdData['price'],
                    currency: $jsonLdData['currency'] ?? 'EUR',
                    imageUrl: $jsonLdData['image'],
                    priceSelector: 'jsonld:offers.price',
                    detectionMethod: 'jsonld',
                    availableSelectors: $this->buildAvailableSelectors($jsonLdData, $html),
                );
            }

            // Fallback: try to find price elements in HTML
            $cssData = $this->detectPriceElements($html);
            $metaData = $this->extractMetaTags($html);
            
            return new UrlAnalysisResult(
                success: true,
                url: $url,
                domain: $domain,
                productName: $metaData['title'],
                price: $cssData['price'],
                currency: $cssData['currency'] ?? 'EUR',
                imageUrl: $metaData['image'],
                priceSelector: $cssData['selector'],
                detectionMethod: $cssData['selector'] ? 'css' : 'none',
                availableSelectors: $cssData['availableSelectors'] ?? [],
            );

        } catch (\Throwable $e) {
            return new UrlAnalysisResult(
                success: false,
                error: 'Analysis failed: ' . $e->getMessage()
            );
        }
    }

    private function extractJsonLdProduct(string $html): ?array
    {
        $crawler = new Crawler($html);
        $scripts = $crawler->filter('script[type="application/ld+json"]');

        foreach ($scripts as $script) {
            $json = trim($script->textContent);
            $data = json_decode($json, true);

            if (!is_array($data)) {
                continue;
            }

            // Handle @graph structure
            $items = isset($data['@graph']) ? $data['@graph'] : [$data];

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $type = $item['@type'] ?? '';
                if ($type !== 'Product') {
                    continue;
                }

                // Found a Product
                $name = $item['name'] ?? null;
                $image = $this->extractImage($item);
                $price = null;
                $currency = null;

                // Extract price from offers
                $offers = $item['offers'] ?? null;
                if ($offers) {
                    // offers can be object or array
                    if (isset($offers['@type'])) {
                        $price = $offers['price'] ?? null;
                        $currency = $offers['priceCurrency'] ?? null;
                    } elseif (is_array($offers) && !empty($offers)) {
                        $firstOffer = $offers[0] ?? $offers;
                        $price = $firstOffer['price'] ?? null;
                        $currency = $firstOffer['priceCurrency'] ?? null;
                    }
                }

                if ($name || $price) {
                    return [
                        'name' => $name,
                        'price' => $price,
                        'currency' => $currency,
                        'image' => $image,
                    ];
                }
            }
        }

        return null;
    }

    private function extractImage(array $item): ?string
    {
        $image = $item['image'] ?? null;
        
        if (is_string($image)) {
            return $image;
        }
        
        if (is_array($image)) {
            // Could be array of URLs or array with @url
            if (isset($image['@url'])) {
                return $image['@url'];
            }
            if (isset($image['url'])) {
                return $image['url'];
            }
            // Array of URLs, take first
            foreach ($image as $img) {
                if (is_string($img)) {
                    return $img;
                }
                if (is_array($img) && isset($img['url'])) {
                    return $img['url'];
                }
            }
        }

        return null;
    }

    private function extractMetaTags(string $html): array
    {
        $crawler = new Crawler($html);
        $result = ['title' => null, 'image' => null];

        // Title: og:title > twitter:title > <title>
        try {
            $ogTitle = $crawler->filter('meta[property="og:title"]')->attr('content');
            if ($ogTitle) {
                $result['title'] = $ogTitle;
            }
        } catch (\Exception $e) {}

        if (!$result['title']) {
            try {
                $twitterTitle = $crawler->filter('meta[name="twitter:title"]')->attr('content');
                if ($twitterTitle) {
                    $result['title'] = $twitterTitle;
                }
            } catch (\Exception $e) {}
        }

        if (!$result['title']) {
            try {
                $title = $crawler->filter('title')->text();
                if ($title) {
                    $result['title'] = trim($title);
                }
            } catch (\Exception $e) {}
        }

        // Image: og:image > twitter:image
        try {
            $ogImage = $crawler->filter('meta[property="og:image"]')->attr('content');
            if ($ogImage) {
                $result['image'] = $ogImage;
            }
        } catch (\Exception $e) {}

        if (!$result['image']) {
            try {
                $twitterImage = $crawler->filter('meta[name="twitter:image"]')->attr('content');
                if ($twitterImage) {
                    $result['image'] = $twitterImage;
                }
            } catch (\Exception $e) {}
        }

        return $result;
    }

    private function detectPriceElements(string $html): array
    {
        $crawler = new Crawler($html);
        $result = [
            'price' => null,
            'selector' => null,
            'currency' => 'EUR',
            'availableSelectors' => [],
        ];

        // Common price selectors to try
        $priceSelectors = [
            '[data-price]',
            '[itemprop="price"]',
            '.product-price',
            '.price',
            '.current-price',
            '.sale-price',
            '.product__price',
            '.price__current',
            '#product-price',
            '.pdp-price',
        ];

        foreach ($priceSelectors as $selector) {
            try {
                $nodes = $crawler->filter($selector);
                if ($nodes->count() > 0) {
                    $text = trim($nodes->first()->text());
                    $price = $this->parsePrice($text);
                    
                    if ($price) {
                        $result['availableSelectors'][] = [
                            'selector' => $selector,
                            'price' => $price,
                            'rawText' => $text,
                        ];
                        
                        // Use first found as default
                        if (!$result['price']) {
                            $result['price'] = $price;
                            $result['selector'] = $selector;
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $result;
    }

    private function buildAvailableSelectors(array $jsonLdData, string $html): array
    {
        $selectors = [];
        
        // JSON-LD is always first option
        if ($jsonLdData['price']) {
            $selectors[] = [
                'selector' => 'jsonld:offers.price',
                'price' => $jsonLdData['price'],
                'rawText' => $jsonLdData['price'],
                'recommended' => true,
            ];
        }

        // Also detect CSS selectors as alternatives
        $cssData = $this->detectPriceElements($html);
        foreach ($cssData['availableSelectors'] as $css) {
            $selectors[] = array_merge($css, ['recommended' => false]);
        }

        return $selectors;
    }

    private function parsePrice(string $text): ?string
    {
        $text = preg_replace('/[€$£¥\s]/', '', $text);
        $text = preg_replace('/[^\d.,]/', '', $text);

        if (empty($text)) {
            return null;
        }

        $dotCount = substr_count($text, '.');
        $commaCount = substr_count($text, ',');

        if ($commaCount === 1 && $dotCount === 0) {
            $text = str_replace(',', '.', $text);
        } elseif ($dotCount > 0 && $commaCount === 1) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } elseif ($commaCount > 0 && $dotCount === 1) {
            $text = str_replace(',', '', $text);
        }

        if (!is_numeric($text)) {
            return null;
        }

        return number_format((float) $text, 2, '.', '');
    }
}
