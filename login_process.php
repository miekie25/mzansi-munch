<?php
session_start();
include 'includes/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $_POST['username']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($_POST['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Store seller_id in session
            if ($user['role'] === 'seller') {
                $seller_stmt = $conn->prepare("SELECT seller_id FROM sellers WHERE user_id = ?");
                $seller_stmt->bind_param("i", $user['id']);
                $seller_stmt->execute();
                $seller_res = $seller_stmt->get_result();
                if ($row = $seller_res->fetch_assoc()) {
                    $_SESSION['seller_id'] = $row['seller_id'];
                }
            }

            // Store buyer_id in session
            if ($user['role'] === 'buyer') {
                $buyer_stmt = $conn->prepare("SELECT buyer_id FROM buyers WHERE user_id = ?");
                $buyer_stmt->bind_param("i", $user['id']);
                $buyer_stmt->execute();
                $buyer_res = $buyer_stmt->get_result();
                if ($row = $buyer_res->fetch_assoc()) {
                    $_SESSION['buyer_id'] = $row['buyer_id'];
                }
            }

            // Role-based routing
            switch ($_SESSION['role']) {
                case 'admin':
                    header("Location: admin/AdminDashboard.php");
                    break;
                case 'seller':
                    header("Location: Dashboard.php");
                    break;
                case 'delivery':
                    header("Location: DeliveryDashboard.php");
                    break;
                case 'buyer':
                    header("Location: BuyerDashboard.php");
                    break;
                default:
                    header("Location: Shop.php");
                    break;
            }
            exit();

        } else {
            $_SESSION['login_error'] = "The username or password you entered is incorrect.";
            header("Location: Login.php");
            exit();
        }
    } else {
        $_SESSION['login_error'] = "This user does not exist. Please check your username or register.";
        header("Location: Login.php");
        exit();
    }
}
?>