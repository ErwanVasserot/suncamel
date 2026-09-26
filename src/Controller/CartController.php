<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'cart_show', methods: ['GET'], priority: 30)]
    public function show(CartService $cart, BookingRepository $bookings): Response
    {
        $items = $cart->items();

        return $this->render('cart/show.html.twig', [
            'items' => $items,
            'total_amount' => $cart->totalAmount(),
            'availability' => $this->availability($items, $bookings),
        ]);
    }

    #[Route('/cart/add/{slug}', name: 'cart_add', methods: ['POST'], priority: 30)]
    public function add(string $slug, Request $request, ProductRepository $products, CartService $cart, BookingRepository $bookings): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('cart_add_' . $slug, (string) $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Invalid cart token.');
        }

        $product = $products->findOneBy(['slug' => $slug, 'isActive' => true]);
        if ($product === null) {
            throw $this->createNotFoundException('Product not found.');
        }

        try {
            $timezone = new \DateTimeZone('Pacific/Auckland');
            $pickupValue = (string) $request->request->get('pickup');
            $returnValue = (string) $request->request->get('return');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $pickupValue) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $returnValue)) {
                throw new \InvalidArgumentException('Invalid date format.');
            }
            $pickup = new \DateTimeImmutable($pickupValue . ' 08:00:00', $timezone);
            $return = new \DateTimeImmutable($returnValue . ' 12:00:00', $timezone);
        } catch (\Exception) {
            $this->addFlash('error', 'Please select a rental period before adding a bike to the cart.');
            return $this->redirect('/products/' . $slug);
        }

        if ($pickup < new \DateTimeImmutable('today', new \DateTimeZone('Pacific/Auckland')) || $return <= $pickup) {
            $this->addFlash('error', 'The selected rental period is invalid.');
            return $this->redirect('/products/' . $slug);
        }

        if ($bookings->reservedQuantity($product, $pickup, $return) >= $product->getStockQuantity()) {
            $this->addFlash('error', 'This bike is no longer available for the selected period.');
            return $this->redirect('/collections/all');
        }

        $cart->add($product, $pickup, $return);
        $this->addFlash('success', $product->getTitle() . ' was added to your cart.');

        return $this->redirectToRoute('cart_show');
    }

    #[Route('/cart/remove/{key}', name: 'cart_remove', methods: ['POST'], priority: 30)]
    public function remove(string $key, Request $request, CartService $cart): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('cart_remove_' . $key, (string) $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Invalid cart token.');
        }

        $cart->remove($key);
        return $this->redirectToRoute('cart_show');
    }

    /** @param list<array<string, mixed>> $items */
    private function availability(array $items, BookingRepository $bookings): array
    {
        $result = [];
        foreach ($items as $item) {
            $available = $item['product']->getStockQuantity()
                - $bookings->reservedQuantity($item['product'], $item['pickup'], $item['return']);
            $result[$item['key']] = $available >= $item['quantity'];
        }
        return $result;
    }
}
