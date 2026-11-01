<?php

namespace App\Tests\Twig;

use App\Entity\SiteImage;
use App\Twig\SiteImageExtension;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class SiteImageExtensionTest extends TestCase
{
    public function testResolvesReplacementsAndPreservesOriginalsWithOneQuery(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('findAll')->willReturn([
            (new SiteImage())->setSource('/images/home.jpg')->setImage('site-custom.webp'),
            (new SiteImage())->setSource('/images/original.jpg'),
        ]);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('getRepository')->with(SiteImage::class)->willReturn($repository);
        $extension = new SiteImageExtension($manager);

        self::assertSame('/images/suncamel/products/site-custom.webp', $extension->resolve('/images/home.jpg'));
        self::assertSame('/images/original.jpg', $extension->resolve('/images/original.jpg'));
        self::assertSame('https://example.com/logo.png', $extension->resolve('https://example.com/logo.png'));
        self::assertSame('', $extension->resolve(null));
    }
}
