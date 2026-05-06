<?php
session_start();

// Verify seller is logged in
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'seller') {
    echo json_encode(['error' => 'Unauthorized']);
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
    die(json_encode(['error' => 'Database connection failed']));
}

if (isset($_GET['order_id'])) {
    $orderId = $_GET['order_id'];

    // Now also restrict to orders belonging to this seller
    $sql = "SELECT * FROM orders WHERE order_id = ? AND seller_username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $orderId, $sellerUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $order = $result->fetch_assoc();
        echo json_encode($order);
    } else {
        echo json_encode(['error' => 'Order not found']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Order ID not provided']);
}

$conn->close();
?>
