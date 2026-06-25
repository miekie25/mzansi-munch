<?php
include 'includes/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $token = mysqli_real_escape_string($conn, $_POST['token']);
    $password = $_POST['password'];


    // Find reset request
    $sql = "
        SELECT *
        FROM password_resets
        WHERE token = '$token'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {

        $reset = $result->fetch_assoc();
        $user_id = $reset['user_id'];

        // Regex matching your registration rules
$regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[_*@]).{6,}$/';

if (!preg_match($regex, $password)) {
    die("Password does not meet complexity requirements. Please try again.");
}

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update user's password
        $update_sql = "
            UPDATE users
            SET password = '$hashed_password'
            WHERE id = '$user_id'
        ";

        if ($conn->query($update_sql)) {

            // Remove token after use
            $conn->query("
                DELETE FROM password_resets
                WHERE token = '$token'
            ");

            echo "<h2>Password Updated Successfully!</h2>";
            echo "<p><a href='Login.php'>Click here to login</a></p>";

        } else {

            echo "Error updating password.";

        }

    } else {

        echo "Invalid or expired reset link.";

    }
}
?>