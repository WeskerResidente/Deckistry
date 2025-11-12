<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/settings', name: 'app_settings')]
    public function settings(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Gestion de la mise à jour du profil
        if ($request->isMethod('POST') && $request->request->get('action') === 'update_profile') {
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $username = $request->request->get('username');
            
            // Gestion de l'upload d'avatar
            $avatarFile = $request->files->get('avatar');
            if ($avatarFile) {
                // Validation du fichier
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $fileExtension = strtolower($avatarFile->getClientOriginalExtension());
                
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $this->addFlash('error', 'Format de fichier non autorisé. Utilisez JPG, PNG, GIF ou WEBP.');
                } elseif ($avatarFile->getSize() > 2 * 1024 * 1024) { // 2 Mo max
                    $this->addFlash('error', 'Le fichier est trop volumineux. Taille maximale : 2 Mo.');
                } else {
                    // Supprimer l'ancien avatar s'il existe
                    if ($user->getAvatar()) {
                        $oldAvatarPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $user->getAvatar();
                        if (file_exists($oldAvatarPath)) {
                            unlink($oldAvatarPath);
                        }
                    }
                    
                    // Générer un nom de fichier unique
                    $newFilename = uniqid() . '.' . $fileExtension;
                    
                    // Déplacer le fichier
                    $avatarFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                        $newFilename
                    );
                    
                    // Sauvegarder le nom du fichier dans la base de données
                    $user->setAvatar($newFilename);
                }
            }
            
            if ($nom && $prenom && $username) {
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setUsername($username);
                
                $em->flush();
                $this->addFlash('success', 'Profil mis à jour avec succès !');
                return $this->redirectToRoute('app_settings');
            }
        }

        // Gestion de la suppression d'avatar
        if ($request->isMethod('POST') && $request->request->get('action') === 'delete_avatar') {
            if ($user->getAvatar()) {
                $avatarPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $user->getAvatar();
                if (file_exists($avatarPath)) {
                    unlink($avatarPath);
                }
                $user->setAvatar(null);
                $em->flush();
                $this->addFlash('success', 'Photo de profil supprimée avec succès !');
            }
            return $this->redirectToRoute('app_settings');
        }

        // Gestion du changement de mot de passe
        if ($request->isMethod('POST') && $request->request->get('action') === 'change_password') {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');
            
            // Vérifier le mot de passe actuel
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            } elseif ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
            } elseif (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins 6 caractères.');
            } else {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $em->flush();
                
                $this->addFlash('success', 'Mot de passe modifié avec succès !');
                return $this->redirectToRoute('app_settings');
            }
        }

        return $this->render('user/settings.html.twig', [
            'user' => $user,
        ]);
    }

    // Route moved: actual registration handled by RegisterController
    #[Route('/register/info', name: 'app_register_info')]
    public function register(): Response
    {
        return $this->render('user/register.html.twig');
    }
}
