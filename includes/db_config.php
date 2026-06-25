<?php
// Session timeout: 30 minutes of inactivity
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$timeout = 30 * 60; // 30 minutes in seconds

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: Login.php?reason=timeout");
    exit();
}

$_SESSION['last_activity'] = time();

// Database configuration settings
$host = "sql304.infinityfree.com";
$db_user = "if0_42140840"; 
$db_pass = "Miekieland25"; 
$db_name = "if0_42140840_mzansi_munch";

// Create the connection to the MySQL database
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// Check if the connection worked
if ($conn->connect_error) {
    // If it failed, stop the script and show the error
    die("Connection failed: " . $conn->connect_error);
}

// If the cart doesn't exist yet, create an empty one
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}
?>