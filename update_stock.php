<?php
session_start();
include 'includes/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: Login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meal_id']) && isset($_POST['new_stock'])) {
    $meal_id = intval($_POST['meal_id']);
    $new_stock = intval($_POST['new_stock']);
    $user_id = $_SESSION['user_id'];

    // Verify meal belongs to this seller
    $stmt = $conn->prepare("SELECT m.meal_id, m.meal_name FROM meals m 
                            INNER JOIN sellers s ON m.seller_id = s.seller_id 
                            WHERE m.meal_id = ? AND s.user_id = ?");
    $stmt->bind_param("ii", $meal_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $meal = $result->fetch_assoc();
    $stmt->close();

    if ($meal) {
        $stmt = $conn->prepare("UPDATE meals SET stock_quantity = ? WHERE meal_id = ?");
        $stmt->bind_param("ii", $new_stock, $meal_id);
        if ($stmt->execute()) {
            $_SESSION['stock_success'] = "Quantity updated for " . htmlspecialchars($meal['meal_name']) . "!";
        }
        $stmt->close();
    }
}

header("Location: CheckStock.php");
exit();
?>