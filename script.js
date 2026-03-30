// Toggle between Sign In and Sign Up forms
function toggleForms() {

    const signinForm = document.getElementById('signin-form');
    const signupForm = document.getElementById('signup-form');

    signinForm.classList.toggle('hidden');
    signinForm.classList.toggle('active');
    signupForm.classList.toggle('hidden');
    signupForm.classList.toggle('active');
}

// Toggle password visibility

function togglePassword(inputId, btn) {

    const passwordInput = document.getElementById(inputId);
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        btn.innerHTML = '🙈';
    } else {                                
        passwordInput.type = 'password';
        btn.innerHTML = '👁️️';
    }
}