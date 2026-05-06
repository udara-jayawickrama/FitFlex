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

// Fetch Gyms for Dropdown
$gymsQuery = "SELECT gym_username, gym_name FROM gyms";
$gymsResult = $conn->query($gymsQuery);
$gymOptions =[];
if ($gymsResult && $gymsResult->num_rows > 0) {
    while ($row = $gymsResult->fetch_assoc()) {
        $gymOptions[$row['gym_username']] = $row['gym_name'];
    }
}

// Fetch Trainer Details
$trainerDetailsQuery = "SELECT full_name, email, phone, specialization, experience, certifications, gym_username, profile_picture, fee FROM trainers WHERE username = ?";
$trainerDetailsStmt = $conn->prepare($trainerDetailsQuery);
$trainerDetailsStmt->bind_param("s", $trainerUsername);
$trainerDetailsStmt->execute();
$trainerDetailsResult = $trainerDetailsStmt->get_result();
$trainer = $trainerDetailsResult->fetch_assoc() ??[];
$trainerDetailsStmt->close();

// Process Form Submission (Edit Profile)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['saveProfile'])) {
    $fullName         = trim($_POST['fullName']);
    $email            = trim($_POST['email']);
    $phone            = trim($_POST['phone']);
    $specialization    = trim($_POST['specialization']);
    $experienceYears = trim($_POST['experience']);
    $certifications   = trim($_POST['certifications']);
    $selectedGym      = trim($_POST['gym']);
    // Handle profile picture upload (requires backend processing)
    $profilePicture    = $_FILES['profilePicture']['name'] ?? $trainer['profile_picture']; // Get file name

    $updateTrainerQuery = "UPDATE trainers SET full_name = ?, email = ?, phone = ?, specialization = ?, experience = ?, certifications = ?, gym_username = ?, profile_picture = ? WHERE username = ?";
    $updateTrainerStmt = $conn->prepare($updateTrainerQuery);
    $updateTrainerStmt->bind_param("sssssssss", $fullName, $email, $phone, $specialization, $experienceYears, $certifications, $selectedGym, $profilePicture, $trainerUsername);

    if ($updateTrainerStmt->execute()) {
        $success = "Profile updated successfully!";
        // Refetch trainer details to update the form
        $trainerDetailsStmt = $conn->prepare($trainerDetailsQuery);
        $trainerDetailsStmt->bind_param("s", $trainerUsername);
        $trainerDetailsStmt->execute();
        $trainerDetailsResult = $trainerDetailsStmt->get_result();
        $trainer = $trainerDetailsResult->fetch_assoc() ??[];
        $trainerDetailsStmt->close();
        // You would typically move the uploaded file to a permanent location here
        if (!empty($_FILES['profilePicture']['tmp_name'])) {
            $uploadDir = '../assets/images/trainers/'; // Set your upload directory
            $uploadFile = $uploadDir . basename($_FILES['profilePicture']['name']);
            move_uploaded_file($_FILES['profilePicture']['tmp_name'], $uploadFile);
        }
    } else {
        $error = "Error updating profile: " . $updateTrainerStmt->error;
    }
    $updateTrainerStmt->close();

    header("Location: dashboard.php?success=" . urlencode($success) . "&error=" . urlencode($error));
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex - Trainer Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/trainer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="trainer-dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Trainer Dashboard</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="active">
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
                    <h2>My Account</h2>
                </div>
                <div class="header-profile">
    <span class="trainer-name"><?php echo htmlspecialchars($trainer['full_name'] ?? 'Trainer Name'); ?></span>
    <img src="../assets/images/trainers/<?php echo htmlspecialchars($trainer['profile_picture'] ?? 'profile.jpg'); ?>" alt="Profile" class="profile-pic" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover; margin-left: 10px;">
</div>
            </header>

        <div class="content-section">
            <div class="profile-card">
                <div class="profile-header">
                    <h3>Trainer Profile</h3>
                    <button class="edit-btn" id="editProfileBtn">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                </div>
                <?php if (isset($_GET['success'])): ?>
                    <div class="success-message" style="color: green; margin-bottom: 10px;">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="error-message" style="color: red; margin-bottom: 10px;">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="profile-details" id="profileDetails">
                    <div class="profile-info">
                        <div class="info-group">
                            <label>Full Name</label>
                            <p><?php echo htmlspecialchars($trainer['full_name'] ?? ''); ?></p>
                        </div>
                        <div class="info-group">
                            <label>Email</label>
                            <p><?php echo htmlspecialchars($trainer['email'] ?? ''); ?></p>
                        </div>
                        <div class="info-group">
                            <label>Phone</label>
                            <p><?php echo htmlspecialchars($trainer['phone'] ?? ''); ?></p>
                        </div>
                        <div class="info-group">
                            <label>Specialization</label>
                            <p><?php echo htmlspecialchars($trainer['specialization'] ?? ''); ?></p>
                        </div>
                        <div class="info-group">
                            <label>Experience</label>
                            <p><?php echo htmlspecialchars($trainer['experience'] ?? ''); ?> years</p>
                        </div>
                        <div class="info-group">
                            <label>Certifications</label>
                            <p><?php echo htmlspecialchars($trainer['certifications'] ?? ''); ?></p>
                        </div>
                        <div class="info-group">
                            <label>Gym</label>
                            <p><?php echo htmlspecialchars($trainer['gym_username'] ?? 'Not Assigned'); ?></p>
                        </div>
                        <div class="info-group">
                            <label>Fee</label>
                            <p><?php echo htmlspecialchars($trainer['fee'] ?? 'Not Set'); ?></p>
                        </div>
                    </div>
                </div>

                <form class="edit-profile-form" id="editProfileForm" style="display: none;" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="profilePicture">Profile Picture</label>
                        <input type="file" class="form-control" id="profilePicture" name="profilePicture" accept="image/*">
                        <small class="form-text text-muted">Upload a profile picture for yourself.</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="fullName" value="<?php echo htmlspecialchars($trainer['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($trainer['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($trainer['phone'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Specialization</label>
                            <select name="specialization">
                                <option value="">Select Specialization</option>
                                <option value="Yoga" <?php if ($trainer['specialization'] === 'Yoga') echo 'selected'; ?>>Yoga</option>
                                <option value="Strength Training" <?php if ($trainer['specialization'] === 'Strength Training') echo 'selected'; ?>>Strength Training</option>
                                <option value="Cardio" <?php if ($trainer['specialization'] === 'Cardio') echo 'selected'; ?>>Cardio</option>
                                <option value="CrossFit" <?php if ($trainer['specialization'] === 'CrossFit') echo 'selected'; ?>>CrossFit</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Experience (years)</label>
                            <input type="number" name="experience" value="<?php echo htmlspecialchars($trainer['experience'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Certifications</label>
                            <input type="text" name="certifications" value="<?php echo htmlspecialchars($trainer['certifications'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Gym</label>
                        <?php if (empty($trainer['gym_username'])): ?>
                            <select name="gym" required>
                                <option value="">Select Gym</option>
                                <?php foreach ($gymOptions as $gymUsername => $gymName): ?>
                                    <option value="<?php echo htmlspecialchars($gymUsername); ?>"><?php echo htmlspecialchars($gymName); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" value="<?php echo htmlspecialchars($gymOptions[$trainer['gym_username']] ?? 'Gym Assigned'); ?>" readonly>
                            <input type="hidden" name="gym" value="<?php echo htmlspecialchars($trainer['gym_username']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Fee</label>
                        <input type="text" value="<?php echo htmlspecialchars($trainer['fee'] ?? 'Not Set'); ?>" readonly>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="save-btn" name="saveProfile">Save Changes</button>
                        <button type="button" class="cancel-btn" id="cancelEditBtn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const editProfileBtn  = document.getElementById('editProfileBtn');
    const profileDetails  = document.getElementById('profileDetails');
    const editProfileForm = document.getElementById('editProfileForm');
    const cancelEditBtn   = document.getElementById('cancelEditBtn');
    const successMessage  = document.querySelector('.success-message');
    const errorMessage    = document.querySelector('.error-message');

    editProfileBtn.addEventListener('click', function() {
        profileDetails.style.display   = 'none';
        editProfileForm.style.display = 'block';
    });

    cancelEditBtn.addEventListener('click', function() {
        profileDetails.style.display   = 'block';
        editProfileForm.style.display = 'none';
    });

    if (successMessage) {
        setTimeout(() => {
            successMessage.style.display = 'none';
            window.location.href = window.location.pathname;
        }, 3000);
    }

    if (errorMessage) {
        setTimeout(() => {
            errorMessage.style.display = 'none';
            window.location.href = window.location.pathname;
        }, 3000);
    }
</script>
</body>
</html>