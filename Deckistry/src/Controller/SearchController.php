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
        
        // Get filters
        $colors = $request->query->all('colors') ?? [];
        $types = $request->query->all('type') ?? [];
        $rarities = $request->query->all('rarity') ?? [];
        $set = $request->query->get('set', '');
        $logic = $request->query->get('logic', 'or');
        
        // Get all sets for the filter dropdown
        $sets = $this->scryfallService->getSets();
        
        $cards = [];
        $totalCards = 0;
        $hasMore = false;
        $error = null;

        // Build search query with filters
        $searchQuery = $query;
        
        // Add color filter
        if (!empty($colors)) {
            $colorFilter = '';
            if ($logic === 'or') {
                // OR logic: cards with ANY of these colors
                $colorFilter = 'c:' . implode('', $colors);
            } else {
                // AND logic: cards with ALL these colors
                foreach ($colors as $color) {
                    $colorFilter .= ' c:' . $color;
                }
            }
            $searchQuery = trim($searchQuery . ' ' . $colorFilter);
        }
        
        // Add type filter
        if (!empty($types)) {
            foreach ($types as $type) {
                $searchQuery = trim($searchQuery . ' t:' . $type);
            }
        }
        
        // Add rarity filter
        if (!empty($rarities)) {
            $rarityFilter = 'r:' . implode('|', $rarities);
            $searchQuery = trim($searchQuery . ' ' . $rarityFilter);
        }
        
        // Add set filter
        if (!empty($set)) {
            $searchQuery = trim($searchQuery . ' e:' . $set);
        }

        // Execute search if we have any query or filters
        $hasFilters = !empty($colors) || !empty($types) || !empty($rarities) || !empty($set);
        
        if (!empty($query) || $hasFilters) {
            // Clean up the search query - remove extra spaces
            $searchQuery = trim($searchQuery);
            
            // If search query is empty after trimming, it means we only have filters
            // In that case, we need to ensure there's at least something to search
            if (empty($searchQuery) && $hasFilters) {
                // This should not happen, but just in case
                $this->logger->warning('Empty search query with filters', [
                    'colors' => $colors,
                    'types' => $types,
                    'rarities' => $rarities,
                    'set' => $set
                ]);
            }
            
            $this->logger->info('Executing search', [
                'originalQuery' => $query,
                'finalQuery' => $searchQuery,
                'hasFilters' => $hasFilters,
                'colors' => $colors,
                'types' => $types,
                'rarities' => $rarities,
                'set' => $set
            ]);
            
            try {
                $result = $this->scryfallService->searchCards($searchQuery, $page);
                $cards = $result['data'] ?? [];
                $totalCards = $result['total_cards'] ?? 0;
                $hasMore = $result['has_more'] ?? false;
                
                $this->logger->info('Search results', [
                    'totalCards' => $totalCards,
                    'cardsReturned' => count($cards)
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Search error', [
                    'query' => $searchQuery,
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
            'sets' => $sets,
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
