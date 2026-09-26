<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', priority: 10)]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('home');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/register', name: 'app_register', priority: 10)]
    public function register(
        Request $request,
        UserRepository $users,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('home');
        }

        $errors = [];
        $email = '';

        if ($request->isMethod('POST')) {
            $email = strtolower(trim((string) $request->request->get('email', '')));
            $password = (string) $request->request->get('password', '');
            $confirmPassword = (string) $request->request->get('confirm_password', '');

            if (!$this->isCsrfTokenValid('register', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'The registration form expired. Please try again.';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid email address.';
            } elseif ($users->findOneBy(['email' => $email]) !== null) {
                $errors[] = 'An account already exists with this email address.';
            }

            if (strlen($password) < 8) {
                $errors[] = 'Password must contain at least 8 characters.';
            } elseif ($password !== $confirmPassword) {
                $errors[] = 'Password confirmation does not match.';
            }

            if ($errors === []) {
                $user = (new User())
                    ->setEmail($email)
                    ->setRoles(['ROLE_USER']);
                $user->setPassword($passwordHasher->hashPassword($user, $password));

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Account created. You can now login.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', [
            'email' => $email,
            'errors' => $errors,
        ]);
    }

    #[Route('/logout', name: 'app_logout', priority: 10)]
    public function logout(): void
    {
        throw new \LogicException('This method is intercepted by the security firewall.');
    }
}
