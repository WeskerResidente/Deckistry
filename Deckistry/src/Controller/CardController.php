<?php

namespace App\Controller;

use App\Service\ScryfallService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/cards', name: 'api_cards_')]
class CardController extends AbstractController
{
    public function __construct(
        private readonly ScryfallService $scryfallService
    ) {}

    /**
     * Search cards
     * GET /api/cards/search?q=lightning bolt&page=1
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $page = (int) $request->query->get('page', 1);

        if (empty($query)) {
            return $this->json([
                'error' => 'Query parameter "q" is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->scryfallService->searchCards($query, $page);
            
            return $this->json([
                'cards' => array_map(fn($card) => [
                    'id' => $card->id,
                    'name' => $card->name,
                    'mana_cost' => $card->manaCost,
                    'type_line' => $card->typeLine,
                    'oracle_text' => $card->oracleText,
                    'image' => $card->getBestImageUri(),
                    'set' => $card->setCode,
                    'rarity' => $card->rarity,
                ], $result['data']),
                'total_cards' => $result['total_cards'],
                'has_more' => $result['has_more'],
                'current_page' => $page,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to search cards: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get a single card by Scryfall ID
     * GET /api/cards/{id}
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            $card = $this->scryfallService->getCardById($id);
            
            return $this->json([
                'id' => $card->id,
                'name' => $card->name,
                'mana_cost' => $card->manaCost,
                'type_line' => $card->typeLine,
                'oracle_text' => $card->oracleText,
                'power' => $card->power,
                'toughness' => $card->toughness,
                'loyalty' => $card->loyalty,
                'colors' => $card->colors,
                'color_identity' => $card->colorIdentity,
                'set_code' => $card->setCode,
                'set_name' => $card->setName,
                'rarity' => $card->rarity,
                'images' => [
                    'small' => $card->imageUriSmall,
                    'normal' => $card->imageUriNormal,
                    'large' => $card->imageUriLarge,
                    'art_crop' => $card->imageUriArtCrop,
                ],
                'prices' => [
                    'eur' => $card->eurPrice,
                    'usd' => $card->usdPrice,
                ],
                'scryfall_uri' => $card->scryfallUri,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Card not found: ' . $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Get card by exact name
     * GET /api/cards/named?exact=Lightning Bolt&set=lea
     */
    #[Route('/named', name: 'named', methods: ['GET'])]
    public function named(Request $request): JsonResponse
    {
        $name = $request->query->get('exact', '');
        $set = $request->query->get('set');

        if (empty($name)) {
            return $this->json([
                'error' => 'Query parameter "exact" is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $card = $this->scryfallService->getCardByName($name, $set);
            
            return $this->json([
                'id' => $card->id,
                'name' => $card->name,
                'mana_cost' => $card->manaCost,
                'type_line' => $card->typeLine,
                'oracle_text' => $card->oracleText,
                'image' => $card->getBestImageUri(),
                'set' => $card->setCode,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Card not found: ' . $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Autocomplete card names
     * GET /api/cards/autocomplete?q=light
     */
    #[Route('/autocomplete', name: 'autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (empty($query)) {
            return $this->json([
                'suggestions' => []
            ]);
        }

        try {
            $suggestions = $this->scryfallService->autocomplete($query);
            
            return $this->json([
                'suggestions' => $suggestions
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Autocomplete failed: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get a random card
     * GET /api/cards/random?q=t:creature c:red (optional query)
     */
    #[Route('/random', name: 'random', methods: ['GET'])]
    public function random(Request $request): JsonResponse
    {
        $query = $request->query->get('q');

        try {
            $card = $this->scryfallService->getRandomCard($query);
            
            return $this->json([
                'id' => $card->id,
                'name' => $card->name,
                'mana_cost' => $card->manaCost,
                'type_line' => $card->typeLine,
                'oracle_text' => $card->oracleText,
                'image' => $card->getBestImageUri(),
                'set' => $card->setCode,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to get random card: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
