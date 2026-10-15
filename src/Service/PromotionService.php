<?php

namespace App\Service;

use App\Entity\Promotion;
use App\Repository\PromotionRepository;
use Symfony\Component\HttpFoundation\RequestStack;

final class PromotionService
{
    private const SESSION_KEY = 'promotion_code';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly PromotionRepository $promotions,
    ) {
    }

    public function apply(string $code): ?Promotion
    {
        $promotion = $this->promotions->findActiveByCode($code);
        if ($promotion instanceof Promotion) {
            $this->requestStack->getSession()->set(self::SESSION_KEY, $promotion->getCode());
        }

        return $promotion;
    }

    public function current(): ?Promotion
    {
        $code = $this->requestStack->getSession()->get(self::SESSION_KEY);
        if (!is_string($code) || $code === '') {
            return null;
        }

        $promotion = $this->promotions->findActiveByCode($code);
        if (!$promotion instanceof Promotion) {
            $this->clear();
        }

        return $promotion;
    }

    public function discountAmount(int $subtotal, ?Promotion $promotion = null): int
    {
        $promotion ??= $this->current();

        return $promotion instanceof Promotion
            ? (int) round($subtotal * $promotion->getDiscountPercent() / 100)
            : 0;
    }

    public function clear(): void
    {
        $this->requestStack->getSession()->remove(self::SESSION_KEY);
    }
}
