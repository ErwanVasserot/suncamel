<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true)]
    private string $reference = '';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private int $totalAmount = 0;

    #[ORM\Column]
    private int $subtotalAmount = 0;

    #[ORM\Column]
    private int $discountAmount = 0;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $promotionCode = null;

    #[ORM\Column(nullable: true)]
    private ?int $promotionPercent = null;

    #[ORM\Column(length: 3)]
    private string $currency = 'NZD';

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $stripeRefundId = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $stripeRefundStatus = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $refundedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    /** @var Collection<int, BookingItem> */
    #[ORM\OneToMany(mappedBy: 'booking', targetEntity: BookingItem::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = new \DateTimeImmutable('+31 minutes');
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getReference(): string { return $this->reference; }
    public function setReference(string $reference): self { $this->reference = $reference; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = strtolower(trim($email)); return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getTotalAmount(): int { return $this->totalAmount; }
    public function setTotalAmount(int $totalAmount): self
    {
        $this->totalAmount = max(0, $totalAmount);
        if ($this->subtotalAmount === 0) {
            $this->subtotalAmount = $this->totalAmount;
        }

        return $this;
    }
    public function getSubtotalAmount(): int { return $this->subtotalAmount; }
    public function getDiscountAmount(): int { return $this->discountAmount; }
    public function getPromotionCode(): ?string { return $this->promotionCode; }
    public function getPromotionPercent(): ?int { return $this->promotionPercent; }
    public function applyPromotion(string $code, int $percent, int $subtotal, int $discount): self
    {
        $this->promotionCode = strtoupper(trim($code));
        $this->promotionPercent = $percent;
        $this->subtotalAmount = max(0, $subtotal);
        $this->discountAmount = max(0, min($discount, $this->subtotalAmount));
        $this->totalAmount = $this->subtotalAmount - $this->discountAmount;

        return $this;
    }
    public function getCurrency(): string { return strtoupper($this->currency); }
    public function setCurrency(string $currency): self { $this->currency = strtoupper($currency); return $this; }
    public function getStripeSessionId(): ?string { return $this->stripeSessionId; }
    public function setStripeSessionId(?string $stripeSessionId): self { $this->stripeSessionId = $stripeSessionId; return $this; }
    public function getStripePaymentIntentId(): ?string { return $this->stripePaymentIntentId; }
    public function setStripePaymentIntentId(?string $stripePaymentIntentId): self { $this->stripePaymentIntentId = $stripePaymentIntentId; return $this; }
    public function getStripeRefundId(): ?string { return $this->stripeRefundId; }
    public function getStripeRefundStatus(): ?string { return $this->stripeRefundStatus; }
    public function setStripeRefundStatus(string $stripeRefundStatus): self { $this->stripeRefundStatus = $stripeRefundStatus; return $this; }
    public function getRefundedAt(): ?\DateTimeImmutable { return $this->refundedAt; }
    public function markRefunded(string $refundId, string $status): self
    {
        $this->stripeRefundId = $refundId;
        $this->stripeRefundStatus = $status;
        $this->refundedAt = new \DateTimeImmutable();

        return $this;
    }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function getPaidAt(): ?\DateTimeImmutable { return $this->paidAt; }
    public function markPaid(): self { $this->status = self::STATUS_PAID; $this->paidAt = new \DateTimeImmutable(); return $this; }
    /** @return Collection<int, BookingItem> */
    public function getItems(): Collection { return $this->items; }
    public function addItem(BookingItem $item): self { if (!$this->items->contains($item)) { $this->items->add($item); $item->setBooking($this); } return $this; }
}
