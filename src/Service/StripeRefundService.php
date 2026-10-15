<?php

namespace App\Service;

use App\Entity\Booking;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class StripeRefundService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
        #[Autowire('%env(string:STRIPE_SECRET_KEY)%')]
        private readonly string $stripeSecretKey,
    ) {
    }

    /** @return array{id: string, status: string, payment_intent: string, amount: int} */
    public function refund(Booking $booking): array
    {
        if ($this->stripeSecretKey === '') {
            throw new \RuntimeException('La clé secrète Stripe n’est pas configurée.');
        }
        if ($this->environment === 'dev' && !str_starts_with($this->stripeSecretKey, 'sk_test_')) {
            throw new \RuntimeException('En développement, les remboursements exigent une clé Stripe de test.');
        }

        if ($booking->getStripeRefundId() !== null) {
            throw new \LogicException('Cette réservation possède déjà un remboursement Stripe.');
        }

        $paymentIntentId = $booking->getStripePaymentIntentId() ?? $this->retrievePaymentIntentId($booking);
        $response = $this->httpClient->request('POST', 'https://api.stripe.com/v1/refunds', [
            'auth_bearer' => $this->stripeSecretKey,
            'headers' => [
                'Idempotency-Key' => 'booking-refund-'.$booking->getReference(),
            ],
            'body' => [
                'payment_intent' => $paymentIntentId,
                'reason' => 'requested_by_customer',
                'metadata[booking_reference]' => $booking->getReference(),
            ],
        ]);
        $data = $response->toArray(false);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException((string) ($data['error']['message'] ?? 'Le remboursement Stripe a échoué.'));
        }

        $refundId = $data['id'] ?? null;
        $status = $data['status'] ?? null;
        $amount = $data['amount'] ?? null;
        if (!is_string($refundId) || !is_string($status) || !is_int($amount) || !in_array($status, ['pending', 'succeeded'], true)) {
            throw new \RuntimeException('Stripe n’a pas confirmé la création du remboursement.');
        }
        if ($amount !== $booking->getTotalAmount()) {
            throw new \RuntimeException('Le montant remboursé par Stripe ne correspond pas au montant payé.');
        }

        return [
            'id' => $refundId,
            'status' => $status,
            'payment_intent' => $paymentIntentId,
            'amount' => $amount,
        ];
    }

    private function retrievePaymentIntentId(Booking $booking): string
    {
        $sessionId = $booking->getStripeSessionId();
        if ($sessionId === null || $sessionId === '') {
            throw new \RuntimeException('Cette réservation ne possède pas de session Stripe.');
        }

        $response = $this->httpClient->request(
            'GET',
            'https://api.stripe.com/v1/checkout/sessions/'.rawurlencode($sessionId),
            ['auth_bearer' => $this->stripeSecretKey],
        );
        $data = $response->toArray(false);
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException((string) ($data['error']['message'] ?? 'Impossible de retrouver le paiement Stripe.'));
        }

        $paymentIntentId = $data['payment_intent'] ?? null;
        if (!is_string($paymentIntentId) || $paymentIntentId === '') {
            throw new \RuntimeException('Aucun paiement Stripe remboursable n’est associé à cette réservation.');
        }

        return $paymentIntentId;
    }
}
