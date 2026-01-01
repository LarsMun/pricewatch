<?php

namespace App\Service;

use App\Entity\PriceCheck;
use App\Entity\ProductWatch;
use App\Scraper\HttpEngine;
use App\Scraper\PriceExtractor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PriceCheckService
{
    public function __construct(
        private HttpEngine $httpEngine,
        private PriceExtractor $priceExtractor,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {}

    /**
     * Check the price for a single ProductWatch.
     * Creates a PriceCheck record and updates the watch.
     */
    public function check(ProductWatch $watch): PriceCheck
    {
        $this->logger->info("Checking price for watch #{$watch->getId()}: {$watch->getUrl()}");

        // Fetch the page
        $scrapeResult = $this->httpEngine->fetch($watch->getUrl());

        $priceCheck = new PriceCheck();
        $priceCheck->setProductWatch($watch);
        $priceCheck->setHttpStatus($scrapeResult->httpStatus);
        $priceCheck->setDurationMs($scrapeResult->durationMs);
        $priceCheck->setCheckedAt(new \DateTimeImmutable());

        $watch->setLastCheckedAt(new \DateTimeImmutable());

        if (!$scrapeResult->success) {
            $priceCheck->setWasSuccessful(false);
            $priceCheck->setErrorMessage($scrapeResult->error);
            $watch->incrementFailures();
            
            $this->logger->warning("Fetch failed for watch #{$watch->getId()}: {$scrapeResult->error}");
        } else {
            // Extract price from HTML
            $extractResult = $this->priceExtractor->extract(
                $scrapeResult->html,
                $watch->getPriceSelector()
            );

            if (!$extractResult->success) {
                $priceCheck->setWasSuccessful(false);
                $priceCheck->setErrorMessage($extractResult->error);
                $watch->incrementFailures();
                
                $this->logger->warning("Extraction failed for watch #{$watch->getId()}: {$extractResult->error}");
            } else {
                $priceCheck->setWasSuccessful(true);
                $priceCheck->setPrice($extractResult->price);
                $priceCheck->setRawText($extractResult->rawText);
                
                $watch->resetFailures();
                $watch->setLastSuccessfulCheckAt(new \DateTimeImmutable());
                $watch->setLastSeenRawText($extractResult->rawText);

                // Update price on watch (handles debounce)
                $priceChanged = $watch->updatePrice($extractResult->price);
                
                if ($watch->getOriginalPrice() === null) {
                    $watch->setOriginalPrice($extractResult->price);
                }

                $this->logger->info(
                    "Price check successful for watch #{$watch->getId()}: {$extractResult->price}" .
                    ($priceChanged ? " (CHANGED)" : "")
                );
            }
        }

        // Schedule next check
        $watch->scheduleNextCheck();

        // Persist
        $this->entityManager->persist($priceCheck);
        $this->entityManager->flush();

        return $priceCheck;
    }
}
