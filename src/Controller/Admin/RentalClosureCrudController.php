<?php
namespace App\Controller\Admin;

use App\Entity\RentalClosure;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class RentalClosureCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return RentalClosure::class; }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Période de fermeture')->setEntityLabelInPlural('Périodes de fermeture')->setDefaultSort(['startDate' => 'DESC']);
    }
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield DateField::new('startDate', 'Date de début');
        yield DateField::new('endDate', 'Date de fin');
        yield TextField::new('message', 'Message')->setHelp('Par défaut : Rental is closed')->setRequired(false);
    }
}
