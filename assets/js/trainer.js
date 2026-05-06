document.addEventListener('DOMContentLoaded', function() {
    const editProfileBtn = document.getElementById('editProfileBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const profileDetails = document.getElementById('profileDetails');
    const editProfileForm = document.getElementById('editProfileForm');

    // Toggle between view and edit mode
    editProfileBtn.addEventListener('click', () => {
        profileDetails.style.display = 'none';
        editProfileForm.style.display = 'block';
    });

    cancelEditBtn.addEventListener('click', () => {
        profileDetails.style.display = 'block';
        editProfileForm.style.display = 'none';
    });

    // Handle form submission
    editProfileForm.addEventListener('submit', (e) => {
        e.preventDefault();
        // Add your save logic here
        console.log('Profile update attempted');
        
        // Switch back to view mode
        profileDetails.style.display = 'block';
        editProfileForm.style.display = 'none';
    });
}); 