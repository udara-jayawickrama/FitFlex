<?php
session_start();

// Database connection settings
$servername   = "localhost";
$db_username  = "root";
$db_password  = "";
$dbname       = "gym";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if gym goer is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit();
}

$goerUsername = $_SESSION['username'];

// Fetch the gym_username from the gym_goer table for the logged in user
$query = "SELECT gym_username FROM gym_goers WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $goerUsername);
$stmt->execute();
$stmt->bind_result($gymUsername);
$stmt->fetch();
$stmt->close();

// If gymUsername is empty, fallback to a default (or handle the error as needed)
if (empty($gymUsername)) {
    $gymUsername = 'default_gym';
}

$message = "";

// Process visit recording
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['record_visit'])) {
    $today = date("Y-m-d");
    // Check if a visit is already recorded today for this user
    $checkVisitQuery = "SELECT * FROM gym_visits WHERE username = ? AND DATE(visit_date) = ?";
    $checkVisitStmt = $conn->prepare($checkVisitQuery);
    $checkVisitStmt->bind_param("ss", $goerUsername, $today);
    $checkVisitStmt->execute();
    $visitResult = $checkVisitStmt->get_result();

    if ($visitResult->num_rows == 0) {
        // Insert new visit record with current real time (timestamp)
        $insertVisitQuery = "INSERT INTO gym_visits (username, gym_username, visit_date) VALUES (?, ?, NOW())";
        $insertVisitStmt = $conn->prepare($insertVisitQuery);
        $insertVisitStmt->bind_param("ss", $goerUsername, $gymUsername);
        if ($insertVisitStmt->execute()) {
            $message = "Visit recorded successfully for today!";
            // Store in localStorage via inline script so that alert is not repeated on refresh.
            echo '<script>localStorage.setItem("visited_' . $goerUsername . '_' . $today . '", "true");</script>';
        } else {
            $message = "Error recording visit: " . $insertVisitStmt->error;
        }
        $insertVisitStmt->close();
    } else {
        $message = "You have already recorded your visit for today!";
    }
    $checkVisitStmt->close();
}

// Fetch visit history for the current month
$currentMonth = date("Y-m");
$visitHistoryQuery = "SELECT DATE(visit_date) AS visit_day FROM gym_visits WHERE username = ? AND gym_username = ? AND DATE_FORMAT(visit_date, '%Y-%m') = ?";
$visitHistoryStmt = $conn->prepare($visitHistoryQuery);
$visitHistoryStmt->bind_param("sss", $goerUsername, $gymUsername, $currentMonth);
$visitHistoryStmt->execute();
$visitHistoryResult = $visitHistoryStmt->get_result();
$visitedDays = [];
while ($row = $visitHistoryResult->fetch_assoc()) {
    // Build an array of visited day strings (YYYY-MM-DD)
    $visitedDays[] = $row['visit_day'];
}
$visitHistoryStmt->close();

$conn->close();

// Function to generate a scrollable calendar for the current month
function generateCalendar($year, $month, $visitedDays) {
    // Calculate first day of month and total days
    $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
    $daysInMonth = date('t', $firstDayOfMonth);
    $firstDayOfWeek = date('w', $firstDayOfMonth); // 0=Sunday, 6=Saturday
    $currentDay = date('Y-m-d');

    $calendar = '<div style="overflow-x: auto;"><table class="calendar">';
    $calendar .= '<thead><tr><th colspan="7">' . date('F Y', $firstDayOfMonth) . '</th></tr>';
    $calendar .= '<tr><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr></thead>';
    $calendar .= '<tbody><tr>';

    // Blank cells before first day
    for ($i = 0; $i < $firstDayOfWeek; $i++) {
        $calendar .= '<td></td>';
    }

    // Loop through days of month
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $statusClass = in_array($date, $visitedDays) ? 'present' : 'absent';
        $isToday = ($date == $currentDay) ? 'today' : '';
        $calendar .= '<td class="' . $statusClass . ' ' . $isToday . '">' . $day . '</td>';
        // New row after Saturday or last day
        if ((($firstDayOfWeek + $day) % 7) == 0 && $day < $daysInMonth) {
            $calendar .= '</tr><tr>';
        }
    }
    // Fill remaining cells if needed
    $remaining = (7 - (($firstDayOfWeek + $daysInMonth) % 7)) % 7;
    for ($i = 0; $i < $remaining; $i++) {
        $calendar .= '<td></td>';
    }
    $calendar .= '</tr></tbody></table></div>';
    return $calendar;
}

$year = date('Y');
$month = date('n');
$calendar = generateCalendar($year, $month, $visitedDays);

// Count visited days in current month
$visitedCount = count($visitedDays);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Visit - FitFlex</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/gym-goer.css">
    <link rel="stylesheet" href="../assets/css/shop.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap CSS for table styling -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .member-dashboard {
            display: flex;
            min-height: 100vh;
        }
        .main-content {
            flex: 1;
            padding: 20px;
            background: #2e2e2e;
        }
        h2, h3 {
            color: white;
        }
        button {
            background-color: #007bff;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 15px;
        }
        button:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-weight: bold;
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
        /* Calendar styles */
        .calendar {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .calendar th, .calendar td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .calendar th {
            background-color: #4F4F4F;
        }
        .calendar .present {
            background-color: #28a745;
            color: #fff;
        }
        .calendar .absent {
            background-color: #dc3545;
            color: #fff;
        }
        .calendar .today {
            font-weight: bold;
            border: 2px solid #000;
        }
        /* Visited count dashboard style */
        .dashboard-cart {
            background-color: #007bff;
            color: #fff;
            padding: 10px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 20px;
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
                <a href="record-visit.php" class="active">
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
        <h2>Record Your Gym Visit</h2>
    
        <form method="post">
            <button type="submit" name="record_visit" id="recordVisitBtn">Mark as Visited Today</button>
        </form>
        <p id="visitStatus" class="<?php echo (strpos($message, 'Error') !== false) ? 'alert-danger' : 'alert-success'; ?>">
            <?php echo $message; ?>
        </p>
        
        <h3>Attendance Calendar</h3>
        <?php echo $calendar; ?>
        <p>
            <span style="background-color: #28a745; padding: 5px; color: #fff;">Visited</span>
            <span style="background-color: #dc3545; padding: 5px; color: #fff;">Not Visited</span>
        </p>
    </div>

    <script>
        // Disable "Mark as Visited Today" button if already visited (using localStorage)
        
        // Auto-hide the alert (if any) after 3 seconds and remove query parameters
        const alertBox = document.getElementById('visitStatus');
        if (alertBox && alertBox.innerText.trim() !== "") {
            setTimeout(() => {
                alertBox.style.display = 'none';
                window.history.replaceState({}, document.title, window.location.pathname);
            }, 3000);
        }
    </script>
</body>
</html>
