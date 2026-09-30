<?php

namespace App\Service;

use App\Entity\Product;

final class RentalPricing
{
    /** @return array{unit_amount: int, day_count: int, total_amount: int, label: string} */
    public function calculate(Product $product, RentalPeriod $period, int $quantity = 1): array
    {
        $quantity = max(1, $quantity);
        if ($period->isHalfDay()) {
            $unitAmount = $product->getHalfDayAmount();
            $dayCount = 1;
        } else {
            $unitAmount = $product->getFullDayAmount();
            $dayCount = $period->dayCount;
            $appliedMinimumDays = 1;
            foreach ($product->getPricingTiers() as $tier) {
                if ($dayCount >= $tier->getMinimumDays() && $tier->getMinimumDays() > $appliedMinimumDays) {
                    $unitAmount = $tier->getDailyAmount();
                    $appliedMinimumDays = $tier->getMinimumDays();
                }
            }
        }

        return [
            'unit_amount' => $unitAmount,
            'day_count' => $dayCount,
            'total_amount' => $unitAmount * $dayCount * $quantity,
            'label' => $period->label(),
        ];
    }
}
