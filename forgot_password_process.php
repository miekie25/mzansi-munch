<?php
session_start();
include 'includes/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $_SESSION['message'] = "Please enter an email address.";
        header("Location: forgot_password.php");
        exit();
    }

    // Find user with prepared statement (secure)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        $token = bin2hex(random_bytes(32));

        // Delete previous reset requests (secure)
        $del_stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $del_stmt->bind_param("i", $user_id);
        $del_stmt->execute();
        $del_stmt->close();

        // Create new reset request (secure)
        $insert_stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, created_at) VALUES (?, ?, NOW())");
        $insert_stmt->bind_param("is", $user_id, $token);
        $insert_stmt->execute();
        $insert_stmt->close();

        // Store reset link for demonstration
        $_SESSION['reset_link'] = "reset_password.php?token=" . $token;
        $_SESSION['message'] = "Password reset link generated. Check your email (demo: link shown below).";
        $_SESSION['message_type'] = "success";

    } else {
        // Email not found — block them
        $_SESSION['message'] = "No account found with that email address.";
        $_SESSION['message_type'] = "error";
    }

    $stmt->close();

    header("Location: reset_confirmation.php");
    exit();
}
?>