document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('workoutPlanModal');
    const modalTitle = modal.querySelector('.modal-header h3');
    const addWorkoutBtn = document.getElementById('addWorkoutBtn');
    const closeBtn = document.querySelector('.close-btn');
    const addExerciseBtn = document.getElementById('addExerciseBtn');
    const exercisesContainer = document.getElementById('exercisesContainer');
    const planForm = document.querySelector('.plan-form');

    // Function to open modal
    function openModal(isEditing = false, planData = null) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Update modal title and form based on mode
        modalTitle.textContent = isEditing ? 'Edit Workout Plan' : 'Add New Workout Plan';
        
        if (isEditing && planData) {
            // Fill form with existing plan data
            planForm.querySelector('input[placeholder="Enter plan name"]').value = planData.name;
            planForm.querySelector('textarea').value = planData.description;
            planForm.querySelector('input[type="number"][min="1"]').value = planData.duration;
            planForm.querySelector('input[type="number"][max="7"]').value = planData.sessions;
            planForm.querySelector('select').value = planData.difficulty;

            // Clear existing exercises
            exercisesContainer.innerHTML = '';
            
            // Add existing exercises
            planData.exercises.forEach(exercise => {
                addExerciseField(exercise.name, exercise.setsReps);
            });
        } else {
            // Reset form for new plan
            planForm.reset();
            exercisesContainer.innerHTML = `
                <div class="exercise-item">
                    <input type="text" placeholder="Exercise name">
                    <input type="text" placeholder="Sets x Reps">
                    <button type="button" class="remove-exercise"><i class="fas fa-times"></i></button>
                </div>
            `;
        }
    }

    // Function to add exercise field
    function addExerciseField(exerciseName = '', setsReps = '') {
        const exerciseItem = document.createElement('div');
        exerciseItem.className = 'exercise-item';
        exerciseItem.innerHTML = `
            <input type="text" placeholder="Exercise name" value="${exerciseName}">
            <input type="text" placeholder="Sets x Reps" value="${setsReps}">
            <button type="button" class="remove-exercise"><i class="fas fa-times"></i></button>
        `;
        exercisesContainer.appendChild(exerciseItem);
    }

    // Open modal for new plan
    addWorkoutBtn.addEventListener('click', () => openModal(false));

    // Open modal for editing
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            // Get plan data from the card
            const planCard = btn.closest('.plan-card');
            const planData = {
                name: planCard.querySelector('h3').textContent,
                description: planCard.querySelector('.plan-description').textContent,
                duration: parseInt(planCard.querySelector('.fa-clock').parentElement.textContent),
                sessions: parseInt(planCard.querySelector('.fa-dumbbell').parentElement.textContent),
                difficulty: planCard.querySelector('.stat-value').textContent.toLowerCase(),
                exercises: [] // You would need to store exercises data in the card or fetch from backend
            };
            
            // Example exercises (in real app, you'd get this from your data source)
            planData.exercises = [
                { name: 'Squats', setsReps: '3x12' },
                { name: 'Push-ups', setsReps: '3x15' }
            ];

            openModal(true, planData);
        });
    });

    // Close modal
    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    });

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });

    // Add new exercise field
    addExerciseBtn.addEventListener('click', () => addExerciseField());

    // Remove exercise field
    exercisesContainer.addEventListener('click', (e) => {
        if (e.target.closest('.remove-exercise')) {
            const exerciseItems = exercisesContainer.querySelectorAll('.exercise-item');
            if (exerciseItems.length > 1) {
                e.target.closest('.exercise-item').remove();
            } else {
                alert('You must have at least one exercise!');
            }
        }
    });

    // Handle form submission
    planForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        // Collect form data
        const formData = {
            name: planForm.querySelector('input[placeholder="Enter plan name"]').value,
            description: planForm.querySelector('textarea').value,
            duration: planForm.querySelector('input[type="number"][min="1"]').value,
            sessions: planForm.querySelector('input[type="number"][max="7"]').value,
            difficulty: planForm.querySelector('select').value,
            exercises: Array.from(exercisesContainer.querySelectorAll('.exercise-item')).map(item => ({
                name: item.querySelector('input[placeholder="Exercise name"]').value,
                setsReps: item.querySelector('input[placeholder="Sets x Reps"]').value
            }))
        };

        // Log the collected data (replace with your save logic)
        console.log('Plan data:', formData);
        
        // Close modal
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    });
}); 