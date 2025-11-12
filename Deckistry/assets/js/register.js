/**
 * Registration Form - AJAX validation and password strength checker
 */
(function() {
  // Prevent multiple executions
  if (window.registerFormInitialized) {
    console.log('Register form already initialized, skipping...');
    return;
  }
  window.registerFormInitialized = true;

  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('registration-form');
    if (!form) {
      console.error('Registration form not found');
      return;
    }

    // Try to find fields by name attribute first, then by ID pattern
    const pseudoField = document.querySelector('[name="registration_form[username]"]') || 
                        document.querySelector('input[id*="username"]');
    const emailField = document.querySelector('[name="registration_form[email]"]') || 
                       document.querySelector('input[id*="email"]');
    const passwordField = document.querySelector('[name="registration_form[plainPassword][first]"]') || 
                          document.querySelector('input[id*="plainPassword_first"]');
    const confirmField = document.querySelector('[name="registration_form[plainPassword][second]"]') || 
                         document.querySelector('input[id*="plainPassword_second"]');
    const registerBtn = document.getElementById('register-btn');

    // Debug: log if fields are found
    console.log('Form elements found:', {
      form: !!form,
      pseudoField: !!pseudoField,
      emailField: !!emailField,
      passwordField: !!passwordField,
      confirmField: !!confirmField,
      registerBtn: !!registerBtn
    });

    if (!pseudoField || !emailField || !passwordField || !confirmField || !registerBtn) {
      console.error('Some form fields are missing');
      return;
    }

    // Prevent re-initialization of already initialized fields
    if (pseudoField.dataset.initialized) {
      console.log('Form fields already initialized, skipping...');
      return;
    }
    pseudoField.dataset.initialized = 'true';
    emailField.dataset.initialized = 'true';
    passwordField.dataset.initialized = 'true';
    confirmField.dataset.initialized = 'true';
  
  // Create error container for confirm password (check if already exists)
  let confirmError = confirmField.parentNode.querySelector('.form-error');
  if (!confirmError) {
    confirmError = document.createElement('div');
    confirmError.className = 'form-error';
    confirmField.parentNode.appendChild(confirmError);
  }
  
  // Validation state
  let isPseudoValid = false;
  let isEmailValid = false;
  let isPasswordValid = false;

  // Create error boxes
  const pseudoError = createErrorBox(pseudoField);
  const emailError = createErrorBox(emailField);
  const passwordError = createErrorBox(passwordField);

  // Password strength indicator
  const strengthBar = document.querySelector('.password-strength');
  const strengthMeter = document.querySelector('.strength-meter');
  strengthBar.style.display = 'none';
  strengthMeter.style.display = 'none';

  // Check username availability
  pseudoField.addEventListener('blur', () => {
    const pseudo = pseudoField.value.trim();
    if (!pseudo) return;

    // Get the check_pseudo route from data attribute or global variable
    const checkPseudoUrl = form.dataset.checkPseudoUrl || '/check-pseudo';
    
    fetch(checkPseudoUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ pseudo })
    })
    .then(res => res.json())
    .then(data => {
      if (data.exists) {
        pseudoError.innerHTML = 'Ce pseudo est déjà utilisé.';
        pseudoField.classList.add('has-error');
        isPseudoValid = false;
      } else {
        pseudoError.innerHTML = '';
        pseudoField.classList.remove('has-error');
        isPseudoValid = true;
      }
      toggleRegisterButton();
    });
  });

  // Check email availability
  emailField.addEventListener('blur', () => {
    const email = emailField.value.trim();
    if (!email) return;

    // Get the check_email route from data attribute or global variable
    const checkEmailUrl = form.dataset.checkEmailUrl || '/check-email';
    
    fetch(checkEmailUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    })
    .then(res => res.json())
    .then(data => {
      if (data.exists) {
        emailError.innerHTML = 'Cet email est déjà utilisé.';
        emailField.classList.add('has-error');
        isEmailValid = false;
      } else {
        emailError.innerHTML = '';
        emailField.classList.remove('has-error');
        isEmailValid = true;
      }
      toggleRegisterButton();
    });
  });

  // Password validation with strength meter
  function validatePassword() {
    const password = passwordField.value;
    const confirm = confirmField.value;

    let errors = [];
    let score = 0;
    
    if (password === '') {
        strengthBar.innerHTML = '';
        strengthBar.style.display = 'none';
        strengthMeter.innerHTML = '';
        strengthMeter.style.display = 'none';
        isPasswordValid = false;
        toggleRegisterButton();
        return;
    }

    // Show strength indicators
    strengthBar.style.display = 'block';
    strengthMeter.style.display = 'block';
    
    // Calculate password strength
    if (password.length >= 8) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;

    // Validation errors
    if (password.length < 8) errors.push('• Minimum 8 caractères');
    if (!/[A-Z]/.test(password)) errors.push('• Une majuscule');
    if (!/[a-z]/.test(password)) errors.push('• Une minuscule');
    if (!/[0-9]/.test(password)) errors.push('• Un chiffre');
    if (!/[^A-Za-z0-9]/.test(password)) errors.push('• Un caractère spécial');

    // Update strength bar
    let strength = '';
    let color = '';
    let width = '0%';

    if (score <= 2) {
      strength = 'Faible';
      color = 'red';
      width = '30%';
    } else if (score <= 4) {
      strength = 'Moyen';
      color = 'orange';
      width = '65%';
    } else {
      strength = 'Fort';
      color = 'green';
      width = '100%';
    }

    strengthBar.innerHTML = `<span style="color: ${color}; font-weight: bold;">Mot de passe : ${strength}</span>`;
    
    if (!strengthMeter.querySelector('.fill')) {
      const fill = document.createElement('div');
      fill.className = 'fill';
      strengthMeter.appendChild(fill);
    }

    const fillBar = strengthMeter.querySelector('.fill');
    fillBar.style.width = width;
    fillBar.style.backgroundColor = color;

    // Display strength errors
    if (errors.length > 0) {
      passwordError.innerHTML = errors.join('<br>');
      passwordField.classList.add('has-error');
      isPasswordValid = false;
    } else {
      passwordError.innerHTML = '';
      passwordField.classList.remove('has-error');
      isPasswordValid = true;
    }

    // Check password match
    if (confirm && password !== confirm) {
      confirmError.innerHTML = '• Les mots de passe ne correspondent pas';
      confirmField.classList.add('has-error');
      isPasswordValid = false;
    } else {
      confirmError.innerHTML = '';
      confirmField.classList.remove('has-error');
    }

    toggleRegisterButton();
  }

  passwordField.addEventListener('input', validatePassword);
  confirmField.addEventListener('input', validatePassword);

  function toggleRegisterButton() {
    registerBtn.disabled = !(isPseudoValid && isEmailValid && isPasswordValid);
  }

  function createErrorBox(field) {
    // Check if error box already exists
    let box = field.parentNode.querySelector('.form-error');
    if (!box) {
      box = document.createElement('div');
      box.className = 'form-error';
      field.parentNode.appendChild(box);
    }
    return box;
  }
  });
})();
