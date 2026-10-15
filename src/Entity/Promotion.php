<?php

namespace App\Entity;

use App\Repository\PromotionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PromotionRepository::class)]
#[UniqueEntity(fields: ['code'], message: 'Ce code promotionnel existe déjà.')]
class Promotion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[A-Z0-9_-]+$/', message: 'Utilisez uniquement des lettres, chiffres, tirets et underscores.')]
    private string $code = '';

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 99)]
    private int $discountPercent = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    public function __toString(): string
    {
        return sprintf('%s — %d%%', $this->code, $this->discountPercent);
    }

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): self
    {
        $this->code = strtoupper(trim($code));

        return $this;
    }
    public function getDiscountPercent(): int { return $this->discountPercent; }
    public function setDiscountPercent(int $discountPercent): self
    {
        $this->discountPercent = $discountPercent;

        return $this;
    }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
}
