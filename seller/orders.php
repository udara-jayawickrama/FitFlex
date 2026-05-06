<?php 
session_start();

// Check if seller is logged in
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'seller') {
    header("Location: ../index.php"); // Redirect to login page
    exit();
}

$username = $_SESSION['username'];
$msg = "";
$error = "";

// Get selected status from filter
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : 'all';

// Fetch orders from the database for the logged-in seller only
if ($selectedStatus !== 'all') {
    $sql = "SELECT * FROM orders WHERE seller_username = ? AND order_status = ?";
} else {
    $sql = "SELECT * FROM orders WHERE seller_username = ?";
}

$conn = new mysqli("localhost", "root", "", "gym");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($selectedStatus !== 'all') {
    $stmtPrepared = $conn->prepare($sql);
    $stmtPrepared->bind_param("ss", $username, $selectedStatus);
} else {
    $stmtPrepared = $conn->prepare($sql);
    $stmtPrepared->bind_param("s", $username);
}
$stmtPrepared->execute();
$result = $stmtPrepared->get_result();
$orders = [];
if ($result) {
    if ($result->num_rows > 0) {
        $orders = $result->fetch_all(MYSQLI_ASSOC);
    }
}
$stmtPrepared->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Orders Management</title>
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
    </style>
</head>
<body>
    <div class="seller-dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Seller Dashboard</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php">
                    <i class="fas fa-chart-line"></i>
                    Overview
                </a>
                <a href="inventory.php">
                    <i class="fas fa-box"></i>
                    Inventory
                </a>
                <a href="orders.php" class="active">
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
                <h2>Orders Management</h2>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search orders..." id="searchOrders">
                    </div>
                    <select class="filter-select" id="statusFilter" onchange="filterOrders(this.value)">
                        <option value="all" <?php if ($selectedStatus === 'all') echo 'selected'; ?>>All Status</option>
                        <option value="pending" <?php if ($selectedStatus === 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="processing" <?php if ($selectedStatus === 'processing') echo 'selected'; ?>>Processing</option>
                        <option value="shipped" <?php if ($selectedStatus === 'shipped') echo 'selected'; ?>>Shipped</option>
                        <option value="delivered" <?php if ($selectedStatus === 'delivered') echo 'selected'; ?>>Delivered</option>
                        <option value="cancelled" <?php if ($selectedStatus === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                    </select>
                </div>
            </header>

            <div class="content-section">
                <div class="orders-table-container">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr><td colspan="6">No orders found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>">
                                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                        <td class="customer-info">
                                            <div>
                                                <span class="customer-name"><?php echo htmlspecialchars($order['customer_name'] ?? ''); ?></span>
                                                <span class="customer-email"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                                        <td>Rs.<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></td>
                                        <td><span class="status <?php echo htmlspecialchars(strtolower($order['order_status'])); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="view-btn" title="View Details" onclick="openOrderModal('<?php echo htmlspecialchars($order['order_id']); ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="process-btn" title="Process Order" onclick="openProcessModal('<?php echo htmlspecialchars($order['order_id']); ?>')">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="orderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Order Details</h3>
                <button class="close-btn" onclick="closeModal('orderModal')">&times;</button>
            </div>
            <div class="modal-body" id="orderModalBody">
            </div>
        </div>
    </div>

    
    <div class="modal" id="processModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Process Order</h3>
                <button class="close-btn" onclick="closeModal('processModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form class="process-form" id="processOrderForm">
                    <input type="hidden" id="orderIdToProcess" name="order_id">
                    <div class="form-group">
                        <label>Update Status</label>
                        <select name="status" required>
                            <option value="">Select status</option>
                            <option value="Processing">Processing</option>
                            <option value="Shipped">Shipped</option>
                            <option value="Delivered">Delivered</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tracking Number (optional)</label>
                        <input type="text" placeholder="Enter tracking number" name="tracking_number">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea rows="3" placeholder="Add notes about the order" name="notes"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="save-btn">Update Order</button>
                        <button type="button" class="cancel-btn" onclick="closeModal('processModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openOrderModal(orderId) {
            const orderModal = document.getElementById('orderModal');
            const orderModalBody = document.getElementById('orderModalBody');

            orderModalBody.innerHTML = '<p>Loading order details...</p>';
            orderModal.style.display = 'block';

            fetch(`getOrderDetails.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        orderModalBody.innerHTML = `<p class="error">${data.error}</p>`;
                    } else {
                        orderModalBody.innerHTML = `
                            <div class="order-info">
                                <div class="info-section">
                                    <h4>Order Information</h4>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <label>Order ID</label>
                                            <p>${data.order_id}</p>
                                        </div>
                                        <div class="info-item">
                                            <label>Order Date</label>
                                            <p>${data.order_date}</p>
                                        </div>
                                        <div class="info-item">
                                            <label>Status</label>
                                            <p><span class="status ${data.order_status.toLowerCase()}">${data.order_status}</span></p>
                                        </div>
                                        <div class="info-item">
                                            <label>Total Amount</label>
                                            <p>Rs.${parseFloat(data.total_amount).toFixed(2)}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-section">
                                    <h4>Customer Details</h4>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <label>Name</label>
                                            <p>${data.customer_name}</p>
                                        </div>
                                        <div class="info-item">
                                            <label>Email</label>
                                            <p>${data.customer_email}</p>
                                        </div>
                                        <div class="info-item">
                                            <label>Phone</label>
                                            <p>${data.customer_phone}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-section">
                                    <h4>Shipping Address</h4>
                                    <p>${data.shipping_address}</p>
                                </div>

                                <div class="info-section">
                                    <h4>Billing Address</h4>
                                    <p>${data.billing_address}</p>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    orderModalBody.innerHTML = `<p class="error">Error fetching order details.</p>`;
                    console.error('Error:', error);
                });
        }

        function openProcessModal(orderId) {
            const processModal = document.getElementById('processModal');
            const orderIdInput = document.getElementById('orderIdToProcess');
            const statusDropdown = processModal.querySelector('select[name="status"]');
            const trackingNumberInput = processModal.querySelector('input[name="tracking_number"]');
            const notesTextarea = processModal.querySelector('textarea[name="notes"]');

            // Reset form to editable state
            statusDropdown.disabled = false;
            trackingNumberInput.readOnly = false;
            notesTextarea.readOnly = false;
            processModal.querySelector('.save-btn').disabled = false;

            // Fetch current order details
            fetch(`getOrderDetails.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    // Populate existing values
                    trackingNumberInput.value = data.tracking_number || '';
                    notesTextarea.value = data.notes || '';

                    const currentStatus = data.order_status.toLowerCase();
                    statusDropdown.innerHTML = '<option value="">Select status</option>';

                    // Available status transitions
                    const statuses = ["Processing", "Shipped", "Delivered"];
                    
                    if (currentStatus === 'delivered') {
                        // Disable all fields for delivered status
                        statusDropdown.innerHTML = '<option value="Delivered" selected>Delivered</option>';
                        statusDropdown.disabled = true;
                        trackingNumberInput.readOnly = true;
                        notesTextarea.readOnly = true;
                        processModal.querySelector('.save-btn').disabled = true;
                    } else {
                        // Filter out current status and populate options
                        statuses.forEach(status => {
                            if (status.toLowerCase() !== currentStatus) {
                                const option = document.createElement('option');
                                option.value = status;
                                option.textContent = status;
                                statusDropdown.appendChild(option);
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading order details');
                });

            orderIdInput.value = orderId;
            processModal.style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        document.getElementById('processOrderForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const orderId = document.getElementById('orderIdToProcess').value;
            const status = this.querySelector('select[name="status"]').value;
            const trackingNumber = this.querySelector('input[name="tracking_number"]').value;
            const notes = this.querySelector('textarea[name="notes"]').value;

            fetch('updateOrderStatus.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${orderId}&status=${encodeURIComponent(status)}&tracking_number=${encodeURIComponent(trackingNumber)}&notes=${encodeURIComponent(notes)}`,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.success);
                    closeModal('processModal');
                    window.location.reload();
                } else if (data.error) {
                    alert(data.error);
                } else {
                    alert('An unexpected error occurred.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating order status.');
            });
        });

        function filterOrders(status) {
            window.location.href = 'orders.php?status=' + status;
        }
    </script>
</body>
</html>