<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Service\BookingEmailService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BookingCrudController extends AbstractCrudController
{
    public function __construct(private readonly BookingEmailService $bookingEmails)
    {
    }

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
        yield EmailField::new('email', 'E-mail');
        yield TextField::new('status', 'Statut');
        yield MoneyField::new('totalAmount', 'Total')->setCurrencyPropertyPath('currency')->setStoredAsCents();
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield DateTimeField::new('expiresAt', 'Expire le')->hideOnForm();
        yield DateTimeField::new('paidAt', 'Payée le')->hideOnForm();
        yield TextField::new('stripeSessionId', 'Session Stripe')->hideOnForm();
    }

    public function updateEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        if (!$entityInstance instanceof Booking) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $originalData = $entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance);
        $wasPaid = ($originalData['status'] ?? null) === Booking::STATUS_PAID;
        $isNowCancelled = $entityInstance->getStatus() === Booking::STATUS_CANCELLED;

        parent::updateEntity($entityManager, $entityInstance);

        if ($wasPaid && $isNowCancelled) {
            $this->bookingEmails->sendBookingCancelledEmail($entityInstance);
        }
    }
}
