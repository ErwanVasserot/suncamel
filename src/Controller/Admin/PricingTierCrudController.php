<?php

namespace App\Controller\Admin;

use App\Entity\PricingTier;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;

class PricingTierCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PricingTier::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('product', 'Produit')->setRequired(true);
        yield IntegerField::new('minimumDays', 'À partir de (jours)')
            ->setHelp('Minimum 2. Le palier le plus élevé atteint est appliqué à tous les jours.');
        yield MoneyField::new('dailyAmount', 'Prix par jour')
            ->setCurrency('NZD')
            ->setStoredAsCents();
    }
}
