<?php

namespace App\Entity;

use App\Repository\EmailSubscriberRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmailSubscriberRepository::class)]
#[ORM\Table(name: 'email_subscriber')]
#[ORM\UniqueConstraint(name: 'unique_email_watch', columns: ['email', 'product_watch_id'])]
#[ORM\Index(columns: ['email'], name: 'idx_subscriber_email')]
#[ORM\Index(columns: ['verification_token'], name: 'idx_verification_token')]
#[ORM\Index(columns: ['unsubscribe_token'], name: 'idx_unsubscribe_token')]
class EmailSubscriber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProductWatch $productWatch;

    #[ORM\Column(length: 64)]
    private string $unsubscribeToken;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $verificationExpiresAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->unsubscribeToken = bin2hex(random_bytes(32));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getProductWatch(): ProductWatch
    {
        return $this->productWatch;
    }

    public function setProductWatch(ProductWatch $productWatch): static
    {
        $this->productWatch = $productWatch;
        return $this;
    }

    public function getUnsubscribeToken(): string
    {
        return $this->unsubscribeToken;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function getVerificationExpiresAt(): ?\DateTimeImmutable
    {
        return $this->verificationExpiresAt;
    }

    public function generateVerificationToken(): void
    {
        $this->verificationToken = bin2hex(random_bytes(32));
        $this->verificationExpiresAt = new \DateTimeImmutable('+24 hours');
    }

    public function clearVerificationToken(): void
    {
        $this->verificationToken = null;
        $this->verificationExpiresAt = null;
    }

    public function isVerificationTokenValid(string $token): bool
    {
        if ($this->verificationToken === null) {
            return false;
        }

        if (!hash_equals($this->verificationToken, $token)) {
            return false;
        }

        return $this->verificationExpiresAt !== null
            && $this->verificationExpiresAt > new \DateTimeImmutable();
    }

    public function verify(): void
    {
        $this->isVerified = true;
        $this->clearVerificationToken();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
