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

// Only gym-goers may access this page.
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'gym-goer') {
    header("Location: ../index.php");
    exit();
}

// For badge counts if needed, but no longer used for actual DB storage.
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}


$user_id = $_SESSION['username'];

// Query cart table for sum of quantities
$stmtCartCount = $conn->prepare("SELECT IFNULL(SUM(quantity), 0) AS total_qty FROM cart WHERE user_id = ?");
$stmtCartCount->bind_param("s", $user_id);
$stmtCartCount->execute();
$resCartCount = $stmtCartCount->get_result();
$rowCartCount = $resCartCount->fetch_assoc();
$stmtCartCount->close();
$cartCount = (int)($rowCartCount['total_qty'] ?? 0);

// Query wishlist table for count of rows
$stmtWishCount = $conn->prepare("SELECT COUNT(*) AS wcount FROM wishlist WHERE user_id = ?");
$stmtWishCount->bind_param("s", $user_id);
$stmtWishCount->execute();
$resWishCount = $stmtWishCount->get_result();
$rowWishCount = $resWishCount->fetch_assoc();
$stmtWishCount->close();
$wishlistCount = (int)($rowWishCount['wcount'] ?? 0);

/* ------------------------------------------------------------------
   ACTION: ADD TO CART
------------------------------------------------------------------ */
if (isset($_GET['action']) && $_GET['action'] === 'add_to_cart') {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    $requestedQty = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
    if ($productId > 0 && $requestedQty > 0) {
        $_SESSION['pending_add_to_cart'] = [
            'product_id' => $productId,
            'requested_qty' => $requestedQty
        ];
    }
    header("Location: shop.php");
    exit();
}


if (isset($_GET['action']) && $_GET['action'] === 'add_to_wishlist') {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    if ($productId > 0) {
        // Check if the wishlist record already exists for this user
        $stmt = $conn->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("si", $user_id, $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 0 && $wishlistCount < 10) {
            // Insert a new wishlist row
            $stmtInsert = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmtInsert->bind_param("si", $user_id, $productId);
            $stmtInsert->execute();
            $stmtInsert->close();
        }
        $stmt->close();
    }
    header("Location: shop.php");
    exit();
}


$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';
$searchFilter   = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT * FROM inventory WHERE 1=1";
$params = [];
$types  = "";
if ($categoryFilter !== '' && $categoryFilter !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
    $types   .= "s";
}
if ($searchFilter !== '') {
    $sql .= " AND (product_name LIKE ? OR description LIKE ?)";
    $searchLike = "%{$searchFilter}%";
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types   .= "ss";
}
$sql .= " ORDER BY product_name ASC";
$stmt = $conn->prepare($sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();


$catSql = "SELECT DISTINCT category FROM inventory ORDER BY category ASC";
$catRes = $conn->query($catSql);
$categories = [];
if ($catRes && $catRes->num_rows > 0) {
    while ($r = $catRes->fetch_assoc()) {
        $categories[] = $r['category'];
    }
}


if (isset($_SESSION['pending_add_to_cart'])) {
    $p = $_SESSION['pending_add_to_cart'];
    unset($_SESSION['pending_add_to_cart']);
    $prodId = $p['product_id'];
    $reqQty = $p['requested_qty'];

    // Find the product in $products
    $foundProduct = null;
    foreach ($products as $pr) {
        if ((int)$pr['product_id'] === $prodId) {
            $foundProduct = $pr;
            break;
        }
    }
    if ($foundProduct) {
        $stock = (int)$foundProduct['stock_quantity'];

        // If stock is 0, do nothing (don't add to cart)
        if ($stock > 0) {
            // Get the current cart quantity from DB for this user & product
            $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("si", $user_id, $prodId);
            $stmt->execute();
            $resultCart = $stmt->get_result();
            $cartQtyAlready = 0;
            if ($row = $resultCart->fetch_assoc()) {
                $cartQtyAlready = (int)$row['quantity'];
            }
            $stmt->close();

            $available = $stock - $cartQtyAlready;
            if ($available > 0) {
                $quantityToAdd = min($reqQty, $available);

                // Retrieve the seller's shipping fee from the sellers table
                $sellerUsername = $foundProduct['seller_username'];
                $stmtSeller = $conn->prepare("SELECT shipping_fee FROM sellers WHERE username = ?");
                $stmtSeller->bind_param("s", $sellerUsername);
                $stmtSeller->execute();
                $resultSeller = $stmtSeller->get_result();
                $sellerData = $resultSeller->fetch_assoc();
                $sellerShippingFee = $sellerData ? (float)$sellerData['shipping_fee'] : 0.00;
                $stmtSeller->close();

                // Check if product already exists in cart
                $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("si", $user_id, $prodId);
                $stmt->execute();
                $resultCart = $stmt->get_result();
                if ($resultCart->num_rows > 0) {
                    // Update record with increased quantity (shipping fee remains the same)
                    $newQty = $cartQtyAlready + $quantityToAdd;
                    $stmtUpdate = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                    $stmtUpdate->bind_param("isi", $newQty, $user_id, $prodId);
                    $stmtUpdate->execute();
                    $stmtUpdate->close();
                } else {
                    // Insert new record in cart table
                    $stmtInsert = $conn->prepare("
                        INSERT INTO cart (user_id, product_id, quantity, shipping_fee)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmtInsert->bind_param("siid", $user_id, $prodId, $quantityToAdd, $sellerShippingFee);
                    $stmtInsert->execute();
                    $stmtInsert->close();
                }
            }
        }
    }
    header("Location: shop.php");
    exit();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FitFlex - Shop</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/gym-goer.css">
    <link rel="stylesheet" href="../assets/css/shop.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .shop-container {
            background-color: black;
            padding: 20px;
        }
        .categories-filter {
            width: 100%;
            margin-bottom: 20px;
            text-align: center;
        }
        .category-list {
            display: inline-block;
        }
        .category-btn {
            margin: 0 8px;
            padding: 8px 16px;
            background: black;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .category-btn.active {
            background: #007bff;
            color: #fff;
        }
        .product-card {
            background: #4F4F4F;
            border: 1px solid white;
            padding: 10px;
            margin: 10px;
            width: 220px;
            display: inline-block;
            vertical-align: top;
            position: relative;
        }
        .wishlist-btn {
            border: 1px solid white;
            border-radius: 50%;
            background: transparent;
            color: white;
            font-size: 18px;
            position: absolute;
            top: 8px;
            right: 8px;
            cursor: pointer;
        }
        .wishlist-btn:hover {
            border-color: red;
        }
        #modalQtyInput {
            width: 60px;
            text-align: center;
            pointer-events: none;
            border: none;
            background: #f0f0f0;
        }
        .qty-btn {
            padding: 5px 10px;
            cursor: pointer;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<div class="member-dashboard">
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Member Dashboard</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <a href="profile.php">
                <i class="fas fa-user"></i>
                My Profile
            </a>
            <a href="meal-plans.php">
                <i class="fas fa-utensils"></i>
                Meal Plans
            </a>
            <a href="workouts.php">
                <i class="fas fa-dumbbell"></i>
                Workouts
            </a>
            <a href="shop.php" class="active">
                <i class="fas fa-shopping-cart"></i>
                Shop
            </a>
            <a href="order_history.php">
                <i class="fas fa-history"></i>
                Order History
            </a>
            <a href="record-visit.php">
                <i class="fa-solid fa-person-walking"></i>
                Gym Visit
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <header class="dashboard-header">
            <h2>Shop</h2>
            <div class="header-actions">
                <div class="search-bar">
                    <form method="GET" action="shop.php">
                        <input type="text" name="search" placeholder="Search products..."
                               value="<?php echo htmlspecialchars($searchFilter); ?>">
                        <button class="search-btn" type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <div class="shop-icons">
                    <!-- Cart icon: use $cartCount from DB -->
                    <a href="checkout.php" style="color:inherit;">
                        <div class="cart-icon">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="badge" id="cartCount">
                                <?php echo $cartCount; ?>
                            </span>
                        </div>
                    </a>
                    <!-- Wishlist icon: use $wishlistCount from DB -->
                    <a href="wishlist.php" style="color:inherit;">
                        <div class="wishlist-icon">
                            <i class="fas fa-heart"></i>
                            <span class="badge" id="wishlistCount">
                                <?php echo $wishlistCount; ?>
                            </span>
                        </div>
                    </a>
                </div>
            </div>
        </header>

        <div class="shop-container">
            <div class="categories-filter">
                <div class="category-list">
                    <a href="shop.php?category=all<?php echo ($searchFilter!='' ? '&search='.urlencode($searchFilter) : ''); ?>">
                        <button class="category-btn <?php echo ($categoryFilter=='' || $categoryFilter=='all') ? 'active' : ''; ?>">
                            All Products
                        </button>
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="shop.php?category=<?php echo urlencode($cat); ?><?php echo ($searchFilter!='' ? '&search='.urlencode($searchFilter) : ''); ?>">
                            <button class="category-btn <?php echo ($categoryFilter==$cat) ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($cat); ?>
                            </button>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <p style="color:white;">No products available.</p>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" data-category="<?php echo htmlspecialchars($product['category']); ?>">
                            <!-- Add to Wishlist -->
                            <form action="shop.php" method="GET">
                                <input type="hidden" name="action" value="add_to_wishlist">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <button class="wishlist-btn" type="submit" title="Add to Wishlist">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </form>

                            <div class="product-image" style="text-align:center;">
                                <img src="<?php echo htmlspecialchars($product['image_path'] ?: '../assets/images/product-placeholder.jpg'); ?>"
                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                     style="max-width:100%; height:auto;">
                            </div>

                            <div class="product-info" style="margin-top:10px; text-align:center;">
                                <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                                <p class="product-description">
                                    <?php
                                        $desc = $product['description'] ?? '';
                                        echo htmlspecialchars(substr($desc, 0, 50))
                                             . (strlen($desc)>50 ? '...' : '');
                                    ?>
                                </p>
                                <span class="price">Rs.<?php echo number_format($product['price'], 2); ?></span>
                            </div>

                            <div class="product-actions" style="text-align:center; margin-top:10px;">
                                <button class="view-product-btn"
                                        onclick="openProductModal(<?php echo $product['product_id']; ?>)">
                                    View Details
                                </button>
                                <br><br>
                                <?php if ((int)$product['stock_quantity'] > 0): ?>
                                    <!-- If in stock, show Add to Cart link -->
                                    <a class="add-to-cart-btn"
                                       href="shop.php?action=add_to_cart&product_id=<?php echo $product['product_id']; ?>&quantity=1"
                                       style="text-decoration:none; background:#007bff; color:#fff; padding:5px 10px; border-radius:4px;">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </a>
                                <?php else: ?>
                                    <!-- If out of stock, show Out of Stock text -->
                                    <span style="color:red; font-weight:bold;">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Modal -->
        <div id="productModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal('productModal')">&times;</span>
                <div class="product-details" id="productDetailsContent"></div>
            </div>
        </div>
    </div>
</div>
<script>
    const products = <?php echo json_encode($products); ?>;

    function openProductModal(productId) {
        const product = products.find(p => parseInt(p.product_id) === parseInt(productId));
        if (!product) return;
        const modal = document.getElementById('productModal');
        const contentDiv = document.getElementById('productDetailsContent');
        const stockQty = product.stock_quantity ? parseInt(product.stock_quantity) : 0;
        const inStockText = stockQty > 0 ? 'In Stock' : 'Out of Stock';

        contentDiv.innerHTML = `
            <div class="product-gallery">
                <img src="${product.image_path || '../assets/images/product-placeholder.jpg'}"
                     alt="${product.product_name}" style="max-width:300px;">
            </div>
            <div class="product-info-detailed">
                <h2>${product.product_name}</h2>
                <div class="price-info">
                    <span class="price">Rs.${parseFloat(product.price).toFixed(2)}</span>
                    <span class="stock">Stock: ${inStockText}</span>
                </div>
                <div class="product-description">
                    <h3>Description</h3>
                    <p>${product.description || 'No description available.'}</p>
                </div>
                <div class="purchase-options" style="margin-top:15px;">
                    <div style="display:inline-block; margin-right:10px;">
                        <button class="qty-btn" onclick="changeQty(-1, ${stockQty})">-</button>
                    </div>
                    <input type="number" id="modalQtyInput" value="1" min="1" max="${stockQty}" readonly>
                    <div style="display:inline-block; margin-left:10px;">
                        <button class="qty-btn" onclick="changeQty(1, ${stockQty})">+</button>
                    </div>
                    <br><br>
                    ${
                        stockQty > 0 
                        ? `<a href="#" onclick="addToCartFromModal(${product.product_id}); return false;"
                             style="padding:6px 12px; background:#007bff; color:#fff; text-decoration:none; border-radius:4px;">
                             <i class="fas fa-cart-plus"></i> Add to Cart
                           </a>`
                        : `<span style="color:red; font-weight:bold;">Out of Stock</span>`
                    }
                </div>
            </div>
        `;
        modal.style.display = 'block';
    }

    function changeQty(delta, maxQty) {
        const qtyInput = document.getElementById('modalQtyInput');
        let current = parseInt(qtyInput.value);
        let newVal = current + delta;
        if (newVal < 1) newVal = 1;
        if (newVal > maxQty) newVal = maxQty;
        qtyInput.value = newVal;
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function addToCartFromModal(productId) {
        const qtyInput = document.getElementById('modalQtyInput');
        const quantity = qtyInput ? parseInt(qtyInput.value) : 1;
        window.location.href = `shop.php?action=add_to_cart&product_id=${productId}&quantity=${quantity}`;
    }

    window.onclick = function(event) {
        const modal = document.getElementById('productModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
</script>
</body>
</html>
