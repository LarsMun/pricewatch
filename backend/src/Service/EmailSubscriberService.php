<?php

namespace App\Service;

use App\Entity\EmailSubscriber;
use App\Entity\Notification;
use App\Entity\ProductWatch;
use App\Repository\EmailSubscriberRepository;
use App\Repository\ProductWatchRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class EmailSubscriberService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
        private readonly EmailSubscriberRepository $emailSubscriberRepository,
        private readonly ProductWatchRepository $productWatchRepository,
        private readonly LoggerInterface $logger,
        private readonly string $frontendUrl,
        private readonly string $fromEmail = 'noreply@shopq.app',
        private readonly string $fromName = 'ShopQ',
    ) {
    }

    /**
     * Subscribe an email to a product watch.
     */
    public function subscribe(string $email, int $productWatchId): EmailSubscriber
    {
        $watch = $this->productWatchRepository->find($productWatchId);

        if ($watch === null) {
            throw new \InvalidArgumentException('Product not found');
        }

        if (!$watch->isPublic() || !$watch->isActive()) {
            throw new \InvalidArgumentException('Product is not available for subscription');
        }

        // Check if already subscribed
        $existing = $this->emailSubscriberRepository->findByEmailAndWatch($email, $watch);
        if ($existing !== null) {
            if ($existing->isVerified()) {
                throw new \InvalidArgumentException('Email is already subscribed to this product');
            }
            // Resend verification
            $existing->generateVerificationToken();
            $this->entityManager->flush();
            $this->sendVerificationEmail($existing);
            return $existing;
        }

        // Create new subscription
        $subscriber = new EmailSubscriber();
        $subscriber->setEmail($email);
        $subscriber->setProductWatch($watch);
        $subscriber->generateVerificationToken();

        $this->entityManager->persist($subscriber);
        $this->entityManager->flush();

        $this->sendVerificationEmail($subscriber);
        $this->logger->info("Created subscription for {$email} on watch #{$productWatchId}");

        return $subscriber;
    }

    /**
     * Verify a subscription token.
     */
    public function verify(string $token): ?EmailSubscriber
    {
        $subscriber = $this->emailSubscriberRepository->findByVerificationToken($token);

        if ($subscriber === null || !$subscriber->isVerificationTokenValid($token)) {
            return null;
        }

        $subscriber->verify();

        // Update subscriber count on the watch
        $subscriber->getProductWatch()->incrementSubscriberCount();

        $this->entityManager->flush();

        $this->logger->info("Verified subscription for {$subscriber->getEmail()}");
        return $subscriber;
    }

    /**
     * Unsubscribe using the unsubscribe token.
     */
    public function unsubscribe(string $token): bool
    {
        $subscriber = $this->emailSubscriberRepository->findByUnsubscribeToken($token);

        if ($subscriber === null) {
            return false;
        }

        // Decrement subscriber count if was verified
        if ($subscriber->isVerified()) {
            $subscriber->getProductWatch()->decrementSubscriberCount();
        }

        $this->entityManager->remove($subscriber);
        $this->entityManager->flush();

        $this->logger->info("Unsubscribed {$subscriber->getEmail()}");
        return true;
    }

    /**
     * Notify all verified subscribers of a product about a price change.
     */
    public function notifySubscribers(ProductWatch $watch, Notification $notification): void
    {
        $subscribers = $this->emailSubscriberRepository->findVerifiedByWatch($watch);

        foreach ($subscribers as $subscriber) {
            try {
                $this->sendPriceAlertEmail($subscriber, $notification);
            } catch (\Throwable $e) {
                $this->logger->error(
                    "Failed to send alert to subscriber {$subscriber->getEmail()}: " . $e->getMessage()
                );
            }
        }

        $this->logger->info("Notified " . count($subscribers) . " subscribers for watch #{$watch->getId()}");
    }

    private function sendVerificationEmail(EmailSubscriber $subscriber): void
    {
        $verifyUrl = "{$this->frontendUrl}/verify-subscription?token={$subscriber->getVerificationToken()}";
        $watch = $subscriber->getProductWatch();

        $email = (new TemplatedEmail())
            ->from("{$this->fromName} <{$this->fromEmail}>")
            ->to($subscriber->getEmail())
            ->subject('Bevestig je prijsalert - ShopQ')
            ->htmlTemplate('emails/subscription_verification.html.twig')
            ->context([
                'verifyUrl' => $verifyUrl,
                'productName' => $watch->getProductName() ?: $watch->getDomain(),
                'productUrl' => $watch->getUrl(),
                'imageUrl' => $watch->getImageUrl(),
                'currentPrice' => $watch->getCurrentPrice(),
                'currency' => $watch->getCurrency(),
            ]);

        $this->mailer->send($email);
    }

    private function sendPriceAlertEmail(EmailSubscriber $subscriber, Notification $notification): void
    {
        $watch = $subscriber->getProductWatch();
        $unsubscribeUrl = "{$this->frontendUrl}/unsubscribe?token={$subscriber->getUnsubscribeToken()}";

        $templateName = match ($notification->getType()->value) {
            'price_decrease' => 'emails/subscriber_price_decrease.html.twig',
            'price_increase' => 'emails/subscriber_price_increase.html.twig',
            default => null,
        };

        if ($templateName === null) {
            return; // Don't notify subscribers about site_broken
        }

        $subject = match ($notification->getType()->value) {
            'price_decrease' => 'Prijsdaling: ' . ($watch->getProductName() ?: $watch->getDomain()),
            'price_increase' => 'Prijsstijging: ' . ($watch->getProductName() ?: $watch->getDomain()),
            default => 'Prijsalert - ShopQ',
        };

        $email = (new TemplatedEmail())
            ->from("{$this->fromName} <{$this->fromEmail}>")
            ->to($subscriber->getEmail())
            ->subject($subject)
            ->htmlTemplate($templateName)
            ->context([
                'productName' => $watch->getProductName() ?: $watch->getDomain(),
                'productUrl' => $watch->getUrl(),
                'imageUrl' => $watch->getImageUrl(),
                'oldPrice' => $notification->getOldPrice(),
                'newPrice' => $notification->getNewPrice(),
                'priceChangePercent' => $notification->getPriceChangePercentage(),
                'currency' => $watch->getCurrency(),
                'unsubscribeUrl' => $unsubscribeUrl,
            ]);

        $this->mailer->send($email);
    }
}
