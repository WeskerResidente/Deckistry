# 🎴 Nouvelles Fonctionnalités - Gestion des Extensions, Foil et Langues

## ✨ Résumé des Améliorations

Votre application Deckistry a été enrichie avec trois nouvelles fonctionnalités majeures :

### 1. 📦 Gestion des Extensions (Sets)
- **Voir toutes les versions** : Cliquez sur le bouton "📦 Versions" sur n'importe quelle carte dans la recherche
- **Choisir l'extension** : Une modale s'ouvre affichant toutes les éditions disponibles de la carte
- **Informations détaillées** : Chaque version affiche :
  - L'image de la carte
  - Le nom de l'extension
  - Le code de l'extension (ex: `MH3`, `2XM`)
  - Le numéro de collection
  - La rareté
  - Le prix (si disponible)

### 2. ✨ Gestion du Foil
- **Collection** : Vous pouvez maintenant avoir la même carte en version normale ET foil
- **Indicateur visuel** : Les cartes foil affichent un badge doré "✨ FOIL" qui brille
- **Ajout avec option** : Dans la modale des versions, cochez "✨ Foil" avant d'ajouter la carte
- **Base de données** : Les cartes foil et normales sont stockées séparément

### 3. 🌍 Gestion des Langues
- **Filtre de langue** : Sélecteur de langue dans la barre de recherche
- **Langues disponibles** :
  - 🇬🇧 English
  - 🇪🇸 Español
  - 🇫🇷 Français
  - 🇩🇪 Deutsch
  - 🇮🇹 Italiano
  - 🇵🇹 Português
  - 🇯🇵 日本語
  - 🇰🇷 한국어
  - 🇷🇺 Русский
  - 🇨🇳 简体中文
  - 🇹🇼 繁體中文
  - 🌍 Toutes les langues
- **Stockage** : La langue de chaque carte est enregistrée en base de données

## 🗄️ Modifications de la Base de Données

### Nouvelles colonnes ajoutées :

**Table `cards`** :
- `lang VARCHAR(10)` - Code langue (ex: 'en', 'fr', 'ja')

**Table `deck_cards`** :
- `is_foil TINYINT(1)` - Indique si la carte est foil (0 = normal, 1 = foil)

**Table `collection_cards`** :
- `is_foil TINYINT(1)` - Indique si la carte est foil (0 = normal, 1 = foil)

### Migration exécutée :
```sql
ALTER TABLE cards ADD lang VARCHAR(10) DEFAULT 'en' NOT NULL;
ALTER TABLE collection_cards ADD is_foil TINYINT(1) DEFAULT 0 NOT NULL;
ALTER TABLE deck_cards ADD is_foil TINYINT(1) DEFAULT 0 NOT NULL;
```

## 🚀 Utilisation

### Rechercher une carte dans une langue spécifique :
1. Allez sur la page de recherche
2. Sélectionnez la langue dans le menu déroulant
3. Tapez le nom de la carte
4. Les résultats afficheront les cartes dans cette langue

### Voir toutes les versions d'une carte :
1. Recherchez une carte (ex: "Sol Ring")
2. Cliquez sur le bouton "📦 Versions"
3. Une modale s'ouvre avec toutes les éditions disponibles
4. Chaque version affiche son extension, son prix, etc.

### Ajouter une carte foil à votre collection :
1. Ouvrez la modale des versions
2. Cochez la case "✨ Foil" sous la version souhaitée
3. Cliquez sur "+ Collection"
4. La carte foil est ajoutée séparément de la version normale

### Exemple concret :
Si vous avez besoin de :
- 2x Sol Ring normal de l'extension Commander Legends
- 1x Sol Ring foil de l'extension Kaladesh Inventions
- 3x Sol Ring normal de l'extension Commander Masters

Vous pouvez maintenant avoir ces 3 entrées différentes dans votre collection !

## 🔧 Nouveaux Endpoints API

### 1. GET `/api/card-prints/{cardName}`
Récupère toutes les versions d'une carte.

**Paramètres** :
- `cardName` (path) : Nom de la carte
- `lang` (query) : Code langue (optionnel, défaut: 'en')

**Réponse** :
```json
{
  "success": true,
  "prints": [
    {
      "id": "uuid-scryfall",
      "name": "Sol Ring",
      "set": "cmm",
      "setName": "Commander Masters",
      "collectorNumber": "827",
      "rarity": "uncommon",
      "imageUri": "https://...",
      "imageUriSmall": "https://...",
      "lang": "en",
      "prices": { "eur": "1.50" },
      "foil": true,
      "nonfoil": true
    }
  ],
  "total": 50
}
```

### 2. GET `/api/card-prints/{cardName}/languages`
Récupère toutes les langues disponibles pour une carte.

**Réponse** :
```json
{
  "success": true,
  "languages": {
    "en": "English",
    "fr": "French",
    "ja": "Japanese"
  }
}
```

### 3. POST `/api/collection/add` (Mis à jour)
Ajoute une carte à la collection avec support du foil.

**Body** :
```json
{
  "scryfallId": "uuid-scryfall",
  "quantity": 1,
  "isFoil": true
}
```

### 4. GET `/api/collection` (Mis à jour)
Retourne maintenant les informations d'extension et foil :

**Réponse** :
```json
{
  "success": true,
  "cards": [
    {
      "scryfallId": "uuid",
      "name": "Sol Ring",
      "setCode": "cmm",
      "setName": "Commander Masters",
      "lang": "en",
      "quantity": 2,
      "isFoil": false
    }
  ]
}
```

## 📝 Notes Techniques

### Gestion des cartes identiques mais différentes :
- La clé primaire reste `scryfall_id` qui est unique par carte + extension
- Deux Sol Ring de sets différents ont des `scryfall_id` différents
- Le foil est géré au niveau de `collection_cards` et `deck_cards`, pas au niveau de `cards`

### Cache et Performance :
- Les recherches par langue sont mises en cache (1 heure)
- Les listes de versions sont mises en cache
- Respect du rate limiting de l'API Scryfall (100ms entre requêtes)

### Compatibilité :
- Les cartes existantes ont automatiquement `lang = 'en'` et `is_foil = false`
- Aucune migration manuelle nécessaire
- Backward compatible avec l'ancien système

## 🎯 Prochaines Étapes Possibles

1. **Deck Builder** : Ajouter la sélection d'extension et foil lors de l'ajout au deck
2. **Statistiques** : Afficher le nombre de cartes foil vs normales
3. **Filtres** : Filtrer la collection par extension ou foil
4. **Prix** : Afficher les prix des différentes versions
5. **Wishlist** : Marquer les versions spécifiques souhaitées

## 🐛 Débogage

En cas de problème :

1. **Vérifier la migration** :
```bash
php bin/console doctrine:migrations:status
```

2. **Vérifier les logs** :
```bash
tail -f var/log/dev.log
```

3. **Tester l'API** :
```bash
# Versions d'une carte
curl http://localhost:8000/api/card-prints/Sol%20Ring?lang=en

# Langues disponibles
curl http://localhost:8000/api/card-prints/Sol%20Ring/languages
```

4. **Console navigateur** :
- Ouvrir F12
- Vérifier les logs de `showCardPrints()`
- Vérifier les requêtes réseau

## ✅ Checklist de Vérification

- [x] Migration exécutée
- [x] Colonnes `lang`, `is_foil` ajoutées
- [x] API `/api/card-prints` fonctionnelle
- [x] Modale des versions affichée correctement
- [x] Badge foil affiché dans la collection
- [x] Sélecteur de langue dans la recherche
- [x] Les cartes existantes mises à jour avec les images
- [x] Code compilé (Webpack Encore)

---

**Version** : 1.0  
**Date** : 14 novembre 2025  
**Auteur** : GitHub Copilot
