<?php

namespace App\Controller\Admin;

use App\Entity\FaqItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FaqItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FaqItem::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('category', 'Categorie');
        yield TextField::new('question', 'Question');
        yield TextareaField::new('answerHtml', 'Reponse HTML');
        yield IntegerField::new('position', 'Ordre');
        yield BooleanField::new('isActive', 'Actif');
    }
}
