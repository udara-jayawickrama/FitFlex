document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('productModal');
    const addProductBtn = document.getElementById('addProductBtn');
    const closeBtn = modal.querySelector('.close-btn');
    const cancelBtn = modal.querySelector('.cancel-btn');
    const productForm = modal.querySelector('.product-form');
    const searchInput = document.getElementById('searchProducts');
    const categoryFilter = document.getElementById('categoryFilter');

    // Open modal
    addProductBtn.addEventListener('click', () => {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    });

    // Close modal functions
    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        productForm.reset();
    }

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Handle form submission
    productForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        // Collect form data
        const formData = new FormData(productForm);
        console.log('Saving product...', Object.fromEntries(formData));
        
        // Close modal after saving
        closeModal();
    });

    // Image preview
    const imageInput = document.getElementById('productImage');
    const imageLabel = imageInput.nextElementSibling;

    imageInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imageLabel.innerHTML = `
                    <img src="${e.target.result}" style="max-width: 100%; max-height: 200px; margin-bottom: 10px;">
                    <span>Click to change image</span>
                `;
            };
            reader.readAsDataURL(file);
        }
    });

    // Search functionality
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const products = document.querySelectorAll('.product-card');
        
        products.forEach(product => {
            const name = product.querySelector('h3').textContent.toLowerCase();
            const category = product.querySelector('.product-category').textContent.toLowerCase();
            const visible = name.includes(searchTerm) || category.includes(searchTerm);
            product.style.display = visible ? '' : 'none';
        });
    });

    // Category filter
    categoryFilter.addEventListener('change', (e) => {
        const category = e.target.value.toLowerCase();
        const products = document.querySelectorAll('.product-card');
        
        products.forEach(product => {
            const productCategory = product.querySelector('.product-category').textContent.toLowerCase();
            const visible = category === 'all' || productCategory === category;
            product.style.display = visible ? '' : 'none';
        });
    });

    // Product Actions
    const deleteModal = document.getElementById('deleteModal');
    let productToDelete = null;

    // Edit product
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const productCard = btn.closest('.product-card');
            const productData = {
                name: productCard.querySelector('h3').textContent,
                category: productCard.querySelector('.product-category').textContent,
                price: productCard.querySelector('.price').textContent.replace('$', ''),
                // Add other fields as needed
            };

            // Populate the edit form
            modal.querySelector('.modal-header h3').textContent = 'Edit Product';
            const form = modal.querySelector('.product-form');
            form.querySelector('input[placeholder="Enter product name"]').value = productData.name;
            form.querySelector('select').value = productData.category.toLowerCase();
            form.querySelector('input[type="number"]').value = productData.price;

            // Show the modal
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
    });

    // Delete product
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            productToDelete = btn.closest('.product-card');
            deleteModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
    });

    // Handle delete confirmation
    deleteModal.querySelector('.confirm-delete').addEventListener('click', () => {
        if (productToDelete) {
            productToDelete.remove();
            console.log('Product deleted');
        }
        deleteModal.style.display = 'none';
        document.body.style.overflow = 'auto';
        productToDelete = null;
    });

    // Close delete modal
    deleteModal.querySelector('.cancel-btn').addEventListener('click', () => {
        deleteModal.style.display = 'none';
        document.body.style.overflow = 'auto';
        productToDelete = null;
    });

    deleteModal.querySelector('.close-btn').addEventListener('click', () => {
        deleteModal.style.display = 'none';
        document.body.style.overflow = 'auto';
        productToDelete = null;
    });

    // Close delete modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === deleteModal) {
            deleteModal.style.display = 'none';
            document.body.style.overflow = 'auto';
            productToDelete = null;
        }
    });

    // Reset modal title when adding new product
    addProductBtn.addEventListener('click', () => {
        modal.querySelector('.modal-header h3').textContent = 'Add New Product';
        productForm.reset();
    });
}); 