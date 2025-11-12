// Avatar upload preview
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validation du type de fichier
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format de fichier non autorisé. Utilisez JPG, PNG, GIF ou WEBP.');
                    avatarInput.value = '';
                    return;
                }
                
                // Validation de la taille (2 Mo max)
                const maxSize = 2 * 1024 * 1024; // 2 Mo
                if (file.size > maxSize) {
                    alert('Le fichier est trop volumineux. Taille maximale : 2 Mo.');
                    avatarInput.value = '';
                    return;
                }
                
                // Prévisualisation de l'image
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Si l'élément de prévisualisation est un div (placeholder), on le remplace par une image
                    if (avatarPreview.tagName === 'DIV') {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Preview';
                        img.className = 'avatar-preview-image';
                        img.id = 'avatarPreview';
                        avatarPreview.parentNode.replaceChild(img, avatarPreview);
                    } else {
                        // Si c'est déjà une image, on met à jour simplement la source
                        avatarPreview.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
