# 🃏 Scryfall API Integration - Guide d'utilisation

## 📋 Vue d'ensemble

Le projet utilise l'API Scryfall pour récupérer les informations des cartes Magic: The Gathering à la volée, sans les stocker en base de données.

## 🏗️ Architecture

### Entités

- **`User`** : Utilisateurs de l'application
- **`Deck`** : Decks créés par les utilisateurs
- **`DeckCard`** : Association entre un deck et une carte Scryfall (avec quantité)
  - Stocke uniquement `scryfall_id` (pas de données de carte)
- **`CollectionCard`** : Cartes possédées par un utilisateur
  - Stocke uniquement `scryfall_id` et `quantity`
- **`Comment`** : Commentaires sur les decks
- **`Rating`** : Notes données aux decks (1-5)

### Service Scryfall

**`ScryfallService`** (`src/Service/ScryfallService.php`)
- Gère toutes les interactions avec l'API Scryfall
- Cache automatique des réponses (1 heure)
- Respect des limites de débit de l'API

**`CardDTO`** (`src/DTO/CardDTO.php`)
- Objet de transfert de données pour les cartes
- Contient toutes les informations utiles d'une carte

## 🚀 Utilisation du Service

### 1. Récupérer une carte par son ID

```php
use App\Service\ScryfallService;

class MyController extends AbstractController
{
    public function __construct(
        private readonly ScryfallService $scryfallService
    ) {}

    public function showCard(string $scryfallId): Response
    {
        try {
            $card = $this->scryfallService->getCardById($scryfallId);
            
            return $this->render('card/show.html.twig', [
                'card' => $card,
            ]);
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Card not found');
        }
    }
}
```

### 2. Rechercher des cartes

```php
// Recherche simple
$result = $this->scryfallService->searchCards('lightning bolt');

// Recherche avancée avec syntaxe Scryfall
$result = $this->scryfallService->searchCards('t:creature c:red pow>=4');

// Pagination
$result = $this->scryfallService->searchCards('elf', page: 2);

// Le résultat contient :
// - data: array de CardDTO
// - total_cards: nombre total de cartes trouvées
// - has_more: booléen indiquant s'il y a d'autres pages
```

### 3. Récupérer plusieurs cartes

```php
use App\Entity\Deck;

public function showDeckWithCards(Deck $deck): Response
{
    // Récupérer tous les IDs Scryfall du deck
    $scryfallIds = $deck->getDeckCards()
        ->map(fn($deckCard) => $deckCard->getScryfallId())
        ->toArray();

    // Récupérer les cartes depuis Scryfall
    $cards = $this->scryfallService->getCardsByIds($scryfallIds);

    return $this->render('deck/show.html.twig', [
        'deck' => $deck,
        'cards' => $cards,
    ]);
}
```

### 4. Autocomplétion

```php
// Pour un champ de recherche avec autocomplétion
$suggestions = $this->scryfallService->autocomplete('light');
// Retourne: ['Lightning Bolt', 'Light Up the Night', ...]
```

### 5. Carte aléatoire

```php
// Carte complètement aléatoire
$randomCard = $this->scryfallService->getRandomCard();

// Carte aléatoire avec contraintes
$randomCreature = $this->scryfallService->getRandomCard('t:creature');
```

### 6. Recherche par nom exact

```php
// Recherche par nom exact
$card = $this->scryfallService->getCardByName('Lightning Bolt');

// Recherche par nom avec édition spécifique
$card = $this->scryfallService->getCardByName('Lightning Bolt', 'lea');
```

## 🌐 Routes API disponibles

### Rechercher des cartes
```
GET /api/cards/search?q=lightning bolt&page=1
```

### Récupérer une carte par ID
```
GET /api/cards/{scryfall-id}
```

### Rechercher par nom exact
```
GET /api/cards/named?exact=Lightning Bolt&set=lea
```

### Autocomplétion
```
GET /api/cards/autocomplete?q=light
```

### Carte aléatoire
```
GET /api/cards/random
GET /api/cards/random?q=t:creature c:red
```

## 📝 Syntaxe de recherche Scryfall

Quelques exemples de recherche :

```
# Par nom
lightning bolt

# Par type
t:creature
t:instant
t:legendary t:artifact

# Par couleur
c:red          # Exactement rouge
c>=red         # Rouge ou multicolore contenant du rouge
c:wr           # Blanc ET rouge
c<=boros       # Maximum blanc et rouge

# Par puissance/endurance
pow>=4
tou<=2
pow=tou        # Cartes où puissance = endurance

# Par coût de mana
cmc=3          # Coût converti = 3
cmc<=2         # CMC 2 ou moins

# Par édition
set:war        # War of the Spark
set:dom OR set:m19

# Par rareté
r:mythic
r:common

# Combinaisons
t:creature c:green pow>=5 cmc<=6
```

[Documentation complète Scryfall](https://scryfall.com/docs/syntax)

## 🎨 Utilisation dans les templates Twig

```twig
{# Afficher une carte #}
<div class="card">
    <h3>{{ card.name }}</h3>
    
    {% if card.manaCost %}
        <span class="mana-cost">{{ card.manaCost }}</span>
    {% endif %}
    
    <p class="type">{{ card.typeLine }}</p>
    
    {% if card.oracleText %}
        <p class="oracle-text">{{ card.oracleText }}</p>
    {% endif %}
    
    {% if card.getStats() %}
        <p class="stats">{{ card.getStats() }}</p>
    {% endif %}
    
    {% if card.getBestImageUri() %}
        <img src="{{ card.getBestImageUri() }}" alt="{{ card.name }}">
    {% endif %}
    
    <p class="set">{{ card.setName }} ({{ card.setCode|upper }})</p>
    
    {% if card.eurPrice %}
        <p class="price">€{{ card.eurPrice }}</p>
    {% endif %}
</div>
```

## ⚡ Cache et Performance

- **Cache activé** : 1 heure par défaut
- **Rate limiting** : Respecte les limites de Scryfall (50-100ms entre requêtes)
- **Cache key** : Basé sur l'ID/query pour éviter les doublons

Pour vider le cache d'une carte spécifique :
```php
$this->scryfallService->clearCardCache($scryfallId);
```

## 🔧 Configuration

Le service utilise :
- `HttpClientInterface` : Pour les requêtes HTTP
- `CacheInterface` : Pour le cache (par défaut Symfony cache)
- `LoggerInterface` : Pour logger les erreurs

## 📊 Exemple complet : Afficher un deck

```php
use App\Entity\Deck;
use App\Service\ScryfallService;

#[Route('/deck/{id}', name: 'deck_show')]
public function show(
    Deck $deck,
    ScryfallService $scryfallService
): Response {
    // Récupérer toutes les cartes du deck
    $deckCardsData = [];
    
    foreach ($deck->getDeckCards() as $deckCard) {
        try {
            $card = $scryfallService->getCardById($deckCard->getScryfallId());
            $deckCardsData[] = [
                'card' => $card,
                'quantity' => $deckCard->getQuantity(),
            ];
        } catch (\Exception $e) {
            // Log l'erreur mais continue
            $this->logger->warning('Card not found', [
                'scryfall_id' => $deckCard->getScryfallId()
            ]);
        }
    }
    
    return $this->render('deck/show.html.twig', [
        'deck' => $deck,
        'cards' => $deckCardsData,
    ]);
}
```

## 🧪 Tester l'API

Une fois le serveur démarré :

```bash
# Démarrer le serveur Symfony
symfony server:start

# Tester les endpoints
curl "http://localhost:8000/api/cards/search?q=lightning"
curl "http://localhost:8000/api/cards/autocomplete?q=light"
curl "http://localhost:8000/api/cards/random"
```

## 📚 Ressources

- [Documentation Scryfall API](https://scryfall.com/docs/api)
- [Syntaxe de recherche Scryfall](https://scryfall.com/docs/syntax)
- [Carte de référence](https://scryfall.com/docs/api/cards)
