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

    public function testEmailIsNormalizedAndBookingCanBeOwnedByAGuest(): void
    {
        $booking = (new Booking())
            ->setUser(null)
            ->setEmail(' Guest@Example.COM ');

        self::assertNull($booking->getUser());
        self::assertSame('guest@example.com', $booking->getEmail());
    }
}
