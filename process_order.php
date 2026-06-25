<?php
include 'includes/db_config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Guard Rail: Ensure user is logged in and cart is not empty
if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    header("Location: Shop.php");
    exit();
}

$user_id    = $_SESSION['user_id'];
$cart_items = $_SESSION['cart'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Resolve the real buyer_id from the buyers table
    $stmt = $conn->prepare("SELECT buyer_id FROM buyers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $buyer_row = $res->fetch_assoc();
    $stmt->close();

    if (!$buyer_row) {
        echo "Could not find your buyer profile. Please contact support.";
        exit();
    }

    $buyer_id = $buyer_row['buyer_id'];

    // Sanitize address
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $postal_code   = trim($_POST['postal_code'] ?? '');

    $full_address = $address_line1;
    if (!empty($address_line2)) {
        $full_address .= ", " . $address_line2;
    }
    $full_address .= ", " . $city . ", " . $postal_code;

    // Validate and calculate delivery fee server-side — never trust posted amounts
    $allowed_delivery_types = [
        'walking' => 20,
        'driving' => 40,
        'biking'  => 40
    ];

    $delivery_type = $_POST['delivery_type_hidden'] ?? 'walking';
    if (!array_key_exists($delivery_type, $allowed_delivery_types)) {
        $delivery_type = 'walking';
    }
    $delivery_fee = $allowed_delivery_types[$delivery_type];

    // Recalculate order subtotal server-side using verified session value
    $order_subtotal = isset($_SESSION['verified_subtotal']) ? floatval($_SESSION['verified_subtotal']) : 0;

    if ($order_subtotal <= 0) {
        $_SESSION['checkout_error'] = "Your order total could not be verified. Please try again.";
        header("Location: Checkout.php");
        exit();
    }

    $total_amount   = $order_subtotal + $delivery_fee;
    $payment_status = "Secured by Platform";
    $order_status   = "Pending";
    $driver_id      = null;

    $conn->begin_transaction();

    try {

        // Validate stock and check all meals still exist before inserting anything
        $unavailable_meals = [];
        $insufficient_stock = [];

        foreach ($cart_items as $meal_id => $quantity) {
            $check_stmt = $conn->prepare("SELECT meal_name, stock_quantity FROM meals WHERE meal_id = ?");
            $check_stmt->bind_param("i", $meal_id);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();
            $check_meal = $check_res->fetch_assoc();
            $check_stmt->close();

            if (!$check_meal) {
                $unavailable_meals[] = $meal_id;
            } elseif ($check_meal['stock_quantity'] < $quantity) {
                $insufficient_stock[] = $check_meal['meal_name'];
            }
        }

        if (!empty($unavailable_meals)) {
            // Remove unavailable items from session cart and redirect
            foreach ($unavailable_meals as $missing_id) {
                unset($_SESSION['cart'][$missing_id]);
            }
            $conn->rollback();
            $_SESSION['cart_warning'] = "Some items were removed from your basket because they are no longer available. Please review your order.";
            header("Location: Cart.php");
            exit();
        }

        if (!empty($insufficient_stock)) {
            $conn->rollback();
            $names = implode(', ', $insufficient_stock);
            $_SESSION['checkout_error'] = "Sorry, there is not enough stock for: " . htmlspecialchars($names) . ". Please update your basket.";
            header("Location: Checkout.php");
            exit();
        }

        // INSERT INTO ORDERS
        $order_stmt = $conn->prepare("INSERT INTO orders (buyer_id, driver_id, delivery_address, total_amount, delivery_fee, delivery_type, payment_status, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $order_stmt->bind_param("iisddsss", $buyer_id, $driver_id, $full_address, $total_amount, $delivery_fee, $delivery_type, $payment_status, $order_status);
        $order_stmt->execute();
        $order_id = $conn->insert_id;
        $order_stmt->close();

        // INSERT INTO ORDER_ITEMS and deduct stock
        foreach ($cart_items as $meal_id => $quantity) {
            $meal_stmt = $conn->prepare("SELECT meal_name, price, seller_id FROM meals WHERE meal_id = ?");
            $meal_stmt->bind_param("i", $meal_id);
            $meal_stmt->execute();
            $meal_res = $meal_stmt->get_result();

            if ($meal = $meal_res->fetch_assoc()) {
                $meal_seller_id = $meal['seller_id'];

                $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, meal_name, price, quantity, seller_id) VALUES (?, ?, ?, ?, ?)");
                $item_stmt->bind_param("isdii", $order_id, $meal['meal_name'], $meal['price'], $quantity, $meal_seller_id);
                $item_stmt->execute();
                $item_stmt->close();

                // Deduct stock
                $stock_stmt = $conn->prepare("UPDATE meals SET stock_quantity = stock_quantity - ? WHERE meal_id = ?");
                $stock_stmt->bind_param("ii", $quantity, $meal_id);
                $stock_stmt->execute();
                $stock_stmt->close();
            }

            $meal_stmt->close();
        }

        $conn->commit();

        // Clear cart and verified subtotal from session
        unset($_SESSION['cart']);
        unset($_SESSION['verified_subtotal']);

        header("Location: order_success.php?order_id=" . $order_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "Oops! Something went wrong: " . $e->getMessage();
    }

} else {
    header("Location: Shop.php");
    exit();
}
?>