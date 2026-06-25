<?php
session_start();
include 'includes/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Find user
    $sql = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {

        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        $token = bin2hex(random_bytes(32));

        // Delete any previous reset requests for this user
        $conn->query("DELETE FROM password_resets WHERE user_id = '$user_id'");

        // Create new reset request
        $sql = "INSERT INTO password_resets (user_id, token, created_at)
                VALUES ('$user_id', '$token', NOW())";

        $conn->query($sql);

        // Store reset link for demonstration purposes
        $_SESSION['reset_link'] = "reset_password.php?token=" . $token;
    }

    $_SESSION['message'] = "If that email exists, a password reset link has been generated.";

    header("Location: reset_confirmation.php");
    exit();
}
?>