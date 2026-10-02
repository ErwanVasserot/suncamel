<?php

namespace App\Tests\Entity;

use App\Entity\Booking;
use PHPUnit\Framework\TestCase;

final class BookingTest extends TestCase
{
    public function testCurrencyIsExposedInIcuCompatibleUppercaseFormat(): void
    {
        $booking = (new Booking())->setCurrency('nzd');

        self::assertSame('NZD', $booking->getCurrency());
    }
}
