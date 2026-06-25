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

// Resolve seller_id
$stmt = $conn->prepare("SELECT seller_id, first_name, last_name FROM sellers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    die("Seller account not found.");
}

$seller_id = $res['seller_id'];
$first_name = $res['first_name'] ?? $username;
$last_name = $res['last_name'] ?? '';
$full_name = trim("$first_name $last_name") ?: 'Seller';
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) ?: strtoupper(substr($username, 0, 2));

// Fetch distinct orders
$stmt = $conn->prepare("SELECT DISTINCT o.id, o.delivery_address, o.order_status 
                         FROM orders o 
                         INNER JOIN order_items oi ON o.id = oi.order_id 
                         WHERE oi.seller_id = ? 
                         ORDER BY o.id DESC");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$stmt->close();

// Count pending orders for badge
$stmt = $conn->prepare("SELECT COUNT(DISTINCT o.id) as total 
                        FROM orders o 
                        INNER JOIN order_items oi ON o.id = oi.order_id 
                        WHERE oi.seller_id = ? AND o.order_status = 'Pending'");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$pending_orders = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Manage Orders</title>
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
                <a href="CheckStock.php" class="nav-item"><i class="ti ti-package"></i> Check stock</a>
                <a href="ManageOrders.php" class="nav-item active">
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
            <h1 class="greeting">Manage Orders</h1>
            <p class="greeting-sub">Track, accept, and complete live customer requests.</p>

            <div class="section-card">
                <h3><i class="ti ti-receipt"></i> Incoming Kitchen Orders</h3>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Items Ordered</th>
                                <th>Delivery Address</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($orders_result && $orders_result->num_rows > 0):
                                while ($order = $orders_result->fetch_assoc()):
                                    $order_id = $order['id'];
                                    $status   = $order['order_status'];

                                    // Fetch items
                                    $items_stmt = $conn->prepare("SELECT meal_name, quantity FROM order_items WHERE order_id = ? AND seller_id = ?");
                                    $items_stmt->bind_param("ii", $order_id, $seller_id);
                                    $items_stmt->execute();
                                    $items_res = $items_stmt->get_result();
                                    $items_stmt->close();

                                    $items_array = [];
                                    while ($item = $items_res->fetch_assoc()) {
                                        $items_array[] = htmlspecialchars($item['meal_name']) . " (x" . intval($item['quantity']) . ")";
                                    }
                                    $items_string = implode(", ", $items_array);

                                    $status_class = match($status) {
                                        'Pending'          => 'status-pending',
                                        'Preparing'        => 'status-prepared',
                                        'Ready for Pickup' => 'status-ready',
                                        'Accepted'         => 'status-accepted',
                                        'Out for Delivery' => 'status-out',
                                        'Delivered'        => 'status-delivered',
                                        default            => ''
                                    };
                            ?>
                                <tr>
                                    <td><strong>#<?php echo $order_id; ?></strong></td>
                                    <td><?php echo $items_string; ?></td>
                                    <td><?php echo htmlspecialchars($order['delivery_address']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">

                                        <?php if ($status === 'Pending'): ?>
                                            <form action="update_order_status.php" method="POST" style="margin:0;">
                                                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                                <input type="hidden" name="status" value="Preparing">
                                                <button type="submit" class="btn-action btn-accept">Accept Order</button>
                                            </form>

                                        <?php elseif ($status === 'Preparing'): ?>
                                            <form action="update_order_status.php" method="POST" style="margin:0;">
                                                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                                <input type="hidden" name="status" value="Ready for Pickup">
                                                <button type="submit" class="btn-action btn-ready">Mark as Ready</button>
                                            </form>

                                        <?php elseif ($status === 'Ready for Pickup'): ?>
                                            <span class="status-text waiting">Waiting for Driver</span>

                                        <?php elseif ($status === 'Accepted'): ?>
                                            <span class="status-text accepted">Driver Accepted</span>

                                        <?php elseif ($status === 'Out for Delivery'): ?>
                                            <span class="status-text out">Out for Delivery</span>

                                        <?php else: ?>
                                            <span class="status-text delivered">✓ Delivered</span>
                                        <?php endif; ?>

                                    </td>
                                </tr>
                            <?php endwhile;
                            else: ?>
                                <tr class="no-orders-row">
                                    <td colspan="5" class="text-center">No active customer orders right now.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>