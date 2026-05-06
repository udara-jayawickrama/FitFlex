// Product Data
const products = [
    {
        id: 1,
        name: 'Premium Whey Protein',
        description: 'High-quality protein powder for muscle recovery and growth.',
        price: 49.99,
        category: 'supplements',
        image: '../assets/images/product-1.jpg',
        rating: 4.5,
        reviews: 128,
        stock: 50,
        features: [
            '24g protein per serving',
            'Low in calories and carbs',
            'Easy to mix and digest',
            'No artificial sweeteners'
        ]
    },{
        id: 1,
        name: '1 Premium Whey Protein ',
        description: 'High-quality protein powder for muscle recovery and growth.',
        price: 49.99,
        category: 'supplements',
        image: '../assets/images/product-1.jpg',
        rating: 4.5,
        reviews: 128,
        stock: 50,
        features: [
            '24g protein per serving',
            'Low in calories and carbs',
            'Easy to mix and digest',
            'No artificial sweeteners'
        ]
    },
    // Add more products here
];

// Cart State
let cart = [];
let wishlist = [];

// DOM Elements
const searchInput = document.getElementById('searchInput');
const productsGrid = document.querySelector('.products-grid');
const cartSidebar = document.querySelector('.cart-sidebar');
const wishlistSidebar = document.querySelector('.wishlist-sidebar');
const cartItemsContainer = document.querySelector('.cart-items');
const wishlistItemsContainer = document.querySelector('.wishlist-items');
const cartCount = document.getElementById('cartCount');
const wishlistCount = document.getElementById('wishlistCount');
const productModal = document.getElementById('productModal');
const checkoutModal = document.getElementById('checkoutModal');
const notificationToast = document.querySelector('.notification-toast');

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    renderProducts(products);
    setupEventListeners();
});

function setupEventListeners() {
    // Search functionality
    searchInput.addEventListener('input', handleSearch);

    // Category filters
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', () => filterByCategory(btn.dataset.category));
    });

    // Cart toggle
    document.querySelector('.cart-icon').addEventListener('click', toggleCart);
    document.querySelector('.close-cart').addEventListener('click', toggleCart);

    // Wishlist toggle
    document.querySelector('.wishlist-icon').addEventListener('click', toggleWishlistSidebar);
    document.querySelector('.close-wishlist').addEventListener('click', toggleWishlistSidebar);

    // Clear wishlist
    document.querySelector('.clear-wishlist-btn').addEventListener('click', clearWishlist);

    // Close modals
    document.querySelectorAll('.close-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            productModal.style.display = 'none';
            checkoutModal.style.display = 'none';
        });
    });

    // Close notification
    document.querySelector('.close-notification').addEventListener('click', () => {
        notificationToast.classList.remove('show');
    });
}

// Product Rendering
function renderProducts(productsToRender) {
    productsGrid.innerHTML = productsToRender.map(product => `
        <div class="product-card" data-category="${product.category}">
            <div class="product-image">
                <img src="${product.image}" alt="${product.name}">
                <button class="wishlist-btn" onclick="toggleWishlist(${product.id})">
                    <i class="far fa-heart"></i>
                </button>
            </div>
            <div class="product-info">
                <h4>${product.name}</h4>
                <p class="product-description">${product.description}</p>
                <div class="product-meta">
                    <span class="price">$${product.price.toFixed(2)}</span>
                    <div class="rating">
                        ${generateRatingStars(product.rating)}
                        <span>(${product.rating})</span>
                    </div>
                </div>
                <div class="product-actions">
                    <button class="view-product-btn" onclick="viewProduct(${product.id})">View Details</button>
                    <button class="add-to-cart-btn" onclick="addToCart(${product.id})">
                        <i class="fas fa-cart-plus"></i>
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Search and Filter Functions
function handleSearch() {
    const searchTerm = searchInput.value.toLowerCase();
    const filteredProducts = products.filter(product => 
        product.name.toLowerCase().includes(searchTerm) ||
        product.description.toLowerCase().includes(searchTerm)
    );
    renderProducts(filteredProducts);
}

function filterByCategory(category) {
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.category === category) {
            btn.classList.add('active');
        }
    });

    const filteredProducts = category === 'all' 
        ? products 
        : products.filter(product => product.category === category);
    renderProducts(filteredProducts);
}

// Cart Functions
function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    const existingItem = cart.find(item => item.id === productId);

    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({ ...product, quantity: 1 });
    }

    updateCartCount();
    updateCartDisplay();
    showNotification('Product added to cart');
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCartCount();
    updateCartDisplay();
    showNotification('Product removed from cart');
}

function updateQuantity(productId, delta) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        item.quantity = Math.max(1, item.quantity + delta);
        updateCartDisplay();
    }
}

function updateCartCount() {
    cartCount.textContent = cart.reduce((total, item) => total + item.quantity, 0);
}

function updateCartDisplay() {
    cartItemsContainer.innerHTML = cart.map(item => `
        <div class="cart-item">
            <img src="${item.image}" alt="${item.name}">
            <div class="item-details">
                <h4>${item.name}</h4>
                <div class="quantity-controls">
                    <button onclick="updateQuantity(${item.id}, -1)">-</button>
                    <span>${item.quantity}</span>
                    <button onclick="updateQuantity(${item.id}, 1)">+</button>
                </div>
                <span class="price">$${(item.price * item.quantity).toFixed(2)}</span>
            </div>
            <button class="remove-item" onclick="removeFromCart(${item.id})">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');

    updateCartSummary();
}

function updateCartSummary() {
    const subtotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    const shipping = subtotal > 0 ? 5.99 : 0;
    const total = subtotal + shipping;

    document.querySelector('.subtotal .amount').textContent = `$${subtotal.toFixed(2)}`;
    document.querySelector('.shipping .amount').textContent = `$${shipping.toFixed(2)}`;
    document.querySelector('.total .amount').textContent = `$${total.toFixed(2)}`;
}

// Wishlist Functions
function toggleWishlist(productId) {
    const index = wishlist.indexOf(productId);
    if (index === -1) {
        wishlist.push(productId);
        showNotification('Item added to wishlist');
    } else {
        wishlist.splice(index, 1);
        showNotification('Item removed from wishlist');
    }
    updateWishlistCount();
    updateWishlistDisplay();
    updateWishlistButtons();
}

function toggleWishlistSidebar() {
    wishlistSidebar.classList.toggle('active');
}

function updateWishlistDisplay() {
    if (!wishlistItemsContainer) return;
    
    wishlistItemsContainer.innerHTML = wishlist.map(productId => {
        const product = products.find(p => p.id === productId);
        if (!product) return '';
        
        return `
            <div class="wishlist-item">
                <img src="${product.image}" alt="${product.name}">
                <div class="wishlist-item-info">
                    <h4>${product.name}</h4>
                    <p>$${product.price.toFixed(2)}</p>
                </div>
                <div class="wishlist-item-actions">
                    <button onclick="addToCart(${product.id})" class="add-to-cart-btn">
                        <i class="fas fa-cart-plus"></i>
                    </button>
                    <button onclick="toggleWishlist(${product.id})" class="remove-wishlist-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function clearWishlist() {
    wishlist = [];
    updateWishlistCount();
    updateWishlistDisplay();
    updateWishlistButtons();
    showNotification('Wishlist cleared');
}

function updateWishlistCount() {
    wishlistCount.textContent = wishlist.length;
}

function updateWishlistButtons() {
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        const productId = parseInt(btn.closest('.product-card').dataset.productId);
        if (wishlist.includes(productId)) {
            btn.innerHTML = '<i class="fas fa-heart"></i>';
            btn.classList.add('active');
        } else {
            btn.innerHTML = '<i class="far fa-heart"></i>';
            btn.classList.remove('active');
        }
    });
}

// Modal Functions
function viewProduct(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;

    const modalContent = productModal.querySelector('.product-details');
    modalContent.innerHTML = `
        <div class="product-gallery">
            <img src="${product.image}" alt="${product.name}" class="main-image">
            <div class="thumbnail-images">
                <img src="${product.image}" alt="Thumbnail 1">
                <!-- Add more thumbnails if available -->
            </div>
        </div>
        <div class="product-info-detailed">
            <h2>${product.name}</h2>
            <div class="rating">
                ${generateRatingStars(product.rating)}
                <span>(${product.rating} - ${product.reviews} reviews)</span>
            </div>
            <div class="price-info">
                <span class="price">$${product.price.toFixed(2)}</span>
                <span class="stock">${product.stock > 0 ? 'In Stock' : 'Out of Stock'}</span>
            </div>
            <div class="product-description">
                <h3>Description</h3>
                <p>${product.description}</p>
                <ul class="features">
                    ${product.features.map(feature => `
                        <li><i class="fas fa-check"></i> ${feature}</li>
                    `).join('')}
                </ul>
            </div>
            <div class="purchase-options">
                <div class="quantity-selector">
                    <button class="qty-btn minus" onclick="updateModalQuantity(-1)">-</button>
                    <input type="number" value="1" min="1" max="10" id="modalQuantity">
                    <button class="qty-btn plus" onclick="updateModalQuantity(1)">+</button>
                </div>
                <button class="buy-now-btn" onclick="buyNow(${product.id})">Buy Now</button>
                <button class="add-to-cart-btn" onclick="addToCart(${product.id})">Add to Cart</button>
            </div>
        </div>
    `;

    productModal.style.display = 'block';
}

function updateModalQuantity(delta) {
    const input = document.getElementById('modalQuantity');
    const newValue = Math.max(1, Math.min(10, parseInt(input.value) + delta));
    input.value = newValue;
}

// Checkout Functions
function proceedToCheckout() {
    if (cart.length === 0) {
        showNotification('Your cart is empty');
        return;
    }

    const summaryItems = document.querySelector('.summary-items');
    summaryItems.innerHTML = cart.map(item => `
        <div class="summary-item">
            <span>${item.name} x ${item.quantity}</span>
            <span>$${(item.price * item.quantity).toFixed(2)}</span>
        </div>
    `).join('');

    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    document.querySelector('.summary-total .amount').textContent = `$${total.toFixed(2)}`;

    checkoutModal.style.display = 'block';
    cartSidebar.classList.remove('open');
}

function confirmOrder() {
    // Here you would typically handle the payment processing and order submission
    showNotification('Order placed successfully!');
    cart = [];
    updateCartCount();
    updateCartDisplay();
    checkoutModal.style.display = 'none';
}

// Utility Functions
function generateRatingStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;
    let stars = '';

    for (let i = 0; i < fullStars; i++) {
        stars += '<i class="fas fa-star"></i>';
    }
    if (hasHalfStar) {
        stars += '<i class="fas fa-star-half-alt"></i>';
    }
    const emptyStars = 5 - Math.ceil(rating);
    for (let i = 0; i < emptyStars; i++) {
        stars += '<i class="far fa-star"></i>';
    }

    return stars;
}

function toggleCart() {
    cartSidebar.classList.toggle('open');
}

function showNotification(message) {
    const toast = document.querySelector('.notification-toast');
    toast.querySelector('.message').textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

// Direct Purchase Function
function buyNow(productId) {
    const product = products.find(p => p.id === productId);
    const quantity = document.getElementById('modalQuantity')?.value || 1;
    
    cart = [{ ...product, quantity: parseInt(quantity) }];
    updateCartCount();
    updateCartDisplay();
    productModal.style.display = 'none';
    proceedToCheckout();
}
