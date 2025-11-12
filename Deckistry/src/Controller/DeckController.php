<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeckController extends AbstractController
{
    #[Route('/decks', name: 'app_decks')]
    public function index(): Response
    {
        return $this->render('deck/index.html.twig', [
            'message' => 'Browse all decks - Coming soon!',
        ]);
    }

    #[Route('/deck/{id}', name: 'app_deck_view')]
    public function view(int $id): Response
    {
        return $this->render('deck/view.html.twig', [
            'deckId' => $id,
        ]);
    }

    #[Route('/deck-builder', name: 'app_deck_builder')]
    public function builder(): Response
    {
        return $this->render('deck/builder.html.twig', [
            'message' => 'Deck Builder - Coming soon!',
        ]);
    }

    #[Route('/my-decks', name: 'app_my_decks')]
    public function myDecks(): Response
    {
        return $this->render('deck/my-decks.html.twig', [
            'message' => 'My Decks - Coming soon!',
        ]);
    }
}
