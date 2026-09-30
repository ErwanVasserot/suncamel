<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 190, unique: true)]
    private string $slug = '';

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 255)]
    private string $tagline = '';

    #[ORM\Column(type: 'text')]
    private string $summary = '';

    #[ORM\Column(length: 80)]
    private string $price = '';

    #[ORM\Column(length: 80)]
    private string $duration = '4 hours';

    #[ORM\Column(length: 190, nullable: true)]
    private ?string $collectionSlug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $heroImage = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $highlights = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $specs = [];

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $stockQuantity = 1;

    #[ORM\Column(type: 'integer', options: ['default' => 7900])]
    private int $halfDayAmount = 7900;

    #[ORM\Column(type: 'integer', options: ['default' => 7900])]
    private int $fullDayAmount = 7900;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    /**
     * @var Collection<int, ProductImage>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductImage::class, cascade: ['persist'], orphanRemoval: false)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $images;

    /**
     * @var Collection<int, PricingTier>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: PricingTier::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['minimumDays' => 'ASC'])]
    private Collection $pricingTiers;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->pricingTiers = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title ?: $this->slug;
    }

    public function getId(): ?int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): self { $this->slug = $slug; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getTagline(): string { return $this->tagline; }
    public function setTagline(string $tagline): self { $this->tagline = $tagline; return $this; }
    public function getSummary(): string { return $this->summary; }
    public function setSummary(string $summary): self { $this->summary = $summary; return $this; }
    public function getPrice(): string { return $this->price; }
    public function setPrice(string $price): self { $this->price = $price; return $this; }
    public function getDuration(): string { return $this->duration; }
    public function setDuration(string $duration): self { $this->duration = $duration; return $this; }
    public function getCollectionSlug(): ?string { return $this->collectionSlug; }
    public function setCollectionSlug(?string $collectionSlug): self { $this->collectionSlug = $collectionSlug; return $this; }
    public function getCoverImage(): ?string { return $this->coverImage; }
    public function setCoverImage(?string $coverImage): self { $this->coverImage = $coverImage; return $this; }
    public function getHeroImage(): ?string { return $this->heroImage; }
    public function setHeroImage(?string $heroImage): self { $this->heroImage = $heroImage; return $this; }
    public function getHighlights(): array { return $this->highlights ?? []; }
    public function setHighlights(?array $highlights): self { $this->highlights = $highlights ?? []; return $this; }
    public function getSpecs(): array { return $this->specs ?? []; }
    public function setSpecs(?array $specs): self { $this->specs = $specs ?? []; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }
    public function getStockQuantity(): int { return $this->stockQuantity; }
    public function setStockQuantity(int $stockQuantity): self { $this->stockQuantity = max(0, $stockQuantity); return $this; }
    public function getHalfDayAmount(): int { return $this->halfDayAmount; }
    public function setHalfDayAmount(int $halfDayAmount): self { $this->halfDayAmount = max(0, $halfDayAmount); return $this; }
    public function getFullDayAmount(): int { return $this->fullDayAmount; }
    public function setFullDayAmount(int $fullDayAmount): self { $this->fullDayAmount = max(0, $fullDayAmount); return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }

    /**
     * @return Collection<int, ProductImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    /** @return Collection<int, PricingTier> */
    public function getPricingTiers(): Collection { return $this->pricingTiers; }

    public function addPricingTier(PricingTier $pricingTier): self
    {
        if (!$this->pricingTiers->contains($pricingTier)) {
            $this->pricingTiers->add($pricingTier);
            $pricingTier->setProduct($this);
        }

        return $this;
    }

    public function removePricingTier(PricingTier $pricingTier): self
    {
        $this->pricingTiers->removeElement($pricingTier);

        return $this;
    }
}
