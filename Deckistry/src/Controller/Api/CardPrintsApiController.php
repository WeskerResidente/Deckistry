<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/card-prints')]
class CardPrintsApiController extends AbstractController
{
    /**
     * Récupère toutes les versions (prints) d'une carte depuis Scryfall
     */
    #[Route('/{cardName}', name: 'api_card_prints', methods: ['GET'])]
    public function getCardPrints(string $cardName, Request $request): JsonResponse
    {
        $lang = $request->query->get('lang', 'en');
        
        try {
            // Rechercher la carte par nom exact
            $searchUrl = 'https://api.scryfall.com/cards/search?q=' . urlencode('!"' . $cardName . '" lang:' . $lang) . '&unique=prints&order=released&dir=desc';
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'header' => "User-Agent: Deckistry/1.0\r\n"
                ]
            ]);
            
            $response = @file_get_contents($searchUrl, false, $context);
            
            if ($response === false) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Could not fetch card prints from Scryfall'
                ], 500);
            }
            
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['data'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'No prints found for this card'
                ], 404);
            }
            
            // Formater les données pour le frontend
            $prints = array_map(function($card) {
                $imageUri = null;
                $imageUriSmall = null;
                
                if (isset($card['image_uris'])) {
                    $imageUri = $card['image_uris']['normal'] ?? $card['image_uris']['large'] ?? null;
                    $imageUriSmall = $card['image_uris']['small'] ?? $card['image_uris']['normal'] ?? null;
                } elseif (isset($card['card_faces'][0]['image_uris'])) {
                    $imageUri = $card['card_faces'][0]['image_uris']['normal'] ?? null;
                    $imageUriSmall = $card['card_faces'][0]['image_uris']['small'] ?? null;
                }
                
                return [
                    'id' => $card['id'],
                    'name' => $card['name'],
                    'set' => $card['set'],
                    'setName' => $card['set_name'],
                    'collectorNumber' => $card['collector_number'],
                    'rarity' => $card['rarity'],
                    'imageUri' => $imageUri,
                    'imageUriSmall' => $imageUriSmall,
                    'lang' => $card['lang'],
                    'prices' => $card['prices'] ?? [],
                    'releasedAt' => $card['released_at'],
                    'foil' => $card['foil'] ?? false,
                    'nonfoil' => $card['nonfoil'] ?? false,
                ];
            }, $data['data']);
            
            return new JsonResponse([
                'success' => true,
                'prints' => $prints,
                'total' => count($prints)
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les langues disponibles pour une carte
     */
    #[Route('/{cardName}/languages', name: 'api_card_languages', methods: ['GET'])]
    public function getCardLanguages(string $cardName): JsonResponse
    {
        try {
            // Rechercher toutes les langues disponibles
            $searchUrl = 'https://api.scryfall.com/cards/search?q=' . urlencode('!"' . $cardName . '"') . '&unique=prints';
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'header' => "User-Agent: Deckistry/1.0\r\n"
                ]
            ]);
            
            $response = @file_get_contents($searchUrl, false, $context);
            
            if ($response === false) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Could not fetch card languages'
                ], 500);
            }
            
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['data'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'No data found'
                ], 404);
            }
            
            // Extraire les langues uniques
            $languages = [];
            foreach ($data['data'] as $card) {
                $lang = $card['lang'] ?? 'en';
                if (!isset($languages[$lang])) {
                    $languages[$lang] = $this->getLanguageName($lang);
                }
            }
            
            return new JsonResponse([
                'success' => true,
                'languages' => $languages
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getLanguageName(string $code): string
    {
        $languages = [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'ru' => 'Russian',
            'zhs' => 'Simplified Chinese',
            'zht' => 'Traditional Chinese',
            'he' => 'Hebrew',
            'la' => 'Latin',
            'grc' => 'Ancient Greek',
            'ar' => 'Arabic',
            'sa' => 'Sanskrit',
            'ph' => 'Phyrexian',
        ];
        
        return $languages[$code] ?? $code;
    }
}
