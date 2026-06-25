<?php
    include 'includes/db_config.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Shop</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/shop.css">
</head>
<body>

    <?php 
        $current_page = 'shop'; 
        include 'includes/header.php'; 
    ?>

    <main>
        <section class="filter-section">
    <div class="search-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="darkgreen" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" class="search-bar" placeholder="Search for Kotas, Platters, or Magwinya...">
    </div>
</section>

        <div class="shop-grid">
            <?php
            $query = "SELECT meals.*, sellers.business_name AS shop_name 
                      FROM meals 
                      LEFT JOIN sellers ON meals.seller_id = sellers.seller_id 
                      ORDER BY meals.meal_id DESC";
            $result = $conn->query($query);

            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $vendor_display = !empty($row['shop_name']) ? $row['shop_name'] : "Local Kitchen";
                    $stock = (int)$row['stock_quantity'];
                    $category = htmlspecialchars($row['category'] ?? 'uncategorized');
                    ?>
                    
                    <article class="food-card" data-category="<?php echo $category; ?>">
                        <div class="food-image">
                            <?php if(!empty($row['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['meal_name']); ?>">
                            <?php else: ?>
                                <div class="no-image">No Image Available</div>
                            <?php endif; ?>
                        </div>

                        <div class="card-content">
                            <p class="vendor-name"><?php echo htmlspecialchars($vendor_display); ?></p> 
                            <h3 class="food-title"><?php echo htmlspecialchars($row['meal_name']); ?></h3>
                            <p class="meal-description">
                                <?php echo htmlspecialchars($row['meal_description']); ?>
                            </p>

                            <?php if ($stock > 5): ?>
                                <p class="stock-status in-stock">In stock</p>
                            <?php elseif ($stock > 0): ?>
                                <p class="stock-status low-stock">Hurry, only <?php echo $stock; ?> left!</p>
                            <?php else: ?>
                                <p class="stock-status out-stock">Out of stock</p>
                            <?php endif; ?>

                            <div class="card-footer">
                                <span class="price">R <?php echo number_format($row['price'], 2); ?></span>
                                
                                <?php if ($stock > 0): ?>
                                    <div class="quantity-selector">
                                        <label for="qty-<?php echo $row['meal_id']; ?>">Qty:</label>
                                        <input type="number" id="qty-<?php echo $row['meal_id']; ?>" value="1" min="1" max="<?php echo $stock; ?>">
                                    </div>

                                    <button class="munch-btn add-to-cart-btn" 
                                            data-id="<?php echo $row['meal_id']; ?>" 
                                            data-stock="<?php echo $stock; ?>"
                                            onclick="addToCart(this)">
                                        Add to Munch
                                    </button>
                                <?php else: ?>
                                    <span class="munch-btn disabled">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                    <?php
                }
            } else {
                ?>
                <div class="no-meals-message">
                    <i class="ti ti-tools-kitchen-2"></i>
                    <h3>No items in the shop today</h3>
                    <p>Check back later — our sellers are cooking up something delicious!</p>
                </div>
                <?php
            }
            ?>

            <div id="no-items-msg" class="no-meals-message" style="display: none;">
                <i class="ti ti-search"></i>
                <h3>We do not have this item in our shop right now.</h3>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/shop_validation.js" defer></script>
</body>
</html>