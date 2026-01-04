<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetService
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private LoggerInterface $logger,
        private string $frontendUrl,
        private string $fromEmail = 'noreply@prijswacht.nl',
        private string $fromName = 'PrijsWacht',
    ) {}

    /**
     * Send password reset email. Silent if user not found (security).
     */
    public function sendResetEmail(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $this->logger->info("Password reset requested for non-existent email: {$email}");
            return;
        }

        $user->generatePasswordResetToken();
        $this->entityManager->flush();

        $this->sendEmail($user);
        $this->logger->info("Sent password reset email to {$user->getEmail()}");
    }

    /**
     * Validate a password reset token.
     */
    public function validateToken(string $token): ?User
    {
        $user = $this->userRepository->findOneBy(['passwordResetToken' => $token]);

        if (!$user || !$user->isPasswordResetTokenValid($token)) {
            return null;
        }

        return $user;
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = $this->validateToken($token);

        if (!$user) {
            return false;
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user->clearPasswordResetToken();
        $this->entityManager->flush();

        $this->logger->info("Password reset completed for {$user->getEmail()}");
        return true;
    }

    private function sendEmail(User $user): void
    {
        $resetUrl = $this->buildResetUrl($user->getPasswordResetToken());

        $email = (new TemplatedEmail())
            ->from("{$this->fromName} <{$this->fromEmail}>")
            ->to($user->getEmail())
            ->subject('Wachtwoord resetten - PrijsWacht')
            ->htmlTemplate('emails/password_reset.html.twig')
            ->context([
                'user' => $user,
                'resetUrl' => $resetUrl,
                'expiresAt' => $user->getPasswordResetExpiresAt(),
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error("Failed to send password reset email: " . $e->getMessage());
            throw $e;
        }
    }

    private function buildResetUrl(?string $token): string
    {
        return "{$this->frontendUrl}/reset-password?token={$token}";
    }
}
