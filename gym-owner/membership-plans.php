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

// Check if gym owner is logged in
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'gym-owner') {
    header("Location: ../index.php"); // Redirect to login page if not logged in or not a gym owner
    exit();
}

$gymOwnerUsername = $_SESSION['username']; // This should match gym_owners.username
$error     = "";
$success   = "";

// Fetch gyms belonging to the logged-in gym owner (using owner_username field)
$gymsQuery = "SELECT gym_username, gym_name FROM gyms WHERE owner_username = ?";
$gymsStmt  = $conn->prepare($gymsQuery);
$gymsStmt->bind_param("s", $gymOwnerUsername);
$gymsStmt->execute();
$gymsResult = $gymsStmt->get_result();
$gyms       = $gymsResult->fetch_all(MYSQLI_ASSOC);
$gymsStmt->close();

// Fetch Existing Membership Plans for all gyms that belong to the owner
$plansQuery = "SELECT mp.plan_id, mp.plan_name, mp.price, mp.features, mp.status, mp.gym_username 
               FROM membership_plans mp 
               JOIN gyms g ON mp.gym_username = g.gym_username 
               WHERE g.owner_username = ?";
$plansStmt  = $conn->prepare($plansQuery);
$plansStmt->bind_param("s", $gymOwnerUsername);
$plansStmt->execute();
$plansResult     = $plansStmt->get_result();
$membershipPlans = $plansResult->fetch_all(MYSQLI_ASSOC);
$plansStmt->close();

// Handle Adding/Editing New Membership Plan
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['savePlan'])) {
    $planId       = trim($_POST['planId']);
    $planName     = trim($_POST['planName']);
    $price        = trim($_POST['price']);
    $features     = trim($_POST['features']);
    $status       = trim($_POST['status']);
    $selectedGym  = trim($_POST['selectedGym']); // This will be the gym_username of the selected gym

    if (empty($planId)) {
        // INSERT new plan with the selected gym's gym_username
        $insertPlanQuery = "INSERT INTO membership_plans (gym_username, plan_name, price, features, status) VALUES (?, ?, ?, ?, ?)";
        $insertPlanStmt  = $conn->prepare($insertPlanQuery);
        $insertPlanStmt->bind_param("ssdss", $selectedGym, $planName, $price, $features, $status);

        if ($insertPlanStmt->execute()) {
            $success = "Membership plan added successfully!";
        } else {
            $error = "Error adding membership plan: " . $insertPlanStmt->error;
        }
        $insertPlanStmt->close();
    } else {
        // UPDATE existing plan (ensure it belongs to one of the owner's gyms)
        $updatePlanQuery = "UPDATE membership_plans 
                            SET plan_name = ?, price = ?, features = ?, status = ?, gym_username = ? 
                            WHERE plan_id = ? 
                            AND gym_username IN (SELECT gym_username FROM gyms WHERE owner_username = ?)";
        $updatePlanStmt  = $conn->prepare($updatePlanQuery);
        $updatePlanStmt->bind_param("ssdssis", $planName, $price, $features, $status, $selectedGym, $planId, $gymOwnerUsername);

        if ($updatePlanStmt->execute()) {
            $success = "Membership plan updated successfully!";
        } else {
            $error = "Error updating membership plan: " . $updatePlanStmt->error;
        }
        $updatePlanStmt->close();
    }

    // Set flash messages and redirect
    if (!empty($success)) {
        $_SESSION['success'] = $success;
    }
    if (!empty($error)) {
        $_SESSION['error'] = $error;
    }
    header("Location: membership-plans.php");
    exit();
}

// Handle Deleting Membership Plan
if (isset($_GET['delete_plan_id'])) {
    $planIdToDelete   = trim($_GET['delete_plan_id']);
    $deletePlanQuery  = "DELETE FROM membership_plans 
                         WHERE plan_id = ? 
                         AND gym_username IN (SELECT gym_username FROM gyms WHERE owner_username = ?)";
    $deletePlanStmt   = $conn->prepare($deletePlanQuery);
    $deletePlanStmt->bind_param("is", $planIdToDelete, $gymOwnerUsername);

    if ($deletePlanStmt->execute()) {
        $success = "Membership plan deleted successfully!";
    } else {
        $error = "Error deleting membership plan: " . $deletePlanStmt->error;
    }
    $deletePlanStmt->close();

    // Set flash messages and redirect
    if (!empty($success)) {
        $_SESSION['success'] = $success;
    }
    if (!empty($error)) {
        $_SESSION['error'] = $error;
    }
    header("Location: membership-plans.php");
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FitFlex - Membership Plans</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/gym-owner.css">
  <link rel="stylesheet" href="../assets/css/account-management.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
      body, h1, h2, h3, h4, h5, h6, p, a, li, label, span, select, input, textarea {
          color: white;
      }
      .plans-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
          gap: 20px;
          margin-top: 20px;
      }
      .plan-card {
          background-color: #272727;
          border-radius: 8px;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
          padding: 20px;
      }
      .plan-header h3 {
          margin-top: 0;
      }
      .price {
          color: #007bff;
          font-size: 1.5em;
          margin-bottom: 10px;
      }
      .price span {
          font-size: 0.8em;
      }
      .plan-features ul {
          list-style: none;
          padding: 0;
      }
      .plan-features li {
          margin-bottom: 8px;
      }
      .plan-features li i {
          margin-right: 5px;
          color: #28a745;
      }
      .plan-features li .fa-times {
          color: #dc3545;
      }
      .plan-actions button, .plan-actions a {
          background-color: #007bff;
          color: white;
          border: none;
          padding: 8px 15px;
          border-radius: 5px;
          cursor: pointer;
          transition: background-color 0.3s ease;
          margin-right: 5px;
          text-decoration: none;
      }
      .plan-actions button:hover, .plan-actions a:hover {
          background-color: #0056b3;
      }
      .add-plan-btn {
          background-color: black;
          color: white;
          border: none;
          padding: 10px 20px;
          border-radius: 5px;
          cursor: pointer;
          transition: background-color 0.3s ease;
          margin-left: auto;
          display: block;
          margin-top: 15px;
      }
      .add-plan-btn:hover {
          background-color: #1e7e34;
      }
      #planModal {
          display: none; /* Hidden by default */
          position: fixed; /* Stay in place */
          z-index: 1; /* Sit on top */
          left: 0;
          top: 0;
          width: 100%; /* Full width */
          height: 100%; /* Full height */
          overflow: auto; /* Enable scroll if needed */
          background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
      }
      .modal-content {
          background-color: #fefefe;
          margin: 15% auto; /* 15% from the top and centered */
          padding: 20px;
          border: 1px solid #888;
          width: 80%; /* Could be more or less, depending on screen size */
          border-radius: 8px;
          position: relative;
          color: black; /* Ensure all text inside modal is black */
      }
      .modal-content h3, .modal-content label, .modal-content select,
      .modal-content input, .modal-content textarea, .modal-content button {
          color: black;
      }
      .close-btn {
          color: #aaa;
          position: absolute;
          top: 10px;
          right: 15px;
          font-size: 28px;
          font-weight: bold;
          cursor: pointer;
      }
      .close-btn:hover,
      .close-btn:focus {
          color: black;
          text-decoration: none;
          cursor: pointer;
      }
      .plan-form label {
          display: block;
          margin-bottom: 5px;
          font-weight: bold;
      }
      .plan-form input[type=text],
      .plan-form input[type=number],
      .plan-form select,
      .plan-form textarea {
          width: 100%;
          padding: 10px;
          margin-bottom: 15px;
          border: 1px solid #ccc;
          border-radius: 5px;
          box-sizing: border-box;
      }
      .plan-form .price-input {
          display: flex;
          align-items: center;
      }
      .plan-form .price-input span {
          margin-right: 5px;
      }
      .plan-form .form-actions {
          display: flex;
          justify-content: flex-end;
      }
      .plan-form .form-actions button {
          margin-left: 10px;
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
                  <a href="membership-plans.php" class="active">
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
                      <h1>Membership Plans</h1>
                      <p class="subtitle">Manage your gym's membership plans and pricing</p>
                  </div>
                  <button class="add-plan-btn" onclick="openPlanModal()">
                      <i class="fas fa-plus"></i>
                      Add New Plan
                  </button>
              </div>
          </header>

          <div class="content-body">
              <?php if (isset($_SESSION['error']) && $_SESSION['error'] !== ''): ?>
                  <div id="errorMessage" class="error-message" style="color: red; margin-top: 10px;">
                      <?php 
                          echo htmlspecialchars($_SESSION['error']); 
                          unset($_SESSION['error']);
                      ?>
                  </div>
              <?php endif; ?>
              <?php if (isset($_SESSION['success']) && $_SESSION['success'] !== ''): ?>
                  <div id="successMessage" class="success-message" style="color: green; margin-top: 10px;">
                      <?php 
                          echo htmlspecialchars($_SESSION['success']); 
                          unset($_SESSION['success']);
                      ?>
                  </div>
              <?php endif; ?>

              <div class="plans-grid">
                  <?php if (empty($membershipPlans)): ?>
                      <p style="color: black;">No membership plans added yet.</p>
                  <?php else: ?>
                      <?php foreach ($membershipPlans as $plan): ?>
                          <div class="plan-card">
                              <div class="plan-header">
                                  <h3><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
                                  <p class="price">Rs.<?php echo htmlspecialchars($plan['price']); ?><span>/month</span></p>
                              </div>
                              <div class="plan-features">
                                  <ul>
                                      <?php
                                      $featuresList = explode("\n", $plan['features']);
                                      foreach ($featuresList as $feature):
                                          $trimmedFeature = trim($feature);
                                          if (!empty($trimmedFeature)):
                                      ?>
                                              <li><i class="fas fa-check"></i> <?php echo htmlspecialchars($trimmedFeature); ?></li>
                                      <?php
                                          endif;
                                      endforeach;
                                      ?>
                                  </ul>
                              </div>
                              <div class="plan-actions">
                                  <a href="membership-plans.php?delete_plan_id=<?php echo urlencode($plan['plan_id']); ?>"
                                     class="delete-btn"
                                     onclick="return confirm('Are you sure you want to delete this plan?')">
                                      <i class="fas fa-trash"></i> Delete
                                  </a>
                              </div>
                          </div>
                      <?php endforeach; ?>
                  <?php endif; ?>
              </div>
          </div>

          <!-- Add/Edit Plan Modal -->
          <div id="planModal" class="modal">
              <div class="modal-content">
                  <span class="close-btn" onclick="closePlanModal()">&times;</span>
                  <h3>Add New Plan</h3>
                  <form id="planForm" class="plan-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                      <!-- Hidden field to differentiate add vs. edit -->
                      <input type="hidden" name="planId" id="planId" value="">
                      
                      <!-- Combo box to select the gym (using gym_username as value) -->
                      <div class="form-group">
                          <label>Select Gym</label>
                          <select name="selectedGym" id="selectedGym" required>
                              <?php foreach($gyms as $gym): ?>
                                  <option value="<?php echo htmlspecialchars($gym['gym_username']); ?>">
                                      <?php echo htmlspecialchars($gym['gym_name']); ?>
                                  </option>
                              <?php endforeach; ?>
                          </select>
                      </div>

                      <div class="form-group">
                          <label>Plan Name</label>
                          <select name="planName" id="planName" required>
                              <option value="">Select Plan</option>
                              <option value="Basic Plan">Basic Plan</option>
                              <option value="Premium Plan">Premium Plan</option>
                              <option value="Elite Plan">Elite Plan</option>
                          </select>
                      </div>

                      <div class="form-group">
                          <label>Price (Monthly)</label>
                          <div class="price-input">
                              <span>Rs.</span>
                              <input type="number" name="price" id="price" step="0.01" min="0" required>
                          </div>
                      </div>

                      <div class="form-group">
                          <label>Features (one per line)</label>
                          <textarea name="features" id="features" rows="4" required></textarea>
                      </div>

                      <div class="form-group">
                          <label>Status</label>
                          <select name="status" id="status" required>
                              <option value="active">Active</option>
                              <option value="inactive">Inactive</option>
                          </select>
                      </div>

                      <div class="form-actions">
                          <button type="button" class="cancel-btn" onclick="closePlanModal()">Cancel</button>
                          <button type="submit" class="save-btn" name="savePlan">Save Plan</button>
                      </div>
                  </form>
              </div>
          </div>

          <!-- Delete Plan Modal (Unused in this setup) -->
          <div id="deleteModal" class="modal">
              <div class="modal-content">
                  <span class="close-btn" onclick="closeDeleteModal()">&times;</span>
                  <h3>Delete Plan</h3>
                  <p>Are you sure you want to delete this membership plan? This action cannot be undone.</p>
                  <div class="modal-actions">
                      <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                      <button class="delete-btn" onclick="confirmDelete()">Delete</button>
                  </div>
              </div>
          </div>

      </div>
  </div>

  <script>
      const planModal   = document.getElementById("planModal");
      const deleteModal = document.getElementById("deleteModal");

      // Open modal for adding a plan
      function openPlanModal() {
          planModal.style.display = "block";
          document.querySelector("#planModal h3").innerText = "Add New Plan";
          document.querySelector("#planForm").reset();

          // Clear hidden ID to indicate 'add'
          document.getElementById('planId').value = '';

          // Re-enable planName (so user can pick from dropdown)
          document.getElementById('planName').disabled = false;
      }

      function closePlanModal() {
          planModal.style.display = "none";
      }

      // Open modal for editing a plan
      function editPlan(planId, planName, price, features, status) {
          planModal.style.display = "block";
          document.querySelector("#planModal h3").innerText = "Edit Plan";

          // Populate form fields
          document.getElementById('planId').value     = planId;
          document.getElementById('planName').value   = planName;
          document.getElementById('price').value      = price;
          document.getElementById('features').value   = features;
          document.getElementById('status').value     = status;

          // Allow editing planName or disable if needed:
          document.getElementById('planName').disabled = false;
      }

      function closeDeleteModal() {
          deleteModal.style.display = "none";
      }

      function confirmDelete() {
          // Not used in this example, since we directly link with confirm() in the Delete anchor
      }

      // Close modals if clicked outside
      window.onclick = function(event) {
          if (event.target === planModal) {
              planModal.style.display = "none";
          }
          if (event.target === deleteModal) {
              deleteModal.style.display = "none";
          }
      };

      // Auto-hide messages after 3 seconds
      const successMessage = document.getElementById('successMessage');
      const errorMessage   = document.getElementById('errorMessage');

      if (successMessage) {
          setTimeout(() => {
              successMessage.style.display = 'none';
          }, 3000);
      }
      if (errorMessage) {
          setTimeout(() => {
              errorMessage.style.display = 'none';
          }, 3000);
      }
  </script>
</body>
</html>
