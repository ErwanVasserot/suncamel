<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\BookingItem;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\RentalClosureRepository;
use App\Service\CartService;
use App\Service\BookingEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CheckoutController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
        #[Autowire('%env(string:STRIPE_SECRET_KEY)%')]
        private readonly string $stripeSecretKey,
        #[Autowire('%env(string:STRIPE_CURRENCY)%')]
        private readonly string $stripeCurrency,
        #[Autowire('%env(string:STRIPE_WEBHOOK_SECRET)%')]
        private readonly string $stripeWebhookSecret,
    ) {
    }

    #[Route('/checkout', name: 'checkout_create', methods: ['POST'], priority: 30)]
    public function create(Request $request, CartService $cart, BookingRepository $bookings, RentalClosureRepository $closures, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('checkout', (string) $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Invalid checkout token.');
        }

        $email = strtolower(trim((string) $request->request->get('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Enter a valid email address to proceed to payment.');
            return $this->redirectToRoute('cart_show');
        }

        if ($this->getUser() === null && $request->request->getBoolean('want_account')) {
            $request->getSession()->set('registration_from_checkout', true);
            $request->getSession()->set('registration_email', $email);

            return $this->redirectToRoute('app_register');
        }
        $request->getSession()->remove('registration_from_checkout');
        $request->getSession()->remove('registration_email');

        if ($this->stripeSecretKey === '') {
            throw new \RuntimeException('STRIPE_SECRET_KEY is not configured. Add a Stripe test key to .env.local.');
        }
        if ($this->environment === 'dev' && !str_starts_with($this->stripeSecretKey, 'sk_test_')) {
            throw new \RuntimeException('Local Stripe checkout only accepts an sk_test_ key.');
        }

        $cartItems = $cart->items();
        if ($cartItems === []) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('cart_show');
        }

        foreach ($cartItems as $item) {
            if (($closure = $closures->findOverlapping($item['pickup'], $item['return'])) !== null) {
                $this->addFlash('error', $closure->getMessage());
                return $this->redirectToRoute('cart_show');
            }
            $available = $item['product']->getStockQuantity()
                - $bookings->reservedQuantity($item['product'], $item['pickup'], $item['return']);
            if ($available < $item['quantity']) {
                $this->addFlash('error', $item['product']->getTitle() . ' is no longer available for this period.');
                return $this->redirectToRoute('cart_show');
            }
        }

        $authenticatedUser = $this->getUser();
        $user = $authenticatedUser instanceof User ? $authenticatedUser : null;

        $entityManager->beginTransaction();
        try {
            foreach ($cartItems as $item) {
                $entityManager->lock($item['product'], LockMode::PESSIMISTIC_WRITE);
                $available = $item['product']->getStockQuantity()
                    - $bookings->reservedQuantity($item['product'], $item['pickup'], $item['return']);
                if ($available < $item['quantity']) {
                    throw new \UnexpectedValueException($item['product']->getTitle() . ' is no longer available for this period.');
                }
            }

            $booking = (new Booking())
                ->setReference('SC-' . strtoupper(bin2hex(random_bytes(5))))
                ->setUser($user)
                ->setEmail($email)
                ->setCurrency($this->stripeCurrency)
                ->setTotalAmount($cart->totalAmount());

            foreach ($cartItems as $item) {
                $booking->addItem((new BookingItem())
                    ->setProduct($item['product'])
                    ->setPickupAt($item['pickup'])
                    ->setReturnAt($item['return'])
                    ->setQuantity($item['quantity'])
                    ->setDayCount($item['days'])
                    ->setUnitAmount($item['unit_amount']));
            }

            $entityManager->persist($booking);
            $entityManager->flush();
            $entityManager->commit();
        } catch (\UnexpectedValueException $exception) {
            $entityManager->rollback();
            $this->addFlash('error', $exception->getMessage());
            return $this->redirectToRoute('cart_show');
        } catch (\Throwable $exception) {
            $entityManager->rollback();
            throw $exception;
        }

        try {
            $session = $this->createStripeSession($booking);
            $checkoutUrl = $session['url'] ?? null;
            $sessionId = $session['id'] ?? null;
            if (!is_string($checkoutUrl) || !is_string($sessionId)) {
                throw new \RuntimeException('Stripe did not return a checkout session.');
            }
            $booking->setStripeSessionId($sessionId);
            $entityManager->flush();
            $request->getSession()->set('checkout_booking_reference', $booking->getReference());
        } catch (\Throwable $exception) {
            $booking->setStatus(Booking::STATUS_CANCELLED);
            $entityManager->flush();
            throw $exception;
        }

        return $this->render('checkout/redirect.html.twig', [
            'checkout_url' => $checkoutUrl,
        ]);
    }

    #[Route('/checkout/success', name: 'checkout_success', methods: ['GET'], priority: 40)]
    public function success(Request $request, BookingRepository $bookings, CartService $cart): Response
    {
        $reference = (string) $request->query->get('booking');
        $booking = $bookings->findOneBy(['reference' => $reference]);
        if ($booking instanceof Booking && $this->canAccessBooking($request, $booking)) {
            $cart->clear();
            $request->getSession()->remove('checkout_booking_reference');
        }

        return $this->render('checkout/status.html.twig', [
            'title' => 'Payment received',
            'message' => 'Your payment is being confirmed. Your booking reference is ' . ($reference ?: 'unavailable') . '.',
            'session_id' => null,
        ]);
    }

    #[Route('/checkout/cancel', name: 'checkout_cancel', methods: ['GET'], priority: 40)]
    public function cancel(Request $request, BookingRepository $bookings, EntityManagerInterface $entityManager): Response
    {
        $reference = (string) $request->query->get('booking');
        $booking = $bookings->findOneBy(['reference' => $reference, 'status' => Booking::STATUS_PENDING]);
        if ($booking instanceof Booking && $this->canAccessBooking($request, $booking)) {
            $booking->setStatus(Booking::STATUS_CANCELLED);
            $entityManager->flush();
            $request->getSession()->remove('checkout_booking_reference');
        }

        return $this->render('checkout/status.html.twig', [
            'title' => 'Payment cancelled',
            'message' => 'No payment was taken. Your cart has been kept so you can try again.',
            'session_id' => null,
        ]);
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'], priority: 50)]
    public function webhook(Request $request, BookingRepository $bookings, EntityManagerInterface $entityManager, BookingEmailService $bookingEmails): JsonResponse
    {
        $payload = $request->getContent();
        $signatureError = $this->stripeSignatureError($payload, (string) $request->headers->get('Stripe-Signature'));
        if ($signatureError !== null) {
            return new JsonResponse(['error' => $signatureError], Response::HTTP_BAD_REQUEST);
        }
        $event = json_decode($payload, true);
        if (!is_array($event)) {
            return new JsonResponse(['error' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        }

        if (in_array(($event['type'] ?? null), ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $session = $event['data']['object'] ?? [];
            $reference = $session['metadata']['booking_reference'] ?? null;
            $booking = is_string($reference) ? $bookings->findOneBy(['reference' => $reference]) : null;
            if ($booking instanceof Booking && $booking->getStatus() === Booking::STATUS_PENDING && ($session['payment_status'] ?? null) === 'paid') {
                $booking->setStripeSessionId((string) ($session['id'] ?? $booking->getStripeSessionId()));
                $booking->markPaid();
                $entityManager->flush();
                $bookingEmails->sendBookingConfirmedEmails($booking);
            }
        }

        if (in_array(($event['type'] ?? null), ['checkout.session.expired', 'checkout.session.async_payment_failed'], true)) {
            $session = $event['data']['object'] ?? [];
            $reference = $session['metadata']['booking_reference'] ?? null;
            $booking = is_string($reference) ? $bookings->findOneBy(['reference' => $reference]) : null;
            if ($booking instanceof Booking && $booking->getStatus() === Booking::STATUS_PENDING) {
                $booking->setStatus(Booking::STATUS_CANCELLED);
                $entityManager->flush();
            }
        }

        return new JsonResponse(['received' => true]);
    }

    /** @return array<string, mixed> */
    private function createStripeSession(Booking $booking): array
    {
        $body = [
            'mode' => 'payment',
            'submit_type' => 'book',
            'success_url' => $this->generateUrl('checkout_success', ['booking' => $booking->getReference()], UrlGeneratorInterface::ABSOLUTE_URL) . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->generateUrl('checkout_cancel', ['booking' => $booking->getReference()], UrlGeneratorInterface::ABSOLUTE_URL),
            'client_reference_id' => $booking->getReference(),
            'customer_email' => $booking->getEmail(),
            'metadata[booking_reference]' => $booking->getReference(),
            'expires_at' => (string) $booking->getExpiresAt()->getTimestamp(),
        ];

        foreach ($booking->getItems() as $index => $item) {
            $product = $item->getProduct();
            $body["line_items[$index][quantity]"] = (string) $item->getQuantity();
            $body["line_items[$index][price_data][currency]"] = strtolower($booking->getCurrency());
            $body["line_items[$index][price_data][unit_amount]"] = (string) ($item->getUnitAmount() * $item->getDayCount());
            $body["line_items[$index][price_data][product_data][name]"] = $product?->getTitle() . ' — ' . $item->getPeriodLabel();
            $timezone = new \DateTimeZone('Pacific/Auckland');
            $body["line_items[$index][price_data][product_data][description]"] = $item->getPickupAt()->setTimezone($timezone)->format('d M Y H:i') . ' to ' . $item->getReturnAt()->setTimezone($timezone)->format('d M Y H:i');
            $body["line_items[$index][price_data][product_data][metadata][product_slug]"] = $product?->getSlug();
            $body["line_items[$index][price_data][product_data][metadata][pickup]"] = $item->getPickupAt()->format(DATE_ATOM);
            $body["line_items[$index][price_data][product_data][metadata][return]"] = $item->getReturnAt()->format(DATE_ATOM);
        }

        $response = $this->httpClient->request('POST', 'https://api.stripe.com/v1/checkout/sessions', [
            'auth_bearer' => $this->stripeSecretKey,
            'body' => $body,
        ]);
        $data = $response->toArray(false);
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException((string) ($data['error']['message'] ?? 'Stripe checkout session creation failed.'));
        }
        return $data;
    }

    private function stripeSignatureError(string $payload, string $header): ?string
    {
        if ($this->stripeWebhookSecret === '') {
            return 'Webhook secret is not configured.';
        }
        if ($header === '') {
            return 'Stripe-Signature header is missing.';
        }

        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key !== null && $value !== null) { $parts[$key][] = $value; }
        }
        $timestamp = $parts['t'][0] ?? null;
        if (!is_string($timestamp) || !ctype_digit($timestamp)) {
            return 'Stripe-Signature header is malformed.';
        }

        $signatureAge = abs(time() - (int) $timestamp);
        if ($signatureAge > 300) {
            return sprintf('Signature timestamp is outside the 300-second tolerance (age: %d seconds).', $signatureAge);
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $this->stripeWebhookSecret);
        foreach ($parts['v1'] ?? [] as $signature) {
            if (hash_equals($expected, $signature)) { return null; }
        }

        return 'Signature does not match the configured webhook secret.';
    }

    private function canAccessBooking(Request $request, Booking $booking): bool
    {
        $user = $this->getUser();
        if ($user instanceof User && $booking->getUser() === $user) {
            return true;
        }

        return hash_equals(
            $booking->getReference(),
            (string) $request->getSession()->get('checkout_booking_reference', ''),
        );
    }
}
