<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Nom');
        yield SlugField::new('slug')->setTargetFieldName('title');
        yield TextField::new('tagline', 'Accroche');
        yield TextareaField::new('summary', 'Description');
        yield TextField::new('price', 'Prix');
        yield TextField::new('duration', 'Duree');
        yield TextField::new('collectionSlug', 'Collection')->setHelp('Ex: adventure-bikes, cruiser-e-bikes');
        yield ImageField::new('coverImage', 'Image liste')
            ->setBasePath('/images/suncamel/products')
            ->setUploadDir('public/images/suncamel/products')
            ->setUploadedFileNamePattern('[slug]-cover-[timestamp].[extension]')
            ->setRequired(false);
        yield ImageField::new('heroImage', 'Image hero')
            ->setBasePath('/images/suncamel/products')
            ->setUploadDir('public/images/suncamel/products')
            ->setUploadedFileNamePattern('[slug]-hero-[timestamp].[extension]')
            ->setRequired(false);
        yield ArrayField::new('highlights', 'Points forts');
        yield ArrayField::new('specs', 'Specs');
        yield IntegerField::new('stockQuantity', 'Articles existants')
            ->setHelp('Nombre total de velos disponibles pour ce produit. 0 masque la carte sur la page disponibilites.');
        yield IntegerField::new('position', 'Ordre');
        yield BooleanField::new('isActive', 'Actif');
    }
}
