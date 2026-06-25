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

$seller_id = null;
$first_name = $username;
$full_name = "New Seller";
$initials = strtoupper(substr($username, 0, 2));

$stmt = $conn->prepare("SELECT seller_id, first_name, last_name FROM sellers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $seller_id  = $row['seller_id'];
    $first_name = $row['first_name'];
    $full_name  = $row['first_name'] . " " . $row['last_name'];
    $initials   = strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1));
}
$stmt->close();

$earnings_total = 0;
$earnings_today = 0;
$total_orders = 0;
$pending_orders = 0;
$meals_count = 0;
$low_stock_count = 0;

if ($seller_id) {
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM seller_earnings WHERE seller_id = ?");
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $earn_result = $stmt->get_result();
    if ($earn_row = $earn_result->fetch_assoc()) {
        $earnings_total = $earn_row['total'] ?? 0;
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM seller_earnings WHERE seller_id = ? AND DATE(earned_at) = CURDATE()");
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $today_result = $stmt->get_result();
    if ($today_row = $today_result->fetch_assoc()) {
        $earnings_today = $today_row['total'] ?? 0;
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT order_id) as total FROM seller_earnings WHERE seller_id = ?");
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $orders_result = $stmt->get_result();
    if ($orders_row = $orders_result->fetch_assoc()) {
        $total_orders = $orders_row['total'] ?? 0;
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT o.id) as total 
                            FROM orders o 
                            INNER JOIN order_items oi ON o.id = oi.order_id 
                            WHERE oi.seller_id = ? AND o.order_status = 'Pending'");
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $pending_result = $stmt->get_result();
    if ($pending_row = $pending_result->fetch_assoc()) {
        $pending_orders = $pending_row['total'] ?? 0;
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM meals WHERE seller_id = ?");
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $meals_result = $stmt->get_result();
    if ($meals_row = $meals_result->fetch_assoc()) {
        $meals_count = $meals_row['total'] ?? 0;
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM meals WHERE seller_id = ? AND stock_quantity < 5");
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $low_result = $stmt->get_result();
    if ($low_row = $low_result->fetch_assoc()) {
        $low_stock_count = $low_row['total'] ?? 0;
    }
    $stmt->close();

    $recent_orders = [];
    $stmt = $conn->prepare("SELECT o.id, o.order_status, o.created_at, o.delivery_address,
                            SUM(oi.quantity * oi.price) as seller_amount,
                            GROUP_CONCAT(CONCAT(oi.quantity, 'x ', oi.meal_name) SEPARATOR ', ') as items
                            FROM orders o
                            INNER JOIN order_items oi ON o.id = oi.order_id
                            WHERE oi.seller_id = ?
                            GROUP BY o.id
                            ORDER BY o.created_at DESC
                            LIMIT 5");
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $recent_result = $stmt->get_result();
    while ($order = $recent_result->fetch_assoc()) {
        $recent_orders[] = $order;
    }
    $stmt->close();

    $low_stock_items = [];
    $stmt = $conn->prepare("SELECT meal_id, meal_name, stock_quantity FROM meals WHERE seller_id = ? AND stock_quantity < 5 ORDER BY stock_quantity ASC LIMIT 5");
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $low_items_result = $stmt->get_result();
    while ($item = $low_items_result->fetch_assoc()) {
        $low_stock_items[] = $item;
    }
    $stmt->close();
}
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Seller Dashboard</title>
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
                <a href="Dashboard.php" class="nav-item active"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
                <a href="AddMeal.php" class="nav-item"><i class="ti ti-tools-kitchen-2"></i> Sell a meal</a>
                <a href="CheckStock.php" class="nav-item"><i class="ti ti-package"></i> Check stock</a>
                <a href="ManageOrders.php" class="nav-item">
                    <i class="ti ti-receipt"></i> Manage orders
                    <?php if ($pending_orders > 0): ?>
                        <span class="notif-badge"><?php echo $pending_orders; ?></span>
                    <?php endif; ?>
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
            <h1 class="greeting">Hello, <?php echo htmlspecialchars($first_name); ?></h1>
            <p class="greeting-sub">Here's what's happening with your store today.</p>

            <div class="earnings-card">
                <h3>Total Earnings</h3>
                <div class="amount">R <?php echo number_format($earnings_total, 2); ?></div>
                <div class="sub">R <?php echo number_format($earnings_today, 2); ?> earned today</div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending Orders</div>
                    <div class="stat-value danger"><?php echo number_format($pending_orders); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Meals Listed</div>
                    <div class="stat-value success"><?php echo number_format($meals_count); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Low Stock</div>
                    <div class="stat-value <?php echo $low_stock_count > 0 ? 'danger' : 'success'; ?>">
                        <?php echo number_format($low_stock_count); ?>
                    </div>
                </div>
            </div>

            <div class="dash-sections">
                <div class="section-card">
                    <h3><i class="ti ti-receipt"></i> Recent Orders</h3>
                    <?php if (!empty($recent_orders)): ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Items</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): 
                                    $status_class = 'status-pending';
                                    $status_lower = strtolower($order['order_status']);
                                    if ($status_lower == 'prepared') $status_class = 'status-prepared';
                                    elseif ($status_lower == 'ready for pickup') $status_class = 'status-ready';
                                    elseif ($status_lower == 'accepted') $status_class = 'status-accepted';
                                    elseif ($status_lower == 'out for delivery') $status_class = 'status-out';
                                    elseif ($status_lower == 'delivered') $status_class = 'status-delivered';
                                ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['items'] ?? 'N/A'); ?></td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                                    <td>R <?php echo number_format($order['seller_amount'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <a href="ManageOrders.php" class="view-all-link">
                            <i class="ti ti-arrow-right"></i> View all orders
                        </a>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="ti ti-receipt-off"></i>
                            No orders yet. Start selling!
                        </div>
                    <?php endif; ?>
                </div>

                <div class="section-card">
                    <h3><i class="ti ti-alert-triangle"></i> Low Stock Alerts</h3>
                    <?php if (!empty($low_stock_items)): ?>
                        <ul class="low-stock-list">
                            <?php foreach ($low_stock_items as $item): 
                                $stock_class = $item['stock_quantity'] <= 2 ? 'stock-critical' : 'stock-low';
                            ?>
                            <li>
                                <span class="item-name"><?php echo htmlspecialchars($item['meal_name']); ?></span>
                                <span class="stock-count <?php echo $stock_class; ?>"><?php echo $item['stock_quantity']; ?> left</span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="CheckStock.php" class="view-all-link">
                            <i class="ti ti-arrow-right"></i> Manage stock
                        </a>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="ti ti-check-circle"></i>
                            All stock levels are healthy!
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <h3 class="quick-links-heading"><i class="ti ti-bolt"></i> Quick Links</h3>
            <div class="dash-actions">
                <a href="AddMeal.php" class="dash-card"><i class="ti ti-tools-kitchen-2"></i><span>Sell a meal</span></a>
                <a href="CheckStock.php" class="dash-card"><i class="ti ti-package"></i><span>Check stock</span></a>
                <a href="ManageOrders.php" class="dash-card">
                    <i class="ti ti-receipt"></i><span>Manage orders</span>
                    <?php if ($pending_orders > 0): ?>
                        <span class="notif-badge"><?php echo $pending_orders; ?></span>
                    <?php endif; ?>
                </a>
                <a href="Income.php" class="dash-card"><i class="ti ti-chart-bar"></i><span>View income</span></a>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>