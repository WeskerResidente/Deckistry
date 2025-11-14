// Deck Builder JavaScript

(function() {
    'use strict';

    // Vérifier si on est en mode visualisation
    const isViewMode = typeof DECK_VIEW_MODE !== 'undefined' && DECK_VIEW_MODE === true;
    const deckId = typeof DECK_ID !== 'undefined' ? DECK_ID : (typeof window.deckId !== 'undefined' ? window.deckId : undefined);

    // État du deck
    let deckState = {
        commander: null,
        cards: new Map(), // Map<scryfallId, {card, quantity}>
        format: 'Commander'
    };

    // Map temporaire pour les cartes de recherche (évite JSON.stringify dans HTML)
    const searchCardsCache = new Map();

    // Charger le deck existant
    async function loadDeck() {
        if (typeof deckId === 'undefined') return;

        try {
            const response = await fetch(`/api/deck/${deckId}`);
            const data = await response.json();
            
            if (data.success) {
                deckState.format = data.deck.format;
                
                // Charger le commander
                if (data.deck.commanderId) {
                    console.log('Chargement du commander:', data.deck.commanderId);
                    // Récupérer les détails du commander depuis la carte
                    fetch(`/api/card/${data.deck.commanderId}`)
                        .then(res => res.json())
                        .then(cmdData => {
                            if (cmdData.success) {
                                deckState.commander = cmdData.card;
                                searchCardsCache.set(cmdData.card.id, cmdData.card);
                                displayCommander();
                            }
                        })
                        .catch(err => console.error('Erreur chargement commander:', err));
                }
                
                // Charger les cartes du deck
                if (data.deck.cards && data.deck.cards.length > 0) {
                    console.log('Chargement de', data.deck.cards.length, 'cartes');
                    
                    data.deck.cards.forEach(cardData => {
                        // Ajouter à la cache
                        searchCardsCache.set(cardData.id, cardData);
                        
                        // Ajouter au deck state
                        deckState.cards.set(cardData.id, {
                            card: cardData,
                            quantity: cardData.quantity
                        });
                    });
                    
                    console.log('Cartes chargées dans deckState:', deckState.cards.size);
                }
                
                if (isViewMode) {
                    displayDeckView();
                } else {
                    updateDeckDisplay();
                }
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

        // Stocker les cartes dans la cache
        cards.forEach(card => searchCardsCache.set(card.id, card));

        searchResults.innerHTML = cards.map(card => `
            <div class="search-result-card" data-card-id="${card.id}">
                <img src="${card.imageUriSmall || card.bestImageUri || ''}" 
                     alt="${card.name}"
                     onerror="this.style.display='none'">
                <div class="card-info">
                    <div class="card-name">${card.name}</div>
                    <div class="card-type">${card.typeLine || ''}</div>
                </div>
                <button class="add-btn" onclick="addCardToDeck('${card.id}')">
                    Ajouter
                </button>
            </div>
        `).join('');
    }

    // Ajouter une carte au deck
    window.addCardToDeck = async function(cardId) {
        const card = searchCardsCache.get(cardId);
        if (!card) {
            console.error('Carte non trouvée:', cardId);
            return;
        }

        console.log('Ajout de carte:', card.name);

        // Sauvegarder la carte dans la BDD d'abord
        try {
            const saveResponse = await fetch('/api/card/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(card)
            });
            
            const saveResult = await saveResponse.json();
            if (!saveResult.success) {
                console.error('Erreur lors de la sauvegarde de la carte:', saveResult.error);
            } else {
                console.log('Carte sauvegardée en BDD:', card.name);
            }
        } catch (error) {
            console.error('Erreur lors de la sauvegarde de la carte:', error);
            // On continue quand même, la carte sera peut-être déjà en BDD
        }

        // Vérifications pour Commander
        if (deckState.format === 'Commander') {
            // Vérifier si c'est un légendaire pour le commander
            const isLegendary = card.typeLine && card.typeLine.includes('Legendary');
            const isCreatureOrVehicle = card.typeLine && 
                (card.typeLine.includes('Creature') || card.typeLine.includes('Vehicle'));

            // Si pas de commander et c'est un légendaire approprié, proposer comme commander
            if (!deckState.commander && isLegendary && isCreatureOrVehicle) {
                const confirmCommander = confirm(`Voulez-vous utiliser "${card.name}" comme commander ?`);
                if (confirmCommander) {
                    setCommander(card);
                    return;
                }
                // Si non, on continue pour l'ajouter au deck normalement
            }

            // Règle de la color identity du commander
            if (deckState.commander) {
                const commanderIdentity = deckState.commander.colorIdentity || [];
                const cardIdentity = card.colorIdentity || [];
                
                // Vérifier si la carte a des couleurs en dehors de l'identité du commander
                const hasInvalidColors = cardIdentity.some(color => !commanderIdentity.includes(color));
                
                if (hasInvalidColors) {
                    const commanderColors = commanderIdentity.length > 0 ? commanderIdentity.join(', ') : 'Incolore';
                    const cardColors = cardIdentity.length > 0 ? cardIdentity.join(', ') : 'Incolore';
                    alert(`Cette carte ne respecte pas l'identité de couleur de votre commander.\nCommander: ${commanderColors}\nCarte: ${cardColors}`);
                    return;
                }
            }

            // Règle des cartes uniques (sauf terrains de base)
            const isBasicLand = card.typeLine && 
                card.typeLine.includes('Basic') && 
                card.typeLine.includes('Land');

            if (deckState.cards.has(cardId) && !isBasicLand) {
                alert('En Commander, vous ne pouvez avoir qu\'un seul exemplaire de chaque carte (sauf terrains de base).');
                return;
            }
        }

        // Ajouter ou incrémenter
        if (deckState.cards.has(cardId)) {
            const cardEntry = deckState.cards.get(cardId);
            cardEntry.quantity++;
            console.log('Carte incrémentée:', card.name, 'quantité:', cardEntry.quantity);
        } else {
            deckState.cards.set(cardId, {
                card: card,
                quantity: 1
            });
            console.log('Carte ajoutée au deck:', card.name);
        }

        console.log('État du deck après ajout:', deckState.cards.size, 'cartes');
        updateDeckDisplay();
        
        // Basculer automatiquement sur l'onglet "Deck" après ajout d'une carte
        const deckTab = document.querySelector('.tab-btn[data-tab="deck"]');
        if (deckTab) {
            deckTab.click();
        }
        
        // Attendre un peu pour s'assurer que la carte est bien en BDD avant de sauvegarder le deck
        await new Promise(resolve => setTimeout(resolve, 100));
        await saveDeck();
    };

    // Définir le commander
    function setCommander(card) {
        deckState.commander = card;
        displayCommander();
        saveDeck();
    }

    function displayCommander() {
        const commanderZone = document.getElementById('commander-zone');
        if (!commanderZone) return;
        
        if (!deckState.commander) {
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
            return;
        }

        const card = deckState.commander;
        const imageUrl = card.imageUri || card.imageUriSmall || card.bestImageUri || '';
        
        commanderZone.innerHTML = `
            <div class="commander-card">
                <img src="${imageUrl}" 
                     alt="${card.name}"
                     onerror="this.style.display='none'">
                <div class="commander-info">
                    <div class="commander-name">${card.name}</div>
                    <div class="commander-type">${card.typeLine || ''}</div>
                </div>
                <button class="remove-btn" onclick="removeCommander()">
                    Retirer
                </button>
            </div>
        `;
    }

    window.removeCommander = function() {
        deckState.commander = null;
        displayCommander();
        saveDeck();
    };

    // Mettre à jour l'affichage du deck
    function updateDeckDisplay() {
        console.log('updateDeckDisplay appelé, nombre de cartes:', deckState.cards.size);
        const deckList = document.getElementById('deck-list');
        const totalCardsEl = document.getElementById('total-cards');

        let totalCards = 0;
        deckState.cards.forEach(entry => {
            totalCards += entry.quantity;
        });

        console.log('Total cartes à afficher:', totalCards);

        if (totalCardsEl) {
            totalCardsEl.textContent = totalCards;
        }

        if (!deckList) {
            console.error('deck-list element not found!');
            return;
        }

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
            const isBasicLand = card.typeLine && 
                card.typeLine.includes('Basic') && 
                card.typeLine.includes('Land');
            
            // Fallback pour l'image
            const imageUrl = card.imageUriSmall || card.imageUri || card.bestImageUri || '';

            return `
                <div class="deck-card-item" data-card-id="${cardId}">
                    <img src="${imageUrl}" 
                         alt="${card.name}"
                         onerror="this.style.display='none'">
                    <div class="card-info">
                        <div class="card-name">${card.name}</div>
                        <div class="card-type">${card.typeLine || ''}</div>
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
    window.saveDeck = async function() {
        if (typeof deckId === 'undefined') return;

        const deckData = {
            commander: deckState.commander,
            cards: Array.from(deckState.cards.entries()).map(([id, entry]) => ({
                scryfallId: id,
                quantity: entry.quantity
            }))
        };

        console.log('Sauvegarde du deck avec', deckData.cards.length, 'cartes');

        try {
            const response = await fetch(`/api/deck/${deckId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(deckData)
            });
            
            if (!response.ok) {
                console.error('Erreur HTTP:', response.status, response.statusText);
                return;
            }
            
            const result = await response.json();
            if (!result.success) {
                console.error('Erreur sauvegarde deck:', result.error || 'Erreur inconnue');
            } else {
                console.log('✅ Deck sauvegardé avec succès');
            }
        } catch (error) {
            console.error('❌ Erreur sauvegarde:', error);
        }
    }

    // Valider le deck
    window.validateDeck = function() {
        const totalCards = Array.from(deckState.cards.values())
            .reduce((sum, entry) => sum + entry.quantity, 0);
        
        const errors = [];
        const warnings = [];
        
        // Règles selon le format
        const format = (deckState.format || '').toLowerCase();
        
        if (format === 'commander') {
            if (!deckState.commander) {
                errors.push('Un deck Commander doit avoir un commandant');
            }
            
            // Commander format: 1 commandant + 99 cartes = 100 total
            // Les cartes du deck ne doivent pas inclure le commandant
            if (totalCards !== 99) {
                errors.push(`Un deck Commander doit contenir exactement 99 cartes (${totalCards} actuellement, sans compter le commandant)`);
            }
            
            // Vérifier la règle singleton (sauf terrains de base)
            for (const [id, entry] of deckState.cards.entries()) {
                const card = searchCardsCache.get(id);
                if (card && entry.quantity > 1) {
                    const isBasicLand = card.typeLine && card.typeLine.includes('Basic Land');
                    if (!isBasicLand) {
                        errors.push(`${card.name}: Les decks Commander ne peuvent avoir qu'un exemplaire de chaque carte (sauf terrains de base)`);
                    }
                }
            }
        } else if (format === 'standard' || format === 'modern') {
            if (totalCards < 60) {
                errors.push(`Un deck ${format} doit contenir au moins 60 cartes (${totalCards} actuellement)`);
            }
            
            // Limite de 4 exemplaires (sauf terrains de base)
            for (const [id, entry] of deckState.cards.entries()) {
                const card = searchCardsCache.get(id);
                if (card && entry.quantity > 4) {
                    const isBasicLand = card.typeLine && card.typeLine.includes('Basic Land');
                    if (!isBasicLand) {
                        warnings.push(`${card.name}: Maximum 4 exemplaires autorisés (${entry.quantity} actuellement)`);
                    }
                }
            }
        }
        
        // Afficher les résultats
        let message = '<div class="validation-results">';
        
        if (errors.length === 0 && warnings.length === 0) {
            message += '<div class="validation-success"><h3>✓ Deck valide!</h3>';
            message += `<p>Votre deck ${deckState.format} est conforme aux règles.</p>`;
            message += `<p>Total: ${totalCards} cartes</p></div>`;
        } else {
            if (errors.length > 0) {
                message += '<div class="validation-errors"><h3>❌ Erreurs</h3><ul>';
                errors.forEach(err => message += `<li>${err}</li>`);
                message += '</ul></div>';
            }
            
            if (warnings.length > 0) {
                message += '<div class="validation-warnings"><h3>⚠️ Avertissements</h3><ul>';
                warnings.forEach(warn => message += `<li>${warn}</li>`);
                message += '</ul></div>';
            }
        }
        
        message += '</div>';
        
        // Afficher dans une modal
        const modal = document.createElement('div');
        modal.className = 'validation-modal';
        
        let buttons = '';
        if (errors.length > 0) {
            // Deck invalide : deux boutons - continuer ou fermer
            buttons = `
                <button onclick="this.closest('.validation-modal').remove()" class="btn btn-secondary">Continuer l'édition</button>
                <button onclick="this.closest('.validation-modal').remove()" class="btn btn-primary">Fermer</button>
            `;
        } else {
            // Deck valide : bouton pour fermer
            buttons = `<button onclick="this.closest('.validation-modal').remove()" class="btn btn-primary">Fermer</button>`;
        }
        
        modal.innerHTML = `
            <div class="validation-modal-content">
                ${message}
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    ${buttons}
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    };


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

    // Filtres de type et couleur
    let activeFilters = {
        types: [],
        colors: []
    };

    // Gestion des filtres de type
    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filterType = this.dataset.filter;
            this.classList.toggle('active');
            
            if (this.classList.contains('active')) {
                if (!activeFilters.types.includes(filterType)) {
                    activeFilters.types.push(filterType);
                }
            } else {
                activeFilters.types = activeFilters.types.filter(t => t !== filterType);
            }
            
            applyFilters();
        });
    });

    // Gestion des filtres de couleur
    const colorBtns = document.querySelectorAll('.color-btn');
    colorBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const color = this.dataset.color;
            this.classList.toggle('active');
            
            if (this.classList.contains('active')) {
                if (!activeFilters.colors.includes(color)) {
                    activeFilters.colors.push(color);
                }
            } else {
                activeFilters.colors = activeFilters.colors.filter(c => c !== color);
            }
            
            applyFilters();
        });
    });

    function applyFilters() {
        const query = searchInput.value.trim();
        if (query.length < 2) return;
        
        // Construire la requête avec filtres
        let searchQuery = query;
        
        if (activeFilters.types.length > 0) {
            const typeQuery = activeFilters.types.map(t => `t:${t}`).join(' OR ');
            searchQuery += ` (${typeQuery})`;
        }
        
        if (activeFilters.colors.length > 0) {
            const colorQuery = activeFilters.colors.map(c => `c:${c}`).join(' OR ');
            searchQuery += ` (${colorQuery})`;
        }
        
        searchCards(searchQuery);
    }

    window.clearFilters = function() {
        const filterBtns = document.querySelectorAll('.filter-btn');
        const colorBtns = document.querySelectorAll('.color-btn');
        
        filterBtns.forEach(btn => btn.classList.remove('active'));
        colorBtns.forEach(btn => btn.classList.remove('active'));
        
        activeFilters = { types: [], colors: [] };
        
        // Relancer la recherche sans filtres
        const query = searchInput.value.trim();
        if (query.length >= 2) {
            searchCards(query);
        }
    };

    // Initialisation - sera appelée à la fin du script
    // if (typeof deckId !== 'undefined') {
    //     loadDeck();
    // }

    // Gestion de la modale d'ajout rapide
    let modalSearchTimeout;

    window.openQuickAddModal = function() {
        const modal = document.getElementById('quick-add-modal');
        if (modal) {
            modal.classList.add('active');
            const input = document.getElementById('modal-search-input');
            if (input) {
                setTimeout(() => input.focus(), 100);
            }
        }
    };

    window.closeQuickAddModal = function() {
        const modal = document.getElementById('quick-add-modal');
        if (modal) {
            modal.classList.remove('active');
        }
    };

    window.toggleAdvancedOptions = function() {
        const options = document.getElementById('advanced-options');
        if (options) {
            options.style.display = options.style.display === 'none' ? 'block' : 'none';
        }
    };

    // Recherche dans la modale
    const modalSearchInput = document.getElementById('modal-search-input');
    const quickSearchInput = document.getElementById('quick-search-input');

    if (modalSearchInput) {
        modalSearchInput.addEventListener('input', function(e) {
            clearTimeout(modalSearchTimeout);
            const query = e.target.value.trim();

            if (query.length < 2) {
                document.getElementById('modal-cards-grid').innerHTML = 
                    '<p style="text-align: center; color: #999; padding: 40px;">Recherchez une carte pour commencer</p>';
                return;
            }

            modalSearchTimeout = setTimeout(() => searchCardsModal(query), 300);
        });
    }

    if (quickSearchInput) {
        quickSearchInput.addEventListener('focus', function() {
            openQuickAddModal();
        });
    }

    async function searchCardsModal(query) {
        try {
            const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
            const data = await response.json();

            displayModalResults(data.cards || []);
            document.getElementById('modal-results-count').textContent = data.total_cards || 0;
        } catch (error) {
            console.error('Erreur recherche modale:', error);
            document.getElementById('modal-cards-grid').innerHTML = 
                '<p style="text-align: center; color: #f44336; padding: 40px;">Erreur de recherche</p>';
        }
    }

    function displayModalResults(cards) {
        const grid = document.getElementById('modal-cards-grid');
        
        if (!cards.length) {
            grid.innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">Aucune carte trouvée</p>';
            return;
        }

        // Stocker dans la cache
        cards.forEach(card => searchCardsCache.set(card.id, card));

        grid.innerHTML = cards.map(card => `
            <div class="search-result-card" data-card-id="${card.id}">
                <img src="${card.imageUriSmall || card.bestImageUri || ''}" 
                     alt="${card.name}"
                     onerror="this.style.display='none'">
                <div class="card-info">
                    <div class="card-name">${card.name}</div>
                    <div class="card-type">${card.typeLine || ''}</div>
                </div>
                <button class="add-btn" onclick="addCardFromModal('${card.id}'); event.stopPropagation();">
                    Ajouter
                </button>
            </div>
        `).join('');
    }

    window.addCardFromModal = function(cardId) {
        addCardToDeck(cardId);
        closeQuickAddModal();
    };

    // Raccourci clavier Ctrl + '
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === "'") {
            e.preventDefault();
            openQuickAddModal();
        }
        
        // Échap pour fermer la modale
        if (e.key === 'Escape') {
            closeQuickAddModal();
        }
    });

    // ============================================
    // MODE VISUALISATION
    // ============================================

    function displayDeckView() {
        if (!isViewMode) return;

        // Afficher le commander
        if (deckState.commander) {
            const commanderSection = document.getElementById('commander-section');
            const commanderDisplay = document.getElementById('commander-display');
            
            if (commanderSection && commanderDisplay) {
                commanderSection.style.display = 'block';
                commanderDisplay.innerHTML = `
                    <div class="commander-card-large">
                        <img src="${deckState.commander.imageUri || deckState.commander.imageUriSmall || deckState.commander.bestImageUri || ''}" 
                             alt="${deckState.commander.name}"
                             onerror="this.src='https://via.placeholder.com/250x350?text=No+Image'">
                        <div class="commander-info">
                            <h3>${deckState.commander.name}</h3>
                            <p class="type">${deckState.commander.typeLine || ''}</p>
                            <p class="mana-cost">${deckState.commander.manaCost || ''}</p>
                        </div>
                    </div>
                `;
            }
        }

        // Afficher la liste des cartes par catégories
        displayCategorizedDeckList();

        // Afficher les statistiques
        displayDeckStats();

        // Afficher la courbe de mana
        displayManaCurve();

        // Afficher la distribution des couleurs
        displayColorDistribution();
    }

    function displayCategorizedDeckList() {
        const container = document.getElementById('deck-list-view');
        if (!container) return;

        // Catégoriser les cartes
        const categories = {
            'Creatures': [],
            'Instants': [],
            'Sorceries': [],
            'Enchantments': [],
            'Artifacts': [],
            'Planeswalkers': [],
            'Lands': [],
            'Other': []
        };

        deckState.cards.forEach(({card, quantity}) => {
            const typeLine = (card.typeLine || '').toLowerCase();
            
            if (typeLine.includes('creature')) {
                categories.Creatures.push({card, quantity});
            } else if (typeLine.includes('instant')) {
                categories.Instants.push({card, quantity});
            } else if (typeLine.includes('sorcery')) {
                categories.Sorceries.push({card, quantity});
            } else if (typeLine.includes('enchantment')) {
                categories.Enchantments.push({card, quantity});
            } else if (typeLine.includes('artifact')) {
                categories.Artifacts.push({card, quantity});
            } else if (typeLine.includes('planeswalker')) {
                categories.Planeswalkers.push({card, quantity});
            } else if (typeLine.includes('land')) {
                categories.Lands.push({card, quantity});
            } else {
                categories.Other.push({card, quantity});
            }
        });

        // Trier par CMC puis par nom
        Object.keys(categories).forEach(cat => {
            categories[cat].sort((a, b) => {
                if (cat === 'Lands') return a.card.name.localeCompare(b.card.name);
                const cmcDiff = (a.card.cmc || 0) - (b.card.cmc || 0);
                return cmcDiff !== 0 ? cmcDiff : a.card.name.localeCompare(b.card.name);
            });
        });

        // Afficher
        let html = '';
        Object.entries(categories).forEach(([category, cards]) => {
            if (cards.length > 0) {
                html += `
                    <div class="deck-category">
                        <h3>${category} (${cards.reduce((sum, {quantity}) => sum + quantity, 0)})</h3>
                        <div class="deck-category-list">
                            ${cards.map(({card, quantity}) => `
                                <div class="deck-list-item">
                                    <span class="quantity">${quantity}x</span>
                                    <img src="${card.imageUriSmall || card.imageUri || card.bestImageUri || ''}" 
                                         alt="${card.name}"
                                         class="card-thumbnail"
                                         onerror="this.style.display='none'">
                                    <span class="card-name">${card.name}</span>
                                    <span class="card-mana">${card.manaCost || ''}</span>
                                    <span class="card-cmc">${card.cmc !== undefined ? card.cmc : ''}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
        });

        container.innerHTML = html;

        // Mettre à jour le total
        const totalCards = Array.from(deckState.cards.values())
            .reduce((sum, {quantity}) => sum + quantity, 0);
        const totalElement = document.getElementById('total-cards');
        if (totalElement) {
            totalElement.textContent = totalCards;
        }
    }

    function displayDeckStats() {
        const stats = calculateDeckStats();
        
        document.getElementById('stat-total').textContent = stats.total;
        document.getElementById('stat-commander').textContent = deckState.commander ? deckState.commander.name : '-';
        document.getElementById('stat-creatures').textContent = stats.creatures;
        document.getElementById('stat-instants').textContent = stats.instants;
        document.getElementById('stat-sorceries').textContent = stats.sorceries;
        document.getElementById('stat-enchantments').textContent = stats.enchantments;
        document.getElementById('stat-artifacts').textContent = stats.artifacts;
        document.getElementById('stat-planeswalkers').textContent = stats.planeswalkers;
        document.getElementById('stat-lands').textContent = stats.lands;
        document.getElementById('stat-avg-cmc').textContent = stats.avgCmc.toFixed(2);
    }

    function calculateDeckStats() {
        const stats = {
            total: 0,
            creatures: 0,
            instants: 0,
            sorceries: 0,
            enchantments: 0,
            artifacts: 0,
            planeswalkers: 0,
            lands: 0,
            totalCmc: 0,
            nonLandCards: 0
        };

        deckState.cards.forEach(({card, quantity}) => {
            stats.total += quantity;
            const typeLine = (card.typeLine || '').toLowerCase();
            const cmc = card.cmc || 0;

            if (typeLine.includes('creature')) stats.creatures += quantity;
            if (typeLine.includes('instant')) stats.instants += quantity;
            if (typeLine.includes('sorcery')) stats.sorceries += quantity;
            if (typeLine.includes('enchantment')) stats.enchantments += quantity;
            if (typeLine.includes('artifact')) stats.artifacts += quantity;
            if (typeLine.includes('planeswalker')) stats.planeswalkers += quantity;
            if (typeLine.includes('land')) {
                stats.lands += quantity;
            } else {
                stats.totalCmc += cmc * quantity;
                stats.nonLandCards += quantity;
            }
        });

        stats.avgCmc = stats.nonLandCards > 0 ? stats.totalCmc / stats.nonLandCards : 0;

        return stats;
    }

    function displayManaCurve() {
        const container = document.getElementById('mana-curve');
        if (!container) return;

        const curve = {};
        
        deckState.cards.forEach(({card, quantity}) => {
            const typeLine = (card.typeLine || '').toLowerCase();
            if (!typeLine.includes('land')) {
                const cmc = Math.min(card.cmc || 0, 7);
                const key = cmc === 7 ? '7+' : cmc;
                curve[key] = (curve[key] || 0) + quantity;
            }
        });

        const maxCount = Math.max(...Object.values(curve), 1);

        let html = '<div class="mana-curve-bars">';
        for (let i = 0; i <= 7; i++) {
            const key = i === 7 ? '7+' : i;
            const count = curve[key] || 0;
            const height = (count / maxCount) * 100;
            
            html += `
                <div class="mana-curve-bar-wrapper">
                    <div class="mana-curve-bar" style="height: ${height}%;">
                        <span class="count">${count}</span>
                    </div>
                    <span class="cmc-label">${key}</span>
                </div>
            `;
        }
        html += '</div>';

        container.innerHTML = html;
    }

    function displayColorDistribution() {
        const container = document.getElementById('color-distribution');
        if (!container) return;

        const colorCounts = {
            W: 0, U: 0, B: 0, R: 0, G: 0, C: 0
        };

        deckState.cards.forEach(({card, quantity}) => {
            const colors = card.colors || [];
            if (colors.length === 0) {
                colorCounts.C += quantity;
            } else {
                colors.forEach(color => {
                    if (colorCounts.hasOwnProperty(color)) {
                        colorCounts[color] += quantity;
                    }
                });
            }
        });

        const colorNames = {
            W: 'White',
            U: 'Blue',
            B: 'Black',
            R: 'Red',
            G: 'Green',
            C: 'Colorless'
        };

        const colorClasses = {
            W: 'white',
            U: 'blue',
            B: 'black',
            R: 'red',
            G: 'green',
            C: 'colorless'
        };

        const total = Object.values(colorCounts).reduce((sum, count) => sum + count, 0);

        let html = '<div class="color-bars">';
        Object.entries(colorCounts).forEach(([color, count]) => {
            if (count > 0) {
                const percentage = total > 0 ? (count / total * 100).toFixed(1) : 0;
                html += `
                    <div class="color-bar-item">
                        <div class="color-bar ${colorClasses[color]}" style="width: ${percentage}%;">
                            <span class="color-symbol">{${color}}</span>
                        </div>
                        <div class="color-info">
                            <span class="color-name">${colorNames[color]}</span>
                            <span class="color-count">${count} (${percentage}%)</span>
                        </div>
                    </div>
                `;
            }
        });
        html += '</div>';

        container.innerHTML = html;
    }

    // ============================================
    // COMMENTS MANAGEMENT
    // ============================================

    // Gestion de la suppression des commentaires
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-delete-comment')) {
            e.preventDefault();
            const button = e.target.closest('.btn-delete-comment');
            const commentId = button.dataset.commentId;
            
            if (confirm('Are you sure you want to delete this comment?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/comment/${commentId}/delete`;
                document.body.appendChild(form);
                form.submit();
            }
        }
    });

    // ============================================
    // INITIALISATION
    // ============================================

    // Charger le deck au démarrage
    loadDeck();

})();
