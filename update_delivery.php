<?php
session_start();
include 'includes/db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['order_id']) || !isset($_POST['action_type'])) {
    header("Location: Login.php");
    exit();
}

$order_id = (int)$_POST['order_id'];
$action   = trim($_POST['action_type']);
$user_id  = $_SESSION['user_id'];

// Whitelist allowed actions
if (!in_array($action, ['claim', 'pickup', 'complete'])) {
    header("Location: IncomingOrders.php");
    exit();
}

// Fetch driver using prepared statement
$stmt = $conn->prepare("SELECT driver_id, vehicle_type FROM drivers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$driver = $res->fetch_assoc();
$stmt->close();

if (!$driver) {
    die("Error: No driver profile found.");
}

$driver_id    = $driver['driver_id'];
$vehicle_type = $driver['vehicle_type'];

if ($action === 'claim') {
    // Assign driver, update order status and driver availability
    $stmt = $conn->prepare("UPDATE orders SET driver_id = ?, order_status = 'Accepted' WHERE id = ? AND driver_id IS NULL");
    $stmt->bind_param("ii", $driver_id, $order_id);
    $stmt->execute();
    $stmt->close();

    // Mark driver as on delivery
    $stmt = $conn->prepare("UPDATE drivers SET availability_status = 'on_delivery' WHERE driver_id = ?");
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $stmt->close();

} elseif ($action === 'pickup') {
    $stmt = $conn->prepare("UPDATE orders SET order_status = 'Out for Delivery' WHERE id = ? AND driver_id = ?");
    $stmt->bind_param("ii", $order_id, $driver_id);
    $stmt->execute();
    $stmt->close();

} elseif ($action === 'complete') {
    // 1. Mark order as delivered and release payment to seller
    $stmt = $conn->prepare("UPDATE orders SET order_status = 'Delivered', payment_status = 'Paid out to Seller' WHERE id = ? AND driver_id = ?");
    $stmt->bind_param("ii", $order_id, $driver_id);
    $stmt->execute();
    $stmt->close();

    // 2. Mark driver as available again
    $stmt = $conn->prepare("UPDATE drivers SET availability_status = 'available' WHERE driver_id = ?");
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $stmt->close();

    // 3. Record driver earnings — consistent with checkout fees
    $earning_amount = ($vehicle_type === 'walking') ? 20.00 : 40.00;
    $stmt = $conn->prepare("INSERT INTO driver_earnings (driver_id, order_id, amount) VALUES (?, ?, ?)");
    $stmt->bind_param("iid", $driver_id, $order_id, $earning_amount);
    $stmt->execute();
    $stmt->close();

    // 4. Record seller earnings per seller in this order
    $stmt = $conn->prepare("SELECT seller_id, SUM(price * quantity) as seller_amount FROM order_items WHERE order_id = ? GROUP BY seller_id");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    while ($row = $result->fetch_assoc()) {
        $ins = $conn->prepare("INSERT INTO seller_earnings (seller_id, order_id, amount) VALUES (?, ?, ?)");
        $ins->bind_param("iid", $row['seller_id'], $order_id, $row['seller_amount']);
        $ins->execute();
        $ins->close();
    }
}

header("Location: IncomingOrders.php");
exit();
?>