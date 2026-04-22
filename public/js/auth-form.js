function showFieldError(input, message) {
  let help = input.parentElement.querySelector('.field-error');
  if (!help) {
    help = document.createElement('small');
    help.className = 'field-error';
    help.style.color = '#ef4444';
    help.style.marginTop = '4px';
    help.style.fontFamily = 'DM Sans, sans-serif';
    input.parentElement.appendChild(help);
  }
  help.textContent = message;
}

function clearFieldError(input) {
  const help = input.parentElement.querySelector('.field-error');
  if (help) help.remove();
}

function validateLoginForm(form) {
  const email = form.querySelector('[name="email"]');
  const password = form.querySelector('[name="password"]');
  let ok = true;

  clearFieldError(email);
  clearFieldError(password);

  if (!email.value.trim() || !email.validity.valid) {
    showFieldError(email, 'Veuillez saisir un email valide.');
    ok = false;
  }

  if ((password.value || '').length < 6) {
    showFieldError(password, 'Minimum 6 caracteres.');
    ok = false;
  }

  return ok;
}

function validateRegisterForm(form) {
  const required = ['lastname', 'firstname', 'email', 'phone', 'password', 'password2'];
  let ok = true;

  required.forEach((name) => {
    const input = form.querySelector(`[name="${name}"]`);
    clearFieldError(input);
    if (!input.value.trim()) {
      showFieldError(input, 'Champ obligatoire.');
      ok = false;
    }
  });

  const email = form.querySelector('[name="email"]');
  const password = form.querySelector('[name="password"]');
  const password2 = form.querySelector('[name="password2"]');

  if (email.value.trim() && !email.validity.valid) {
    showFieldError(email, 'Veuillez saisir un email valide.');
    ok = false;
  }

  if (password.value.length > 0 && password.value.length < 6) {
    showFieldError(password, 'Minimum 6 caracteres.');
    ok = false;
  }

  if (password.value && password2.value && password.value !== password2.value) {
    showFieldError(password2, 'Les mots de passe ne correspondent pas.');
    ok = false;
  }

  return ok;
}

document.addEventListener('DOMContentLoaded', () => {
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
      if (!validateLoginForm(loginForm)) {
        e.preventDefault();
      }
    });
  }

  const registerForm = document.getElementById('register-form');
  if (registerForm) {
    registerForm.addEventListener('submit', (e) => {
      if (!validateRegisterForm(registerForm)) {
        e.preventDefault();
      }
    });
  }
});
