<?php
session_start();
include 'includes/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: Login.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

$stmt = $conn->prepare("SELECT buyer_id, first_name, last_name FROM buyers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $buyer_id   = $row['buyer_id'];
    $first_name = $row['first_name'];
    $full_name  = $row['first_name'] . " " . $row['last_name'];
    $initials   = strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1));
} else {
    $buyer_id   = null;
    $first_name = $username;
    $full_name  = "Valued Customer";
    $initials   = strtoupper(substr($username, 0, 2));
}
$stmt->close();

$status_steps = ['Pending', 'Prepared', 'Ready for Pickup', 'Accepted', 'Out for Delivery', 'Delivered'];

$active_orders = [];
if ($buyer_id) {
    $stmt = $conn->prepare("SELECT id, total_amount, delivery_type, delivery_fee, order_status, created_at, driver_id 
                            FROM orders 
                            WHERE buyer_id = ? 
                            AND order_status != 'Delivered'
                            ORDER BY created_at DESC");
    $stmt->bind_param("i", $buyer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $active_orders[] = $row;
    }
    $stmt->close();
}

$past_orders = [];
if ($buyer_id) {
    $stmt = $conn->prepare("SELECT id, total_amount, delivery_type, delivery_fee, order_status, created_at 
                            FROM orders 
                            WHERE buyer_id = ? 
                            AND order_status = 'Delivered'
                            ORDER BY created_at DESC");
    $stmt->bind_param("i", $buyer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $past_orders[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | My Orders</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/BuyerDashboard.css">
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
                <a href="BuyerDashboard.php" class="nav-item active"><i class="ti ti-layout-dashboard"></i> My Orders</a>
                <a href="Shop.php" class="nav-item"><i class="ti ti-tools-kitchen-2"></i> Browse Meals</a>
                <a href="Cart.php" class="nav-item"><i class="ti ti-shopping-cart"></i> My Cart</a>
                <span class="nav-label">Account</span>
                <a href="BuyerSettings.php" class="nav-item"><i class="ti ti-settings"></i> Settings</a>
            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="logout-link"><i class="ti ti-logout"></i> Log out</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <h1 class="greeting">Hello, <?php echo htmlspecialchars($first_name); ?></h1>
            <p class="greeting-sub">Track your current orders and view your order history.</p>

            <div class="orders-section">
                <div class="section-heading">Active Orders</div>

                <?php if (!empty($active_orders)): ?>
                    <?php foreach ($active_orders as $order):
                        $items_stmt = $conn->prepare("SELECT meal_name, quantity FROM order_items WHERE order_id = ?");
                        $items_stmt->bind_param("i", $order['id']);
                        $items_stmt->execute();
                        $items_res = $items_stmt->get_result();
                        $items = [];
                        while ($item = $items_res->fetch_assoc()) {
                            $items[] = $item;
                        }
                        $items_stmt->close();

                        $current_step = array_search($order['order_status'], $status_steps);
                        if ($current_step === false) $current_step = 0;

                        $driver_name = null;
                        $driver_vehicle = null;
                        if (!empty($order['driver_id'])) {
                            $d_stmt = $conn->prepare("SELECT first_name, last_name, vehicle_type FROM drivers WHERE driver_id = ?");
                            $d_stmt->bind_param("i", $order['driver_id']);
                            $d_stmt->execute();
                            $d_res = $d_stmt->get_result();
                            if ($d_row = $d_res->fetch_assoc()) {
                                $driver_name    = $d_row['first_name'] . ' ' . $d_row['last_name'];
                                $driver_vehicle = ucfirst($d_row['vehicle_type']);
                            }
                            $d_stmt->close();
                        }
                    ?>
                        <div class="order-card">
                            <div class="order-card-header">
                                <span class="order-ref">#MZ-<?php echo $order['id']; ?></span>
                                <span class="order-date"><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></span>
                            </div>

                            <?php if ($driver_name): ?>
                                <div class="driver-banner">
                                    <i class="ti ti-bike"></i>
                                    <span>Your delivery person <strong><?php echo htmlspecialchars($driver_name); ?></strong> &mdash; <?php echo htmlspecialchars($driver_vehicle); ?> delivery</span>
                                </div>
                            <?php else: ?>
                                <div class="driver-banner pending">
                                    <i class="ti ti-clock"></i>
                                    <span>Awaiting driver assignment</span>
                                </div>
                            <?php endif; ?>

                            <ul class="order-items-list">
                                <?php foreach ($items as $item): ?>
                                    <li><?php echo htmlspecialchars($item['meal_name']); ?> &times; <?php echo $item['quantity']; ?></li>
                                <?php endforeach; ?>
                            </ul>

                            <div class="order-meta">
                                <div>Delivery Type: <span><?php echo ucfirst(htmlspecialchars($order['delivery_type'])); ?></span></div>
                                <div>Total Paid: <span>R <?php echo number_format($order['total_amount'], 2); ?></span></div>
                            </div>

                            <div class="timeline">
                                <?php foreach ($status_steps as $index => $step):
                                    if ($index < $current_step) {
                                        $dot_class = $label_class = 'completed';
                                    } elseif ($index === $current_step) {
                                        $dot_class = $label_class = 'active';
                                    } else {
                                        $dot_class = $label_class = '';
                                    }
                                ?>
                                    <div class="timeline-step">
                                        <div class="timeline-dot <?php echo $dot_class; ?>"></div>
                                        <div class="timeline-label <?php echo $label_class; ?>"><?php echo $step; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php else: ?>
                    <p class="no-orders-msg">You have no active orders right now. <a href="Shop.php">Order something delicious!</a></p>
                <?php endif; ?>
            </div>

            <div class="orders-section">
                <div class="section-heading">Order History</div>

                <?php if (!empty($past_orders)): ?>
                    <?php foreach ($past_orders as $order):
                        $items_stmt = $conn->prepare("SELECT meal_name, quantity FROM order_items WHERE order_id = ?");
                        $items_stmt->bind_param("i", $order['id']);
                        $items_stmt->execute();
                        $items_res = $items_stmt->get_result();
                        $past_items = [];
                        while ($item = $items_res->fetch_assoc()) {
                            $past_items[] = $item['quantity'] . 'x ' . $item['meal_name'];
                        }
                        $items_stmt->close();
                    ?>
                        <div class="past-order-card">
                            <div class="past-order-left">
                                <span class="past-order-ref">#MZ-<?php echo $order['id']; ?></span>
                                <span class="past-order-date"><?php echo date('d M Y', strtotime($order['created_at'])); ?></span>
                                <span class="past-order-items"><?php echo htmlspecialchars(implode(', ', $past_items)); ?></span>
                            </div>
                            <div class="past-order-right">
                                <div class="past-order-total">R <?php echo number_format($order['total_amount'], 2); ?></div>
                                <span class="past-order-status">Delivered</span>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php else: ?>
                    <p class="no-orders-msg">No past orders yet.</p>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>