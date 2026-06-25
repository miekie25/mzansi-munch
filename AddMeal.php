<?php
session_start();
include 'includes/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

if ($_SESSION['role'] !== 'seller') {
    header("Location: Shop.php");
    exit();
}

// Resolve seller_id using prepared statement
$stmt = $conn->prepare("SELECT seller_id FROM sellers WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    die("Seller account not found.");
}
$seller_id = $res['seller_id'];

$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $meal_name        = trim($_POST['meal_name'] ?? '');
    $meal_description = trim($_POST['meal_description'] ?? '');
    $price            = floatval($_POST['price'] ?? 0);
    $stock_quantity   = intval($_POST['stock_quantity'] ?? 0);

    // Server-side input validation
    if (empty($meal_name) || empty($meal_description)) {
        $error = "Meal name and description are required.";
    } elseif ($price <= 0) {
        $error = "Please enter a valid price.";
    } elseif ($stock_quantity < 0) {
        $error = "Stock quantity cannot be negative.";
    } elseif (!isset($_FILES['meal_image']) || $_FILES['meal_image']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please upload a meal image.";
    } else {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $file_type     = mime_content_type($_FILES['meal_image']['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            $error = "Only JPG, PNG, WEBP and GIF images are allowed.";
        } elseif ($_FILES['meal_image']['size'] > 5 * 1024 * 1024) {
            $error = "Image must be under 5MB.";
        } else {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $ext         = pathinfo($_FILES['meal_image']['name'], PATHINFO_EXTENSION);
            $file_name   = time() . "_" . bin2hex(random_bytes(8)) . "." . $ext;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES['meal_image']['tmp_name'], $target_file)) {
                $stmt = $conn->prepare("INSERT INTO meals (seller_id, meal_name, meal_description, price, stock_quantity, image_url) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issdis", $seller_id, $meal_name, $meal_description, $price, $stock_quantity, $target_file);
                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: ManageMeals.php");
                    exit();
                } else {
                    $error = "Database error: " . $conn->error;
                }
                $stmt->close();
            } else {
                $error = "Sorry, there was an error uploading your image.";
            }
        }
    }
}
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Add New Meal</title>
     <link rel="stylesheet" href="css/style.css">
     <link rel="stylesheet" href="css/header.css">
     <link rel="stylesheet" href="css/dashboard.css">
     <link rel="stylesheet" href="css/forms.css">
     <link rel="stylesheet" href="css/SellerDashboard.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
<a href="Dashboard.php" class="back-link">&larr; Back to Dashboard</a>
        <section class="register-section">
            <h2>Post a New Meal</h2>
            <p>Tell the community what's cooking in your kitchen today!</p>

            <?php if ($error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <form action="AddMeal.php" method="POST" enctype="multipart/form-data">
                <fieldset>
                    <legend>Meal Information</legend>
                    <label>Meal Name</label>
                    <input type="text" name="meal_name" placeholder="e.g. Traditional Beef Stew" required>

                    <label>Description</label>
                    <textarea name="meal_description" placeholder="Describe the flavors..." required></textarea>
                </fieldset>

                <fieldset>
                    <legend>Pricing, Stock and Photo</legend>
                    <label>Price (R)</label>
                    <input type="number" name="price" step="0.01" min="0.01" placeholder="0.00" required>

                    <label>Stock Quantity / Batches</label>
                    <input type="number" name="stock_quantity" min="0" placeholder="e.g. 6" required>

                    <label>Meal Photo (JPG, PNG, WEBP or GIF — max 5MB)</label>
                    <input type="file" name="meal_image" id="meal_image" accept="image/*" required onchange="previewImage(event)">

                    <div id="preview-container" style="margin-top: 15px; display: none;">
                        <p style="font-size: 13px; color: darkgreen; font-weight: bold;">Image Preview:</p>
                        <img id="image-preview" src="#" style="max-width: 100%; height: 200px; border-radius: 15px; border: 2px solid darkgreen; object-fit: cover;">
                    </div>
                </fieldset>

                <button type="submit" class="btn-save">Add to Shop</button>
            </form>
        </section>
    </main>

    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function () {
                var output = document.getElementById('image-preview');
                output.src = reader.result;
                document.getElementById('preview-container').style.display = 'block';
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>