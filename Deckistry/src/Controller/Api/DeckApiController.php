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
    public function getDeck(Deck $deck, CardRepository $cardRepository): JsonResponse
    {
        // Vérifier l'accès pour les decks privés seulement
        if ($deck->isPrivate()) {
            if (!$this->getUser() || $deck->getUser() !== $this->getUser()) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
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

        // Récupérer les détails du commander si défini
        $commanderData = null;
        if ($deck->getCommanderId()) {
            $commanderCard = $cardRepository->findOneBy(['scryfallId' => $deck->getCommanderId()]);
            if ($commanderCard) {
                $commanderData = [
                    'id' => $commanderCard->getScryfallId(),
                    'scryfallId' => $commanderCard->getScryfallId(),
                    'name' => $commanderCard->getName(),
                    'typeLine' => $commanderCard->getTypeLine(),
                    'manaCost' => $commanderCard->getManaCost(),
                    'oracleText' => $commanderCard->getOracleText(),
                    'imageUri' => $commanderCard->getImageUri(),
                    'imageUriSmall' => $commanderCard->getImageUriSmall(),
                    'colors' => $commanderCard->getColors(),
                    'colorIdentity' => $commanderCard->getColorIdentity(),
                    'cmc' => $commanderCard->getCmc(),
                    'rarity' => $commanderCard->getRarity(),
                    'setCode' => $commanderCard->getSetCode(),
                    'setName' => $commanderCard->getSetName(),
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
                'commander' => $commanderData,
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

        error_log("=== DÉBUT SAUVEGARDE DECK {$deck->getId()} ===");
        error_log("Commander reçu: " . json_encode($data['commander'] ?? null));
        error_log("Nombre de cartes reçues: " . count($data['cards'] ?? []));

        if (!isset($data['cards']) || !is_array($data['cards'])) {
            error_log("Erreur: données invalides");
            return $this->json(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        // Mettre à jour le commander
        if (isset($data['commander']) && is_array($data['commander'])) {
            $commanderId = $data['commander']['id'] ?? null;
            error_log("Mise à jour commander ID: $commanderId");
            $deck->setCommanderId($commanderId);
        } else {
            error_log("Suppression du commander");
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

            // Récupérer la carte (depuis BDD ou Scryfall si nécessaire)
            $card = $cardRepository->findOrFetchFromScryfall($cardData['scryfallId']);
            
            if (!$card) {
                error_log("Impossible de récupérer la carte: " . $cardData['scryfallId']);
                continue;
            }

            $deckCard = new DeckCard();
            $deckCard->setDeck($deck);
            $deckCard->setCard($card);
            $deckCard->setQuantity($cardData['quantity']);
            $deckCard->setIsFoil($cardData['isFoil'] ?? false);

            $em->persist($deckCard);
        }

        $deck->setUpdatedAt(new \DateTime());
        $em->flush();

        error_log("✅ Deck sauvegardé avec succès. Commander: " . ($deck->getCommanderId() ?? 'aucun') . ", Cartes: " . count($deck->getDeckCards()));
        error_log("=== FIN SAUVEGARDE DECK {$deck->getId()} ===");

        return $this->json([
            'success' => true,
            'message' => 'Deck updated successfully',
            'commander' => $deck->getCommanderId(),
            'cardCount' => count($deck->getDeckCards())
        ]);
    }

    #[Route('/deck/{id}/toggle-privacy', name: 'api_deck_toggle_privacy', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function togglePrivacy(
        Deck $deck,
        EntityManagerInterface $em
    ): JsonResponse {
        // Vérifier le propriétaire
        if ($deck->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $deck->setIsPrivate(!$deck->isPrivate());
        $em->flush();

        return $this->json([
            'success' => true,
            'isPrivate' => $deck->isPrivate()
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
