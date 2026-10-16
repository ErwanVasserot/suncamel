<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Service\BookingEmailService;
use App\Service\StripeRefundService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class BookingCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly BookingEmailService $bookingEmails,
        private readonly StripeRefundService $stripeRefunds,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Booking::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $cancel = Action::new('cancelBooking', 'Annuler', 'fa fa-ban')
            ->linkToUrl(fn (Booking $booking): string => $this->urlGenerator->generate('admin_booking_cancel', [
                'entityId' => $booking->getId(),
                'token' => $this->csrfTokenManager->getToken('cancel_booking_'.$booking->getId())->getValue(),
            ]))
            ->displayIf(static fn (Booking $booking): bool => $booking->getStatus() === Booking::STATUS_PAID)
            ->renderAsForm()
            ->asDangerAction()
            ->askConfirmation(
                'Confirmer l’annulation et le remboursement Stripe intégral de cette réservation ?',
                'Annuler et rembourser',
            );

        return $actions
            ->disable(Crud::PAGE_NEW)
            ->add(Crud::PAGE_INDEX, $cancel)
            ->add(Crud::PAGE_DETAIL, $cancel)
            ->add(Crud::PAGE_EDIT, $cancel);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('reference', 'Reference');
        yield AssociationField::new('user', 'Client');
        yield EmailField::new('email', 'E-mail');
        yield ChoiceField::new('status', 'Statut')->setChoices([
            'En attente' => Booking::STATUS_PENDING,
            'Payée' => Booking::STATUS_PAID,
            'Annulée' => Booking::STATUS_CANCELLED,
        ])->hideOnForm();
        yield MoneyField::new('totalAmount', 'Total')->setCurrencyPropertyPath('currency')->setStoredAsCents();
        yield TextField::new('promotionCode', 'Code promotionnel')->hideOnForm();
        yield IntegerField::new('promotionPercent', 'Réduction (%)')->hideOnForm();
        yield MoneyField::new('discountAmount', 'Montant de la remise')->setCurrencyPropertyPath('currency')->setStoredAsCents()->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield DateTimeField::new('expiresAt', 'Expire le')->hideOnForm();
        yield DateTimeField::new('paidAt', 'Payée le')->hideOnForm();
        yield TextField::new('stripeSessionId', 'Session Stripe')->hideOnForm();
        yield TextField::new('stripePaymentIntentId', 'Paiement Stripe')->hideOnForm();
        yield TextField::new('stripeRefundId', 'Remboursement Stripe')->hideOnForm();
        yield TextField::new('stripeRefundStatus', 'Statut du remboursement')->hideOnForm();
        yield DateTimeField::new('refundedAt', 'Remboursement créé le')->hideOnForm();
    }

    #[Route('/admin/booking/{entityId}/cancel', name: 'admin_booking_cancel', methods: ['POST'])]
    public function cancelBooking(
        int $entityId,
        Request $request,
        BookingRepository $bookings,
        EntityManagerInterface $entityManager,
    ): RedirectResponse
    {
        $entityInstance = $bookings->find($entityId);
        if (!$entityInstance instanceof Booking) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        $token = new CsrfToken('cancel_booking_'.$entityInstance->getId(), (string) $request->query->get('token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw $this->createAccessDeniedException('Action d’annulation invalide.');
        }

        if ($entityInstance->getStatus() !== Booking::STATUS_PAID) {
            $this->addFlash('warning', 'Seule une réservation payée peut être annulée.');
        } else {
            try {
                $refund = $this->stripeRefunds->refund($entityInstance);
                $entityInstance
                    ->setStripePaymentIntentId($refund['payment_intent'])
                    ->markRefunded($refund['id'], $refund['status'])
                    ->setStatus(Booking::STATUS_CANCELLED);
                $entityManager->flush();
                $this->bookingEmails->sendBookingCancelledEmail($entityInstance);
                $this->addFlash('success', sprintf('La réservation %s a été annulée et son remboursement Stripe a été créé.', $entityInstance->getReference()));
            } catch (\Throwable $exception) {
                $this->addFlash('danger', 'La réservation n’a pas été annulée : '.$exception->getMessage());
            }
        }

        return $this->redirect($this->adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl());
    }
}
