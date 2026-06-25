<?php
session_start();
include 'includes/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($_SESSION['user_id'])) {
        die("Error: User session not found. Please register again.");
    }
    
    $user_id = $_SESSION['user_id'];

    $fname = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lname = mysqli_real_escape_string($conn, $_POST['last_name']);
    $addr1 = mysqli_real_escape_string($conn, $_POST['addr_line1']);
    $addr2 = mysqli_real_escape_string($conn, $_POST['addr_line2']);
    $city  = mysqli_real_escape_string($conn, $_POST['city']);
    $zip   = mysqli_real_escape_string($conn, $_POST['postal_code']);

    $prefs = isset($_POST['cuisine']) ? implode(", ", $_POST['cuisine']) : "None";

    $sql = "INSERT INTO buyers (user_id, first_name, last_name, address_line1, address_line2, city, postal_code, preferences) 
            VALUES ('$user_id', '$fname', '$lname', '$addr1', '$addr2', '$city', '$zip', '$prefs')";

    if ($conn->query($sql) === TRUE) {
        header("Location: Shop.php");
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
    <title>Mzansi Munch | Buyer Setup</title>
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
            <h2>So you'd like to be a buyer... almost there!</h2>
            <p>Please fill in your details to help us connect you to local, delicious food!</p> <br>
            
            <?php if(isset($error_msg)) echo "<p style='color:red;'>$error_msg</p>"; ?>

            <form action="BuyerInfo.php" method="POST">
            
                <fieldset>
                    <legend>Personal Information</legend>
                    <input type="text" name="first_name" placeholder="First Name(s)" required>
                    <input type="text" name="last_name" placeholder="Surname" required>
                </fieldset>
                
                <fieldset>
                    <legend>Residential Address</legend>
                    <div class="address-group">
                        <input type="text" name="addr_line1" placeholder="Street Address / House Number" required>
                        <input type="text" name="addr_line2" placeholder="Apartment, suite, unit, etc. (Optional)">
                        <input type="text" name="city" placeholder="Township / Suburb" required>
                        <input type="text" name="postal_code" placeholder="Postal Code" required>
                    </div>
                </fieldset>
                
                <fieldset>
                    <legend>What are you looking for?</legend>
                    <label>Select all that apply:</label>
                    <div class="checkbox-grid">
                        <div class="checkbox-item">
                            <input type="checkbox" name="cuisine[]" value="braai"> Braai (Shisanyama)
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="cuisine[]" value="cape_malay"> Cape Malay
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="cuisine[]" value="indian_sa"> SA Indian
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="cuisine[]" value="afrikaans"> Boerekos
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="cuisine[]" value="street_food"> Street Food (Kota)
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="cuisine[]" value="baked_goods"> Baked Goods
                        </div>
                    </div>
                </fieldset>
                
                <button type="submit" class="btn-primary">Start Munching!</button>
            </form>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/buyer_info_validation.js"></script>

</body>
</html>