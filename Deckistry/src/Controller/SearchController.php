<?php

namespace App\Controller;

use App\Service\ScryfallService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class SearchController extends AbstractController
{
    public function __construct(
        private readonly ScryfallService $scryfallService,
        private readonly LoggerInterface $logger
    ) {}

    #[Route('/search', name: 'app_search')]
    public function index(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $page = max(1, (int) $request->query->get('page', 1));
        $cards = [];
        $totalCards = 0;
        $hasMore = false;
        $error = null;

        if ($query) {
            try {
                $result = $this->scryfallService->searchCards($query, $page);
                $cards = $result['data'] ?? [];
                $totalCards = $result['total_cards'] ?? 0;
                $hasMore = $result['has_more'] ?? false;
            } catch (\Exception $e) {
                $this->logger->error('Search error', [
                    'query' => $query,
                    'error' => $e->getMessage()
                ]);
                $error = 'Une erreur est survenue lors de la recherche. Veuillez réessayer.';
            }
        }

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'cards' => $cards,
            'total_cards' => $totalCards,
            'has_more' => $hasMore,
            'current_page' => $page,
            'error' => $error,
        ]);
    }

    #[Route('/api/cards/autocomplete', name: 'api_cards_autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request): Response
    {
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return $this->json([]);
        }

        try {
            $suggestions = $this->scryfallService->autocomplete($query);
            return $this->json($suggestions);
        } catch (\Exception $e) {
            $this->logger->error('Autocomplete error', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return $this->json([], 500);
        }
    }

    #[Route('/api/cards/random', name: 'api_cards_random', methods: ['GET'])]
    public function random(Request $request): Response
    {
        $query = $request->query->get('q', '');

        try {
            $card = $this->scryfallService->getRandomCard($query);
            return $this->json($card);
        } catch (\Exception $e) {
            $this->logger->error('Random card error', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
