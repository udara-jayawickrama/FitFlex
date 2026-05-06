document.addEventListener('DOMContentLoaded', function() {
    const orderModal = document.getElementById('orderModal');
    const processModal = document.getElementById('processModal');
    const searchInput = document.getElementById('searchOrders');
    const statusFilter = document.getElementById('statusFilter');

    // View order details
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const orderRow = btn.closest('tr');
            const orderData = {
                id: orderRow.querySelector('td:first-child').textContent,
                customer: orderRow.querySelector('.customer-name').textContent,
                email: orderRow.querySelector('.customer-email').textContent,
                date: orderRow.querySelector('td:nth-child(3)').textContent,
                status: orderRow.querySelector('.status').textContent
            };

            // Populate order details modal
            const modal = document.getElementById('orderModal');
            modal.querySelector('.modal-header h3').textContent = `Order ${orderData.id}`;
            
            // Show modal
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
    });

    // Process order
    document.querySelectorAll('.process-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const orderRow = btn.closest('tr');
            const currentStatus = orderRow.querySelector('.status').textContent;
            
            // Set current status in dropdown
            const statusSelect = processModal.querySelector('select');
            Array.from(statusSelect.options).forEach(option => {
                if (option.textContent.toLowerCase() === currentStatus.toLowerCase()) {
                    option.selected = true;
                }
            });

            // Show modal
            processModal.style.display = 'block';
            document.body.style.overflow = 'hidden';

            // Store reference to current order
            processModal.dataset.orderId = orderRow.querySelector('td:first-child').textContent;
        });
    });

    // Handle process form submission
    const processForm = processModal.querySelector('.process-form');
    processForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const orderId = processModal.dataset.orderId;
        const newStatus = processForm.querySelector('select').value;
        const trackingNumber = processForm.querySelector('input[type="text"]').value;
        const notes = processForm.querySelector('textarea').value;

        // Update order status in the table
        const orderRow = document.querySelector(`td:first-child[textContent="${orderId}"]`).closest('tr');
        const statusBadge = orderRow.querySelector('.status');
        statusBadge.className = `status ${newStatus.toLowerCase()}`;
        statusBadge.textContent = newStatus;

        console.log('Order updated:', {
            orderId,
            newStatus,
            trackingNumber,
            notes
        });

        // Close modal and reset form
        processModal.style.display = 'none';
        document.body.style.overflow = 'auto';
        processForm.reset();
    });

    // Close modals
    document.querySelectorAll('.modal .close-btn, .modal .cancel-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            orderModal.style.display = 'none';
            processModal.style.display = 'none';
            document.body.style.overflow = 'auto';
            processForm.reset();
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === orderModal || e.target === processModal) {
            orderModal.style.display = 'none';
            processModal.style.display = 'none';
            document.body.style.overflow = 'auto';
            processForm.reset();
        }
    });

    // Search functionality
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('.orders-table tbody tr');

        rows.forEach(row => {
            const orderId = row.querySelector('td:first-child').textContent.toLowerCase();
            const customerName = row.querySelector('.customer-name').textContent.toLowerCase();
            const customerEmail = row.querySelector('.customer-email').textContent.toLowerCase();
            
            const visible = orderId.includes(searchTerm) || 
                          customerName.includes(searchTerm) || 
                          customerEmail.includes(searchTerm);
            
            row.style.display = visible ? '' : 'none';
        });
    });

    // Status filter
    statusFilter.addEventListener('change', (e) => {
        const filterValue = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('.orders-table tbody tr');

        rows.forEach(row => {
            const status = row.querySelector('.status').textContent.toLowerCase();
            const visible = filterValue === 'all' || status === filterValue;
            row.style.display = visible ? '' : 'none';
        });
    });
}); 