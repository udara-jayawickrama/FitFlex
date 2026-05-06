document.addEventListener('DOMContentLoaded', function() {
    // User type selector functionality
    const userTypeButtons = document.querySelectorAll('.user-type');
    
    userTypeButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons
            userTypeButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            button.classList.add('active');
        });
    });

    // Form submission handling
    const loginForm = document.querySelector('.login-form');
    loginForm.addEventListener('submit', (e) => {
        e.preventDefault();
        // Add your login logic here
        console.log('Login attempted');
    });
}); 