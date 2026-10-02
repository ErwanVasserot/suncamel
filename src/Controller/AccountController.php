<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\User;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccountController extends AbstractController
{
    #[Route('/account/orders', name: 'account_orders', methods: ['GET'], priority: 10)]
    public function orders(BookingRepository $bookings): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Pacific/Auckland'));
        $upcoming = [];
        $past = [];

        foreach ($bookings->findForUser($user) as $booking) {
            $row = $this->orderRow($booking);
            if ($row['return'] !== null && $row['return'] >= $now) {
                $upcoming[] = $row;
            } else {
                $past[] = $row;
            }
        }

        usort($upcoming, static fn (array $a, array $b): int => ($a['pickup'] ?? $now) <=> ($b['pickup'] ?? $now));

        return $this->render('account/orders.html.twig', [
            'upcoming_orders' => $upcoming,
            'past_orders' => $past,
        ]);
    }

    /** @return array{booking: Booking, pickup: ?\DateTimeImmutable, return: ?\DateTimeImmutable, quantity: int} */
    private function orderRow(Booking $booking): array
    {
        $pickup = null;
        $return = null;
        $quantity = 0;

        foreach ($booking->getItems() as $item) {
            $pickup = $pickup === null || $item->getPickupAt() < $pickup ? $item->getPickupAt() : $pickup;
            $return = $return === null || $item->getReturnAt() > $return ? $item->getReturnAt() : $return;
            $quantity += $item->getQuantity();
        }

        return compact('booking', 'pickup', 'return', 'quantity');
    }
}
