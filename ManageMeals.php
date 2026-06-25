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

// Resolve seller info
$stmt = $conn->prepare("SELECT seller_id, first_name, last_name FROM sellers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    die("Seller account not found.");
}

$seller_id = $res['seller_id'];
$first_name = $res['first_name'] ?? $username;
$last_name = $res['last_name'] ?? '';
$full_name = trim("$first_name $last_name") ?: 'Seller';
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) ?: strtoupper(substr($username, 0, 2));

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_meal_id'])) {
    $delete_id = intval($_POST['delete_meal_id']);

    $stmt = $conn->prepare("SELECT meal_id, image_url FROM meals WHERE meal_id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $delete_id, $seller_id);
    $stmt->execute();
    $meal = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($meal) {
        if (!empty($meal['image_url']) && file_exists($meal['image_url'])) {
            unlink($meal['image_url']);
        }
        $stmt = $conn->prepare("DELETE FROM meals WHERE meal_id = ? AND seller_id = ?");
        $stmt->bind_param("ii", $delete_id, $seller_id);
        $stmt->execute();
        $stmt->close();
        $success = "Meal deleted successfully.";
    } else {
        $error = "Could not delete — meal not found or not yours.";
    }
}

// Fetch all meals
$stmt = $conn->prepare("SELECT meal_id, meal_name, price, stock_quantity, image_url FROM meals WHERE seller_id = ? ORDER BY meal_id DESC");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$meals_result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mzansi Munch | Manage Meals</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/dashboard.css">
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
                <a href="SellerDashboard.php" class="nav-item"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
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
            <h1 class="greeting">Manage Meals</h1>
            <p class="greeting-sub">View, edit, and remove your listed meals.</p>

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

            <div class="section-card">
                <div class="manage-header">
                    <h3><i class="ti ti-tools-kitchen-2"></i> My Meals</h3>
                    <a href="AddMeal.php" class="btn-add">+ Add New Meal</a>
                </div>

                <?php if ($meals_result && $meals_result->num_rows > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Meal Name</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($meal = $meals_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($meal['image_url']); ?>"
                                             alt="<?php echo htmlspecialchars($meal['meal_name']); ?>"
                                             class="meal-thumb"
                                             onerror="this.src='uploads/placeholder.png'">
                                    </td>
                                    <td><?php echo htmlspecialchars($meal['meal_name']); ?></td>
                                    <td>R <?php echo number_format($meal['price'], 2); ?></td>
                                    <td><?php echo intval($meal['stock_quantity']); ?></td>
                                    <td class="text-center">
                                        <a href="EditMeals.php?meal_id=<?php echo $meal['meal_id']; ?>" class="btn-edit">Edit</a>
                                        <button class="btn-delete"
                                            onclick="confirmDelete(<?php echo $meal['meal_id']; ?>, '<?php echo htmlspecialchars(addslashes($meal['meal_name'])); ?>')">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="ti ti-tools-kitchen-2"></i>
                        <p>You haven't added any meals yet.</p>
                        <a href="AddMeal.php" class="btn-primary">Add your first meal</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Confirm Delete Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <h3>Delete Meal?</h3>
            <p id="modalMealName"></p>
            <p style="color:#666; font-size:13px;">This cannot be undone.</p>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal()">Cancel</button>
                <form method="POST" action="ManageMeals.php" id="deleteForm">
                    <input type="hidden" name="delete_meal_id" id="deleteMealId">
                    <button type="submit" class="btn-delete">Yes, Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(mealId, mealName) {
            document.getElementById('deleteMealId').value = mealId;
            document.getElementById('modalMealName').textContent = 'Are you sure you want to delete "' + mealName + '"?';
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>