/**
 * Card Search - Autocomplete and search functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const suggestionsDiv = document.getElementById('searchSuggestions');
    let debounceTimer;

    if (!searchInput || !suggestionsDiv) return;

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
                            .map(name => `<div class="suggestion-item" onclick="window.selectSuggestion('${name.replace(/'/g, "\\'")}')">${name}</div>`)
                            .join('');
                        suggestionsDiv.classList.add('active');
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

    // Select suggestion function (global)
    window.selectSuggestion = function(name) {
        searchInput.value = name;
        suggestionsDiv.innerHTML = '';
        suggestionsDiv.classList.remove('active');
        searchInput.form.submit();
    };

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.classList.remove('active');
        }
    });

    // Add to collection function (to be implemented)
    window.addToCollection = function(cardId) {
        alert('Fonctionnalité à venir : ajouter ' + cardId + ' à votre collection');
    };
});
