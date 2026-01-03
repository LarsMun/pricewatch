<?php

namespace App\Service;

use App\Entity\PriceCheck;
use App\Entity\ProductWatch;
use App\Enum\CheckMethod;
use App\Scraper\BrowserEngine;
use App\Scraper\HttpEngine;
use App\Scraper\ImageExtractor;
use App\Scraper\PriceExtractor;
use App\Scraper\ScrapeEngineInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PriceCheckService
{
    private const USER_AGENT = 'ShopQBot/1.0';

    public function __construct(
        private HttpEngine $httpEngine,
        private BrowserEngine $browserEngine,
        private PriceExtractor $priceExtractor,
        private ImageExtractor $imageExtractor,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private NotificationService $notificationService,
        private DomainRateLimiter $rateLimiter,
        private RobotsTxtChecker $robotsChecker,
    ) {}

    /**
     * Get the appropriate scrape engine for a watch.
     */
    private function getEngine(ProductWatch $watch): ScrapeEngineInterface
    {
        return match ($watch->getCheckMethod()) {
            CheckMethod::BROWSER => $this->browserEngine,
            default => $this->httpEngine,
        };
    }

    /**
     * Check the price for a single ProductWatch.
     * Creates a PriceCheck record and updates the watch.
     */
    public function check(ProductWatch $watch): PriceCheck
    {
        $url = $watch->getUrl();
        $domain = $this->extractDomain($url);
        $engine = $this->getEngine($watch);
        $engineName = $watch->getCheckMethod()->value;

        $this->logger->info("Checking price for watch #{$watch->getId()} using {$engineName} engine: {$url}");

        // Check robots.txt compliance
        if (!$this->robotsChecker->checkAndLog($url, self::USER_AGENT)) {
            $this->logger->warning("URL blocked by robots.txt: {$url}");
            return $this->createRateLimitedCheck($watch, 'URL blocked by robots.txt');
        }

        // Check domain rate limit
        if (!$this->rateLimiter->consume($domain)) {
            $this->logger->warning("Rate limit exceeded for domain: {$domain}");
            return $this->createRateLimitedCheck($watch, 'Rate limit exceeded for domain');
        }

        // Respect crawl-delay if specified in robots.txt
        $crawlDelay = $this->robotsChecker->getCrawlDelay($url, self::USER_AGENT);
        if ($crawlDelay !== null && $crawlDelay > 0) {
            $this->logger->debug("Respecting crawl-delay of {$crawlDelay}s for {$domain}");
            usleep((int) ($crawlDelay * 1000000));
        }

        $scrapeResult = $engine->fetch($url);

        $priceCheck = new PriceCheck();
        $priceCheck->setProductWatch($watch);
        $priceCheck->setHttpStatus($scrapeResult->httpStatus);
        $priceCheck->setDurationMs($scrapeResult->durationMs);
        $priceCheck->setCheckedAt(new \DateTimeImmutable());

        $watch->setLastCheckedAt(new \DateTimeImmutable());

        if (!$scrapeResult->success) {
            $this->handleFailure($watch, $priceCheck, $scrapeResult->error);
        } else {
            $extractResult = $this->priceExtractor->extract(
                $scrapeResult->html,
                $watch->getPriceSelector()
            );

            if (!$extractResult->success) {
                $this->handleFailure($watch, $priceCheck, $extractResult->error);
            } else {
                $this->handleSuccess($watch, $priceCheck, $extractResult->price, $extractResult->rawText, $scrapeResult->html);
            }
        }

        $watch->scheduleNextCheck();

        $this->entityManager->persist($priceCheck);
        $this->entityManager->flush();

        return $priceCheck;
    }

    private function handleFailure(ProductWatch $watch, PriceCheck $priceCheck, string $error): void
    {
        $priceCheck->setWasSuccessful(false);
        $priceCheck->setErrorMessage($error);
        $watch->incrementFailures();

        $this->logger->warning("Check failed for watch #{$watch->getId()}: {$error}");

        // Check if we hit the failure threshold (5 consecutive failures)
        if ($watch->hasReachedFailureThreshold()) {
            $this->logger->warning("Watch #{$watch->getId()} reached failure threshold, sending notification and pausing");
            
            $watch->pause();
            
            try {
                $this->notificationService->notifySiteBroken($watch);
            } catch (\Throwable $e) {
                $this->logger->error("Failed to send site_broken notification: " . $e->getMessage());
            }
        }
    }

    private function handleSuccess(ProductWatch $watch, PriceCheck $priceCheck, string $price, string $rawText, string $html): void
    {
        $priceCheck->setWasSuccessful(true);
        $priceCheck->setPrice($price);
        $priceCheck->setRawText($rawText);

        $oldPrice = $watch->getCurrentPrice();

        $watch->resetFailures();
        $watch->setLastSuccessfulCheckAt(new \DateTimeImmutable());
        $watch->setLastSeenRawText($rawText);

        // Update price on watch (handles debounce)
        $priceChanged = $watch->updatePrice($price);

        if ($watch->getOriginalPrice() === null) {
            $watch->setOriginalPrice($price);
        }

        // Extract product image if not already set
        if ($watch->getImageUrl() === null) {
            $imageUrl = $this->imageExtractor->extract($html, $watch->getUrl());
            if ($imageUrl) {
                $watch->setImageUrl($imageUrl);
                $this->logger->info("Extracted image for watch #{$watch->getId()}: {$imageUrl}");
            }
        }

        $this->logger->info(
            "Price check successful for watch #{$watch->getId()}: {$price}" .
            ($priceChanged ? " (CHANGED from {$oldPrice})" : "")
        );

        // Send notification if price changed
        if ($priceChanged && $oldPrice !== null) {
            $this->sendPriceChangeNotification($watch, $oldPrice, $price);
        }
    }

    private function sendPriceChangeNotification(ProductWatch $watch, string $oldPrice, string $newPrice): void
    {
        try {
            $oldFloat = (float) $oldPrice;
            $newFloat = (float) $newPrice;

            if ($newFloat < $oldFloat) {
                $this->notificationService->notifyPriceDecrease($watch, $oldPrice, $newPrice);
            } else {
                $this->notificationService->notifyPriceIncrease($watch, $oldPrice, $newPrice);
            }
        } catch (\Throwable $e) {
            $this->logger->error("Failed to send price change notification: " . $e->getMessage());
        }
    }

    private function extractDomain(string $url): string
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? 'unknown';
    }

    private function createRateLimitedCheck(ProductWatch $watch, string $reason): PriceCheck
    {
        $priceCheck = new PriceCheck();
        $priceCheck->setProductWatch($watch);
        $priceCheck->setWasSuccessful(false);
        $priceCheck->setErrorMessage($reason);
        $priceCheck->setCheckedAt(new \DateTimeImmutable());
        $priceCheck->setDurationMs(0);

        // Don't increment failures for rate limiting - it's not a site problem
        // but do schedule next check further out
        $watch->setLastCheckedAt(new \DateTimeImmutable());
        $watch->scheduleNextCheck();

        $this->entityManager->persist($priceCheck);
        $this->entityManager->flush();

        return $priceCheck;
    }
}
