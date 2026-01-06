<?php

namespace App\Message;

/**
 * Message to trigger an async price check for a specific watch.
 */
final class CheckPriceMessage
{
    public function __construct(
        public readonly int $watchId,
    ) {}
}
