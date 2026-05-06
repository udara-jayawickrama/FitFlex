<?php
session_start();

// Database connection settings
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "gym";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
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
    $preferredTrainer = $row['preferred_trainer'];
}
$preferredTrainerStmt->close();

// Handle workout plan selection
if (isset($_GET['select_plan']) && isset($_GET['plan_id'])) {
    $planId = $_GET['plan_id'];
    $updateQuery = "UPDATE gym_goers SET workout_plan = ? WHERE username = ?";
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


$workoutPlansQuery = "
    SELECT plan_id, plan_name, description, duration_weeks, sessions_per_week, difficulty_level
    FROM workout_plans
    WHERE trainer_username = ?
";
$workoutPlansStmt = $conn->prepare($workoutPlansQuery);
$workoutPlansStmt->bind_param("s", $preferredTrainer);
$workoutPlansStmt->execute();
$workoutPlansResult = $workoutPlansStmt->get_result();

$workoutPlans = [];
if ($workoutPlansResult->num_rows > 0) {
    while ($row = $workoutPlansResult->fetch_assoc()) {
        $workoutPlans[] = $row;  // Correctly append each row to the array
    }
}
$workoutPlansStmt->close();

// Fetch the currently selected workout plan for the user
$selectedPlanId = null;
$getSelectedPlanQuery = "SELECT workout_plan FROM gym_goers WHERE username = ?";
$selectedPlanStmt = $conn->prepare($getSelectedPlanQuery);
if (!$selectedPlanStmt) {
    echo "Error preparing statement: " . $conn->error;
    echo "<br>";
    echo "Connection Error: " . $conn->error;
}
$selectedPlanStmt->bind_param("s", $goerUsername);
$selectedPlanStmt->execute();

$selectedPlanResult = $selectedPlanStmt->get_result();
if ($selectedPlanResult->num_rows > 0) {
    $selectedPlan = $selectedPlanResult->fetch_assoc();
    $selectedPlanId = $selectedPlan['workout_plan'];
}
$selectedPlanStmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Workout Plans</title>
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
                <a href="meal-plans.php">
                    <i class="fas fa-utensils"></i>
                    Meal Plans
                </a>
                <a href="workouts.php" class="active">
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
                <h2>Workout Plans</h2>
                
            </header>

            <div class="workout-plans-container">
                <section class="featured-plans">
                    <h3>Featured Workout Plans</h3>
                    <div class="plans-grid" id="workoutPlansGrid">
                        <?php if (!empty($workoutPlans)): ?>
                            <?php foreach ($workoutPlans as $plan): ?>
                                <div class="plan-card">
                                    
                                    <div class="plan-content">
                                        <h4><?php echo htmlspecialchars($plan['plan_name']); ?></h4>
                                        <p class="plan-description">
                                            <?php echo htmlspecialchars($plan['description']); ?>
                                        </p>
                                        <div class="plan-details">
                                            <p><strong>Duration:</strong> 
                                                <?php echo htmlspecialchars($plan['duration_weeks']); ?> weeks
                                            </p>
                                            <p><strong>Sessions/Week:</strong> 
                                                <?php echo htmlspecialchars($plan['sessions_per_week']); ?>
                                            </p>
                                        </div>
                                        <button
                                            class="select-plan-btn <?php if ($plan['plan_id'] == $selectedPlanId) echo 'selected-plan'; ?>"
                                            data-plan-id="<?php echo htmlspecialchars($plan['plan_id']); ?>">
                                            <?php echo ($plan['plan_id'] == $selectedPlanId) ? 'Selected' : 'Select Plan'; ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No workout plans available from your preferred trainer.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const workoutPlansGrid = document.getElementById('workoutPlansGrid');
            workoutPlansGrid.addEventListener('click', function(event) {
                if (event.target.classList.contains('select-plan-btn')) {
                    const planId = event.target.dataset.planId;
                    const buttonElement = event.target;

                    fetch('workouts.php?select_plan=1&plan_id=' + planId)
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
                        .catch(error => console.error('Error selecting workout plan:', error));
                }
            });
        });
    </script>
</body>
</html>
