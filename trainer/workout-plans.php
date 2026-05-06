<?php
session_start();

// Database connection settings
$servername    = "localhost";
$db_username   = "root";
$db_password   = "";
$dbname        = "gym";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if trainer is logged in
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'trainer') {
    header("Location: ../index.php"); // Redirect to login page if not logged in or not a trainer
    exit();
}

$trainerUsername = $_SESSION['username'];
$error = "";
$success = "";


$trainerDetailsQuery = "SELECT full_name, email, phone, specialization, experience, certifications 
                        FROM trainers 
                        WHERE username = ?";
$trainerDetailsStmt = $conn->prepare($trainerDetailsQuery);
$trainerDetailsStmt->bind_param("s", $trainerUsername);
$trainerDetailsStmt->execute();
$trainerDetailsResult = $trainerDetailsStmt->get_result();
$trainer = $trainerDetailsResult->fetch_assoc() ?? [];
$trainerDetailsStmt->close();


$workoutPlansQuery = "SELECT plan_id, plan_name, description, duration_weeks, 
                             sessions_per_week, difficulty_level 
                      FROM workout_plans 
                      WHERE trainer_username = ?
                      ORDER BY plan_id ASC";  // optional ORDER BY
$workoutPlansStmt = $conn->prepare($workoutPlansQuery);
$workoutPlansStmt->bind_param("s", $trainerUsername);
$workoutPlansStmt->execute();
$workoutPlansResult = $workoutPlansStmt->get_result();

$workoutPlans = [];
while ($plan = $workoutPlansResult->fetch_assoc()) {
    $plan_id = $plan['plan_id'];

    // Count how many gym_goers have this plan
    $activeUsersQuery = "SELECT COUNT(*) AS activeUsers 
                         FROM gym_goers 
                         WHERE workout_plan = ?";
    $activeUsersStmt = $conn->prepare($activeUsersQuery);
    $activeUsersStmt->bind_param("i", $plan_id);
    $activeUsersStmt->execute();
    $activeUsersRow = $activeUsersStmt->get_result()->fetch_assoc();
    $activeUsersStmt->close();

    // Add active_users to the $plan array
    $plan['active_users'] = $activeUsersRow['activeUsers'];

    // Push this plan into the $workoutPlans array
    $workoutPlans[] = $plan;
}
$workoutPlansStmt->close();


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['saveProfile'])) {
    $fullName         = trim($_POST['fullName']);
    $email            = trim($_POST['email']);
    $phone            = trim($_POST['phone']);
    $specialization   = trim($_POST['specialization']);
    $experienceYears  = trim($_POST['experience']);
    $certifications   = trim($_POST['certifications']);

    $updateTrainerQuery = "UPDATE trainers 
                           SET full_name = ?, 
                               email = ?, 
                               phone = ?, 
                               specialization = ?, 
                               experience = ?, 
                               certifications = ? 
                           WHERE username = ?";
    $updateTrainerStmt = $conn->prepare($updateTrainerQuery);
    $updateTrainerStmt->bind_param(
        "sssssss", 
        $fullName, 
        $email, 
        $phone, 
        $specialization, 
        $experienceYears, 
        $certifications, 
        $trainerUsername
    );

    if ($updateTrainerStmt->execute()) {
        $success = "Profile updated successfully!";

        // Refetch trainer details to update the form
        $trainerDetailsStmt = $conn->prepare($trainerDetailsQuery);
        $trainerDetailsStmt->bind_param("s", $trainerUsername);
        $trainerDetailsStmt->execute();
        $trainerDetailsResult = $trainerDetailsStmt->get_result();
        $trainer = $trainerDetailsResult->fetch_assoc() ?? [];
        $trainerDetailsStmt->close();
    } else {
        $error = "Error updating profile: " . $updateTrainerStmt->error;
    }
    $updateTrainerStmt->close();

    header("Location: trainer-dashboard.php?success=" . urlencode($success) . "&error=" . urlencode($error));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add_plan') {
    $planName        = trim($_POST['plan_name']);
    $description     = trim($_POST['description']);
    $durationWeeks   = (int)$_POST['duration_weeks'];
    $sessionsPerWeek = (int)$_POST['sessions_per_week'];
    $difficultyLevel = trim($_POST['difficulty_level']);
    $exercises       = trim($_POST['exercises']);  // We'll store all exercises in one text column

    // Basic validation
    if (
        empty($planName) || empty($description) ||
        $durationWeeks <= 0 || $sessionsPerWeek <= 0 ||
        empty($difficultyLevel)
    ) {
        $error = "Please fill all required fields for the new plan.";
    } else {
        // Insert the new plan
        $insertQuery = "INSERT INTO workout_plans
            (trainer_username, plan_name, description, duration_weeks, sessions_per_week, 
             difficulty_level, exercises, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param(
            "sssiiss",
            $trainerUsername,
            $planName,
            $description,
            $durationWeeks,
            $sessionsPerWeek,
            $difficultyLevel,
            $exercises
        );

        if ($stmt->execute()) {
            $success = "New workout plan added successfully!";
        } else {
            $error = "Error adding plan: " . $stmt->error;
        }
        $stmt->close();
    }

    // Redirect to see the updated list
    header("Location: workout-plans.php?success=" . urlencode($success) . "&error=" . urlencode($error));
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete_plan') {
    $planId = (int)$_POST['plan_id'];
    // Only delete plan if it belongs to this trainer
    $deleteQuery = "DELETE FROM workout_plans WHERE plan_id = ? AND trainer_username = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("is", $planId, $trainerUsername);

    if ($stmt->execute()) {
        $success = "Plan deleted successfully!";
    } else {
        $error = "Error deleting plan: " . $stmt->error;
    }
    $stmt->close();

    header("Location: workout-plans.php?success=" . urlencode($success) . "&error=" . urlencode($error));
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Workout Plans</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/trainer.css">
    <link rel="stylesheet" href="../assets/css/plans.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="trainer-dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Trainer Dashboard</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php">
                    <i class="fas fa-user-circle"></i>
                    My Account
                </a>
                <a href="workout-plans.php" class="active">
                    <i class="fas fa-dumbbell"></i>
                    Workout Plans
                </a>
                <a href="meal-plans.php">
                    <i class="fas fa-utensils"></i>
                    Meal Plans
                </a>
                <a href="gym-goers.php">
                    <i class="fas fa-users"></i>
                    Gym Goers
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <div class="main-content">
            <header class="dashboard-header">
                <div class="header-title">
                    <h2>Workout Plans</h2>
                </div>
                <button class="add-plan-btn" id="addWorkoutBtn">
                    <i class="fas fa-plus"></i> Add New Plan
                </button>
            </header>

            <div class="content-section">
                <!-- Display success/error from GET params -->
                <?php if (isset($_GET['success']) && $_GET['success']): ?>
                    <div class="success-message" style="color: green; margin-bottom: 10px;">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error']): ?>
                    <div class="error-message" style="color: red; margin-bottom: 10px;">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="plans-grid">
                    <?php if (empty($workoutPlans)): ?>
                        <p>No workout plans have been added yet.</p>
                    <?php else: ?>
                        <?php foreach ($workoutPlans as $plan): ?>
                            <div class="plan-card" data-plan-id="<?php echo htmlspecialchars($plan['plan_id']); ?>">
                                <div class="plan-header">
                                    <h3><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
                                    <div class="plan-actions">
                                        <form method="POST" 
                                              action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" 
                                              onsubmit="return confirm('Are you sure you want to delete this plan?');">
                                            <input type="hidden" name="action" value="delete_plan">
                                            <input type="hidden" name="plan_id" value="<?php echo htmlspecialchars($plan['plan_id']); ?>">
                                            <button class="delete-btn" title="Delete plan"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                                <div class="plan-details">
                                    <p class="plan-description"><?php echo htmlspecialchars($plan['description']); ?></p>
                                    <div class="plan-meta">
                                        <span>
                                            <i class="fas fa-clock"></i> 
                                            <?php echo htmlspecialchars($plan['duration_weeks']); ?> weeks
                                        </span>
                                        <span>
                                            <i class="fas fa-dumbbell"></i> 
                                            <?php echo htmlspecialchars($plan['sessions_per_week']); ?> days/week
                                        </span>
                                    </div>
                                    <div class="plan-stats">
                                        <div class="stat">
                                            <span class="stat-label">Difficulty</span>
                                            <span class="stat-value"><?php echo htmlspecialchars($plan['difficulty_level']); ?></span>
                                        </div>
                                        <div class="stat">
                                            <span class="stat-label">Active Users</span>
                                            <span class="stat-value"><?php echo htmlspecialchars($plan['active_users']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- .plan-card -->
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div> <!-- .plans-grid -->
            </div> <!-- .content-section -->
        </div> <!-- .main-content -->
    </div> <!-- .trainer-dashboard -->

    <!-- ADD PLAN MODAL -->
    <div class="modal" id="workoutPlanModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Workout Plan</h3>
                <button class="close-btn">&times;</button>
            </div>
            <form class="plan-form" id="planForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="action" value="add_plan">
                <div class="form-group">
                    <label>Plan Name</label>
                    <input type="text" name="plan_name" required placeholder="Enter plan name">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required placeholder="Enter plan description"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Duration (weeks)</label>
                        <input type="number" name="duration_weeks" required min="1">
                    </div>
                    <div class="form-group">
                        <label>Sessions per Week</label>
                        <input type="number" name="sessions_per_week" required min="1" max="7">
                    </div>
                </div>
                <div class="form-group">
                    <label>Difficulty Level</label>
                    <select name="difficulty_level" required>
                        <option value="">Select difficulty</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>
                <!-- We'll store the final exercise list in a hidden input -->
                <input type="hidden" name="exercises" id="exercisesInput" value="">
                <div class="form-group">
                    <label>Exercises</label>
                    <div class="exercises-container" id="exercisesContainer">
                        <!-- Default row -->
                        <div class="exercise-item">
                            <input type="text" placeholder="Exercise name">
                            <input type="text" placeholder="Sets x Reps">
                            <button type="button" class="remove-exercise">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="add-exercise-btn" id="addExerciseBtn">
                        <i class="fas fa-plus"></i> Add Exercise
                    </button>
                </div>
                <div class="form-actions">
                    <button type="submit" class="save-btn">Save Plan</button>
                    <button type="button" class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>

    const addWorkoutBtn      = document.getElementById('addWorkoutBtn');
    const workoutPlanModal   = document.getElementById('workoutPlanModal');
    const closeBtn           = workoutPlanModal.querySelector('.close-btn');
    const cancelBtn          = workoutPlanModal.querySelector('.cancel-btn');
    const planForm           = document.getElementById('planForm');
    const exercisesContainer = document.getElementById('exercisesContainer');
    const exercisesInput     = document.getElementById('exercisesInput');
    const addExerciseBtn     = document.getElementById('addExerciseBtn');

    // Show the modal
    addWorkoutBtn.addEventListener('click', () => {
        workoutPlanModal.style.display = 'block';
    });

    // Close modal function
    function closeModal() {
        workoutPlanModal.style.display = 'none';
    }

    // Close button and Cancel button
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', (e) => {
        e.preventDefault();
        closeModal();
    });


    addExerciseBtn.addEventListener('click', () => {
        const div = document.createElement('div');
        div.className = 'exercise-item';
        div.innerHTML = `
            <input type="text" placeholder="Exercise name">
            <input type="text" placeholder="Sets x Reps">
            <button type="button" class="remove-exercise"><i class="fas fa-times"></i></button>
        `;
        exercisesContainer.appendChild(div);
    });

    exercisesContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('fa-times')) {
            const exerciseItem = e.target.closest('.exercise-item');
            if (exerciseItem) {
                exercisesContainer.removeChild(exerciseItem);
            }
        }
    });


    planForm.addEventListener('submit', () => {
        const exerciseItems = exercisesContainer.querySelectorAll('.exercise-item');
        let exercisesArr = [];
        exerciseItems.forEach(item => {
            const inputs = item.querySelectorAll('input');
            if (inputs.length === 2) {
                const exerciseName = inputs[0].value.trim();
                const setsReps     = inputs[1].value.trim();
                // Only push if user typed something
                if (exerciseName || setsReps) {
                    exercisesArr.push(exerciseName + ' (' + setsReps + ')');
                }
            }
        });
        exercisesInput.value = exercisesArr.join('; ');
    });


    window.addEventListener('DOMContentLoaded', function() {
        const successMessage = document.querySelector('.success-message');
        const errorMessage   = document.querySelector('.error-message');

        if (successMessage || errorMessage) {
            setTimeout(() => {
                if (successMessage) successMessage.style.display = 'none';
                if (errorMessage)   errorMessage.style.display   = 'none';
                // Remove ?success=...&error=... from URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }, 3000);
        }
    });
    </script>
</body>
</html>
