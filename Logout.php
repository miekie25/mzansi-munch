<?php
session_start();

// 1. Clear all session variables
$_SESSION = array();

// 2. Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// 3. Destroy the session on the server
session_destroy();

// 4. Redirect to the Landing page or Login page
header("Location: Landing.php");
exit();
?>