<?php
session_start();

// Database connection settings
$servername     = "localhost";
$db_username    = "root";
$db_password    = "";
$dbname         = "gym";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if gym owner is logged in
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'gym-owner') {
    header("Location: ../index.php"); // Redirect if not a gym owner
    exit();
}

$gymOwnerUsername = $_SESSION['username'];

/**
 * 1. Fetch all gyms owned by this gym owner.
 */
$ownedGyms = [];
$gymsQuery = "SELECT gym_username, gym_name FROM gyms WHERE owner_username = ?";
$gymsStmt = $conn->prepare($gymsQuery);
$gymsStmt->bind_param("s", $gymOwnerUsername);
$gymsStmt->execute();
$gymsRes = $gymsStmt->get_result();
while ($row = $gymsRes->fetch_assoc()) {
    $ownedGyms[$row['gym_username']] = $row['gym_name'];
}
$gymsStmt->close();

/**
 * 2. Handle gym selection.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_gym'])) {
    $selectedGymUsername = $_POST['gym_username'] ?? '';
    $_SESSION['selected_gym_username'] = $selectedGymUsername;
    header("Location: list.php");
    exit();
}

// 3. Determine which gym is currently selected.
$selectedGymUsername = $_SESSION['selected_gym_username'] ?? '';
if (empty($selectedGymUsername) && count($ownedGyms) === 1) {
    $selectedGymUsername = array_key_first($ownedGyms);
    $_SESSION['selected_gym_username'] = $selectedGymUsername;
}

$trainers = [];
$specializations = [];

// We'll also prepare variables for success/error messages.
$removeSuccessMessage = '';
$removeErrorMessage   = '';
$feeSuccessMessage    = '';
$feeErrorMessage      = '';

// 4. If a gym is selected, fetch trainers & specializations.
if (!empty($selectedGymUsername)) {
    // Fetch Trainers for the Selected Gym.
    $trainersQuery = "
        SELECT username, full_name, specialization, certifications, experience, fee, profile_picture
        FROM trainers
        WHERE gym_username = ?
    ";
    $trainersStmt = $conn->prepare($trainersQuery);
    $trainersStmt->bind_param("s", $selectedGymUsername);
    $trainersStmt->execute();
    $trainersResult = $trainersStmt->get_result();
    $trainers = $trainersResult->fetch_all(MYSQLI_ASSOC);
    $trainersStmt->close();

    // Fetch Unique Specializations for Filter.
    $specializationsQuery = "
        SELECT DISTINCT specialization
        FROM trainers
        WHERE gym_username = ?
        ORDER BY specialization ASC
    ";
    $specializationsStmt = $conn->prepare($specializationsQuery);
    $specializationsStmt->bind_param("s", $selectedGymUsername);
    $specializationsStmt->execute();
    $specializationsResult = $specializationsStmt->get_result();
    $specializations = $specializationsResult->fetch_all(MYSQLI_ASSOC);
    $specializationsStmt->close();

    // Handle Trainer Removal.
    // Instead of deleting the trainer from the system,
    // update the record by setting gym_username to NULL.
    if (isset($_GET['delete_trainer'])) {
        $trainerToRemove = $_GET['delete_trainer'];
        $removeQuery = "UPDATE trainers SET gym_username = NULL WHERE username = ? AND gym_username = ?";
        $removeStmt = $conn->prepare($removeQuery);
        $removeStmt->bind_param("ss", $trainerToRemove, $selectedGymUsername);
        if ($removeStmt->execute()) {
            $removeSuccessMessage = "Trainer removed from gym successfully.";
            header("Location: list.php?message=" . urlencode($removeSuccessMessage));
            exit();
        } else {
            $removeErrorMessage = "Error removing trainer: " . $removeStmt->error;
        }
        $removeStmt->close();
    }

    // Handle Trainer Fee Update.
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_fee'])) {
        $trainerUsername = $_POST['trainer_username'];
        $fee = $_POST['fee'];
        $updateFeeQuery = "UPDATE trainers SET fee = ? WHERE username = ? AND gym_username = ?";
        $updateFeeStmt = $conn->prepare($updateFeeQuery);
        $updateFeeStmt->bind_param("dss", $fee, $trainerUsername, $selectedGymUsername);
        if ($updateFeeStmt->execute()) {
            $feeSuccessMessage = "Trainer fee updated successfully.";
            header("Location: list.php?message=" . urlencode($feeSuccessMessage));
            exit();
        } else {
            $feeErrorMessage = "Error updating trainer fee: " . $updateFeeStmt->error;
        }
        $updateFeeStmt->close();
    }

    // Re-fetch trainers if any update was made.
    if (!empty($removeSuccessMessage) || !empty($removeErrorMessage) ||
        !empty($feeSuccessMessage)    || !empty($feeErrorMessage)) {

        $trainersStmt = $conn->prepare($trainersQuery);
        $trainersStmt->bind_param("s", $selectedGymUsername);
        $trainersStmt->execute();
        $trainersResult = $trainersStmt->get_result();
        $trainers = $trainersResult->fetch_all(MYSQLI_ASSOC);
        $trainersStmt->close();
    }

    // Fetch Client Count for each trainer.
    foreach ($trainers as &$trainer) {
        $clientCountQuery = "
            SELECT COUNT(*) AS client_count
            FROM gym_goers
            WHERE preferred_trainer = ? AND gym_username = ?
        ";
        $clientCountStmt = $conn->prepare($clientCountQuery);
        $clientCountStmt->bind_param("ss", $trainer['username'], $selectedGymUsername);
        $clientCountStmt->execute();
        $clientCountResult = $clientCountStmt->get_result();
        $trainer['client_count'] = $clientCountResult->fetch_assoc()['client_count'] ?? 0;
        $clientCountStmt->close();
    }
    unset($trainer);
}

// Check for success message passed via query string.
if (isset($_GET['message'])) {
    $successMessage = $_GET['message'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FitFlex - Trainer Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/gym-owner.css">
    <link rel="stylesheet" href="../assets/css/trainer-management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Smaller trainer card box */
        .trainer-card {
            width: 220px;
            margin: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            overflow: hidden;
            display: inline-block;
            vertical-align: top;
        }
        /* Same size for Delete and Save Fee buttons */
        .delete-btn, .save-btn {
            padding: 8px 12px;
            font-size: 4px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .delete-btn {
            background-color: #dc3545;
            color: #fff;
        }
        .save-btn {
            background-color: #007bff;
            color: #fff;
        }
        .trainer-filters label {
            color: white;
        }
        .trainer-filters select {
            color: black;
        }
        .trainer-card .fee-input {
            width: 60px;
            padding: 5px;
            margin-top: 10px;
        }
        .gym-selection-header {
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .gym-selection-header form {
            display: inline-block;
            margin-left: 20px;
        }
        .gym-selection-header label {
            font-weight: bold;
            margin-right: 5px;
        }
        /* Alert messages */
        .success-message, .error-message {
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
            font-weight: bold;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
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
                <a href="list.php" class="active">
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
                <h4>Customer Management</h4>
                <a href="customers-list.php">
                    <i class="fas fa-user-friends"></i>
                    Customer List
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
            <div class="header-wrapper">
                <div class="header-content">
                    <h1>Trainer Management</h1>
                    <p class="subtitle">View and manage your gym's trainers</p>
                </div>
            </div>
            <!-- Gym Selection -->
            <?php if (count($ownedGyms) > 1): ?>
                <div class="gym-selection-header">
                    <form method="post">
                        <label for="gym_username">Gym:</label>
                        <select name="gym_username" id="gym_username" onchange="this.form.submit()">
                            <?php foreach ($ownedGyms as $gymUsername => $gymName): ?>
                                <option value="<?php echo htmlspecialchars($gymUsername); ?>"
                                    <?php if ($gymUsername === $selectedGymUsername) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($gymName); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="select_gym" value="1">
                    </form>
                </div>
            <?php elseif (count($ownedGyms) === 1): ?>
                <div class="gym-selection-header">
                    Selected Gym:
                    <?php
                        $onlyKey = array_key_first($ownedGyms);
                        echo htmlspecialchars($ownedGyms[$onlyKey]);
                    ?>
                </div>
            <?php else: ?>
                <div class="gym-selection-header">
                    No Gyms Assigned
                </div>
            <?php endif; ?>
        </header>

        <!-- Alert messages -->
        <?php if (!empty($removeSuccessMessage)): ?>
            <div class="success-message" id="alertBox"><?php echo $removeSuccessMessage; ?></div>
        <?php elseif (!empty($removeErrorMessage)): ?>
            <div class="error-message" id="alertBox"><?php echo $removeErrorMessage; ?></div>
        <?php elseif (!empty($feeSuccessMessage)): ?>
            <div class="success-message" id="alertBox"><?php echo $feeSuccessMessage; ?></div>
        <?php elseif (!empty($feeErrorMessage)): ?>
            <div class="error-message" id="alertBox"><?php echo $feeErrorMessage; ?></div>
        <?php elseif (isset($successMessage)): ?>
            <div class="success-message" id="alertBox"><?php echo $successMessage; ?></div>
        <?php endif; ?>

        <!-- Display trainer filters and list if a gym is selected -->
        <?php if (!empty($selectedGymUsername)): ?>
            <div class="trainer-filters">
                <div class="filter-group">
                    <label>Specialization</label>
                    <select id="specializationFilter">
                        <option value="">All</option>
                        <?php foreach ($specializations as $spec): ?>
                            <option value="<?php echo htmlspecialchars($spec['specialization']); ?>">
                                <?php echo htmlspecialchars($spec['specialization']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="trainer-list">
                <?php if (empty($trainers)): ?>
                    <p>No trainers found for this gym.</p>
                <?php else: ?>
                    <?php foreach ($trainers as $trainer): ?>
                        <div class="trainer-card">
                            <div class="trainer-header">
                                <img src="../assets/images/trainers/<?php echo htmlspecialchars($trainer['profile_picture'] ?? 'trainer-placeholder.png'); ?>"
                                     alt="Trainer" class="trainer-image">
                                <div class="trainer-status active">Active</div>
                            </div>
                            <div class="trainer-info">
                                <h3><?php echo htmlspecialchars($trainer['full_name']); ?></h3>
                                <p class="specialization"><?php echo htmlspecialchars($trainer['specialization']); ?></p>
                                <div class="trainer-stats">
                                    <div class="stat">
                                        <i class="fas fa-user-friends"></i>
                                        <span><?php echo $trainer['client_count']; ?> Clients</span>
                                    </div>
                                    <div class="stat">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo htmlspecialchars($trainer['experience']); ?> Years</span>
                                    </div>
                                </div>
                                <div class="trainer-tags">
                                    <?php
                                    $certifications = explode(',', $trainer['certifications']);
                                    foreach ($certifications as $certification): ?>
                                        <span class="tag"><?php echo htmlspecialchars(trim($certification)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="trainer-actions">
                                <button class="delete-btn" onclick="confirmDelete('<?php echo htmlspecialchars($trainer['username']); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                                <form method="post" class="fee-form" style="display:inline-block;">
                                    <input type="hidden" name="trainer_username" value="<?php echo htmlspecialchars($trainer['username']); ?>">
                                    Fee:
                                    <input type="number" name="fee" value="<?php echo htmlspecialchars($trainer['fee'] ?? ''); ?>" class="fee-input">
                                    <button type="submit" name="update_fee" class="save-btn">Save Fee</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p style="margin: 20px;">Please select a gym or contact support if no gyms are assigned to your account.</p>
        <?php endif; ?>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal('deleteModal')">&times;</span>
                <h2>Delete Trainer</h2>
                <p>Are you sure you want to remove this trainer from your gym?</p>
                <div class="modal-actions">
                    <a id="deleteLink" href="#"><button class="confirm-delete-btn">Delete</button></a>
                    <button onclick="closeModal('deleteModal')">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Filter trainers by specialization
    const specializationFilter = document.getElementById('specializationFilter');
    if (specializationFilter) {
        specializationFilter.addEventListener('change', function() {
            const selectedValue = this.value.toLowerCase();
            const trainerCards = document.querySelectorAll('.trainer-card');
            trainerCards.forEach(card => {
                const specializationElem = card.querySelector('.specialization');
                if (!specializationElem) return;
                const specText = specializationElem.textContent.toLowerCase();
                card.style.display = (selectedValue === '' || specText === selectedValue) ? 'block' : 'none';
            });
        });
    }

    // Delete Confirmation Modal functions
    function confirmDelete(trainerUsername) {
        document.getElementById('deleteLink').href = 'list.php?delete_trainer=' + trainerUsername;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Auto-hide alert message after 3 seconds
    const alertBox = document.getElementById('alertBox');
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.display = 'none';
        }, 3000);
    }

    // Remove query parameters from URL after page load to prevent alert re-display on refresh
    if (window.location.search.indexOf('message=') !== -1) {
        history.replaceState(null, '', window.location.pathname);
    }
</script>
</body>
</html>
