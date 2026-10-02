<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private const SESSION_KEY = 'rental_cart';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProductRepository $products,
        private readonly RentalPricing $pricing,
    ) {
    }

    public function add(Product $product, RentalPeriod $period, int $quantity = 1): void
    {
        $items = $this->rawItems();
        $key = $product->getId() . '_' . $period->pickup->format('YmdHi') . '_' . $period->return->format('YmdHi');
        $items[$key] = [
            'product_id' => $product->getId(),
            'pickup' => $period->pickup->format(DATE_ATOM),
            'return' => $period->return->format(DATE_ATOM),
            'slot' => $period->slot,
            'quantity' => max(1, min($quantity, $product->getStockQuantity())),
        ];
        $this->session()->set(self::SESSION_KEY, $items);
    }

    public function remove(string $key): void
    {
        $items = $this->rawItems();
        unset($items[$key]);
        $this->session()->set(self::SESSION_KEY, $items);
    }

    public function updateQuantity(string $key, int $quantity): bool
    {
        $items = $this->rawItems();
        if (!isset($items[$key])) {
            return false;
        }

        $items[$key]['quantity'] = max(1, $quantity);
        $this->session()->set(self::SESSION_KEY, $items);

        return true;
    }

    /**
     * @return list<array{key: string, product: Product, pickup: \DateTimeImmutable, return: \DateTimeImmutable, quantity: int, days: int, slot: string, period_label: string, unit_amount: int, total_amount: int}>
     */
    public function items(): array
    {
        $resolved = [];
        foreach ($this->rawItems() as $key => $item) {
            $product = $this->products->find($item['product_id'] ?? 0);
            if (!$product instanceof Product || !$product->isActive()) {
                continue;
            }

            try {
                $pickup = new \DateTimeImmutable((string) ($item['pickup'] ?? ''));
                $return = new \DateTimeImmutable((string) ($item['return'] ?? ''));
            } catch (\Exception) {
                continue;
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $days = max(1, (int) $pickup->setTime(0, 0)->diff($return->setTime(0, 0))->days + 1);
            $slot = (string) ($item['slot'] ?? RentalPeriod::FULL_DAY);
            if (in_array($slot, ['morning', 'afternoon'], true)) {
                $slot = RentalPeriod::HALF_DAY;
            }
            $period = new RentalPeriod($pickup, $return, $slot, $days);
            $price = $this->pricing->calculate($product, $period, $quantity);
            $resolved[] = compact('key', 'product', 'pickup', 'return', 'quantity', 'days', 'slot') + [
                'period_label' => $price['label'],
                'unit_amount' => $price['unit_amount'],
                'total_amount' => $price['total_amount'],
            ];
        }

        return $resolved;
    }

    public function count(): int
    {
        return array_sum(array_map(static fn (array $item): int => $item['quantity'], $this->items()));
    }

    public function totalAmount(): int
    {
        return array_sum(array_column($this->items(), 'total_amount'));
    }

    public function clear(): void
    {
        $this->session()->remove(self::SESSION_KEY);
    }

    /** @return array<string, array<string, mixed>> */
    private function rawItems(): array
    {
        $items = $this->session()->get(self::SESSION_KEY, []);

        return is_array($items) ? $items : [];
    }

    private function session(): \Symfony\Component\HttpFoundation\Session\SessionInterface
    {
        return $this->requestStack->getSession();
    }

}
