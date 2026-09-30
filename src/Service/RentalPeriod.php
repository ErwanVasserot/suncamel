<?php

namespace App\Service;

final readonly class RentalPeriod
{
    public const HALF_DAY = 'half_day';
    public const FULL_DAY = 'full_day';

    public function __construct(
        public \DateTimeImmutable $pickup,
        public \DateTimeImmutable $return,
        public string $slot,
        public int $dayCount,
    ) {
    }

    public function isHalfDay(): bool
    {
        return $this->slot === self::HALF_DAY;
    }

    public function label(): string
    {
        if ($this->isHalfDay()) {
            return $this->pickup->format('H:i') === '08:00'
                ? 'Morning (08:00–12:00)'
                : 'Afternoon (12:00–16:00)';
        }

        return $this->dayCount === 1 ? 'Full day (08:00–16:00)' : $this->dayCount . ' days';
    }
}
