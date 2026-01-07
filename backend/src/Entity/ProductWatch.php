<?php

namespace App\Entity;

use App\Enum\CheckMethod;
use App\Repository\ProductWatchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductWatchRepository::class)]
#[ORM\Table(name: 'product_watch')]
#[ORM\Index(columns: ['next_check_at'], name: 'idx_next_check')]
#[ORM\Index(columns: ['domain'], name: 'idx_domain')]
#[ORM\Index(columns: ['is_active'], name: 'idx_active')]
#[ORM\Index(columns: ['user_id', 'is_active'], name: 'idx_user_active')]
class ProductWatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'productWatches')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 2048)]
    #[Assert\NotBlank]
    #[Assert\Url]
    private ?string $url = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $domain = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $productName = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    private ?string $priceSelector = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $productSelector = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 3, options: ['default' => 'EUR'])]
    private string $currency = 'EUR';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $currentPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $previousPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $originalPrice = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $lastSeenRawText = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $parseRuleJson = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $selectorContextHtml = null;

    #[ORM\Column(length: 20, enumType: CheckMethod::class, options: ['default' => 'http'])]
    private CheckMethod $checkMethod = CheckMethod::HTTP;

    #[ORM\Column(options: ['default' => 0])]
    private int $consecutiveFailures = 0;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $lastErrorMessage = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $nextCheckAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastCheckedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastSuccessfulCheckAt = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    /** @var Collection<int, PriceCheck> */
    #[ORM\OneToMany(targetEntity: PriceCheck::class, mappedBy: 'productWatch', orphanRemoval: true)]
    #[ORM\OrderBy(['checkedAt' => 'DESC'])]
    private Collection $priceChecks;

    /** @var Collection<int, Notification> */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'productWatch', orphanRemoval: true)]
    #[ORM\OrderBy(['sentAt' => 'DESC'])]
    private Collection $notifications;

    /** @var Collection<int, \App\Entity\Collection> */
    #[ORM\ManyToMany(targetEntity: \App\Entity\Collection::class, mappedBy: 'productWatches')]
    private Collection $collections;

    public function __construct()
    {
        $this->priceChecks = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->collections = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->scheduleNextCheck();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;
        $this->domain = parse_url($url, PHP_URL_HOST) ?? '';
        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(?string $productName): static
    {
        $this->productName = $productName;
        return $this;
    }

    public function getPriceSelector(): ?string
    {
        return $this->priceSelector;
    }

    public function setPriceSelector(string $priceSelector): static
    {
        $this->priceSelector = $priceSelector;
        return $this;
    }

    public function getProductSelector(): ?string
    {
        return $this->productSelector;
    }

    public function setProductSelector(?string $productSelector): static
    {
        $this->productSelector = $productSelector;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getCurrentPrice(): ?string
    {
        return $this->currentPrice;
    }

    public function setCurrentPrice(?string $currentPrice): static
    {
        $this->currentPrice = $currentPrice;
        return $this;
    }

    public function getPreviousPrice(): ?string
    {
        return $this->previousPrice;
    }

    public function setPreviousPrice(?string $previousPrice): static
    {
        $this->previousPrice = $previousPrice;
        return $this;
    }

    public function getOriginalPrice(): ?string
    {
        return $this->originalPrice;
    }

    public function setOriginalPrice(?string $originalPrice): static
    {
        $this->originalPrice = $originalPrice;
        return $this;
    }

    public function getLastSeenRawText(): ?string
    {
        return $this->lastSeenRawText;
    }

    public function setLastSeenRawText(?string $lastSeenRawText): static
    {
        $this->lastSeenRawText = $lastSeenRawText;
        return $this;
    }

    public function getParseRuleJson(): ?array
    {
        return $this->parseRuleJson;
    }

    public function setParseRuleJson(?array $parseRuleJson): static
    {
        $this->parseRuleJson = $parseRuleJson;
        return $this;
    }

    public function getSelectorContextHtml(): ?string
    {
        return $this->selectorContextHtml;
    }

    public function setSelectorContextHtml(?string $selectorContextHtml): static
    {
        $this->selectorContextHtml = $selectorContextHtml;
        return $this;
    }

    public function getCheckMethod(): CheckMethod
    {
        return $this->checkMethod;
    }

    public function setCheckMethod(CheckMethod $checkMethod): static
    {
        $this->checkMethod = $checkMethod;
        return $this;
    }

    public function getConsecutiveFailures(): int
    {
        return $this->consecutiveFailures;
    }

    public function incrementFailures(): static
    {
        $this->consecutiveFailures++;
        return $this;
    }

    public function resetFailures(): static
    {
        $this->consecutiveFailures = 0;
        return $this;
    }

    public function hasReachedFailureThreshold(): bool
    {
        return $this->consecutiveFailures >= 5;
    }

    public function getLastErrorMessage(): ?string
    {
        return $this->lastErrorMessage;
    }

    public function setLastErrorMessage(?string $lastErrorMessage): static
    {
        $this->lastErrorMessage = $lastErrorMessage;
        return $this;
    }

    public function getNextCheckAt(): ?\DateTimeImmutable
    {
        return $this->nextCheckAt;
    }

    public function setNextCheckAt(\DateTimeImmutable $nextCheckAt): static
    {
        $this->nextCheckAt = $nextCheckAt;
        return $this;
    }

    /**
     * Schedule next check: 12 hours + random jitter (0-60 min)
     */
    public function scheduleNextCheck(): static
    {
        $baseInterval = 12 * 60 * 60; // 12 hours in seconds
        $jitter = random_int(0, 60 * 60); // 0-60 minutes in seconds
        $this->nextCheckAt = new \DateTimeImmutable('+'.($baseInterval + $jitter).' seconds');
        return $this;
    }

    public function getLastCheckedAt(): ?\DateTimeImmutable
    {
        return $this->lastCheckedAt;
    }

    public function setLastCheckedAt(?\DateTimeImmutable $lastCheckedAt): static
    {
        $this->lastCheckedAt = $lastCheckedAt;
        return $this;
    }

    public function getLastSuccessfulCheckAt(): ?\DateTimeImmutable
    {
        return $this->lastSuccessfulCheckAt;
    }

    public function setLastSuccessfulCheckAt(?\DateTimeImmutable $lastSuccessfulCheckAt): static
    {
        $this->lastSuccessfulCheckAt = $lastSuccessfulCheckAt;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function pause(): static
    {
        $this->isActive = false;
        return $this;
    }

    public function resume(): static
    {
        $this->isActive = true;
        $this->scheduleNextCheck();
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

    /** @return Collection<int, PriceCheck> */
    public function getPriceChecks(): Collection
    {
        return $this->priceChecks;
    }

    public function addPriceCheck(PriceCheck $priceCheck): static
    {
        if (!$this->priceChecks->contains($priceCheck)) {
            $this->priceChecks->add($priceCheck);
            $priceCheck->setProductWatch($this);
        }
        return $this;
    }

    /** @return Collection<int, Notification> */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setProductWatch($this);
        }
        return $this;
    }

    /** @return Collection<int, \App\Entity\Collection> */
    public function getCollections(): Collection
    {
        return $this->collections;
    }

    /** @return int[] */
    public function getCollectionIds(): array
    {
        return $this->collections->map(fn($c) => $c->getId())->toArray();
    }

    /**
     * Process a new price, handling debounce logic.
     * Returns true if price actually changed (notification needed).
     */
    public function updatePrice(string $newPrice): bool
    {
        // Same as current - no change
        if ($this->currentPrice === $newPrice) {
            return false;
        }

        // Same as previous - flapping, ignore
        if ($this->previousPrice === $newPrice) {
            return false;
        }

        // Real change
        $this->previousPrice = $this->currentPrice;
        $this->currentPrice = $newPrice;
        return true;
    }
}
