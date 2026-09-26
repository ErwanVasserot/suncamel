<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BookingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Booking::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Crud::PAGE_NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('reference', 'Reference');
        yield AssociationField::new('user', 'Client');
        yield TextField::new('status', 'Statut');
        yield MoneyField::new('totalAmount', 'Total')->setCurrencyPropertyPath('currency')->setStoredAsCents();
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield DateTimeField::new('expiresAt', 'Expire le')->hideOnForm();
        yield DateTimeField::new('paidAt', 'Payée le')->hideOnForm();
        yield TextField::new('stripeSessionId', 'Session Stripe')->hideOnForm();
    }
}
