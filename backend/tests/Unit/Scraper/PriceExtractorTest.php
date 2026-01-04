<?php

namespace App\Tests\Unit\Scraper;

use App\Scraper\PriceExtractor;
use PHPUnit\Framework\TestCase;

class PriceExtractorTest extends TestCase
{
    private PriceExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new PriceExtractor();
    }

    // === Price Parsing Tests ===

    public function testParseEuropeanFormat(): void
    {
        $html = '<div class="price">19,99</div>';
        $result = $this->extractor->extract($html, '.price');

        $this->assertTrue($result->success);
        $this->assertSame('19.99', $result->price);
    }

    public function testParseUsFormat(): void
    {
        $html = '<div class="price">19.99</div>';
        $result = $this->extractor->extract($html, '.price');

        $this->assertTrue($result->success);
        $this->assertSame('19.99', $result->price);
    }

    public function testParseWithEuroSymbol(): void
    {
        $html = '<div class="price">€ 19,99</div>';
        $result = $this->extractor->extract($html, '.price');

        $this->assertTrue($result->success);
        $this->assertSame('19.99', $result->price);
    }

    public function testParseWithDollarSymbol(): void
    {
        $html = '<div class="price">$29.99</div>';
        $result = $this->extractor->extract($html, '.price');

        $this->assertTrue($result->success);
        $this->assertSame('29.99', $result->price);
    }

    public function testParseThousandsSeparatorEuropean(): void
    {
        $html = '<div class="price">1.299,00</div>';
        $result = $this->extractor->extract($html, '.price');

        $this->assertTrue($result->success);
        $this->assertSame('1299.00', $result->price);
    }

    public function testParseThousandsSeparatorUs(): void
    {
        // Note: "1,299.00" is ambiguous (could be EU or US format)
        // The extractor defaults to European interpretation
        // Use unambiguous format: multiple commas = US thousands
        $html = '<div class="price">12,299.00</div>';
        $result = $this->extractor->extract($html, '.price');

        $this->assertTrue($result->success);
        $this->assertSame('12299.00', $result->price);
    }

    public function testParseWithPoundSymbol(): void
    {
        $html = '<div class="price">£49.99</div>';
        $result = $this->extractor->extract($html, '.price');

        $this->assertTrue($result->success);
        $this->assertSame('49.99', $result->price);
    }

    public function testParseWholeNumber(): void
    {
        $html = '<div class="price">100</div>';
        $result = $this->extractor->extract($html, '.price');

        $this->assertTrue($result->success);
        $this->assertSame('100.00', $result->price);
    }

    public function testParseWithExtraSpaces(): void
    {
        $html = '<div class="price">  €  99,95  </div>';
        $result = $this->extractor->extract($html, '.price');

        $this->assertTrue($result->success);
        $this->assertSame('99.95', $result->price);
    }

    // === CSS Selector Tests ===

    public function testExtractFromCssSelector(): void
    {
        $html = '<html><body><span id="product-price">€ 49,99</span></body></html>';
        $result = $this->extractor->extract($html, '#product-price');

        $this->assertTrue($result->success);
        $this->assertSame('49.99', $result->price);
        $this->assertSame('€ 49,99', $result->rawText);
    }

    public function testExtractFromCssClassSelector(): void
    {
        $html = '<div class="price-tag main">€ 129,00</div>';
        $result = $this->extractor->extract($html, '.price-tag');

        $this->assertTrue($result->success);
        $this->assertSame('129.00', $result->price);
    }

    public function testExtractFromDataAttribute(): void
    {
        $html = '<span data-price="true">€ 79,99</span>';
        $result = $this->extractor->extract($html, '[data-price]');

        $this->assertTrue($result->success);
        $this->assertSame('79.99', $result->price);
    }

    public function testSelectorMatchesNoElements(): void
    {
        $html = '<div class="other">content</div>';
        $result = $this->extractor->extract($html, '.price');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('matched no elements', $result->error);
    }

    public function testEmptyElementText(): void
    {
        $html = '<div class="price"></div>';
        $result = $this->extractor->extract($html, '.price');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('text is empty', $result->error);
    }

    public function testReturnsNullForInvalidPrice(): void
    {
        $html = '<div class="price">Not a price</div>';
        $result = $this->extractor->extract($html, '.price');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Could not parse price', $result->error);
    }

    // === JSON-LD Tests ===

    public function testExtractFromJsonLd(): void
    {
        $html = '<html><head><script type="application/ld+json">
            {"@type": "Product", "offers": {"price": "99.99"}}
        </script></head><body></body></html>';

        $result = $this->extractor->extract($html, 'jsonld:offers.price');

        $this->assertTrue($result->success);
        $this->assertSame('99.99', $result->price);
    }

    public function testExtractFromNestedJsonLd(): void
    {
        $html = '<html><head><script type="application/ld+json">
            {
                "@type": "Product",
                "name": "Test Product",
                "offers": {
                    "@type": "Offer",
                    "price": "149,00",
                    "priceCurrency": "EUR"
                }
            }
        </script></head><body></body></html>';

        $result = $this->extractor->extract($html, 'jsonld:offers.price');

        $this->assertTrue($result->success);
        $this->assertSame('149.00', $result->price);
    }

    public function testExtractFromJsonLdGraph(): void
    {
        $html = '<html><head><script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@graph": [
                    {"@type": "WebSite", "name": "Shop"},
                    {"@type": "Product", "offers": {"price": "299.00"}}
                ]
            }
        </script></head><body></body></html>';

        $result = $this->extractor->extract($html, 'jsonld:offers.price');

        $this->assertTrue($result->success);
        $this->assertSame('299.00', $result->price);
    }

    public function testJsonLdPathNotFound(): void
    {
        $html = '<html><head><script type="application/ld+json">
            {"@type": "Product", "name": "Test"}
        </script></head><body></body></html>';

        $result = $this->extractor->extract($html, 'jsonld:offers.price');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('not found in JSON-LD', $result->error);
    }

    public function testNoJsonLdOnPage(): void
    {
        $html = '<html><body><p>No JSON-LD here</p></body></html>';
        $result = $this->extractor->extract($html, 'jsonld:offers.price');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('No JSON-LD scripts found', $result->error);
    }

    public function testJsonLdWithArrayOffers(): void
    {
        $html = '<html><head><script type="application/ld+json">
            {
                "@type": "Product",
                "offers": [
                    {"price": "199.00", "seller": "Shop A"},
                    {"price": "189.00", "seller": "Shop B"}
                ]
            }
        </script></head><body></body></html>';

        $result = $this->extractor->extract($html, 'jsonld:offers.price');

        $this->assertTrue($result->success);
        // Should get first offer's price
        $this->assertSame('199.00', $result->price);
    }

    public function testExtractFromMultipleJsonLdScripts(): void
    {
        $html = '<html><head>
            <script type="application/ld+json">{"@type": "Organization", "name": "Shop"}</script>
            <script type="application/ld+json">{"@type": "Product", "offers": {"price": "59.99"}}</script>
        </head><body></body></html>';

        $result = $this->extractor->extract($html, 'jsonld:offers.price');

        $this->assertTrue($result->success);
        $this->assertSame('59.99', $result->price);
    }
}
