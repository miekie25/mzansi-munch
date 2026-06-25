<?php
// Ensure the session is active so we can see who is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Initialize cart count
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']); 
}
?>
<header>
    <div class="header-container">
        
        <button class="mobile-menu-toggle" aria-label="Toggle Navigation">&#9776;</button>

        <div class="logo-container">
            <a href="Landing.php">
                <img src="images/logo.jpg" alt="Mzansi Munch Logo" class="logo">
            </a>
        </div>

        <nav class="nav-links-wrapper">
            <a href="Landing.php" class="<?php echo ($current_page == 'landing') ? 'active' : ''; ?>">Home</a>
            <a href="Shop.php" class="<?php echo ($current_page == 'shop') ? 'active' : ''; ?>">Shop</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                    // Dynamic dashboard link based on role
                    $dashboard_link = 'Shop.php'; // fallback
                    if (isset($_SESSION['role'])) {
                        switch ($_SESSION['role']) {
                            case 'seller':
                                $dashboard_link = 'Dashboard.php';
                                break;
                            case 'delivery':
                                $dashboard_link = 'DeliveryDashboard.php';
                                break;
                            case 'buyer':
                                $dashboard_link = 'BuyerDashboard.php';
                                break;
                            case 'admin':
                                $dashboard_link = 'admin/AdminDashboard.php';
                                break;
                        }
                    }
                ?>
                <a href="<?php echo $dashboard_link; ?>" class="<?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">My Dashboard</a>
                <a href="Logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>

            <?php else: ?>
                <a href="Login.php" class="<?php echo ($current_page == 'login') ? 'active' : ''; ?>">Login</a>
                <a href="Register.php" class="<?php echo ($current_page == 'register') ? 'active' : ''; ?>">Register</a>
            <?php endif; ?>
        </nav>

        <div class="header-cart-container">
            <a href="Cart.php" class="cart-link">
                <div class="cart-wrapper">
                    <i class="fa-solid fa-cart-shopping cart-icon"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </div>
            </a>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links-wrapper');
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('show-mobile-menu');
        });
    }
});
</script>