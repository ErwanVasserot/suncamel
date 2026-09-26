<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email', 'Email');
        yield ChoiceField::new('roles', 'Profil')
            ->allowMultipleChoices()
            ->setChoices([
                'User' => 'ROLE_USER',
                'Admin' => 'ROLE_ADMIN',
            ]);
        yield TextField::new('plainPassword', 'Mot de passe')
            ->setFormType(PasswordType::class)
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->onlyOnForms();
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        $this->hashPassword($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        $this->hashPassword($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashPassword(mixed $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        $plainPassword = $entityInstance->getPlainPassword();
        if ($plainPassword === null || $plainPassword === '') {
            return;
        }

        $entityInstance->setPassword($this->passwordHasher->hashPassword($entityInstance, $plainPassword));
        $entityInstance->eraseCredentials();
    }
}
