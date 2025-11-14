<?php

namespace App\Controller\Api;

use App\Entity\CollectionCard;
use App\Repository\CardRepository;
use App\Repository\CollectionCardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/collection')]
#[IsGranted('ROLE_USER')]
class CollectionApiController extends AbstractController
{
    #[Route('', name: 'api_collection_get', methods: ['GET'])]
    public function getCollection(
        CollectionCardRepository $collectionRepo,
        CardRepository $cardRepo
    ): JsonResponse {
        $user = $this->getUser();
        $collectionCards = $collectionRepo->findBy(['user' => $user]);

        $cards = [];
        foreach ($collectionCards as $collectionCard) {
            $card = $cardRepo->find($collectionCard->getScryfallId());
            
            if ($card) {
                // Récupérer le prix depuis Scryfall
                $price = null;
                try {
                    $scryfallUrl = "https://api.scryfall.com/cards/{$card->getScryfallId()}";
                    $context = stream_context_create([
                        'http' => [
                            'timeout' => 5,
                            'header' => "User-Agent: Deckistry/1.0\r\n"
                        ]
                    ]);
                    $response = @file_get_contents($scryfallUrl, false, $context);
                    
                    if ($response !== false) {
                        $cardData = json_decode($response, true);
                        $isFoil = $collectionCard->isFoil();
                        
                        if (isset($cardData['prices'])) {
                            if ($isFoil && isset($cardData['prices']['eur_foil'])) {
                                $price = $cardData['prices']['eur_foil'];
                            } elseif (!$isFoil && isset($cardData['prices']['eur'])) {
                                $price = $cardData['prices']['eur'];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Prix non disponible
                }
                
                $cards[] = [
                    'scryfallId' => $card->getScryfallId(),
                    'name' => $card->getName(),
                    'typeLine' => $card->getTypeLine(),
                    'manaCost' => $card->getManaCost(),
                    'imageUri' => $card->getImageUri(),
                    'imageUriSmall' => $card->getImageUriSmall(),
                    'cmc' => $card->getCmc(),
                    'colors' => $card->getColors(),
                    'rarity' => $card->getRarity(),
                    'setCode' => $card->getSetCode(),
                    'setName' => $card->getSetName(),
                    'lang' => $card->getLang(),
                    'quantity' => $collectionCard->getQuantity(),
                    'isFoil' => $collectionCard->isFoil(),
                    'price' => $price,
                ];
            }
        }

        return new JsonResponse([
            'success' => true,
            'cards' => $cards
        ]);
    }

    #[Route('/add', name: 'api_collection_add', methods: ['POST'])]
    public function addCard(
        Request $request,
        EntityManagerInterface $em,
        CollectionCardRepository $collectionRepo,
        CardRepository $cardRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $scryfallId = $data['scryfallId'] ?? null;
        $quantity = $data['quantity'] ?? 1;
        $isFoil = $data['isFoil'] ?? false;

        if (!$scryfallId) {
            return new JsonResponse(['success' => false, 'error' => 'Missing scryfallId'], 400);
        }

        $user = $this->getUser();

        // Vérifier si la carte existe dans la table cards, sinon la récupérer de Scryfall
        $card = $cardRepo->find($scryfallId);
        
        if (!$card) {
            // Récupérer la carte depuis Scryfall
            try {
                $scryfallUrl = "https://api.scryfall.com/cards/{$scryfallId}";
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 10,
                        'header' => "User-Agent: Deckistry/1.0\r\n"
                    ]
                ]);
                $response = @file_get_contents($scryfallUrl, false, $context);
                
                if ($response === false) {
                    return new JsonResponse([
                        'success' => false, 
                        'error' => 'Could not fetch card from Scryfall API'
                    ], 500);
                }
                
                $cardData = json_decode($response, true);
                
                if (!$cardData || !isset($cardData['id'])) {
                    return new JsonResponse([
                        'success' => false, 
                        'error' => 'Invalid card data from Scryfall'
                    ], 500);
                }
                
                // Créer la carte dans la base de données
                $card = \App\Entity\Card::fromScryfallData($cardData);
                $em->persist($card);
                $em->flush();
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false, 'error' => 'Error: ' . $e->getMessage()
                ], 500);
            }
        }

        // Vérifier si la carte existe déjà dans la collection (même foil status)
        $existingCard = $collectionRepo->findOneBy([
            'user' => $user,
            'scryfallId' => $scryfallId,
            'isFoil' => $isFoil
        ]);

        if ($existingCard) {
            // Augmenter la quantité
            $existingCard->setQuantity($existingCard->getQuantity() + $quantity);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Card quantity updated',
                'quantity' => $existingCard->getQuantity()
            ]);
        }

        // Créer une nouvelle entrée
        $collectionCard = new CollectionCard();
        $collectionCard->setUser($user);
        $collectionCard->setScryfallId($scryfallId);
        $collectionCard->setIsFoil($isFoil);
        $collectionCard->setQuantity($quantity);

        $em->persist($collectionCard);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Card added to collection'
        ]);
    }

    #[Route('/update', name: 'api_collection_update', methods: ['POST'])]
    public function updateQuantity(
        Request $request,
        EntityManagerInterface $em,
        CollectionCardRepository $collectionRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $scryfallId = $data['scryfallId'] ?? null;
        $quantity = $data['quantity'] ?? null;

        if (!$scryfallId || $quantity === null || $quantity < 1) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid data'], 400);
        }

        $user = $this->getUser();
        $collectionCard = $collectionRepo->findOneBy([
            'user' => $user,
            'scryfallId' => $scryfallId
        ]);

        if (!$collectionCard) {
            return new JsonResponse(['success' => false, 'error' => 'Card not found'], 404);
        }

        $collectionCard->setQuantity($quantity);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'quantity' => $quantity
        ]);
    }

    #[Route('/remove', name: 'api_collection_remove', methods: ['POST'])]
    public function removeCard(
        Request $request,
        EntityManagerInterface $em,
        CollectionCardRepository $collectionRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $scryfallId = $data['scryfallId'] ?? null;

        if (!$scryfallId) {
            return new JsonResponse(['success' => false, 'error' => 'Missing scryfallId'], 400);
        }

        $user = $this->getUser();
        $collectionCard = $collectionRepo->findOneBy([
            'user' => $user,
            'scryfallId' => $scryfallId
        ]);

        if (!$collectionCard) {
            return new JsonResponse(['success' => false, 'error' => 'Card not found'], 404);
        }

        $em->remove($collectionCard);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Card removed from collection'
        ]);
    }
}
