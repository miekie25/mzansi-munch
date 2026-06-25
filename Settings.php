<?php
session_start();
include 'includes/db_config.php';

// Authentication & Role Check
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}
if ($_SESSION['role'] !== 'seller') {
    header("Location: Shop.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

$success_messages = [];
$errors = [];

// -------------------------------------------------------------------
// Fetch current seller data
// -------------------------------------------------------------------
$seller_id  = null;
$first_name = '';
$last_name  = '';
$full_name  = 'New Seller';
$initials   = strtoupper(substr($username, 0, 2));
$business_name  = '';
$address_line1  = '';
$address_line2  = '';
$city           = '';
$postal_code    = '';
$cuisine_types  = '';
$email          = '';

// Fetch from users table
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $username = $row['username'];
    $email    = $row['email'];
}
$stmt->close();

// Fetch from sellers table
$stmt = $conn->prepare("SELECT seller_id, first_name, last_name, business_name, address_line1, address_line2, city, postal_code, cuisine_types FROM sellers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $seller_id     = $row['seller_id'];
    $first_name    = $row['first_name'];
    $last_name     = $row['last_name'];
    $full_name     = $first_name . ' ' . $last_name;
    $initials      = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
    $business_name = $row['business_name'];
    $address_line1 = $row['address_line1'];
    $address_line2 = $row['address_line2'];
    $city          = $row['city'];
    $postal_code   = $row['postal_code'];
    $cuisine_types = $row['cuisine_types'];
}
$stmt->close();

// -------------------------------------------------------------------
// Handle form submissions
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -- Profile update --
    if (isset($_POST['update_profile'])) {
        $new_first    = trim($_POST['first_name'] ?? '');
        $new_last     = trim($_POST['last_name'] ?? '');
        $new_business = trim($_POST['business_name'] ?? '');
        $new_addr1    = trim($_POST['address_line1'] ?? '');
        $new_addr2    = trim($_POST['address_line2'] ?? '');
        $new_city     = trim($_POST['city'] ?? '');
        $new_postal   = trim($_POST['postal_code'] ?? '');
        $new_cuisine  = trim($_POST['cuisine_types'] ?? '');

        if (empty($new_first) || empty($new_last)) {
            $errors[] = "First and last name are required.";
        } else {
            $stmt = $conn->prepare("UPDATE sellers SET first_name=?, last_name=?, business_name=?, address_line1=?, address_line2=?, city=?, postal_code=?, cuisine_types=? WHERE user_id=?");
            $stmt->bind_param("ssssssssi", $new_first, $new_last, $new_business, $new_addr1, $new_addr2, $new_city, $new_postal, $new_cuisine, $user_id);
            if ($stmt->execute()) {
                $success_messages[] = "Profile updated successfully.";
                // Refresh local vars
                $first_name    = $new_first;
                $last_name     = $new_last;
                $full_name     = "$new_first $new_last";
                $initials      = strtoupper(substr($new_first, 0, 1) . substr($new_last, 0, 1));
                $business_name = $new_business;
                $address_line1 = $new_addr1;
                $address_line2 = $new_addr2;
                $city          = $new_city;
                $postal_code   = $new_postal;
                $cuisine_types = $new_cuisine;
            } else {
                $errors[] = "Failed to update profile. Please try again.";
            }
            $stmt->close();
        }
    }

    // -- Account update (username only) --
    if (isset($_POST['update_account'])) {
        $new_username = trim($_POST['username'] ?? '');

        if (empty($new_username)) {
            $errors[] = "Username cannot be empty.";
        } else {
            // Check username uniqueness (excluding self)
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->bind_param("si", $new_username, $user_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "That username is already taken.";
            }
            $stmt->close();

            if (empty($errors)) {
                $stmt = $conn->prepare("UPDATE users SET username=? WHERE id=?");
                $stmt->bind_param("si", $new_username, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['username'] = $new_username;
                    $username = $new_username;
                    $success_messages[] = "Account details updated successfully.";
                } else {
                    $errors[] = "Failed to update account details. Please try again.";
                }
                $stmt->close();
            }
        }
    }

    // -- Password change --
    if (isset($_POST['update_password'])) {
        $current_pw  = $_POST['current_password'] ?? '';
        $new_pw      = $_POST['new_password'] ?? '';
        $confirm_pw  = $_POST['confirm_password'] ?? '';

        if (empty($current_pw) || empty($new_pw) || empty($confirm_pw)) {
            $errors[] = "All password fields are required.";
        } elseif ($new_pw !== $confirm_pw) {
            $errors[] = "New passwords do not match.";
        } elseif (strlen($new_pw) < 8) {
            $errors[] = "New password must be at least 8 characters.";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $user_row = $res->fetch_assoc();
            $stmt->close();

            if (!$user_row || !password_verify($current_pw, $user_row['password'])) {
                $errors[] = "Your current password is incorrect.";
            } else {
                $hashed = password_hash($new_pw, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                $stmt->bind_param("si", $hashed, $user_id);
                if ($stmt->execute()) {
                    $success_messages[] = "Password changed successfully.";
                } else {
                    $errors[] = "Failed to change password. Please try again.";
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Settings</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/settings.css">
   
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
                <a href="ManageMeals.php" class="nav-item"><i class="ti ti-edit"></i> Manage meals</a>
                <a href="Income.php" class="nav-item"><i class="ti ti-chart-bar"></i> View income</a>
                <span class="nav-label">Account</span>
                <a href="Settings.php" class="nav-item active"><i class="ti ti-settings"></i> Settings</a>
            </nav>

            <div class="sidebar-footer">
                <a href="Logout.php" class="logout-link"><i class="ti ti-logout"></i> Log out</a>
            </div>
        </aside>

        <main class="settings-main">
            <h1>Settings</h1>
            <p class="page-sub">Manage your profile, account, and security details.</p>

            <?php if (!empty($success_messages)): ?>
                <?php foreach ($success_messages as $msg): ?>
                    <div class="alert alert-success">
                        <i class="ti ti-circle-check"></i>
                        <?php echo htmlspecialchars($msg); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-error">
                        <i class="ti ti-alert-circle"></i>
                        <?php echo htmlspecialchars($err); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Profile Info -->
            <div class="settings-card">
                <h2><i class="ti ti-user"></i> Profile information</h2>
                <p class="card-desc">Update your personal and business details.</p>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First name</label>
                            <input type="text" id="first_name" name="first_name"
                                value="<?php echo htmlspecialchars($first_name); ?>"
                                placeholder="e.g. Thabo" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last name</label>
                            <input type="text" id="last_name" name="last_name"
                                value="<?php echo htmlspecialchars($last_name); ?>"
                                placeholder="e.g. Nkosi" required>
                        </div>
                        <div class="form-group full-width">
                            <label for="business_name">Business name</label>
                            <input type="text" id="business_name" name="business_name"
                                value="<?php echo htmlspecialchars($business_name); ?>"
                                placeholder="e.g. Thabo's Kitchen">
                        </div>
                        <div class="form-group full-width">
                            <label for="address_line1">Address line 1</label>
                            <input type="text" id="address_line1" name="address_line1"
                                value="<?php echo htmlspecialchars($address_line1); ?>"
                                placeholder="Street address">
                        </div>
                        <div class="form-group full-width">
                            <label for="address_line2">Address line 2 <span style="font-weight:400;color:#9ca3af">(optional)</span></label>
                            <input type="text" id="address_line2" name="address_line2"
                                value="<?php echo htmlspecialchars($address_line2); ?>"
                                placeholder="Flat, unit, suite, etc.">
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city"
                                value="<?php echo htmlspecialchars($city); ?>"
                                placeholder="e.g. Johannesburg">
                        </div>
                        <div class="form-group">
                            <label for="postal_code">Postal code</label>
                            <input type="text" id="postal_code" name="postal_code"
                                value="<?php echo htmlspecialchars($postal_code); ?>"
                                placeholder="e.g. 1861">
                        </div>
                        <div class="form-group full-width">
                            <label for="cuisine_types">Cuisine types</label>
                            <input type="text" id="cuisine_types" name="cuisine_types"
                                value="<?php echo htmlspecialchars($cuisine_types); ?>"
                                placeholder="e.g. Braai, Pap, Vetkoek">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn-save">
                        <i class="ti ti-device-floppy"></i> Save profile
                    </button>
                </form>
            </div>

            <!-- Account Details -->
            <div class="settings-card">
                <h2><i class="ti ti-at"></i> Account details</h2>
                <p class="card-desc">Change your username.</p>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username"
                                value="<?php echo htmlspecialchars($username); ?>"
                                required autocomplete="username">
                        </div>
                        <div class="form-group">
                            <label>Email address</label>
                            <input type="email" value="<?php echo htmlspecialchars($email); ?>"
                                disabled class="form-input-disabled" style="background:#f3f4f6;color:#6b7280;cursor:not-allowed;">
                            <small style="color:#9ca3af;">Email cannot be changed.</small>
                        </div>
                    </div>
                    <button type="submit" name="update_account" class="btn-save">
                        <i class="ti ti-device-floppy"></i> Save account details
                    </button>
                </form>
            </div>

            <!-- Password -->
            <div class="settings-card">
                <h2><i class="ti ti-lock"></i> Change password</h2>
                <p class="card-desc">Choose a strong password of at least 8 characters.</p>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="current_password">Current password</label>
                            <input type="password" id="current_password" name="current_password"
                                placeholder="Enter your current password" autocomplete="current-password">
                        </div>
                        <div class="form-group">
                            <label for="new_password">New password</label>
                            <input type="password" id="new_password" name="new_password"
                                placeholder="Min. 8 characters" autocomplete="new-password"
                                oninput="updateStrength(this.value)">
                            <div class="pw-strength"><div class="pw-strength-bar" id="pw-bar"></div></div>
                            <span class="pw-hint" id="pw-hint"></span>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm new password</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                placeholder="Repeat new password" autocomplete="new-password">
                        </div>
                    </div>
                    <button type="submit" name="update_password" class="btn-save">
                        <i class="ti ti-lock-check"></i> Change password
                    </button>
                </form>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        function updateStrength(pw) {
            const bar  = document.getElementById('pw-bar');
            const hint = document.getElementById('pw-hint');
            let score  = 0;
            if (pw.length >= 8)  score++;
            if (pw.length >= 12) score++;
            if (/[A-Z]/.test(pw)) score++;
            if (/[0-9]/.test(pw)) score++;
            if (/[^A-Za-z0-9]/.test(pw)) score++;

            const levels = [
                { pct: '0%',   color: '#e5e7eb', label: '' },
                { pct: '25%',  color: '#ef4444', label: 'Weak' },
                { pct: '50%',  color: '#f97316', label: 'Fair' },
                { pct: '75%',  color: '#eab308', label: 'Good' },
                { pct: '100%', color: '#22c55e', label: 'Strong' },
            ];
            const lvl = levels[Math.min(score, 4)];
            bar.style.width    = lvl.pct;
            bar.style.background = lvl.color;
            hint.textContent   = lvl.label;
            hint.style.color   = lvl.color;
        }
    </script>
</body>
</html>