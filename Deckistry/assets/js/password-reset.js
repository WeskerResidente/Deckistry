/**
 * Password Reset - Password confirmation validation
 */
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const matchDiv = document.getElementById('passwordMatch');
    const submitBtn = document.getElementById('submitBtn');

    function checkPasswords() {
        if (confirmPassword.value === '') {
            matchDiv.textContent = '';
            matchDiv.className = 'form-text';
            submitBtn.disabled = false;
            return;
        }

        if (password.value === confirmPassword.value) {
            matchDiv.textContent = '✓ Les mots de passe correspondent';
            matchDiv.className = 'form-text text-success';
            submitBtn.disabled = false;
        } else {
            matchDiv.textContent = '✗ Les mots de passe ne correspondent pas';
            matchDiv.className = 'form-text text-danger';
            submitBtn.disabled = true;
        }
    }

    password.addEventListener('input', checkPasswords);
    confirmPassword.addEventListener('input', checkPasswords);
});
