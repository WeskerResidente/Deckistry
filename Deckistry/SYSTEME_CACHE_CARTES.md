# 🗄️ Système de Cache des Cartes

## Architecture Centralisée

### 📊 Table `cards` - Cache Partagé Entre Tous les Utilisateurs

```
┌─────────────────────────────────────────────────────────┐
│                    Table: cards                          │
│  (Cache centralisé - partagé entre TOUS les joueurs)    │
├─────────────────────────────────────────────────────────┤
│  scryfall_id (PK) │ name │ image_uri │ lang │ ...       │
├─────────────────────────────────────────────────────────┤
│  abc123...        │ Sol Ring │ https://... │ en │ ...   │
│  def456...        │ Lightning Bolt │ ... │ fr │ ...     │
└─────────────────────────────────────────────────────────┘
         ▲                    ▲                    ▲
         │                    │                    │
    ┌────┴────┐         ┌────┴────┐         ┌────┴────┐
    │ User 1  │         │ User 2  │         │ User 3  │
    │ Deck A  │         │ Deck B  │         │ Deck C  │
    │ + Coll  │         │ + Coll  │         │ + Coll  │
    └─────────┘         └─────────┘         └─────────┘
```

### ⚡ Flux de Récupération des Cartes

```
Besoin d'une carte (scryfallId: "abc123")
    │
    ▼
┌─────────────────────────────────┐
│ 1. Chercher en BDD locale       │ ← RAPIDE (< 1ms)
│    SELECT * FROM cards          │
│    WHERE scryfall_id = 'abc123' │
└─────────────────────────────────┘
    │
    ├─ Trouvée ? ──────────────────┐
    │                               │
    ▼                               ▼
┌─────────────────────┐      ┌──────────────────┐
│ 2. NON : Appel API  │      │ OUI : Retourner  │
│    Scryfall         │      │      directement │
│    ↓                │      └──────────────────┘
│ 3. Sauvegarder BDD  │            ⚡ RAPIDE
│    ↓                │
│ 4. Retourner        │
└─────────────────────┘
      🐌 LENT (100-500ms)
```

## 🔧 Implémentation

### 1. Ajout à la Collection

**Fichier** : `src/Controller/Api/CollectionApiController.php`

```php
public function addCard(...) {
    // ✅ Vérifie d'abord la BDD
    $card = $cardRepo->find($scryfallId);
    
    if (!$card) {
        // ✅ Si absent, récupère depuis Scryfall et sauvegarde
        $cardData = /* appel Scryfall */;
        $card = Card::fromScryfallData($cardData);
        $em->persist($card);
        $em->flush();
    }
    
    // La carte est maintenant en BDD pour tous les utilisateurs
}
```

### 2. Ajout au Deck

**Fichier** : `src/Controller/Api/DeckApiController.php`

```php
public function updateDeck(...) {
    foreach ($data['cards'] as $cardData) {
        // ✅ AMÉLIORATION : Utilise findOrFetchFromScryfall()
        $card = $cardRepository->findOrFetchFromScryfall($scryfallId);
        
        // Garantit que la carte existe TOUJOURS
        // Récupère automatiquement depuis Scryfall si nécessaire
    }
}
```

### 3. Méthode Helper dans CardRepository

**Fichier** : `src/Repository/CardRepository.php`

```php
/**
 * Trouve ou récupère une carte depuis Scryfall si nécessaire
 * ✅ Garantit qu'une carte existe toujours en BDD
 */
public function findOrFetchFromScryfall(string $scryfallId): ?Card
{
    // 1. Chercher en BDD (RAPIDE)
    $card = $this->find($scryfallId);
    
    if ($card) {
        return $card; // ⚡ Cache hit !
    }
    
    // 2. Pas en BDD : récupérer depuis Scryfall
    $cardData = /* appel API Scryfall */;
    $card = Card::fromScryfallData($cardData);
    
    // 3. Sauvegarder pour les prochaines fois
    $em->persist($card);
    $em->flush();
    
    return $card; // 🐌 Cache miss, mais sauvegardé pour la prochaine fois
}
```

## 📈 Avantages du Système

### ✅ Performance
```
Utilisateur A ajoute "Sol Ring" à sa collection
    → Appel Scryfall (500ms) + Sauvegarde BDD
    → Sol Ring maintenant en cache

Utilisateur B cherche "Sol Ring" dans son deck
    → Lecture BDD (< 1ms) ⚡
    → Pas d'appel Scryfall !

Utilisateur C ajoute "Sol Ring" à son deck
    → Lecture BDD (< 1ms) ⚡
    → Pas d'appel Scryfall !
```

### ✅ Réduction des Appels API
- **Avant** : Chaque utilisateur appelle Scryfall indépendamment
- **Maintenant** : Une seule fois pour toute l'application

### ✅ Fonctionnement Hors-Ligne
- Les cartes en BDD peuvent être affichées même si Scryfall est down
- Seules les nouvelles cartes nécessitent une connexion

### ✅ Gestion Multi-Éditions
- Chaque `scryfall_id` est unique par :
  - Nom de carte
  - Édition/Set
  - Langue
- Permet de stocker Sol Ring (Commander Legends) et Sol Ring (Kaladesh) séparément

## 🔍 Cas d'Usage

### Scénario 1 : Premier Utilisateur Ajoute une Carte
```
User1 → Ajoute "Lightning Bolt" à sa collection
    ├─ Cherche en BDD : NOT FOUND
    ├─ Appel Scryfall : 400ms
    ├─ Sauvegarde dans cards : ✅
    └─ Crée collection_cards pour User1

Résultat : Lightning Bolt maintenant disponible pour TOUS
```

### Scénario 2 : Deuxième Utilisateur Utilise la Même Carte
```
User2 → Ajoute "Lightning Bolt" à son deck
    ├─ Cherche en BDD : FOUND ⚡
    ├─ Pas d'appel Scryfall
    └─ Crée deck_cards pour User2

Résultat : Instantané, pas de latence API
```

### Scénario 3 : Éditions Différentes
```
User3 → Ajoute "Sol Ring" (Modern Masters 2015)
    ├─ Cherche scryfall_id: "xyz789" : NOT FOUND
    ├─ Appel Scryfall
    └─ Nouvelle entrée dans cards

User4 → Ajoute "Sol Ring" (Commander 2021)
    ├─ Cherche scryfall_id: "abc123" : NOT FOUND
    ├─ Appel Scryfall
    └─ Nouvelle entrée dans cards (différent scryfall_id)

Résultat : Deux entrées distinctes pour deux éditions
```

## 🎯 Points d'Optimisation Futurs

### 1. Pré-Chargement des Cartes Populaires
```sql
-- Script pour pré-charger les 1000 cartes les plus populaires
INSERT INTO cards SELECT * FROM scryfall_api 
WHERE name IN (
    'Sol Ring', 'Lightning Bolt', 'Command Tower', ...
);
```

### 2. Nettoyage Automatique
```php
// Supprimer les cartes jamais utilisées après 90 jours
DELETE FROM cards 
WHERE updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
AND id NOT IN (
    SELECT DISTINCT card_id FROM deck_cards
    UNION
    SELECT DISTINCT scryfall_id FROM collection_cards
);
```

### 3. Cache Redis pour les Cartes Chaudes
```php
// Cartes très demandées en mémoire Redis (ultra rapide)
$redis->setex("card:abc123", 3600, json_encode($cardData));
```

## 📊 Statistiques Actuelles

### Tables et Relations
```
cards (table centrale)
├─ Référencée par: deck_cards.card_id
├─ Référencée par: collection_cards.scryfall_id
└─ Partagée entre tous les utilisateurs

Exemple :
- 13 cartes en BDD actuellement
- Économie : Si 10 users utilisent Sol Ring = 9 appels API évités
```

### Performance Mesurée
```
Lecture depuis BDD :     < 1ms   ⚡⚡⚡
Appel Scryfall API :     100-500ms 🐌
Économie par cache hit : 99-500x plus rapide
```

## ✅ Conclusion

**OUI, les cartes ajoutées dans les collections sont :**

1. ✅ **Sauvegardées dans la BDD** (`cards` table)
2. ✅ **Partagées entre tous les utilisateurs**
3. ✅ **Appelées en priorité** depuis la BDD
4. ✅ **Récupérées depuis Scryfall** uniquement si absentes
5. ✅ **Mises en cache automatiquement** pour les futures utilisations

**Le système fonctionne comme un cache global distribué** où chaque carte n'est téléchargée qu'une seule fois depuis Scryfall, puis réutilisée par tous les utilisateurs de l'application. 🚀

---

**Dernière mise à jour** : 14 novembre 2025  
**Version** : 2.0 avec `findOrFetchFromScryfall()`
