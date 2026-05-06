<?php
session_start();

// 1) DATABASE CONNECT
$host = 'localhost';
$dbname = 'gym';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// 2) CHECK IF USER IS LOGGED IN
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
$goer_username = $_SESSION['username'];

// 3) INITIAL FETCH OF GYM GOER’S BASIC INFO
$stmt = $pdo->prepare("SELECT * FROM gym_goers WHERE username = ?");
$stmt->execute([$goer_username]);
$goer = $stmt->fetch(PDO::FETCH_ASSOC);

// 4) FETCH ALL DB RECORDS
$gymsStmt = $pdo->query("SELECT * FROM gyms");
$gyms = $gymsStmt->fetchAll(PDO::FETCH_ASSOC);

$mpStmt = $pdo->query("SELECT * FROM membership_plans");
$membershipPlans = $mpStmt->fetchAll(PDO::FETCH_ASSOC);

$tStmt = $pdo->query("SELECT * FROM trainers");
$trainers = $tStmt->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // A) UPDATE PROFILE (BASIC INFO)
    if (isset($_POST['update_profile'])) {
        // Grab the updated data from the form
        $full_name    = trim($_POST['full_name']);
        $email        = trim($_POST['email']);
        $phone        = trim($_POST['phone']);
        $dob          = trim($_POST['dob']);
        $gender       = trim($_POST['gender']);
        $fitness_goal = trim($_POST['fitness_goal']);

        // Basic validation: all fields must not be empty
        if (empty($full_name) || empty($email) || empty($phone) || empty($dob) || empty($gender) || empty($fitness_goal)) {
            $errors[] = "All fields are required for updating your profile.";
        } else {
            // Update the gym_goers table
            $updateProfileStmt = $pdo->prepare("
                UPDATE gym_goers
                   SET full_name    = ?,
                       email        = ?,
                       phone        = ?,
                       dob          = ?,
                       gender       = ?,
                       fitness_goal = ?
                 WHERE username     = ?
            ");
            $updateProfileStmt->execute([
                $full_name,
                $email,
                $phone,
                $dob,
                $gender,
                $fitness_goal,
                $goer_username
            ]);

            // Store success message in session to show after page reload
            $_SESSION['success'] = "Profile updated successfully.";

            // Redirect to the same page to prevent form resubmission on refresh
            header("Location: profile.php");
            exit;
        }
    }

    // B) COMPLETE REGISTRATION (GYM, MEMBERSHIP PLAN, TRAINER, PAYMENT)
    if (isset($_POST['complete_registration'])) {
        // Retrieve selected IDs from hidden inputs
        $selected_gym             = trim($_POST['selected_gym']);
        $selected_membership_plan = trim($_POST['selected_membership_plan']);
        $selected_trainer         = trim($_POST['selected_trainer']);

        // Retrieve card details for format validation
        $card_number = trim($_POST['card_number']);
        $expiry      = trim($_POST['expiry']);
        $cvv         = trim($_POST['cvv']);
        $card_name   = trim($_POST['card_name']);

        // Validate payment info
        if (!preg_match('/^\d{4}\s?\d{4}\s?\d{4}\s?\d{4}$/', $card_number)) {
            $errors[] = "Invalid card number.";
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
            $errors[] = "Invalid expiry date (MM/YY).";
        }
        if (!preg_match('/^\d{3}$/', $cvv)) {
            $errors[] = "Invalid CVV (3 digits).";
        }
        if (empty($card_name)) {
            $errors[] = "Name on card is required.";
        }

        if (empty($errors)) {
            // Process payment (assumed successful)

            // Update gym_goers record with the selected gym
            $updateGymStmt = $pdo->prepare("
                UPDATE gym_goers
                   SET gym_username = ?
                 WHERE username    = ?
            ");
            $updateGymStmt->execute([$selected_gym, $goer_username]);

            // Store membership_plan (plan_id) and trainer in gym_goers table
            $updatePlanTrainerStmt = $pdo->prepare("
                UPDATE gym_goers
                   SET membership_plan  = ?,
                       preferred_trainer = ?
                 WHERE username         = ?
            ");
            $updatePlanTrainerStmt->execute([$selected_membership_plan, $selected_trainer, $goer_username]);

            // Redirect to dashboard or success page
            header("Location: dashboard.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>FitFlex - Profile Setup</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/gym-goer.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Make profile picture smaller in Basic Info */
    #profilePreview {
      width: 100px;
      height: 100px;
      object-fit: cover;
    }
    /* Use the same styling for update and next buttons */
    .next-btn {
      background-color: red;
      color: #fff;
      border: none;
      padding: 10px 20px;
      cursor: pointer;
    }
    .next-btn:hover {
      background-color: darkred;
    }
    .select-btn.selected,
    .select-btn:hover {
      background-color: darkred;
      color: #fff;
    }
    .error-messages p {
      color: red;
      margin: 0.3rem 0;
    }
    .success-message {
      color: green;
      margin: 0.3rem 0;
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
      <a href="profile.php" class="active">
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
    </nav>
    <div class="sidebar-footer">
      <a href="../index.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </a>
    </div>
  </div>

  <div class="main-content">
    <header class="dashboard-header">
      <h2>Complete Your Profile</h2>
      <div class="setup-progress">
        <div class="progress-step active" data-step="1">
          <i class="fas fa-user"></i>
          <span>Basic Info</span>
        </div>
        <div class="progress-step" data-step="2">
          <i class="fas fa-dumbbell"></i>
          <span>Gym Selection</span>
        </div>
        <div class="progress-step" data-step="3">
          <i class="fas fa-user-friends"></i>
          <span>Trainer Selection</span>
        </div>
        <div class="progress-step" data-step="4">
          <i class="fas fa-credit-card"></i>
          <span>Payment</span>
        </div>
      </div>
    </header>

    <?php if (isset($_SESSION['success'])): ?>
      <div class="success-message">
        <p><?php echo htmlspecialchars($_SESSION['success']); ?></p>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="error-messages">
        <?php foreach($errors as $error): ?>
          <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" id="profileSetupForm" enctype="multipart/form-data">
      <div class="setup-container">

        <div class="setup-step active" id="step1">
          <div class="profile-form">
            <div class="profile-picture">
              <input type="file" id="profileUpload" name="profile_picture" accept="image/*" hidden>
            </div>
            <div class="form-grid">
              <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($goer['full_name'] ?? ''); ?>" required>
              </div>
              <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($goer['email'] ?? ''); ?>" required>
              </div>
              <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($goer['phone'] ?? ''); ?>" required>
              </div>
              <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="dob" value="<?php echo htmlspecialchars($goer['dob'] ?? ''); ?>" required>
              </div>
              <div class="form-group">
                <label>Gender</label>
                <select name="gender" required>
                  <option value="male" <?php echo (isset($goer['gender']) && $goer['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                  <option value="female" <?php echo (isset($goer['gender']) && $goer['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                  <option value="other" <?php echo (isset($goer['gender']) && $goer['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                </select>
              </div>
              <div class="form-group">
                <label>Fitness Goal</label>
                <select name="fitness_goal" required>
                  <option value="weight-loss" <?php echo (!empty($goer['fitness_goal']) && $goer['fitness_goal'] == 'weight-loss') ? 'selected' : ''; ?>>Weight Loss</option>
                  <option value="muscle-gain" <?php echo (!empty($goer['fitness_goal']) && $goer['fitness_goal'] == 'muscle-gain') ? 'selected' : ''; ?>>Muscle Gain</option>
                  <option value="endurance" <?php echo (!empty($goer['fitness_goal']) && $goer['fitness_goal'] == 'endurance') ? 'selected' : ''; ?>>Endurance</option>
                  <option value="flexibility" <?php echo (!empty($goer['fitness_goal']) && $goer['fitness_goal'] == 'flexibility') ? 'selected' : ''; ?>>Flexibility</option>
                </select>
              </div>
            </div>
            <div class="form-actions">
              <!-- Update Profile button styled like the Next button -->
              <button type="button" class="next-btn" onclick="validateStep1()">Next: Select Gym</button>
            </div>
          </div>
        </div>

        <div class="setup-step" id="step2">
          <div class="gym-selection">
            <div class="gyms-grid" id="gymsGrid">
              <?php foreach ($gyms as $gym): ?>
                <div class="gym-card" data-gymid="<?php echo htmlspecialchars($gym['gym_username']); ?>" data-location="<?php echo htmlspecialchars($gym['address']); ?>">
                  <div class="gym-info">
                    <h3><?php echo htmlspecialchars($gym['gym_name']); ?></h3>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($gym['address']); ?></p>
                    <p class="membership-fee">$<?php echo htmlspecialchars($gym['RegistrationFee']); ?>/month</p>
                    <button type="button" class="select-btn" onclick="selectGym('<?php echo htmlspecialchars($gym['gym_username']); ?>', <?php echo (float) $gym['RegistrationFee']; ?>, event)">Select Gym</button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Membership Plans Container -->
            <div id="membershipPlansContainer" style="display:none; margin-top:20px;">
              <h3>Select a Membership Plan</h3>
              <div class="membership-plans-grid" id="membershipPlansGrid">
                <!-- Plans injected by JavaScript -->
              </div>
            </div>
            <div class="form-actions">
              <button type="button" class="back-btn" onclick="prevStep(1)">Back</button>
              <button type="button" class="next-btn" onclick="nextStep(3)" id="gymNextBtn" disabled>Next: Select Trainer</button>
            </div>
          </div>
        </div>

        <div class="setup-step" id="step3">
          <div class="trainer-selection">
            <div class="trainers-grid" id="trainersGrid">
              <!-- Trainer cards injected by JavaScript -->
            </div>
            <div class="form-actions">
              <button type="button" class="back-btn" onclick="prevStep(2)">Back</button>
              <button type="button" class="next-btn" onclick="nextStep(4)" id="trainerNextBtn" disabled>Next: Payment</button>
            </div>
          </div>
        </div>

        <div class="setup-step" id="step4">
          <div class="payment-section">
            <div class="summary-card">
              <h3>Registration Summary</h3>
              <div class="summary-details">
                <div class="summary-item">
                  <span>Gym Membership</span>
                  <span id="gymMembershipFee">$0</span>
                </div>
                <div class="summary-item">
                  <span>Trainer Services</span>
                  <span id="trainerFee">$0</span>
                </div>
                <div class="summary-item">
                  <span>Registration Fee</span>
                  <span id="registrationFee">$0</span>
                </div>
                <div class="summary-item total">
                  <span>Total</span>
                  <span id="totalAmount">$0</span>
                </div>
              </div>
            </div>
            <div class="payment-form">
              <div class="form-group">
                <label>Card Number</label>
                <input type="text" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Expiry Date</label>
                  <input type="text" name="expiry" placeholder="MM/YY" maxlength="5" required>
                </div>
                <div class="form-group">
                  <label>CVV</label>
                  <input type="text" name="cvv" placeholder="123" maxlength="3" required>
                </div>
              </div>
              <div class="form-group">
                <label>Name on Card</label>
                <input type="text" name="card_name" placeholder="John Doe" required>
              </div>
            </div>
            <!-- Hidden inputs to store final selections -->
            <input type="hidden" name="selected_gym" id="selectedGym">
            <input type="hidden" name="selected_membership_plan" id="selectedMembershipPlan">
            <input type="hidden" name="selected_trainer" id="selectedTrainer">
            <div class="form-actions">
              <button type="button" class="back-btn" onclick="prevStep(3)">Back</button>
              <button type="submit" class="next-btn" name="complete_registration">Complete Registration</button>
            </div>
          </div>
        </div>

      </div>
    </form>
  </div>
</div>

<script>
  var membershipPlans = <?php echo json_encode($membershipPlans); ?>;
  var trainers        = <?php echo json_encode($trainers); ?>;

  let selectedGymId            = '';
  let selectedMembershipFee    = 0;
  let selectedTrainerFee       = 0;
  let selectedRegistrationFee  = 0;
  let selectedMembershipPlanId = '';
  let selectedTrainerId        = '';

  function validateStep1() {
    var fullName = document.querySelector('input[name="full_name"]').value.trim();
    var email = document.querySelector('input[name="email"]').value.trim();
    var phone = document.querySelector('input[name="phone"]').value.trim();
    var dob = document.querySelector('input[name="dob"]').value.trim();
    var gender = document.querySelector('select[name="gender"]').value;
    var fitnessGoal = document.querySelector('select[name="fitness_goal"]').value;
    if(fullName === "" || email === "" || phone === "" || dob === "" || gender === "" || fitnessGoal === "") {
      alert("Please fill in all the basic information fields.");
      return;
    }
    nextStep(2);
  }

  function nextStep(step) {
    document.querySelectorAll('.setup-step').forEach(function(stepDiv) {
      stepDiv.classList.remove('active');
    });
    document.querySelectorAll('.progress-step').forEach(function(pStep) {
      pStep.classList.remove('active');
    });
    document.getElementById('step' + step).classList.add('active');
    document.querySelector('.progress-step[data-step="'+step+'"]').classList.add('active');
    if (step === 4) {
      updateSummary();
    }
  }

  function prevStep(step) {
    document.querySelectorAll('.setup-step').forEach(function(stepDiv) {
      stepDiv.classList.remove('active');
    });
    document.querySelectorAll('.progress-step').forEach(function(pStep) {
      pStep.classList.remove('active');
    });
    document.getElementById('step' + step).classList.add('active');
    document.querySelector('.progress-step[data-step="'+step+'"]').classList.add('active');
  }

  function selectGym(gymId, regFee, evt) {
    selectedGymId           = gymId;
    selectedRegistrationFee = regFee;
    document.getElementById('selectedGym').value = gymId;

    document.querySelectorAll('.gym-card .select-btn').forEach(function(btn) {
      btn.classList.remove('selected');
    });
    evt.target.classList.add('selected');

    let plansContainer = document.getElementById('membershipPlansContainer');
    let plansGrid      = document.getElementById('membershipPlansGrid');
    plansGrid.innerHTML = "";
    membershipPlans.forEach(function(plan) {
      if (plan.gym_username === gymId) {
        let planDiv = document.createElement('div');
        planDiv.className = "membership-plan-card";
        planDiv.setAttribute("data-planid", plan.plan_id);
        planDiv.setAttribute("data-price", plan.price);

        planDiv.innerHTML = `
          <h4>${plan.plan_name}</h4>
          <p>Price: $${plan.price}</p>
          <p>Features: ${plan.features}</p>
          <button type="button" class="select-btn" onclick="selectMembershipPlan('${plan.plan_id}', ${plan.price}, event)">Select Plan</button>
        `;
        plansGrid.appendChild(planDiv);
      }
    });
    plansContainer.style.display = "block";
    document.getElementById('gymNextBtn').disabled = true;
  }

  function selectMembershipPlan(planId, price, evt) {
    selectedMembershipPlanId = planId;
    selectedMembershipFee    = price;
    document.getElementById('selectedMembershipPlan').value = planId;
    document.querySelectorAll('.membership-plan-card .select-btn').forEach(function(btn) {
      btn.classList.remove('selected');
    });
    evt.target.classList.add('selected');
    document.getElementById('gymNextBtn').disabled = false;
  }

  function loadTrainers() {
    let trainersGrid = document.getElementById('trainersGrid');
    trainersGrid.innerHTML = "";
    trainers.forEach(function(trainer) {
      if (trainer.gym_username === selectedGymId && trainer.fee && trainer.fee !== '0') {
        let trainerDiv = document.createElement('div');
        trainerDiv.className = "trainer-card";
        trainerDiv.setAttribute("data-trainerid", trainer.username);
        let profilePic = `..\\assets\\images\\trainers\\${trainer.profile_picture}`;
        trainerDiv.innerHTML = `
          <div class='trainer-header'>
            <img src='${profilePic}' alt='${trainer.full_name}'>
            <div class='trainer-status available'></div>
          </div>
          <div class='trainer-info'>
            <h3>${trainer.full_name}</h3>
            <p class='specialization'>${trainer.specialization}</p>
            <p class='experience'><i class='fas fa-clock'></i> ${trainer.experience} years experience</p>
            <p class='rate'>$${trainer.fee}/session</p>
            <button type='button' class='select-btn' onclick='selectTrainer("${trainer.username}", ${trainer.fee}, event)'>Select Trainer</button>
          </div>
        `;
        trainersGrid.appendChild(trainerDiv);
      }
    });
  }

  function selectTrainer(trainerId, fee, evt) {
    selectedTrainerId  = trainerId;
    selectedTrainerFee = fee;
    document.getElementById('selectedTrainer').value = trainerId;
    document.querySelectorAll('.trainer-card .select-btn').forEach(function(btn) {
      btn.classList.remove('selected');
    });
    evt.target.classList.add('selected');
    document.getElementById('trainerNextBtn').disabled = false;
  }

  function updateSummary() {
    let mFee = parseFloat(selectedMembershipFee) || 0;
    let tFee = parseFloat(selectedTrainerFee) || 0;
    let rFee = parseFloat(selectedRegistrationFee) || 0;
    document.getElementById('gymMembershipFee').innerText = "$" + mFee;
    document.getElementById('trainerFee').innerText       = "$" + tFee;
    document.getElementById('registrationFee').innerText  = "$" + rFee;
    document.getElementById('totalAmount').innerText      = "$" + (mFee + tFee + rFee);
  }

  document.addEventListener("DOMContentLoaded", function() {
    document.getElementById('gymNextBtn').addEventListener('click', function() {
      loadTrainers();
      nextStep(3);
    });

    // ----- KEY FIX BELOW -----
    document.getElementById('profileSetupForm').addEventListener('submit', function(e) {
      // Validate payment fields
      var cardNumber = document.querySelector('input[name="card_number"]').value.trim();
      var expiry     = document.querySelector('input[name="expiry"]').value.trim();
      var cvv        = document.querySelector('input[name="cvv"]').value.trim();
      var cardName   = document.querySelector('input[name="card_name"]').value.trim();

      var cardNumberPattern = /^\d{4}\s?\d{4}\s?\d{4}\s?\d{4}$/;
      var expiryPattern     = /^(0[1-9]|1[0-2])\/\d{2}$/;
      var cvvPattern        = /^\d{3}$/;

      // If any validation fails, prevent submission
      if(!cardNumberPattern.test(cardNumber)) {
        alert("Invalid card number.");
        e.preventDefault();
        return;
      }
      if(!expiryPattern.test(expiry)) {
        alert("Invalid expiry date (MM/YY).");
        e.preventDefault();
        return;
      }
      if(!cvvPattern.test(cvv)) {
        alert("Invalid CVV (3 digits).");
        e.preventDefault();
        return;
      }
      if(cardName === "") {
        alert("Name on card is required.");
        e.preventDefault();
        return;
      }

      var confirmPayment = confirm("Are you sure you want to complete the payment?");
      if(!confirmPayment) {
        // If user cancels payment, prevent form submission
        e.preventDefault();
        return;
      } else {
        alert("Payment successful.");
      }
    });
  });
</script>

</body>
</html>
