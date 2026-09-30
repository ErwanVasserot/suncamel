<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class BookingItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Booking $booking = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column]
    private \DateTimeImmutable $pickupAt;

    #[ORM\Column]
    private \DateTimeImmutable $returnAt;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column]
    private int $unitAmount = 0;

    #[ORM\Column]
    private int $dayCount = 1;

    public function getId(): ?int { return $this->id; }
    public function getBooking(): ?Booking { return $this->booking; }
    public function setBooking(Booking $booking): self { $this->booking = $booking; return $this; }
    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(Product $product): self { $this->product = $product; return $this; }
    public function getPickupAt(): \DateTimeImmutable { return $this->pickupAt; }
    public function setPickupAt(\DateTimeImmutable $pickupAt): self { $this->pickupAt = $pickupAt; return $this; }
    public function getReturnAt(): \DateTimeImmutable { return $this->returnAt; }
    public function setReturnAt(\DateTimeImmutable $returnAt): self { $this->returnAt = $returnAt; return $this; }
    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): self { $this->quantity = max(1, $quantity); return $this; }
    public function getUnitAmount(): int { return $this->unitAmount; }
    public function setUnitAmount(int $unitAmount): self { $this->unitAmount = $unitAmount; return $this; }
    public function getDayCount(): int { return $this->dayCount; }
    public function setDayCount(int $dayCount): self { $this->dayCount = max(1, $dayCount); return $this; }
    public function getTotalAmount(): int { return $this->unitAmount * $this->dayCount * $this->quantity; }
    public function getPeriodLabel(): string
    {
        if ($this->dayCount > 1) {
            return $this->dayCount . ' days';
        }

        $start = $this->pickupAt->format('H:i');
        $end = $this->returnAt->format('H:i');

        return match ([$start, $end]) {
            ['08:00', '12:00'] => 'Morning (08:00–12:00)',
            ['12:00', '16:00'] => 'Afternoon (12:00–16:00)',
            default => 'Full day (08:00–16:00)',
        };
    }
}
