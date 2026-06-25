<?php
session_start();
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Reset Link</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/forms.css">
</head>
<body>
    <main>
        <section class="login-section">
            <h2>Password Reset</h2>

            <?php if (isset($_SESSION['message_type']) && $_SESSION['message_type'] === 'error'): ?>
                
                <!-- Error: Email not found -->
                <div style="background: #fee2e2; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #ef4444; color: #991b1b;">
                    <?php echo htmlspecialchars($_SESSION['message']); ?>
                </div>
                <a href="forgot_password.php" class="btn-primary">Try Again</a>

            <?php elseif (isset($_SESSION['reset_link'])): ?>
                
                <!-- Success: Reset link generated -->
                <p>Success! Please use the link below to reset your password:</p>
                
                <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; margin: 25px 0; border: 2px solid darkgreen;">
                    <a href="<?php echo htmlspecialchars($_SESSION['reset_link']); ?>" style="color: darkgreen; font-weight: bold; word-break: break-all;">
                        Click here to reset your password
                    </a>
                </div>
                
                <p style="font-size: 0.9em; color: #666;">This link is valid for 1 hour.</p>

            <?php else: ?>
                
                <!-- No active request -->
                <p>No active reset request found. Please try again.</p>
                <a href="forgot_password.php" class="btn-primary">Return to Forgot Password</a>

            <?php endif; ?>

            <?php
            // Clear messages after displaying
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            unset($_SESSION['reset_link']);
            ?>
        </section>
    </main>
</body>
</html>