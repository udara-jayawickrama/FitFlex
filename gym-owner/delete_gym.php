<?php
session_start();

// Database connection settings
$servername   = "localhost";
$db_username  = "root";
$db_password  = "";
$dbname       = "gym";

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

// Get gym_username to delete from GET parameter
if (!isset($_GET['gym_username'])) {
    header("Location: manage.php");
    exit();
}
$gymToDelete = $_GET['gym_username'];

// Check for associated trainers
$trainerCheckQuery = "SELECT COUNT(*) AS trainer_count FROM trainers WHERE gym_username = ?";
$stmt1 = $conn->prepare($trainerCheckQuery);
$stmt1->bind_param("s", $gymToDelete);
$stmt1->execute();
$result1 = $stmt1->get_result();
$row1 = $result1->fetch_assoc();
$trainerCount = $row1['trainer_count'] ?? 0;
$stmt1->close();

// Check for associated gym goers
$goerCheckQuery = "SELECT COUNT(*) AS goer_count FROM gym_goers WHERE gym_username = ?";
$stmt2 = $conn->prepare($goerCheckQuery);
$stmt2->bind_param("s", $gymToDelete);
$stmt2->execute();
$result2 = $stmt2->get_result();
$row2 = $result2->fetch_assoc();
$goerCount = $row2['goer_count'] ?? 0;
$stmt2->close();

if ($trainerCount > 0 || $goerCount > 0) {
    $error = "Cannot delete gym because there are trainers or gym goers associated with it.";
} else {
    $deleteQuery = "DELETE FROM gyms WHERE gym_username = ? AND owner_username = ?";
    $stmt3 = $conn->prepare($deleteQuery);
    $stmt3->bind_param("ss", $gymToDelete, $gymOwnerUsername);
    if ($stmt3->execute()) {
        $success = "Gym deleted successfully!";
    } else {
        $error = "Error deleting gym: " . $stmt3->error;
    }
    $stmt3->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Gym - FitFlex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS for alerts -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script>
        // Auto-hide alert after 3 seconds and redirect to manage.php
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                window.location.href = "manage.php";
            }, 3000);
        });
    </script>
</head>
<body>
    <div class="container mt-5">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert" id="alertBox">
                <?php echo $error; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-success" role="alert" id="alertBox">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
