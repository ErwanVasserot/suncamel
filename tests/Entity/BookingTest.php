<?php

namespace App\Tests\Entity;

use App\Entity\Booking;
use App\Entity\BookingItem;
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

    public function testBookingItemStoresUtcAndKeepsTheAucklandPeriodLabel(): void
    {
        $auckland = new \DateTimeZone('Pacific/Auckland');
        $item = (new BookingItem())
            ->setPickupAt(new \DateTimeImmutable('2026-12-10 08:00:00', $auckland))
            ->setReturnAt(new \DateTimeImmutable('2026-12-10 12:00:00', $auckland));

        self::assertSame('UTC', $item->getPickupAt()->getTimezone()->getName());
        self::assertSame('2026-12-09 19:00', $item->getPickupAt()->format('Y-m-d H:i'));
        self::assertSame('Morning (08:00–12:00)', $item->getPeriodLabel());
    }

    public function testBookingStoresStripeRefundDetails(): void
    {
        $booking = (new Booking())->markRefunded('re_test_123', 'succeeded');

        self::assertSame('re_test_123', $booking->getStripeRefundId());
        self::assertSame('succeeded', $booking->getStripeRefundStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $booking->getRefundedAt());

        $booking->setStripeRefundStatus('failed');
        self::assertSame('failed', $booking->getStripeRefundStatus());
    }

    public function testBookingKeepsThePromotionSnapshot(): void
    {
        $booking = (new Booking())
            ->setTotalAmount(10000)
            ->applyPromotion('summer-10', 10, 10000, 1000);

        self::assertSame('SUMMER-10', $booking->getPromotionCode());
        self::assertSame(10, $booking->getPromotionPercent());
        self::assertSame(10000, $booking->getSubtotalAmount());
        self::assertSame(1000, $booking->getDiscountAmount());
        self::assertSame(9000, $booking->getTotalAmount());
    }
}
