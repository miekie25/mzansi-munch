<?php
session_start();
include 'includes/db_config.php';

// --- LOGIC: Handle the Seller Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty($_POST['biz_name']) || empty($_POST['first_name'])) {
        return; 
    }
    
    if (!isset($_SESSION['user_id'])) {
        die("Error: Session expired or user not found. Please register again.");
    }
    
    $user_id = $_SESSION['user_id'];

    // Sanitize all inputs
    $fname    = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lname    = mysqli_real_escape_string($conn, $_POST['last_name']);
    $biz_name = mysqli_real_escape_string($conn, $_POST['biz_name']);
    $addr1    = mysqli_real_escape_string($conn, $_POST['addr_line1']);
    $addr2    = mysqli_real_escape_string($conn, $_POST['addr_line2']);
    $city     = mysqli_real_escape_string($conn, $_POST['city']);
    $zip      = mysqli_real_escape_string($conn, $_POST['postal_code']);

    // --- Cuisine/Specialties Logic ---
    // Note: Used 'preferences' as the name in your form, mapping to 'cuisine_types' for DB
    $prefs_array = $_POST['preferences'] ?? [];

    // Filter out the workd "other"
    $prefs_array = array_filter($prefs_array, function($item) {
    return $item !== 'other';
});
    
    if (isset($_POST['other_prefs_text']) && !empty(trim($_POST['other_prefs_text']))) {
        $prefs_array[] = trim($_POST['other_prefs_text']);
    }

    $cuisines = !empty($prefs_array) ? implode(", ", $prefs_array) : "General Cuisine";

    // Insert into 'sellers' table
    $sql = "INSERT INTO sellers (user_id, first_name, last_name, business_name, address_line1, address_line2, city, postal_code, cuisine_types) 
            VALUES ('$user_id', '$fname', '$lname', '$biz_name', '$addr1', '$addr2', '$city', '$zip', '$cuisines')";

    if ($conn->query($sql) === TRUE) {
        header("Location: Dashboard.php");
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
    <title>Mzansi Munch | Business Setup</title>
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
            <h2>So you'd like to be a seller... almost there!</h2>
            <h2>Please fill in your Kitchen Details to help locals find your delicious food!</h2> <br>
            
            <?php if(isset($error_msg)) echo "<p style='color:red;'>$error_msg</p>"; ?>
            
            <form action="SellerInfo.php" method="POST">
                <fieldset>
                    <legend>Personal Information</legend>
                    <input type="text" name="first_name" placeholder="First Name(s)" required>
                    <input type="text" name="last_name" placeholder="Surname" required>
                </fieldset>
                
                <fieldset>
                    <legend>Trading Name and Address</legend>
                    <input type="text" name="biz_name" placeholder="e.g. Mpho's Kotas" required>
                    <div class="address-group">
                        <label style="font-size: 13px; color: darkgreen; display: block; margin-bottom: 5px;">Pickup Address Details</label>
                        <input type="text" name="addr_line1" placeholder="Street Address / House Number" required>
                        <input type="text" name="addr_line2" placeholder="Apartment, suite, unit, etc. (Optional)">
                        <input type="text" name="city" placeholder="Township / Suburb" required>
                        <input type="text" name="postal_code" placeholder="Postal Code" required>
                    </div>
                </fieldset>
                
                <fieldset>
                    <legend>Your Specialties</legend>
                    <label>Select all that apply:</label>
                    <div class="checkbox-grid">
                        <div class="checkbox-item"><input type="checkbox" name="preferences[]" value="braai"> Braai (Shisanyama)</div>
                        <div class="checkbox-item"><input type="checkbox" name="preferences[]" value="cape_malay"> Cape Malay</div>
                        <div class="checkbox-item"><input type="checkbox" name="preferences[]" value="indian_sa"> SA Indian</div>
                        <div class="checkbox-item"><input type="checkbox" name="preferences[]" value="afrikaans"> Boerekos</div>
                        <div class="checkbox-item"><input type="checkbox" name="preferences[]" value="street_food"> Street Food (Kota/Gatsby)</div>
                        <div class="checkbox-item"><input type="checkbox" name="preferences[]" value="baked_goods"> Baked Goods</div>
                        
                        <div class="checkbox-item" style="border-top: 1px solid #ccc; padding-top: 10px; margin-top: 10px;">
                            <input type="checkbox" name="preferences[]" value="other"> Other:
                            <input type="text" name="other_prefs_text" placeholder="Specify here..." style="margin-left: 10px; padding: 5px;">
                        </div>
                    </div>
                </fieldset>
                
                <button type="submit" class="btn-primary">Open My Kitchen</button>
            </form>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/seller_info_validation.js"></script>
</body>
</html>