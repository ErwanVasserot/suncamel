<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 120, unique: true)]
    private string $name = '';

    /**
     * @var Collection<int, MenuItem>
     */
    #[ORM\OneToMany(mappedBy: 'menu', targetEntity: MenuItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, MenuItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(MenuItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setMenu($this);
        }

        return $this;
    }

    public function removeItem(MenuItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getMenu() === $this) {
                $item->setMenu(null);
            }
        }

        return $this;
    }
}
