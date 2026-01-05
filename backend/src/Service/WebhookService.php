<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\ProductWatch;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WebhookService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {}

    public function sendNotification(User $user, ProductWatch $watch, Notification $notification): void
    {
        if ($user->getDiscordWebhookUrl()) {
            $this->sendDiscordWebhook($user->getDiscordWebhookUrl(), $watch, $notification);
        }

        if ($user->getSlackWebhookUrl()) {
            $this->sendSlackWebhook($user->getSlackWebhookUrl(), $watch, $notification);
        }
    }

    private function sendDiscordWebhook(string $url, ProductWatch $watch, Notification $notification): void
    {
        $productName = $watch->getProductName() ?: $watch->getDomain();
        $type = $notification->getType()->value;
        
        $embed = match ($type) {
            'price_decrease' => [
                'title' => "📉 Prijsdaling: {$productName}",
                'color' => 0x22c55e, // green
                'fields' => [
                    ['name' => 'Oude prijs', 'value' => "€{$notification->getOldPrice()}", 'inline' => true],
                    ['name' => 'Nieuwe prijs', 'value' => "€{$notification->getNewPrice()}", 'inline' => true],
                    ['name' => 'Besparing', 'value' => "{$notification->getPriceChangePercentage()}%", 'inline' => true],
                ],
                'url' => $watch->getUrl(),
            ],
            'price_increase' => [
                'title' => "📈 Prijsstijging: {$productName}",
                'color' => 0xef4444, // red
                'fields' => [
                    ['name' => 'Oude prijs', 'value' => "€{$notification->getOldPrice()}", 'inline' => true],
                    ['name' => 'Nieuwe prijs', 'value' => "€{$notification->getNewPrice()}", 'inline' => true],
                ],
                'url' => $watch->getUrl(),
            ],
            'site_broken' => [
                'title' => "⚠️ Site onbereikbaar: {$productName}",
                'color' => 0xf59e0b, // yellow
                'description' => 'De prijscheck is 5x mislukt. Controleer of de URL nog klopt.',
                'url' => $watch->getUrl(),
            ],
            default => null,
        };

        if (!$embed) {
            return;
        }

        try {
            $this->httpClient->request('POST', $url, [
                'json' => [
                    'username' => 'PrijsWacht',
                    'embeds' => [$embed],
                ],
            ]);
            $this->logger->info("Sent Discord webhook for watch #{$watch->getId()}");
        } catch (\Throwable $e) {
            $this->logger->error("Failed to send Discord webhook: " . $e->getMessage());
        }
    }

    private function sendSlackWebhook(string $url, ProductWatch $watch, Notification $notification): void
    {
        $productName = $watch->getProductName() ?: $watch->getDomain();
        $type = $notification->getType()->value;

        $blocks = match ($type) {
            'price_decrease' => [
                [
                    'type' => 'header',
                    'text' => ['type' => 'plain_text', 'text' => "📉 Prijsdaling: {$productName}"],
                ],
                [
                    'type' => 'section',
                    'fields' => [
                        ['type' => 'mrkdwn', 'text' => "*Oude prijs:*\n€{$notification->getOldPrice()}"],
                        ['type' => 'mrkdwn', 'text' => "*Nieuwe prijs:*\n€{$notification->getNewPrice()}"],
                        ['type' => 'mrkdwn', 'text' => "*Besparing:*\n{$notification->getPriceChangePercentage()}%"],
                    ],
                ],
                [
                    'type' => 'actions',
                    'elements' => [
                        ['type' => 'button', 'text' => ['type' => 'plain_text', 'text' => 'Bekijk product'], 'url' => $watch->getUrl()],
                    ],
                ],
            ],
            'price_increase' => [
                [
                    'type' => 'header',
                    'text' => ['type' => 'plain_text', 'text' => "📈 Prijsstijging: {$productName}"],
                ],
                [
                    'type' => 'section',
                    'fields' => [
                        ['type' => 'mrkdwn', 'text' => "*Oude prijs:*\n€{$notification->getOldPrice()}"],
                        ['type' => 'mrkdwn', 'text' => "*Nieuwe prijs:*\n€{$notification->getNewPrice()}"],
                    ],
                ],
            ],
            'site_broken' => [
                [
                    'type' => 'header',
                    'text' => ['type' => 'plain_text', 'text' => "⚠️ Site onbereikbaar: {$productName}"],
                ],
                [
                    'type' => 'section',
                    'text' => ['type' => 'mrkdwn', 'text' => 'De prijscheck is 5x mislukt. Controleer of de URL nog klopt.'],
                ],
            ],
            default => null,
        };

        if (!$blocks) {
            return;
        }

        try {
            $this->httpClient->request('POST', $url, [
                'json' => ['blocks' => $blocks],
            ]);
            $this->logger->info("Sent Slack webhook for watch #{$watch->getId()}");
        } catch (\Throwable $e) {
            $this->logger->error("Failed to send Slack webhook: " . $e->getMessage());
        }
    }
}
