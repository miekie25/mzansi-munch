document.addEventListener("DOMContentLoaded", function () {

    const form = document.querySelector("form");

    const fname = document.querySelector("input[name='first_name']");
    const lname = document.querySelector("input[name='last_name']");
    const city = document.querySelector("input[name='city']");
    const postal = document.querySelector("input[name='postal_code']");

    const vehicleOptions = document.querySelectorAll("input[name='vehicle_type']");

    // ----------------------------
    // SECURITY HELPERS
    // ----------------------------

    function sanitizeInput(value) {
        return value
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
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
    // VEHICLE VALIDATION HELPERS
    // ----------------------------

    function isVehicleSelected() {
        return Array.from(vehicleOptions).some(r => r.checked);
    }

    function showVehicleError(message) {
        let box = document.querySelector(".delivery-options");

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

    function clearVehicleError() {
        const error = document.querySelector(".delivery-options .error-msg");
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

    city.addEventListener("input", () => {
        const val = city.value.trim();

        if (val === "") {
            showError(city, "This field is required");
        } else if (!validateSafeInput(city, val)) {
            return;
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

    // radio live feedback
    vehicleOptions.forEach(option => {
        option.addEventListener("change", () => {
            if (isVehicleSelected()) {
                clearVehicleError();
            }
        });
    });

    // ----------------------------
    // FINAL SUBMIT VALIDATION
    // ----------------------------

    form.addEventListener("submit", function (e) {

        let hasError = false;

        const fields = [fname, lname, city, postal];

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

        // radio validation (required selection)
        if (!isVehicleSelected()) {
            showVehicleError("Please select a delivery method");
            hasError = true;
        }

        if (hasError) {
            e.preventDefault();
        }
    });

});