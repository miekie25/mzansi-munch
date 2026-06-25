<?php
session_start();
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Reset Link Sent</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main>
        <section class="login-section">
            <h2>Reset Link Ready</h2>
            
            <?php if (isset($_SESSION['reset_link'])): ?>
                <p>Success! Please use the link below to reset your password:</p>
                
                <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; margin: 25px 0; border: 2px solid darkgreen;">
                    <a href="<?php echo htmlspecialchars($_SESSION['reset_link']); ?>" style="color: darkgreen; font-weight: bold; word-break: break-all;">
                        Click here to reset your password
                    </a>
                </div>
                
                <p style="font-size: 0.9em; color: #666;">This link is valid for 1 hour.</p>
                
                <?php 
             
                ?>
            <?php else: ?>
                <p>No active reset request found. Please try again.</p>
                <a href="ForgotPassword.php" class="btn-primary">Return to Forgot Password</a>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>