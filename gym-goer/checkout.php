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

// Ensure only gym-goers can access
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'gym-goer') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['username'];

/* ------------------------------------------------------------------
   Fetch Cart Items from Database
   - shipping_fee is taken from the seller table via triple JOIN
------------------------------------------------------------------ */
$stmt = $conn->prepare("
    SELECT 
        c.*, 
        i.product_name, 
        i.price, 
        i.seller_username,
        s.shipping_fee
    FROM cart c
    JOIN inventory i ON c.product_id = i.product_id
    JOIN sellers s ON i.seller_username = s.username
    WHERE c.user_id = ?
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cartItems = [];
while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
}
$stmt->close();

// If cart is empty, show alert and redirect
if (empty($cartItems)) {
    echo "<p>Your cart is empty.</p>
          <script>
              alert('Your cart is empty');
              window.location.href='shop.php';
          </script>";
    exit();
}

/* ------------------------------------------------------------------
   Handle Removal from Cart
------------------------------------------------------------------ */
if (isset($_GET['remove']) && isset($_GET['product_id'])) {
    $pid = (int)$_GET['product_id'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ss", $user_id, $pid);
    $stmt->execute();
    $stmt->close();
    header("Location: checkout.php");
    exit();
}

/* ------------------------------------------------------------------
   Calculate Subtotals and Overall Total
   - Group items by seller so each seller’s order total (subtotal + shipping fee) can be stored
------------------------------------------------------------------ */
$overallSubtotal = 0;
$sellerOrders = [];  // Keyed by seller_username
foreach ($cartItems as $item) {
    $sellerUsername = $item['seller_username'];
    $price          = (float)$item['price'];
    $quantity       = (int)$item['quantity'];
    $lineTotal      = $price * $quantity;
    $overallSubtotal += $lineTotal;
    
    if (!isset($sellerOrders[$sellerUsername])) {
        $sellerOrders[$sellerUsername] = [
            'items'    => [],
            'subtotal' => 0,
            'shipping' => (float)$item['shipping_fee']
        ];
    }
    $sellerOrders[$sellerUsername]['items'][] = $item;
    $sellerOrders[$sellerUsername]['subtotal'] += $lineTotal;
}

// Overall total is the sum of each seller's (subtotal + shipping)
$overallTotal = 0;
foreach ($sellerOrders as $order) {
    $overallTotal += $order['subtotal'] + $order['shipping'];
}

/* ------------------------------------------------------------------
   Handle Payment Confirmation + Order Processing
------------------------------------------------------------------ */
if (isset($_POST['confirm_payment'])) {
    // Basic empty-check for required fields (billing address optional)
    $customer_name    = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
    $customer_email   = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';
    $customer_phone   = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
    $shipping_address = isset($_POST['shipping_address']) ? trim($_POST['shipping_address']) : '';
    $billing_address  = isset($_POST['billing_address']) ? trim($_POST['billing_address']) : '';

    if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($shipping_address)) {
        die("Please fill in all required details (billing address is optional). <a href='checkout.php'>Go back</a>");
    }

    // Validate Payment Details
    $cardNumber = isset($_POST['card_number']) ? trim($_POST['card_number']) : '';
    $expiryDate = isset($_POST['expiry_date']) ? trim($_POST['expiry_date']) : '';
    $cvv        = isset($_POST['cvv']) ? trim($_POST['cvv']) : '';

    if (!preg_match('/^[0-9]{16}$/', $cardNumber)) {
        die("Invalid card number. <a href='checkout.php'>Go back</a>");
    }
    if (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $expiryDate)) {
        die("Invalid expiry date. <a href='checkout.php'>Go back</a>");
    }
    if (!preg_match('/^[0-9]{3,4}$/', $cvv)) {
        die("Invalid CVV. <a href='checkout.php'>Go back</a>");
    }

    // Begin transaction
    $conn->begin_transaction();

    // Insert payment record (one payment for the overall total)
    $payment_method = $_POST['card_type']; // "credit" or "debit"
    $payment_status = "Completed";
    $stmt = $conn->prepare("
        INSERT INTO payments (user_id, payment_method, amount, payment_status) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("ssds", $user_id, $payment_method, $overallTotal, $payment_status);
    if (!$stmt->execute()) {
        $conn->rollback();
        die("Payment insertion failed: " . $stmt->error);
    }
    $payment_id = $conn->insert_id;
    $stmt->close();

    // For each seller, insert an order record with that seller's details
    $order_status = "Processing";
    foreach ($sellerOrders as $sellerUsername => $orderData) {
        $sellerTotal = $orderData['subtotal'] + $orderData['shipping'];
        $stmt = $conn->prepare("
            INSERT INTO orders (
                user_id, 
                seller_username,
                total_amount, 
                order_status, 
                shipping_address, 
                billing_address, 
                payment_id, 
                customer_name, 
                customer_email, 
                customer_phone
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssdsssiiss", 
            $user_id, 
            $sellerUsername,
            $sellerTotal, 
            $order_status, 
            $shipping_address, 
            $billing_address, 
            $payment_id, 
            $customer_name, 
            $customer_email, 
            $customer_phone
        );
        if (!$stmt->execute()) {
            $conn->rollback();
            die("Order insertion failed for seller " . $sellerUsername . ": " . $stmt->error);
        }
        $order_id = $conn->insert_id;
        $stmt->close();
    }

    // Update inventory stock for each cart item
    foreach ($cartItems as $cItem) {
        $pid = $cItem['product_id'];
        $qty = $cItem['quantity'];
        $updStmt = $conn->prepare("
            UPDATE inventory 
            SET stock_quantity = stock_quantity - ? 
            WHERE product_id = ? AND stock_quantity >= ?
        ");
        $updStmt->bind_param("iii", $qty, $pid, $qty);
        if (!$updStmt->execute()) {
            $conn->rollback();
            die("Inventory update failed: " . $updStmt->error);
        }
        $updStmt->close();
    }

    // Commit transaction
    $conn->commit();

    // Clear the user's cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->close();

    // Show success alert on the same page, then redirect
    echo '
    <script>
        alert("Payment Successful! Thank you for your order.");
        window.location.href = "shop.php";
    </script>
    ';
    $conn->close();
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - FitFlex</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/gym-goer.css">
    <link rel="stylesheet" href="../assets/css/shop.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap CSS for alert styling -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .checkout-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border: 1px solid #ccc;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .cart-summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .cart-summary-table th, .cart-summary-table td {
            border: 1px solid #ddd;
            padding: 10px;
        }
        .cart-summary-table th {
            background: #f2f2f2;
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
        .payment-section {
            margin-top: 20px;
        }
        .payment-form, .customer-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .payment-form input, .customer-form input, .customer-form textarea {
            padding: 8px;
            width: 100%;
            max-width: 300px;
        }
        .summary-box {
            margin-top: 20px;
            text-align: right;
        }
        .summary-box p {
            margin: 5px 0;
        }
        .confirm-payment-btn, .next-btn {
            background: #28a745;
            color: #fff;
            padding: 10px 20px;
            border: none; 
            border-radius: 5px;
            cursor: pointer;
        }
        .confirm-payment-btn:hover, .next-btn:hover {
            background: #218838;
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
        /* Billing address is optional, so not marked as required */
        .address-field {
            resize: vertical;
            height: 80px;
        }
    </style>
    <script>
        // Toggle between steps in a multi-step form
        function showStep(stepNumber) {
            document.querySelectorAll('.step').forEach(function(step) {
                step.classList.remove('active');
            });
            document.getElementById('step' + stepNumber).classList.add('active');
        }

        // Validate all required fields in Step 1 before proceeding
        function validateStep1() {
            var requiredFields = ["customer_name", "customer_email", "customer_phone", "shipping_address"];
            for (var i = 0; i < requiredFields.length; i++) {
                var field = document.getElementById(requiredFields[i]);
                if (field.value.trim() === "") {
                    alert("Please fill out all required fields in Step 1.");
                    field.focus();
                    return false;
                }
            }
            return true;
        }

        // Modified function to check validation before moving to next step
        function nextStep() {
            if (validateStep1()) {
                showStep(2);
            }
        }

        window.onload = function() {
            showStep(1); // start with customer details
        }

        // Confirmation dialog for payment submission.
        function confirmPayment() {
            return confirm("Are you sure you want to confirm payment?");
        }
    </script>
</head>
<body>
<div class="checkout-container">
    <a href="shop.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Shop</a>
    <h2>Checkout Process</h2>
    
    <!-- Cart Summary -->
    <table class="cart-summary-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Price (Each)</th>
                <th>Quantity</th>
                <th>Line Total</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($cartItems as $item): 
            $pName = htmlspecialchars($item['product_name']);
            $pPrice = (float)$item['price'];
            $qty = (int)$item['quantity'];
            $lineTotal = $pPrice * $qty;
        ?>
            <tr>
                <td><?php echo $pName; ?></td>
                <td>$<?php echo number_format($pPrice, 2); ?></td>
                <td><?php echo $qty; ?></td>
                <td>$<?php echo number_format($lineTotal, 2); ?></td>
                <td>
                    <a href="checkout.php?remove=1&product_id=<?php echo $item['product_id']; ?>" 
                       class="remove-btn">Remove</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="summary-box">
        <?php
        // For display, show overall totals
        $overallShipping = $overallTotal - $overallSubtotal;
        ?>
        <p><span>Subtotal:</span> <strong>$<?php echo number_format($overallSubtotal, 2); ?></strong></p>
        <p><span>Shipping:</span> <strong>$<?php echo number_format($overallShipping, 2); ?></strong></p>
        <p><span>Total:</span> <strong>$<?php echo number_format($overallTotal, 2); ?></strong></p>
    </div>
    
    <!-- Multi-Step Form -->
    <form method="POST" action="checkout.php" onsubmit="return confirmPayment();">
        <!-- Step 1: Customer Details -->
        <div id="step1" class="step">
            <h3>Step 1: Enter Your Details</h3>
            <div class="customer-form">
                <label for="customer_name">Full Name</label>
                <input type="text" name="customer_name" id="customer_name" placeholder="Enter your full name" required>
                
                <label for="customer_email">Email Address</label>
                <input type="email" name="customer_email" id="customer_email" placeholder="Enter your email" required>
                
                <label for="customer_phone">Phone Number</label>
                <input type="text" name="customer_phone" id="customer_phone" placeholder="Enter your phone number" required>
                
                <label for="shipping_address">Shipping Address</label>
                <textarea name="shipping_address" id="shipping_address" class="address-field" 
                          placeholder="Enter your shipping address" required></textarea>
                
                <label for="billing_address">Billing Address (optional)</label>
                <textarea name="billing_address" id="billing_address" class="address-field" 
                          placeholder="Enter your billing address"></textarea>
            </div>
            <button type="button" class="next-btn" onclick="nextStep()">Continue to Payment</button>
        </div>
        
        <!-- Step 2: Payment Details -->
        <div id="step2" class="step">
            <h3>Step 2: Payment Details</h3>
            <div class="payment-form">
                <div class="payment-methods">
                    <label>
                        <input type="radio" name="card_type" value="credit" checked> Credit Card
                    </label>
                    <label>
                        <input type="radio" name="card_type" value="debit"> Debit Card
                    </label>
                </div>
                <label for="card_number">Card Number</label>
                <input type="text" name="card_number" id="card_number" placeholder="XXXX XXXX XXXX XXXX" 
                       required pattern="[0-9]{16}" title="16 digit card number (numbers only)">
                
                <label for="expiry_date">Expiry Date (MM/YY)</label>
                <input type="text" name="expiry_date" id="expiry_date" placeholder="MM/YY" 
                       required pattern="(0[1-9]|1[0-2])\/[0-9]{2}" title="Format MM/YY, e.g. 07/25">
                
                <label for="cvv">CVV</label>
                <input type="text" name="cvv" id="cvv" placeholder="XXX" 
                       required pattern="[0-9]{3,4}" title="3 or 4 digit CVV code">
            </div>
            <button type="submit" name="confirm_payment" class="confirm-payment-btn">Confirm Payment</button>
        </div>
    </form>
</div>
</body>
</html>
