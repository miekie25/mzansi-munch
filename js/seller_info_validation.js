document.addEventListener("DOMContentLoaded", function () {

    const form = document.querySelector("form");

    const fname = document.querySelector("input[name='first_name']");
    const lname = document.querySelector("input[name='last_name']");
    const bizName = document.querySelector("input[name='biz_name']");
    const city = document.querySelector("input[name='city']");
    const postal = document.querySelector("input[name='postal_code']");

    const checkboxes = document.querySelectorAll("input[name='preferences[]']");

    // ----------------------------
    // SECURITY HELPERS
    // ----------------------------

    function sanitizeInput(value) {
        return value
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function containsMaliciousInput(value) {
        const pattern = /<script.*?>|<\/script>|on\w+=|javascript:/i;
        return pattern.test(value);
    }

    function validateSafeInput(input, value) {
        if (containsMaliciousInput(value)) {
            showError(input, "Invalid entry");
            return false;
        }

        input.value = sanitizeInput(value);
        return true;
    }

    // ----------------------------
    // ERROR HELPERS
    // ----------------------------

    function showError(input, message) {
        let error = input.nextElementSibling;

        if (!error || !error.classList.contains("error-msg")) {
            error = document.createElement("small");
            error.classList.add("error-msg");
            input.parentNode.appendChild(error);
        }

        error.textContent = message;
        error.style.color = "red";
    }

    function clearError(input) {
        let error = input.nextElementSibling;
        if (error && error.classList.contains("error-msg")) {
            error.textContent = "";
        }
    }

    // ----------------------------
    // CUISINE HELPERS
    // ----------------------------

    function isAnyChecked() {
        return Array.from(checkboxes).some(cb => cb.checked);
    }

    function showCuisineError(message) {
        let box = document.querySelector(".checkbox-grid");

        let error = box.querySelector(".error-msg");

        if (!error) {
            error = document.createElement("small");
            error.classList.add("error-msg");
            error.style.display = "block";
            error.style.marginTop = "10px";
            box.appendChild(error);
        }

        error.textContent = message;
        error.style.color = "red";
    }

    function clearCuisineError() {
        const error = document.querySelector(".checkbox-grid .error-msg");
        if (error) error.textContent = "";
    }

    // ----------------------------
    // LIVE VALIDATION
    // ----------------------------

    fname.addEventListener("input", () => {
        const val = fname.value.trim();

        if (val === "") {
            showError(fname, "This field is required");
        } else if (!validateSafeInput(fname, val)) {
            return;
        } else {
            clearError(fname);
        }
    });

    lname.addEventListener("input", () => {
        const val = lname.value.trim();

        if (val === "") {
            showError(lname, "This field is required");
        } else if (!validateSafeInput(lname, val)) {
            return;
        } else {
            clearError(lname);
        }
    });

    // Update your bizName validation logic in the JS file:
    bizName.addEventListener("input", () => {
        const val = bizName.value.trim();

        // Check if empty
        if (val === "") {
            showError(bizName, "Business name is required");
        }
        // Check for malicious code ONLY, allow letters, numbers, spaces, and apostrophes
        else if (containsMaliciousInput(val)) {
            showError(bizName, "Invalid characters detected");
        }
        else {
            clearError(bizName);
        }
    });

    city.addEventListener("input", () => {
        const val = city.value;

        if (val.trim() === "") {
            showError(city, "This field is required");
        } else if (containsMaliciousInput(val)) {
            showError(city, "Invalid entry");
        } else {
            clearError(city);
        }
    });

    postal.addEventListener("input", () => {
        const val = postal.value.trim();

        if (val === "") {
            showError(postal, "This field is required");
        } else if (!/^\d+$/.test(val)) {
            showError(postal, "Invalid entry");
        } else {
            clearError(postal);
        }
    });

    // checkbox live feedback
    checkboxes.forEach(cb => {
        cb.addEventListener("change", () => {
            if (isAnyChecked()) {
                clearCuisineError();
            }
        });
    });

    // ----------------------------
    // FINAL SUBMIT VALIDATION
    // ----------------------------

    form.addEventListener("submit", function (e) {

        let hasError = false;

        const fields = [fname, lname, bizName, city, postal];

        fields.forEach(field => {
            const val = field.value.trim();

            if (val === "") {
                showError(field, "This field is required");
                hasError = true;
                return;
            }

            if (!validateSafeInput(field, val)) {
                hasError = true;
            }
        });

        if (!/^\d+$/.test(postal.value.trim())) {
            showError(postal, "Invalid entry");
            hasError = true;
        }

        if (!isAnyChecked()) {
            showCuisineError("Please select at least one specialty");
            hasError = true;
        }

        if (hasError) {
            e.preventDefault();
        }
    });

});