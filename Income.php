<?php
session_start();
include 'includes/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: Login.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch seller profile
$query = "SELECT seller_id, first_name, last_name FROM sellers WHERE user_id = '$user_id'";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $seller    = $result->fetch_assoc();
    $seller_id = $seller['seller_id'];
    $full_name = $seller['first_name'] . " " . $seller['last_name'];
    $initials  = strtoupper(substr($seller['first_name'], 0, 1) . substr($seller['last_name'], 0, 1));
} else {
    $seller_id = null;
    $full_name = "New Seller";
    $initials  = strtoupper(substr($username, 0, 2));
}

// Helper function using seller_earnings table
function getEarnings($conn, $seller_id, $interval = null) {
    $sql = "SELECT SUM(amount) as earnings 
            FROM seller_earnings 
            WHERE seller_id = '$seller_id'";
    if ($interval) {
        $sql .= " AND earned_at >= DATE_SUB(NOW(), INTERVAL $interval)";
    }
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['earnings'] ?? 0;
}

// Count completed orders for this seller
$orders_total = 0;
if ($seller_id) {
    $count_result = $conn->query("SELECT COUNT(*) as total FROM seller_earnings WHERE seller_id = '$seller_id'");
    $count_row    = $count_result->fetch_assoc();
    $orders_total = $count_row['total'];
}

// Fetch metrics
$earnings_week  = $seller_id ? getEarnings($conn, $seller_id, '7 DAY')  : 0;
$earnings_month = $seller_id ? getEarnings($conn, $seller_id, '1 MONTH') : 0;
$earnings_total = $seller_id ? getEarnings($conn, $seller_id)            : 0;
?>

<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Income Report</title>
    <?php include 'includes/head.php'; ?>
     <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/SellerDashboard.css">
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
        .stat-card .orders {
            font-size: 1.8em; 
            font-weight: 700; 
            color: darkgreen; 
        }
    </style>
</head>
<body class="dashboard-body">
    <?php 
        $current_page = 'income';
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
                <a href="ManageOrders.php" class="nav-item"><i class="ti ti-receipt"></i> Manage orders</a>
                <a href="Income.php" class="nav-item active"><i class="ti ti-chart-bar"></i> View income</a>
                <span class="nav-label">Account</span>
                <a href="Settings.php" class="nav-item"><i class="ti ti-settings"></i> Settings</a>
            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="logout-link"><i class="ti ti-logout"></i> Log out</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <h1 class="greeting">Financial Overview</h1>
            <p class="greeting-sub">Track your growth and earnings.</p>

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
                    <h3>Orders Fulfilled</h3>
                    <p class="orders"><?php echo $orders_total; ?></p>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>