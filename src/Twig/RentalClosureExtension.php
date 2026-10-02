<?php
namespace App\Twig;

use App\Repository\RentalClosureRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class RentalClosureExtension extends AbstractExtension
{
    public function __construct(private readonly RentalClosureRepository $closures) {}
    public function getFunctions(): array { return [new TwigFunction('rental_closures', [$this, 'getClosures'])]; }
    public function getClosures(): array
    {
        $today = new \DateTimeImmutable('today', new \DateTimeZone('Pacific/Auckland'));
        return array_map(static fn ($c) => ['start' => $c->getStartDate()->format('Y-m-d'), 'end' => $c->getEndDate()->format('Y-m-d'), 'message' => $c->getMessage()], $this->closures->findFrom($today));
    }
}
