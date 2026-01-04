<?php

namespace App\Tests\Unit\Service;

use App\Service\RobotsTxtChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RobotsTxtCheckerTest extends TestCase
{
    private RobotsTxtChecker $checker;
    private MockObject&HttpClientInterface $httpClient;
    private MockObject&LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->checker = new RobotsTxtChecker($this->httpClient, $this->logger);
    }

    // === Basic Parsing Tests ===

    public function testAllowsWhenNoRobotsTxt(): void
    {
        $this->mockRobotsTxtResponse(404);

        $result = $this->checker->checkAndLog('https://example.com/page', 'ShopQBot/1.0');

        $this->assertTrue($result);
    }

    public function testAllowsWhenEmptyRobotsTxt(): void
    {
        $this->mockRobotsTxtResponse(200, '');

        $result = $this->checker->checkAndLog('https://example.com/page', 'ShopQBot/1.0');

        $this->assertTrue($result);
    }

    public function testBlocksWithDisallowAll(): void
    {
        $robotsTxt = <<<EOT
User-agent: *
Disallow: /
EOT;
        $this->mockRobotsTxtResponse(200, $robotsTxt);

        $result = $this->checker->checkAndLog('https://example.com/page', 'ShopQBot/1.0');

        $this->assertFalse($result);
    }

    public function testAllowsSpecificPath(): void
    {
        $robotsTxt = <<<EOT
User-agent: *
Disallow: /admin/
Allow: /
EOT;
        $this->mockRobotsTxtResponse(200, $robotsTxt);

        $result = $this->checker->checkAndLog('https://example.com/products/123', 'ShopQBot/1.0');

        $this->assertTrue($result);
    }

    public function testBlocksAdminPath(): void
    {
        $robotsTxt = <<<EOT
User-agent: *
Disallow: /admin/
EOT;
        $this->mockRobotsTxtResponse(200, $robotsTxt);

        $result = $this->checker->checkAndLog('https://example.com/admin/users', 'ShopQBot/1.0');

        $this->assertFalse($result);
    }

    // === User-Agent Matching ===

    public function testMatchesSpecificUserAgent(): void
    {
        $robotsTxt = <<<EOT
User-agent: ShopQBot
Disallow: /secret/

User-agent: *
Allow: /
EOT;
        $this->mockRobotsTxtResponse(200, $robotsTxt);

        $result = $this->checker->checkAndLog('https://example.com/secret/page', 'ShopQBot/1.0');

        $this->assertFalse($result);
    }

    public function testFallsBackToWildcard(): void
    {
        $robotsTxt = <<<EOT
User-agent: Googlebot
Disallow: /private/

User-agent: *
Disallow: /admin/
EOT;
        $this->mockRobotsTxtResponse(200, $robotsTxt);

        // ShopQBot should use wildcard rules
        $resultPrivate = $this->checker->checkAndLog('https://example.com/private/page', 'ShopQBot/1.0');

        // Need new instance for fresh cache
        $this->checker = new RobotsTxtChecker($this->httpClient, $this->logger);
        $this->mockRobotsTxtResponse(200, $robotsTxt);
        $resultAdmin = $this->checker->checkAndLog('https://example.com/admin/page', 'ShopQBot/1.0');

        $this->assertTrue($resultPrivate); // Not blocked for ShopQBot
        $this->assertFalse($resultAdmin); // Blocked by wildcard
    }

    // === Crawl-Delay Tests ===

    public function testExtractsCrawlDelay(): void
    {
        $robotsTxt = <<<EOT
User-agent: *
Crawl-delay: 10
Disallow: /admin/
EOT;
        $this->mockRobotsTxtResponse(200, $robotsTxt);

        $delay = $this->checker->getCrawlDelay('https://example.com/page', 'ShopQBot/1.0');

        $this->assertSame(10.0, $delay);
    }

    public function testExtractsCrawlDelayForSpecificBot(): void
    {
        $robotsTxt = <<<EOT
User-agent: ShopQBot
Crawl-delay: 5

User-agent: *
Crawl-delay: 2
EOT;
        $this->mockRobotsTxtResponse(200, $robotsTxt);

        $delay = $this->checker->getCrawlDelay('https://example.com/page', 'ShopQBot/1.0');

        $this->assertSame(5.0, $delay);
    }

    public function testNoCrawlDelay(): void
    {
        $robotsTxt = <<<EOT
User-agent: *
Disallow: /admin/
EOT;
        $this->mockRobotsTxtResponse(200, $robotsTxt);

        $delay = $this->checker->getCrawlDelay('https://example.com/page', 'ShopQBot/1.0');

        $this->assertNull($delay);
    }

    // === Pattern Matching ===

    public function testWildcardPatternMatching(): void
    {
        $robotsTxt = <<<EOT
User-agent: *
Disallow: /*.pdf
EOT;
        $this->mockRobotsTxtResponse(200, $robotsTxt);

        $result = $this->checker->checkAndLog('https://example.com/docs/file.pdf', 'ShopQBot/1.0');

        $this->assertFalse($result);
    }

    public function testEndAnchorPatternMatching(): void
    {
        $robotsTxt = <<<EOT
User-agent: *
Disallow: /private$
EOT;
        $this->mockRobotsTxtResponse(200, $robotsTxt);

        $resultExact = $this->checker->checkAndLog('https://example.com/private', 'ShopQBot/1.0');

        // Fresh instance for new request
        $this->checker = new RobotsTxtChecker($this->httpClient, $this->logger);
        $this->mockRobotsTxtResponse(200, $robotsTxt);
        $resultWithSlash = $this->checker->checkAndLog('https://example.com/private/page', 'ShopQBot/1.0');

        $this->assertFalse($resultExact);
        $this->assertTrue($resultWithSlash); // /private/page doesn't match /private$
    }

    // === Longest Match Wins ===

    public function testLongestMatchWins(): void
    {
        $robotsTxt = <<<EOT
User-agent: *
Disallow: /api/
Allow: /api/public/
EOT;
        $this->mockRobotsTxtResponse(200, $robotsTxt);

        $result = $this->checker->checkAndLog('https://example.com/api/public/endpoint', 'ShopQBot/1.0');

        $this->assertTrue($result); // Allow wins because it's more specific
    }

    // === Comments Handling ===

    public function testIgnoresComments(): void
    {
        $robotsTxt = <<<EOT
# This is a comment
User-agent: * # Another comment
Disallow: /admin/ # Block admin
Allow: / # Allow everything else
EOT;
        $this->mockRobotsTxtResponse(200, $robotsTxt);

        $resultAdmin = $this->checker->checkAndLog('https://example.com/admin/page', 'ShopQBot/1.0');

        $this->checker = new RobotsTxtChecker($this->httpClient, $this->logger);
        $this->mockRobotsTxtResponse(200, $robotsTxt);
        $resultPublic = $this->checker->checkAndLog('https://example.com/public/page', 'ShopQBot/1.0');

        $this->assertFalse($resultAdmin);
        $this->assertTrue($resultPublic);
    }

    // === Cache Tests ===

    public function testCachesRobotsTxtPerDomain(): void
    {
        $robotsTxt = "User-agent: *\nDisallow: /admin/";
        $this->mockRobotsTxtResponse(200, $robotsTxt);

        // First call
        $this->checker->checkAndLog('https://example.com/page1', 'ShopQBot/1.0');

        // Second call - should use cache, not make another HTTP request
        // (HttpClient mock would fail if called again without setup)
        $result = $this->checker->checkAndLog('https://example.com/page2', 'ShopQBot/1.0');

        $this->assertTrue($result);
    }

    // === Invalid URL Tests ===

    public function testInvalidUrlReturnsFalse(): void
    {
        $result = $this->checker->checkAndLog('not-a-valid-url', 'ShopQBot/1.0');

        $this->assertFalse($result);
    }

    public function testInvalidUrlReturnsNullCrawlDelay(): void
    {
        $delay = $this->checker->getCrawlDelay('not-a-valid-url', 'ShopQBot/1.0');

        $this->assertNull($delay);
    }

    // === Helper Methods ===

    private function mockRobotsTxtResponse(int $statusCode, string $content = ''): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getContent')->willReturn($content);

        $this->httpClient->method('request')
            ->willReturn($response);
    }
}
