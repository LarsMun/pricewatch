<?php

namespace App\Scraper;

class ScrapeResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $html = null,
        public readonly ?int $httpStatus = null,
        public readonly ?string $error = null,
        public readonly ?int $durationMs = null,
    ) {}

    public static function success(string $html, int $httpStatus, int $durationMs): self
    {
        return new self(
            success: true,
            html: $html,
            httpStatus: $httpStatus,
            durationMs: $durationMs,
        );
    }

    public static function failure(string $error, ?int $httpStatus = null, ?int $durationMs = null): self
    {
        return new self(
            success: false,
            error: $error,
            httpStatus: $httpStatus,
            durationMs: $durationMs,
        );
    }
}

interface ScrapeEngineInterface
{
    public function fetch(string $url): ScrapeResult;
}
