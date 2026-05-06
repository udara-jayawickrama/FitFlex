<?php
session_start();

// Database connection settings
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "gym";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if gym goer is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php"); // Redirect to login page
    exit();
}

$goerUsername = $_SESSION['username'];

// Get the preferred trainer for the current gym goer
$preferredTrainerQuery = "SELECT preferred_trainer FROM gym_goers WHERE username = ?";
$preferredTrainerStmt = $conn->prepare($preferredTrainerQuery);
$preferredTrainerStmt->bind_param("s", $goerUsername);
$preferredTrainerStmt->execute();
$preferredTrainerResult = $preferredTrainerStmt->get_result();
$preferredTrainer = null;
if ($preferredTrainerResult->num_rows > 0) {
    $row = $preferredTrainerResult->fetch_assoc();
    $preferredTrainer = $row['preferred_trainer'] ?? '';
}
$preferredTrainerStmt->close();

// Handle meal plan selection via AJAX
if (isset($_GET['select_plan']) && isset($_GET['plan_id'])) {
    $planId = $_GET['plan_id'];
    $updateQuery = "UPDATE gym_goers SET meal_plan = ? WHERE username = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("is", $planId, $goerUsername);
    if ($updateStmt->execute()) {
        echo "Selected"; // Send "Selected" back to JS
    } else {
        echo "Error selecting plan: " . $updateStmt->error;
    }
    $updateStmt->close();
    exit(); // Stop further execution
}

$mealPlansQuery = "SELECT plan_id, plan_name, description, daily_calories, meals_per_day, protein_percentage, carbs_percentage, fats_percentage, meal_schedule 
                   FROM meal_plans 
                   WHERE trainer_username = ?";
$mealPlansStmt = $conn->prepare($mealPlansQuery);
$mealPlansStmt->bind_param("s", $preferredTrainer);
$mealPlansStmt->execute();
$mealPlansResult = $mealPlansStmt->get_result();

$mealPlans = [];
if ($mealPlansResult->num_rows > 0) {
    while ($row = $mealPlansResult->fetch_assoc()) {
        $mealPlans[] = $row;  // Append each row to the $mealPlans array
    }
}
$mealPlansStmt->close();

// Fetch the currently selected meal plan for the user
$selectedPlanId = null;
$getSelectedPlanQuery = "SELECT meal_plan FROM gym_goers WHERE username = ?";
$selectedPlanStmt = $conn->prepare($getSelectedPlanQuery);
if (!$selectedPlanStmt) {
    echo "Error preparing statement: " . $conn->error;
    exit();
}
$selectedPlanStmt->bind_param("s", $goerUsername);
$selectedPlanStmt->execute();
$selectedPlanResult = $selectedPlanStmt->get_result();
if ($selectedPlanResult->num_rows > 0) {
    $selectedPlan = $selectedPlanResult->fetch_assoc();
    $selectedPlanId = $selectedPlan['meal_plan'] ?? '';
}
$selectedPlanStmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Meal Plans</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/gym-goer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .selected-plan {
            background-color: #28a745 !important;
            color: white !important;
        }
        .plan-details p {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="member-dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Member Dashboard</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
                <a href="profile.php">
                    <i class="fas fa-user"></i>
                    My Profile
                </a>
                <a href="meal-plans.php" class="active">
                    <i class="fas fa-utensils"></i>
                    Meal Plans
                </a>
                <a href="workouts.php">
                    <i class="fas fa-dumbbell"></i>
                    Workouts
                </a>
                <a href="shop.php">
                    <i class="fas fa-shopping-cart"></i>
                    Shop
                </a>
                <a href="order_history.php">
                    <i class="fas fa-history"></i>
                    Order History
                </a>
                <a href="record-visit.php">
                    <i class="fa-solid fa-person-walking"></i>
                    Gym Visit
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
                <h2>Meal Plans</h2>
                <div class="header-actions">
                    <!-- Add any header actions here if needed -->
                </div>
            </header>

            <div class="meal-plans-container">
                <section class="featured-plans">
                    <h3>Meal Plans by Your Trainer</h3>
                    <div class="plans-grid" id="mealPlansGrid">
                        <?php if (!empty($mealPlans)): ?>
                            <?php foreach ($mealPlans as $plan): ?>
                                <div class="plan-card">
                                    <div class="plan-header">
                                        <!-- You can add a header image if available -->
                                    </div>
                                    <div class="plan-content">
                                        <h4><?php echo htmlspecialchars($plan['plan_name'] ?? ''); ?></h4>
                                        <p class="plan-description">
                                            <?php echo htmlspecialchars($plan['description'] ?? ''); ?>
                                        </p>
                                        <div class="plan-details">
                                            <p><strong>Daily Calories:</strong> 
                                                <?php echo htmlspecialchars($plan['daily_calories'] ?? ''); ?>
                                            </p>
                                            <p><strong>Meals Per Day:</strong> 
                                                <?php echo htmlspecialchars($plan['meals_per_day'] ?? ''); ?>
                                            </p>
                                            <p><strong>Protein:</strong> 
                                                <?php echo htmlspecialchars($plan['protein_percentage'] ?? ''); ?>%
                                            </p>
                                            <p><strong>Carbs:</strong> 
                                                <?php echo htmlspecialchars($plan['carbs_percentage'] ?? ''); ?>%
                                            </p>
                                            <p><strong>Fats:</strong> 
                                                <?php echo htmlspecialchars($plan['fats_percentage'] ?? ''); ?>%
                                            </p>
                                            <p><strong>Schedule:</strong> 
                                                <?php echo htmlspecialchars($plan['meal_schedule'] ?? ''); ?>
                                            </p>
                                        </div>
                                        <button class="select-plan-btn <?php if (($plan['plan_id'] ?? '') == ($selectedPlanId ?? '')) echo 'selected-plan'; ?>" 
                                            data-plan-id="<?php echo htmlspecialchars($plan['plan_id'] ?? ''); ?>">
                                            <?php echo (($plan['plan_id'] ?? '') == ($selectedPlanId ?? '')) ? 'Selected' : 'Select Plan'; ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No meal plans available from your preferred trainer.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mealPlansGrid = document.getElementById('mealPlansGrid');
            mealPlansGrid.addEventListener('click', function(event) {
                if (event.target.classList.contains('select-plan-btn')) {
                    const planId = event.target.dataset.planId;
                    const buttonElement = event.target;

                    fetch('meal-plans.php?select_plan=1&plan_id=' + planId)
                        .then(response => response.text())
                        .then(message => {
                            // Reset button styles
                            document.querySelectorAll('.select-plan-btn').forEach(btn => {
                                btn.classList.remove('selected-plan');
                                btn.innerText = 'Select Plan';
                            });
                            // Highlight the selected button
                            buttonElement.classList.add('selected-plan');
                            buttonElement.innerText = 'Selected';
                            console.log(message);
                        })
                        .catch(error => console.error('Error selecting meal plan:', error));
                }
            });
        });
    </script>
</body>
</html>
