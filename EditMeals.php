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

$user_id = $_SESSION['user_id'];

// Fetch seller info
$stmt = $conn->prepare("SELECT seller_id, first_name, last_name FROM sellers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$seller = $res->fetch_assoc();
$stmt->close();

if (!$seller) {
    die("Seller account not found.");
}

$seller_id = $seller['seller_id'];
$first_name = $seller['first_name'] ?? '';
$last_name = $seller['last_name'] ?? '';
$full_name = trim("$first_name $last_name") ?: 'Seller';
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) ?: strtoupper(substr($username, 0, 2));

// Validate meal_id
if (!isset($_GET['meal_id']) || !is_numeric($_GET['meal_id'])) {
    header("Location: ManageMeals.php");
    exit();
}

$meal_id = intval($_GET['meal_id']);

// Fetch meal
$stmt = $conn->prepare("SELECT * FROM meals WHERE meal_id = ? AND seller_id = ?");
$stmt->bind_param("ii", $meal_id, $seller_id);
$stmt->execute();
$meal_result = $stmt->get_result();

if ($meal_result->num_rows === 0) {
    header("Location: ManageMeals.php");
    exit();
}

$meal = $meal_result->fetch_assoc();
$stmt->close();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_meal'])) {
    $meal_name = trim($_POST['meal_name']);
    $meal_description = trim($_POST['meal_description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);

    $image_url = $meal['image_url'];

    if (!empty($_FILES['meal_image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["meal_image"]["name"]);
        $target_file = $target_dir . $file_name;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($_FILES['meal_image']['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            $error = "Only JPG, PNG, GIF, and WEBP images are allowed.";
        } elseif (move_uploaded_file($_FILES["meal_image"]["tmp_name"], $target_file)) {
            if (!empty($meal['image_url']) && file_exists($meal['image_url'])) {
                unlink($meal['image_url']);
            }
            $image_url = $target_file;
        } else {
            $error = "Sorry, there was an error uploading the new image.";
        }
    }

    if (!isset($error)) {
        $update_stmt = $conn->prepare(
            "UPDATE meals SET meal_name=?, meal_description=?, price=?, stock_quantity=?, image_url=?
             WHERE meal_id=? AND seller_id=?"
        );
        $update_stmt->bind_param(
            "ssdisii",
            $meal_name, $meal_description, $price, $stock_quantity, $image_url, $meal_id, $seller_id
        );

        if ($update_stmt->execute()) {
            $meal['meal_name'] = $meal_name;
            $meal['meal_description'] = $meal_description;
            $meal['price'] = $price;
            $meal['stock_quantity'] = $stock_quantity;
            $meal['image_url'] = $image_url;
            $success = "Meal updated successfully!";
        } else {
            $error = "Database error: " . $conn->error;
        }
        $update_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mzansi Munch | Edit Meal</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/SellerDashboard.css">
</head>
<body class="dashboard-body">
    <?php
        $current_page = 'dashboard';
        include 'includes/header.php';
    ?>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-profile">
                <div class="pfp-circle"><?php echo htmlspecialchars($initials); ?></div>
                <div class="profile-info">
                    <span class="profile-name"><?php echo htmlspecialchars($full_name); ?></span>
                    <span class="profile-handle">@<?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <span class="nav-label">Menu</span>
                <a href="Dashboard.php" class="nav-item"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
                <a href="AddMeal.php" class="nav-item"><i class="ti ti-tools-kitchen-2"></i> Sell a meal</a>
                <a href="CheckStock.php" class="nav-item"><i class="ti ti-package"></i> Check stock</a>
                <a href="ManageOrders.php" class="nav-item"><i class="ti ti-receipt"></i> Manage orders</a>
                <a href="ManageMeals.php" class="nav-item active"><i class="ti ti-edit"></i> Manage meals</a>
                <a href="Income.php" class="nav-item"><i class="ti ti-chart-bar"></i> View income</a>
                <span class="nav-label">Account</span>
                <a href="Settings.php" class="nav-item"><i class="ti ti-settings"></i> Settings</a>
            </nav>

            <div class="sidebar-footer">
                <a href="Logout.php" class="logout-link"><i class="ti ti-logout"></i> Log out</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <h1 class="greeting">Edit Meal</h1>
            <p class="greeting-sub">Update your meal details and save changes.</p>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="ti ti-circle-check"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="ti ti-alert-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="settings-card">
                <div class="form-grid">
                    <form action="EditMeals.php?meal_id=<?php echo $meal_id; ?>" method="POST" enctype="multipart/form-data" class="full-width">
                        <input type="hidden" name="update_meal" value="1">

                        <div class="form-group full-width">
                            <label for="meal_name">Meal Name</label>
                            <input type="text" id="meal_name" name="meal_name" 
                                value="<?php echo htmlspecialchars($meal['meal_name']); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="meal_description">Description</label>
                            <textarea id="meal_description" name="meal_description" required><?php echo htmlspecialchars($meal['meal_description']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="price">Price (R)</label>
                            <input type="number" id="price" name="price" step="0.01" 
                                value="<?php echo htmlspecialchars($meal['price']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="stock_quantity">Stock Quantity</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" min="0" 
                                value="<?php echo intval($meal['stock_quantity']); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label>Current Photo</label>
                            <div class="current-image">
                                <img src="<?php echo htmlspecialchars($meal['image_url']); ?>" 
                                    alt="Current meal photo"
                                    onerror="this.src='uploads/placeholder.png'">
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="meal_image">New Photo (optional)</label>
                            <input type="file" id="meal_image" name="meal_image" accept="image/*" onchange="previewImage(event)">
                            
                            <div id="preview-container" style="margin-top: 15px; display: none;">
                                <p style="font-size: 13px; color: darkgreen; font-weight: bold;">New Image Preview:</p>
                                <img id="image-preview" src="#" style="max-width: 100%; height: 200px; border-radius: 15px; border: 2px solid darkgreen; object-fit: cover;">
                            </div>
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="ti ti-device-floppy"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

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