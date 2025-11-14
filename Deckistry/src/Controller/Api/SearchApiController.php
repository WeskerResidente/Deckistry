<?php

namespace App\Controller\Api;

use App\Repository\CardRepository;
use App\Service\ScryfallService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class SearchApiController extends AbstractController
{
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, ScryfallService $scryfallService, CardRepository $cardRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        $lang = $request->query->get('lang', 'en');
        
        if (strlen($query) < 2) {
            return $this->json([
                'success' => false,
                'cards' => [],
                'message' => 'Query too short'
            ]);
        }

        try {
            $result = $scryfallService->searchCards($query, $lang);
            
            // Les cartes seront sauvegardées automatiquement lors de l'ajout au deck
            // Pour l'instant, on ne les sauvegarde pas ici pour éviter les erreurs de conversion DTO->Array
            
            return $this->json([
                'success' => true,
                'cards' => $result['data'],
                'total_cards' => $result['total_cards'],
                'has_more' => $result['has_more']
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'cards' => [],
                'message' => 'Search error: ' . $e->getMessage()
            ], 500);
        }
    }
}
