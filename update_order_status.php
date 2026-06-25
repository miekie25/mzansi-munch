<?php
session_start();
include 'includes/db_config.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Only sellers and drivers may update order status
$role = $_SESSION['role'];
if ($role !== 'seller' && $role !== 'delivery') {
    header("Location: Shop.php");
    exit();
}

if (isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id   = intval($_POST['order_id']);
    $new_status = trim($_POST['status']);

    // Whitelist allowed statuses per role — prevents arbitrary status injection
    $allowed = [
        'seller'   => ['Preparing', 'Ready for Pickup'],
        'delivery' => ['Accepted', 'Out for Delivery', 'Delivered'],
    ];

    if (!in_array($new_status, $allowed[$role])) {
        // Status not permitted for this role — silently redirect
        header("Location: ManageOrders.php");
        exit();
    }

    if ($role === 'seller') {
        // Verify the order actually contains this seller's items before updating
        $stmt = $conn->prepare("SELECT seller_id FROM sellers WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$res) {
            header("Location: ManageOrders.php");
            exit();
        }

        $seller_id = $res['seller_id'];

        // Check the order belongs to this seller
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM order_items WHERE order_id = ? AND seller_id = ?");
        $stmt->bind_param("ii", $order_id, $seller_id);
        $stmt->execute();
        $check = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$check || $check['cnt'] == 0) {
            header("Location: ManageOrders.php");
            exit();
        }

        $redirect = "ManageOrders.php";

    } else {
        // Driver: verify this order is assigned to them
        $stmt = $conn->prepare("SELECT driver_id FROM drivers WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$res) {
            header("Location: ManageOrders.php");
            exit();
        }

        $redirect = "DriverOrders.php";
    }

    // Safe to update
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    if (!$stmt->execute()) {
        error_log("Order status update failed: " . $conn->error);
    }
    $stmt->close();

    header("Location: " . $redirect);
    exit();
}

header("Location: ManageOrders.php");
exit();
?>