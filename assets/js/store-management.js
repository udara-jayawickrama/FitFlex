// Store Products Modal Functions
function openAddProductModal() {
    const modal = document.getElementById('productModal');
    modal.style.display = 'block';
}

function closeProductModal() {
    const modal = document.getElementById('productModal');
    modal.style.display = 'none';
}

function viewProduct(productId) {
    const modal = document.getElementById('viewProductModal');
    modal.style.display = 'block';
    // Add logic to load product details
}

function editProduct(productId) {
    const modal = document.getElementById('productModal');
    modal.querySelector('h3').textContent = 'Edit Product';
    modal.style.display = 'block';
    // Add logic to load product details for editing
}

function deleteProduct(productId) {
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'block';
}

// Store Inventory Modal Functions
function openAddStockModal() {
    const modal = document.getElementById('stockModal');
    modal.style.display = 'block';
}

function closeStockModal() {
    const modal = document.getElementById('stockModal');
    modal.style.display = 'none';
}

function updateStock(productId) {
    const modal = document.getElementById('stockModal');
    modal.style.display = 'block';
    // Add logic to load current stock details
}

function viewHistory(productId) {
    const modal = document.getElementById('historyModal');
    modal.style.display = 'block';
    // Add logic to load stock history
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.getElementsByClassName('modal');
    for (let modal of modals) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
}

// Close modal when clicking close button
document.addEventListener('DOMContentLoaded', function() {
    const closeButtons = document.getElementsByClassName('close-btn');
    for (let button of closeButtons) {
        button.onclick = function() {
            const modal = button.closest('.modal');
            modal.style.display = 'none';
        }
    }

    // Initialize form submissions
    initializeFormSubmissions();
});

function initializeFormSubmissions() {
    // Product Form
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.onsubmit = function(e) {
            e.preventDefault();
            // Add logic to save product
            closeProductModal();
        }
    }

    // Stock Form
    const stockForm = document.getElementById('stockForm');
    if (stockForm) {
        stockForm.onsubmit = function(e) {
            e.preventDefault();
            // Add logic to update stock
            closeStockModal();
        }
    }
}
