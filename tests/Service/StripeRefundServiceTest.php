<?php

namespace App\Tests\Service;

use App\Entity\Booking;
use App\Service\StripeRefundService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class StripeRefundServiceTest extends TestCase
{
    public function testRefundsTheStoredPaymentIntentWithAnIdempotencyKey(): void
    {
        $client = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertSame('POST', $method);
            self::assertSame('https://api.stripe.com/v1/refunds', $url);
            self::assertStringContainsString('payment_intent=pi_test_123', $options['body']);
            self::assertStringNotContainsString('amount=', $options['body']);
            self::assertContains('Idempotency-Key: booking-refund-SC-123', $options['headers']);

            return new MockResponse(json_encode([
                'id' => 're_test_123',
                'status' => 'succeeded',
                'amount' => 9000,
            ], JSON_THROW_ON_ERROR));
        });
        $booking = (new Booking())
            ->setReference('SC-123')
            ->setTotalAmount(10000)
            ->applyPromotion('PROMO10', 10, 10000, 1000)
            ->setStripePaymentIntentId('pi_test_123');

        $refund = (new StripeRefundService($client, 'test', 'sk_test_example'))->refund($booking);

        self::assertSame([
            'id' => 're_test_123',
            'status' => 'succeeded',
            'payment_intent' => 'pi_test_123',
            'amount' => 9000,
        ], $refund);
    }

    public function testRetrievesThePaymentIntentFromTheCheckoutSessionForExistingBookings(): void
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode(['payment_intent' => 'pi_from_session'], JSON_THROW_ON_ERROR)),
            new MockResponse(json_encode(['id' => 're_test_456', 'status' => 'pending', 'amount' => 10000], JSON_THROW_ON_ERROR)),
        ]);
        $booking = (new Booking())
            ->setReference('SC-456')
            ->setTotalAmount(10000)
            ->setStripeSessionId('cs_test_456');

        $refund = (new StripeRefundService($client, 'test', 'sk_test_example'))->refund($booking);

        self::assertSame('pi_from_session', $refund['payment_intent']);
        self::assertSame('re_test_456', $refund['id']);
        self::assertSame('pending', $refund['status']);
        self::assertSame(10000, $refund['amount']);
    }
}
