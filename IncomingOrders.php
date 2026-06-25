<?php
session_start();
include 'includes/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'delivery') {
    header("Location: Login.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch driver using prepared statement
$stmt = $conn->prepare("SELECT driver_id, first_name, vehicle_type, availability_status FROM drivers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res    = $stmt->get_result();
$driver = $res->fetch_assoc();
$stmt->close();

if (!$driver) {
    die("Error: No driver profile found.");
}

$driver_id    = $driver['driver_id'];
$vehicle_type = $driver['vehicle_type'];

// Map driver vehicle_type to order delivery_type
$delivery_type_map = [
    'Foot'     => 'walking',
    'Bicycle'  => 'biking',
    'Car'      => 'driving'
];

$order_delivery_type = $delivery_type_map[$vehicle_type] ?? 'walking';
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Incoming Orders</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/DeliveryDashboard.css">
</head>
<body class="dashboard-body">
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-profile">
                <div class="pfp-circle"><?php echo strtoupper(substr($driver['first_name'], 0, 1)); ?></div>
                <div class="profile-info">
                    <span class="profile-name"><?php echo htmlspecialchars($driver['first_name']); ?></span>
                    <span class="profile-handle">@<?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <span class="nav-label">Menu</span>
                <a href="DeliveryDashboard.php" class="nav-item"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
                <a href="IncomingOrders.php" class="nav-item active"><i class="ti ti-bell"></i> Incoming orders</a>
                <a href="PastOrders.php" class="nav-item"><i class="ti ti-history"></i> Past orders</a>
                <a href="DeliveryIncome.php" class="nav-item"><i class="ti ti-chart-bar"></i> Total income</a>
                <span class="nav-label">Account</span>
                <a href="DeliverySettings.php" class="nav-item"><i class="ti ti-settings"></i> Settings</a>
            </nav>
            <div class="sidebar-footer">
                <a href="Logout.php" class="logout-link"><i class="ti ti-logout"></i> Log out</a>
            </div>
        </aside>

        <main class="dashboard-main">

            <?php
            // Active orders assigned to this driver
            $stmt = $conn->prepare("SELECT id, delivery_address, order_status FROM orders WHERE driver_id = ? AND order_status IN ('Accepted', 'Out for Delivery') ORDER BY id DESC");
            $stmt->bind_param("i", $driver_id);
            $stmt->execute();
            $active_result = $stmt->get_result();
            $stmt->close();

            if ($active_result && $active_result->num_rows > 0): ?>
                <h1 class="greeting">My Active Deliveries</h1>
                <p class="greeting-sub">Orders you have accepted — keep them moving!</p>

                <div class="table-container" style="margin-bottom: 2.5rem;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Destination</th>
                                <th>Status</th>
                                <th class="text-center">Next Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $active_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['delivery_address']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['order_status'])); ?>">
                                            <?php echo htmlspecialchars($order['order_status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($order['order_status'] === 'Accepted'): ?>
                                            <form action="update_delivery.php" method="POST">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="action_type" value="pickup">
                                                <button type="submit" class="btn-primary">Out for Delivery</button>
                                            </form>
                                        <?php elseif ($order['order_status'] === 'Out for Delivery'): ?>
                                            <form action="update_delivery.php" method="POST">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="action_type" value="complete">
                                                <button type="submit" class="btn-primary">Mark as Delivered</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <h1 class="greeting">Available Orders</h1>
            <p class="greeting-sub">These orders are ready for pickup and match your delivery type.</p>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Destination</th>
                            <th>Type</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Only show orders matching this driver's vehicle type
                        $stmt = $conn->prepare("SELECT id, delivery_address, delivery_type FROM orders WHERE order_status = 'Ready for Pickup' AND driver_id IS NULL AND delivery_type = ? ORDER BY id DESC");
                        $stmt->bind_param("s", $order_delivery_type);
                        $stmt->execute();
                        $pool_result = $stmt->get_result();
                        $stmt->close();

                        if ($pool_result && $pool_result->num_rows > 0):
                            while ($order = $pool_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['delivery_address']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($order['delivery_type'])); ?></td>
                                    <td class="text-center">
                                        <form action="update_delivery.php" method="POST">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="action_type" value="claim">
                                            <button type="submit" class="btn-primary">Accept</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr class="no-orders-row">
                                <td colspan="4" class="text-center">No orders available for your delivery type right now. Check back shortly!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>