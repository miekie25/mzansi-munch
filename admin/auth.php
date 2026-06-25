<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

if (time() - $_SESSION['last_activity'] > 1800) {
    session_unset();
    session_destroy();
    header("Location: ../Login.php?reason=timeout");
    exit();
}

$_SESSION['last_activity'] = time();

function verify_csrf() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token. Action blocked.");
    }
}

function csrf_field() {
    return "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token']) . "'>";
}
?>