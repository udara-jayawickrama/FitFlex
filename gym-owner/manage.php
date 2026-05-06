<?php
session_start();

// Database connection settings
$servername     = "localhost";
$db_username    = "root";
$db_password    = "";
$dbname         = "gym";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if gym owner is logged in
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'gym-owner') {
    header("Location: ../index.php");
    exit();
}

$gymOwnerUsername = $_SESSION['username'];
$error = "";
$success = "";

// Process Add New Gym Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addGym'])) {
    $gymUsername     = trim($_POST['gym_username']);
    $gymName         = trim($_POST['gymName']);
    $phone           = trim($_POST['phone']);
    $email           = trim($_POST['email']);
    $address         = trim($_POST['address']);
    $weekdayOpen     = trim($_POST['weekdayOpen']);
    $weekdayClose    = trim($_POST['weekdayClose']);
    $weekendOpen     = trim($_POST['weekendOpen']);
    $weekendClose    = trim($_POST['weekendClose']);
    $registrationFee = trim($_POST['registrationFee']);

    $weekdaysHours = $weekdayOpen . " - " . $weekdayClose;
    $weekendsHours = $weekendOpen . " - " . $weekendClose;

    // Validate required fields
    if (empty($gymUsername) || empty($gymName) || empty($phone) || empty($email) || empty($address) || empty($registrationFee)) {
        $error = "Please fill out all required fields including Gym Username and Registration Fee.";
    } elseif (!preg_match('/^[A-Za-z0-9_-]{3,20}$/', $gymUsername)) {
        $error = "Gym Username must be 3-20 characters using letters, numbers, underscores or dashes.";
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $error = "Phone number must be exactly 10 digits.";
    } elseif (!is_numeric($registrationFee) || $registrationFee < 0) {
        $error = "Registration Fee must be a non-negative number.";
    } else {
        // Check if gym username already exists
        $checkGymQuery = "SELECT COUNT(*) FROM gyms WHERE gym_username = ?";
        $checkStmt = $conn->prepare($checkGymQuery);
        $checkStmt->bind_param("s", $gymUsername);
        $checkStmt->execute();
        $checkStmt->bind_result($existingCount);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($existingCount > 0) {
            $error = "Gym username already exists. Please choose a different username.";
        } else {
            // Handle profile picture upload if provided
            $uploadDir = "../uploads/gyms/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $profilePicPath = "";
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                $newFileName = $gymUsername . "_" . time() . "." . $ext;
                $destination = $uploadDir . $newFileName;
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                    $profilePicPath = $destination;
                } else {
                    $error = "Error uploading profile picture.";
                }
            }

            if (empty($error)) {
                $insertGymQuery = "INSERT INTO gyms (gym_username, owner_username, gym_name, phone_num, email, address, weekdays_hours, weekends_hours, profile_pic, RegistrationFee) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertGymQuery);
                $stmt->bind_param("sssssssssd", 
                    $gymUsername, 
                    $gymOwnerUsername, 
                    $gymName, 
                    $phone, 
                    $email, 
                    $address, 
                    $weekdaysHours, 
                    $weekendsHours, 
                    $profilePicPath, 
                    $registrationFee
                );

                if ($stmt->execute()) {
                    $success = "Gym details added successfully!";
                } else {
                    $error = "Error adding gym details: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Fetch all gyms owned by the current gym owner
$gymsQuery = "SELECT gym_username, gym_name, phone_num, email, address, weekdays_hours, weekends_hours, profile_pic, RegistrationFee 
              FROM gyms 
              WHERE owner_username = ?";
$gymsStmt = $conn->prepare($gymsQuery);
$gymsStmt->bind_param("s", $gymOwnerUsername);
$gymsStmt->execute();
$gymsResult = $gymsStmt->get_result();
$gyms = $gymsResult->fetch_all(MYSQLI_ASSOC);
$gymsStmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FitFlex - Gym Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/gym-owner.css">
    <link rel="stylesheet" href="../assets/css/account-management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .gym-card {
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin: 10px;
            padding: 10px;
            display: inline-block;
            vertical-align: top;
            background: #444444;
        }
        .gym-card img {
            max-width: 100px;
            max-height: 100px;
            display: block;
            margin-bottom: 10px;
        }
        .btn, .edit-btn, .delete-btn, .save-btn {
            padding: 8px 12px;
            font-size: 14px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin: 5px 2px;
        }
        .edit-btn, .save-btn {
            background-color: #007bff;
            color: #fff;
        }
        .delete-btn {
            background-color: #dc3545;
            color: #fff;
        }
        .alert {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
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
                <a href="dashboard.php">
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
                <a href="manage.php" class="active">
                    <i class="fas fa-cog"></i>
                    Account Settings
                </a>
                <a href="membership-plans.php">
                    <i class="fas fa-clipboard-list"></i>
                    Membership Plans
                </a>
            </div>

            <div class="nav-section">
                <h4>Customer Management</h4>
                <a href="customers-list.php">
                    <i class="fas fa-user-friends"></i>
                    Customer List
                </a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <header class="dashboard-header">
            <div class="header-wrapper">
                <div class="header-content">
                    <h1>Gym Management</h1>
                    <p class="subtitle">Manage your gym accounts</p>
                </div>
            </div>
        </header>

        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success" id="alertBox"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" id="alertBox"><?php echo $error; ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success" id="alertBox"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="gyms-list">
            <h2>Your Gyms</h2>
            <?php if (empty($gyms)): ?>
                <p>No gyms added yet.</p>
            <?php else: ?>
                <?php foreach ($gyms as $g): ?>
                    <div class="gym-card">
                        <?php if (!empty($g['profile_pic'])): ?>
                            <img src="<?php echo htmlspecialchars($g['profile_pic']); ?>" alt="Gym Profile">
                        <?php else: ?>
                            <img src="../assets/images/gym-placeholder.png" alt="Gym Placeholder">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($g['gym_name']); ?></h3>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($g['gym_username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($g['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($g['phone_num']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($g['address']); ?></p>
                        <p><strong>Weekdays:</strong> <?php echo htmlspecialchars($g['weekdays_hours']); ?></p>
                        <p><strong>Weekends:</strong> <?php echo htmlspecialchars($g['weekends_hours']); ?></p>
                        <p><strong>Registration Fee:</strong> <?php echo htmlspecialchars($g['RegistrationFee']); ?></p>
                        <div class="gym-actions">
                            <a href="edit_gym.php?gym_username=<?php echo urlencode($g['gym_username']); ?>" class="btn edit-btn">Edit</a>
                            <a href="delete_gym.php?gym_username=<?php echo urlencode($g['gym_username']); ?>" class="btn delete-btn" onclick="return confirm('Are you sure you want to delete this gym? It cannot be deleted if trainers or gym goers are associated with it.')">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="add-gym">
            <h2>Add New Gym</h2>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Gym Username (Unique)</label>
                    <input type="text" name="gym_username" required pattern="[A-Za-z0-9_-]{3,20}" title="3-20 characters; letters, numbers, underscore or dash">
                </div>
                <div class="form-group">
                    <label>Gym Name</label>
                    <input type="text" name="gymName" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" required pattern="\d{10}" title="Enter exactly 10 digits">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Weekdays Hours</label>
                    <input type="time" name="weekdayOpen" required> -
                    <input type="time" name="weekdayClose" required>
                </div>
                <div class="form-group">
                    <label>Weekends Hours</label>
                    <input type="time" name="weekendOpen" required> -
                    <input type="time" name="weekendClose" required>
                </div>
                <div class="form-group">
                    <label>Registration Fee</label>
                    <input type="number" name="registrationFee" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="profile_pic" accept="image/*">
                </div>
                <div class="form-actions">
                    <button type="submit" name="addGym" class="btn save-btn">Add Gym</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    // Auto-hide alert after 3 seconds and remove query parameters
    window.addEventListener('DOMContentLoaded', function() {
        const alertBox = document.getElementById('alertBox');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.display = 'none';
                window.history.replaceState({}, document.title, window.location.pathname);
            }, 3000);
        }
    });
</script>
</body>
</html>
