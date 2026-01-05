<?php

namespace App\Scraper;

use Symfony\Component\Panther\Client;

class BrowserEngine implements ScrapeEngineInterface
{
    private const TIMEOUT = 30;
    private const USER_AGENT = 'ShopQ/1.0 (prijsmonitor; +https://shopq.app/bot; legal@shopq.app)';

    public function fetch(string $url): ScrapeResult
    {
        $startTime = microtime(true);
        $client = null;

        try {
            $client = Client::createChromeClient(null, [
                '--headless',
                '--disable-gpu',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--window-size=1920,1080',
                '--user-agent=' . self::USER_AGENT,
            ]);

            $client->request('GET', $url);
            
            // Wait for page to load (JavaScript execution)
            usleep(2000000); // 2 seconds wait for JS rendering

            $html = $client->getCrawler()->html();
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $client->quit();

            if (empty($html)) {
                return ScrapeResult::failure(
                    'Empty response from browser',
                    null,
                    $durationMs
                );
            }

            return ScrapeResult::success($html, 200, $durationMs);

        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            
            if ($client !== null) {
                try {
                    $client->quit();
                } catch (\Throwable) {
                    // Ignore quit errors
                }
            }

            return ScrapeResult::failure(
                'Browser error: ' . $e->getMessage(),
                null,
                $durationMs
            );
        }
    }
}
