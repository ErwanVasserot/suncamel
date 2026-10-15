<?php

namespace App\Tests\Service;

use App\Entity\Promotion;
use App\Repository\PromotionRepository;
use App\Service\PromotionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class PromotionServiceTest extends TestCase
{
    public function testAppliesAnActivePromotionAndCalculatesRoundedDiscount(): void
    {
        $promotion = (new Promotion())->setCode('summer-10')->setDiscountPercent(10);
        $repository = $this->createStub(PromotionRepository::class);
        $repository->method('findActiveByCode')->willReturn($promotion);
        $service = new PromotionService($this->requestStack(), $repository);

        self::assertSame($promotion, $service->apply(' summer-10 '));
        self::assertSame($promotion, $service->current());
        self::assertSame(1235, $service->discountAmount(12345));
    }

    public function testInactiveOrUnknownPromotionIsRejected(): void
    {
        $repository = $this->createStub(PromotionRepository::class);
        $repository->method('findActiveByCode')->willReturn(null);
        $service = new PromotionService($this->requestStack(), $repository);

        self::assertNull($service->apply('DISABLED'));
        self::assertNull($service->current());
        self::assertSame(0, $service->discountAmount(10000));
    }

    private function requestStack(): RequestStack
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }
}
