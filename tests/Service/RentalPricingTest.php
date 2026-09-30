<?php

namespace App\Tests\Service;

use App\Entity\PricingTier;
use App\Entity\Product;
use App\Service\RentalPeriod;
use App\Service\RentalPricing;
use PHPUnit\Framework\TestCase;

final class RentalPricingTest extends TestCase
{
    public function testUsesHalfDayFullDayAndHighestReachedTier(): void
    {
        $product = (new Product())
            ->setHalfDayAmount(7900)
            ->setFullDayAmount(12000)
            ->addPricingTier((new PricingTier())->setMinimumDays(7)->setDailyAmount(8500))
            ->addPricingTier((new PricingTier())->setMinimumDays(3)->setDailyAmount(10000));
        $pricing = new RentalPricing();
        $date = new \DateTimeImmutable('2030-01-01 08:00:00');

        $halfDay = $pricing->calculate($product, new RentalPeriod($date, $date->setTime(12, 0), RentalPeriod::HALF_DAY, 1));
        $oneDay = $pricing->calculate($product, new RentalPeriod($date, $date->setTime(16, 0), RentalPeriod::FULL_DAY, 1));
        $sevenDays = $pricing->calculate($product, new RentalPeriod($date, $date->modify('+6 days')->setTime(16, 0), RentalPeriod::FULL_DAY, 7));

        self::assertSame(7900, $halfDay['total_amount']);
        self::assertSame(12000, $oneDay['total_amount']);
        self::assertSame(8500, $sevenDays['unit_amount']);
        self::assertSame(59500, $sevenDays['total_amount']);
    }
}
