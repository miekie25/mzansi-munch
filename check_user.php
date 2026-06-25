<?php
include 'includes/db_config.php';

$field = $_GET['field']; // 'username' or 'email'
$value = $_GET['value'];

$stmt = $conn->prepare("SELECT id FROM users WHERE $field = ?");
$stmt->bind_param("s", $value);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "taken";
} else {
    echo "available";
}
?>