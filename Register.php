<?php
session_start();
include 'includes/db_config.php';
?>
<!DOCTYPE HTML>
<html>

<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Register</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/forms.css">
</head>

<body>

    <?php 
        $current_page = 'register'; 
        include 'includes/header.php'; 
    ?>

    <main>
        <section class="register-section">
            <h2>Create Your Account</h2>

            <?php if (isset($_SESSION['reg_error'])): ?>
                <div class="error-msg" style="margin-bottom: 20px; text-align: center; color: #cc0000; font-weight: bold; background: #ffe6e6; padding: 10px; border-radius: 10px;">
                    <?php 
                        echo $_SESSION['reg_error']; 
                        unset($_SESSION['reg_error']); 
                    ?>
                </div>
            <?php endif; ?>

            <form id="registerForm" action="register_process.php" method="post" novalidate>

                <fieldset>
                    <legend>User Details</legend>
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required>
                    <small id="usernameError" class="error-msg"></small>

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    <small id="emailError" class="error-msg"></small>
                </fieldset>

                <fieldset>
                    <legend>Account Type</legend>
                    <label for="role">Select Role</label>
                    <select id="role" name="role" required>
                        <option value="buyer">Buyer</option>
                        <option value="seller">Seller</option>
                        <option value="delivery">Delivery</option>
                    </select>
                </fieldset>

                <fieldset>
                    <legend>Security</legend>

                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <i class="fa-solid fa-eye" id="togglePassword"></i>
                    </div>
                    <small id="passwordRequirement" class="password-hint">
                        <span id="len">Min 6 chars</span>, 
                        <span id="cap">1 capital</span>, 
                        <span id="low">1 lowercase</span>, 
                        <span id="spec">1 special character (must be _, *, or @)</span>
                    </small>
                    <small id="passwordError" class="error-msg"></small>

                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your password" required>
                        <i class="fa-solid fa-eye" id="toggleConfirmPassword"></i>
                    </div>
                    <small id="confirmError" class="error-msg"></small>
                </fieldset>

                <button type="submit" class="btn-primary">Register</button>
            </form>

            <p>Already have an account? <a href="Login.php">Login</a></p>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/register_validation.js"></script>
</body>
</html>