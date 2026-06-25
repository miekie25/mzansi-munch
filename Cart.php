<?php
    include 'includes/db_config.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $total_price = 0;
    $cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    $cart_details = [];

    if (!empty($cart_items)) {
        $ids = implode(',', array_map('intval', array_keys($cart_items)));
        
        $query = "SELECT * FROM meals WHERE meal_id IN ($ids)";
        $result = $conn->query($query);
        while($item = $result->fetch_assoc()) {
            $meal_id = $item['meal_id'];
            $qty = $cart_items[$meal_id];
            $subtotal = $item['price'] * $qty;
            
            $total_price += $subtotal;
            
            $cart_details[] = [
                'id' => $meal_id,
                'name' => $item['meal_name'],
                'price' => $item['price'],
                'qty' => $qty,
                'subtotal' => $subtotal,
                'image' => $item['image_url']
            ];
        }

        $fetched_ids = array_column($cart_details, 'id');
        $missing_ids = array_diff(array_keys($cart_items), $fetched_ids);

        if (!empty($missing_ids)) {
            foreach ($missing_ids as $missing_id) {
                unset($_SESSION['cart'][$missing_id]);
            }
            $_SESSION['cart_warning'] = "Some items were removed from your basket because they are no longer available.";
        }
    }
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Munch Basket | Mzansi Munch</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/cart.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <section class="cart-container">
            <h2>My Munch Basket</h2>

            <?php if (isset($_SESSION['cart_warning'])): ?>
                <div class="cart-warning">
                    <?php echo htmlspecialchars($_SESSION['cart_warning']); unset($_SESSION['cart_warning']); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($cart_details)): ?>
                <div class="cart-empty">
                    <i class="ti ti-shopping-cart-off"></i>
                    <p>Your basket is empty! Go grab some food first.</p>
                    <a href="Shop.php" class="btn-primary">Back to Shop</a>
                </div>
            <?php else: ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Meal</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_details as $item): ?>
                            <tr>
                                <td class="cart-item-cell">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                </td>
                                <td><?php echo $item['qty']; ?></td>
                                <td>R <?php echo number_format($item['subtotal'], 2); ?></td>
                                <td>
                                    <a href="remove_item.php?id=<?php echo $item['id']; ?>" class="cart-remove">Remove</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="cart-total">
                    <h3>Total: <span>R <?php echo number_format($total_price, 2); ?></span></h3>
                    <div class="cart-actions">
                        <a href="Shop.php" class="cart-continue">Keep Shopping</a>
                        <a href="Checkout.php" class="btn-primary">Place Order</a>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>