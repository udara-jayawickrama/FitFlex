// Modal Elements
const customerModal = document.getElementById('customerModal');
const progressModal = document.getElementById('progressModal');
const goalsModal = document.getElementById('goalsModal');
const deleteModal = document.getElementById('deleteModal');
const addCustomerModal = document.getElementById('addCustomerModal');
const viewCustomerModal = document.getElementById('viewCustomerModal');
const editCustomerModal = document.getElementById('editCustomerModal');
const deleteCustomerModal = document.getElementById('deleteCustomerModal');
const updateProgressModal = document.getElementById('updateProgressModal');

// Close buttons
document.querySelectorAll('.close-btn').forEach(btn => {
    btn.onclick = function() {
        customerModal.style.display = 'none';
        progressModal.style.display = 'none';
        goalsModal.style.display = 'none';
        deleteModal.style.display = 'none';
        addCustomerModal.style.display = 'none';
        viewCustomerModal.style.display = 'none';
        editCustomerModal.style.display = 'none';
        deleteCustomerModal.style.display = 'none';
        updateProgressModal.style.display = 'none';
    }
});

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
    // Customer Form
    const customerForm = document.getElementById('customerForm');
    if (customerForm) {
        customerForm.onsubmit = function(e) {
            e.preventDefault();
            // Add logic to save customer
            closeAddCustomerModal();
        }
    }

    // Progress Form
    const progressForm = document.getElementById('progressForm');
    if (progressForm) {
        progressForm.onsubmit = function(e) {
            e.preventDefault();
            // Add logic to update progress
            closeUpdateProgressModal();
        }
    }
}

// Customer List Page Functions
let currentCustomerId = null;

function openAddCustomerModal() {
    const modal = document.getElementById('addCustomerModal');
    modal.style.display = 'block';
}

function closeAddCustomerModal() {
    const modal = document.getElementById('addCustomerModal');
    modal.style.display = 'none';
}

function viewCustomer(customerId) {
    const modal = document.getElementById('viewCustomerModal');
    modal.style.display = 'block';
    // Add logic to load customer details
    currentCustomerId = customerId;
    const modalTitle = modal.querySelector('h3');
    const form = document.getElementById('customerForm');

    // Update modal title
    modalTitle.textContent = 'View Customer Details';

    // Simulate fetching customer data
    const customerData = {
        fullName: 'Sarah Johnson',
        email: 'sarah.j@example.com',
        phone: '+1 234-567-8900',
        membershipPlan: 'premium',
        startDate: '2024-07-15',
        status: 'active'
    };

    // Populate form fields
    Object.keys(customerData).forEach(key => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            input.value = customerData[key];
            input.disabled = true;
        }
    });

    // Hide form actions
    form.querySelector('.form-actions').style.display = 'none';
}

function editCustomer(customerId) {
    const modal = document.getElementById('editCustomerModal');
    modal.style.display = 'block';
    // Add logic to load customer details for editing
    currentCustomerId = customerId;
    const modalTitle = modal.querySelector('h3');
    const form = document.getElementById('customerForm');

    // Update modal title
    modalTitle.textContent = 'Edit Customer';

    // Simulate fetching customer data
    const customerData = {
        fullName: 'Sarah Johnson',
        email: 'sarah.j@example.com',
        phone: '+1 234-567-8900',
        membershipPlan: 'premium',
        startDate: '2024-07-15',
        status: 'active'
    };

    // Populate form fields
    Object.keys(customerData).forEach(key => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            input.value = customerData[key];
            input.disabled = false;
        }
    });

    // Show form actions
    form.querySelector('.form-actions').style.display = 'flex';
}

function deleteCustomer(customerId) {
    const modal = document.getElementById('deleteCustomerModal');
    modal.style.display = 'block';
    currentCustomerId = customerId;
}

function confirmDelete() {
    // Implement delete functionality here
    console.log(`Deleting customer ${currentCustomerId}`);
    deleteCustomerModal.style.display = 'none';
    // Refresh customer list or remove customer card
}

// Customer Progress Page Functions
function viewProgress(customerId) {
    const modal = document.getElementById('progressModal');
    const progressDetails = modal.querySelector('.progress-details');

    // Simulate fetching progress data
    const progressData = {
        name: 'Sarah Johnson',
        weeklyVisits: [4, 5, 3, 4, 5, 4, 4], // Last 7 weeks
        sessionDurations: [75, 80, 65, 85, 90, 85, 85], // Minutes
        weightProgress: [68, 67.5, 67, 66.8, 66.5, 66.2, 66], // KG
        goals: {
            weeklyVisits: 4,
            sessionDuration: 60,
            targetWeight: 65
        }
    };

    // Create progress content
    progressDetails.innerHTML = `
        <div class="customer-profile">
            <h4>${progressData.name}'s Progress</h4>
        </div>
        <div class="progress-charts">
            <div class="chart-section">
                <h5>Weekly Gym Visits</h5>
                <div class="chart-placeholder">
                    <!-- Chart would be rendered here using a charting library -->
                    <p>Average: ${average(progressData.weeklyVisits)} visits/week</p>
                    <p>Goal: ${progressData.goals.weeklyVisits} visits/week</p>
                </div>
            </div>
            <div class="chart-section">
                <h5>Session Duration</h5>
                <div class="chart-placeholder">
                    <p>Average: ${average(progressData.sessionDurations)} minutes</p>
                    <p>Goal: ${progressData.goals.sessionDuration} minutes</p>
                </div>
            </div>
            <div class="chart-section">
                <h5>Weight Progress</h5>
                <div class="chart-placeholder">
                    <p>Current: ${progressData.weightProgress[progressData.weightProgress.length - 1]} kg</p>
                    <p>Target: ${progressData.goals.targetWeight} kg</p>
                </div>
            </div>
        </div>
    `;

    modal.style.display = 'block';
}

function openUpdateProgressModal() {
    const modal = document.getElementById('updateProgressModal');
    modal.style.display = 'block';
}

function closeUpdateProgressModal() {
    const modal = document.getElementById('updateProgressModal');
    modal.style.display = 'none';
}

function updateGoals(customerId) {
    const modal = document.getElementById('goalsModal');
    const form = document.getElementById('goalsForm');

    // Simulate fetching current goals
    const currentGoals = {
        weeklyVisits: 4,
        sessionDuration: 60,
        weightGoal: 65,
        targetDate: '2024-12-31'
    };

    // Populate form fields
    Object.keys(currentGoals).forEach(key => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            input.value = currentGoals[key];
        }
    });

    modal.style.display = 'block';
}

// Helper Functions
function average(arr) {
    return (arr.reduce((a, b) => a + b, 0) / arr.length).toFixed(1);
}

// Form Submissions
document.getElementById('customerForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    // Handle customer form submission
    console.log('Saving customer data...');
    customerModal.style.display = 'none';
});

document.getElementById('goalsForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    // Handle goals form submission
    console.log('Saving goals...');
    goalsModal.style.display = 'none';
});

// Modal Close Functions
function closeCustomerModal() {
    customerModal.style.display = 'none';
}

function closeProgressModal() {
    progressModal.style.display = 'none';
}

function closeGoalsModal() {
    goalsModal.style.display = 'none';
}

function closeDeleteModal() {
    deleteModal.style.display = 'none';
}

// Initialize search and filters
document.getElementById('searchCustomer')?.addEventListener('input', function(e) {
    // Implement search functionality
    console.log('Searching:', e.target.value);
});

document.querySelectorAll('select[id$="Filter"]').forEach(select => {
    select.addEventListener('change', function() {
        // Implement filter functionality
        console.log('Filter changed:', select.id, select.value);
    });
});
