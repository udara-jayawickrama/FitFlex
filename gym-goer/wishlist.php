<?php
session_start();

// ------------------------------------------------------------------
// Database & User Check
// ------------------------------------------------------------------
$servername    = "localhost";
$db_username   = "root";
$db_password   = "";
$dbname        = "gym";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Only gym-goers may access.
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'gym-goer') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['username'];

// ------------------------------------------------------------------
// Handle Removal from Wishlist in Database
// ------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['product_id'])) {
    $pid = (int)$_GET['product_id'];
    $delStmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $delStmt->bind_param("si", $user_id, $pid);
    $delStmt->execute();
    $delStmt->close();

    // After removing, we’ll check if the wishlist is now empty.
    $checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM wishlist WHERE user_id = ?");
    $checkStmt->bind_param("s", $user_id);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    $row = $checkRes->fetch_assoc();
    $checkStmt->close();

    if ((int)$row['cnt'] === 0) {
        // If empty, show an alert and redirect to shop
        echo "<script>
                alert('Your Wishlist is empty.');
                window.location.href = 'shop.php';
              </script>";
        exit();
    } else {
        // If not empty, just reload wishlist page
        header("Location: wishlist.php");
        exit();
    }
}

// ------------------------------------------------------------------
// Fetch wishlist product data from DB
// ------------------------------------------------------------------
$sql = "
    SELECT i.* 
    FROM wishlist w
    JOIN inventory i ON w.product_id = i.product_id
    WHERE w.user_id = ?
    ORDER BY i.product_name ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$wishlistItems = [];
while ($row = $result->fetch_assoc()) {
    $wishlistItems[] = $row;
}
$stmt->close();

// If the wishlist is empty, show alert and redirect to shop
if (empty($wishlistItems)) {
    echo "<script>
            alert('Your Wishlist is empty.');
            window.location.href = 'shop.php';
          </script>";
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Wishlist - FitFlex</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/gym-goer.css">
    <link rel="stylesheet" href="../assets/css/shop.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .wishlist-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #ccc;
        }
        .wishlist-table {
            width: 100%;
            border-collapse: collapse;
        }
        .wishlist-table th, .wishlist-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .wishlist-table th {
            background: #f2f2f2;
        }
        .back-btn {
            background: #555;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background: #333;
        }
        .remove-btn {
            background: #e74c3c;
            color: #fff;
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .remove-btn:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
<div class="wishlist-container">
    <a href="shop.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Shop</a>
    <h2>My Wishlist</h2>
    <table class="wishlist-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Product Name</th>
                <th>Price</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($wishlistItems as $item): ?>
            <tr>
                <td>
                    <img src="<?php echo htmlspecialchars($item['image_path'] ?: '../assets/images/product-placeholder.jpg'); ?>"
                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                         style="max-width:80px;">
                </td>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td>$<?php echo number_format($item['price'], 2); ?></td>
                <td>
                    <a href="wishlist.php?action=remove&product_id=<?php echo $item['product_id']; ?>" 
                       class="remove-btn">Remove</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>

