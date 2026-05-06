<?php
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

$error     = "";
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and trim all inputs (common for all users)
    $user_type         = isset($_POST['user_type']) ? trim($_POST['user_type']) : "";
    $full_name         = isset($_POST['full_name']) ? trim($_POST['full_name']) : "";
    $email             = isset($_POST['email']) ? trim($_POST['email']) : "";
    $phone             = isset($_POST['phone']) ? trim($_POST['phone']) : "";
    $dob               = isset($_POST['dob']) ? trim($_POST['dob']) : "";
    $gender            = isset($_POST['gender']) ? trim($_POST['gender']) : "";
    $username          = isset($_POST['username']) ? trim($_POST['username']) : "";
    $password          = isset($_POST['password']) ? trim($_POST['password']) : "";
    $confirm_password  = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : "";

    // Validate required fields
    if (empty($user_type) || empty($full_name) || empty($email) || empty($phone) || empty($dob) || empty($gender) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // New validation for email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        }
        // New validation for phone number format (exactly 10 digits)
        if (!preg_match('/^\d{10}$/', $phone)) {
            $error = "Phone number must be exactly 10 digits.";
        }
        // New validation for password format: at least 7 characters with uppercase, lowercase, digit, and symbol.
        if (strlen($password) < 7 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[\W_]/', $password)) {
            $error = "Password must be at least 7 characters and include uppercase, lowercase, number, and symbol.";
        }

        // Determine the target table based on user type
        $table = "";
        if ($user_type == "gym-goer") {
            $table = "gym_goers";
        } elseif ($user_type == "trainer") {
            $table = "trainers";
        } elseif ($user_type == "gym-owner") {
            $table = "gym_owners";
        } elseif ($user_type == "seller") {
            $table = "sellers";
        } else {
            $error = "Invalid user type.";
        }

        if (empty($error)) {
            // Validate duplicate username in the selected table
            $checkQuery = "SELECT username FROM $table WHERE username = ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error = "Username already exists. Please choose another.";
            }
            $stmt->close();
        }

        if (empty($error)) {
            // Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert data into the respective table (only common fields)
            if ($user_type == "gym-goer") {
                $sql = "INSERT INTO gym_goers (full_name, email, phone, dob, gender, username, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
            } elseif ($user_type == "trainer") {
                $sql = "INSERT INTO trainers (full_name, email, phone, dob, gender, username, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
            } elseif ($user_type == "gym-owner") {
                $sql = "INSERT INTO gym_owners (full_name, email, phone, dob, gender, username, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
            } elseif ($user_type == "seller") {
                $sql = "INSERT INTO sellers (full_name, email, phone, dob, gender, username, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
            }
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $full_name, $email, $phone, $dob, $gender, $username, $hashed_password);

            if ($stmt->execute()) {
                $success = "Registration successful! Redirecting to login page...";
            } else {
                $error = "Error during registration: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Close connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFlex Gym - Register</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .user-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
        }
        .user-type {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
            text-align: center;
            color: black;
        }
        .user-type.active {
            background-color: red;
            color: white;
            border-color: red;
        }
        /* Alert styles */
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-box">
                <h2>Create Account</h2>
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if(!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                    <script>
                        setTimeout(function(){
                            window.location.href = 'index.php';
                        }, 2000);
                    </script>
                <?php endif; ?>
                <form class="register-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="user-type-selector">
                        <button type="button" class="user-type" data-type="gym-owner">Gym Owner</button>
                        <button type="button" class="user-type" data-type="trainer">Trainer</button>
                        <button type="button" class="user-type" data-type="gym-goer">Gym Goer</button>
                        <button type="button" class="user-type" data-type="seller">Seller</button>
                    </div>
                    <input type="hidden" name="user_type" id="userType" value="">

                    <div class="form-section personal-info">
                        <h3>Personal Information</h3>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="full_name" id="fullName" placeholder="Full Name" required>
                        </div>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" id="email" placeholder="Email Address" required>
                        </div>
                        <div class="input-group">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="phone" id="phoneNumber" placeholder="Phone Number" required>
                        </div>
                        <div class="input-row">
                            <div class="input-group">
                                <i class="fas fa-calendar"></i>
                                <input type="date" name="dob" id="dob" required>
                            </div>
                            <div class="input-group">
                                <i class="fas fa-venus-mars"></i>
                                <select name="gender" required id="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section account-info">
                        <h3>Account Details</h3>
                        <div class="input-group">
                            <i class="fas fa-user-circle"></i>
                            <input type="text" name="username" id="username" placeholder="Username" required>
                        </div>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" placeholder="Password" required>
                        </div>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                        </div>
                    </div>

                    <div class="terms-container">
                        <label class="terms-checkbox">
                            <!-- Checkbox remains unchecked by default -->
                            <input type="checkbox" name="terms" required>
                            <span>I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a></span>
                        </label>
                    </div>

                    <button type="submit" class="register-btn">Create Account</button>
                </form>
                <div class="login-link">
                    <p>Already have an account? <a href="index.php">Login Here</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const userTypeButtons = document.querySelectorAll('.user-type');
        const userTypeInput = document.getElementById('userType');

        userTypeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                // Remove active class from all buttons
                userTypeButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to the clicked button
                this.classList.add('active');
                // Update hidden input with the selected user type
                const selectedType = this.getAttribute('data-type');
                userTypeInput.value = selectedType;
            });
        });

        // Clear the alert messages from the history state so they don't reappear on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
