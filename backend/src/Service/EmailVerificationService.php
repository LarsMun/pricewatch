<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class EmailVerificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
        private string $frontendUrl,
        private string $fromEmail = 'noreply@shopq.app',
        private string $fromName = 'ShopQ',
    ) {}

    public function sendVerificationEmail(User $user): void
    {
        $user->generateVerificationToken();
        $this->entityManager->flush();

        $this->sendEmail($user);
        $this->logger->info("Sent verification email to {$user->getEmail()}");
    }

    public function verifyToken(string $token): ?User
    {
        $user = $this->userRepository->findOneBy(['verificationToken' => $token]);

        if (!$user || !$user->isVerificationTokenValid($token)) {
            return null;
        }

        $user->verify();
        $this->entityManager->flush();

        $this->logger->info("User {$user->getEmail()} verified successfully");
        return $user;
    }

    public function resendVerificationEmail(User $user): bool
    {
        if ($user->isVerified()) {
            return false;
        }

        $this->sendVerificationEmail($user);
        return true;
    }

    private function sendEmail(User $user): void
    {
        $verifyUrl = $this->buildVerifyUrl($user->getVerificationToken());

        $email = (new TemplatedEmail())
            ->from("{$this->fromName} <{$this->fromEmail}>")
            ->to($user->getEmail())
            ->subject('Bevestig je e-mailadres - ShopQ')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'user' => $user,
                'verifyUrl' => $verifyUrl,
                'expiresAt' => $user->getVerificationExpiresAt(),
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error("Failed to send verification email: " . $e->getMessage());
            throw $e;
        }
    }

    private function buildVerifyUrl(?string $token): string
    {
        return "{$this->frontendUrl}/verify-email?token={$token}";
    }
}
