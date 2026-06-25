<?php
session_start();
include 'includes/db_config.php';

// Ensure cart exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get values from URL
$meal_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$qty = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;

if ($meal_id === 0) {
    header("Location: Shop.php");
    exit();
}

// Verify stock from DB
$stmt = $conn->prepare("SELECT stock_quantity FROM meals WHERE meal_id = ?");
$stmt->bind_param("i", $meal_id);
$stmt->execute();
$result = $stmt->get_result();
$meal = $result->fetch_assoc();

if ($meal && $meal['stock_quantity'] >= $qty) {
    // Add or Update quantity in Session
    if (isset($_SESSION['cart'][$meal_id])) {
        $_SESSION['cart'][$meal_id] += $qty;
    } else {
        $_SESSION['cart'][$meal_id] = $qty;
    }
} else {
    // If stock check fails, redirect with error
    header("Location: Shop.php?error=out_of_stock");
    exit();
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "Shop.php"));
exit();
?>