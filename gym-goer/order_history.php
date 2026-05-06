<?php
session_start();

$servername    = "localhost";
$db_username   = "root";
$db_password   = "";
$dbname        = "gym";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure only gym goers can access the page.
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'gym-goer') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['username'];

// Query orders table for orders placed by the logged-in gym goer
$stmt = $conn->prepare("
    SELECT order_id, order_date, total_amount, order_status, 
           shipping_address, customer_name, customer_email, 
           customer_phone, tracking_number, notes
    FROM orders
    WHERE user_id = ?
    ORDER BY order_date DESC
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orderHistory = [];
while ($row = $result->fetch_assoc()) {
    $orderHistory[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History - FitFlex</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/gym-goer.css">
    <link rel="stylesheet" href="../assets/css/shop.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap CSS for table styling -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .member-dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            background: black;
        }
        .dashboard-header h2 {
            margin-bottom: 20px;
        }
        .order-history-table {
            width: 100%;
            border-collapse: collapse;
            background: #4F4F4F;
        }
        .order-history-table th,
        .order-history-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .order-history-table th {
            background: #4F4F4F;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="member-dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Member Dashboard</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
            <a href="meal-plans.php"><i class="fas fa-utensils"></i> Meal Plans</a>
            <a href="workouts.php"><i class="fas fa-dumbbell"></i> Workouts</a>
            <a href="shop.php"><i class="fas fa-shopping-cart"></i> Shop</a>
            <a href="order_history.php" class="active"><i class="fas fa-history"></i> Order History</a>
            <a href="record-visit.php"><i class="fa-solid fa-person-walking"></i></i> Gym Visit</a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header class="dashboard-header">
            <h2>Order History</h2>
        </header>
        <?php if (empty($orderHistory)): ?>
            <p>No orders found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="order-history-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Order Date</th>
                            <th>Total Amount</th>
                            <th>Order Status</th>
                            <th>Shipping Address</th>
                            <th>Customer Name</th>
                            <th>Customer Email</th>
                            <th>Customer Phone</th>
                            <th>Tracking Number</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderHistory as $order): 
                            // Provide a default of 'N/A' if the column is null
                            $order_id        = $order['order_id']        ?? 'N/A';
                            $order_date      = $order['order_date']      ?? 'N/A';
                            $total_amount    = $order['total_amount']    ?? 'N/A';
                            $order_status    = $order['order_status']    ?? 'N/A';
                            $shipping_address= $order['shipping_address']?? 'N/A';
                            $customer_name   = $order['customer_name']   ?? 'N/A';
                            $customer_email  = $order['customer_email']  ?? 'N/A';
                            $customer_phone  = $order['customer_phone']  ?? 'N/A';
                            $tracking_number = $order['tracking_number'] ?? 'N/A';
                            $notes           = $order['notes']           ?? 'N/A';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order_id); ?></td>
                            <td><?php echo htmlspecialchars($order_date); ?></td>
                            <td>
                                <?php 
                                    if ($total_amount !== 'N/A') {
                                        // numeric format
                                        echo 'Rs.' . number_format((float)$total_amount, 2);
                                    } else {
                                        echo 'N/A';
                                    }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($order_status); ?></td>
                            <td><?php echo htmlspecialchars($shipping_address); ?></td>
                            <td><?php echo htmlspecialchars($customer_name); ?></td>
                            <td><?php echo htmlspecialchars($customer_email); ?></td>
                            <td><?php echo htmlspecialchars($customer_phone); ?></td>
                            <td><?php echo htmlspecialchars($tracking_number); ?></td>
                            <td><?php echo htmlspecialchars($notes); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
