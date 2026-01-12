<?php

namespace App\Entity;

use App\Repository\CollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CollectionRepository::class)]
#[ORM\Table(name: 'collection')]
#[ORM\Index(columns: ['user_id'], name: 'idx_collection_user')]
class Collection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'collections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $description = null;

    /** @var DoctrineCollection<int, ProductWatch> */
    #[ORM\ManyToMany(targetEntity: ProductWatch::class, inversedBy: 'collections')]
    #[ORM\JoinTable(name: 'collection_product_watch')]
    private DoctrineCollection $productWatches;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isPublic = false;

    public function __construct()
    {
        $this->productWatches = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /** @return DoctrineCollection<int, ProductWatch> */
    public function getProductWatches(): DoctrineCollection
    {
        return $this->productWatches;
    }

    public function addProductWatch(ProductWatch $productWatch): static
    {
        if (!$this->productWatches->contains($productWatch)) {
            $this->productWatches->add($productWatch);
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function removeProductWatch(ProductWatch $productWatch): static
    {
        if ($this->productWatches->removeElement($productWatch)) {
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function hasProductWatch(ProductWatch $productWatch): bool
    {
        return $this->productWatches->contains($productWatch);
    }

    public function getWatchCount(): int
    {
        return $this->productWatches->count();
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getSlug(): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->name), '-'));
    }
}
