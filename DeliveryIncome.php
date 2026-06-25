<?php
session_start();
include 'includes/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'delivery') {
    header("Location: Login.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch driver profile using prepared statement
$stmt = $conn->prepare("SELECT driver_id, first_name, last_name, vehicle_type FROM drivers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res    = $stmt->get_result();
$driver = $res->fetch_assoc();
$stmt->close();

if ($driver) {
    $driver_id    = $driver['driver_id'];
    $full_name    = $driver['first_name'] . " " . $driver['last_name'];
    $initials     = strtoupper(substr($driver['first_name'], 0, 1) . substr($driver['last_name'], 0, 1));
    $vehicle_type = $driver['vehicle_type'];
} else {
    $driver_id    = null;
    $full_name    = "Driver";
    $initials     = strtoupper(substr($username, 0, 2));
    $vehicle_type = 'walking';
}

// Fetch earnings using prepared statements
$earnings_week = $earnings_month = $earnings_total = 0;
$deliveries_total = 0;

if ($driver_id) {
    $stmt = $conn->prepare("SELECT SUM(amount) as earnings FROM driver_earnings WHERE driver_id = ? AND earned_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $earnings_week = $stmt->get_result()->fetch_assoc()['earnings'] ?? 0;
    $stmt->close();

    $stmt = $conn->prepare("SELECT SUM(amount) as earnings FROM driver_earnings WHERE driver_id = ? AND earned_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $earnings_month = $stmt->get_result()->fetch_assoc()['earnings'] ?? 0;
    $stmt->close();

    $stmt = $conn->prepare("SELECT SUM(amount) as earnings FROM driver_earnings WHERE driver_id = ?");
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $earnings_total = $stmt->get_result()->fetch_assoc()['earnings'] ?? 0;
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM driver_earnings WHERE driver_id = ?");
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $deliveries_total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
}

// Vehicle label for badge
if ($vehicle_type === 'Foot') {
    $vehicle_label = 'Walking Delivery';
    $rate_label    = 'R20 per completed order';
} elseif ($vehicle_type === 'Car') {
    $vehicle_label = 'Car Delivery';
    $rate_label    = 'R40 per completed order';
} else {
    $vehicle_label = 'Bike Delivery';
    $rate_label    = 'R40 per completed order';
}
?>

<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Delivery Income</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/DeliveryDashboard.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            text-align: center;
            border: 1px solid #e8e6e0;
        }
        .stat-card h3 {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 10px;
        }
        .stat-card .amount,
        .stat-card .deliveries {
            font-size: 1.8em;
            font-weight: 700;
            color: darkgreen;
        }
        .vehicle-badge {
            display: inline-block;
            background: #f0fff0;
            border: 1px solid darkgreen;
            color: darkgreen;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include 'includes/header.php'; ?>

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
                <a href="DeliveryDashboard.php" class="nav-item"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
                <a href="IncomingOrders.php" class="nav-item"><i class="ti ti-bell"></i> Incoming orders</a>
                <a href="PastOrders.php" class="nav-item"><i class="ti ti-history"></i> Past orders</a>
                <a href="DeliveryIncome.php" class="nav-item active"><i class="ti ti-chart-bar"></i> Total income</a>
                <span class="nav-label">Account</span>
                <a href="DeliverySettings.php" class="nav-item"><i class="ti ti-settings"></i> Settings</a>
            </nav>

            <div class="sidebar-footer">
                <a href="Logout.php" class="logout-link"><i class="ti ti-logout"></i> Log out</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <h1 class="greeting">My Earnings</h1>
            <p class="greeting-sub">Track your delivery income over time.</p>

            <div class="vehicle-badge">
                <?php echo htmlspecialchars($vehicle_label); ?> &mdash; <?php echo htmlspecialchars($rate_label); ?>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>This Week</h3>
                    <p class="amount">R <?php echo number_format($earnings_week, 2); ?></p>
                </div>
                <div class="stat-card">
                    <h3>This Month</h3>
                    <p class="amount">R <?php echo number_format($earnings_month, 2); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Lifetime</h3>
                    <p class="amount">R <?php echo number_format($earnings_total, 2); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Deliveries Completed</h3>
                    <p class="deliveries"><?php echo $deliveries_total; ?></p>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>