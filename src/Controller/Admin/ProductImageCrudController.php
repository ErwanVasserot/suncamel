<?php

namespace App\Controller\Admin;

use App\Entity\ProductImage;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductImageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductImage::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('product', 'Produit');
        yield ImageField::new('image', 'Image')
            ->setBasePath('/images/suncamel/products')
            ->setUploadDir('public/images/suncamel/products')
            ->setUploadedFileNamePattern('[slug]-gallery-[timestamp].[extension]');
        yield TextField::new('alt', 'Texte alternatif')->setRequired(false);
        yield IntegerField::new('position', 'Ordre');
    }
}
