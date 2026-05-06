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
    header("Location: ../index.php");
    exit();
}

$trainerUsername = $_SESSION['username'];


$gymGoersQuery = "
    SELECT 
        g.username,
        g.full_name,
        g.email,
        g.phone,
        g.gender,
        g.membership_plan,
        g.workout_plan,
        g.profile_picture,
        v.last_visit
    FROM gym_goers g
    LEFT JOIN (
        SELECT username, MAX(visit_date) AS last_visit 
        FROM gym_visits 
        GROUP BY username
    ) v ON g.username = v.username
    WHERE g.preferred_trainer = ?
";
$gymGoersStmt = $conn->prepare($gymGoersQuery);
$gymGoersStmt->bind_param("s", $trainerUsername);
$gymGoersStmt->execute();
$gymGoersResult = $gymGoersStmt->get_result();
$gymGoers = $gymGoersResult->fetch_all(MYSQLI_ASSOC);
$gymGoersStmt->close();

// ---------------------------------------------------------------------
// 2) Helper function: get the workout plan name by plan_id
// ---------------------------------------------------------------------
function getWorkoutPlanName($conn, $planId) {
    if ($planId) {
        $query = "SELECT plan_name FROM workout_plans WHERE plan_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $planId);
        $stmt->execute();
        $result = $stmt->get_result();
        $plan = $result->fetch_assoc();
        $stmt->close();
        return $plan['plan_name'] ?? 'No Plan Assigned';
    }
    return 'No Plan Assigned';
}


function getMembershipPlanName($conn, $planId) {
    if ($planId) {
        $query = "SELECT plan_name FROM membership_plans WHERE plan_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $planId);
        $stmt->execute();
        $result = $stmt->get_result();
        $plan = $result->fetch_assoc();
        $stmt->close();
        return $plan['plan_name'] ?? 'No Membership Plan';
    }
    return 'No Membership Plan';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Gym Goers</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/trainer.css">
    <link rel="stylesheet" href="../assets/css/gym-goers.css">
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
                <a href="meal-plans.php">
                    <i class="fas fa-utensils"></i>
                    Meal Plans
                </a>
                <a href="gym-goers.php" class="active">
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
                    <h2>Gym Goers</h2>
                </div>
            </header>

            <div class="content-section">
                <div class="members-table-container">
                    <table class="members-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Membership</th>
                                <th>Workout Plan</th>
                                <th>Phone / Gender</th>
                                <th>Last Visit</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($gymGoers)): ?>
                                <tr>
                                    <td colspan="6">No gym goers assigned yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($gymGoers as $goer): ?>
                                    <tr>
                                        <td class="member-info">
                                            <div>
                                                <span class="member-name">
                                                    <?php echo htmlspecialchars($goer['full_name']); ?>
                                                </span>
                                                <span class="member-email">
                                                    <?php echo htmlspecialchars($goer['email']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                                echo htmlspecialchars(
                                                    getMembershipPlanName($conn, $goer['membership_plan'])
                                                ); 
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                                echo htmlspecialchars(
                                                    getWorkoutPlanName($conn, $goer['workout_plan'])
                                                ); 
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                                echo htmlspecialchars($goer['phone'] ?? 'N/A'); 
                                                echo " / ";
                                                echo htmlspecialchars($goer['gender'] ?? 'N/A');
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($goer['last_visit'] ?? 'N/A'); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge active">Active</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div> <!-- .members-table-container -->
            </div> <!-- .content-section -->
        </div> <!-- .main-content -->
    </div> <!-- .trainer-dashboard -->
    <?php $conn->close(); ?>

    <script src="../assets/js/gym-goers.js"></script>
</body>
</html>
