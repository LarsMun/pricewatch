<?php

namespace App\Scraper;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HttpEngine implements ScrapeEngineInterface
{
    private const TIMEOUT = 30;
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    public function __construct(
        private HttpClientInterface $httpClient,
    ) {}

    public function fetch(string $url): ScrapeResult
    {
        $startTime = microtime(true);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => self::TIMEOUT,
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'nl-NL,nl;q=0.9,en;q=0.8',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $html = $response->getContent();
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($statusCode >= 400) {
                return ScrapeResult::failure(
                    "HTTP error: $statusCode",
                    $statusCode,
                    $durationMs
                );
            }

            return ScrapeResult::success($html, $statusCode, $durationMs);

        } catch (TransportExceptionInterface $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            return ScrapeResult::failure(
                "Transport error: " . $e->getMessage(),
                null,
                $durationMs
            );
        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            return ScrapeResult::failure(
                "Error: " . $e->getMessage(),
                null,
                $durationMs
            );
        }
    }
}
