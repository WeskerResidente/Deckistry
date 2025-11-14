<?php

namespace App\Controller\Api;

use App\Entity\Deck;
use App\Entity\DeckCard;
use App\Repository\CardRepository;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class DeckApiController extends AbstractController
{
    #[Route('/deck/{id}', name: 'api_deck_get', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getDeck(Deck $deck): JsonResponse
    {
        // Vérifier l'accès
        if ($deck->isPrivate() && $deck->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $cardsData = [];
        foreach ($deck->getDeckCards() as $deckCard) {
            $card = $deckCard->getCard();
            if ($card) {
                $cardsData[] = [
                    'id' => $card->getScryfallId(),
                    'scryfallId' => $card->getScryfallId(),
                    'name' => $card->getName(),
                    'typeLine' => $card->getTypeLine(),
                    'manaCost' => $card->getManaCost(),
                    'oracleText' => $card->getOracleText(),
                    'imageUri' => $card->getImageUri(),
                    'imageUriSmall' => $card->getImageUriSmall(),
                    'colors' => $card->getColors(),
                    'colorIdentity' => $card->getColorIdentity(),
                    'cmc' => $card->getCmc(),
                    'rarity' => $card->getRarity(),
                    'setCode' => $card->getSetCode(),
                    'setName' => $card->getSetName(),
                    'quantity' => $deckCard->getQuantity(),
                ];
            }
        }

        return $this->json([
            'success' => true,
            'deck' => [
                'id' => $deck->getId(),
                'name' => $deck->getName(),
                'format' => $deck->getFormat(),
                'isPrivate' => $deck->isPrivate(),
                'commanderId' => $deck->getCommanderId(),
                'cards' => $cardsData,
            ]
        ]);
    }

    #[Route('/deck/{id}', name: 'api_deck_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateDeck(
        Deck $deck,
        Request $request,
        EntityManagerInterface $em,
        CardRepository $cardRepository
    ): JsonResponse {
        // Vérifier le propriétaire
        if ($deck->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['cards']) || !is_array($data['cards'])) {
            return $this->json(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        // Mettre à jour le commander
        if (isset($data['commander']) && is_array($data['commander'])) {
            $deck->setCommanderId($data['commander']['id'] ?? null);
        } else {
            $deck->setCommanderId(null);
        }

        // Supprimer toutes les cartes existantes
        foreach ($deck->getDeckCards() as $deckCard) {
            $em->remove($deckCard);
        }
        $em->flush();

        // Ajouter les nouvelles cartes
        foreach ($data['cards'] as $cardData) {
            if (!isset($cardData['scryfallId']) || !isset($cardData['quantity'])) {
                continue;
            }

            // Récupérer la carte dans la base de données
            $card = $cardRepository->find($cardData['scryfallId']);
            
            if (!$card) {
                // La carte n'existe pas en BDD - elle devrait avoir été créée lors de l'ajout
                error_log("Carte non trouvée en BDD: " . $cardData['scryfallId']);
                continue;
            }

            $deckCard = new DeckCard();
            $deckCard->setDeck($deck);
            $deckCard->setCard($card);
            $deckCard->setQuantity($cardData['quantity']);

            $em->persist($deckCard);
        }

        $deck->setUpdatedAt(new \DateTime());
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Deck updated successfully'
        ]);
    }

    #[Route('/deck/{id}', name: 'api_deck_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteDeck(
        Deck $deck,
        EntityManagerInterface $em
    ): JsonResponse {
        // Vérifier le propriétaire
        if ($deck->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($deck);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Deck deleted successfully'
        ]);
    }
}
