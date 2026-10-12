<?php

namespace App\Controller\Admin;

use App\Entity\BookingEmailContent;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

final class BookingEmailContentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BookingEmailContent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Messages de commande')
            ->setEntityLabelInPlural('Messages de commande')
            ->setPageTitle(Crud::PAGE_INDEX, 'Messages des e-mails de commande')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier les messages des e-mails');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextEditorField::new('confirmationMessage', 'Message de confirmation')
            ->setHelp('La référence, les vélos, les dates et le montant sont ajoutés automatiquement.')
            ->onlyOnForms();
        yield TextEditorField::new('cancellationMessage', 'Message d’annulation')
            ->setHelp('La référence de la commande et les coordonnées de contact sont ajoutées automatiquement.')
            ->onlyOnForms();
        yield DateTimeField::new('updatedAt', 'Dernière modification')->hideOnForm();
    }
}
