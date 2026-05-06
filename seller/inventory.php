<?php
session_start();

// Database connection settings
$servername    = "localhost";
$db_username   = "root";
$db_password   = "";
$dbname        = "gym";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if seller is logged in (adjust user_type as needed)
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'seller') {
    header("Location: ../index.php");
    exit();
}

$sellerUsername = $_SESSION['username'];

// --- Handle Add New Product ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $product_name   = trim($_POST['product_name']);
    $category       = trim($_POST['category']);
    $price          = trim($_POST['price']);
    $stock_quantity = trim($_POST['stock_quantity']);
    $description    = trim($_POST['description']);
    
    // Process file upload if available
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] == 0) {
        $targetDir    = "../assets/images/";
        $fileName     = basename($_FILES['productImage']['name']);
        $ext          = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName  = uniqid("product_", true) . "." . $ext;
        $targetFilePath = $targetDir . $newFileName;
        if (move_uploaded_file($_FILES['productImage']['tmp_name'], $targetFilePath)) {
            $image_path = $targetFilePath;
        } else {
            $image_path = "../assets/images/product-placeholder.jpg";
        }
    } else {
        $image_path = "../assets/images/product-placeholder.jpg";
    }

    // Include seller_username in the INSERT query
    $insertSql = "INSERT INTO inventory (product_name, category, price, stock_quantity, description, image_path, seller_username) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param("ssdisss", $product_name, $category, $price, $stock_quantity, $description, $image_path, $sellerUsername);
    if ($stmt->execute()) {
        $msg = "Product added successfully!";
    } else {
        $error = "Error adding product: " . $stmt->error;
    }
    $stmt->close();
    // Redirect to avoid form re-submission and pass message via GET
    if (!empty($msg)) {
        header("Location: inventory.php?msg=" . urlencode($msg));
    } elseif (!empty($error)) {
        header("Location: inventory.php?error=" . urlencode($error));
    }
    exit();
}

// --- Handle Edit Product ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_product'])) {
    $product_id     = trim($_POST['product_id']);
    $product_name   = trim($_POST['product_name']);
    $category       = trim($_POST['category']);
    $price          = trim($_POST['price']);
    $stock_quantity = trim($_POST['stock_quantity']);
    $description    = trim($_POST['description']);

    // Process file upload if a new image is provided
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] == 0) {
        $targetDir    = "../assets/images/";
        $fileName     = basename($_FILES['productImage']['name']);
        $ext          = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName  = uniqid("product_", true) . "." . $ext;
        $targetFilePath = $targetDir . $newFileName;
        if (move_uploaded_file($_FILES['productImage']['tmp_name'], $targetFilePath)) {
            $image_path = $targetFilePath;
        }
    }
    
    // If a new image was uploaded, update the image_path too
    if (isset($image_path) && !empty($image_path)) {
        $updateSql = "UPDATE inventory SET product_name=?, category=?, price=?, stock_quantity=?, description=?, image_path=? WHERE product_id=? AND seller_username=?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("ssdissis", $product_name, $category, $price, $stock_quantity, $description, $image_path, $product_id, $sellerUsername);
    } else {
        $updateSql = "UPDATE inventory SET product_name=?, category=?, price=?, stock_quantity=?, description=? WHERE product_id=? AND seller_username=?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("ssdisis", $product_name, $category, $price, $stock_quantity, $description, $product_id, $sellerUsername);
    }
    
    if ($stmt->execute()) {
        $msg = "Product updated successfully!";
    } else {
        $error = "Error updating product: " . $stmt->error;
    }
    $stmt->close();
    if (!empty($msg)) {
        header("Location: inventory.php?msg=" . urlencode($msg));
    } elseif (!empty($error)) {
        header("Location: inventory.php?error=" . urlencode($error));
    }
    exit();
}

// --- Fetch Products from Database ---
// Only select products added by the logged in seller
$sql = "SELECT * FROM inventory WHERE seller_username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $sellerUsername);
$stmt->execute();
$productsResult = $stmt->get_result();
$products = [];
if ($productsResult && $productsResult->num_rows > 0) {
    while ($row = $productsResult->fetch_assoc()) {
        $products[] = $row;
    }
}
$stmt->close();

// --- Handle Delete Product ---
if (isset($_GET['delete_id'])) {
    $product_id = trim($_GET['delete_id']);
    // Only delete if the product belongs to the logged in seller
    $deleteSql = "DELETE FROM inventory WHERE product_id=? AND seller_username=?";
    $stmt = $conn->prepare($deleteSql);
    $stmt->bind_param("is", $product_id, $sellerUsername);
    if ($stmt->execute()) {
        $msg = "Product deleted successfully!";
    } else {
        $error = "Error deleting product: " . $stmt->error;
    }
    $stmt->close();
    header("Location: inventory.php?msg=" . urlencode($msg));
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Inventory Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Additional inline CSS for modal (customize as needed) */
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.4); 
        }
        .modal-content { 
            background-color: black; 
            margin: 15% auto; 
            padding: 20px; 
            border: 1px solid #888; 
            width: 80%; 
        }
        .close-btn { 
            color: #aaa; 
            float: right; 
            font-size: 28px; 
            font-weight: bold; 
        }
        .close-btn:hover, .close-btn:focus { 
            color: black; 
            text-decoration: none; 
            cursor: pointer; 
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
        }
        .form-group input[type="text"], 
        .form-group input[type="number"], 
        .form-group select, 
        .form-group textarea { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }
        .form-actions button { 
            padding: 10px 15px; 
            margin-right: 5px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
        }
        .save-btn { 
            background-color: #5cb85c; 
            color: white; 
        }
        .cancel-btn { 
            background-color: #f44336; 
            color: white; 
        }
        .header-actions button.add-product-btn { 
            padding: 10px 15px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            background-color: red; 
            color: white; 
        }
        .product-actions button { 
            background: none;
            border: none; 
            cursor: pointer; 
            margin-left: 5px; 
        }
        .message { 
            margin: 10px 0; 
            padding: 10px; 
            border-radius: 5px; 
        }
        .success-message { 
            background-color: #d4edda; 
            color: #155724; 
        }
        .error-message { 
            background-color: #f8d7da; 
            color: #721c24; 
        }
        .delete-modal .modal-content { 
            width: 40%; 
        }
        .warning-icon { 
            font-size: 40px; 
            color: #ffc107; 
            display: block; 
            text-align: center; 
            margin-bottom: 10px; 
        }
        .button-group { 
            text-align: center; 
            margin-top: 20px; 
        }
        .confirm-delete { 
            background-color: #dc3545; 
            color: white; 
        }
    </style>
</head>
<body>
    <div class="seller-dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Seller Dashboard</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php">
                    <i class="fas fa-chart-line"></i>
                    Overview
                </a>
                <a href="inventory.php" class="active">
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
                <h2>Inventory Management</h2>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search products..." id="searchProducts">
                    </div>
                    <select class="filter-select" id="categoryFilter" style="background-color: black;">
                        <option value="all">All Categories</option>
                        <option value="supplements">Supplements</option>
                        <option value="equipment">Equipment</option>
                        <option value="apparel">Apparel</option>
                    </select>
                    <button class="add-product-btn" id="addProductBtn">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </div>
            </header>

            <div class="content-section">
                <!-- Show success or error message from GET parameters -->
                <?php if (isset($_GET['msg'])): ?>
                    <div class="message success-message"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="message error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>

                <div class="products-grid">
                    <?php if (empty($products)): ?>
                        <p>No products in inventory.</p>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-card" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>">
                                <div class="product-image">
                                    <!-- Display the image path from database -->
                                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                    <div class="product-actions">
                                        <button class="edit-btn" title="Edit Product" onclick='openEditModal(<?php echo json_encode($product["product_id"]); ?>, <?php echo json_encode($product["product_name"]); ?>, <?php echo json_encode($product["category"]); ?>, <?php echo json_encode($product["price"]); ?>, <?php echo json_encode($product["stock_quantity"]); ?>, <?php echo json_encode($product["description"]); ?>, <?php echo json_encode($product["image_path"]); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="delete-btn" title="Delete Product" onclick="openDeleteModal(<?php echo htmlspecialchars($product['product_id']); ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                    <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
                                    <div class="product-meta">
                                        <span class="price">Rs.<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></span>
                                        <span class="stock">Stock: <?php echo htmlspecialchars($product['stock_quantity']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Product</h3>
                <button class="close-btn" onclick="closeModal('productModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form class="product-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Product Image</label>
                        <div class="image-upload">
                            <input type="file" accept="image/*" id="productImage" name="productImage">
                            <label for="productImage">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Click to upload image</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" required placeholder="Enter product name" name="product_name">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select required name="category">
                                <option value="">Select category</option>
                                <option value="supplements">Supplements</option>
                                <option value="equipment">Equipment</option>
                                <option value="apparel">Apparel</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Price (Rs.)</label>
                            <input type="number" required min="0" step="0.01" name="price">
                        </div>
                        <div class="form-group">
                            <label>Stock Quantity</label>
                            <input type="number" required min="0" name="stock_quantity">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea required rows="4" placeholder="Enter product description" name="description"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="save-btn" name="add_product">Save Product</button>
                        <button type="button" class="cancel-btn" onclick="closeModal('productModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal (Hidden Initially) -->
    <div class="modal" id="editProductModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Product</h3>
                <button class="close-btn" onclick="closeModal('editProductModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form class="product-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" id="editProductId">
                    <div class="form-group">
                        <label>Product Image</label>
                        <div class="image-upload">
                            <input type="file" accept="image/*" id="editProductImage" name="productImage">
                            <label for="editProductImage">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Click to upload image</span>
                            </label>
                        </div>
                        <!-- Display the current image in the edit panel -->
                        <div id="currentImagePreview" style="margin-top:10px;">
                            <img src="" alt="Current Product Image" style="max-width:150px;">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" required placeholder="Enter product name" name="product_name" id="editProductName">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select required name="category" id="editProductCategory">
                                <option value="">Select category</option>
                                <option value="supplements">Supplements</option>
                                <option value="equipment">Equipment</option>
                                <option value="apparel">Apparel</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Price ($)</label>
                            <input type="number" required min="0" step="0.01" name="price" id="editProductPrice">
                        </div>
                        <div class="form-group">
                            <label>Stock Quantity</label>
                            <input type="number" required min="0" name="stock_quantity" id="editProductStock">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea required rows="4" placeholder="Enter product description" name="description" id="editProductDescription"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="save-btn" name="edit_product">Update Product</button>
                        <button type="button" class="cancel-btn" onclick="closeModal('editProductModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal delete-modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Product</h3>
                <button class="close-btn" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <i class="fas fa-exclamation-triangle warning-icon"></i>
                <h4>Are you sure you want to delete this product?</h4>
                <p>This action cannot be undone.</p>
                <div class="button-group">
                    <button class="cancel-btn" onclick="closeModal('deleteModal')">Cancel</button>
                    <a href="#" class="save-btn confirm-delete" id="deleteConfirmLink">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const productModal = document.getElementById('productModal');
        const editProductModal = document.getElementById('editProductModal');
        const deleteModal = document.getElementById('deleteModal');

        document.getElementById('addProductBtn').addEventListener('click', () => {
            productModal.style.display = "block";
            document.querySelector('#productModal .modal-header h3').textContent = 'Add New Product';
            document.querySelector('#productModal .product-form').reset();
        });

        // Open edit modal with current product details and image preview
        function openEditModal(id, name, category, price, stock, description, imagePath) {
            document.getElementById('editProductModal').style.display = "block";
            document.querySelector('#editProductModal .modal-header h3').textContent = 'Edit Product';
            document.getElementById('editProductId').value = id;
            document.getElementById('editProductName').value = name;
            document.getElementById('editProductCategory').value = category;
            document.getElementById('editProductPrice').value = price;
            document.getElementById('editProductStock').value = stock;
            document.getElementById('editProductDescription').value = description;
            // Set current image preview
            document.querySelector('#currentImagePreview img').src = imagePath;
        }

        function openDeleteModal(productId) {
            document.getElementById('deleteModal').style.display = "block";
            document.getElementById('deleteConfirmLink').href = `inventory.php?delete_id=${productId}`;
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == productModal) {
                closeModal('productModal');
            } else if (event.target == editProductModal) {
                closeModal('editProductModal');
            } else if (event.target == deleteModal) {
                closeModal('deleteModal');
            }
        }

        // Auto-hide the success/error message after 3 seconds and remove query parameters
        setTimeout(function(){
            var message = document.querySelector('.message');
            if(message) {
                message.style.display = "none";
                window.history.replaceState(null, null, window.location.pathname);
            }
        }, 3000);

        // Search and Filter functionality
        const searchInput = document.getElementById('searchProducts');
        const categoryFilter = document.getElementById('categoryFilter');
        const productCards = document.querySelectorAll('.product-card');

        function filterProducts() {
            const searchTerm = searchInput.value.toLowerCase();
            const categoryTerm = categoryFilter.value.toLowerCase();

            productCards.forEach(card => {
                const productName = card.querySelector('.product-info h3').textContent.toLowerCase();
                const productCategory = card.querySelector('.product-category').textContent.toLowerCase();

                // Check if the card matches the search input and filter criteria
                const matchesSearch = productName.includes(searchTerm) || productCategory.includes(searchTerm);
                const matchesCategory = categoryTerm === 'all' || productCategory === categoryTerm;

                card.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', filterProducts);
        categoryFilter.addEventListener('change', filterProducts);
    </script>
</body>
</html>
