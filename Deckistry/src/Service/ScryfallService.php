<?php

namespace App\Service;

use App\DTO\CardDTO;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service to interact with Scryfall API
 * Documentation: https://scryfall.com/docs/api
 */
class ScryfallService
{
    private const BASE_URL = 'https://api.scryfall.com';
    private const CACHE_TTL = 3600; // 1 hour cache
    
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get a single card by its Scryfall ID
     * 
     * @throws \Exception if card not found or API error
     */
    public function getCardById(string $scryfallId): CardDTO
    {
        $cacheKey = "scryfall_card_{$scryfallId}";
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($scryfallId) {
            $item->expiresAfter(self::CACHE_TTL);
            
            try {
                $response = $this->httpClient->request(
                    'GET',
                    self::BASE_URL . "/cards/{$scryfallId}"
                );

                if ($response->getStatusCode() !== 200) {
                    throw new \Exception("Card not found: {$scryfallId}");
                }

                $data = $response->toArray();
                return CardDTO::fromScryfallData($data);
                
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Scryfall API transport error', [
                    'scryfallId' => $scryfallId,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to fetch card from Scryfall API', 0, $e);
            }
        });
    }

    /**
     * Get multiple cards by their Scryfall IDs
     * 
     * @param string[] $scryfallIds
     * @return CardDTO[]
     */
    public function getCardsByIds(array $scryfallIds): array
    {
        $cards = [];
        foreach ($scryfallIds as $id) {
            try {
                $cards[$id] = $this->getCardById($id);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to fetch card', [
                    'scryfallId' => $id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        return $cards;
    }

    /**
     * Search for cards using Scryfall's search syntax
     * 
     * @param string $query Scryfall search query (e.g., "lightning bolt", "t:creature c:red")
     * @param string $lang Language code (e.g., "en", "fr", "ja")
     * @param int $page Page number (starts at 1)
     * @return array{data: CardDTO[], total_cards: int, has_more: bool}
     */
    public function searchCards(string $query, string $lang = 'en', int $page = 1): array
    {
        $cacheKey = "scryfall_search_" . md5($query . "_{$lang}_page_{$page}");
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $lang, $page) {
            $item->expiresAfter(self::CACHE_TTL);
            
            try {
                // Ajouter le filtre de langue à la requête
                $searchQuery = $query;
                if ($lang !== 'any') {
                    $searchQuery .= " lang:{$lang}";
                }
                
                $response = $this->httpClient->request('GET', self::BASE_URL . '/cards/search', [
                    'query' => [
                        'q' => $searchQuery,
                        'page' => $page,
                        'format' => 'json',
                    ]
                ]);

                if ($response->getStatusCode() === 404) {
                    // No cards found
                    return [
                        'data' => [],
                        'total_cards' => 0,
                        'has_more' => false,
                    ];
                }

                $data = $response->toArray();
                
                $cards = array_map(
                    fn($cardData) => CardDTO::fromScryfallData($cardData),
                    $data['data'] ?? []
                );

                return [
                    'data' => $cards,
                    'total_cards' => $data['total_cards'] ?? 0,
                    'has_more' => $data['has_more'] ?? false,
                ];
                
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Scryfall API search error', [
                    'query' => $query,
                    'lang' => $lang,
                    'page' => $page,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to search cards on Scryfall API', 0, $e);
            }
        });
    }

    /**
     * Get autocomplete suggestions for card names
     * 
     * @return string[] Array of card names
     */
    public function autocomplete(string $query): array
    {
        $cacheKey = "scryfall_autocomplete_" . md5($query);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query) {
            $item->expiresAfter(self::CACHE_TTL);
            
            try {
                $response = $this->httpClient->request('GET', self::BASE_URL . '/cards/autocomplete', [
                    'query' => ['q' => $query]
                ]);

                $data = $response->toArray();
                return $data['data'] ?? [];
                
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Scryfall API autocomplete error', [
                    'query' => $query,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Get a random card
     */
    public function getRandomCard(?string $query = null): CardDTO
    {
        try {
            $params = $query ? ['q' => $query] : [];
            
            $response = $this->httpClient->request('GET', self::BASE_URL . '/cards/random', [
                'query' => $params
            ]);

            $data = $response->toArray();
            return CardDTO::fromScryfallData($data);
            
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Scryfall API random card error', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to fetch random card from Scryfall API', 0, $e);
        }
    }

    /**
     * Get card by exact name
     */
    public function getCardByName(string $name, ?string $set = null): CardDTO
    {
        $cacheKey = "scryfall_named_" . md5($name . ($set ?? ''));
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($name, $set) {
            $item->expiresAfter(self::CACHE_TTL);
            
            try {
                $params = ['exact' => $name];
                if ($set) {
                    $params['set'] = $set;
                }
                
                $response = $this->httpClient->request('GET', self::BASE_URL . '/cards/named', [
                    'query' => $params
                ]);

                if ($response->getStatusCode() !== 200) {
                    throw new \Exception("Card not found: {$name}");
                }

                $data = $response->toArray();
                return CardDTO::fromScryfallData($data);
                
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Scryfall API named card error', [
                    'name' => $name,
                    'set' => $set,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to fetch card by name from Scryfall API', 0, $e);
            }
        });
    }

    /**
     * Get all MTG sets/editions
     * 
     * @return array Array of sets with 'code', 'name', 'released_at'
     */
    public function getSets(): array
    {
        $cacheKey = "scryfall_sets";
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(86400); // Cache for 24 hours
            
            try {
                $response = $this->httpClient->request('GET', self::BASE_URL . '/sets');

                $data = $response->toArray();
                
                // Filter and map sets to only include relevant info
                $sets = array_map(function($set) {
                    return [
                        'code' => $set['code'],
                        'name' => $set['name'],
                        'released_at' => $set['released_at'] ?? null,
                        'set_type' => $set['set_type'] ?? null,
                    ];
                }, $data['data'] ?? []);
                
                // Sort by name (alphabetical order)
                usort($sets, function($a, $b) {
                    return strcasecmp($a['name'], $b['name']);
                });
                
                return $sets;
                
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Scryfall API sets error', [
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Clear cache for a specific card ID
     */
    public function clearCardCache(string $scryfallId): void
    {
        $cacheKey = "scryfall_card_{$scryfallId}";
        $this->cache->delete($cacheKey);
    }

    /**
     * Respect Scryfall's rate limiting (50-100ms between requests)
     * This is handled automatically by caching, but you can add explicit delays if needed
     */
    private function respectRateLimit(): void
    {
        usleep(100000); // 100ms delay
    }
}
