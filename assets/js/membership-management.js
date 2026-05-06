// Membership Plan Modal Functions
function openAddPlanModal() {
    const modal = document.getElementById('addPlanModal');
    modal.style.display = 'block';
}

function closeAddPlanModal() {
    const modal = document.getElementById('addPlanModal');
    modal.style.display = 'none';
}

function editPlan(planId) {
    const modal = document.getElementById('editPlanModal');
    modal.style.display = 'block';
    // Add logic to load plan details for editing
}

function deletePlan(planId) {
    const modal = document.getElementById('deletePlanModal');
    modal.style.display = 'block';
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
    // Plan Form
    const planForm = document.getElementById('planForm');
    if (planForm) {
        planForm.onsubmit = function(e) {
            e.preventDefault();
            // Add logic to save plan
            closeAddPlanModal();
        }
    }
}
