<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class DomainRateLimiter
{
    public function __construct(
        private RateLimiterFactory $domainScraperLimiter,
        private LoggerInterface $logger,
    ) {}

    /**
     * Try to consume a rate limit token for the given domain.
     * Returns true if allowed, false if rate limited.
     */
    public function consume(string $domain): bool
    {
        $limiter = $this->domainScraperLimiter->create($domain);
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter();
            $this->logger->warning(
                "Rate limit hit for domain {$domain}. Retry after: " . 
                ($retryAfter ? $retryAfter->format('c') : 'unknown')
            );
            return false;
        }

        return true;
    }

    /**
     * Check if a request to the domain would be allowed (without consuming).
     */
    public function isAllowed(string $domain): bool
    {
        $limiter = $this->domainScraperLimiter->create($domain);
        $limit = $limiter->consume(0);

        return $limit->getRemainingTokens() > 0;
    }

    /**
     * Get remaining tokens for a domain.
     */
    public function getRemainingTokens(string $domain): int
    {
        $limiter = $this->domainScraperLimiter->create($domain);
        $limit = $limiter->consume(0);

        return $limit->getRemainingTokens();
    }
}
