<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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

    #[Route('/account/password', name: 'account_password', methods: ['GET', 'POST'], priority: 10)]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $errors = [];
        if ($request->isMethod('POST')) {
            $currentPassword = (string) $request->request->get('current_password', '');
            $password = (string) $request->request->get('password', '');
            $confirmation = (string) $request->request->get('confirm_password', '');

            if (!$this->isCsrfTokenValid('change_password', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'The form expired. Please try again.';
            }
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $errors[] = 'The current password is incorrect.';
            }
            $this->validateNewPassword($password, $confirmation, $errors);

            if ($errors === []) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->clearPasswordReset();
                $entityManager->flush();
                $this->addFlash('success', 'Your password has been changed.');

                return $this->redirectToRoute('account_password');
            }
        }

        return $this->render('security/change_password.html.twig', ['errors' => $errors]);
    }

    #[Route('/forgot-password', name: 'forgot_password', methods: ['GET', 'POST'], priority: 10)]
    public function forgotPassword(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('forgot_password', (string) $request->request->get('_csrf_token'))) {
                return $this->render('security/forgot_password.html.twig', [
                    'error' => 'The form expired. Please try again.',
                ]);
            }

            $email = strtolower(trim((string) $request->request->get('email', '')));
            $user = $users->findOneBy(['email' => $email]);
            if ($user instanceof User) {
                $token = bin2hex(random_bytes(32));
                $user
                    ->setPasswordResetTokenHash(hash('sha256', $token))
                    ->setPasswordResetExpiresAt(new \DateTimeImmutable('+1 hour'));
                $entityManager->flush();

                $resetUrl = $this->generateUrl('reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                $mailer->send((new Email())
                    ->from('no-reply@suncamel.co.nz')
                    ->to($user->getEmail())
                    ->subject('Reset your SunCamel password')
                    ->text("Use the following link to reset your SunCamel password. It is valid for one hour:\n\n".$resetUrl."\n\nIf you did not request this, you can ignore this email."));
            }

            $this->addFlash('success', 'If an account exists for that email address, a reset link has been sent.');

            return $this->redirectToRoute('forgot_password');
        }

        return $this->render('security/forgot_password.html.twig', ['error' => null]);
    }

    #[Route('/reset-password/{token}', name: 'reset_password', methods: ['GET', 'POST'], priority: 10)]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $users,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $users->findOneBy(['passwordResetTokenHash' => hash('sha256', $token)]);
        if (!$user instanceof User || $user->getPasswordResetExpiresAt() === null || $user->getPasswordResetExpiresAt() <= new \DateTimeImmutable()) {
            return $this->render('security/reset_password.html.twig', ['invalid' => true, 'errors' => []]);
        }

        $errors = [];
        if ($request->isMethod('POST')) {
            $password = (string) $request->request->get('password', '');
            $confirmation = (string) $request->request->get('confirm_password', '');
            if (!$this->isCsrfTokenValid('reset_password_'.$token, (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'The form expired. Please try again.';
            }
            $this->validateNewPassword($password, $confirmation, $errors);

            if ($errors === []) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->clearPasswordReset();
                $entityManager->flush();
                $this->addFlash('success', 'Your password has been reset. You can now login.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'invalid' => false,
            'errors' => $errors,
            'token' => $token,
        ]);
    }

    /** @param list<string> $errors */
    private function validateNewPassword(string $password, string $confirmation, array &$errors): void
    {
        if (strlen($password) < 8) {
            $errors[] = 'Password must contain at least 8 characters.';
        } elseif ($password !== $confirmation) {
            $errors[] = 'Password confirmation does not match.';
        }
    }

    #[Route('/logout', name: 'app_logout', priority: 10)]
    public function logout(): void
    {
        throw new \LogicException('This method is intercepted by the security firewall.');
    }
}
