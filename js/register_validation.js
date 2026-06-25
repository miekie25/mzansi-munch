document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('registerForm');
    const username = document.getElementById('username');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');

    // Requirement Elements
    const lenReq = document.getElementById('len');
    const capReq = document.getElementById('cap');
    const lowReq = document.getElementById('low');
    const specReq = document.getElementById('spec');

    function isUsernameSafe(val) {
        const forbidden = ['admin', 'root', 'superuser', 'mzansimunch'];
        const onlyAlphanumeric = /^[a-zA-Z0-9]+$/;
        if (forbidden.includes(val.toLowerCase())) return "This username is reserved.";
        if (!onlyAlphanumeric.test(val)) return "Username can only contain letters and numbers.";
        return true;
    }

    function isEmailValid(val) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
    }

    const isComplex = (val) => /[A-Z]/.test(val) && /[a-z]/.test(val) && /[_*@]/.test(val) && val.length >= 6;

    async function checkAvailability(field, value) {
        const response = await fetch(`check_user.php?field=${field}&value=${encodeURIComponent(value)}`);
        return await response.text();
    }

    // 1. Real-time password requirement listener
    password.addEventListener('input', function () {
        const val = this.value;
        if (lenReq) lenReq.style.color = val.length >= 6 ? 'green' : 'gray';
        if (capReq) capReq.style.color = /[A-Z]/.test(val) ? 'green' : 'gray';
        if (lowReq) lowReq.style.color = /[a-z]/.test(val) ? 'green' : 'gray';
        if (specReq) specReq.style.color = /[_*@]/.test(val) ? 'green' : 'gray';
    });

    // 2. Password visibility toggle
    function toggleVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

        if (icon) {
            icon.addEventListener('click', () => {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
    }
    toggleVisibility('password', 'togglePassword');
    toggleVisibility('confirm_password', 'toggleConfirmPassword');

    // 3. Real-time field validation listeners
    username.addEventListener('input', async function () {
        const err = document.getElementById('usernameError');
        const val = this.value.trim();
        const safeCheck = isUsernameSafe(val);
        if (val.length < 4) err.textContent = "Minimum 4 characters required.";
        else if (safeCheck !== true) err.textContent = safeCheck;
        else {
            const status = await checkAvailability('username', val);
            err.textContent = (status === 'taken') ? "Username taken." : "";
        }
    });

    email.addEventListener('input', async function () {
        const err = document.getElementById('emailError');
        if (!isEmailValid(this.value.trim())) err.textContent = "Invalid email format.";
        else {
            const status = await checkAvailability('email', this.value.trim());
            err.textContent = (status === 'taken') ? "Email already registered." : "";
        }
    });

    // 4. Form submission handler
    form.addEventListener('submit', function (e) {
        let hasErrors = false;
        const errorElements = document.querySelectorAll('.error-msg');
        errorElements.forEach(el => el.textContent = '');

        const setError = (element, message) => {
            element.textContent = message;
            hasErrors = true;
        };

        if (username.value.trim() === "") setError(document.getElementById('usernameError'), "Please fill in this field.");
        else if (username.value.trim().length < 4 || isUsernameSafe(username.value.trim()) !== true) {
            setError(document.getElementById('usernameError'), isUsernameSafe(username.value.trim()) !== true ? isUsernameSafe(username.value.trim()) : "Min 4 characters.");
        }

        if (email.value.trim() === "") setError(document.getElementById('emailError'), "Please fill in this field.");
        else if (!isEmailValid(email.value.trim())) setError(document.getElementById('emailError'), "Invalid email format.");

        if (password.value === "") setError(document.getElementById('passwordError'), "Please fill in this field.");
        else if (!isComplex(password.value)) setError(document.getElementById('passwordError'), "Password too weak.");

        if (confirm.value === "") setError(document.getElementById('confirmError'), "Please fill in this field.");
        else if (confirm.value !== password.value) setError(document.getElementById('confirmError'), "Passwords do not match.");

        if (hasErrors) {
            e.preventDefault();
            document.querySelector('.error-msg:not(:empty)').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});