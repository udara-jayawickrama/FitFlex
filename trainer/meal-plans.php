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

// Fetch Meal Plans for the logged-in trainer
$mealPlansQuery = "SELECT plan_id, plan_name, description, daily_calories, meals_per_day, protein_percentage, carbs_percentage, fats_percentage FROM meal_plans WHERE trainer_username = ?";
$mealPlansStmt = $conn->prepare($mealPlansQuery);
$mealPlansStmt->bind_param("s", $trainerUsername);
$mealPlansStmt->execute();
$mealPlansResult = $mealPlansStmt->get_result();
$mealPlans = $mealPlansResult->fetch_all(MYSQLI_ASSOC);
$mealPlansStmt->close();

// Process Form Submission (Add Meal Plan)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addMealPlan'])) {
    $planName = trim($_POST['planName']);
    $description = trim($_POST['description']);
    $dailyCalories = trim($_POST['dailyCalories']);
    $mealsPerDay = trim($_POST['mealsPerDay']);
    $proteinPercentage = trim($_POST['protein']);
    $carbsPercentage = trim($_POST['carbs']);
    $fatsPercentage = trim($_POST['fats']);
    // You would need to handle meal schedule more carefully, likely as JSON

    $insertMealPlanQuery = "INSERT INTO meal_plans (trainer_username, plan_name, description, daily_calories, meals_per_day, protein_percentage, carbs_percentage, fats_percentage) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $insertMealPlanStmt = $conn->prepare($insertMealPlanQuery);
    $insertMealPlanStmt->bind_param("sssiisii", $trainerUsername, $planName, $description, $dailyCalories, $mealsPerDay, $proteinPercentage, $carbsPercentage, $fatsPercentage);

    if ($insertMealPlanStmt->execute()) {
        $success = "Meal plan added successfully!";
    } else {
        $error = "Error adding meal plan: " . $insertMealPlanStmt->error;
    }
    $insertMealPlanStmt->close();
    header("Location: meal-plans.php?success=" . urlencode($success) . "&error=" . urlencode($error));
    exit();
}

// Process Meal Plan Deletion
if (isset($_GET['deletePlanId']) && !empty($_GET['deletePlanId'])) {
    $planIdToDelete = trim($_GET['deletePlanId']);
    $deleteMealPlanQuery = "DELETE FROM meal_plans WHERE plan_id = ? AND trainer_username = ?";
    $deleteMealPlanStmt = $conn->prepare($deleteMealPlanQuery);
    $deleteMealPlanStmt->bind_param("is", $planIdToDelete, $trainerUsername);

    if ($deleteMealPlanStmt->execute()) {
        $success = "Meal plan deleted successfully!";
    } else {
        $error = "Error deleting meal plan: " . $deleteMealPlanStmt->error;
    }
    $deleteMealPlanStmt->close();
    header("Location: meal-plans.php?success=" . urlencode($success) . "&error=" . urlencode($error));
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Meal Plans</title>
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
                <a href="workout-plans.php">
                    <i class="fas fa-dumbbell"></i>
                    Workout Plans
                </a>
                <a href="meal-plans.php" class="active">
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
                    <h2>Meal Plans</h2>
                </div>
                <button class="add-plan-btn" id="addMealBtn">
                    <i class="fas fa-plus"></i> Add New Meal Plan
                </button>
            </header>

            <div class="content-section">
                <div class="plans-grid">
                    <?php if (empty($mealPlans)): ?>
                        <p>No meal plans have been added yet.</p>
                    <?php else: ?>
                        <?php foreach ($mealPlans as $plan): ?>
                            <div class="plan-card" data-plan-id="<?php echo htmlspecialchars($plan['plan_id']); ?>">
                                <div class="plan-header">
                                    <h3><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
                                    <div class="plan-actions">
                                        <button class="delete-btn" title="Delete plan" data-plan-id="<?php echo htmlspecialchars($plan['plan_id']); ?>"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                                <div class="plan-details">
                                    <p class="plan-description"><?php echo htmlspecialchars($plan['description']); ?></p>
                                    <div class="plan-meta">
                                        <span><i class="fas fa-fire"></i> <?php echo htmlspecialchars($plan['daily_calories']); ?> kcal/day</span>
                                        <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($plan['meals_per_day']); ?> meals/day</span>
                                    </div>
                                    <div class="nutrition-info">
                                        <div class="macro">
                                            <span class="macro-label">Protein</span>
                                            <span class="macro-value"><?php echo htmlspecialchars($plan['protein_percentage']); ?>%</span>
                                        </div>
                                        <div class="macro">
                                            <span class="macro-label">Carbs</span>
                                            <span class="macro-value"><?php echo htmlspecialchars($plan['carbs_percentage']); ?>%</span>
                                        </div>
                                        <div class="macro">
                                            <span class="macro-label">Fats</span>
                                            <span class="macro-value"><?php echo htmlspecialchars($plan['fats_percentage']); ?>%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="mealPlanModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Meal Plan</h3>
                <button class="close-btn">&times;</button>
            </div>
            <form class="plan-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="editPlanId" id="editPlanId">
                <div class="form-group">
                    <label>Plan Name</label>
                    <input type="text" name="planName" required placeholder="Enter plan name">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required placeholder="Enter plan description"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Daily Calories</label>
                        <input type="number" name="dailyCalories" required min="1200" step="50" placeholder="e.g., 2000">
                    </div>
                    <div class="form-group">
                        <label>Meals per Day</label>
                        <input type="number" name="mealsPerDay" required min="3" max="8" placeholder="e.g., 6">
                    </div>
                </div>
                <div class="form-row macros">
                    <div class="form-group">
                        <label>Protein (%)</label>
                        <input type="number" name="protein" required min="0" max="100" class="macro-input" data-macro="protein">
                    </div>
                    <div class="form-group">
                        <label>Carbs (%)</label>
                        <input type="number" name="carbs" required min="0" max="100" class="macro-input" data-macro="carbs">
                    </div>
                    <div class="form-group">
                        <label>Fats (%)</label>
                        <input type="number" name="fats" required min="0" max="100" class="macro-input" data-macro="fats">
                    </div>
                </div>
                <div class="form-group">
                    <label>Meal Schedule</label>
                    <div id="mealSchedule">
                        <div class="meal-item">
                            <input type="time" required>
                            <input type="text" placeholder="Meal description" required>
                            <button type="button" class="remove-meal"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <button type="button" class="add-meal-btn" id="addMealBtn">
                        <i class="fas fa-plus"></i> Add Meal
                    </button>
                </div>
                <div class="form-actions">
                    <button type="submit" class="save-btn" name="addMealPlan">Save Plan</button>
                    <button type="button" class="cancel-btn" id="cancelAddMeal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const addMealBtn = document.getElementById('addMealBtn');
        const mealPlanModal = document.getElementById('mealPlanModal');
        const modalCloseBtn = mealPlanModal ? mealPlanModal.querySelector('.close-btn') : null;
        const cancelAddMealBtn = mealPlanModal ? mealPlanModal.querySelector('#cancelAddMeal') : null;
        const deleteButtons = document.querySelectorAll('.plan-actions .delete-btn');

        if (addMealBtn) {
            addMealBtn.addEventListener('click', function() {
                if (mealPlanModal) {
                    mealPlanModal.style.display = 'block';
                }
            });
        }

        if (modalCloseBtn) {
            modalCloseBtn.addEventListener('click', function() {
                if (mealPlanModal) {
                    mealPlanModal.style.display = 'none';
                }
            });
        }

        if (cancelAddMealBtn) {
            cancelAddMealBtn.addEventListener('click', function() {
                if (mealPlanModal) {
                    mealPlanModal.style.display = 'none';
                }
            });
        }

        window.addEventListener('click', function(event) {
            if (mealPlanModal && event.target == mealPlanModal) {
                mealPlanModal.style.display = 'none';
            }
        });

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const planId = this.getAttribute('data-plan-id');
                if (confirm('Are you sure you want to delete this meal plan?')) {
                    window.location.href = 'meal-plans.php?deletePlanId=' + planId;
                }
            });
        });
    </script>
</body>
</html>