<?php
session_start();
include 'includes/db_config.php';

$success = false;
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($token) || empty($password)) {
        $error = "Token and password are required.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[_*@]).{6,}$/', $password)) {
        $error = "Password does not meet complexity requirements.";
    } else {

        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $reset = $result->fetch_assoc();
            $user_id = $reset['user_id'];
            $stmt->close();

            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $hashed, $user_id);

            if ($update->execute()) {
                $update->close();
                $del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                $del->bind_param("s", $token);
                $del->execute();
                $del->close();
                $success = true;
            } else {
                $error = "Error updating password.";
            }
        } else {
            $error = "Invalid or expired reset link.";
        }
    }
}
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Password Reset</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <section class="login-section" style="text-align: center; max-width: 450px; margin: 80px auto;">

            <?php if ($success): ?>

                <div style="background: #e8f5e9; border: 2px solid darkgreen; border-radius: 16px; padding: 40px 30px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="darkgreen" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 20px;">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <h2 style="color: darkgreen; margin-bottom: 10px;">Password Updated!</h2>
                    <p style="color: #555; margin-bottom: 30px;">Your password has been changed successfully.</p>
                    <a href="Login.php" class="btn-primary" style="display: inline-block; text-decoration: none; padding: 12px 32px;">Log In</a>
                </div>

            <?php elseif ($error): ?>

                <div style="background: #fee2e2; border: 2px solid #ef4444; border-radius: 16px; padding: 40px 30px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 20px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <h2 style="color: #991b1b; margin-bottom: 10px;">Something Went Wrong</h2>
                    <p style="color: #555; margin-bottom: 30px;"><?php echo htmlspecialchars($error); ?></p>
                    <a href="forgot_password.php" class="btn-primary" style="display: inline-block; text-decoration: none; padding: 12px 32px;">Try Again</a>
                </div>

            <?php else: ?>

                <p>No reset request found.</p>
                <a href="ForgotPassword.php">Back to Forgot Password</a>

            <?php endif; ?>

        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>