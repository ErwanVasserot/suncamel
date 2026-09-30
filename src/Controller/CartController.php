<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use App\Service\RentalPeriodFactory;
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
    public function add(string $slug, Request $request, ProductRepository $products, CartService $cart, BookingRepository $bookings, RentalPeriodFactory $periods): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('cart_add_' . $slug, (string) $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Invalid cart token.');
        }

        $product = $products->findOneBy(['slug' => $slug, 'isActive' => true]);
        if ($product === null) {
            throw $this->createNotFoundException('Product not found.');
        }

        try {
            $period = $periods->fromInput(
                (string) $request->request->get('pickup'),
                (string) $request->request->get('return'),
                (string) $request->request->get('duration', 'full_day'),
                (int) $request->request->get('pickup_time', 8),
                (int) $request->request->get('return_time', 16),
            );
        } catch (\Exception) {
            $this->addFlash('error', 'Please select a rental period before adding a bike to the cart.');
            return $this->redirect('/products/' . $slug);
        }

        if ($bookings->reservedQuantity($product, $period->pickup, $period->return) >= $product->getStockQuantity()) {
            $this->addFlash('error', 'This bike is no longer available for the selected period.');
            return $this->redirect('/collections/all');
        }

        $cart->add($product, $period);
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
