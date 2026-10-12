<?php

namespace App\Entity;

use App\Repository\BookingEmailContentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingEmailContentRepository::class)]
class BookingEmailContent
{
    public const SINGLETON_ID = 1;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $confirmationMessage = '<p>Thank you for booking with SunCamel. Your payment has been received.</p>';

    #[ORM\Column(type: Types::TEXT)]
    private string $cancellationMessage = '<p>Your SunCamel booking has been cancelled.</p>';

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getConfirmationMessage(): string { return $this->confirmationMessage; }
    public function setConfirmationMessage(string $message): self { $this->confirmationMessage = $message; $this->touch(); return $this; }
    public function getCancellationMessage(): string { return $this->cancellationMessage; }
    public function setCancellationMessage(string $message): self { $this->cancellationMessage = $message; $this->touch(); return $this; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    private function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }
}
