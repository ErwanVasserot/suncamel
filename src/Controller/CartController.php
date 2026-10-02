<?php

namespace App\Controller;

use App\Exception\RentalClosedException;
use App\Repository\BookingRepository;
use App\Repository\ProductRepository;
use App\Repository\RentalClosureRepository;
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
    public function show(CartService $cart, BookingRepository $bookings, RentalClosureRepository $closures): Response
    {
        $items = $cart->items();
        $availableQuantities = $this->availableQuantities($items, $bookings, $closures);
        $availability = [];
        foreach ($items as $item) {
            $availability[$item['key']] = $availableQuantities[$item['key']] >= $item['quantity'];
        }

        return $this->render('cart/show.html.twig', [
            'items' => $items,
            'total_amount' => $cart->totalAmount(),
            'availability' => $availability,
            'available_quantities' => $availableQuantities,
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
        } catch (RentalClosedException $exception) {
            $this->addFlash('error', $exception->getMessage());
            return $this->redirect('/products/' . $slug);
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

    #[Route('/cart/update/{key}', name: 'cart_update', methods: ['POST'], priority: 30)]
    public function update(string $key, Request $request, CartService $cart, BookingRepository $bookings, RentalClosureRepository $closures): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('cart_update_' . $key, (string) $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Invalid cart token.');
        }

        $item = null;
        foreach ($cart->items() as $cartItem) {
            if ($cartItem['key'] === $key) {
                $item = $cartItem;
                break;
            }
        }

        if ($item === null) {
            $this->addFlash('error', 'This cart item no longer exists.');
            return $this->redirectToRoute('cart_show');
        }

        $requestedQuantity = max(1, (int) $request->request->get('quantity', 1));
        if (($closure = $closures->findOverlapping($item['pickup'], $item['return'])) !== null) {
            $this->addFlash('error', $closure->getMessage());
            return $this->redirectToRoute('cart_show');
        }
        $availableQuantity = max(0, $item['product']->getStockQuantity()
            - $bookings->reservedQuantity($item['product'], $item['pickup'], $item['return']));

        if ($requestedQuantity > $availableQuantity) {
            $this->addFlash('error', sprintf(
                'Only %d unit%s of %s %s available for this period.',
                $availableQuantity,
                $availableQuantity === 1 ? '' : 's',
                $item['product']->getTitle(),
                $availableQuantity === 1 ? 'is' : 'are',
            ));
            return $this->redirectToRoute('cart_show');
        }

        $cart->updateQuantity($key, $requestedQuantity);

        return $this->redirectToRoute('cart_show');
    }

    /** @param list<array<string, mixed>> $items */
    private function availableQuantities(array $items, BookingRepository $bookings, RentalClosureRepository $closures): array
    {
        $result = [];
        foreach ($items as $item) {
            if ($closures->findOverlapping($item['pickup'], $item['return']) !== null) {
                $result[$item['key']] = 0;
                continue;
            }
            $result[$item['key']] = max(0, $item['product']->getStockQuantity()
                - $bookings->reservedQuantity($item['product'], $item['pickup'], $item['return']));
        }

        return $result;
    }
}
