<?php
session_start();
include 'includes/db_config.php';

// --- LOGIC: Handle the Driver Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($_SESSION['user_id'])) {
        die("Error: Session expired or user not found. Please register again.");
    }
    
    $user_id = $_SESSION['user_id'];

    $fname        = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lname        = mysqli_real_escape_string($conn, $_POST['last_name']);
    $vehicle_type = mysqli_real_escape_string($conn, $_POST['vehicle_type']);
    $city         = mysqli_real_escape_string($conn, $_POST['city']);
    $zip          = mysqli_real_escape_string($conn, $_POST['postal_code']);

    $sql = "INSERT INTO drivers (user_id, first_name, last_name, vehicle_type, city, postal_code, availability_status) 
            VALUES ('$user_id', '$fname', '$lname', '$vehicle_type', '$city', '$zip', 'Active')";

    if ($conn->query($sql) === TRUE) {
        header("Location: DeliveryDashboard.php");
        exit();
    } else {
        $error_msg = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mzansi Munch | Driver Setup</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/forms.css">
</head>
<body>

    <?php 
        $current_page = 'register'; 
        include 'includes/header.php'; 
    ?>

    <main>
        <section class="info-box">
            <h2>So you'd like to be a delivery partner... almost there!</h2> 
            <p>Please fill in your delivery details to help us connect you to local, delicious food!</p> <br>
            
            <?php if(isset($error_msg)) echo "<p style='color:red;'>$error_msg</p>"; ?>
            
            <form action="DeliveryInfo.php" method="POST">
            
                <fieldset>
                    <legend>Personal Information</legend>
                    <input type="text" name="first_name" placeholder="First Name(s)" required>
                    <input type="text" name="last_name" placeholder="Surname" required>
                </fieldset>
                
                <fieldset>
                    <legend>Delivery Mode</legend>
                    <label style="font-size: 13px; color: darkgreen; display: block; margin-bottom: 4px;">How will you deliver your orders?</label>
                    
                    <div class="delivery-options">
                        <div class="delivery-item">
                            <input type="radio" name="vehicle_type" id="mode_foot" value="Foot" checked required>
                            <label for="mode_foot">On Foot</label>
                        </div>
                        <div class="delivery-item">
                            <input type="radio" name="vehicle_type" id="mode_bike" value="Bicycle">
                            <label for="mode_bike">Bicycle / Scooter</label>
                        </div>
                        <div class="delivery-item">
                            <input type="radio" name="vehicle_type" id="mode_car" value="Car">
                            <label for="mode_car">Car</label>
                        </div>
                    </div>
                    
                    <div class="pay-rate-info">
                        <p><i class="ti ti-cash"></i> <strong>Pay rates:</strong> Walkers earn <strong>R20</strong> per delivery. Drivers & bikers earn <strong>R40</strong> per delivery.</p>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Operating Area</legend>
                    <div class="address-group">
                        <input type="text" name="city" placeholder="Township / Suburb / City" required>
                        <input type="text" name="postal_code" placeholder="Postal Code" required>
                    </div>
                </fieldset>
                
                <button type="submit" class="btn-primary">Open My Driver Profile</button>
            </form>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/delivery_info_validation.js"></script>

</body>
</html>