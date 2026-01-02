<?php

namespace App\Scraper;

use Symfony\Component\DomCrawler\Crawler;

class ImageExtractor
{
    /**
     * Extract product image URL from HTML.
     * Tries multiple sources in order of preference:
     * 1. JSON-LD structured data (most reliable)
     * 2. Open Graph meta tag (og:image)
     * 3. Twitter card image
     */
    public function extract(string $html, string $baseUrl): ?string
    {
        $crawler = new Crawler($html);

        // Try JSON-LD first
        $image = $this->extractFromJsonLd($crawler);
        if ($image) {
            return $this->makeAbsoluteUrl($image, $baseUrl);
        }

        // Try Open Graph
        $image = $this->extractFromOpenGraph($crawler);
        if ($image) {
            return $this->makeAbsoluteUrl($image, $baseUrl);
        }

        // Try Twitter card
        $image = $this->extractFromTwitterCard($crawler);
        if ($image) {
            return $this->makeAbsoluteUrl($image, $baseUrl);
        }

        return null;
    }

    private function extractFromJsonLd(Crawler $crawler): ?string
    {
        try {
            $scripts = $crawler->filter('script[type="application/ld+json"]');

            foreach ($scripts as $script) {
                $json = trim($script->textContent);
                $data = json_decode($json, true);

                if (!is_array($data)) {
                    continue;
                }

                // Direct image field
                if (isset($data['image'])) {
                    return $this->extractImageFromValue($data['image']);
                }

                // Check @graph array (common in Schema.org)
                if (isset($data['@graph']) && is_array($data['@graph'])) {
                    foreach ($data['@graph'] as $item) {
                        if (isset($item['image'])) {
                            return $this->extractImageFromValue($item['image']);
                        }
                    }
                }
            }
        } catch (\Throwable) {
            // Ignore errors
        }

        return null;
    }

    private function extractImageFromValue(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            // Array of images - take first
            if (isset($value[0])) {
                return $this->extractImageFromValue($value[0]);
            }
            // ImageObject with url
            if (isset($value['url'])) {
                return $value['url'];
            }
            // ImageObject with @id
            if (isset($value['@id'])) {
                return $value['@id'];
            }
        }

        return null;
    }

    private function extractFromOpenGraph(Crawler $crawler): ?string
    {
        try {
            $ogImage = $crawler->filter('meta[property="og:image"]');
            if ($ogImage->count() > 0) {
                return $ogImage->first()->attr('content');
            }
        } catch (\Throwable) {
            // Ignore errors
        }

        return null;
    }

    private function extractFromTwitterCard(Crawler $crawler): ?string
    {
        try {
            $twitterImage = $crawler->filter('meta[name="twitter:image"]');
            if ($twitterImage->count() > 0) {
                return $twitterImage->first()->attr('content');
            }
        } catch (\Throwable) {
            // Ignore errors
        }

        return null;
    }

    private function makeAbsoluteUrl(string $url, string $baseUrl): string
    {
        // Already absolute
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        // Protocol-relative
        if (str_starts_with($url, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https';
            return $scheme . ':' . $url;
        }

        // Relative URL
        $parsed = parse_url($baseUrl);
        $base = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

        if (str_starts_with($url, '/')) {
            return $base . $url;
        }

        return $base . '/' . $url;
    }
}
