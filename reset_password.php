<?php
session_start();
include 'includes/db_config.php';

$token = $_GET['token'] ?? '';
$valid = false;

$sql = "
    SELECT *
    FROM password_resets
    WHERE token = '$token'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $valid = true;
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Mzansi Munch | New Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main>
        <section class="login-section">
            <?php if ($valid): ?>
                <h2>Set New Password</h2>
                <form action="reset_process.php" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <label>New Password</label>
<input type="password" id="password" name="password" required>

<ul id="passwordRequirements" style="list-style: none; font-size: 0.8rem; padding: 0;">
    <li id="len">At least 6 characters</li>
    <li id="cap">At least one uppercase letter</li>
    <li id="low">At least one lowercase letter</li>
    <li id="spec">Special character (_ * @)</li>
</ul>
                    <button type="submit" class="btn-primary">Update Password</button>
                </form>
            <?php else: ?>
                <h2 style="color:red;">Invalid or Expired Link</h2>
                <p>This reset link is no longer valid. Please try requesting a new one.</p>
                <a href="ForgotPassword.php">Back to Forgot Password</a>
            <?php endif; ?>
        </section>
    </main>
</body>

<script>
const password = document.getElementById('password');
const lenReq = document.getElementById('len');
const capReq = document.getElementById('cap');
const lowReq = document.getElementById('low');
const specReq = document.getElementById('spec');

password.addEventListener('input', function () {
    const val = this.value;
    lenReq.style.color = val.length >= 6 ? 'green' : 'gray';
    capReq.style.color = /[A-Z]/.test(val) ? 'green' : 'gray';
    lowReq.style.color = /[a-z]/.test(val) ? 'green' : 'gray';
    specReq.style.color = /[_*@]/.test(val) ? 'green' : 'gray';
});
</script>

</html>