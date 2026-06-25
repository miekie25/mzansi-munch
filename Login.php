<?php
session_start();
include 'includes/db_config.php';

// If someone is already logged in, send them to the shop
if (isset($_SESSION['user_id'])) {
    header("Location: Shop.php");
    exit();
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Login</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/forms.css">
</head>
<body>

    <?php 
        $current_page = 'login'; 
        include 'includes/header.php'; 
    ?>

    <main>
        <section class="login-section">
            <h2>Login to Your Account</h2>

            <?php if (isset($_GET['reason']) && $_GET['reason'] === 'timeout'): ?>
                <div class="error-msg" style="margin-bottom: 20px; text-align: center; color: #92400e; font-weight: bold; background: #fef3c7; padding: 10px; border-radius: 10px; border: 1px solid #fcd34d;">
                    Your session has expired. Please log in again.
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="error-msg" style="margin-bottom: 20px; text-align: center; color: red; font-weight: bold; background: pink; padding: 10px; border-radius: 10px;">
                    <?php 
                        echo $_SESSION['login_error']; 
                        unset($_SESSION['login_error']); // Clear the message so it doesn't persist
                    ?>
                </div>
            <?php endif; ?>

            <form id="loginForm" action="login_process.php" method="POST" novalidate>
                
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
                <small id="usernameError" class="error-msg"></small>

                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <i class="fa-solid fa-eye" id="togglePassword"></i>
                </div>
                <small id="passwordError" class="error-msg"></small>

                <button type="submit" class="btn-primary">Log In</button>
            </form>

            <p><a href="forgot_password.php">Forgot Password?</a></p>
            <p>Don't have an account? <a href="Register.php">Register</a></p>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/login_validation.js"></script>
</body>
</html>