<?php
session_start();

// Database connection settings
$servername    = "localhost";
$db_username = "root";
$db_password = "";
$dbname        = "gym";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if gym owner is logged in
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'gym-owner') {
    header("Location: ../index.php"); // Redirect to login page if not logged in or not a gym owner
    exit();
}

$gymOwnerUsername = $_SESSION['username'];

// Fetch Gyms owned by the current owner
$gymsQuery = "SELECT gym_username, gym_name FROM gyms WHERE owner_username = ?"; 
$gymsStmt = $conn->prepare($gymsQuery);
$gymsStmt->bind_param("s", $gymOwnerUsername);
$gymsStmt->execute();
$gymsResult = $gymsStmt->get_result();
$ownedGyms =[];
while ($row = $gymsResult->fetch_assoc()) {
    $ownedGyms[$row['gym_username']] = $row['gym_name'];
}
$gymsStmt->close();

// Determine the selected gym
$selectedGymUsername = $_SESSION['selected_gym_username'] ?? array_key_first($ownedGyms);
if (isset($_POST['select_gym']) && isset($_POST['gym_username'])) {
    $selectedGymUsername = $_POST['gym_username'];
    $_SESSION['selected_gym_username'] = $selectedGymUsername;
}

// Count Total Trainers for the Selected Gym
$totalTrainers = 0;
if ($selectedGymUsername) {
    $trainerCountQuery = "SELECT COUNT(*) AS total_trainers FROM trainers WHERE gym_username = ?";
    $trainerCountStmt = $conn->prepare($trainerCountQuery);
    $trainerCountStmt->bind_param("s", $selectedGymUsername);
    $trainerCountStmt->execute();
    $trainerCountResult = $trainerCountStmt->get_result();
    $totalTrainers = $trainerCountResult->fetch_assoc()['total_trainers'] ?? 0;
    $trainerCountStmt->close();
}

// Count Active Members (Gym Goers) for the Selected Gym
$activeMembers = 0;
if ($selectedGymUsername) {
    $memberCountQuery = "SELECT COUNT(*) AS active_members FROM gym_goers WHERE gym_username = ?";
    $memberCountStmt = $conn->prepare($memberCountQuery);
    $memberCountStmt->bind_param("s", $selectedGymUsername);
    $memberCountStmt->execute();
    $memberCountResult = $memberCountStmt->get_result();
    $activeMembers = $memberCountResult->fetch_assoc()['active_members'] ?? 0;
    $memberCountStmt->close();
}


// Fetch Last Joined New Member for the Selected Gym
$lastNewMember = 'No new members';
if ($selectedGymUsername) {
    $lastMemberQuery = "SELECT full_name FROM gym_goers WHERE gym_username = ? ORDER BY username DESC LIMIT 1"; // Using username as a proxy for registration time
    $lastMemberStmt = $conn->prepare($lastMemberQuery);
    $lastMemberStmt->bind_param("s", $selectedGymUsername);
    $lastMemberStmt->execute();
    $lastMemberResult = $lastMemberStmt->get_result();
    $lastNewMember = $lastMemberResult->fetch_assoc()['full_name'] ?? 'No new members';
    $lastMemberStmt->close();
}

// Fetch Last Added New Trainer for the Selected Gym
$lastNewTrainer = 'No new trainers';
if ($selectedGymUsername) {
    $lastTrainerQuery = "SELECT full_name FROM trainers WHERE gym_username = ? ORDER BY username DESC LIMIT 1"; // Using username as a proxy for registration time
    $lastTrainerStmt = $conn->prepare($lastTrainerQuery);
    $lastTrainerStmt->bind_param("s", $selectedGymUsername);
    $lastTrainerStmt->execute();
    $lastTrainerResult = $lastTrainerStmt->get_result();
    $lastNewTrainer = $lastTrainerResult->fetch_assoc()['full_name'] ?? 'No new trainers';
    $lastTrainerStmt->close();
}



$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Gym Owner Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/gym-owner.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="owner-dashboard">
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Owner Dashboard</h3>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <h4>Navigation</h4>
                <a href="dashboard.php" class="active">
                    <i class="fas fa-home"></i>
                    Home
                </a>
            </div>
            <div class="nav-section">
                <h4>Trainer Management</h4>
                <a href="list.php">
                    <i class="fas fa-users"></i>
                    View Trainers
                </a>
            </div>

            <div class="nav-section">
                <h4>Gym Management</h4>
                <a href="manage.php">
                    <i class="fas fa-cog"></i>
                    Account Settings
                </a>
                <a href="membership-plans.php">
                    <i class="fas fa-clipboard-list"></i>
                    Membership Plans
                </a>
            </div>

            <div class="nav-section">
                <h4>Gym Goer Management</h4>
                <a href="customers-list.php">
                    <i class="fas fa-user-friends"></i>
                    Gym Goer List
                </a>
            </div>
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
            <h2>Dashboard Overview</h2>
            <div class="header-actions">
                <div class="profile-menu">
                    <span><?php echo $_SESSION['username']; ?></span>
                </div>
            </div>
            <?php if (count($ownedGyms) > 1): ?>
                <div class="gym-selection-header">
                    <form method="post">
                        <label for="gym_username">Select Gym:</label>
                        <select name="gym_username" id="gym_username" onchange="this.form.submit()">
                            <?php foreach ($ownedGyms as $username => $name): ?>
                                <option value="<?php echo htmlspecialchars($username); ?>" <?php if ($username === $selectedGymUsername) echo 'selected'; ?>><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="select_gym" value="1">
                    </form>
                </div>
            <?php elseif (!empty($ownedGyms)): ?>
                <div class="gym-selection-header">
                    Selected Gym: <?php echo htmlspecialchars($ownedGyms[$selectedGymUsername] ?? 'No Gym Assigned'); ?>
                </div>
            <?php else: ?>
                <div class="gym-selection-header">
                    No Gyms Assigned
                </div>
            <?php endif; ?>
        </header>

        <div class="stats-grid">
            <div class="stat-card trainer">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Trainers</h3>
                    <p class="stat-value"><?php echo $totalTrainers; ?></p>
                </div>
            </div>

            <div class="stat-card customer">
                <div class="stat-icon">
                    <i class="fas fa-user-friends"></i>
                </div>
                <div class="stat-info">
                    <h3>Active Members</h3>
                    <p class="stat-value"><?php echo $activeMembers; ?></p>
                </div>
            </div>
        </div>

        <div class="dashboard-section">
            <div class="section-header">
                <h3>Recent Activities</h3>
                
            </div>
            <div class="activity-list">
                <div class="activity-item new-member">
                    <div class="activity-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="activity-details">
                        <h4>New Member Registration</h4>
                        <p><?php echo $lastNewMember; ?></p>
                        <span class="activity-time">Recently</span>
                    </div>
                </div>

                <div class="activity-item new-trainer">
                    <div class="activity-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="activity-details">
                        <h4>New Trainer Added</h4>
                        <p><?php echo $lastNewTrainer; ?></p>
                        <span class="activity-time">Recently</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/gym-owner.js"></script>
</body>
</html>
