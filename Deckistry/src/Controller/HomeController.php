<?php

namespace App\Controller;

use App\Repository\DeckRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        DeckRepository $deckRepository,
        UserRepository $userRepository
    ): Response {
        // Récupérer les decks populaires
        $popularDecks = $deckRepository->findPopular(6);
        
        // Récupérer les decks récents
        $recentDecks = $deckRepository->findRecent(6);
        
        // Statistiques globales
        $stats = [
            'totalUsers' => $userRepository->countTotal(),
            'totalDecks' => count($deckRepository->findAll()),
        ];

        return $this->render('home/index.html.twig', [
            'popularDecks' => $popularDecks,
            'recentDecks' => $recentDecks,
            'stats' => $stats,
        ]);
    }
}
