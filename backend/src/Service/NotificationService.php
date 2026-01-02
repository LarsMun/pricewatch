<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\ProductWatch;
use App\Enum\NotificationType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private string $fromEmail = 'noreply@prijswacht.nl',
        private string $fromName = 'PrijsWacht',
    ) {}

    public function notifyPriceDecrease(ProductWatch $watch, string $oldPrice, string $newPrice): Notification
    {
        $notification = Notification::priceDecrease($watch, $oldPrice, $newPrice);
        $this->sendEmail($watch, $notification);
        $this->persist($notification);
        $this->logger->info("Sent price decrease notification for watch #{$watch->getId()}: {$oldPrice} -> {$newPrice}");
        return $notification;
    }

    public function notifyPriceIncrease(ProductWatch $watch, string $oldPrice, string $newPrice): Notification
    {
        $notification = Notification::priceIncrease($watch, $oldPrice, $newPrice);
        $this->sendEmail($watch, $notification);
        $this->persist($notification);
        $this->logger->info("Sent price increase notification for watch #{$watch->getId()}: {$oldPrice} -> {$newPrice}");
        return $notification;
    }

    public function notifySiteBroken(ProductWatch $watch): Notification
    {
        $notification = Notification::siteBroken($watch);
        $this->sendEmail($watch, $notification);
        $this->persist($notification);
        $this->logger->info("Sent site broken notification for watch #{$watch->getId()}");
        return $notification;
    }

    private function sendEmail(ProductWatch $watch, Notification $notification): void
    {
        $user = $watch->getUser();
        $type = $notification->getType();

        $email = (new TemplatedEmail())
            ->from("{$this->fromName} <{$this->fromEmail}>")
            ->to($user->getEmail())
            ->subject($this->getSubject($watch, $notification))
            ->htmlTemplate("emails/{$type->value}.html.twig")
            ->context([
                'watch' => $watch,
                'notification' => $notification,
                'user' => $user,
                'productName' => $watch->getProductName() ?: $watch->getDomain(),
                'oldPrice' => $notification->getOldPrice(),
                'newPrice' => $notification->getNewPrice(),
                'priceChangePercent' => $notification->getPriceChangePercentage(),
                'url' => $watch->getUrl(),
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error("Failed to send notification email: " . $e->getMessage());
            throw $e;
        }
    }

    private function getSubject(ProductWatch $watch, Notification $notification): string
    {
        $productName = $watch->getProductName() ?: $watch->getDomain();
        $type = $notification->getType();

        return match ($type) {
            NotificationType::PRICE_DECREASE => "Prijsdaling: {$productName}",
            NotificationType::PRICE_INCREASE => "Prijsstijging: {$productName}",
            NotificationType::SITE_BROKEN => "Site onbereikbaar: {$productName}",
        };
    }

    private function persist(Notification $notification): void
    {
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }
}
