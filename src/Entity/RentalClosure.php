<?php
namespace App\Entity;

use App\Repository\RentalClosureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RentalClosureRepository::class)]
#[Assert\Expression(expression: 'this.getEndDate() >= this.getStartDate()', message: 'La date de fin doit être postérieure ou égale à la date de début.')]
class RentalClosure
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $startDate;
    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $endDate;
    #[ORM\Column(length: 255, options: ['default' => 'Rental is closed'])]
    private string $message = 'Rental is closed';

    public function __construct() { $this->startDate = $this->endDate = new \DateTimeImmutable('today', new \DateTimeZone('Pacific/Auckland')); }
    public function __toString(): string { return $this->startDate->format('d/m/Y').' – '.$this->endDate->format('d/m/Y'); }
    public function getId(): ?int { return $this->id; }
    public function getStartDate(): \DateTimeImmutable { return $this->startDate; }
    public function setStartDate(\DateTimeImmutable $value): self { $this->startDate = $value; return $this; }
    public function getEndDate(): \DateTimeImmutable { return $this->endDate; }
    public function setEndDate(\DateTimeImmutable $value): self { $this->endDate = $value; return $this; }
    public function getMessage(): string { return $this->message; }
    public function setMessage(?string $value): self { $this->message = trim((string) $value) ?: 'Rental is closed'; return $this; }
}
