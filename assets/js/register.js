document.addEventListener('DOMContentLoaded', function () {
    // User type selector functionality
    const userTypeButtons = document.querySelectorAll('.user-type');
    const membershipSection = document.getElementById('membership-section');
    const userTypeInput = document.createElement('input'); // Hidden input for user type
    userTypeInput.type = 'hidden';
    userTypeInput.name = 'user_type';
    document.querySelector('.register-form').appendChild(userTypeInput);

    userTypeButtons.forEach(button => {
        button.addEventListener('click', () => {
            userTypeButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            userTypeInput.value = button.dataset.type; // Set user type value
            if (button.dataset.type === 'gym-goer') {
                membershipSection.style.display = 'block';
            } else {
                membershipSection.style.display = 'none';
            }
        });
    });

    // Form validation
    const registerForm = document.querySelector('.register-form');
    const password = registerForm.querySelector('input[name="password"]');
    const confirmPassword = registerForm.querySelector('input[name="confirm_password"]');

    confirmPassword.addEventListener('input', () => {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity("Passwords don't match");
        } else {
            confirmPassword.setCustomValidity('');
        }
    });

    registerForm.addEventListener('submit', (e) => {
        if (!userTypeInput.value) {
            e.preventDefault();
            alert('Please select a user type.');
        }
    });
});
