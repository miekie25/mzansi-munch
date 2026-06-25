<?php 
require_once 'auth.php'; 
require_once '../includes/db_config.php'; 

// Fetch admin name from admins table
$admin_first_name = 'Admin';
$admin_full_name = 'Admin';

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT first_name, last_name FROM admins WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $admin_first_name = $row['first_name'];
        $admin_full_name = $row['first_name'] . ' ' . $row['last_name'];
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['order_status'];
    
    $allowed_statuses = ['Pending', 'Prepared', 'Ready for Pickup', 'Accepted', 'Out for Delivery', 'Delivered'];
    if (!in_array($new_status, $allowed_statuses)) {
        die("Invalid status value");
    }
    
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: AdminDashboard.php?tab=" . ($_GET['tab'] ?? 'dashboard'));
    exit();
}

if (isset($_GET['delete_user_id'])) {
    $delete_id = intval($_GET['delete_user_id']);
    $user_role = $_GET['role'] ?? '';
    
    if ($delete_id !== ($_SESSION['user_id'] ?? 0)) {
        
        if ($user_role === 'seller') {
            $stmt = $conn->prepare("SELECT seller_id FROM sellers WHERE user_id = ?");
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $seller_row = $res->fetch_assoc();
            $stmt->close();
            
            if ($seller_row) {
                $seller_id = $seller_row['seller_id'];
                
                $conn->query("DELETE FROM seller_earnings WHERE seller_id = $seller_id");
                $conn->query("DELETE FROM order_items WHERE seller_id = $seller_id");
                $conn->query("DELETE FROM meals WHERE seller_id = $seller_id");
                $conn->query("DELETE FROM sellers WHERE user_id = $delete_id");
            }
            
        } elseif ($user_role === 'driver') {
            $stmt = $conn->prepare("SELECT driver_id FROM drivers WHERE user_id = ?");
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $driver_row = $res->fetch_assoc();
            $stmt->close();
            
            if ($driver_row) {
                $driver_id = $driver_row['driver_id'];
                $conn->query("DELETE FROM driver_earnings WHERE driver_id = $driver_id");
                $conn->query("DELETE FROM drivers WHERE user_id = $delete_id");
            }
            
        } elseif ($user_role === 'buyer') {
            // ==== FIXED BUYER DELETION START ====
            // Get all order IDs for this buyer
            $stmt = $conn->prepare("SELECT id FROM orders WHERE buyer_id = ?");
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $order_res = $stmt->get_result();
            $stmt->close();
            
            // Collect order IDs
            $order_ids = [];
            while ($order = $order_res->fetch_assoc()) {
                $order_ids[] = $order['id'];
            }
            
            // Delete order_items for each order
            foreach ($order_ids as $oid) {
                $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
                $stmt->bind_param("i", $oid);
                $stmt->execute();
                $stmt->close();
            }
            
            // Delete seller_earnings for each order
            foreach ($order_ids as $oid) {
                $stmt = $conn->prepare("DELETE FROM seller_earnings WHERE order_id = ?");
                $stmt->bind_param("i", $oid);
                $stmt->execute();
                $stmt->close();
            }
            
            // Delete driver_earnings for each order
            foreach ($order_ids as $oid) {
                $stmt = $conn->prepare("DELETE FROM driver_earnings WHERE order_id = ?");
                $stmt->bind_param("i", $oid);
                $stmt->execute();
                $stmt->close();
            }
            
            // Delete orders individually
            foreach ($order_ids as $oid) {
                $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
                $stmt->bind_param("i", $oid);
                $stmt->execute();
                $stmt->close();
            }
            
            // Delete buyer record
            $stmt = $conn->prepare("DELETE FROM buyers WHERE user_id = ?");
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $stmt->close();
            // ==== FIXED BUYER DELETION END ====
            
        } elseif ($user_role === 'admin') {
            $conn->query("DELETE FROM admins WHERE user_id = $delete_id");
        }
        
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
    }
    
    header("Location: AdminDashboard.php?tab=" . ($_GET['tab'] ?? 'dashboard'));
    exit();
}

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

if ($current_tab === 'buyers') {
    $buyer_sql = "SELECT id, username, email FROM users WHERE role = 'buyer'";
    $buyer_result = $conn->query($buyer_sql);
}

if ($current_tab === 'sellers') {
    $seller_sql = "SELECT s.seller_id, u.id as user_id, u.username, u.email, s.business_name, s.city, s.cuisine_types 
                   FROM sellers s 
                   INNER JOIN users u ON s.user_id = u.id";
    $seller_result = $conn->query($seller_sql);
}

if ($current_tab === 'drivers') {
    $driver_sql = "SELECT d.driver_id, u.id as user_id, d.first_name, d.last_name, d.vehicle_type, d.city, d.availability_status, u.email 
                   FROM drivers d 
                   INNER JOIN users u ON d.user_id = u.id";
    $driver_result = $conn->query($driver_sql);
}

if ($current_tab === 'admin') {
    $admin_sql = "SELECT id, username, email FROM users WHERE role = 'admin'";
    $admin_result = $conn->query($admin_sql);
}

if ($current_tab === 'dashboard') {
    $live_orders_sql = "SELECT o.id, u.username as customer, o.total_amount, o.order_status, o.delivery_address,
                        GROUP_CONCAT(CONCAT(oi.quantity, 'x ', oi.meal_name) SEPARATOR ', ') as ordered_items
                        FROM orders o
                        INNER JOIN users u ON o.buyer_id = u.id
                        LEFT JOIN order_items oi ON o.id = oi.order_id
                        WHERE o.order_status NOT IN ('Delivered', 'Cancelled')
                        GROUP BY o.id
                        ORDER BY o.created_at DESC";
    $live_orders_result = $conn->query($live_orders_sql);

    $total_buyers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'buyer'")->fetch_assoc()['count'];
    $total_sellers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'seller'")->fetch_assoc()['count'];
    $total_drivers = $conn->query("SELECT COUNT(*) as count FROM drivers")->fetch_assoc()['count'];
    $total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
    $total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status IN ('paid', 'completed')")->fetch_assoc()['total'] ?? 0;
    $pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'Pending'")->fetch_assoc()['count'];
}

if ($current_tab === 'orders') {
    $all_orders_sql = "SELECT o.id, u.username as customer, o.total_amount, o.order_status, o.payment_status, o.delivery_address, o.created_at,
                       GROUP_CONCAT(CONCAT(oi.quantity, 'x ', oi.meal_name) SEPARATOR ', ') as ordered_items
                       FROM orders o
                       INNER JOIN users u ON o.buyer_id = u.id
                       LEFT JOIN order_items oi ON o.id = oi.order_id
                       GROUP BY o.id
                       ORDER BY o.created_at DESC";
    $all_orders_result = $conn->query($all_orders_sql);
}
?>
<!DOCTYPE html>
<html lang="en">
 <head>
   <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
   <title>Mzansi Munch | Admin Dashboard</title>
   <link rel="stylesheet" href="assets/vendors/feather/feather.css">
   <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
   <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
   <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
   <link rel="stylesheet" href="assets/vendors/typicons/typicons.css">
   <link rel="stylesheet" href="assets/vendors/simple-line-icons/css/simple-line-icons.css">
   <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
   <link rel="stylesheet" href="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css">
   <link rel="stylesheet" href="assets/vendors/datatables.net-bs4/dataTables.bootstrap4.css">
   <link rel="stylesheet" type="text/css" href="assets/js/select.dataTables.min.css">
   <link rel="stylesheet" href="assets/css/style.css">
   <link rel="stylesheet" href="admin_style.css?v=1.2">
   <link rel="shortcut icon" href="../images/favicon.png" />
 </head>
  <body class="with-welcome-text">
    <div class="container-scroller">
      <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
        <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
          <div class="me-3">
            <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
              <span class="icon-menu"></span>
            </button>
          </div>
          <div>
            <a class="navbar-brand brand-logo" href="AdminDashboard.php">
              <img src="../images/logo.jpg" alt="logo" style="width: 150px; height: auto;" />
            </a>
          </div>
        </div>
        <div class="navbar-menu-wrapper d-flex align-items-top">
          <ul class="navbar-nav">
            <li class="nav-item fw-semibold d-none d-lg-block ms-0">
              <h1 class="welcome-text">Hello, <?php echo htmlspecialchars($admin_first_name); ?></h1>
              <h3 class="welcome-sub-text">Mzansi Munch Management System Workspace</h3>
            </li>
          </ul>
          <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown user-dropdown">
              <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="badge badge-primary p-2 text-white fw-bold">ADMIN</div>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                <a class="dropdown-item" href="Logout.php"><i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign Out</a>
              </div>
            </li>
          </ul>
          <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
            <span class="mdi mdi-menu"></span>
          </button>
        </div>
      </nav>
      <div class="container-fluid page-body-wrapper">
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
          <ul class="nav">
            <li class="nav-item <?php echo $current_tab === 'dashboard' ? 'active' : ''; ?>">
              <a class="nav-link" href="AdminDashboard.php">
                <i class="mdi mdi-grid-large menu-icon"></i>
                <span class="menu-title">Dashboard</span>
              </a>
            </li>
            <li class="nav-item nav-category">User Management</li>
            <li class="nav-item <?php echo $current_tab === 'buyers' ? 'active' : ''; ?>">
              <a class="nav-link" href="AdminDashboard.php?tab=buyers">
                <i class="menu-icon mdi mdi-account-group"></i>
                <span class="menu-title">Manage Buyers</span>
              </a>
            </li>
            <li class="nav-item <?php echo $current_tab === 'sellers' ? 'active' : ''; ?>">
              <a class="nav-link" href="AdminDashboard.php?tab=sellers">
                <i class="menu-icon mdi mdi-store"></i>
                <span class="menu-title">Manage Sellers</span>
              </a>
            </li>
            <li class="nav-item <?php echo $current_tab === 'drivers' ? 'active' : ''; ?>">
              <a class="nav-link" href="AdminDashboard.php?tab=drivers">
                <i class="menu-icon mdi mdi-run"></i>
                <span class="menu-title">Delivery Personnel</span>
              </a>
            </li>
            <li class="nav-item <?php echo $current_tab === 'admin' ? 'active' : ''; ?>">
              <a class="nav-link" href="AdminDashboard.php?tab=admin">
                <i class="menu-icon mdi mdi-shield-account"></i>
                <span class="menu-title">Admin Staff</span>
              </a>
            </li>
            <li class="nav-item nav-category">Order Management</li>
            <li class="nav-item <?php echo $current_tab === 'orders' ? 'active' : ''; ?>">
              <a class="nav-link" href="AdminDashboard.php?tab=orders">
                <i class="menu-icon mdi mdi-cart-outline"></i>
                <span class="menu-title">Manage Orders</span>
              </a>
            </li>
            <li class="nav-item nav-category">System</li>
            <li class="nav-item">
              <a class="nav-link" href="../Logout.php">
                <i class="menu-icon mdi mdi-logout"></i>
                <span class="menu-title">Logout</span>
              </a>
            </li>
          </ul>
        </nav>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="tab-content tab-content-basic">
              <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">

                <?php if ($current_tab === 'dashboard'): ?>

                <div class="row mb-4">
                  <div class="col-lg-2 col-md-4 col-sm-6 grid-margin stretch-card">
                    <div class="card card-rounded text-center p-3">
                      <i class="mdi mdi-account-group text-primary" style="font-size:2rem;"></i>
                      <h4 class="mt-2 mb-0"><?php echo $total_buyers; ?></h4>
                      <p class="text-muted mb-0">Buyers</p>
                    </div>
                  </div>
                  <div class="col-lg-2 col-md-4 col-sm-6 grid-margin stretch-card">
                    <div class="card card-rounded text-center p-3">
                      <i class="mdi mdi-store text-warning" style="font-size:2rem;"></i>
                      <h4 class="mt-2 mb-0"><?php echo $total_sellers; ?></h4>
                      <p class="text-muted mb-0">Sellers</p>
                    </div>
                  </div>
                  <div class="col-lg-2 col-md-4 col-sm-6 grid-margin stretch-card">
                    <div class="card card-rounded text-center p-3">
                      <i class="mdi mdi-run text-success" style="font-size:2rem;"></i>
                      <h4 class="mt-2 mb-0"><?php echo $total_drivers; ?></h4>
                      <p class="text-muted mb-0">Delivery Personnel</p>
                    </div>
                  </div>
                  <div class="col-lg-2 col-md-4 col-sm-6 grid-margin stretch-card">
                    <div class="card card-rounded text-center p-3">
                      <i class="mdi mdi-cart-outline text-info" style="font-size:2rem;"></i>
                      <h4 class="mt-2 mb-0"><?php echo $total_orders; ?></h4>
                      <p class="text-muted mb-0">Total Orders</p>
                    </div>
                  </div>
                  <div class="col-lg-2 col-md-4 col-sm-6 grid-margin stretch-card">
                    <div class="card card-rounded text-center p-3">
                      <i class="mdi mdi-clock-alert text-danger" style="font-size:2rem;"></i>
                      <h4 class="mt-2 mb-0"><?php echo $pending_orders; ?></h4>
                      <p class="text-muted mb-0">Pending Orders</p>
                    </div>
                  </div>
                  <div class="col-lg-2 col-md-4 col-sm-6 grid-margin stretch-card">
                    <div class="card card-rounded text-center p-3">
                      <i class="mdi mdi-cash text-success" style="font-size:2rem;"></i>
                      <h4 class="mt-2 mb-0">R <?php echo number_format($total_revenue, 2); ?></h4>
                      <p class="text-muted mb-0">Total Revenue</p>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card card-rounded">
                      <div class="card-body">
                        <div class="d-sm-flex justify-content-between align-items-start mb-4">
                          <div>
                            <h4 class="card-title card-title-dash">Live Active Order Queue</h4>
                            <p class="card-subtitle card-subtitle-dash">Change status dropdown values to control live orders dynamically</p>
                          </div>
                        </div>
                        <div class="table-responsive">
                          <table class="table select-table">
                            <thead>
                              <tr>
                                <th>Order ID</th>
                                <th>Customer / Delivery Address</th>
                                <th>Items Ordered</th>
                                <th>Total Amount</th>
                                <th>Live Status Controls</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php 
                              if (isset($live_orders_result) && $live_orders_result->num_rows > 0) {
                                  while ($row = $live_orders_result->fetch_assoc()) {
                                      echo "<tr>";
                                      echo "<td><h6>#MM-" . htmlspecialchars($row['id']) . "</h6></td>";
                                      echo "<td><h6>" . htmlspecialchars($row['customer']) . "</h6><p>" . htmlspecialchars($row['delivery_address']) . "</p></td>";
                                      echo "<td><p class='text-wrap fw-bold text-dark' style='max-width: 250px;'>" . htmlspecialchars($row['ordered_items'] ?? 'No items') . "</p></td>";
                                      echo "<td><h6>R " . number_format($row['total_amount'], 2) . "</h6></td>";
                                      echo "<td>
                                              <form method='POST' action='AdminDashboard.php?tab=dashboard' class='d-flex align-items-center gap-2'>
                                                  <input type='hidden' name='action' value='update_order_status'>
                                                  <input type='hidden' name='order_id' value='" . $row['id'] . "'>
                                                  <select name='order_status' class='form-control form-control-sm' style='width: 160px; padding: 5px;' onchange='this.form.submit()'>
                                                      <option value='Pending' " . (strtolower($row['order_status']) == 'pending' ? 'selected' : '') . ">Pending</option>
                                                      <option value='Prepared' " . (strtolower($row['order_status']) == 'prepared' ? 'selected' : '') . ">Prepared</option>
                                                      <option value='Ready for Pickup' " . (strtolower($row['order_status']) == 'ready for pickup' ? 'selected' : '') . ">Ready for Pickup</option>
                                                      <option value='Accepted' " . (strtolower($row['order_status']) == 'accepted' ? 'selected' : '') . ">Accepted</option>
                                                      <option value='Out for Delivery' " . (strtolower($row['order_status']) == 'out for delivery' ? 'selected' : '') . ">Out for Delivery</option>
                                                      <option value='Delivered' " . (strtolower($row['order_status']) == 'delivered' ? 'selected' : '') . ">Delivered</option>
                                                  </select>
                                              </form>
                                            </td>";
                                      echo "</tr>";
                                  }
                              } else {
                                  echo "<tr><td colspan='5' class='text-center'><h6>No active live orders at the moment.</h6></td></tr>";
                              }
                              ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <?php elseif ($current_tab === 'buyers'): ?>
                <div class="row">
                  <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card card-rounded">
                      <div class="card-body">
                        <h4 class="card-title card-title-dash">Registered Buyers</h4>
                        <div class="table-responsive">
                          <table class="table select-table">
                            <thead>
                              <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Email Address</th>
                                <th>Actions (CRUD)</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php 
                              if (isset($buyer_result) && $buyer_result->num_rows > 0) {
                                  while ($row = $buyer_result->fetch_assoc()) {
                                      echo "<tr>";
                                      echo "<td><h6>#" . htmlspecialchars($row['id']) . "</h6></td>";
                                      echo "<td><h6>" . htmlspecialchars($row['username']) . "</h6></td>";
                                      echo "<td><h6>" . htmlspecialchars($row['email']) . "</h6></td>";
                                      echo "<td>
                                              <a href='AdminEditUser.php?id=" . $row['id'] . "&role=buyer' class='btn btn-primary btn-sm text-white py-1 px-3 me-2'>
                                                  <i class='fa fa-edit'></i> Edit
                                              </a>
                                              <a href='AdminDashboard.php?tab=buyers&delete_user_id=" . $row['id'] . "&role=buyer' class='btn btn-danger btn-sm text-white py-1 px-3' onclick='return confirm(\"Are you sure you want to completely delete this customer account?\")'>
                                                  <i class='fa fa-trash'></i> Delete
                                              </a>
                                            </td>";
                                      echo "</tr>";
                                  }
                              } else {
                                  echo "<tr><td colspan='4' class='text-center'><h6>No buyers found.</h6></td></tr>";
                              }
                              ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <?php elseif ($current_tab === 'sellers'): ?>
                <div class="row">
                  <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card card-rounded">
                      <div class="card-body">
                        <h4 class="card-title card-title-dash">Partner Kitchens / Sellers</h4>
                        <div class="table-responsive">
                          <table class="table select-table">
                            <thead>
                              <tr>
                                <th>Seller ID</th>
                                <th>Business Name</th>
                                <th>Cuisine Types</th>
                                <th>City</th>
                                <th>Owner Account</th>
                                <th>Actions (CRUD)</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php 
                              if (isset($seller_result) && $seller_result->num_rows > 0) {
                                  while ($row = $seller_result->fetch_assoc()) {
                                      echo "<tr>";
                                      echo "<td><h6>#" . htmlspecialchars($row['seller_id']) . "</h6></td>";
                                      echo "<td><h6>" . htmlspecialchars($row['business_name']) . "</h6></td>";
                                      echo "<td><h6>" . htmlspecialchars($row['cuisine_types'] ?? 'General') . "</h6></td>";
                                      echo "<td><h6>" . htmlspecialchars($row['city']) . "</h6></td>";
                                      echo "<td><h6>" . htmlspecialchars($row['username']) . "</h6></td>";
                                      echo "<td>
                                              <a href='AdminEditUser.php?id=" . $row['user_id'] . "&role=seller' class='btn btn-primary btn-sm text-white py-1 px-3 me-2'>
                                                  <i class='fa fa-edit'></i> Edit
                                              </a>
                                              <a href='AdminDashboard.php?tab=sellers&delete_user_id=" . $row['user_id'] . "&role=seller' class='btn btn-danger btn-sm text-white py-1 px-3' onclick='return confirm(\"Are you sure you want to completely remove this business and account from the system?\")'>
                                                  <i class='fa fa-trash'></i> Delete
                                              </a>
                                            </td>";
                                      echo "</tr>";
                                  }
                              } else {
                                  echo "<tr><td colspan='6' class='text-center'><h6>No vendors found.</h6></td></tr>";
                              }
                              ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <?php elseif ($current_tab === 'drivers'): ?>
                <div class="row">
                  <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card card-rounded">
                      <div class="card-body">
                        <h4 class="card-title card-title-dash">Delivery Personnel</h4>
                        <div class="table-responsive">
                          <table class="table select-table">
                            <thead>
                              <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email Address</th>
                                <th>Transport Method</th>
                                <th>Status</th>
                                <th>Actions (CRUD)</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php 
                              if (isset($driver_result) && $driver_result->num_rows > 0) {
                                  while ($row = $driver_result->fetch_assoc()) {
                                      echo "<tr>";
                                      echo "<td><h6>#" . htmlspecialchars($row['driver_id']) . "</h6></td>";
                                      echo "<td><h6>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</h6></td>";
                                      echo "<td><h6>" . htmlspecialchars($row['email']) . "</h6></td>";
                                      echo "<td><h6>" . htmlspecialchars($row['vehicle_type']) . "</h6></td>";
                                      
                                      $status = strtolower($row['availability_status']);
                                      if ($status === 'available') {
                                          echo "<td><div class='badge badge-opacity-success'>Available</div></td>";
                                      } elseif ($status === 'on_delivery') {
                                          echo "<td><div class='badge badge-opacity-warning'>On Delivery</div></td>";
                                      } else {
                                          echo "<td><div class='badge badge-opacity-danger'>Unavailable</div></td>";
                                      }
                                      
                                      echo "<td>
                                              <a href='AdminEditUser.php?id=" . $row['user_id'] . "&role=driver' class='btn btn-primary btn-sm text-white py-1 px-3 me-2'>
                                                  <i class='fa fa-edit'></i> Edit
                                              </a>
                                              <a href='AdminDashboard.php?tab=drivers&delete_user_id=" . $row['user_id'] . "&role=driver' class='btn btn-danger btn-sm text-white py-1 px-3' onclick='return confirm(\"Are you sure you want to remove this delivery person from the system?\")'>
                                                  <i class='fa fa-trash'></i> Dismiss
                                              </a>
                                            </td>";
                                      echo "</tr>";
                                  }
                              } else {
                                  echo "<tr><td colspan='6' class='text-center'><h6>No delivery personnel found.</h6></td></tr>";
                              }
                              ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <?php elseif ($current_tab === 'admin'): ?>
                <div class="row">
                  <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card card-rounded">
                      <div class="card-body">
                        <h4 class="card-title card-title-dash">Admin Staff Members</h4>
                        <div class="table-responsive">
                          <table class="table select-table">
                            <thead>
                              <tr>
                                <th>Admin ID</th>
                                <th>Username</th>
                                <th>Email Address</th>
                                <th>Actions (CRUD)</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php 
                              if (isset($admin_result) && $admin_result->num_rows > 0) {
                                  while ($row = $admin_result->fetch_assoc()) {
                                      echo "<tr>";
                                      echo "<td><h6>#" . htmlspecialchars($row['id']) . "</h6></td>";
                                      echo "<td><h6>" . htmlspecialchars($row['username']) . "</h6></td>";
                                      echo "<td><h6>" . htmlspecialchars($row['email']) . "</h6></td>";
                                      
                                      if (isset($_SESSION['user_id']) && $row['id'] == $_SESSION['user_id']) {
                                          echo "<td><span class='badge badge-secondary text-muted'>Current Session Account</span></td>";
                                      } else {
                                          echo "<td>
                                                  <a href='AdminEditUser.php?id=" . $row['id'] . "&role=admin' class='btn btn-primary btn-sm text-white py-1 px-3 me-2'>
                                                      <i class='fa fa-edit'></i> Edit
                                                  </a>
                                                  <a href='AdminDashboard.php?tab=admin&delete_user_id=" . $row['id'] . "&role=admin' class='btn btn-danger btn-sm text-white py-1 px-3' onclick='return confirm(\"Revoke admin dashboard credentials for this user?\")'>
                                                      <i class='fa fa-user-times'></i> Revoke
                                                  </a>
                                                </td>";
                                      }
                                      echo "</tr>";
                                  }
                              } else {
                                  echo "<tr><td colspan='4' class='text-center'><h6>No administrative accounts found.</h6></td></tr>";
                              }
                              ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <?php elseif ($current_tab === 'orders'): ?>
                <div class="row">
                  <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card card-rounded">
                      <div class="card-body">
                        <h4 class="card-title card-title-dash">Master Orders Archive</h4>
                        <div class="table-responsive">
                          <table class="table select-table">
                            <thead>
                              <tr>
                                <th>Order ID</th>
                                <th>Customer Account</th>
                                <th>Items Summary</th>
                                <th>Total Cost</th>
                                <th>Payment</th>
                                <th>Update Order Status (CRUD Update)</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php 
                              if (isset($all_orders_result) && $all_orders_result->num_rows > 0) {
                                  while ($row = $all_orders_result->fetch_assoc()) {
                                      echo "<tr>";
                                      echo "<td><h6>#MM-" . htmlspecialchars($row['id']) . "</h6></td>";
                                      echo "<td><h6>" . htmlspecialchars($row['customer']) . "</h6><p>" . htmlspecialchars($row['delivery_address']) . "</p></td>";
                                      echo "<td><p class='text-wrap fw-bold text-dark' style='max-width: 250px;'>" . htmlspecialchars($row['ordered_items'] ?? 'No items') . "</p></td>";
                                      echo "<td><h6>R " . number_format($row['total_amount'], 2) . "</h6></td>";
                                      
                                      $pay_status = strtolower($row['payment_status']);
                                      if ($pay_status === 'paid' || $pay_status === 'completed') {
                                          echo "<td><label class='badge badge-success text-white'>Paid</label></td>";
                                      } else {
                                          echo "<td><label class='badge badge-danger text-white'>" . htmlspecialchars($row['payment_status']) . "</label></td>";
                                      }

                                      echo "<td>
                                              <form method='POST' action='AdminDashboard.php?tab=orders' class='d-flex align-items-center gap-2'>
                                                  <input type='hidden' name='action' value='update_order_status'>
                                                  <input type='hidden' name='order_id' value='" . $row['id'] . "'>
                                                  <select name='order_status' class='form-control form-control-sm' style='width: 160px; padding: 5px;' onchange='this.form.submit()'>
                                                      <option value='Pending' " . (strtolower($row['order_status']) == 'pending' ? 'selected' : '') . ">Pending</option>
                                                      <option value='Prepared' " . (strtolower($row['order_status']) == 'prepared' ? 'selected' : '') . ">Prepared</option>
                                                      <option value='Ready for Pickup' " . (strtolower($row['order_status']) == 'ready for pickup' ? 'selected' : '') . ">Ready for Pickup</option>
                                                      <option value='Accepted' " . (strtolower($row['order_status']) == 'accepted' ? 'selected' : '') . ">Accepted</option>
                                                      <option value='Out for Delivery' " . (strtolower($row['order_status']) == 'out for delivery' ? 'selected' : '') . ">Out for Delivery</option>
                                                      <option value='Delivered' " . (strtolower($row['order_status']) == 'delivered' ? 'selected' : '') . ">Delivered</option>
                                                  </select>
                                              </form>
                                            </td>";
                                      echo "</tr>";
                                  }
                              } else {
                                  echo "<tr><td colspan='6' class='text-center'><h6>No orders found in transaction logs.</h6></td></tr>";
                              }
                              ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endif; ?>

              </div>
            </div>
          </div>
          <footer class="footer">
            <div class="d-sm-flex justify-content-center justify-content-sm-between">
              <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Mzansi Munch Management Framework Core Portal</span>
            </div>
          </footer>
        </div>
      </div>
    </div>
    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <script src="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script src="assets/js/off-canvas.js"></script>
    <script src="assets/js/template.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="assets/js/hoverable-collapse.js"></script>
    <script src="assets/js/jquery.cookie.js" type="text/javascript"></script>
    <script src="assets/js/dashboard.js"></script>
  </body>
</html>