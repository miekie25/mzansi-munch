<?php
session_start();
include 'includes/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'delivery') {
    header("Location: Login.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle availability toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_availability'])) {
    $new_status = $_POST['new_status'];
    $allowed = ['available', 'offline'];
    if (in_array($new_status, $allowed)) {
        $stmt = $conn->prepare("UPDATE drivers SET availability_status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_status, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: DeliveryDashboard.php");
    exit();
}

// Fetch driver details
$stmt = $conn->prepare("SELECT driver_id, first_name, last_name, vehicle_type, availability_status FROM drivers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res    = $stmt->get_result();
$driver = $res->fetch_assoc();
$stmt->close();

if (!$driver) {
    header("Location: Login.php?error=no_profile");
    exit();
}

$driver_id = $driver['driver_id'];
$full_name = $driver['first_name'] . ' ' . $driver['last_name'];
$initials  = strtoupper(substr($driver['first_name'], 0, 1) . substr($driver['last_name'], 0, 1));

// Today's earnings
$stmt = $conn->prepare("SELECT SUM(amount) as today FROM driver_earnings WHERE driver_id = ? AND DATE(earned_at) = CURDATE()");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$earnings_today = $row['today'] ?? 0;

// Total earnings
$stmt = $conn->prepare("SELECT SUM(amount) as total FROM driver_earnings WHERE driver_id = ?");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$earnings_total = $row['total'] ?? 0;

// Total deliveries completed
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE driver_id = ? AND order_status = 'Delivered'");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$deliveries_total = $row['total'] ?? 0;

// Active order count
$stmt = $conn->prepare("SELECT COUNT(*) as active FROM orders WHERE driver_id = ? AND order_status IN ('Accepted', 'Out for Delivery')");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$active_count = $row['active'] ?? 0;

// Recent deliveries (last 3)
$recent_deliveries = [];
$stmt = $conn->prepare("SELECT id, order_status, total_amount, delivery_address, created_at FROM orders WHERE driver_id = ? ORDER BY created_at DESC LIMIT 3");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$recent_result = $stmt->get_result();
while ($row = $recent_result->fetch_assoc()) {
    $recent_deliveries[] = $row;
}
$stmt->close();

// Availability state
$avail_status = $driver['availability_status'];
$is_available = ($avail_status === 'available');

$avail_labels = [
    'available' => 'Available',
    'busy'      => 'Busy',
    'offline'   => 'Offline'
];
$avail_classes = [
    'available' => 'avail-available',
    'busy'      => 'avail-busy',
    'offline'   => 'avail-offline'
];
$avail_label = $avail_labels[$avail_status] ?? 'Unknown';
$avail_class = $avail_classes[$avail_status] ?? 'avail-offline';
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Driver Dashboard</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/DeliveryDashboard.css">
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
                <a href="DeliveryDashboard.php" class="nav-item active"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
                <a href="IncomingOrders.php" class="nav-item"><i class="ti ti-bell"></i> Incoming orders</a>
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
            <h1 class="greeting">Hello, <?php echo htmlspecialchars($driver['first_name']); ?></h1>
            <p class="greeting-sub">Here is your delivery overview for today.</p>

            <?php if ($avail_status === 'busy'): ?>
                <div class="availability-toggle">
                    <span class="availability-badge avail-busy">Busy</span>
                    <span class="toggle-note">Complete your current delivery to change status</span>
                </div>
            <?php else: ?>
                <div class="availability-toggle">
                    <form method="POST" action="DeliveryDashboard.php" style="display: flex; align-items: center; gap: 0.75rem;">
                        <input type="hidden" name="toggle_availability" value="1">
                        <input type="hidden" name="new_status" value="<?php echo $is_available ? 'offline' : 'available'; ?>">
                        <label class="switch">
                            <input type="checkbox" <?php echo $is_available ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span class="slider"></span>
                        </label>
                        <span class="toggle-label <?php echo $is_available ? 'available' : 'offline'; ?>">
                            <?php echo $avail_label; ?>
                        </span>
                    </form>
                </div>
            <?php endif; ?>

            <div class="stats-row">
                <div class="stat-card">
                    <h3>Today's Earnings</h3>
                    <div class="stat-value success">R <?php echo number_format($earnings_today, 2); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Earnings</h3>
                    <div class="stat-value success">R <?php echo number_format($earnings_total, 2); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Deliveries Done</h3>
                    <div class="stat-value"><?php echo $deliveries_total; ?></div>
                </div>
                <div class="stat-card">
                    <a href="IncomingOrders.php" class="stat-card-link">
                        <h3>Active Orders</h3>
                        <div class="stat-value <?php echo $active_count > 0 ? 'warning' : ''; ?>">
                            <?php echo $active_count; ?>
                        </div>
                    </a>
                </div>
            </div>

            <div class="section-card">
                <h3><i class="ti ti-history"></i> Recent Deliveries</h3>
                <?php if (!empty($recent_deliveries)): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_deliveries as $order):
                                $status_lower = strtolower($order['order_status']);
                                $status_class = 'status-pending';
                                if ($status_lower === 'delivered') $status_class = 'status-delivered';
                                elseif ($status_lower === 'out for delivery') $status_class = 'status-out';
                                elseif ($status_lower === 'accepted') $status_class = 'status-accepted';
                            ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['delivery_address'] ?? 'N/A'); ?></td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                                <td>R <?php echo number_format($order['total_amount'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="PastOrders.php" class="view-all-link"><i class="ti ti-arrow-right"></i> View all orders</a>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="ti ti-motorbike"></i>
                        No deliveries yet. Check incoming orders!
                    </div>
                <?php endif; ?>
            </div>

            <h3 class="quick-links-heading"><i class="ti ti-bolt"></i> Quick Links</h3>
            <div class="dash-actions">
                <a href="IncomingOrders.php" class="dash-card">
                    <i class="ti ti-bell"></i>
                    <span>Incoming orders</span>
                </a>
                <a href="PastOrders.php" class="dash-card">
                    <i class="ti ti-history"></i>
                    <span>Past orders</span>
                </a>
                <a href="DeliveryIncome.php" class="dash-card">
                    <i class="ti ti-chart-bar"></i>
                    <span>Total income</span>
                </a>
                <a href="DeliverySettings.php" class="dash-card">
                    <i class="ti ti-settings"></i>
                    <span>Settings</span>
                </a>
            </div>
        </main>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>