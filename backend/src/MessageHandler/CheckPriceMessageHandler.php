<?php

namespace App\MessageHandler;

use App\Message\CheckPriceMessage;
use App\Repository\ProductWatchRepository;
use App\Service\PriceCheckService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CheckPriceMessageHandler
{
    public function __construct(
        private ProductWatchRepository $watchRepository,
        private PriceCheckService $priceCheckService,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(CheckPriceMessage $message): void
    {
        $watch = $this->watchRepository->find($message->watchId);

        if ($watch === null) {
            $this->logger->warning('Watch not found for async price check', [
                'watchId' => $message->watchId,
            ]);
            return;
        }

        if (!$watch->isActive()) {
            $this->logger->debug('Watch is not active, skipping price check', [
                'watchId' => $message->watchId,
            ]);
            return;
        }

        try {
            $this->priceCheckService->check($watch);
            $this->logger->info('Async price check completed', [
                'watchId' => $message->watchId,
                'domain' => $watch->getDomain(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Async price check failed', [
                'watchId' => $message->watchId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
