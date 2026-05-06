<?php
session_start();

// Database connection settings
$servername    = "localhost";
$db_username   = "root";
$db_password   = "";
$dbname        = "gym";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

$username = $_SESSION['username'];

// Process profile update if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Retrieve and trim form inputs (using null coalescing operator to avoid passing null)
    $full_name    = trim($_POST['full_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $dob          = trim($_POST['dob'] ?? '');
    $gender       = trim($_POST['gender'] ?? '');
    $fitness_goal = trim($_POST['fitness_goal'] ?? '');

    // Check if any required field is empty
    if($full_name === "" || $email === "" || $phone === "" || $dob === "" || $gender === "" || $fitness_goal === "") {
        // You may want to add error handling here if needed.
    } else {
        // Update database values including fitness_goal
        $sqlUpdate = "UPDATE gym_goers SET full_name = ?, email = ?, phone = ?, dob = ?, gender = ?, fitness_goal = ? WHERE username = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("sssssss", $full_name, $email, $phone, $dob, $gender, $fitness_goal, $username);
        $stmtUpdate->execute();
        $stmtUpdate->close();
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
    }
}

// Fetch user profile data with joins (using IFNULL for fitness_goal to ensure it's always defined)
$sql = "SELECT 
            g.full_name, 
            g.email, 
            g.phone, 
            g.dob, 
            g.gender, 
            IFNULL(g.fitness_goal, '') AS fitness_goal, 
            m.plan_name AS membership_plan,
            t.full_name AS trainer_name,
            gym.gym_name,
            mp.plan_name AS meal_plan,
            wp.plan_name AS workout_plan,
            g.profile_picture
        FROM gym_goers g
        LEFT JOIN membership_plans m ON g.membership_plan = m.plan_id
        LEFT JOIN trainers t ON g.preferred_trainer = t.username
        LEFT JOIN gyms gym ON g.gym_username = gym.gym_username
        LEFT JOIN meal_plans mp ON g.meal_plan = mp.plan_id
        LEFT JOIN workout_plans wp ON g.workout_plan = wp.plan_id
        WHERE g.username = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Member Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/gym-goer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-card {
            padding: 1rem;
            background: black;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            position: relative;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }
        .edit-btn {
            position: absolute;
            right: 10px;
            top: 10px;
            background: #4CAF50;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
        }
        .profile-content {
            display: flex;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-left: 170px;
        }
        .profile-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        .detail-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .detail-item i {
            font-size: 1.2rem;
            margin-right: 1rem;
            color: #4CAF50;
            width: 30px;
            text-align: center;
        }
        .detail-content span {
            display: block;
        }
        .detail-label {
            font-size: 0.9rem;
            color: #666;
        }
        .detail-value {
            font-weight: 500;
            color: #333;
        }
        /* Edit Form Styles */
        #editForm {
            display: none;
            margin-left: 170px;
        }
        #editForm input, #editForm select {
            width: 100%;
            padding: 0.5rem;
            margin: 0.3rem 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        #editForm .form-group {
            margin-bottom: 1rem;
        }
        #editForm button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        #editForm .save-btn {
            background: #4CAF50;
            color: #fff;
            margin-right: 1rem;
        }
        #editForm .cancel-btn {
            background: #ccc;
            color: #333;
        }
        /* Success Alert */
        .alert-success {
            background-color: #4CAF50;
            color: #fff;
            padding: 1rem;
            text-align: center;
            border-radius: 5px;
            margin: 1rem 170px;
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
                <a href="dashboard.php" class="active">
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
                <div class="sidebar-footer">
                  <a href="../index.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                  </a>
                </div>
            </nav>
        </div>

        <div class="main-content">
            <header class="dashboard-header">
                <h2>
                    Welcome Back,
                    <?php
                        $sqlUserName = "SELECT full_name FROM gym_goers WHERE username = ?";
                        $stmtUserName = $conn->prepare($sqlUserName);
                        $stmtUserName->bind_param("s", $username);
                        $stmtUserName->execute();
                        $resultUserName = $stmtUserName->get_result();
                        if ($resultUserName && $resultUserName->num_rows > 0) {
                            $rowUserName = $resultUserName->fetch_assoc();
                            echo htmlspecialchars($rowUserName['full_name'] ?? 'User');
                        } else {
                            echo "User";
                        }
                        $stmtUserName->close();
                    ?>!
                </h2>
                <div class="header-actions">
                </div>
            </header>

            <!-- Success Alert (if profile updated) -->
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert-success" id="successAlert">
                    <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <div class="dashboard-card profile-card">
                    <div class="profile-header">
                        <h2><?= htmlspecialchars($user['full_name'] ?? '') ?></h2>
                        <button class="edit-btn" id="editBtn">Edit Profile</button>
                    </div>
                    
                    <!-- Read-only Profile Display -->
                    <div id="profileDisplay">
                        <div class="profile-content">
                            <div class="profile-details">
                                <div class="detail-item">
                                    <i class="fas fa-envelope"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Email</span>
                                        <span class="detail-value"><?= htmlspecialchars($user['email'] ?? '') ?></span>
                                    </div>
                                </div>

                                <div class="detail-item">
                                    <i class="fas fa-phone"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Phone</span>
                                        <span class="detail-value"><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></span>
                                    </div>
                                </div>

                                <div class="detail-item">
                                    <i class="fas fa-birthday-cake"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Date of Birth</span>
                                        <span class="detail-value"><?= htmlspecialchars($user['dob'] ?? 'N/A') ?></span>
                                    </div>
                                </div>

                                <div class="detail-item">
                                    <i class="fas fa-venus-mars"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Gender</span>
                                        <span class="detail-value"><?= htmlspecialchars($user['gender'] ?? 'N/A') ?></span>
                                    </div>
                                </div>

                                <div class="detail-item">
                                    <i class="fas fa-bullseye"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Fitness Goal</span>
                                        <span class="detail-value"><?= htmlspecialchars($user['fitness_goal'] ?? 'Not set') ?></span>
                                    </div>
                                </div>

                                <div class="detail-item">
                                    <i class="fas fa-id-card"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Membership Plan</span>
                                        <span class="detail-value"><?= htmlspecialchars($user['membership_plan'] ?? 'No active plan') ?></span>
                                    </div>
                                </div>

                                <div class="detail-item">
                                    <i class="fas fa-dumbbell"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Workout Plan</span>
                                        <span class="detail-value"><?= htmlspecialchars($user['workout_plan'] ?? 'No active plan') ?></span>
                                    </div>
                                </div>

                                <div class="detail-item">
                                    <i class="fas fa-utensils"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Meal Plan</span>
                                        <span class="detail-value"><?= htmlspecialchars($user['meal_plan'] ?? 'No active plan') ?></span>
                                    </div>
                                </div>

                                <div class="detail-item">
                                    <i class="fas fa-user-tie"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Preferred Trainer</span>
                                        <span class="detail-value"><?= htmlspecialchars($user['trainer_name'] ?? 'Not selected') ?></span>
                                    </div>
                                </div>

                                <div class="detail-item">
                                    <i class="fa-solid fa-dumbbell"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Home Gym</span>
                                        <span class="detail-value"><?= htmlspecialchars($user['gym_name'] ?? 'Not assigned') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Profile Form -->
                    <div id="editForm">
                        <form method="post" action="profile.php" onsubmit="return validateForm();">
                            <div class="form-group">
                                <label for="full_name" style="color:#fff;">Full Name</label>
                                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email" style="color:#fff;">Email</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone" style="color:#fff;">Phone</label>
                                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="dob" style="color:#fff;">Date of Birth</label>
                                <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($user['dob'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="gender" style="color:#fff;">Gender</label>
                                <select id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?= ((isset($user['gender']) && strtolower($user['gender']) === 'male') ? 'selected' : '') ?>>Male</option>
                                    <option value="female" <?= ((isset($user['gender']) && strtolower($user['gender']) === 'female') ? 'selected' : '') ?>>Female</option>
                                    <option value="other" <?= ((isset($user['gender']) && strtolower($user['gender']) === 'other') ? 'selected' : '') ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="fitness_goal" style="color:#fff;">Fitness Goal</label>
                                <select id="fitness_goal" name="fitness_goal" required>
                                    <option value="">Select Fitness Goal</option>
                                    <option value="Lose Weight" <?= ((isset($user['fitness_goal']) && strtolower($user['fitness_goal']) === 'lose weight') ? 'selected' : '') ?>>Lose Weight</option>
                                    <option value="Build Muscle" <?= ((isset($user['fitness_goal']) && strtolower($user['fitness_goal']) === 'build muscle') ? 'selected' : '') ?>>Build Muscle</option>
                                    <option value="Increase Endurance" <?= ((isset($user['fitness_goal']) && strtolower($user['fitness_goal']) === 'increase endurance') ? 'selected' : '') ?>>Increase Endurance</option>
                                    <option value="General Fitness" <?= ((isset($user['fitness_goal']) && strtolower($user['fitness_goal']) === 'general fitness') ? 'selected' : '') ?>>General Fitness</option>
                                    <option value="Maintain Health" <?= ((isset($user['fitness_goal']) && strtolower($user['fitness_goal']) === 'maintain health') ? 'selected' : '') ?>>Maintain Health</option>
                                </select>
                            </div>
                            <button type="submit" name="update_profile" class="save-btn">Save</button>
                            <button type="button" class="cancel-btn" id="cancelBtn">Cancel</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle edit form visibility
        document.getElementById('editBtn').addEventListener('click', function(){
            document.getElementById('profileDisplay').style.display = 'none';
            document.getElementById('editForm').style.display = 'block';
        });
        document.getElementById('cancelBtn').addEventListener('click', function(){
            document.getElementById('editForm').style.display = 'none';
            document.getElementById('profileDisplay').style.display = 'block';
        });

        // Form validation: ensure no empty fields (HTML5 "required" handles this as well)
        function validateForm() {
            var fullName = document.getElementById('full_name').value.trim();
            var email = document.getElementById('email').value.trim();
            var phone = document.getElementById('phone').value.trim();
            var dob = document.getElementById('dob').value.trim();
            var gender = document.getElementById('gender').value.trim();
            var fitnessGoal = document.getElementById('fitness_goal').value.trim();
            if(fullName === "" || email === "" || phone === "" || dob === "" || gender === "" || fitnessGoal === ""){
                alert("Please fill in all required fields.");
                return false;
            }
            return true;
        }

        // Auto hide success alert after 3 seconds
        window.addEventListener('DOMContentLoaded', (event) => {
            var successAlert = document.getElementById('successAlert');
            if(successAlert) {
                setTimeout(function(){
                    successAlert.style.display = 'none';
                }, 3000);
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
