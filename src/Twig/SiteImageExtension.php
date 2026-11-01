<?php

namespace App\Twig;

use App\Entity\SiteImage;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Contracts\Service\ResetInterface;

final class SiteImageExtension extends AbstractExtension implements ResetInterface
{
    private ?array $images = null;

    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function getFunctions(): array
    {
        return [new TwigFunction('site_image', [$this, 'resolve'])];
    }

    public function reset(): void
    {
        $this->images = null;
    }

    public function resolve(?string $source): string
    {
        if ($this->images === null) {
            $this->images = [];
            foreach ($this->entityManager->getRepository(SiteImage::class)->findAll() as $image) {
                if ($image->getImage()) {
                    $this->images[$image->getSource()] = '/images/suncamel/products/' . $image->getImage();
                }
            }
        }

        return $this->images[$source ?? ''] ?? $source ?? '';
    }
}
