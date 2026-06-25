<?php
session_start();
include 'includes/db_config.php';

// If someone is already logged in, no need for password reset
if (isset($_SESSION['user_id'])) {
    header("Location: Shop.php");
    exit();
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Forgot Password</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php 
        $current_page = 'forgot_password'; 
        include 'includes/header.php'; 
    ?>

    <main>
        <section class="login-section">
            <h2>Reset Password</h2>
            <p>Enter your email address to receive your secure reset link.</p>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="message" style="margin-bottom: 20px; text-align: center; color: green; font-weight: bold; background: #e8f5e9; padding: 10px; border-radius: 10px;">
                    <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']); 
                    ?>
                </div>
            <?php endif; ?>

            <form action="forgot_password_process.php" method="POST">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
                
                <button type="submit" class="btn-primary">Send Reset Link</button>
            </form>

            <p style="margin-top: 15px;"><a href="Login.php">Back to Login</a></p>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>