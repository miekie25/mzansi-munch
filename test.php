<?php
$host = "sql304.infinityfree.com";
$db_user = "if0_42140840";
$db_pass = "Miekieland25_";
$db_name = "if0_42140840_mzansi_munch";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully!";
}
?>