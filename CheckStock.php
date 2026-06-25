<?php
session_start();
include 'includes/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

if ($_SESSION['role'] !== 'seller') {
    header("Location: Shop.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch seller
$stmt = $conn->prepare("SELECT seller_id, first_name, last_name FROM sellers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$seller = $res->fetch_assoc();
$stmt->close();

if (!$seller) {
    die("Seller account not found.");
}

$seller_id  = $seller['seller_id'];
$first_name = $seller['first_name'];
$last_name  = $seller['last_name'];
$full_name  = trim("$first_name $last_name") ?: 'Seller';
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) ?: strtoupper(substr($username, 0, 2));

// Fetch meals
$stmt = $conn->prepare("SELECT meal_id, meal_name, price, stock_quantity, image_url FROM meals WHERE seller_id = ? ORDER BY meal_id DESC");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Count low stock
$low_stock_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM meals WHERE seller_id = ? AND stock_quantity < 5");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$low_stock_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Check Stock</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/SellerDashboard.css">
</head>
<body class="dashboard-body">
    <?php
        $current_page = 'dashboard';
        include 'includes/header.php';
    ?>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-profile">
                <div class="pfp-circle"><?php echo htmlspecialchars($initials); ?></div>
                <div class="profile-info">
                    <span class="profile-name"><?php echo htmlspecialchars($full_name); ?></span>
                    <span class="profile-handle">@<?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <span class="nav-label">Menu</span>
                <a href="Dashboard.php" class="nav-item"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
                <a href="AddMeal.php" class="nav-item"><i class="ti ti-tools-kitchen-2"></i> Sell a meal</a>
                <a href="CheckStock.php" class="nav-item active"><i class="ti ti-package"></i> Check stock</a>
                <a href="ManageOrders.php" class="nav-item">
                    <i class="ti ti-receipt"></i> Manage orders
                </a>
                <a href="ManageMeals.php" class="nav-item"><i class="ti ti-edit"></i> Manage meals</a>
                <a href="Income.php" class="nav-item"><i class="ti ti-chart-bar"></i> View income</a>
                <span class="nav-label">Account</span>
                <a href="Settings.php" class="nav-item"><i class="ti ti-settings"></i> Settings</a>
            </nav>

            <div class="sidebar-footer">
                <a href="Logout.php" class="logout-link"><i class="ti ti-logout"></i> Log out</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <h1 class="greeting">Check Stock</h1>
            <p class="greeting-sub">View and manage your current active food batches.</p>

            <?php if (isset($_SESSION['stock_success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 1.5rem;">
        <i class="ti ti-circle-check"></i>
        <?php 
            echo $_SESSION['stock_success']; 
            unset($_SESSION['stock_success']); 
        ?>
    </div>
<?php endif; ?>

            <?php if ($low_stock_count > 0): ?>
                <div class="alert alert-warning" style="margin-bottom: 1.5rem;">
                    <i class="ti ti-alert-triangle"></i>
                    <strong><?php echo $low_stock_count; ?></strong> item(s) are running low on stock.
                </div>
            <?php endif; ?>

            <div class="stock-grid">
                <?php if ($result && $result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        $stock    = intval($row['stock_quantity']);
                        $stock_class = $stock === 0 ? 'out' : ($stock < 5 ? 'low' : 'good');
                ?>
                    <div class="stock-card">
                        <div class="stock-image-wrapper">
                            <?php if (!empty($row['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['meal_name']); ?>">
                            <?php else: ?>
                                <div class="no-image">No Image Available</div>
                            <?php endif; ?>

                            <span class="stock-badge <?php echo $stock_class; ?>">
                                <?php echo $stock; ?> Available
                            </span>
                        </div>

                        <div class="stock-card-body">
                            <div class="stock-info">
                                <h3><?php echo htmlspecialchars($row['meal_name']); ?></h3>
                                <p class="stock-price">R <?php echo number_format($row['price'], 2); ?></p>
                            </div>

                            <form action="update_stock.php" method="POST" class="stock-form">
                                <input type="hidden" name="meal_id" value="<?php echo $row['meal_id']; ?>">
                                <input type="number" name="new_stock" value="<?php echo $stock; ?>" min="0">
                                <button type="submit" class="btn-update">Update</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile;
                else: ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="ti ti-tools-kitchen-2"></i>
                        <p>No active meals found in your inventory.</p>
                        <a href="AddMeal.php" class="btn-primary">Add your first meal</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>