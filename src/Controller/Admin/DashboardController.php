<?php

namespace App\Controller\Admin;

use App\Repository\BookingRepository;
use App\Repository\ProductRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly BookingRepository $bookings,
        private readonly ProductRepository $products,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function index(): Response
    {
        $timezone = new \DateTimeZone('Pacific/Auckland');
        $today = new \DateTimeImmutable('today', $timezone);
        $offset = max(-365, min(365, (int) $this->requestStack->getCurrentRequest()?->query->get('window', 0)));
        $chartStart = $today->modify(sprintf('%+d days', $offset));
        $chartEnd = $chartStart->modify('+30 days');
        $yearStart = $today->setDate((int) $today->format('Y'), 1, 1);
        $nextYear = $yearStart->modify('+1 year');
        $activeProducts = $this->products->findActiveOrdered();
        $totalDailyStock = array_sum(array_map(static fn ($product): int => $product->getStockQuantity(), $activeProducts));
        $stockByProduct = [];
        $productNames = [];
        foreach ($activeProducts as $product) {
            $stockByProduct[$product->getId()] = $product->getStockQuantity();
            $productNames[$product->getId()] = $product->getTitle();
        }
        $chartBookings = $this->bookings->findActiveOverlapping($chartStart, $chartEnd);
        $futureBookings = $this->bookings->findActiveOverlapping($today, $nextYear);

        $daily = [];
        $bookedUnitDays = 0;
        for ($day = $chartStart; $day < $chartEnd; $day = $day->modify('+1 day')) {
            $dayEnd = $day->modify('+1 day');
            $reservedByProduct = [];
            foreach ($chartBookings as $booking) {
                foreach ($booking->getItems() as $item) {
                    $productId = $item->getProduct()?->getId();
                    if ($productId !== null && isset($stockByProduct[$productId]) && $item->getPickupAt() < $dayEnd && $item->getReturnAt() > $day) {
                        $reservedByProduct[$productId] = ($reservedByProduct[$productId] ?? 0) + $item->getQuantity();
                    }
                }
            }

            $reserved = 0;
            $remaining = 0;
            $reservedBreakdown = [];
            foreach ($stockByProduct as $productId => $stock) {
                $productReserved = min($stock, $reservedByProduct[$productId] ?? 0);
                $reserved += $productReserved;
                $remaining += max(0, $stock - $productReserved);
                $reservedBreakdown[] = [
                    'product' => $productNames[$productId],
                    'quantity' => $productReserved,
                ];
            }
            $tooltipLines = [$day->format('d/m/Y'), sprintf('%d vélo(s) réservé(s)', $reserved)];
            foreach ($reservedBreakdown as $breakdown) {
                $tooltipLines[] = sprintf('%s : %d', $breakdown['product'], $breakdown['quantity']);
            }
            $tooltipLines[] = sprintf('%d vélo(s) disponible(s)', $remaining);
            $bookedUnitDays += $reserved;
            $daily[] = [
                'date' => $day,
                'label' => $day->format('d/m'),
                'reserved' => $reserved,
                'remaining' => $remaining,
                'reserved_percent' => $totalDailyStock > 0 ? round($reserved / $totalDailyStock * 100, 1) : 0,
                'remaining_percent' => $totalDailyStock > 0 ? round($remaining / $totalDailyStock * 100, 1) : 0,
                'reserved_breakdown' => $reservedBreakdown,
                'tooltip' => implode("\n", $tooltipLines),
            ];
        }

        $upcoming = [];
        $futureOrderAmount = 0;
        foreach ($futureBookings as $booking) {
            $pickup = null;
            $return = null;
            $quantity = 0;
            $hasFuturePickup = false;
            foreach ($booking->getItems() as $item) {
                $pickup = $pickup === null || $item->getPickupAt() < $pickup ? $item->getPickupAt() : $pickup;
                $return = $return === null || $item->getReturnAt() > $return ? $item->getReturnAt() : $return;
                $quantity += $item->getQuantity();
                $hasFuturePickup = $hasFuturePickup || ($item->getPickupAt() >= $today && $item->getPickupAt() < $nextYear);
            }

            if ($hasFuturePickup) {
                $futureOrderAmount += $booking->getTotalAmount();
            }
            if ($pickup !== null && $return !== null && $return > $today) {
                $upcoming[] = compact('booking', 'pickup', 'return', 'quantity');
            }
        }
        usort($upcoming, static fn (array $a, array $b): int => $a['pickup'] <=> $b['pickup']);

        $capacityUnitDays = $totalDailyStock * count($daily);
        $bookingRate = $capacityUnitDays > 0 ? round($bookedUnitDays / $capacityUnitDays * 100, 1) : 0.0;
        $reservationChartTitle = $chartEnd <= $today
            ? 'Réservations passées'
            : ($chartStart < $today ? 'Réservations passées et à venir' : 'Réservations à venir');

        return $this->render('admin/dashboard.html.twig', [
            'daily' => $daily,
            'upcoming' => array_slice($upcoming, 0, 10),
            'total_daily_stock' => $totalDailyStock,
            'booking_rate' => $bookingRate,
            'received_this_year' => $this->bookings->paidAmountBetween($yearStart, $nextYear),
            'future_order_amount' => $futureOrderAmount,
            'currency' => 'NZD',
            'window_offset' => $offset,
            'previous_window_offset' => max(-365, $offset - 30),
            'next_window_offset' => min(365, $offset + 30),
            'window_start' => $chartStart,
            'window_end' => $chartEnd->modify('-1 day'),
            'reservation_chart_title' => $reservationChartTitle,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SunCamel Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(ProductCrudController::class, 'Types de produit', 'fa fa-bicycle');
        yield MenuItem::linkTo(PricingTierCrudController::class, 'Tarifs dégressifs', 'fa fa-tags');
        yield MenuItem::linkTo(ProductImageCrudController::class, 'Images produit', 'fa fa-image');
        yield MenuItem::linkTo(BookingCrudController::class, 'Réservations', 'fa fa-calendar-check');
        yield MenuItem::linkTo(FaqItemCrudController::class, 'Q&A', 'fa fa-question-circle');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');
        yield MenuItem::linkToUrl('Voir le site', 'fa fa-arrow-left', '/');
    }
}
