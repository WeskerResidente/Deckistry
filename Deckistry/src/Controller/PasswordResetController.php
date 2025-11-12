<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordResetController extends AbstractController
{
    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password')]
    public function request(Request $request, UserRepository $userRepository, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $emailAddress = $request->request->get('email');
            $user = $userRepository->findOneBy(['email' => $emailAddress]);

            if ($user) {
                // Générer un token unique
                $resetToken = bin2hex(random_bytes(32));
                $user->setResetToken($resetToken);
                $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));
                
                $em->flush();

                // Générer le lien de réinitialisation
                $resetUrl = $this->generateUrl('app_reset_password', [
                    'token' => $resetToken
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                // Envoyer l'email
                try {
                    $emailMessage = (new TemplatedEmail())
                        ->from('noreply@deckistry.com')
                        ->to($user->getEmail())
                        ->subject('Réinitialisation de votre mot de passe')
                        ->htmlTemplate('password_reset/email.html.twig')
                        ->context([
                            'resetUrl' => $resetUrl,
                            'user' => $user,
                        ]);

                    $mailer->send($emailMessage);
                } catch (\Exception $e) {
                    // Log l'erreur pour debug
                    error_log('Erreur email reset password: ' . $e->getMessage());
                }
            }
            
            // Toujours rediriger vers la page de succès (pour ne pas révéler si l'email existe)
            return $this->redirectToRoute('app_password_reset_success');
        }

        return $this->render('password_reset/request.html.twig');
    }

    #[Route('/mot-de-passe-oublie/email-envoye', name: 'app_password_reset_success')]
    public function success(): Response
    {
        // Vérifier si on est en mode null (développement)
        $mailerDsn = $_ENV['MAILER_DSN'] ?? '';
        $isNullMailer = str_starts_with($mailerDsn, 'null://');
        
        if ($isNullMailer) {
            return $this->render('password_reset/success_dev.html.twig');
        }
        
        return $this->render('password_reset/success.html.twig');
    }

    #[Route('/reinitialiser-mot-de-passe/{token}', name: 'app_reset_password')]
    public function reset(
        string $token,
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->isResetTokenValid()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            } elseif (strlen($password) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
            } else {
                // Hasher et enregistrer le nouveau mot de passe
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
                
                // Supprimer le token
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                
                $em->flush();

                $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('password_reset/reset.html.twig', [
            'token' => $token,
        ]);
    }

    /**
     * Route de DEBUG uniquement - À supprimer en production
     * Permet de générer un lien de reset sans email
     */
    #[Route('/debug/reset-link/{email}', name: 'app_debug_reset_link')]
    public function debugResetLink(
        string $email,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        // Vérifier qu'on est en environnement dev
        if ($_ENV['APP_ENV'] !== 'dev') {
            throw $this->createNotFoundException();
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            return new Response('Utilisateur non trouvé avec l\'email: ' . $email);
        }

        // Générer un token
        $resetToken = bin2hex(random_bytes(32));
        $user->setResetToken($resetToken);
        $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));
        $em->flush();

        // Générer le lien
        $resetUrl = $this->generateUrl('app_reset_password', [
            'token' => $resetToken
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return new Response(
            '<h1>Lien de réinitialisation (DEBUG)</h1>' .
            '<p>Email: ' . $email . '</p>' .
            '<p>Token valide jusqu\'à: ' . $user->getResetTokenExpiresAt()->format('Y-m-d H:i:s') . '</p>' .
            '<p><a href="' . $resetUrl . '">Cliquez ici pour réinitialiser</a></p>' .
            '<p>Ou copiez ce lien: <br><code>' . $resetUrl . '</code></p>'
        );
    }

    /**
     * Route de test d'envoi d'email
     */
    #[Route('/debug/test-email/{email}', name: 'app_debug_test_email')]
    public function debugTestEmail(
        string $email,
        MailerInterface $mailer
    ): Response {
        // Vérifier qu'on est en environnement dev
        if ($_ENV['APP_ENV'] !== 'dev') {
            throw $this->createNotFoundException();
        }

        try {
            $emailMessage = (new TemplatedEmail())
                ->from('noreply@deckistry.com')
                ->to($email)
                ->subject('Test d\'envoi d\'email')
                ->htmlTemplate('password_reset/email.html.twig')
                ->context([
                    'resetUrl' => 'http://example.com/test',
                    'user' => (object)['username' => 'Test User'],
                ]);

            $mailer->send($emailMessage);
            
            return new Response(
                '<h1>Email de test envoyé avec succès!</h1>' .
                '<p>Vérifiez votre boîte Mailtrap (https://mailtrap.io/)</p>' .
                '<p>Email envoyé à: ' . $email . '</p>'
            );
        } catch (\Exception $e) {
            return new Response(
                '<h1>Erreur lors de l\'envoi</h1>' .
                '<p style="color: red;">Erreur: ' . $e->getMessage() . '</p>' .
                '<pre>' . $e->getTraceAsString() . '</pre>'
            );
        }
    }
}
