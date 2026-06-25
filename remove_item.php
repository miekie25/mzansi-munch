<?php
session_start();

if (isset($_GET['id'])) {
    $id_to_remove = (int)$_GET['id'];
    
    // Remove the item directly using the ID as the key
    if (isset($_SESSION['cart'][$id_to_remove])) {
        unset($_SESSION['cart'][$id_to_remove]);
    }
}

header("Location: Cart.php");
exit();
?>