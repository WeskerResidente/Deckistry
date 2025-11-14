<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Form\DeckType;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DeckController extends AbstractController
{
    #[Route('/decks', name: 'app_decks')]
    public function index(DeckRepository $deckRepository): Response
    {
        // Affiche tous les decks publics
        $decks = $deckRepository->findBy(['isPrivate' => false], ['createdAt' => 'DESC']);
        
        return $this->render('deck/index.html.twig', [
            'decks' => $decks,
        ]);
    }

    #[Route('/deck/new', name: 'app_deck_new')]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $deck = new Deck();
        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $deck->setUser($this->getUser());
            $deck->setCreatedAt(new \DateTime());
            
            $em->persist($deck);
            $em->flush();

            $this->addFlash('success', 'Deck créé avec succès !');
            return $this->redirectToRoute('app_deck_edit', ['id' => $deck->getId()]);
        }

        return $this->render('deck/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/deck/{id}', name: 'app_deck_view', requirements: ['id' => '\d+'])]
    public function view(Deck $deck): Response
    {
        // Vérifier si le deck est privé et que l'utilisateur n'est pas le propriétaire
        if ($deck->isPrivate() && $deck->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Ce deck est privé.');
        }

        return $this->render('deck/view.html.twig', [
            'deck' => $deck,
        ]);
    }

    #[Route('/deck/{id}/edit', name: 'app_deck_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Deck $deck, Request $request, EntityManagerInterface $em): Response
    {
        // Vérifier que l'utilisateur est le propriétaire
        if ($deck->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas éditer ce deck.');
        }

        return $this->render('deck/edit.html.twig', [
            'deck' => $deck,
        ]);
    }

    #[Route('/my-decks', name: 'app_my_decks')]
    #[IsGranted('ROLE_USER')]
    public function myDecks(DeckRepository $deckRepository): Response
    {
        $decks = $deckRepository->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('deck/my-decks.html.twig', [
            'decks' => $decks,
        ]);
    }

    #[Route('/deck/{id}/comment', name: 'app_deck_comment', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addComment(Deck $deck, Request $request, EntityManagerInterface $em): Response
    {
        $content = $request->request->get('content');
        
        if (empty(trim($content))) {
            $this->addFlash('error', 'Comment cannot be empty.');
            return $this->redirectToRoute('app_deck_view', ['id' => $deck->getId()]);
        }

        $comment = new \App\Entity\Comment();
        $comment->setDeck($deck);
        $comment->setUser($this->getUser());
        $comment->setContent($content);
        $comment->setCreatedAt(new \DateTime());

        $em->persist($comment);
        $em->flush();

        $this->addFlash('success', 'Comment posted successfully!');
        return $this->redirectToRoute('app_deck_view', ['id' => $deck->getId()]);
    }

    #[Route('/comment/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteComment(int $id, EntityManagerInterface $em): Response
    {
        $comment = $em->getRepository(\App\Entity\Comment::class)->find($id);
        
        if (!$comment) {
            throw $this->createNotFoundException('Comment not found');
        }

        // Vérifier que l'utilisateur est l'auteur du commentaire ou le propriétaire du deck
        if ($comment->getUser() !== $this->getUser() && $comment->getDeck()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot delete this comment.');
        }

        $deckId = $comment->getDeck()->getId();
        
        $em->remove($comment);
        $em->flush();

        $this->addFlash('success', 'Comment deleted.');
        return $this->redirectToRoute('app_deck_view', ['id' => $deckId]);
    }
}

