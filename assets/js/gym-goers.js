document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchMembers');
    const filterSelect = document.getElementById('memberFilter');
    const viewModal = document.getElementById('memberModal');
    const editModal = document.getElementById('editMemberModal');
    const messageModal = document.getElementById('messageModal');
    const allModals = [viewModal, editModal, messageModal];

    // Search functionality
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('.members-table tbody tr');

        rows.forEach(row => {
            const name = row.querySelector('.member-name').textContent.toLowerCase();
            const email = row.querySelector('.member-email').textContent.toLowerCase();
            const visible = name.includes(searchTerm) || email.includes(searchTerm);
            row.style.display = visible ? '' : 'none';
        });
    });

    // Filter functionality
    filterSelect.addEventListener('change', (e) => {
        const filterValue = e.target.value;
        const rows = document.querySelectorAll('.members-table tbody tr');

        rows.forEach(row => {
            const status = row.querySelector('.status-badge').classList.contains('active') ? 'active' : 'inactive';
            const visible = filterValue === 'all' || status === filterValue;
            row.style.display = visible ? '' : 'none';
        });
    });

    // Helper function to close all modals
    function closeAllModals() {
        allModals.forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = 'auto';
    }

    // Helper function to open a specific modal
    function openModal(modal, memberData = null) {
        closeAllModals();
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';

        if (memberData) {
            if (modal === editModal) {
                const form = modal.querySelector('.edit-member-form');
                form.querySelector('input[type="text"]').value = memberData.name;
                form.querySelector('input[type="email"]').value = memberData.email;
                // Add more field population as needed
            } else if (modal === messageModal) {
                const form = modal.querySelector('.message-form');
                form.querySelector('input[type="text"]').value = `Message to ${memberData.name}`;
            } else if (modal === viewModal) {
                // Populate view modal fields
                modal.querySelector('.profile-info h4').textContent = memberData.name;
                modal.querySelector('.info-item p').textContent = memberData.email;
            }
        }
    }

    // View member details
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const memberRow = btn.closest('tr');
            const memberData = {
                name: memberRow.querySelector('.member-name').textContent,
                email: memberRow.querySelector('.member-email').textContent,
                membership: memberRow.querySelector('td:nth-child(2)').textContent,
                plan: memberRow.querySelector('td:nth-child(3)').textContent,
                status: memberRow.querySelector('.status-badge').textContent
            };
            openModal(viewModal, memberData);
        });
    });

    // Edit member
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const memberRow = btn.closest('tr');
            const memberData = {
                name: memberRow.querySelector('.member-name').textContent,
                email: memberRow.querySelector('.member-email').textContent,
                membership: memberRow.querySelector('td:nth-child(2)').textContent,
                status: memberRow.querySelector('.status-badge').textContent
            };
            openModal(editModal, memberData);
        });
    });

    // Message member
    document.querySelectorAll('.message-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const memberRow = btn.closest('tr');
            const memberData = {
                name: memberRow.querySelector('.member-name').textContent,
                email: memberRow.querySelector('.member-email').textContent
            };
            openModal(messageModal, memberData);
        });
    });

    // Close buttons for all modals
    document.querySelectorAll('.close-btn, .cancel-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            closeAllModals();
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        allModals.forEach(modal => {
            if (e.target === modal) {
                closeAllModals();
            }
        });
    });

    // Handle form submissions
    document.querySelector('.edit-member-form').addEventListener('submit', (e) => {
        e.preventDefault();
        console.log('Saving member changes...');
        closeAllModals();
    });

    document.querySelector('.message-form').addEventListener('submit', (e) => {
        e.preventDefault();
        console.log('Sending message...');
        closeAllModals();
    });
}); 