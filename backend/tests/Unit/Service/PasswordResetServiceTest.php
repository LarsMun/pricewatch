<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetServiceTest extends TestCase
{
    private PasswordResetService $service;
    private MockObject&MailerInterface $mailer;
    private MockObject&EntityManagerInterface $em;
    private MockObject&UserRepository $userRepo;
    private MockObject&UserPasswordHasherInterface $hasher;
    private MockObject&LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->hasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new PasswordResetService(
            $this->mailer,
            $this->em,
            $this->userRepo,
            $this->hasher,
            $this->logger,
            'https://example.com'
        );
    }

    public function testSendResetEmailGeneratesToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->userRepo->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($user);

        $this->em->expects($this->once())->method('flush');
        $this->mailer->expects($this->once())->method('send');

        $this->service->sendResetEmail('test@example.com');

        $this->assertNotNull($user->getPasswordResetToken());
        $this->assertNotNull($user->getPasswordResetExpiresAt());
    }

    public function testSendResetEmailSilentForNonExistentUser(): void
    {
        $this->userRepo->method('findOneBy')
            ->willReturn(null);

        // Should NOT send email
        $this->mailer->expects($this->never())->method('send');
        // Should log info message
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('non-existent email'));

        // Should not throw exception
        $this->service->sendResetEmail('nonexistent@example.com');
    }

    public function testValidateTokenReturnsUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->generatePasswordResetToken();
        $token = $user->getPasswordResetToken();

        $this->userRepo->method('findOneBy')
            ->with(['passwordResetToken' => $token])
            ->willReturn($user);

        $result = $this->service->validateToken($token);

        $this->assertSame($user, $result);
    }

    public function testValidateTokenReturnsNullForInvalidToken(): void
    {
        $this->userRepo->method('findOneBy')
            ->willReturn(null);

        $result = $this->service->validateToken('invalid-token');

        $this->assertNull($result);
    }

    public function testValidateTokenReturnsNullForExpiredToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->generatePasswordResetToken();
        $token = $user->getPasswordResetToken();

        // Expire the token using reflection
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('passwordResetExpiresAt');
        $property->setAccessible(true);
        $property->setValue($user, new \DateTimeImmutable('-1 minute'));

        $this->userRepo->method('findOneBy')
            ->with(['passwordResetToken' => $token])
            ->willReturn($user);

        $result = $this->service->validateToken($token);

        $this->assertNull($result);
    }

    public function testResetPasswordSuccess(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('old-hash');
        $user->generatePasswordResetToken();
        $token = $user->getPasswordResetToken();

        $this->userRepo->method('findOneBy')
            ->with(['passwordResetToken' => $token])
            ->willReturn($user);

        $this->hasher->method('hashPassword')
            ->with($user, 'newpassword123')
            ->willReturn('new-hash');

        $this->em->expects($this->once())->method('flush');

        $result = $this->service->resetPassword($token, 'newpassword123');

        $this->assertTrue($result);
        $this->assertSame('new-hash', $user->getPassword());
        $this->assertNull($user->getPasswordResetToken());
    }

    public function testResetPasswordFailsWithInvalidToken(): void
    {
        $this->userRepo->method('findOneBy')
            ->willReturn(null);

        $this->hasher->expects($this->never())->method('hashPassword');

        $result = $this->service->resetPassword('invalid-token', 'newpassword123');

        $this->assertFalse($result);
    }

    public function testResetPasswordClearsToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->generatePasswordResetToken();
        $token = $user->getPasswordResetToken();
        $this->assertNotNull($token);

        $this->userRepo->method('findOneBy')
            ->with(['passwordResetToken' => $token])
            ->willReturn($user);

        $this->hasher->method('hashPassword')
            ->willReturn('new-hash');

        $this->service->resetPassword($token, 'newpassword123');

        $this->assertNull($user->getPasswordResetToken());
        $this->assertNull($user->getPasswordResetExpiresAt());
    }
}
