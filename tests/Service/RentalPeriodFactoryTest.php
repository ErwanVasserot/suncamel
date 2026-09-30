<?php

namespace App\Tests\Service;

use App\Service\RentalPeriod;
use App\Service\RentalPeriodFactory;
use PHPUnit\Framework\TestCase;

final class RentalPeriodFactoryTest extends TestCase
{
    public function testBuildsMorningAndAfternoonSlots(): void
    {
        $date = (new \DateTimeImmutable('+2 days', new \DateTimeZone('Pacific/Auckland')))->format('Y-m-d');
        $factory = new RentalPeriodFactory();

        $morning = $factory->fromInput($date, $date, RentalPeriod::HALF_DAY, 8);
        $afternoon = $factory->fromInput($date, $date, RentalPeriod::HALF_DAY, 12);

        self::assertSame('08:00', $morning->pickup->format('H:i'));
        self::assertSame('12:00', $morning->return->format('H:i'));
        self::assertSame('12:00', $afternoon->pickup->format('H:i'));
        self::assertSame('16:00', $afternoon->return->format('H:i'));
    }

    public function testDateRangeBecomesFullDays(): void
    {
        $pickup = new \DateTimeImmutable('+2 days', new \DateTimeZone('Pacific/Auckland'));
        $return = $pickup->modify('+3 days');

        $period = (new RentalPeriodFactory())->fromInput(
            $pickup->format('Y-m-d'),
            $return->format('Y-m-d'),
            RentalPeriod::FULL_DAY,
            16,
            8,
        );

        self::assertSame(RentalPeriod::FULL_DAY, $period->slot);
        self::assertSame(4, $period->dayCount);
        self::assertSame('16:00', $period->pickup->format('H:i'));
        self::assertSame('08:00', $period->return->format('H:i'));
    }

    public function testSingleFullDayAlwaysUsesOpeningHours(): void
    {
        $date = (new \DateTimeImmutable('+2 days', new \DateTimeZone('Pacific/Auckland')))->format('Y-m-d');

        $period = (new RentalPeriodFactory())->fromInput($date, $date, RentalPeriod::FULL_DAY, 16, 8);

        self::assertSame('08:00', $period->pickup->format('H:i'));
        self::assertSame('16:00', $period->return->format('H:i'));
    }

    public function testHalfDayCannotStartAtSixteen(): void
    {
        $date = (new \DateTimeImmutable('+2 days', new \DateTimeZone('Pacific/Auckland')))->format('Y-m-d');

        $this->expectException(\InvalidArgumentException::class);
        (new RentalPeriodFactory())->fromInput($date, $date, RentalPeriod::HALF_DAY, 16);
    }
}
