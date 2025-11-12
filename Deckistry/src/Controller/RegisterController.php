<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterTypeForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterController extends AbstractController

{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request,EntityManagerInterface $entityManager,UserPasswordHasherInterface $passwordEncoder): Response {
        $user = new User();
        $form = $this->createForm(RegisterTypeForm::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Set default role
            $user->setRoles(['ROLE_USER']);

            $user->setPassword(
                $passwordEncoder->hashPassword($user, $form->get('plainPassword')->getData())
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Inscription réussie !');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('register/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/register/check-pseudo', name: 'check_pseudo', methods: ['POST'])]
    public function checkPseudo(Request $request, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $pseudo = $data['pseudo'] ?? null;
        if (!$pseudo) {
            return new JsonResponse(['exists' => false]);
        }

        $exists = (bool) $userRepository->findOneBy(['username' => $pseudo]);
        return new JsonResponse(['exists' => $exists]);
    }

    #[Route('/register/check-email', name: 'check_email', methods: ['POST'])]
    public function checkEmail(Request $request, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        if (!$email) {
            return new JsonResponse(['exists' => false]);
        }

        $exists = (bool) $userRepository->findOneBy(['email' => $email]);
        return new JsonResponse(['exists' => $exists]);
    }
}


    // #[Route('/register', name: 'app_register')]
    // public function index(): Response
    // {
    //     return $this->render('register/index.html.twig', [
    //         'controller_name' => 'RegisterController',