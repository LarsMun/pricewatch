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
