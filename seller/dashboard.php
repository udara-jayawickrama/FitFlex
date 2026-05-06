<?php 
session_start();

// Check if seller is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php"); // Redirect to login page
    exit();
}

$sellerUsername = $_SESSION['username'];

// Database connection settings
$servername    = "localhost";
$db_username   = "root";
$db_password   = "";
$dbname        = "gym";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$msg = "";
$error = "";

// Load the seller's current shipping fee from the database
$sqlGetShipping = "SELECT shipping_fee FROM sellers WHERE username = ?";
$stmtGet = $conn->prepare($sqlGetShipping);
$stmtGet->bind_param("s", $sellerUsername);
$stmtGet->execute();
$resultGet = $stmtGet->get_result();
if ($resultGet && $resultGet->num_rows > 0) {
    $row = $resultGet->fetch_assoc();
    // If shipping_fee is not set (NULL), leave it empty
    $shippingFee = ($row['shipping_fee'] !== null) ? $row['shipping_fee'] : "";
} else {
    $shippingFee = "";
}
$stmtGet->close();

// Update shipping fee if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['shipping_fee'])) {
    // Remove any leading/trailing whitespace; allow empty string
    $shippingFeeInput = trim($_POST['shipping_fee']);
    
    // If input is not empty, use the value; else, set to NULL
    if ($shippingFeeInput !== "") {
        $shippingFee = $shippingFeeInput;
    } else {
        $shippingFee = null;
    }
    
    $stmtUpdate = $conn->prepare("UPDATE sellers SET shipping_fee = ? WHERE username = ?");
    // Use "d" for a double value if not null.
    $stmtUpdate->bind_param("ds", $shippingFee, $sellerUsername);
    if ($stmtUpdate->execute()) {
        $msg = "Shipping fee updated to " . ($shippingFee !== null ? "Rs." . htmlspecialchars($shippingFee) : "empty");
    } else {
        $error = "Error updating shipping fee: " . $stmtUpdate->error;
    }
    $stmtUpdate->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Seller Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status.pending { 
            background-color: #ffc107; 
            color: white; 
            padding: 0.2em 0.6em; 
            border-radius: 5px; 
        }
        .status.processing { 
            background-color: #007bff; 
            color: white; 
            padding: 0.2em 0.6em; 
            border-radius: 5px; 
        }
        .status.shipped { 
            background-color: #28a745; 
            color: white; 
            padding: 0.2em 0.6em; 
            border-radius: 5px; 
        }
        .status.delivered { 
            background-color: #17a2b8; 
            color: white; 
            padding: 0.2em 0.6em; 
            border-radius: 5px; 
        }
        .status.cancelled { 
            background-color: #dc3545; 
            color: white; 
            padding: 0.2em 0.6em; 
            border-radius: 5px; 
        }
        .shipping-fee-form {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #444444;
        }
        .shipping-fee-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .shipping-fee-form input[type="number"] {
            width: 100px;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .shipping-fee-form button {
            padding: 8px 15px;
            background-color: #5cb85c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .shipping-fee-form button:hover {
            background-color: #4cae4c;
        }
        .success-message {
            color: green;
            margin-top: 10px;
        }
        .error-message {
            color: red;
            margin-top: 10px;
        }
        /* Additional styling for stats grid */
        .stats-grid {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            flex: 1;
            background: #f1f1f1;
            padding: 15px;
            border-radius: 6px;
            display: flex;
            align-items: center;
        }
        .stat-icon {
            font-size: 40px;
            margin-right: 15px;
            color: #333;
        }
        .stat-info h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .stat-info p {
            margin: 5px 0 0;
            font-size: 16px;
            color: #555;
        }
        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }
        .table-container th, 
        .table-container td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table-container th {
            background-color: black;
            color: white;
        }
    </style>
</head>
<body>
    <div class="seller-dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Seller Dashboard</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="overview.php" class="active">
                    <i class="fas fa-chart-line"></i>
                    Overview
                </a>
                <a href="inventory.php">
                    <i class="fas fa-box"></i>
                    Inventory
                </a>
                <a href="orders.php">
                    <i class="fas fa-shopping-cart"></i>
                    Orders
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <div class="main-content">
            <header class="dashboard-header">
                <h2>Dashboard Overview</h2>
                <div class="header-actions"></div>
            </header>

            <div class="stats-grid">
                <!-- Total Products: only count items added by the logged in seller -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Products</h3>
                        <?php
                        $sqlTotalProducts = "SELECT COUNT(*) as total_products FROM inventory WHERE seller_username = ?";
                        $stmtTotalProducts = $conn->prepare($sqlTotalProducts);
                        $stmtTotalProducts->bind_param("s", $sellerUsername);
                        $stmtTotalProducts->execute();
                        $resultTotalProducts = $stmtTotalProducts->get_result();
                        if ($resultTotalProducts && $resultTotalProducts->num_rows > 0) {
                            $rowTotalProducts = $resultTotalProducts->fetch_assoc();
                            echo "<p>" . htmlspecialchars($rowTotalProducts['total_products']) . "</p>";
                        } else {
                            echo "<p>0</p>";
                        }
                        $stmtTotalProducts->close();
                        ?>
                    </div>
                </div>
                <!-- Orders: count orders belonging to the seller -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Orders</h3>
                        <?php
                        $sqlTotalOrders = "SELECT COUNT(*) as total_orders FROM orders WHERE seller_username = ?";
                        $stmtTotalOrders = $conn->prepare($sqlTotalOrders);
                        $stmtTotalOrders->bind_param("s", $sellerUsername);
                        $stmtTotalOrders->execute();
                        $resultTotalOrders = $stmtTotalOrders->get_result();
                        if ($resultTotalOrders && $resultTotalOrders->num_rows > 0) {
                            $rowTotalOrders = $resultTotalOrders->fetch_assoc();
                            echo "<p>" . htmlspecialchars($rowTotalOrders['total_orders']) . "</p>";
                        } else {
                            echo "<p>0</p>";
                        }
                        $stmtTotalOrders->close();
                        ?>
                    </div>
                </div>
                <!-- Revenue: sum total_amount for Delivered orders belonging to the seller -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Revenue</h3>
                        <?php
                        $sqlTotalRevenue = "SELECT SUM(total_amount) as total_revenue FROM orders WHERE order_status = 'Delivered' AND seller_username = ?";
                        $stmtTotalRevenue = $conn->prepare($sqlTotalRevenue);
                        $stmtTotalRevenue->bind_param("s", $sellerUsername);
                        $stmtTotalRevenue->execute();
                        $resultTotalRevenue = $stmtTotalRevenue->get_result();
                        if ($resultTotalRevenue && $resultTotalRevenue->num_rows > 0) {
                            $rowTotalRevenue = $resultTotalRevenue->fetch_assoc();
                            $total_revenue = ($rowTotalRevenue['total_revenue'] !== null) ? $rowTotalRevenue['total_revenue'] : 0;
                            echo "<p>Rs." . htmlspecialchars(number_format($total_revenue, 2)) . "</p>";
                        } else {
                            echo "<p>Rs.0.00</p>";
                        }
                        $stmtTotalRevenue->close();
                        ?>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <div class="recent-orders">
                    <div class="section-header">
                        <h3>Recent Orders</h3>
                        <a href="orders.php" class="view-all">View All</a>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sqlRecentOrders = "SELECT order_id, customer_name, total_amount, order_status 
                                                    FROM orders 
                                                    WHERE seller_username = ? 
                                                    ORDER BY order_date DESC LIMIT 5";
                                $stmtRecentOrders = $conn->prepare($sqlRecentOrders);
                                $stmtRecentOrders->bind_param("s", $sellerUsername);
                                $stmtRecentOrders->execute();
                                $resultRecentOrders = $stmtRecentOrders->get_result();
                                if ($resultRecentOrders && $resultRecentOrders->num_rows > 0) {
                                    while ($rowRecentOrder = $resultRecentOrders->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($rowRecentOrder['order_id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($rowRecentOrder['customer_name']) . "</td>";
                                        echo "<td>Rs." . htmlspecialchars(number_format($rowRecentOrder['total_amount'], 2)) . "</td>";
                                        echo "<td><span class='status " . htmlspecialchars(strtolower($rowRecentOrder['order_status'])) . "'>" . htmlspecialchars($rowRecentOrder['order_status']) . "</span></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4'>No recent orders found.</td></tr>";
                                }
                                $stmtRecentOrders->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="low-stock">
                    <div class="section-header">
                        <h3>Low Stock Alert</h3>
                    </div>
                    <div class="alert-list">
                        <?php
                        $lowStockThreshold = 10; // Threshold value for low stock
                        $sqlLowStock = "SELECT product_name, stock_quantity FROM inventory WHERE stock_quantity <= ? AND seller_username = ?";
                        $stmtLowStock = $conn->prepare($sqlLowStock);
                        $stmtLowStock->bind_param("is", $lowStockThreshold, $sellerUsername);
                        $stmtLowStock->execute();
                        $resultLowStock = $stmtLowStock->get_result();
                        if ($resultLowStock->num_rows > 0) {
                            while ($rowLowStock = $resultLowStock->fetch_assoc()) {
                                echo "<div class='alert-item'>";
                                echo "<div class='alert-info'>";
                                echo "<h4>" . htmlspecialchars($rowLowStock['product_name']) . "</h4>";
                                echo "<p>Only " . htmlspecialchars($rowLowStock['stock_quantity']) . " units left</p>";
                                echo "</div>";
                                echo "<a href='inventory.php' class='restock-btn'>View Inventory</a>";
                                echo "</div>";
                            }
                        } else {
                            echo "<p>No low stock items.</p>";
                        }
                        $stmtLowStock->close();
                        ?>
                    </div>
                </div>

                <div class="shipping-fee-form">
                    <h3>Shipping Fee</h3>
                    <?php if (!empty($msg)): ?>
                        <p class="success-message"><?php echo htmlspecialchars($msg); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                        <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                    <form method="post" action="dashboard.php">
                        <label for="shipping_fee">Set Shipping Fee:</label>
                        <input type="number" step="0.01" name="shipping_fee" id="shipping_fee" value="<?php echo htmlspecialchars($shippingFee); ?>">
                        <button type="submit">Update Fee</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
