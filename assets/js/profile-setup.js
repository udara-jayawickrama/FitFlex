document.addEventListener('DOMContentLoaded', function() {
    // Profile Image Upload
    const profileUpload = document.getElementById('profileUpload');
    const profilePreview = document.getElementById('profilePreview');

    if (profileUpload && profilePreview) {
        profileUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Modal Functionality
    const modal = document.getElementById('trainerProfileModal');
    const closeBtn = document.querySelector('.close-btn');

    if (modal && closeBtn) {
        // View Profile Button Click Handler
        document.querySelectorAll('.view-profile-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const trainerId = this.getAttribute('data-trainer-id');
                console.log('Opening modal for trainer:', trainerId);
                modal.style.display = 'block';
            });
        });

        // Close Modal
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        // Close Modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    }

    // Step Navigation
    window.nextStep = function(step) {
        const currentStep = document.querySelector('.setup-step.active');
        const nextStep = document.getElementById(`step${step}`);
        const currentProgress = document.querySelector(`.progress-step[data-step="${step-1}"]`);
        const nextProgress = document.querySelector(`.progress-step[data-step="${step}"]`);

        if (currentStep && nextStep && currentProgress && nextProgress) {
            currentStep.classList.remove('active');
            nextStep.classList.add('active');
            currentProgress.classList.add('completed');
            nextProgress.classList.add('active');
        }
    };

    window.prevStep = function(step) {
        const currentStep = document.querySelector('.setup-step.active');
        const prevStep = document.getElementById(`step${step}`);
        const currentProgress = document.querySelector(`.progress-step[data-step="${step+1}"]`);
        const prevProgress = document.querySelector(`.progress-step[data-step="${step}"]`);

        if (currentStep && prevStep && currentProgress && prevProgress) {
            currentStep.classList.remove('active');
            prevStep.classList.add('active');
            currentProgress.classList.remove('active');
            prevProgress.classList.add('active');
        }
    };

    // Gym Selection
    document.querySelectorAll('.gym-card .select-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.gym-card').forEach(card => {
                card.classList.remove('selected');
            });
            this.closest('.gym-card').classList.add('selected');
        });
    });

    // Trainer Selection
    document.querySelectorAll('.trainer-card .select-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.trainer-card').forEach(card => {
                card.classList.remove('selected');
            });
            this.closest('.trainer-card').classList.add('selected');
        });
    });

    // Complete Registration
    window.completeRegistration = function() {
        // Add your registration completion logic here
        console.log('Registration completed!');
        // Redirect to dashboard or show success message
    };
});