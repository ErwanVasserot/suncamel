<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('@EasyAdmin/page/content.html.twig', [
            'content_title' => 'Administration SunCamel',
            'content' => 'Utilisez le menu pour gérer les produits, les images et les Q&A.',
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
