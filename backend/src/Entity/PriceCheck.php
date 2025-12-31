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
