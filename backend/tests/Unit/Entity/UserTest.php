<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    // === Verification Token Tests ===

    public function testGenerateVerificationToken(): void
    {
        $user = new User();
        $this->assertNull($user->getVerificationToken());
        $this->assertNull($user->getVerificationExpiresAt());

        $user->generateVerificationToken();

        $this->assertNotNull($user->getVerificationToken());
        $this->assertSame(64, strlen($user->getVerificationToken())); // 32 bytes = 64 hex chars
        $this->assertNotNull($user->getVerificationExpiresAt());

        // Expiry should be ~24 hours from now
        $expectedMin = new \DateTimeImmutable('+23 hours');
        $expectedMax = new \DateTimeImmutable('+25 hours');
        $this->assertGreaterThan($expectedMin, $user->getVerificationExpiresAt());
        $this->assertLessThan($expectedMax, $user->getVerificationExpiresAt());
    }

    public function testIsVerificationTokenValidWithCorrectToken(): void
    {
        $user = new User();
        $user->generateVerificationToken();
        $token = $user->getVerificationToken();

        $this->assertTrue($user->isVerificationTokenValid($token));
    }

    public function testIsVerificationTokenValidWithWrongToken(): void
    {
        $user = new User();
        $user->generateVerificationToken();

        $this->assertFalse($user->isVerificationTokenValid('wrong-token'));
    }

    public function testIsVerificationTokenValidWhenExpired(): void
    {
        $user = new User();
        $user->generateVerificationToken();
        $token = $user->getVerificationToken();

        // Use reflection to set expired date
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('verificationExpiresAt');
        $property->setAccessible(true);
        $property->setValue($user, new \DateTimeImmutable('-1 hour'));

        $this->assertFalse($user->isVerificationTokenValid($token));
    }

    public function testIsVerificationTokenValidWhenNoToken(): void
    {
        $user = new User();

        $this->assertFalse($user->isVerificationTokenValid('any-token'));
    }

    public function testClearVerificationToken(): void
    {
        $user = new User();
        $user->generateVerificationToken();
        $this->assertNotNull($user->getVerificationToken());

        $user->clearVerificationToken();

        $this->assertNull($user->getVerificationToken());
        $this->assertNull($user->getVerificationExpiresAt());
    }

    public function testVerify(): void
    {
        $user = new User();
        $this->assertFalse($user->isVerified());
        $user->generateVerificationToken();

        $user->verify();

        $this->assertTrue($user->isVerified());
        $this->assertNull($user->getVerificationToken());
        $this->assertNull($user->getVerificationExpiresAt());
    }

    // === Password Reset Token Tests ===

    public function testGeneratePasswordResetToken(): void
    {
        $user = new User();
        $this->assertNull($user->getPasswordResetToken());
        $this->assertNull($user->getPasswordResetExpiresAt());

        $user->generatePasswordResetToken();

        $this->assertNotNull($user->getPasswordResetToken());
        $this->assertSame(64, strlen($user->getPasswordResetToken())); // 32 bytes = 64 hex chars
        $this->assertNotNull($user->getPasswordResetExpiresAt());

        // Expiry should be ~1 hour from now
        $expectedMin = new \DateTimeImmutable('+55 minutes');
        $expectedMax = new \DateTimeImmutable('+65 minutes');
        $this->assertGreaterThan($expectedMin, $user->getPasswordResetExpiresAt());
        $this->assertLessThan($expectedMax, $user->getPasswordResetExpiresAt());
    }

    public function testIsPasswordResetTokenValidWithCorrectToken(): void
    {
        $user = new User();
        $user->generatePasswordResetToken();
        $token = $user->getPasswordResetToken();

        $this->assertTrue($user->isPasswordResetTokenValid($token));
    }

    public function testIsPasswordResetTokenValidWithWrongToken(): void
    {
        $user = new User();
        $user->generatePasswordResetToken();

        $this->assertFalse($user->isPasswordResetTokenValid('wrong-token'));
    }

    public function testIsPasswordResetTokenValidWhenExpired(): void
    {
        $user = new User();
        $user->generatePasswordResetToken();
        $token = $user->getPasswordResetToken();

        // Use reflection to set expired date
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('passwordResetExpiresAt');
        $property->setAccessible(true);
        $property->setValue($user, new \DateTimeImmutable('-1 minute'));

        $this->assertFalse($user->isPasswordResetTokenValid($token));
    }

    public function testClearPasswordResetToken(): void
    {
        $user = new User();
        $user->generatePasswordResetToken();
        $this->assertNotNull($user->getPasswordResetToken());

        $user->clearPasswordResetToken();

        $this->assertNull($user->getPasswordResetToken());
        $this->assertNull($user->getPasswordResetExpiresAt());
    }

    // === Role Tests ===

    public function testDefaultRoleIsUser(): void
    {
        $user = new User();
        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
    }

    public function testAddCustomRole(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles); // Always includes ROLE_USER
    }

    public function testRolesAreUnique(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER', 'ROLE_USER', 'ROLE_ADMIN']);

        $roles = $user->getRoles();

        $this->assertCount(2, $roles);
    }

    // === Basic Property Tests ===

    public function testSetAndGetEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('test@example.com', $user->getUserIdentifier());
    }

    public function testCreatedAtIsSetOnConstruct(): void
    {
        $before = new \DateTimeImmutable();
        $user = new User();
        $after = new \DateTimeImmutable();

        $this->assertNotNull($user->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $user->getCreatedAt());
        $this->assertLessThanOrEqual($after, $user->getCreatedAt());
    }

    public function testNewUserIsNotVerified(): void
    {
        $user = new User();
        $this->assertFalse($user->isVerified());
    }
}
