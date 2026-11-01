<?php

namespace App\Tests\Entity;

use App\Entity\SiteImage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class SiteImageTest extends TestCase
{
    public function testPreviewShowsOriginalUntilAnImageIsUploaded(): void
    {
        $image = (new SiteImage())->setSource('/images/suncamel/home/hero.jpg');
        self::assertSame('/images/suncamel/home/hero.jpg', $image->getDisplayImage());
        $image->setImage('site-custom.webp');
        self::assertSame('/images/suncamel/products/site-custom.webp', $image->getDisplayImage());
        $image->setImage(null);
        self::assertSame('/images/suncamel/home/hero.jpg', $image->getDisplayImage());
    }

    public function testBikeImagesAreRejectedButTheSharedCatalogueBannerIsAllowed(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $image = (new SiteImage())->setLabel('Bannière')->setSource('/images/suncamel/products/collection-hero.png');
        self::assertCount(0, $validator->validateProperty($image, 'source'));
        $image->setSource('/images/suncamel/products/cruiser-cover.png');
        self::assertCount(1, $validator->validateProperty($image, 'source'));
    }
}
