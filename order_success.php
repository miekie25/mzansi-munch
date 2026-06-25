<?php
    include 'includes/db_config.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Grab the order ID out of the URL string parameter
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    // Safety fallback: If there's no valid order id, send them back to shop
    if ($order_id === 0) {
        header("Location: Shop.php");
        exit();
    }

    // 1. Fetch the main order summary including delivery info
    $order_address = "";
    $total_amount  = 0.00;
    $delivery_fee  = 0.00;
    $delivery_type = "";
    
    $stmt = $conn->prepare("SELECT delivery_address, total_amount, delivery_fee, delivery_type FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($order = $result->fetch_assoc()) {
        $order_address = $order['delivery_address'];
        $total_amount  = $order['total_amount'];
        $delivery_fee  = $order['delivery_fee'];
        $delivery_type = $order['delivery_type'];
    }
    $stmt->close();

    // Order subtotal (total minus delivery fee)
    $order_subtotal = $total_amount - $delivery_fee;

    // Delivery type label
    if ($delivery_type === 'walking') {
        $delivery_label = 'Walking Delivery';
    } elseif ($delivery_type === 'driving') {
        $delivery_label = 'Car Delivery';
    } else {
        $delivery_label = 'Bike Delivery';
    }

    // 2. Fetch the individual items belonging to this order summary
    $items_result = [];
    $items_stmt = $conn->prepare("SELECT meal_name, price, quantity FROM order_items WHERE order_id = ?");
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $res = $items_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $items_result[] = $row;
    }
    $items_stmt->close();
?>

<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmed! | Mzansi Munch</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/checkout.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main style="margin-top: 110px; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 70vh;">
        <div class="success-card">
            <h2 style="color: darkgreen; margin-bottom: 10px; font-size: 28px;">Order Placed Successfully!</h2>
            <p style="color: #555; font-size: 15px;">Thank you for munching with us. Your payment has been securely processed.</p>
            
            <div class="receipt-details">
                <div class="receipt-row">
                    <strong>Order Reference:</strong>
                    <span>#MZ-<?php echo $order_id; ?></span>
                </div>
                <div class="receipt-row">
                    <strong>Payment Method:</strong>
                    <span>Secure Card Gateway</span>
                </div>
                
                <hr style="border: 0; border-top: 1px solid #ddd; margin: 15px 0;">
                
                <div class="summary-title">Order Summary</div>
                <?php if (!empty($items_result)): ?>
                    <?php foreach ($items_result as $item): ?>
                        <div class="summary-item-line">
                            <span><?php echo htmlspecialchars($item['meal_name']); ?> <small style="color:#888;">(x<?php echo $item['quantity']; ?>)</small></span>
                            <span>R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="summary-item-line" style="color: #999; font-style: italic;">
                        <span>Items details loaded dynamically</span>
                    </div>
                <?php endif; ?>

                <hr style="border: 0; border-top: 1px solid #ddd; margin: 15px 0;">

                <!-- Subtotal, delivery fee, total breakdown -->
                <div class="summary-item-line">
                    <span>Subtotal</span>
                    <span>R <?php echo number_format($order_subtotal, 2); ?></span>
                </div>
                <div class="summary-item-line">
                    <span>Delivery Fee <small style="color: #888;">(<?php echo htmlspecialchars($delivery_label); ?>)</small></span>
                    <span>R <?php echo number_format($delivery_fee, 2); ?></span>
                </div>

                <hr style="border: 0; border-top: 1px solid #ddd; margin: 15px 0;">

                <div class="receipt-row">
                    <strong>Delivering To:</strong>
                    <span style="max-width: 65%; text-align: right; font-size: 14px; color: #666; line-height: 1.4;">
                        <?php echo htmlspecialchars($order_address); ?>
                    </span>
                </div>
                
                <div class="receipt-row" style="margin-top: 20px; font-size: 18px; border-top: 2px dashed #eee; padding-top: 15px;">
                    <strong>Total Amount Paid:</strong>
                    <strong style="color: darkgreen;">R <?php echo number_format($total_amount, 2); ?></strong>
                </div>
            </div>

            <p style="font-size: 14px; color: #777; margin-bottom: 30px; font-style: italic;">
                A local kitchen is preparing your meal. Keep an eye out for our delivery partners!
            </p>

            <a href="Shop.php" class="munch-back-btn">
                Go Back to Munch
            </a>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>