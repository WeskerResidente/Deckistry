/**
 * Card Search - Autocomplete and search functionality
 */
(function() {
    'use strict';
    
    console.log('🔍 Search.js module loaded!');
    
    // Prevent multiple initializations
    if (window.searchFormInitialized) {
        console.log('⚠️ Search.js already initialized, skipping...');
        return;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Double-check initialization flag after DOMContentLoaded
        if (window.searchFormInitialized) {
            console.log('⚠️ Search.js already initialized in DOM, skipping...');
            return;
        }
        window.searchFormInitialized = true;
        
        console.log('🎯 DOMContentLoaded fired - Initializing search!');
        
        const searchInput = document.getElementById('searchInput');
        const suggestionsDiv = document.getElementById('searchSuggestions');
        const searchForm = document.querySelector('.search-form');
        let debounceTimer;

        console.log('Search elements:', {
            searchInput: searchInput,
            suggestionsDiv: suggestionsDiv,
            searchForm: searchForm
        });

    // ==========================================
    // FILTER TOGGLES - Collapsible sections
    // ==========================================
    const filterToggles = document.querySelectorAll('.filter-toggle');
    
    filterToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const targetId = this.getAttribute('data-target');
            const filterContent = document.getElementById(targetId);
            const arrow = this.querySelector('span');
            
            if (filterContent) {
                // Toggle visibility
                const isCurrentlyHidden = !filterContent.classList.contains('active');
                
                if (isCurrentlyHidden) {
                    // Open the section
                    filterContent.classList.add('active');
                    this.classList.add('active');
                    if (arrow) arrow.textContent = '▼';
                } else {
                    // Close the section
                    filterContent.classList.remove('active');
                    this.classList.remove('active');
                    if (arrow) arrow.textContent = '▶';
                }
            }
        });
    });

    // ==========================================
    // MANA COLOR FILTERS - Toggle selection
    // ==========================================
    const manaButtons = document.querySelectorAll('.mana-btn');
    
    manaButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.toggle('active');
            console.log('Mana button clicked:', this.getAttribute('data-color'), 'Active:', this.classList.contains('active'));
        });
    });

    // ==========================================
    // FORM SUBMISSION - Collect filter values
    // ==========================================
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            console.log('Form submitting...');
            
            // Remove old hidden inputs for filters
            searchForm.querySelectorAll('input[name="colors[]"]').forEach(el => el.remove());
            
            // Get selected mana colors
            const selectedColors = [];
            document.querySelectorAll('.mana-btn.active').forEach(btn => {
                const color = btn.getAttribute('data-color');
                selectedColors.push(color);
                console.log('Selected color:', color);
            });
            
            console.log('All selected colors:', selectedColors);
            
            // Add colors as hidden inputs
            selectedColors.forEach(color => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'colors[]';
                input.value = color;
                searchForm.appendChild(input);
                console.log('Added hidden input for color:', color);
            });
            
            // Logic is already handled by radio buttons in the form
            console.log('Form will submit with all filters');
        });
    }

    // ==========================================
    // AUTOCOMPLETE - Only if elements exist
    // ==========================================
    if (searchInput && suggestionsDiv) {
        // Get autocomplete URL from data attribute
        const autocompleteUrl = searchInput.dataset.autocompleteUrl;
    
        if (!autocompleteUrl) {
            console.error('Autocomplete URL not found');
            return;
        }

        // Autocomplete with debounce
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            
            if (query.length < 2) {
                suggestionsDiv.innerHTML = '';
                suggestionsDiv.classList.remove('active');
                return;
            }
            
            debounceTimer = setTimeout(() => {
                fetch(`${autocompleteUrl}?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(suggestions => {
                        if (suggestions.length > 0) {
                            suggestionsDiv.innerHTML = suggestions
                                .map(name => `<div class="suggestion-item" data-card-name="${name.replace(/"/g, '&quot;')}">${name}</div>`)
                                .join('');
                            suggestionsDiv.classList.add('active');
                            
                            // Add click listeners to suggestion items
                            suggestionsDiv.querySelectorAll('.suggestion-item').forEach(item => {
                                item.addEventListener('click', function(e) {
                                    e.stopPropagation();
                                    const cardName = this.getAttribute('data-card-name');
                                    searchInput.value = cardName;
                                    suggestionsDiv.innerHTML = '';
                                    suggestionsDiv.classList.remove('active');
                                    searchInput.form.submit();
                                });
                            });
                        } else {
                            suggestionsDiv.innerHTML = '';
                            suggestionsDiv.classList.remove('active');
                        }
                    })
                    .catch(error => {
                        console.error('Autocomplete error:', error);
                    });
            }, 300);
        });

        // Close suggestions when clicking outside (but not on navigation elements)
        document.addEventListener('click', function(e) {
            // Allow all clicks on header and navigation
            if (e.target.closest('.site-header') || 
                e.target.closest('.header-container')) {
                return; // Don't interfere with header clicks at all
            }
            
            // Only close suggestions if clicking outside search area
            if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.classList.remove('active');
            }
        }, { capture: false }); // Use bubbling phase, not capture
    }

    // Add to collection function
    window.addToCollection = async function(cardId) {
        const btn = event?.target || document.querySelector(`button[onclick*="${cardId}"]`);
        
        try {
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Adding...';
            }
            
            const response = await fetch('/api/collection/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    scryfallId: cardId,
                    quantity: 1
                })
            });

            const data = await response.json();

            if (data.success) {
                // Show success message
                if (btn) {
                    btn.textContent = '✓ Added!';
                    btn.style.background = '#4caf50';
                    btn.disabled = false;
                    
                    setTimeout(() => {
                        btn.textContent = '+ Collection';
                        btn.style.background = '';
                    }, 2000);
                }
            } else {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = '+ Collection';
                }
                alert('Error: ' + (data.error || 'Could not add card to collection'));
            }
        } catch (error) {
            console.error('Error adding to collection:', error);
            if (btn) {
                btn.disabled = false;
                btn.textContent = '+ Collection';
            }
            alert('Error adding card to collection. Please try again.');
        }
    };

    // Flip card function for double-faced cards
    window.flipCard = function(cardId) {
        const wrapper = document.querySelector(`.card-image-wrapper[data-card-id="${cardId}"]`);
        if (!wrapper) return;
        
        const img = wrapper.querySelector('.card-image');
        if (!img) return;
        
        const frontImage = img.getAttribute('data-front-image');
        const backImage = img.getAttribute('data-back-image');
        
        if (!backImage) return; // Not a double-faced card
        
        // Toggle between front and back
        if (img.classList.contains('card-face-front')) {
            img.src = backImage;
            img.classList.remove('card-face-front');
            img.classList.add('card-face-back');
        } else {
            img.src = frontImage;
            img.classList.remove('card-face-back');
            img.classList.add('card-face-front');
        }
    };
    });
})();
