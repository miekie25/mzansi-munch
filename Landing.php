<?php
    include 'includes/db_config.php';
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Home</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/landing.css">
</head>
<body>
    <?php 
        $current_page = 'landing'; 
        include 'includes/header.php';
    ?>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="hero-overlay">
            <h1>Local Taste, Delivered to Your Door</h1>
            <p>Support your favorite township entrepreneurs and enjoy authentic Mzansi flavors.</p>
            <div class="hero-btns">
                <a href="Shop.php"><button class="btn-primary">Browse Food</button></a>
                <a href="Register.php"><button class="btn-primary">Start Selling</button></a>
            </div>
        </div>
    </section>
	
    <!-- RECENTLY ADDED MEALS -->
    <section class="secondary-content">
        <h2>Recently Added Meals</h2>
        <div class="meal-container">
            <?php
            $query = "SELECT * FROM meals ORDER BY meal_id DESC LIMIT 3";
            $result = $conn->query($query);

            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    ?>
                    <div class="meal">
                        <?php if(!empty($row['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['meal_name']); ?>">
                        <?php endif; ?>
                        
                        <p><?php echo htmlspecialchars($row['meal_name']); ?> - R<?php echo number_format($row['price'], 2); ?></p>
                        
                        <button class="btn-primary" onclick="window.location.href='Shop.php'">Order Now</button>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="no-meals-message">
                    <i class="ti ti-tools-kitchen-2"></i>
                    <p>No meals available at the moment.</p>
                    <a href="Shop.php" class="browse-link">Browse all meals</a>
                </div>
                <?php
            }
            ?>
        </div>
    </section>
	
    <!-- KEY FEATURES -->
    <section class="key-features">
        <h2>Why Choose Mzansi Munch?</h2>
        <div class="features-container">
            <div class="feature-card">
                <img src="images/connect.jpg" alt="Easy Connection Icon">
                <h3>Easy to Connect</h3>
                <p>Find and chat with local sellers in your area instantly.</p>
            </div>
            <div class="feature-card">
                <img src="images/productListing.jpg" alt="Easy Listing Icon">
                <h3>Quick Listing</h3>
                <p>Upload your meals with photos and prices in just a few clicks.</p>
            </div>
            <div class="feature-card">
                <img src="images/payment.jpg" alt="Secure Payment Icon">
                <h3>Secure Payments</h3>
                <p>We hold payments safely until your food is delivered.</p>
            </div>
            <div class="feature-card">
                <img src="images/delivery.jpg" alt="Delivery Icon">
                <h3>Local Delivery</h3>
                <p>Fast delivery from partners who know your neighborhood.</p>
            </div>
        </div>
    </section>
	
    <!-- FINAL CALL TO ACTION -->
    <section class="final-cta">
        <h2>What's your move?</h2>
        <div class="cta-options">
            <div class="cta-box">
                <h3>Hungry yet?</h3>
                <p>Browse the best local meals in your area.</p>
                <a href="Shop.php"><button class="btn-primary">Order Now</button></a>
            </div>
            <div class="cta-box">
                <h3>Want to feed?</h3>
                <p>Turn your kitchen into a business today.</p>
                <a href="Register.php"><button class="btn-primary">Start Selling</button></a>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>