<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Dit e-mailadres is al in gebruik.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $verificationExpiresAt = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $passwordResetExpiresAt = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $discordWebhookUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $slackWebhookUrl = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    /** @var Collection<int, ProductWatch> */
    #[ORM\OneToMany(targetEntity: ProductWatch::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $productWatches;

    public function __construct()
    {
        $this->productWatches = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // Clear temporary sensitive data if any
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /** @return Collection<int, ProductWatch> */
    public function getProductWatches(): Collection
    {
        return $this->productWatches;
    }

    public function addProductWatch(ProductWatch $productWatch): static
    {
        if (!$this->productWatches->contains($productWatch)) {
            $this->productWatches->add($productWatch);
            $productWatch->setUser($this);
        }

        return $this;
    }

    public function removeProductWatch(ProductWatch $productWatch): static
    {
        if ($this->productWatches->removeElement($productWatch)) {
            if ($productWatch->getUser() === $this) {
                $productWatch->setUser(null);
            }
        }

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
        if ($this->verificationToken !== $token) {
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

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function getPasswordResetExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetExpiresAt;
    }

    public function generatePasswordResetToken(): void
    {
        $this->passwordResetToken = bin2hex(random_bytes(32));
        $this->passwordResetExpiresAt = new \DateTimeImmutable('+1 hour');
    }

    public function clearPasswordResetToken(): void
    {
        $this->passwordResetToken = null;
        $this->passwordResetExpiresAt = null;
    }

    public function isPasswordResetTokenValid(string $token): bool
    {
        if ($this->passwordResetToken !== $token) {
            return false;
        }

        return $this->passwordResetExpiresAt !== null
            && $this->passwordResetExpiresAt > new \DateTimeImmutable();
    }

    public function getDiscordWebhookUrl(): ?string
    {
        return $this->discordWebhookUrl;
    }

    public function setDiscordWebhookUrl(?string $discordWebhookUrl): static
    {
        $this->discordWebhookUrl = $discordWebhookUrl;

        return $this;
    }

    public function getSlackWebhookUrl(): ?string
    {
        return $this->slackWebhookUrl;
    }

    public function setSlackWebhookUrl(?string $slackWebhookUrl): static
    {
        $this->slackWebhookUrl = $slackWebhookUrl;

        return $this;
    }

    public function hasWebhooksConfigured(): bool
    {
        return $this->discordWebhookUrl !== null || $this->slackWebhookUrl !== null;
    }
}
