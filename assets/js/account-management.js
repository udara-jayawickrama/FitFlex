// Account Management JavaScript

// Toggle edit mode for gym info form
function toggleEditMode(formId) {
    const form = document.getElementById(`${formId}Form`);
    const inputs = form.querySelectorAll('input, textarea, select');
    const actions = form.querySelector('.form-actions');
    const editBtn = document.querySelector('.edit-btn');

    inputs.forEach(input => {
        input.disabled = !input.disabled;
    });

    actions.style.display = inputs[0].disabled ? 'none' : 'flex';
    editBtn.style.display = inputs[0].disabled ? 'flex' : 'none';
}

// Cancel edit mode
function cancelEdit(formId) {
    const form = document.getElementById(`${formId}Form`);
    const inputs = form.querySelectorAll('input, textarea, select');
    const actions = form.querySelector('.form-actions');
    const editBtn = document.querySelector('.edit-btn');

    inputs.forEach(input => {
        input.disabled = true;
        // Reset to original value
        input.value = input.defaultValue;
    });

    actions.style.display = 'none';
    editBtn.style.display = 'flex';
}

// Handle gym info form submission
document.getElementById('gymInfoForm').onsubmit = function(e) {
    e.preventDefault();
    
    // TODO: Add API call to update gym info
    const formData = new FormData(this);
    console.log('Updating gym info:', Object.fromEntries(formData));

    // Disable inputs and hide actions
    const inputs = this.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.disabled = true;
        input.defaultValue = input.value; // Update default values
    });

    this.querySelector('.form-actions').style.display = 'none';
    document.querySelector('.edit-btn').style.display = 'flex';
};

// Membership Plan Management
let currentPlanId = null;

// Open add plan modal
function openAddPlanModal() {
    const modal = document.getElementById('planModal');
    const form = document.getElementById('planForm');
    
    // Reset form
    form.reset();
    modal.querySelector('h3').textContent = 'Add New Plan';
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Open edit plan modal
function editPlan(planId) {
    const modal = document.getElementById('planModal');
    const form = document.getElementById('planForm');
    
    modal.querySelector('h3').textContent = 'Edit Plan';
    currentPlanId = planId;

    // TODO: Add API call to get plan details
    // Example data
    const planData = {
        name: 'Basic Plan',
        price: '29.99',
        features: 'Access to gym equipment\nLocker room access\nBasic fitness assessment',
        status: 'active'
    };

    // Populate form
    form.elements.planName.value = planData.name;
    form.elements.price.value = planData.price;
    form.elements.features.value = planData.features;
    form.elements.status.value = planData.status;

    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Close plan modal
function closePlanModal() {
    const modal = document.getElementById('planModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    currentPlanId = null;
}

// Handle plan form submission
document.getElementById('planForm').onsubmit = function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    if (currentPlanId) {
        // TODO: Add API call to update plan
        console.log('Updating plan:', currentPlanId, Object.fromEntries(formData));
    } else {
        // TODO: Add API call to create plan
        console.log('Creating new plan:', Object.fromEntries(formData));
    }

    closePlanModal();
};

// Delete plan
function deletePlan(planId) {
    currentPlanId = planId;
    document.getElementById('deleteModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Close delete modal
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    currentPlanId = null;
}

// Confirm plan deletion
function confirmDelete() {
    if (currentPlanId) {
        // TODO: Add API call to delete plan
        console.log('Deleting plan:', currentPlanId);
        closeDeleteModal();
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
        currentPlanId = null;
    }
};

// Load initial data
document.addEventListener('DOMContentLoaded', function() {
    // TODO: Add API call to get gym info
    // Example data
    const gymData = {
        gymName: 'Fitness Hub',
        ownerName: 'John Doe',
        email: 'john@fitnesshub.com',
        phone: '+1234567890',
        address: '123 Fitness Street\nCity, State 12345',
        weekdayOpen: '06:00',
        weekdayClose: '22:00',
        weekendOpen: '08:00',
        weekendClose: '20:00'
    };

    // Populate gym info form
    const form = document.getElementById('gymInfoForm');
    form.elements.gymName.value = gymData.gymName;
    form.elements.ownerName.value = gymData.ownerName;
    form.elements.email.value = gymData.email;
    form.elements.phone.value = gymData.phone;
    form.elements.address.value = gymData.address;
    form.elements.weekdayOpen.value = gymData.weekdayOpen;
    form.elements.weekdayClose.value = gymData.weekdayClose;
    form.elements.weekendOpen.value = gymData.weekendOpen;
    form.elements.weekendClose.value = gymData.weekendClose;

    // Set default values for form reset
    Array.from(form.elements).forEach(input => {
        input.defaultValue = input.value;
    });
});
