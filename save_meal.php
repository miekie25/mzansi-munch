<?php
session_start();
include 'db_config.php';

// 1. Check if logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: You are not logged in.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];

    // 2. Get Seller ID
    $seller_query = "SELECT seller_id FROM sellers WHERE user_id = '$user_id'";
    $seller_result = $conn->query($seller_query);
    
    if ($seller_result->num_rows == 0) {
        die("Error: No seller profile found for User ID: " . $user_id);
    }

    $seller_data = $seller_result->fetch_assoc();
    $seller_id = $seller_data['seller_id'];

    // 3. Handle File
    $target_dir = "uploads/";
    $image_name = time() . "_" . basename($_FILES["meal_image"]["name"]);
    $target_file = $target_dir . $image_name;

    if (move_uploaded_file($_FILES["meal_image"]["tmp_name"], $target_file)) {
        // 4. Secure the data
        $name = mysqli_real_escape_string($conn, $_POST['meal_name']);
        $desc = mysqli_real_escape_string($conn, $_POST['meal_description']);
        $price = mysqli_real_escape_string($conn, $_POST['price']);

        // 5. Insert to DB
        $sql = "INSERT INTO meals (seller_id, meal_name, meal_description, price, image_url) 
                VALUES ('$seller_id', '$name', '$desc', '$price', '$image_name')";

        if ($conn->query($sql) === TRUE) {
            // Success! Send them to the dashboard
            header("Location: seller_dashboard.php?success=1");
            exit();
        } else {
            die("Database Error: " . $conn->error);
        }
    } else {
        die("File Upload Error. Check if 'uploads' folder exists and is writable.");
    }
}
?>