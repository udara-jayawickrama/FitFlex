<?php
session_start();

// Database connection settings
$servername    = "localhost";
$db_username = "root";
$db_password = "";
$dbname      = "gym";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error    = "";
$success = "";
$redirectScript = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and trim inputs
    $userType = isset($_POST['userType']) ? trim($_POST['userType']) : "";
    $username = isset($_POST['username']) ? trim($_POST['username']) : "";
    $password = isset($_POST['password']) ? trim($_POST['password']) : "";

    // Basic validation
    if (empty($userType) || empty($username) || empty($password)) {
        $error = "Please fill in all required fields.";
    } else {
        // Determine target table and dashboard URL based on user type
        $table = "";
        $dashboardURL = "";
        switch ($userType) {
            case "gym-goer":
                $table = "gym_goers";
                $dashboardURL = "gym-goer/dashboard.php"; // Need actual URL
                break;
            case "trainer":
                $table = "trainers";
                $dashboardURL = "trainer/dashboard.php"; // Need actual URL
                break;
            case "gym-owner":
                $table = "gym_owners";
                $dashboardURL = "gym-owner/dashboard.php"; // Need actual URL
                break;
            case "seller":
                $table = "sellers";
                $dashboardURL = "seller/dashboard.php"; // Need actual URL
                break;
            default:
                $error = "Invalid user type selected.";
        }

        if (empty($error)) {
            // Prepare SQL to fetch user record
            $stmt = $conn->prepare("SELECT username, password FROM $table WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($dbUsername, $dbPassword);
                $stmt->fetch();
                // Verify the password against the hashed password stored in DB
                if (password_verify($password, $dbPassword)) {
                    $success = "Login successful! Redirecting...";
                    // Set session variables if needed
                    $_SESSION['username']  = $dbUsername;
                    $_SESSION['user_type'] = $userType;

                    // JavaScript redirect after 2 seconds
                    $redirectScript = "<script>
                        setTimeout(function(){
                            window.location.href = '$dashboardURL';
                        }, 2000);
                    </script>";
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "User not found.";
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex Gym - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .user-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center; /* Added to center the buttons */
        }

        .user-type {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease; /* Added color transition */
            text-align: center;
            color: black; /* Set default text color to black */
        }

        .user-type.active {
            background-color: red; /* Changed active color to red */
            color: white;
            border-color: red;
        }
        /* New Alert styles for more beautiful display */
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-box">
                <h2>Welcome to FitFlex</h2>
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                    <?php echo $redirectScript; ?>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <!-- Updated: Removed default user type selection -->
                    <input type="hidden" name="userType" id="userType" value="">
                    <div class="user-type-selector">
                        <button type="button" class="user-type" data-type="gym-owner">Gym Owner</button>
                        <button type="button" class="user-type" data-type="trainer">Trainer</button>
                        <button type="button" class="user-type" data-type="gym-goer">Gym Goer</button>
                        <button type="button" class="user-type" data-type="seller">Seller</button>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="login-btn">Login</button>
                </form>
                <div class="register-link">
                    <p>Don't have an account? <a href="register.php">Register Now</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const buttons = document.querySelectorAll('.user-type');
        const userTypeInput = document.getElementById('userType');
        // Updated: No default active user type; remove active class from all buttons on load.
        buttons.forEach(btn => btn.classList.remove('active'));
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                buttons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                userTypeInput.value = button.getAttribute('data-type');
            });
        });
        // Prevent the alert from showing again after browser refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
