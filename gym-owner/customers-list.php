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
$errorMessage = "";
$successMessage = "";

// 1. Fetch all gyms owned by this owner
$ownedGyms = [];
$gymsQuery = "SELECT gym_username, gym_name FROM gyms WHERE owner_username = ?";
$gymsStmt = $conn->prepare($gymsQuery);
$gymsStmt->bind_param("s", $gymOwnerUsername);
$gymsStmt->execute();
$gymsResult = $gymsStmt->get_result();
while ($row = $gymsResult->fetch_assoc()) {
    $ownedGyms[$row['gym_username']] = $row['gym_name'];
}
$gymsStmt->close();

// 2. Determine selected gym
if (isset($_POST['select_gym']) && isset($_POST['gym_username'])) {
    $selectedGymUsername = $_POST['gym_username'];
    $_SESSION['selected_gym_username'] = $selectedGymUsername;
} else {
    $selectedGymUsername = $_SESSION['selected_gym_username'] ?? "";
    if (empty($selectedGymUsername) && count($ownedGyms) === 1) {
        $selectedGymUsername = array_key_first($ownedGyms);
        $_SESSION['selected_gym_username'] = $selectedGymUsername;
    }
}

// 3. Optional membership plan filter
$whereClause = "WHERE gg.gym_username = ?";
$params = [$selectedGymUsername];
$paramTypes = "s";
if (isset($_GET['plan']) && !empty($_GET['plan']) && $_GET['plan'] != 'all') {
    $whereClause .= " AND gg.membership_plan = ?";
    $params[] = $_GET['plan'];
    $paramTypes .= "s";
}

// 4. Fetch gym goers with preferred trainer info
//    Also LEFT JOIN membership_plans to get plan_name for membership_plan
$customersQuery = "
    SELECT 
      gg.username, 
      gg.full_name, 
      gg.phone, 
      gg.membership_plan, 
      mp.plan_name AS membership_plan_name,
      gg.profile_picture,
      gg.preferred_trainer,
      t.full_name AS trainer_name
    FROM gym_goers gg
    LEFT JOIN membership_plans mp ON gg.membership_plan = mp.plan_id
    LEFT JOIN trainers t ON gg.preferred_trainer = t.username
    $whereClause
";

$customersStmt = $conn->prepare($customersQuery);

// Bind parameters based on how many we have
if (count($params) === 2) {
    $customersStmt->bind_param($paramTypes, $params[0], $params[1]);
} else {
    $customersStmt->bind_param($paramTypes, $params[0]);
}

$customersStmt->execute();
$customersRes = $customersStmt->get_result();
$customers = $customersRes->fetch_all(MYSQLI_ASSOC);
$customersStmt->close();

// For each customer, fetch visit data from gym_visits table
foreach ($customers as &$cust) {
    $datesQuery = "SELECT visit_date FROM gym_visits WHERE username = ? AND gym_username = ? ORDER BY visit_date ASC";
    $datesStmt = $conn->prepare($datesQuery);
    $datesStmt->bind_param("ss", $cust['username'], $selectedGymUsername);
    $datesStmt->execute();
    $datesRes = $datesStmt->get_result();

    $visitMap = []; // key: 'YYYY-MM-DD' => array of times in 'H:i'
    while ($r = $datesRes->fetch_assoc()) {
        $dt = $r['visit_date'];
        $datePart = date("Y-m-d", strtotime($dt));
        $timePart = date("H:i", strtotime($dt));
        $visitMap[$datePart][] = $timePart;
    }
    $datesStmt->close();
    $cust['visit_map'] = $visitMap;
}
unset($cust);

// 5. Handle Customer Deletion (set gym_username = NULL)
if (isset($_GET['delete_customer'])) {
    $customerToDelete = $_GET['delete_customer'];
    $removeQuery = "UPDATE gym_goers SET gym_username = NULL WHERE username = ? AND gym_username = ?";
    $removeStmt = $conn->prepare($removeQuery);
    $removeStmt->bind_param("ss", $customerToDelete, $selectedGymUsername);
    if ($removeStmt->execute()) {
        $successMessage = "Gym-goer removed successfully.";
        header("Location: customers-list.php?success=" . urlencode($successMessage));
        exit();
    } else {
        $errorMessage = "Error removing gym-goer: " . $removeStmt->error;
        header("Location: customers-list.php?error=" . urlencode($errorMessage));
        exit();
    }
    $removeStmt->close();
}

// 6. Fetch membership plans for the filter (still uses plan_id)
$membershipPlansQuery = "SELECT DISTINCT membership_plan FROM gym_goers WHERE gym_username = ? ORDER BY membership_plan ASC";
$planStmt = $conn->prepare($membershipPlansQuery);
$planStmt->bind_param("s", $selectedGymUsername);
$planStmt->execute();
$planRes = $planStmt->get_result();
$membershipPlans = $planRes->fetch_all(MYSQLI_ASSOC);
$planStmt->close();

$successMessage = $_GET['success'] ?? $successMessage;
$errorMessage   = $_GET['error'] ?? $errorMessage;

$conn->close();

// Helper function for mini-calendar (unchanged)
function generateMiniCalendar(array $visitMap) {
    $year = date('Y');
    $month = date('n');
    $firstDayTs = mktime(0,0,0, $month, 1, $year);
    $daysInMonth = date('t', $firstDayTs);
    $startDayOfWeek = date('w', $firstDayTs);

    $html = '<div class="mini-calendar">';
    $html .= '<h5 style="text-align:center;">' . date('F Y', $firstDayTs) . '</h5>';
    $html .= '<table class="calendar-table">';
    $html .= '<thead><tr><th>Su</th><th>Mo</th><th>Tu</th><th>We</th><th>Th</th><th>Fr</th><th>Sa</th></tr></thead>';
    $html .= '<tbody><tr>';

    for ($i = 0; $i < $startDayOfWeek; $i++) {
        $html .= '<td></td>';
    }
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $hasVisit = isset($visitMap[$dateStr]);
        $class = $hasVisit ? 'calendar-present' : 'calendar-absent';

        if ($hasVisit) {
            $times = implode(',', $visitMap[$dateStr]);
            $html .= '<td class="'.$class.'" style="cursor:pointer;" data-date="'.$dateStr.'" data-times="'.$times.'" onclick="openDayModal(this)">'.$day.'</td>';
        } else {
            $html .= '<td class="'.$class.'">'.$day.'</td>';
        }
        if ((($startDayOfWeek + $day) % 7) == 0 && $day < $daysInMonth) {
            $html .= '</tr><tr>';
        }
    }
    $totalCells = $startDayOfWeek + $daysInMonth;
    $leftover = (7 - ($totalCells % 7)) % 7;
    for ($i = 0; $i < $leftover; $i++) {
        $html .= '<td></td>';
    }
    $html .= '</tr></tbody></table>';
    $html .= '</div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FitFlex - Customer Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/gym-owner.css">
    <link rel="stylesheet" href="../assets/css/customer-management.css">
    <!-- Font Awesome & Bootstrap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .customer-card {
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 10px;
            margin: 10px;
            display: inline-block;
            vertical-align: top;
            background: #272727;
            color: #fff;
            position: relative;
        }
        .customer-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }
        .customer-actions button {
            padding: 6px 10px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            margin: 5px 4px 0 0;
            cursor: pointer;
        }
        .view-btn { 
            background-color: #007bff; 
            color: #fff; 
        }
        .delete-btn { 
            background-color: #dc3545; 
            color: #fff; 
        }
        .visits-btn { 
            background-color: #343a40; 
            color: #fff; 
            border: 1px solid #fff; 
        }
        .visits-btn:hover { 
            background-color: #495057; 
        }
        .gym-selection-header { 
            margin-top: 10px; 
            margin-bottom: 10px; 
        }
        .alert { 
            padding: 10px; 
            margin-bottom: 15px; 
            border-radius: 4px; 
        }
        /* Mini calendar styling */
        .mini-calendar { 
            margin-top: 5px; 
        }
        .calendar-table { 
            border-collapse: collapse; 
            width: 100%; 
        }
        .calendar-table th, .calendar-table td { 
            border: 1px solid #ddd; 
            padding: 4px; 
            text-align: center; 
            font-size: 12px; 
        }
        .calendar-present { 
            background-color: #28a745; 
            color: #fff; 
        }
        .calendar-absent  { 
            background-color: #dc3545; 
            color: #fff; 
        }
        .calendar-scroll { 
            display: flex; 
            overflow-x: auto; 
            gap: 20px; 
            margin-top: 10px; 
        }
        .calendar-month { 
            min-width: 240px; 
            background: #fff; 
            color: #000; 
            padding: 5px; 
            border-radius: 6px; 
        }
        /* Modal styling */
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 9999; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); 
        }
        .modal-content { 
            background-color: #272727; 
            margin: 5% auto; 
            padding: 20px; 
            border-radius: 6px; 
            width: 80%; 
            max-width: 600px; 
            position: relative; 
        }
        .close-btn { 
            color: #aaa; 
            float: right; 
            font-size: 28px; 
            font-weight: bold; 
            cursor: pointer; 
        }
        .close-btn:hover { 
            color: red; 
        }
        .dayModal-content table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }
        .dayModal-content th, .dayModal-content td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
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
                <a href="customers-list.php" class="active">
                    <i class="fas fa-user-friends"></i>
                    Gym Goer List
                </a>
            </div>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <header class="dashboard-header">
            <div class="header-wrapper">
                <div class="header-content">
                    <h1>Customer List</h1>
                    <p class="subtitle">View gym-goers associated with your gym</p>
                </div>
                <?php if (count($ownedGyms) > 1): ?>
                    <div class="gym-selection-header">
                        <form method="post">
                            <label for="gym_username">Select Gym:</label>
                            <select name="gym_username" id="gym_username" onchange="this.form.submit()">
                                <?php foreach ($ownedGyms as $username => $name): ?>
                                    <option value="<?php echo htmlspecialchars($username); ?>" <?php if ($username === $selectedGymUsername) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
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
            </div>
        </header>

        <!-- Alerts -->
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success" id="alertBox"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger" id="alertBox"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <!-- Membership Plan Filter -->
        <div class="customer-filters">
            <div class="filter-group">
                <label>Membership Plan</label>
                <select id="planFilter" onchange="window.location.href='customers-list.php?plan=' + this.value">
                    <option value="all">All Plans</option>
                    <?php foreach ($membershipPlans as $plan): ?>
                        <option value="<?php echo htmlspecialchars($plan['membership_plan']); ?>" <?php if (isset($_GET['plan']) && $_GET['plan'] == $plan['membership_plan']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars(ucfirst($plan['membership_plan'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Customer Cards -->
        <div class="customer-list">
            <?php if (empty($customers)): ?>
                <p>No gym-goers found for this gym.</p>
            <?php else: ?>
                <?php foreach ($customers as $cust): 
                    // Count how many distinct visit dates
                    $visitCount = 0;
                    if (is_array($cust['visit_map'])) {
                        $visitCount = count($cust['visit_map']);
                    }
                ?>
                    <div class="customer-card">
                        <div class="customer-header">
                            <!-- Adjust the path for profile pictures as needed -->
                            <img src="../assets/images/<?php echo htmlspecialchars($cust['profile_picture'] ?? 'customer-placeholder.png'); ?>" alt="Customer">
                        </div>
                        <div class="customer-info">
                            <h3><?php echo htmlspecialchars($cust['full_name']); ?></h3>
                            <!-- 
                                Instead of displaying the plan_id, we now display membership_plan_name (retrieved by the LEFT JOIN).
                                If membership_plan_name is NULL, fallback to 'Unknown Plan'.
                            -->
                            <p>
                                <?php 
                                    echo htmlspecialchars(
                                        $cust['membership_plan_name'] 
                                            ? ucfirst($cust['membership_plan_name']) 
                                            : 'Unknown Plan'
                                    );
                                ?> Member
                            </p>
                            <div class="customer-stats">
                                <div class="stat">
                                    <i class="fas fa-id-card"></i>
                                    <span><?php echo htmlspecialchars($cust['username']); ?></span>
                                </div>
                                <!-- Show number of distinct visit days -->
                                <div class="stat">
                                    <button class="visits-btn" onclick="openVisitsModal('<?php echo $cust['username']; ?>')">
                                        <i class="fas fa-calendar-check"></i> 
                                        <?php echo $visitCount; ?> Days
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="customer-actions">
                            <button class="view-btn" onclick="openViewModal('<?php echo htmlspecialchars(json_encode($cust)); ?>')">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="delete-btn" onclick="if(confirm('Remove <?php echo htmlspecialchars($cust['full_name']); ?> from this gym?')) { window.location.href='customers-list.php?delete_customer=<?php echo urlencode($cust['username']); ?>'; }">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                        <!-- Hidden input to store visit_map as JSON -->
                        <input type="hidden" class="visit-map" value="<?php echo htmlspecialchars(json_encode($cust['visit_map'])); ?>">
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal for "View" goer details -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('viewModal')">&times;</span>
        <h3>Goer Details</h3>
        <div id="viewModalBody"></div>
    </div>
</div>

<!-- Modal for "Visits" (show date and time details) -->
<div id="visitsModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('visitsModal')">&times;</span>
        <h3>Visit Details for <span id="visitsModalUser"></span></h3>
        <div id="visitsModalContent"></div>
    </div>
</div>

<script>
// Auto-hide alerts after 3 seconds and remove query parameters
document.addEventListener('DOMContentLoaded', function() {
    const alertBox = document.getElementById('alertBox');
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.display = 'none';
            window.history.replaceState({}, document.title, window.location.pathname);
        }, 3000);
    }
});

// Open "View" modal with goer details
function openViewModal(customerJSON) {
    const customer = JSON.parse(customerJSON);
    const modal = document.getElementById('viewModal');
    const modalBody = document.getElementById('viewModalBody');
    const trainerName = customer.trainer_name ? customer.trainer_name : 'No assigned trainer';

    // Use membership_plan_name if you want to display plan name in the modal
    const membershipPlanName = customer.membership_plan_name 
        ? customer.membership_plan_name 
        : 'Unknown Plan';

    let html = `
        <p><strong>Full Name:</strong> ${customer.full_name}</p>
        <p><strong>Username:</strong> ${customer.username}</p>
        <p><strong>Phone:</strong> ${customer.phone}</p>
        <p><strong>Membership Plan:</strong> ${membershipPlanName}</p>
        <p><strong>Assigned Trainer:</strong> ${trainerName}</p>
    `;
    modalBody.innerHTML = html;
    modal.style.display = 'block';
}

// Open "Visits" modal
function openVisitsModal(username) {
    let visitMap = {};
    let customerCards = document.querySelectorAll('.customer-card');
    customerCards.forEach(card => {
        let idSpan = card.querySelector('.customer-stats .stat span');
        if (idSpan && idSpan.innerText.trim() === username) {
            let hiddenInput = card.querySelector('input.visit-map');
            if (hiddenInput) {
                visitMap = JSON.parse(hiddenInput.value);
            }
        }
    });

    const modal = document.getElementById('visitsModal');
    const modalContent = document.getElementById('visitsModalContent');
    const modalUser = document.getElementById('visitsModalUser');
    modalUser.innerText = username;

    let html = '<table class="dayModal-content"><thead><tr><th>Date</th><th>Times</th></tr></thead><tbody>';
    let hasRecords = false;
    for (let date in visitMap) {
        hasRecords = true;
        let times = visitMap[date].join(", ");
        html += `<tr><td>${date}</td><td>${times}</td></tr>`;
    }
    html += '</tbody></table>';
    if (!hasRecords) {
        html = "<p>No visit records found.</p>";
    }
    modalContent.innerHTML = html;
    modal.style.display = 'block';
}

// Close modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}
</script>
</body>
</html>
