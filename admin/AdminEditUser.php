<?php
require_once 'auth.php';
require_once '../includes/db_config.php';

// Validate inputs
if (!isset($_GET['id']) || !isset($_GET['role'])) {
    header("Location: AdminDashboard.php");
    exit();
}

$edit_user_id = intval($_GET['id']);
$edit_role = $_GET['role'];
$allowed_roles = ['buyer', 'seller', 'driver', 'admin'];

if (!in_array($edit_role, $allowed_roles)) {
    header("Location: AdminDashboard.php");
    exit();
}

$success_messages = [];
$errors = [];

// Fetch user data from users table
$stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $edit_user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$stmt->close();

if (!$user_data) {
    header("Location: AdminDashboard.php");
    exit();
}

// Fetch role-specific data
$role_data = [];
if ($edit_role === 'seller') {
    $stmt = $conn->prepare("SELECT seller_id, first_name, last_name, business_name, address_line1, address_line2, city, postal_code, cuisine_types FROM sellers WHERE user_id = ?");
    $stmt->bind_param("i", $edit_user_id);
    $stmt->execute();
    $role_result = $stmt->get_result();
    $role_data = $role_result->fetch_assoc() ?: [];
    $stmt->close();
} elseif ($edit_role === 'driver') {
    $stmt = $conn->prepare("SELECT driver_id, first_name, last_name, vehicle_type, city, postal_code, availability_status FROM drivers WHERE user_id = ?");
    $stmt->bind_param("i", $edit_user_id);
    $stmt->execute();
    $role_result = $stmt->get_result();
    $role_data = $role_result->fetch_assoc() ?: [];
    $stmt->close();
} elseif ($edit_role === 'buyer') {
    $stmt = $conn->prepare("SELECT buyer_id, first_name, last_name, address_line1, address_line2, city, postal_code, preferences FROM buyers WHERE user_id = ?");
    $stmt->bind_param("i", $edit_user_id);
    $stmt->execute();
    $role_result = $stmt->get_result();
    $role_data = $role_result->fetch_assoc() ?: [];
    $stmt->close();
} elseif ($edit_role === 'admin') {
    $stmt = $conn->prepare("SELECT admin_id, first_name, last_name FROM admins WHERE user_id = ?");
    $stmt->bind_param("i", $edit_user_id);
    $stmt->execute();
    $role_result = $stmt->get_result();
    $role_data = $role_result->fetch_assoc() ?: [];
    $stmt->close();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Update account details (all roles)
    if (isset($_POST['update_account'])) {
        $new_username = trim($_POST['username'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        
        if (empty($new_username) || empty($new_email)) {
            $errors[] = "Username and email are required.";
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        } else {
            // Check uniqueness excluding current user
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->bind_param("si", $new_username, $edit_user_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Username is already taken.";
            }
            $stmt->close();
            
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $new_email, $edit_user_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Email is already in use.";
            }
            $stmt->close();
            
            if (empty($errors)) {
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt->bind_param("ssi", $new_username, $new_email, $edit_user_id);
                if ($stmt->execute()) {
                    $user_data['username'] = $new_username;
                    $user_data['email'] = $new_email;
                    $success_messages[] = "Account details updated successfully.";
                } else {
                    $errors[] = "Failed to update account details.";
                }
                $stmt->close();
            }
        }
    }
    
    // Update role-specific profile
    if (isset($_POST['update_profile'])) {
        if ($edit_role === 'seller') {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $business_name = trim($_POST['business_name'] ?? '');
            $address_line1 = trim($_POST['address_line1'] ?? '');
            $address_line2 = trim($_POST['address_line2'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            $cuisine_types = trim($_POST['cuisine_types'] ?? '');
            
            if (empty($business_name)) {
                $errors[] = "Business name is required.";
            } else {
                $stmt = $conn->prepare("UPDATE sellers SET first_name = ?, last_name = ?, business_name = ?, address_line1 = ?, address_line2 = ?, city = ?, postal_code = ?, cuisine_types = ? WHERE user_id = ?");
                $stmt->bind_param("ssssssssi", $first_name, $last_name, $business_name, $address_line1, $address_line2, $city, $postal_code, $cuisine_types, $edit_user_id);
                if ($stmt->execute()) {
                    $role_data['first_name'] = $first_name;
                    $role_data['last_name'] = $last_name;
                    $role_data['business_name'] = $business_name;
                    $role_data['address_line1'] = $address_line1;
                    $role_data['address_line2'] = $address_line2;
                    $role_data['city'] = $city;
                    $role_data['postal_code'] = $postal_code;
                    $role_data['cuisine_types'] = $cuisine_types;
                    $success_messages[] = "Seller profile updated successfully.";
                } else {
                    $errors[] = "Failed to update seller profile: " . $stmt->error;
                }
                $stmt->close();
            }
            
        } elseif ($edit_role === 'driver') {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $vehicle_type = trim($_POST['vehicle_type'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            $availability_status = trim($_POST['availability_status'] ?? '');
            
            $allowed_statuses = ['available', 'unavailable', 'on_delivery'];
            
            if (empty($first_name) || empty($last_name)) {
                $errors[] = "First and last name are required.";
            } elseif (!in_array($availability_status, $allowed_statuses)) {
                $errors[] = "Invalid availability status.";
            } else {
                $stmt = $conn->prepare("UPDATE drivers SET first_name = ?, last_name = ?, vehicle_type = ?, city = ?, postal_code = ?, availability_status = ? WHERE user_id = ?");
                $stmt->bind_param("ssssssi", $first_name, $last_name, $vehicle_type, $city, $postal_code, $availability_status, $edit_user_id);
                if ($stmt->execute()) {
                    $role_data['first_name'] = $first_name;
                    $role_data['last_name'] = $last_name;
                    $role_data['vehicle_type'] = $vehicle_type;
                    $role_data['city'] = $city;
                    $role_data['postal_code'] = $postal_code;
                    $role_data['availability_status'] = $availability_status;
                    $success_messages[] = "Driver profile updated successfully.";
                } else {
                    $errors[] = "Failed to update driver profile: " . $stmt->error;
                }
                $stmt->close();
            }
            
        } elseif ($edit_role === 'buyer') {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $address_line1 = trim($_POST['address_line1'] ?? '');
            $address_line2 = trim($_POST['address_line2'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            $preferences = trim($_POST['preferences'] ?? '');
            
            $stmt = $conn->prepare("UPDATE buyers SET first_name = ?, last_name = ?, address_line1 = ?, address_line2 = ?, city = ?, postal_code = ?, preferences = ? WHERE user_id = ?");
            $stmt->bind_param("sssssssi", $first_name, $last_name, $address_line1, $address_line2, $city, $postal_code, $preferences, $edit_user_id);
            if ($stmt->execute()) {
                $role_data['first_name'] = $first_name;
                $role_data['last_name'] = $last_name;
                $role_data['address_line1'] = $address_line1;
                $role_data['address_line2'] = $address_line2;
                $role_data['city'] = $city;
                $role_data['postal_code'] = $postal_code;
                $role_data['preferences'] = $preferences;
                $success_messages[] = "Buyer profile updated successfully.";
            } else {
                $errors[] = "Failed to update buyer profile: " . $stmt->error;
            }
            $stmt->close();
            
        } elseif ($edit_role === 'admin') {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            
            $stmt = $conn->prepare("UPDATE admins SET first_name = ?, last_name = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $first_name, $last_name, $edit_user_id);
            if ($stmt->execute()) {
                $role_data['first_name'] = $first_name;
                $role_data['last_name'] = $last_name;
                $success_messages[] = "Admin profile updated successfully.";
            } else {
                $errors[] = "Failed to update admin profile: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    // Reset password (admin only feature)
    if (isset($_POST['reset_password'])) {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($new_password) || empty($confirm_password)) {
            $errors[] = "Both password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "Password must be at least 8 characters.";
        } else {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $edit_user_id);
            if ($stmt->execute()) {
                $success_messages[] = "Password has been reset successfully.";
            } else {
                $errors[] = "Failed to reset password.";
            }
            $stmt->close();
        }
    }
}

// Helper for role labels
$role_labels = [
    'buyer' => 'Buyer',
    'seller' => 'Seller / Vendor',
    'driver' => 'Delivery Driver',
    'admin' => 'Admin Staff'
];
$role_label = $role_labels[$edit_role] ?? 'User';

// Page title
$page_title = "Edit " . $role_label;

// Helper for display name
$display_name = $user_data['username'];
if ($edit_role === 'seller' && !empty($role_data['business_name'])) {
    $display_name = $role_data['business_name'];
} elseif (!empty($role_data['first_name'])) {
    $display_name = $role_data['first_name'] . ' ' . ($role_data['last_name'] ?? '');
}

// Helper for initials
$initials = strtoupper(substr($user_data['username'], 0, 2));
if (!empty($role_data['first_name']) && !empty($role_data['last_name'])) {
    $initials = strtoupper(substr($role_data['first_name'], 0, 1) . substr($role_data['last_name'], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mzansi Munch | <?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/images/favicon.png" />
    <style>
        :root {
            --primary: #f97316;
            --primary-hover: #ea6c0a;
            --bg-light: #f8f9fa;
            --border: #e5e7eb;
            --text-dark: #111827;
            --text-muted: #6b7280;
            --success: #22c55e;
            --success-bg: #ecfdf5;
            --error: #ef4444;
            --error-bg: #fef2f2;
        }

        body {
            background: var(--bg-light);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .edit-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .edit-header {
            margin-bottom: 1.5rem;
        }

        .edit-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .edit-header .breadcrumb {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .edit-header .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }

        .edit-header .breadcrumb a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 8px;
            padding: 0.85rem 1rem;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
        }

        .alert-success {
            background: var(--success-bg);
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .alert-error {
            background: var(--error-bg);
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .settings-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .settings-card h2 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .settings-card .card-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1.25rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-grid .full-width {
            grid-column: 1 / -1;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .form-group label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.6rem 0.85rem;
            font-size: 0.95rem;
            color: var(--text-dark);
            transition: border-color 0.15s, box-shadow 0.15s;
            background: #fafafa;
            width: 100%;
            box-sizing: border-box;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
            background: #fff;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn-save {
            margin-top: 1.25rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.65rem 1.4rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: background 0.15s, transform 0.1s;
        }

        .btn-save:hover {
            background: var(--primary-hover);
        }

        .btn-save:active {
            transform: scale(0.98);
        }

        .btn-secondary {
            background: #6b7280;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.65rem 1.4rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
            transition: background 0.15s;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-group {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .role-badge.buyer { background: #dbeafe; color: #1e40af; }
        .role-badge.seller { background: #fef3c7; color: #92400e; }
        .role-badge.driver { background: #d1fae5; color: #065f46; }
        .role-badge.admin { background: #f3e8ff; color: #6b21a8; }

        .user-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .user-meta .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .user-meta .info {
            display: flex;
            flex-direction: column;
        }

        .user-meta .info .name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .user-meta .info .email {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .edit-container { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="edit-header">
            <div class="breadcrumb">
                <a href="AdminDashboard.php">Dashboard</a> / 
                <a href="AdminDashboard.php?tab=<?php echo $edit_role === 'admin' ? 'admin' : ($edit_role . 's'); ?>">
                    Manage <?php echo htmlspecialchars($role_label); ?>s
                </a> / 
                Edit User
            </div>
            <h1>Edit <?php echo htmlspecialchars($role_label); ?></h1>
        </div>

        <?php if (!empty($success_messages)): ?>
            <?php foreach ($success_messages as $msg): ?>
                <div class="alert alert-success">
                    <i class="mdi mdi-check-circle"></i>
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error">
                    <i class="mdi mdi-alert-circle"></i>
                    <?php echo htmlspecialchars($err); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- User Identity Card -->
        <div class="settings-card">
            <div class="user-meta">
                <div class="avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div class="info">
                    <span class="name"><?php echo htmlspecialchars($display_name); ?></span>
                    <span class="email"><?php echo htmlspecialchars($user_data['email']); ?></span>
                </div>
                <span class="role-badge <?php echo $edit_role; ?>"><?php echo htmlspecialchars($role_label); ?></span>
            </div>
        </div>

        <!-- Account Details -->
        <div class="settings-card">
            <h2><i class="mdi mdi-account-circle"></i> Account Details</h2>
            <p class="card-desc">Update the user's login credentials and contact information.</p>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" 
                            value="<?php echo htmlspecialchars($user_data['username']); ?>" 
                            required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                            value="<?php echo htmlspecialchars($user_data['email']); ?>" 
                            required autocomplete="email">
                    </div>
                </div>
                <button type="submit" name="update_account" class="btn-save">
                    <i class="mdi mdi-content-save"></i> Save Account Details
                </button>
            </form>
        </div>

        <?php if ($edit_role === 'seller'): ?>
        <!-- Seller Profile -->
        <div class="settings-card">
            <h2><i class="mdi mdi-store"></i> Business Profile</h2>
            <p class="card-desc">Manage the seller's business information and offerings.</p>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" 
                            value="<?php echo htmlspecialchars($role_data['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" 
                            value="<?php echo htmlspecialchars($role_data['last_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="business_name">Business Name</label>
                        <input type="text" id="business_name" name="business_name" 
                            value="<?php echo htmlspecialchars($role_data['business_name'] ?? ''); ?>" 
                            required>
                    </div>
                    <div class="form-group">
                        <label for="cuisine_types">Cuisine Types</label>
                        <input type="text" id="cuisine_types" name="cuisine_types" 
                            value="<?php echo htmlspecialchars($role_data['cuisine_types'] ?? ''); ?>"
                            placeholder="e.g. South African, Grill, Vegan">
                    </div>
                    <div class="form-group">
                        <label for="address_line1">Address Line 1</label>
                        <input type="text" id="address_line1" name="address_line1" 
                            value="<?php echo htmlspecialchars($role_data['address_line1'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="address_line2">Address Line 2</label>
                        <input type="text" id="address_line2" name="address_line2" 
                            value="<?php echo htmlspecialchars($role_data['address_line2'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" 
                            value="<?php echo htmlspecialchars($role_data['city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="postal_code">Postal Code</label>
                        <input type="text" id="postal_code" name="postal_code" 
                            value="<?php echo htmlspecialchars($role_data['postal_code'] ?? ''); ?>">
                    </div>
                </div>
                <button type="submit" name="update_profile" class="btn-save">
                    <i class="mdi mdi-content-save"></i> Save Business Profile
                </button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($edit_role === 'driver'): ?>
        <!-- Driver Profile -->
        <div class="settings-card">
            <h2><i class="mdi mdi-truck-delivery"></i> Driver Profile</h2>
            <p class="card-desc">Update the driver's personal and logistics details.</p>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" 
                            value="<?php echo htmlspecialchars($role_data['first_name'] ?? ''); ?>" 
                            required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" 
                            value="<?php echo htmlspecialchars($role_data['last_name'] ?? ''); ?>" 
                            required>
                    </div>
                    <div class="form-group">
                        <label for="vehicle_type">Vehicle Type</label>
                        <select id="vehicle_type" name="vehicle_type">
                            <option value="walking" <?php echo ($role_data['vehicle_type'] ?? '') === 'walking' ? 'selected' : ''; ?>>Walking</option>
                            <option value="biking" <?php echo ($role_data['vehicle_type'] ?? '') === 'biking' ? 'selected' : ''; ?>>Biking</option>
                            <option value="driving" <?php echo ($role_data['vehicle_type'] ?? '') === 'driving' ? 'selected' : ''; ?>>Driving</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="availability_status">Availability</label>
                        <select id="availability_status" name="availability_status">
                            <option value="available" <?php echo ($role_data['availability_status'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="unavailable" <?php echo ($role_data['availability_status'] ?? '') === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                            <option value="on_delivery" <?php echo ($role_data['availability_status'] ?? '') === 'on_delivery' ? 'selected' : ''; ?>>On Delivery</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" 
                            value="<?php echo htmlspecialchars($role_data['city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="postal_code">Postal Code</label>
                        <input type="text" id="postal_code" name="postal_code" 
                            value="<?php echo htmlspecialchars($role_data['postal_code'] ?? ''); ?>">
                    </div>
                </div>
                <button type="submit" name="update_profile" class="btn-save">
                    <i class="mdi mdi-content-save"></i> Save Driver Profile
                </button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($edit_role === 'buyer'): ?>
        <!-- Buyer Profile -->
        <div class="settings-card">
            <h2><i class="mdi mdi-account"></i> Buyer Profile</h2>
            <p class="card-desc">Update the buyer's personal and delivery details.</p>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" 
                            value="<?php echo htmlspecialchars($role_data['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" 
                            value="<?php echo htmlspecialchars($role_data['last_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="address_line1">Address Line 1</label>
                        <input type="text" id="address_line1" name="address_line1" 
                            value="<?php echo htmlspecialchars($role_data['address_line1'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="address_line2">Address Line 2</label>
                        <input type="text" id="address_line2" name="address_line2" 
                            value="<?php echo htmlspecialchars($role_data['address_line2'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" 
                            value="<?php echo htmlspecialchars($role_data['city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="postal_code">Postal Code</label>
                        <input type="text" id="postal_code" name="postal_code" 
                            value="<?php echo htmlspecialchars($role_data['postal_code'] ?? ''); ?>">
                    </div>
                    <div class="form-group full-width">
                        <label for="preferences">Preferences</label>
                        <textarea id="preferences" name="preferences" 
                            placeholder="e.g. No spicy food, allergic to nuts..."><?php echo htmlspecialchars($role_data['preferences'] ?? ''); ?></textarea>
                    </div>
                </div>
                <button type="submit" name="update_profile" class="btn-save">
                    <i class="mdi mdi-content-save"></i> Save Buyer Profile
                </button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($edit_role === 'admin'): ?>
        <!-- Admin Profile -->
        <div class="settings-card">
            <h2><i class="mdi mdi-shield-account"></i> Admin Profile</h2>
            <p class="card-desc">Update the admin staff member's personal details.</p>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" 
                            value="<?php echo htmlspecialchars($role_data['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" 
                            value="<?php echo htmlspecialchars($role_data['last_name'] ?? ''); ?>">
                    </div>
                </div>
                <button type="submit" name="update_profile" class="btn-save">
                    <i class="mdi mdi-content-save"></i> Save Admin Profile
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Password Reset -->
        <div class="settings-card">
            <h2><i class="mdi mdi-lock-reset"></i> Reset Password</h2>
            <p class="card-desc">Set a new password for this user. They will be notified to change it on next login (optional: implement email notification).</p>
            <form method="POST" onsubmit="return confirm('Are you sure you want to reset this user\'s password?');">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" 
                            placeholder="Min. 8 characters" autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                            placeholder="Repeat password" autocomplete="new-password">
                    </div>
                </div>
                <button type="submit" name="reset_password" class="btn-save" style="background: #dc2626;">
                    <i class="mdi mdi-lock-reset"></i> Reset Password
                </button>
            </form>
        </div>

        <div class="btn-group">
            <a href="AdminDashboard.php?tab=<?php echo $edit_role === 'admin' ? 'admin' : ($edit_role . 's'); ?>" class="btn-secondary">
                <i class="mdi mdi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
</body>
</html>