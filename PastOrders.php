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
$stmt = $conn->prepare("SELECT driver_id, first_name FROM drivers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res    = $stmt->get_result();
$driver = $res->fetch_assoc();
$stmt->close();

if (!$driver) {
    die("Error: No driver profile found.");
}

$driver_id = $driver['driver_id'];

// Fetch past orders using prepared statement
$stmt = $conn->prepare("SELECT id, delivery_address, delivery_type FROM orders WHERE driver_id = ? AND order_status = 'Delivered' ORDER BY id DESC");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$past_result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Past Orders</title>
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
                <a href="IncomingOrders.php" class="nav-item"><i class="ti ti-bell"></i> Incoming orders</a>
                <a href="PastOrders.php" class="nav-item active"><i class="ti ti-history"></i> Past orders</a>
                <a href="DeliveryIncome.php" class="nav-item"><i class="ti ti-chart-bar"></i> Total income</a>
                <span class="nav-label">Account</span>
                <a href="DeliverySettings.php" class="nav-item"><i class="ti ti-settings"></i> Settings</a>
            </nav>
            <div class="sidebar-footer">
                <a href="Logout.php" class="logout-link"><i class="ti ti-logout"></i> Log out</a>
            </div>
        </aside>
        <main class="dashboard-main">
            <h1 class="greeting">Past Orders</h1>
            <p class="greeting-sub">View your completed delivery history.</p>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Destination</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($past_result && $past_result->num_rows > 0):
                            while ($order = $past_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['delivery_address']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($order['delivery_type'])); ?></td>
                                    <td><span style="color: darkgreen; font-weight: bold;">Completed</span></td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr class="no-orders-row">
                                <td colspan="4">You haven't completed any deliveries yet. Get started!</td>
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