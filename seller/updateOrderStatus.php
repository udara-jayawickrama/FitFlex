<?php
session_start();

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if seller is logged in

    // Sanitize and validate order ID
    $orderId = (int)$_POST['order_id'];
    if ($orderId <= 0) {
        die(json_encode(['error' => 'Invalid order ID']));
    }

    $status = $_POST['status'];
    $trackingNumber = isset($_POST['tracking_number']) ? $_POST['tracking_number'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    // Update query with seller validation
    $sql = "UPDATE orders 
            SET order_status = ?, tracking_number = ?, notes = ? 
            WHERE order_id = ? AND seller_username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssis", $status, $trackingNumber, $notes, $orderId, $sellerUsername);

    if ($stmt->execute()) {
        // Check if any rows were affected
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => 'Order status updated successfully']);
        } else {
            echo json_encode(['error' => 'No order found or unauthorized update']);
        }
    } else {
        echo json_encode(['error' => 'Error updating order status: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Invalid request method']);
}

$conn->close();
?>