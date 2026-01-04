<?php

namespace App\Scraper;

use Symfony\Component\DomCrawler\Crawler;

class PriceExtractionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $price = null,
        public readonly ?string $rawText = null,
        public readonly ?string $error = null,
    ) {}

    public static function success(string $price, string $rawText): self
    {
        return new self(success: true, price: $price, rawText: $rawText);
    }

    public static function failure(string $error): self
    {
        return new self(success: false, error: $error);
    }
}

class PriceExtractor
{
    /**
     * Extract price from HTML using a CSS selector or JSON-LD path.
     *
     * Selector formats:
     * - CSS selector: ".price-class", "#price-id"
     * - JSON-LD path: "jsonld:offers.price" (extracts from script[type="application/ld+json"])
     */
    public function extract(string $html, string $selector): PriceExtractionResult
    {
        // Check if this is a JSON-LD selector
        if (str_starts_with($selector, 'jsonld:')) {
            return $this->extractFromJsonLd($html, substr($selector, 7));
        }

        return $this->extractFromCss($html, $selector);
    }

    /**
     * Extract price using CSS selector.
     */
    private function extractFromCss(string $html, string $selector): PriceExtractionResult
    {
        try {
            $crawler = new Crawler($html);
            $nodes = $crawler->filter($selector);

            if ($nodes->count() === 0) {
                return PriceExtractionResult::failure("Selector '$selector' matched no elements");
            }

            $rawText = trim($nodes->first()->text());

            if (empty($rawText)) {
                return PriceExtractionResult::failure("Element found but text is empty");
            }

            $price = $this->parsePrice($rawText);

            if ($price === null) {
                return PriceExtractionResult::failure("Could not parse price from: '$rawText'");
            }

            return PriceExtractionResult::success($price, $rawText);

        } catch (\InvalidArgumentException $e) {
            return PriceExtractionResult::failure("Invalid selector: " . $e->getMessage());
        } catch (\Throwable $e) {
            return PriceExtractionResult::failure("Extraction error: " . $e->getMessage());
        }
    }

    /**
     * Extract price from JSON-LD structured data.
     * Path format: "offers.price" navigates into the JSON structure.
     * Handles @graph arrays by searching all items for the path.
     */
    private function extractFromJsonLd(string $html, string $path): PriceExtractionResult
    {
        try {
            $crawler = new Crawler($html);
            $scripts = $crawler->filter('script[type="application/ld+json"]');

            if ($scripts->count() === 0) {
                return PriceExtractionResult::failure("No JSON-LD scripts found on page");
            }

            $pathParts = explode('.', $path);

            // Try each JSON-LD script
            foreach ($scripts as $script) {
                $json = trim($script->textContent);
                $data = json_decode($json, true);

                if (!is_array($data)) {
                    continue;
                }

                // Handle @graph structure - search all items in the graph
                if (isset($data['@graph']) && is_array($data['@graph'])) {
                    foreach ($data['@graph'] as $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        $value = $this->getNestedValue($item, $pathParts);
                        if ($value !== null) {
                            $rawText = (string) $value;
                            $price = $this->parsePrice($rawText);
                            if ($price !== null) {
                                return PriceExtractionResult::success($price, $rawText);
                            }
                        }
                    }
                }

                // Also try direct path (non-graph structure)
                $value = $this->getNestedValue($data, $pathParts);

                if ($value !== null) {
                    $rawText = (string) $value;
                    $price = $this->parsePrice($rawText);

                    if ($price !== null) {
                        return PriceExtractionResult::success($price, $rawText);
                    }
                }
            }

            return PriceExtractionResult::failure("Path '$path' not found in JSON-LD data");

        } catch (\Throwable $e) {
            return PriceExtractionResult::failure("JSON-LD extraction error: " . $e->getMessage());
        }
    }

    /**
     * Navigate nested array using dot-separated path.
     * Handles arrays by trying the first element.
     */
    private function getNestedValue(array $data, array $pathParts): mixed
    {
        $current = $data;

        foreach ($pathParts as $key) {
            if (!is_array($current)) {
                return null;
            }

            // If current is a sequential array, try first element
            if (array_is_list($current) && !empty($current)) {
                $current = $current[0];
                if (!is_array($current)) {
                    return null;
                }
            }

            if (!array_key_exists($key, $current)) {
                return null;
            }

            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Parse a price string into a normalized decimal value.
     * Handles various European formats: "€ 19,99", "19.99", "1.299,00", etc.
     */
    private function parsePrice(string $text): ?string
    {
        // Remove currency symbols and whitespace
        $text = preg_replace('/[€$£¥\s]/', '', $text);
        
        // Remove any non-numeric characters except . and ,
        $text = preg_replace('/[^\d.,]/', '', $text);

        if (empty($text)) {
            return null;
        }

        // Count dots and commas
        $dotCount = substr_count($text, '.');
        $commaCount = substr_count($text, ',');

        // Determine decimal separator
        if ($commaCount === 1 && $dotCount === 0) {
            // European: "19,99" -> "19.99"
            $text = str_replace(',', '.', $text);
        } elseif ($dotCount === 1 && $commaCount === 0) {
            // Already US format: "19.99"
        } elseif ($dotCount >= 1 && $commaCount >= 1) {
            // Mixed separators - determine by position of last separator
            $lastDotPos = strrpos($text, '.');
            $lastCommaPos = strrpos($text, ',');

            if ($lastCommaPos > $lastDotPos) {
                // European with thousands: "1.299,00" -> comma is decimal
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            } else {
                // US with thousands: "1,299.00" -> dot is decimal
                $text = str_replace(',', '', $text);
            }
        } elseif (false && $dotCount > 0 && $commaCount === 1) {
            // European with thousands: "1.299,00" -> "1299.00"
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } elseif ($commaCount > 0 && $dotCount === 1) {
            // US with thousands: "1,299.00" -> "1299.00"
            $text = str_replace(',', '', $text);
        } elseif ($dotCount > 1) {
            // Multiple dots as thousands: "1.299.00" - take last as decimal
            $parts = explode('.', $text);
            $decimal = array_pop($parts);
            $text = implode('', $parts) . '.' . $decimal;
        } elseif ($commaCount > 1) {
            // Multiple commas as thousands: "1,299,00" - take last as decimal
            $parts = explode(',', $text);
            $decimal = array_pop($parts);
            $text = implode('', $parts) . '.' . $decimal;
        }

        // Validate it's a number
        if (!is_numeric($text)) {
            return null;
        }

        // Return as formatted decimal (2 decimal places)
        return number_format((float) $text, 2, '.', '');
    }
}
