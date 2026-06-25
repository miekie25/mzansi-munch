<?php
    include 'includes/db_config.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header("Location: Login.php");
        exit();
    }

    $buyer_id = $_SESSION['user_id'];
    $cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

    if (empty($cart_items)) {
        header("Location: Shop.php");
        exit();
    }

    $address_line1 = "";
    $address_line2 = "";
    $city = "";
    $postal_code = "";

    $stmt = $conn->prepare("SELECT address_line1, address_line2, city, postal_code FROM buyers WHERE user_id = ?");
    $stmt->bind_param("i", $buyer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($buyer = $result->fetch_assoc()) {
        $address_line1 = $buyer['address_line1'];
        $address_line2 = $buyer['address_line2'];
        $city          = $buyer['city'];
        $postal_code   = $buyer['postal_code'];
    }
    $stmt->close();

    $order_subtotal = 0;
    $cart_details = [];

    if (!empty($cart_items)) {
        $ids = implode(',', array_map('intval', array_keys($cart_items)));
        $query = "SELECT meal_id, meal_name, price FROM meals WHERE meal_id IN ($ids)";
        $meal_res = $conn->query($query);
        
        while($row = $meal_res->fetch_assoc()) {
            $id = $row['meal_id'];
            $qty = $cart_items[$id];
            $subtotal = $row['price'] * $qty;
            $order_subtotal += $subtotal;
            $cart_details[] = [
                'id'       => $id,
                'name'     => $row['meal_name'],
                'price'    => $row['price'],
                'qty'      => $qty,
                'subtotal' => $subtotal
            ];
        }

        // Check for items that were in the cart but no longer exist in the database
        $fetched_ids = array_column($cart_details, 'id');
        $missing_ids = array_diff(array_keys($cart_items), $fetched_ids);

        if (!empty($missing_ids)) {
            foreach ($missing_ids as $missing_id) {
                unset($_SESSION['cart'][$missing_id]);
            }
            $_SESSION['cart_warning'] = "Some items were removed from your basket because they are no longer available. Please review your order before continuing.";
            header("Location: Cart.php");
            exit();
        }
    }

    $delivery_fees = [
        'walking' => 20,
        'driving' => 40,
        'biking'  => 40
    ];

    // Store verified subtotal in session so process_order.php can trust it
    $_SESSION['verified_subtotal'] = $order_subtotal;
?>

<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Checkout | Mzansi Munch</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/checkout.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div id="loadingScreen" class="loading-overlay">
        <div class="spinner"></div>
        <h2>Processing Secure Gateway Payment...</h2>
    </div>

    <main>
        <section class="form-container checkout-wrapper">
            <h2>Secure Checkout</h2>

            <?php if (isset($_SESSION['checkout_error'])): ?>
                <div style="background: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 15px; color: #721c24;">
                    <?php echo htmlspecialchars($_SESSION['checkout_error']); unset($_SESSION['checkout_error']); ?>
                </div>
            <?php endif; ?>
            
            <form id="checkoutForm" class="checkout-form" action="process_order.php" method="POST" onsubmit="handlePaymentSubmit(event)">
                
                <fieldset>
                    <legend>Order Summary</legend>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($cart_details as $item): ?>
                            <li style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">
                                <span><?php echo $item['qty']; ?> x <?php echo htmlspecialchars($item['name']); ?></span>
                                <span>R <?php echo number_format($item['subtotal'], 2); ?></span>
                            </li>
                        <?php endforeach; ?>
                        <li style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; color: #666;">
                            <span>Subtotal</span>
                            <span>R <span id="subtotal_display"><?php echo number_format($order_subtotal, 2); ?></span></span>
                        </li>
                        <li style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; color: #666;">
                            <span>Delivery Fee</span>
                            <span>R <span id="delivery_fee_display">20.00</span></span>
                        </li>
                        <li style="display: flex; justify-content: space-between; padding: 10px 0; font-weight: bold; font-size: 16px;">
                            <span>Total</span>
                            <span style="color: darkgreen;">R <span id="total_display"><?php echo number_format($order_subtotal + 20, 2); ?></span></span>
                        </li>
                    </ul>
                </fieldset>

                <fieldset>
                    <legend>1. Delivery Type</legend>
                    <p style="color: #666; font-size: 14px; margin-bottom: 15px;">Choose how you'd like your order delivered. Walking deliveries are for short distances within your area.</p>
                    <div class="delivery-options">
                        <div class="delivery-item">
                            <input type="radio" name="delivery_type" id="walking" value="walking" checked onchange="updateDeliveryFee(this.value)">
                            <label for="walking">Walking — R20</label>
                        </div>
                        <div class="delivery-item">
                            <input type="radio" name="delivery_type" id="driving" value="driving" onchange="updateDeliveryFee(this.value)">
                            <label for="driving">Driving — R40</label>
                        </div>
                        <div class="delivery-item">
                            <input type="radio" name="delivery_type" id="biking" value="biking" onchange="updateDeliveryFee(this.value)">
                            <label for="biking">Biking — R40</label>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>2. Delivery Details</legend>
                    <div class="form-group">
                        <label for="address_line1">Street Address / House Number</label>
                        <input type="text" id="address_line1" name="address_line1" required value="<?php echo htmlspecialchars($address_line1); ?>">
                    </div>
                    <div class="form-group">
                        <label for="city">Township / Suburb</label>
                        <input type="text" id="city" name="city" required value="<?php echo htmlspecialchars($city); ?>">
                    </div>
                    <div class="form-group">
                        <label for="postal_code">Postal Code</label>
                        <input type="text" id="postal_code" name="postal_code" required value="<?php echo htmlspecialchars($postal_code); ?>">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>3. Digital Gateway</legend>
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" required maxlength="19" placeholder="4000 1234 5678 9010" oninput="formatCardNumber(this)">
                    </div>
                    <div class="payment-row">
                        <div class="form-group">
                            <label for="card_expiry">Expiry</label>
                            <input type="text" id="card_expiry" required maxlength="5" placeholder="MM/YY" oninput="formatExpiry(this)">
                        </div>
                        <div class="form-group">
                            <label for="card_cvv">CVV</label>
                            <input type="text" id="card_cvv" required maxlength="3" placeholder="123" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>
                    <div id="card_error" style="color: red; font-size: 14px; margin-top: 8px; display: none;"></div>
                </fieldset>

                <!-- Delivery type is sent to process_order.php; total is recalculated server-side -->
                <input type="hidden" name="delivery_type_hidden" id="delivery_type_hidden" value="walking">

                <div class="checkout-summary-box">
                    <h3>Total Due: <span style="color: darkgreen;">R <span id="total_due"><?php echo number_format($order_subtotal + 20, 2); ?></span></span></h3>
                    <button type="submit" class="btn-primary">Confirm & Pay Securely</button>
                </div>
            </form>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        const deliveryFees = {
            walking: 20,
            driving: 40,
            biking: 40
        };
        const orderSubtotal = <?php echo $order_subtotal; ?>;

        function updateDeliveryFee(type) {
            const fee = deliveryFees[type];
            const total = orderSubtotal + fee;

            document.getElementById('delivery_fee_display').textContent = fee.toFixed(2);
            document.getElementById('total_display').textContent = total.toFixed(2);
            document.getElementById('total_due').textContent = total.toFixed(2);
            document.getElementById('delivery_type_hidden').value = type;
        }

        // Keep delivery_type_hidden in sync when radio buttons are clicked
        document.querySelectorAll('input[name="delivery_type"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                document.getElementById('delivery_type_hidden').value = this.value;
            });
        });

        function formatCardNumber(input) {
            let v = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let parts = v.match(/.{1,4}/g) || [];
            input.value = parts.join(' ');
        }

        function formatExpiry(input) {
            let v = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            if (v.length >= 2) {
                input.value = v.substring(0, 2) + '/' + v.substring(2, 4);
            } else {
                input.value = v;
            }
        }

        function validateCardFields() {
            const errorBox = document.getElementById('card_error');
            errorBox.style.display = 'none';
            errorBox.textContent = '';

            // Validate card number (must be 16 digits)
            const cardRaw = document.getElementById('card_number').value.replace(/\s+/g, '');
            if (!/^\d{16}$/.test(cardRaw)) {
                errorBox.textContent = 'Please enter a valid 16-digit card number.';
                errorBox.style.display = 'block';
                return false;
            }

            // Validate expiry (must be MM/YY and not in the past)
            const expiry = document.getElementById('card_expiry').value;
            if (!/^\d{2}\/\d{2}$/.test(expiry)) {
                errorBox.textContent = 'Please enter a valid expiry date in MM/YY format.';
                errorBox.style.display = 'block';
                return false;
            }

            const parts = expiry.split('/');
            const month = parseInt(parts[0], 10);
            const year  = parseInt('20' + parts[1], 10);

            if (month < 1 || month > 12) {
                errorBox.textContent = 'Please enter a valid month (01-12).';
                errorBox.style.display = 'block';
                return false;
            }

            const now = new Date();
            const expiryDate = new Date(year, month); // first day of month after expiry
            if (expiryDate <= now) {
                errorBox.textContent = 'Your card expiry date is in the past. Please use a valid card.';
                errorBox.style.display = 'block';
                return false;
            }

            // Validate CVV (must be exactly 3 digits)
            const cvv = document.getElementById('card_cvv').value;
            if (!/^\d{3}$/.test(cvv)) {
                errorBox.textContent = 'Please enter a valid 3-digit CVV.';
                errorBox.style.display = 'block';
                return false;
            }

            return true;
        }

        function handlePaymentSubmit(event) {
            event.preventDefault();
            if (!validateCardFields()) {
                return;
            }
            document.getElementById('loadingScreen').style.display = 'flex';
            setTimeout(() => {
                document.getElementById('checkoutForm').submit();
            }, 3000);
        }
    </script>
</body>
</html>