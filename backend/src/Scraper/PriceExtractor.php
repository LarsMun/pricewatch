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
     * Extract price from HTML using a CSS selector.
     */
    public function extract(string $html, string $selector): PriceExtractionResult
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
        } elseif ($dotCount > 0 && $commaCount === 1) {
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
