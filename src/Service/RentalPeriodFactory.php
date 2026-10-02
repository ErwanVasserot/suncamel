<?php

namespace App\Service;

use App\Exception\RentalClosedException;
use App\Repository\RentalClosureRepository;

final class RentalPeriodFactory
{
    private const DATE_PATTERN = '/^\d{4}-\d{2}-\d{2}$/';

    public function __construct(private readonly ?RentalClosureRepository $closures = null) {}

    public function fromInput(
        string $pickupDate,
        string $returnDate,
        string $duration,
        int $pickupHour = 8,
        int $returnHour = 16,
    ): RentalPeriod
    {
        if (!preg_match(self::DATE_PATTERN, $pickupDate) || !preg_match(self::DATE_PATTERN, $returnDate)) {
            throw new \InvalidArgumentException('Invalid date format.');
        }

        $timezone = new \DateTimeZone('Pacific/Auckland');
        $pickupDay = new \DateTimeImmutable($pickupDate . ' 00:00:00', $timezone);
        $returnDay = new \DateTimeImmutable($returnDate . ' 00:00:00', $timezone);

        if ($pickupDay < new \DateTimeImmutable('today', $timezone) || $returnDay < $pickupDay) {
            throw new \InvalidArgumentException('The selected rental period is invalid.');
        }

        if (($closure = $this->closures?->findOverlapping($pickupDay, $returnDay)) !== null) {
            throw new RentalClosedException($closure->getMessage());
        }

        $dayCount = (int) $pickupDay->diff($returnDay)->days + 1;
        if ($dayCount === 1 && $duration === RentalPeriod::HALF_DAY) {
            if (!in_array($pickupHour, [8, 12], true)) {
                throw new \InvalidArgumentException('A half-day must start at 08:00 or 12:00.');
            }

            return new RentalPeriod(
                $pickupDay->setTime($pickupHour, 0),
                $pickupDay->setTime($pickupHour + 4, 0),
                RentalPeriod::HALF_DAY,
                1,
            );
        }

        if ($duration !== RentalPeriod::FULL_DAY) {
            throw new \InvalidArgumentException('Invalid rental duration.');
        }

        if ($dayCount === 1) {
            return new RentalPeriod($pickupDay->setTime(8, 0), $returnDay->setTime(16, 0), RentalPeriod::FULL_DAY, 1);
        }

        if (!in_array($pickupHour, [8, 12, 16], true) || !in_array($returnHour, [8, 12, 16], true)) {
            throw new \InvalidArgumentException('Invalid pickup or return time.');
        }

        return new RentalPeriod(
            $pickupDay->setTime($pickupHour, 0),
            $returnDay->setTime($returnHour, 0),
            RentalPeriod::FULL_DAY,
            $dayCount,
        );
    }
}
