document.addEventListener("DOMContentLoaded", function () {
    // 1. STOCK VALIDATION
    const buttons = document.querySelectorAll(".add-to-cart-btn");
    buttons.forEach(btn => {
        btn.addEventListener("click", function (e) {
            const stock = parseInt(this.dataset.stock);
            if (isNaN(stock) || stock <= 0) {
                e.preventDefault();
                alert("Sorry, this item is out of stock.");
            }
        });
    });

    // 2. SEARCH BAR LOGIC (With typo-tolerance & custom message)
    const searchBar = document.querySelector(".search-bar");
    searchBar.addEventListener("keyup", function () {
        const searchTerm = this.value.toLowerCase();
        const cards = document.querySelectorAll(".food-card");
        const noItemsMsg = document.getElementById("no-items-msg");
        let visibleCount = 0;

        cards.forEach(card => {
            const title = card.querySelector(".food-title").innerText.toLowerCase();

            // Basic Fuzzy Matching: 
            // Checks if the title includes the search term OR 
            // if the search term is similar enough (simple version)
            if (title.includes(searchTerm) || isSimilar(searchTerm, title)) {
                card.style.display = "block";
                visibleCount++;
            } else {
                card.style.display = "none";
            }
        });

        // Update the "No results" message
        if (noItemsMsg) {
            noItemsMsg.innerHTML = "<h3>We do not have what you are looking for right now :(</h3>";
            noItemsMsg.style.display = (visibleCount === 0) ? "block" : "none";
        }
    });
});

// Simple helper to catch minor typos (e.g., "cupcales" vs "cupcakes")
function isSimilar(s1, s2) {
    if (s1.length < 3) return false;
    let distance = 0;
    const len = Math.min(s1.length, s2.length);
    for (let i = 0; i < len; i++) {
        if (s1[i] !== s2[i]) distance++;
    }
    return distance <= 2; // Allows up to 2 character differences
}

// Function to handle quantity
function addToCart(element) {
    const mealId = element.dataset.id;
    const stock = parseInt(element.dataset.stock);
    const qty = parseInt(document.getElementById('qty-' + mealId).value);

    if (qty > stock) {
        alert("Sorry, we don't have that many in stock!");
        return;
    }
    if (qty < 1 || isNaN(qty)) {
        alert("Please enter a valid quantity.");
        return;
    }

    // Redirect to add_to_cart.php with ID and Quantity
    window.location.href = `add_to_cart.php?id=${mealId}&qty=${qty}`;
}

// ... Keep your existing filterMeals and Search logic below ...

// 3. CATEGORY FILTERING
function filterMeals(category) {
    const cards = document.querySelectorAll('.food-card');
    const noItemsMsg = document.getElementById('no-items-msg');
    let visibleCount = 0;

    cards.forEach(card => {
        if (category === 'all' || card.getAttribute('data-category') === category) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    if (noItemsMsg) {
        noItemsMsg.innerHTML = "<h3>We do not have this item in our shop right now.</h3>";
        noItemsMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
}