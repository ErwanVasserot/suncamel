<?php

namespace App\Entity;

use App\Repository\PricingTierRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PricingTierRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_product_minimum_days', columns: ['product_id', 'minimum_days'])]
class PricingTier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'pricingTiers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\Column]
    private int $minimumDays = 2;

    #[ORM\Column]
    private int $dailyAmount = 0;

    public function __toString(): string
    {
        return sprintf('%d+ jours — $%.2f/jour', $this->minimumDays, $this->dailyAmount / 100);
    }

    public function getId(): ?int { return $this->id; }
    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(Product $product): self { $this->product = $product; return $this; }
    public function getMinimumDays(): int { return $this->minimumDays; }
    public function setMinimumDays(int $minimumDays): self { $this->minimumDays = max(2, $minimumDays); return $this; }
    public function getDailyAmount(): int { return $this->dailyAmount; }
    public function setDailyAmount(int $dailyAmount): self { $this->dailyAmount = max(0, $dailyAmount); return $this; }
}
