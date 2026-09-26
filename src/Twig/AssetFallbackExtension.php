<?php

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetFallbackExtension extends AbstractExtension
{
    public function __construct(
        private readonly string $projectDir,
        private readonly Packages $packages,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('fallback_asset', [$this, 'fallbackAsset']),
        ];
    }

    public function fallbackAsset(string $localPath): string
    {
        return $this->packages->getUrl($localPath);
    }
}
