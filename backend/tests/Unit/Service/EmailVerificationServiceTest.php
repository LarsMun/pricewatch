<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;

class EmailVerificationServiceTest extends TestCase
{
    private EmailVerificationService $service;
    private MockObject&MailerInterface $mailer;
    private MockObject&EntityManagerInterface $em;
    private MockObject&UserRepository $userRepo;
    private MockObject&LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new EmailVerificationService(
            $this->mailer,
            $this->em,
            $this->userRepo,
            $this->logger,
            'https://example.com'
        );
    }

    public function testSendVerificationEmailGeneratesToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $this->assertNull($user->getVerificationToken());

        $this->em->expects($this->once())->method('flush');
        $this->mailer->expects($this->once())->method('send');

        $this->service->sendVerificationEmail($user);

        $this->assertNotNull($user->getVerificationToken());
        $this->assertNotNull($user->getVerificationExpiresAt());
    }

    public function testVerifyTokenSuccessMarksVerified(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->generateVerificationToken();
        $token = $user->getVerificationToken();

        $this->userRepo->method('findOneBy')
            ->with(['verificationToken' => $token])
            ->willReturn($user);

        $this->em->expects($this->once())->method('flush');

        $result = $this->service->verifyToken($token);

        $this->assertSame($user, $result);
        $this->assertTrue($user->isVerified());
        $this->assertNull($user->getVerificationToken());
    }

    public function testVerifyTokenFailsWithInvalidToken(): void
    {
        $this->userRepo->method('findOneBy')
            ->willReturn(null);

        $result = $this->service->verifyToken('invalid-token');

        $this->assertNull($result);
    }

    public function testVerifyTokenFailsWithExpiredToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->generateVerificationToken();
        $token = $user->getVerificationToken();

        // Expire the token using reflection
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('verificationExpiresAt');
        $property->setAccessible(true);
        $property->setValue($user, new \DateTimeImmutable('-1 hour'));

        $this->userRepo->method('findOneBy')
            ->with(['verificationToken' => $token])
            ->willReturn($user);

        $result = $this->service->verifyToken($token);

        $this->assertNull($result);
        $this->assertFalse($user->isVerified());
    }

    public function testResendVerificationEmailSucceedsForUnverified(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $this->assertFalse($user->isVerified());

        $this->mailer->expects($this->once())->method('send');

        $result = $this->service->resendVerificationEmail($user);

        $this->assertTrue($result);
    }

    public function testResendVerificationEmailFailsIfAlreadyVerified(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->verify();

        $this->mailer->expects($this->never())->method('send');

        $result = $this->service->resendVerificationEmail($user);

        $this->assertFalse($result);
    }
}
