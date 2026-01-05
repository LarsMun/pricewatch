<?php

namespace App\Tests\Unit\Service;

use App\Entity\Notification;
use App\Entity\ProductWatch;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Service\NotificationService;
use App\Service\WebhookService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;

class NotificationServiceTest extends TestCase
{
    private NotificationService $service;
    private MockObject&MailerInterface $mailer;
    private MockObject&EntityManagerInterface $em;
    private MockObject&LoggerInterface $logger;
    private MockObject&WebhookService $webhookService;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->webhookService = $this->createMock(WebhookService::class);

        $this->service = new NotificationService(
            $this->mailer,
            $this->em,
            $this->logger,
            $this->webhookService
        );
    }

    private function createTestWatch(): ProductWatch
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $watch = new ProductWatch();
        $watch->setUrl('https://example.com/product');
        $watch->setProductName('Test Product');
        $watch->setPriceSelector('.price');
        $watch->setUser($user);

        return $watch;
    }

    public function testNotifyPriceDecreaseCreatesNotification(): void
    {
        $watch = $this->createTestWatch();

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->mailer->expects($this->once())->method('send');

        $notification = $this->service->notifyPriceDecrease($watch, '99.99', '79.99');

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertSame(NotificationType::PRICE_DECREASE, $notification->getType());
        $this->assertSame('99.99', $notification->getOldPrice());
        $this->assertSame('79.99', $notification->getNewPrice());
        $this->assertSame($watch, $notification->getProductWatch());
    }

    public function testNotifyPriceIncreaseCreatesNotification(): void
    {
        $watch = $this->createTestWatch();

        $this->em->expects($this->once())->method('persist');
        $this->mailer->expects($this->once())->method('send');

        $notification = $this->service->notifyPriceIncrease($watch, '79.99', '99.99');

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertSame(NotificationType::PRICE_INCREASE, $notification->getType());
        $this->assertSame('79.99', $notification->getOldPrice());
        $this->assertSame('99.99', $notification->getNewPrice());
    }

    public function testNotifySiteBrokenCreatesNotification(): void
    {
        $watch = $this->createTestWatch();

        $this->em->expects($this->once())->method('persist');
        $this->mailer->expects($this->once())->method('send');

        $notification = $this->service->notifySiteBroken($watch);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertSame(NotificationType::SITE_BROKEN, $notification->getType());
        $this->assertNull($notification->getOldPrice());
        $this->assertNull($notification->getNewPrice());
    }

    public function testNotifyPriceDecreaseSendsEmail(): void
    {
        $watch = $this->createTestWatch();

        $this->mailer->expects($this->once())
            ->method('send');

        $this->service->notifyPriceDecrease($watch, '99.99', '79.99');
    }

    public function testNotifySiteBrokenLogsInfo(): void
    {
        $watch = $this->createTestWatch();

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('site broken notification'));

        $this->service->notifySiteBroken($watch);
    }
}
