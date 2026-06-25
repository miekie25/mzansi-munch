document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');

    const usernameError = document.getElementById('usernameError');
    const passwordError = document.getElementById('passwordError');

    // 1. Live Username Validation (Only checks if empty)
    usernameInput.addEventListener('input', function () {
        if (usernameInput.value.trim() === "") {
            usernameError.textContent = "Username is required.";
        } else {
            usernameError.textContent = "";
        }
    });

    // 2. Live Password Validation (Only checks if empty)
    passwordInput.addEventListener('input', function () {
        if (passwordInput.value === "") {
            passwordError.textContent = "Password is required.";
        } else {
            passwordError.textContent = "";
        }
    });

    // 3. Final Form Submission Gate
    loginForm.addEventListener('submit', function (event) {
        let hasErrors = false;

        if (usernameInput.value.trim() === "") {
            usernameError.textContent = "Username is required to log in.";
            hasErrors = true;
        }

        if (passwordInput.value === "") {
            passwordError.textContent = "Password is required to log in.";
            hasErrors = true;
        }

        if (hasErrors) {
            event.preventDefault(); // Stop submission if fields are blank
        }
    });

    // Password Reveal Toggle
    const togglePassword = document.getElementById('togglePassword');
    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.classList.toggle('fa-eye-slash');
    });
});