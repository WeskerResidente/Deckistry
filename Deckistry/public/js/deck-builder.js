// Deck Builder JavaScript

(function() {
    'use strict';

    // État du deck
    let deckState = {
        commander: null,
        cards: new Map(), // Map<scryfallId, {card, quantity}>
        format: 'Commander'
    };

    // Charger le deck existant
    async function loadDeck() {
        if (typeof deckId === 'undefined') return;

        try {
            const response = await fetch(`/api/deck/${deckId}`);
            const data = await response.json();
            
            if (data.success) {
                deckState.format = data.deck.format;
                // Charger les cartes...
                updateDeckDisplay();
            }
        } catch (error) {
            console.error('Erreur chargement deck:', error);
        }
    }

    // Recherche de cartes
    let searchTimeout;
    const searchInput = document.getElementById('card-search-input');
    const searchResults = document.getElementById('search-results');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                searchResults.innerHTML = '';
                return;
            }

            searchTimeout = setTimeout(() => searchCards(query), 300);
        });
    }

    async function searchCards(query) {
        try {
            const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
            const data = await response.json();

            displaySearchResults(data.cards || []);
        } catch (error) {
            console.error('Erreur recherche:', error);
            searchResults.innerHTML = '<p style="padding: 20px; text-align: center; color: #999;">Erreur de recherche</p>';
        }
    }

    function displaySearchResults(cards) {
        if (!cards.length) {
            searchResults.innerHTML = '<p style="padding: 20px; text-align: center; color: #999;">Aucune carte trouvée</p>';
            return;
        }

        searchResults.innerHTML = cards.map(card => `
            <div class="search-result-card" data-card-id="${card.id}">
                <img src="${card.image_uris?.small || card.card_faces?.[0]?.image_uris?.small || ''}" 
                     alt="${card.name}"
                     onerror="this.style.display='none'">
                <div class="card-info">
                    <div class="card-name">${card.name}</div>
                    <div class="card-type">${card.type_line || ''}</div>
                </div>
                <button class="add-btn" onclick="addCardToDeck('${card.id}', ${JSON.stringify(card).replace(/'/g, "\\'")})">
                    Ajouter
                </button>
            </div>
        `).join('');
    }

    // Ajouter une carte au deck
    window.addCardToDeck = function(cardId, cardData) {
        const card = typeof cardData === 'string' ? JSON.parse(cardData) : cardData;

        // Vérifications pour Commander
        if (deckState.format === 'Commander') {
            // Vérifier si c'est un légendaire pour le commander
            const isLegendary = card.type_line && card.type_line.includes('Legendary');
            const isCreatureOrVehicle = card.type_line && 
                (card.type_line.includes('Creature') || card.type_line.includes('Vehicle'));

            // Si pas de commander et c'est un légendaire approprié
            if (!deckState.commander && isLegendary && isCreatureOrVehicle) {
                const confirmCommander = confirm(`Voulez-vous utiliser "${card.name}" comme commander ?`);
                if (confirmCommander) {
                    setCommander(card);
                    return;
                }
            }

            // Règle des cartes uniques (sauf terrains de base)
            const isBasicLand = card.type_line && 
                card.type_line.includes('Basic') && 
                card.type_line.includes('Land');

            if (deckState.cards.has(cardId) && !isBasicLand) {
                alert('En Commander, vous ne pouvez avoir qu\'un seul exemplaire de chaque carte (sauf terrains de base).');
                return;
            }
        }

        // Ajouter ou incrémenter
        if (deckState.cards.has(cardId)) {
            const cardEntry = deckState.cards.get(cardId);
            cardEntry.quantity++;
        } else {
            deckState.cards.set(cardId, {
                card: card,
                quantity: 1
            });
        }

        updateDeckDisplay();
        saveDeck();
    };

    // Définir le commander
    function setCommander(card) {
        deckState.commander = card;
        
        const commanderZone = document.getElementById('commander-zone');
        if (commanderZone) {
            commanderZone.innerHTML = `
                <div class="commander-card">
                    <img src="${card.image_uris?.normal || card.card_faces?.[0]?.image_uris?.normal || ''}" 
                         alt="${card.name}">
                    <div class="commander-info">
                        <div class="commander-name">${card.name}</div>
                        <div class="commander-type">${card.type_line || ''}</div>
                    </div>
                    <button class="remove-btn" onclick="removeCommander()">
                        Retirer
                    </button>
                </div>
            `;
        }

        saveDeck();
    }

    window.removeCommander = function() {
        deckState.commander = null;
        const commanderZone = document.getElementById('commander-zone');
        if (commanderZone) {
            commanderZone.innerHTML = `
                <div class="commander-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    <p>Ajoutez votre commander</p>
                    <small>Une créature ou un véhicule légendaire</small>
                </div>
            `;
        }
        saveDeck();
    };

    // Mettre à jour l'affichage du deck
    function updateDeckDisplay() {
        const deckList = document.getElementById('deck-list');
        const totalCardsEl = document.getElementById('total-cards');

        let totalCards = 0;
        deckState.cards.forEach(entry => {
            totalCards += entry.quantity;
        });

        if (totalCardsEl) {
            totalCardsEl.textContent = totalCards;
        }

        if (!deckList) return;

        if (deckState.cards.size === 0) {
            deckList.innerHTML = `
                <div class="empty-deck">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    <p>Votre deck est vide</p>
                    <small>Recherchez et ajoutez des cartes</small>
                </div>
            `;
            return;
        }

        const cardsArray = Array.from(deckState.cards.entries());
        deckList.innerHTML = cardsArray.map(([cardId, entry]) => {
            const card = entry.card;
            const isBasicLand = card.type_line && 
                card.type_line.includes('Basic') && 
                card.type_line.includes('Land');

            return `
                <div class="deck-card-item" data-card-id="${cardId}">
                    <img src="${card.image_uris?.small || card.card_faces?.[0]?.image_uris?.small || ''}" 
                         alt="${card.name}">
                    <div class="card-info">
                        <div class="card-name">${card.name}</div>
                        <div class="card-type">${card.type_line || ''}</div>
                    </div>
                    ${(deckState.format !== 'Commander' || isBasicLand) ? `
                        <div class="quantity-controls">
                            <button onclick="decrementCard('${cardId}')">−</button>
                            <span class="quantity">${entry.quantity}</span>
                            <button onclick="incrementCard('${cardId}')">+</button>
                        </div>
                    ` : `
                        <span class="quantity">×${entry.quantity}</span>
                    `}
                    <button class="remove-card-btn" onclick="removeCard('${cardId}')">
                        Retirer
                    </button>
                </div>
            `;
        }).join('');
    }

    // Modifier quantité
    window.incrementCard = function(cardId) {
        if (deckState.cards.has(cardId)) {
            deckState.cards.get(cardId).quantity++;
            updateDeckDisplay();
            saveDeck();
        }
    };

    window.decrementCard = function(cardId) {
        if (deckState.cards.has(cardId)) {
            const entry = deckState.cards.get(cardId);
            if (entry.quantity > 1) {
                entry.quantity--;
                updateDeckDisplay();
                saveDeck();
            }
        }
    };

    window.removeCard = function(cardId) {
        deckState.cards.delete(cardId);
        updateDeckDisplay();
        saveDeck();
    };

    // Sauvegarder le deck
    async function saveDeck() {
        if (typeof deckId === 'undefined') return;

        const deckData = {
            commander: deckState.commander,
            cards: Array.from(deckState.cards.entries()).map(([id, entry]) => ({
                scryfallId: id,
                quantity: entry.quantity
            }))
        };

        try {
            await fetch(`/api/deck/${deckId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(deckData)
            });
        } catch (error) {
            console.error('Erreur sauvegarde:', error);
        }
    }

    // Gestion des tabs
    const tabButtons = document.querySelectorAll('.tab-btn');
    const panels = document.querySelectorAll('.deck-panel');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.dataset.tab;

            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            panels.forEach(panel => panel.classList.remove('active'));
            const targetPanel = document.getElementById(`panel-${tabName}`);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });

    // Filtres
    window.clearFilters = function() {
        const filterBtns = document.querySelectorAll('.filter-btn');
        const colorBtns = document.querySelectorAll('.color-btn');
        
        filterBtns.forEach(btn => btn.classList.remove('active'));
        colorBtns.forEach(btn => btn.classList.remove('active'));
    };

    // Initialisation
    if (typeof deckId !== 'undefined') {
        loadDeck();
    }

})();
