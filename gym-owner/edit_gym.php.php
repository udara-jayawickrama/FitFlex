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

// Get gym_username from GET parameter
if (!isset($_GET['gym_username'])) {
    header("Location: manage.php");
    exit();
}
$gymUsername = $_GET['gym_username'];

// Fetch gym details for editing
$gymQuery = "SELECT gym_username, gym_name, phone_num, email, address, weekdays_hours, weekends_hours, profile_pic, RegistrationFee FROM gyms WHERE gym_username = ? AND owner_username = ?";
$stmt = $conn->prepare($gymQuery);
$stmt->bind_param("ss", $gymUsername, $gymOwnerUsername);
$stmt->execute();
$result = $stmt->get_result();
$gym = $result->fetch_assoc();
$stmt->close();

if (!$gym) {
    die("Gym not found or you do not have permission to edit it.");
}

// Process Form Submission for Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['updateGym'])) {
    // For editing, gym username is not allowed to change.
    $gymUsername = $gym['gym_username'];
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

    // Validate phone and email
    if (!preg_match('/^\d{10}$/', $phone)) {
        $error = "Phone number must be exactly 10 digits.";
    }

    if (empty($gymName) || empty($phone) || empty($email) || empty($address) || empty($registrationFee)) {
        $error = "Please fill out all required fields.";
    } elseif (!is_numeric($registrationFee) || $registrationFee < 0) {
        $error = "Registration Fee must be a non-negative number.";
    }

    // Handle image upload if provided
    $uploadDir = "../uploads/gyms/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $profilePicPath = $_POST['current_profile_pic'] ?? $gym['profile_pic'];
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
        $updateQuery = "
            UPDATE gyms
            SET gym_name = ?,
                phone_num = ?,
                email = ?,
                address = ?,
                weekdays_hours = ?,
                weekends_hours = ?,
                profile_pic = ?,
                RegistrationFee = ?
            WHERE gym_username = ? AND owner_username = ?
        ";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("sssssssdss", $gymName, $phone, $email, $address, $weekdaysHours, $weekendsHours, $profilePicPath, $registrationFee, $gymUsername, $gymOwnerUsername);
        if ($updateStmt->execute()) {
            $success = "Gym details updated successfully!";
            echo '
            <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
            <div class="container mt-5">
              <div class="alert alert-success" role="alert">
                ' . $success . ' Redirecting...
              </div>
            </div>
            <script>
              setTimeout(function(){
                window.location.href = "manage.php";
              }, 3000);
            </script>
            ';
            exit();
        } else {
            $error = "Error updating gym details: " . $updateStmt->error;
        }
        $updateStmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Gym Details - FitFlex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/gym-owner.css">
    <link rel="stylesheet" href="../assets/css/account-management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .gym-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #444444;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .back-btn {
            display: inline-block;
            margin: 20px;
            padding: 8px 16px;
            background: #555;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="owner-dashboard">
    <div class="main-content">
        <a href="manage.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Gym Management</a>
        <h1>Edit Gym Details</h1>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" id="alertBox"><?php echo $error; ?></div>
        <?php endif; ?>
        <form class="gym-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?gym_username=' . urlencode($gymUsername); ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label>Gym Username (Unique)</label>
                <input type="text" name="gym_username" value="<?php echo htmlspecialchars($gym['gym_username']); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Gym Name</label>
                <input type="text" name="gymName" value="<?php echo htmlspecialchars($gym['gym_name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($gym['phone_num']); ?>" required pattern="\d{10}" title="Enter exactly 10 digits">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($gym['email']); ?>" required>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3" required><?php echo htmlspecialchars($gym['address']); ?></textarea>
            </div>
            <div class="form-group">
                <label>Weekdays Hours</label>
                <?php
                $weekdaysHours = explode(' - ', $gym['weekdays_hours'] ?? ' - ');
                $weekdayOpen  = $weekdaysHours[0] ?? '';
                $weekdayClose = $weekdaysHours[1] ?? '';
                ?>
                <input type="time" name="weekdayOpen" value="<?php echo htmlspecialchars($weekdayOpen); ?>" required> -
                <input type="time" name="weekdayClose" value="<?php echo htmlspecialchars($weekdayClose); ?>" required>
            </div>
            <div class="form-group">
                <label>Weekends Hours</label>
                <?php
                $weekendsHours = explode(' - ', $gym['weekends_hours'] ?? ' - ');
                $weekendOpen  = $weekendsHours[0] ?? '';
                $weekendClose = $weekendsHours[1] ?? '';
                ?>
                <input type="time" name="weekendOpen" value="<?php echo htmlspecialchars($weekendOpen); ?>" required> -
                <input type="time" name="weekendClose" value="<?php echo htmlspecialchars($weekendClose); ?>" required>
            </div>
            <div class="form-group">
                <label>Profile Picture</label>
                <?php if (!empty($gym['profile_pic'])): ?>
                    <img src="<?php echo htmlspecialchars($gym['profile_pic']); ?>" alt="Gym Profile" style="max-width:100px; max-height:100px;">
                    <input type="hidden" name="current_profile_pic" value="<?php echo htmlspecialchars($gym['profile_pic']); ?>">
                <?php endif; ?>
                <input type="file" name="profile_pic" accept="image/*">
            </div>
            <div class="form-group">
                <label>Registration Fee</label>
                <input type="number" name="registrationFee" step="0.01" value="<?php echo htmlspecialchars($gym['RegistrationFee'] ?? '0.00'); ?>" required>
            </div>
            <div class="form-group">
                <button type="submit" name="updateGym" class="btn save-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<script>
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