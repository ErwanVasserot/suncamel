<?php

namespace App\Controller\Admin;

use App\Entity\SiteImage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class SiteImageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return SiteImage::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Image du site')->setEntityLabelInPlural('Images du site')->setDefaultSort(['label' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('label', 'Emplacement');
        yield ImageField::new('displayImage', 'Image')->hideOnForm()->setSortable(false);
        yield TextField::new('source', 'Adresse de l’image originale')
            ->onlyOnForms()
            ->setFormTypeOption('disabled', $pageName !== Crud::PAGE_NEW)
            ->setHelp('Le remplacement s’applique partout où cette image est utilisée. Pour une nouvelle image, saisir son chemin ou son URL actuelle.');
        yield ImageField::new('image', 'Image')
            ->onlyOnForms()
            ->setBasePath('/images/suncamel/products')
            ->setUploadDir('public/images/suncamel/products')
            ->setUploadedFileNamePattern('site-[uuid].[extension]')
            ->setRequired(false)
            ->setHelp('Supprimer le remplacement rétablit l’image originale. Les fichiers sont conservés dans le volume persistant des images.');
    }
}
