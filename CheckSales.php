<?php
session_start();
include 'includes/db_config.php';

// If not logged in, kick them back to login
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['seller_id'];
$username = $_SESSION['username'];

// 1. Fetch Summary Data (Total Revenue, Orders Count, Total Items Sold)
$summary_query = "SELECT 
                    SUM(oi.price * oi.quantity) as total_revenue,
                    COUNT(DISTINCT oi.order_id) as total_orders,
                    SUM(oi.quantity) as total_items_sold
                  FROM order_items oi
                  JOIN meals m ON oi.meal_name = m.meal_name
                  WHERE m.seller_id = '$user_id'";

$summary_result = $conn->query($summary_query);
$summary = $summary_result->fetch_assoc();

$total_revenue    = !empty($summary['total_revenue']) ? floatval($summary['total_revenue']) : 0.00;
$total_orders     = !empty($summary['total_orders']) ? intval($summary['total_orders']) : 0;
$total_items_sold = !empty($summary['total_items_sold']) ? intval($summary['total_items_sold']) : 0;
?>

<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Check Sales</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php 
        $current_page = 'dashboard'; 
        include 'includes/header.php'; 
    ?>

    <main>
        <section class="register-section">
            <h2>Kitchen Earnings & Sales</h2>
            <p>Track your payouts, client orders, and performance.</p>
            
            <a href="seller_dashboard.php" style="color: darkgreen; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px; margin-bottom: 20px;">Back to Dashboard</a>

            <div class="stock-flex-row" style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 20px; justify-content: center; margin-bottom: 40px;">
                
                <div style="background: #f4fbf7; border: 2px solid darkgreen; padding: 20px; border-radius: 15px; width: 220px; text-align: center;">
                    <span style="font-size: 14px; color: #666; font-weight: bold; text-transform: uppercase;">Total Revenue</span>
                    <h3 style="color: darkgreen; font-size: 28px; margin: 10px 0 0 0;">R <?php echo number_format($total_revenue, 2); ?></h3>
                </div>

                <div style="background: #f4fbf7; border: 2px solid darkgreen; padding: 20px; border-radius: 15px; width: 220px; text-align: center;">
                    <span style="font-size: 14px; color: #666; font-weight: bold; text-transform: uppercase;">Orders Handled</span>
                    <h3 style="color: darkgreen; font-size: 28px; margin: 10px 0 0 0;"><?php echo $total_orders; ?></h3>
                </div>

                <div style="background: #f4fbf7; border: 2px solid darkgreen; padding: 20px; border-radius: 15px; width: 220px; text-align: center;">
                    <span style="font-size: 14px; color: #666; font-weight: bold; text-transform: uppercase;">Portions Sold</span>
                    <h3 style="color: darkgreen; font-size: 28px; margin: 10px 0 0 0;"><?php echo $total_items_sold; ?></h3>
                </div>

            </div>

            <h3 style="text-align: left; color: darkgreen; margin-bottom: 15px;">Recent Orders</h3>
            <div style="overflow-x: auto; width: 100%;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; background: white; border: 1px solid #ddd;">
                    <thead>
                        <tr style="background-color: #f2f2f2; border-bottom: 2px solid #ddd;">
                            <th style="padding: 10px