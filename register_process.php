<?php
session_start();
include 'includes/db_config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['role'])) {

    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into users table
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;

        // SESSION SETUP
        $_SESSION['user_id']  = $new_user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role']     = $role;

        // REDIRECT BY ROLE
        $redirect = match ($role) {
            'seller'   => "SellerInfo.php",
            'delivery' => "DeliveryInfo.php",
            default    => "BuyerInfo.php"
        };

        header("Location: $redirect");
        exit();
    } else {
        $_SESSION['reg_error'] = "Registration failed. Please try again.";
        header("Location: Register.php");
        exit();
    }
}
?>