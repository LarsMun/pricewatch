# ShopQ - Doctrine Entities

## Overzicht

Dit document bevat de complete Doctrine entity definities voor ShopQ (voorheen PrijsWacht). Alle entities gebruiken PHP 8 attributes syntax.

### Entities

| Entity | Tabel | Beschrijving |
|--------|-------|--------------|
| User | `user` | Gebruikersaccount |
| ProductWatch | `product_watch` | Gemonitorde productpagina |
| PriceCheck | `price_check` | Historische prijscheck |
| Notification | `notification` | Verstuurde notificatie |

---

## User Entity

```php
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
}
```

---

## ProductWatch Entity

```php
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

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    private ?string $priceSelector = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $productSelector = null;

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

    public function __construct()
    {
        $this->priceChecks = new ArrayCollection();
        $this->notifications = new ArrayCollection();
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

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
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
        
        $this->nextCheckAt = new \DateTimeImmutable('+' . ($baseInterval + $jitter) . ' seconds');
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
```

---

## PriceCheck Entity

```php
<?php

namespace App\Entity;

use App\Repository\PriceCheckRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PriceCheckRepository::class)]
#[ORM\Table(name: 'price_check')]
#[ORM\Index(columns: ['product_watch_id', 'checked_at'], name: 'idx_watch_checked')]
#[ORM\Index(columns: ['checked_at'], name: 'idx_checked_at')]
class PriceCheck
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'priceChecks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ProductWatch $productWatch = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $rawText = null;

    #[ORM\Column]
    private bool $wasSuccessful = false;

    #[ORM\Column(nullable: true)]
    private ?int $httpStatus = null;

    #[ORM\Column(nullable: true)]
    private ?int $durationMs = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $checkedAt = null;

    public function __construct()
    {
        $this->checkedAt = new \DateTimeImmutable();
    }

    public static function success(
        ProductWatch $watch,
        string $price,
        string $rawText,
        int $httpStatus,
        int $durationMs
    ): self {
        $check = new self();
        $check->productWatch = $watch;
        $check->price = $price;
        $check->rawText = $rawText;
        $check->wasSuccessful = true;
        $check->httpStatus = $httpStatus;
        $check->durationMs = $durationMs;
        
        return $check;
    }

    public static function failure(
        ProductWatch $watch,
        string $errorMessage,
        ?int $httpStatus = null,
        ?int $durationMs = null
    ): self {
        $check = new self();
        $check->productWatch = $watch;
        $check->wasSuccessful = false;
        $check->errorMessage = $errorMessage;
        $check->httpStatus = $httpStatus;
        $check->durationMs = $durationMs;
        
        return $check;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductWatch(): ?ProductWatch
    {
        return $this->productWatch;
    }

    public function setProductWatch(?ProductWatch $productWatch): static
    {
        $this->productWatch = $productWatch;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getRawText(): ?string
    {
        return $this->rawText;
    }

    public function setRawText(?string $rawText): static
    {
        $this->rawText = $rawText;
        return $this;
    }

    public function wasSuccessful(): bool
    {
        return $this->wasSuccessful;
    }

    public function setWasSuccessful(bool $wasSuccessful): static
    {
        $this->wasSuccessful = $wasSuccessful;
        return $this;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function setHttpStatus(?int $httpStatus): static
    {
        $this->httpStatus = $httpStatus;
        return $this;
    }

    public function getDurationMs(): ?int
    {
        return $this->durationMs;
    }

    public function setDurationMs(?int $durationMs): static
    {
        $this->durationMs = $durationMs;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getCheckedAt(): ?\DateTimeImmutable
    {
        return $this->checkedAt;
    }

    public function setCheckedAt(\DateTimeImmutable $checkedAt): static
    {
        $this->checkedAt = $checkedAt;
        return $this;
    }
}
```

---

## Notification Entity

```php
<?php

namespace App\Entity;

use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\Index(columns: ['product_watch_id', 'sent_at'], name: 'idx_watch_sent')]
#[ORM\Index(columns: ['type'], name: 'idx_type')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ProductWatch $productWatch = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $oldPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $newPrice = null;

    #[ORM\Column(length: 50, enumType: NotificationType::class)]
    private ?NotificationType $type = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $sentAt = null;

    public function __construct()
    {
        $this->sentAt = new \DateTimeImmutable();
    }

    public static function priceDecrease(
        ProductWatch $watch,
        string $oldPrice,
        string $newPrice
    ): self {
        $notification = new self();
        $notification->productWatch = $watch;
        $notification->oldPrice = $oldPrice;
        $notification->newPrice = $newPrice;
        $notification->type = NotificationType::PRICE_DECREASE;
        
        return $notification;
    }

    public static function priceIncrease(
        ProductWatch $watch,
        string $oldPrice,
        string $newPrice
    ): self {
        $notification = new self();
        $notification->productWatch = $watch;
        $notification->oldPrice = $oldPrice;
        $notification->newPrice = $newPrice;
        $notification->type = NotificationType::PRICE_INCREASE;
        
        return $notification;
    }

    public static function siteBroken(ProductWatch $watch): self
    {
        $notification = new self();
        $notification->productWatch = $watch;
        $notification->type = NotificationType::SITE_BROKEN;
        
        return $notification;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductWatch(): ?ProductWatch
    {
        return $this->productWatch;
    }

    public function setProductWatch(?ProductWatch $productWatch): static
    {
        $this->productWatch = $productWatch;
        return $this;
    }

    public function getOldPrice(): ?string
    {
        return $this->oldPrice;
    }

    public function setOldPrice(?string $oldPrice): static
    {
        $this->oldPrice = $oldPrice;
        return $this;
    }

    public function getNewPrice(): ?string
    {
        return $this->newPrice;
    }

    public function setNewPrice(?string $newPrice): static
    {
        $this->newPrice = $newPrice;
        return $this;
    }

    public function getType(): ?NotificationType
    {
        return $this->type;
    }

    public function setType(NotificationType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getPriceChangePercentage(): ?float
    {
        if ($this->oldPrice === null || $this->newPrice === null) {
            return null;
        }
        
        $old = (float) $this->oldPrice;
        $new = (float) $this->newPrice;
        
        if ($old === 0.0) {
            return null;
        }
        
        return round((($new - $old) / $old) * 100, 2);
    }
}
```

---

## Enums

### CheckMethod

```php
<?php

namespace App\Enum;

enum CheckMethod: string
{
    case HTTP = 'http';
    case BROWSER = 'browser';
}
```

### NotificationType

```php
<?php

namespace App\Enum;

enum NotificationType: string
{
    case PRICE_DECREASE = 'price_decrease';
    case PRICE_INCREASE = 'price_increase';
    case SITE_BROKEN = 'site_broken';
    
    public function label(): string
    {
        return match($this) {
            self::PRICE_DECREASE => 'Prijsdaling',
            self::PRICE_INCREASE => 'Prijsstijging',
            self::SITE_BROKEN => 'Site onbereikbaar',
        };
    }
    
    public function emoji(): string
    {
        return match($this) {
            self::PRICE_DECREASE => '📉',
            self::PRICE_INCREASE => '📈',
            self::SITE_BROKEN => '⚠️',
        };
    }
}
```

---

## Database Indexes Samenvatting

| Tabel | Index | Kolommen | Reden |
|-------|-------|----------|-------|
| `product_watch` | `idx_next_check` | `next_check_at` | Worker query |
| `product_watch` | `idx_domain` | `domain` | Rate limiting lookups |
| `product_watch` | `idx_active` | `is_active` | Filter actieve watches |
| `product_watch` | `idx_user_active` | `user_id`, `is_active` | User dashboard |
| `price_check` | `idx_watch_checked` | `product_watch_id`, `checked_at` | Prijshistorie per watch |
| `price_check` | `idx_checked_at` | `checked_at` | Cleanup oude records |
| `notification` | `idx_watch_sent` | `product_watch_id`, `sent_at` | Notificatie historie |
| `notification` | `idx_type` | `type` | Filter op type |

---

## Migrations Genereren

Na het aanmaken van de entities:

```bash
# Genereer migration
php bin/console make:migration

# Bekijk SQL (dry run)
php bin/console doctrine:migrations:migrate --dry-run

# Voer migration uit
php bin/console doctrine:migrations:migrate
```

---

## Mapstructuur

```
src/
├── Entity/
│   ├── User.php
│   ├── ProductWatch.php
│   ├── PriceCheck.php
│   └── Notification.php
├── Enum/
│   ├── CheckMethod.php
│   └── NotificationType.php
└── Repository/
    ├── UserRepository.php
    ├── ProductWatchRepository.php
    ├── PriceCheckRepository.php
    └── NotificationRepository.php
```

---

*Document versie: 1.1*
*Laatst bijgewerkt: 2026-01-04*
