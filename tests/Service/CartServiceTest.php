<?php

namespace App\Tests\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\CartService;
use App\Service\RentalPeriod;
use App\Service\RentalPricing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class CartServiceTest extends TestCase
{
    public function testItKeepsItemsWithDifferentRentalPeriodsAndUpdatesTheirQuantitiesIndependently(): void
    {
        $firstProduct = $this->product(1, 'City bike');
        $secondProduct = $this->product(2, 'Cargo bike');
        $products = $this->createStub(ProductRepository::class);
        $products->method('find')->willReturnCallback(
            static fn (int $id): ?Product => match ($id) {
                1 => $firstProduct,
                2 => $secondProduct,
                default => null,
            },
        );

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $cart = new CartService($requestStack, $products, new RentalPricing());

        $firstPeriod = new RentalPeriod(
            new \DateTimeImmutable('2026-11-10 08:00:00'),
            new \DateTimeImmutable('2026-11-10 16:00:00'),
            RentalPeriod::FULL_DAY,
            1,
        );
        $secondPeriod = new RentalPeriod(
            new \DateTimeImmutable('2026-11-14 08:00:00'),
            new \DateTimeImmutable('2026-11-16 16:00:00'),
            RentalPeriod::FULL_DAY,
            3,
        );

        $cart->add($firstProduct, $firstPeriod);
        $cart->add($secondProduct, $secondPeriod);
        $items = $cart->items();

        self::assertCount(2, $items);
        self::assertSame('2026-11-10', $items[0]['pickup']->format('Y-m-d'));
        self::assertSame('2026-11-14', $items[1]['pickup']->format('Y-m-d'));
        self::assertTrue($cart->updateQuantity($items[1]['key'], 3));
        self::assertSame([1, 3], array_column($cart->items(), 'quantity'));
        self::assertSame(4, $cart->count());
    }

    private function product(int $id, string $title): Product
    {
        $product = (new Product())
            ->setTitle($title)
            ->setSlug(strtolower(str_replace(' ', '-', $title)))
            ->setStockQuantity(5)
            ->setFullDayAmount(1000);

        $property = new \ReflectionProperty(Product::class, 'id');
        $property->setValue($product, $id);

        return $product;
    }
}
