/**
 * Settings Page - Password validation and preferences toggles
 */
document.addEventListener('DOMContentLoaded', function() {
    // Password validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const matchDiv = document.getElementById('passwordMatch');
    const submitBtn = document.getElementById('passwordSubmit');

    function checkPasswords() {
        if (confirmPassword.value === '') {
            matchDiv.textContent = '';
            submitBtn.disabled = false;
            return;
        }

        if (newPassword.value === confirmPassword.value) {
            matchDiv.textContent = '✓ Les mots de passe correspondent';
            matchDiv.style.color = '#4CAF50';
            submitBtn.disabled = false;
        } else {
            matchDiv.textContent = '✗ Les mots de passe ne correspondent pas';
            matchDiv.style.color = '#f44336';
            submitBtn.disabled = true;
        }
    }

    if (newPassword && confirmPassword) {
        newPassword.addEventListener('input', checkPasswords);
        confirmPassword.addEventListener('input', checkPasswords);
    }

    // Dark mode toggle (local storage)
    const darkModeToggle = document.getElementById('darkMode');
    const darkMode = localStorage.getItem('darkMode') === 'true';
    
    if (darkModeToggle) {
        if (darkMode) {
            darkModeToggle.checked = true;
        }
        
        darkModeToggle.addEventListener('change', function() {
            localStorage.setItem('darkMode', this.checked);
            alert(this.checked ? 'Mode sombre activé !' : 'Mode clair activé !');
        });
    }

    // Email notifications toggle
    const emailNotifToggle = document.getElementById('emailNotif');
    const emailNotif = localStorage.getItem('emailNotif') === 'true';
    
    if (emailNotifToggle) {
        if (emailNotif) {
            emailNotifToggle.checked = true;
        }
        
        emailNotifToggle.addEventListener('change', function() {
            localStorage.setItem('emailNotif', this.checked);
            alert(this.checked ? 'Notifications activées !' : 'Notifications désactivées !');
        });
    }
});
