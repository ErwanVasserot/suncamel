<?php

namespace App\Entity;

use App\Repository\FaqItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FaqItemRepository::class)]
class FaqItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $category = 'General';

    #[ORM\Column(length: 255)]
    private string $question = '';

    #[ORM\Column(type: 'text')]
    private string $answerHtml = '';

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    public function __toString(): string
    {
        return $this->question;
    }

    public function getId(): ?int { return $this->id; }
    public function getCategory(): string { return $this->category; }
    public function setCategory(string $category): self { $this->category = $category; return $this; }
    public function getQuestion(): string { return $this->question; }
    public function setQuestion(string $question): self { $this->question = $question; return $this; }
    public function getAnswerHtml(): string { return $this->answerHtml; }
    public function setAnswerHtml(string $answerHtml): self { $this->answerHtml = $answerHtml; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
}
