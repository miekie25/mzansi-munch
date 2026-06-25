<?php
session_start();
include 'includes/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'delivery') {
    header("Location: Login.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

$success_messages = [];
$errors = [];

$first_name          = '';
$last_name           = '';
$vehicle_type        = '';
$city                = '';
$postal_code         = '';
$availability_status = '';
$email               = '';

$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $username = $row['username'];
    $email    = $row['email'];
}
$stmt->close();

$stmt = $conn->prepare("SELECT first_name, last_name, vehicle_type, city, postal_code, availability_status FROM drivers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $first_name          = $row['first_name'];
    $last_name           = $row['last_name'];
    $vehicle_type        = $row['vehicle_type'];
    $city                = $row['city'];
    $postal_code         = $row['postal_code'];
    $availability_status = $row['availability_status'];
}
$stmt->close();

$full_name = trim("$first_name $last_name") ?: 'Driver';
$initials  = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) ?: strtoupper(substr($username, 0, 2));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_profile'])) {
        $new_first   = trim($_POST['first_name'] ?? '');
        $new_last    = trim($_POST['last_name'] ?? '');
        $new_vehicle = trim($_POST['vehicle_type'] ?? '');
        $new_city    = trim($_POST['city'] ?? '');
        $new_postal  = trim($_POST['postal_code'] ?? '');
        $new_avail   = trim($_POST['availability_status'] ?? '');

        $allowed_vehicles = ['Foot', 'Bicycle', 'Car'];
        $allowed_statuses = ['available', 'busy', 'offline'];

        if (empty($new_first) || empty($new_last)) {
            $errors[] = "First and last name are required.";
        } elseif (!in_array($new_vehicle, $allowed_vehicles)) {
            $errors[] = "Invalid delivery type selected.";
        } elseif (!in_array($new_avail, $allowed_statuses)) {
            $errors[] = "Invalid availability status selected.";
        } else {
            $stmt = $conn->prepare("UPDATE drivers SET first_name=?, last_name=?, vehicle_type=?, city=?, postal_code=?, availability_status=? WHERE user_id=?");
            $stmt->bind_param("ssssssi", $new_first, $new_last, $new_vehicle, $new_city, $new_postal, $new_avail, $user_id);
            if ($stmt->execute()) {
                $success_messages[] = "Profile updated successfully.";
                $first_name          = $new_first;
                $last_name           = $new_last;
                $vehicle_type        = $new_vehicle;
                $city                = $new_city;
                $postal_code         = $new_postal;
                $availability_status = $new_avail;
                $full_name = trim("$new_first $new_last");
                $initials  = strtoupper(substr($new_first, 0, 1) . substr($new_last, 0, 1));
            } else {
                $errors[] = "Failed to update profile. Please try again.";
            }
            $stmt->close();
        }
    }

    if (isset($_POST['update_account'])) {
        $new_username = trim($_POST['username'] ?? '');

        if (empty($new_username)) {
            $errors[] = "Username cannot be empty.";
        } else {
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

    if (isset($_POST['update_password'])) {
        $current_pw = $_POST['current_password'] ?? '';
        $new_pw     = $_POST['new_password'] ?? '';
        $confirm_pw = $_POST['confirm_password'] ?? '';

        if (empty($current_pw) || empty($new_pw) || empty($confirm_pw)) {
            $errors[] = "All password fields are required.";
        } elseif ($new_pw !== $confirm_pw) {
            $errors[] = "New passwords do not match.";
        } elseif (strlen($new_pw) < 8) {
            $errors[] = "New password must be at least 8 characters.";
        } else {
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
    <title>Mzansi Munch | Delivery Settings</title>
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
                <a href="DeliveryDashboard.php" class="nav-item"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
                <a href="IncomingOrders.php" class="nav-item"><i class="ti ti-bell"></i> Incoming orders</a>
                <a href="PastOrders.php" class="nav-item"><i class="ti ti-history"></i> Past orders</a>
                <a href="DeliveryIncome.php" class="nav-item"><i class="ti ti-chart-bar"></i> Total income</a>
                <span class="nav-label">Account</span>
                <a href="DeliverySettings.php" class="nav-item active"><i class="ti ti-settings"></i> Settings</a>
            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="Logout-link"><i class="ti ti-logout"></i> Log out</a>
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

            <div class="settings-card">
                <h2><i class="ti ti-user"></i> Profile information</h2>
                <p class="card-desc">Update your personal and delivery details.</p>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First name</label>
                            <input type="text" id="first_name" name="first_name"
                                value="<?php echo htmlspecialchars($first_name); ?>"
                                placeholder="e.g. Sipho" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last name</label>
                            <input type="text" id="last_name" name="last_name"
                                value="<?php echo htmlspecialchars($last_name); ?>"
                                placeholder="e.g. Dlamini" required>
                        </div>
                        <div class="form-group">
                            <label for="vehicle_type">Delivery type</label>
                            <select id="vehicle_type" name="vehicle_type">
                                <option value="Foot"    <?php echo $vehicle_type === 'Foot'    ? 'selected' : ''; ?>>Walking</option>
                                <option value="Bicycle" <?php echo $vehicle_type === 'Bicycle' ? 'selected' : ''; ?>>Bicycle / Scooter</option>
                                <option value="Car"     <?php echo $vehicle_type === 'Car'     ? 'selected' : ''; ?>>Car</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="availability_status">Availability</label>
                            <select id="availability_status" name="availability_status">
                                <option value="available" <?php echo $availability_status === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="busy"      <?php echo $availability_status === 'busy'      ? 'selected' : ''; ?>>Busy</option>
                                <option value="offline"   <?php echo $availability_status === 'offline'   ? 'selected' : ''; ?>>Offline</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city"
                                value="<?php echo htmlspecialchars($city); ?>"
                                placeholder="e.g. Soweto">
                        </div>
                        <div class="form-group">
                            <label for="postal_code">Postal code</label>
                            <input type="text" id="postal_code" name="postal_code"
                                value="<?php echo htmlspecialchars($postal_code); ?>"
                                placeholder="e.g. 1804">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn-save">
                        <i class="ti ti-device-floppy"></i> Save profile
                    </button>
                </form>
            </div>

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
            if (pw.length >= 8)               score++;
            if (pw.length >= 12)              score++;
            if (/[A-Z]/.test(pw))             score++;
            if (/[0-9]/.test(pw))             score++;
            if (/[^A-Za-z0-9]/.test(pw))      score++;

            const levels = [
                { pct: '0%',   color: '#e5e7eb', label: '' },
                { pct: '25%',  color: '#ef4444', label: 'Weak' },
                { pct: '50%',  color: '#f97316', label: 'Fair' },
                { pct: '75%',  color: '#eab308', label: 'Good' },
                { pct: '100%', color: '#22c55e', label: 'Strong' },
            ];
            const lvl = levels[Math.min(score, 4)];
            bar.style.width      = lvl.pct;
            bar.style.background = lvl.color;
            hint.textContent     = lvl.label;
            hint.style.color     = lvl.color;
        }
    </script>
</body>
</html>