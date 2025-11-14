<?php

namespace App\Controller\Api;

use App\Entity\Card;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class CardApiController extends AbstractController
{
    /**
     * Sauvegarder une carte dans la BDD
     */
    #[Route('/card/save', name: 'api_card_save', methods: ['POST'])]
    public function saveCard(Request $request, CardRepository $cardRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['id'])) {
            return $this->json(['error' => 'Missing card ID'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Créer la carte à partir des données Scryfall
            $card = $cardRepository->findOrCreateFromScryfallData($data);
            
            return $this->json([
                'success' => true,
                'card' => $card->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer les détails d'une carte
     */
    #[Route('/card/{scryfallId}', name: 'api_card_get', methods: ['GET'])]
    public function getCard(string $scryfallId, CardRepository $cardRepository): JsonResponse
    {
        $card = $cardRepository->find($scryfallId);
        
        if (!$card) {
            return $this->json([
                'success' => false,
                'error' => 'Card not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json([
            'success' => true,
            'card' => $card->toArray()
        ]);
    }
}
