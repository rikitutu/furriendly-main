<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/db_connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: auth.php?error=Please log in first.");
    exit();
}

$username = $_SESSION['username'];
$user = null;
$isAdmin = false;

// Fetch user info for navbar
$isLoggedIn = isset($_SESSION['username']);
if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT name, profile_pic, is_admin FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (isset($user['is_admin']) && $user['is_admin'] == 1) {
        $isAdmin = true;
    }
}

// Check which columns exist in pets table
$cols_result = $conn->query("SHOW COLUMNS FROM pets");
$existing_cols = [];
if ($cols_result) {
    while ($r = $cols_result->fetch_assoc()) {
        $existing_cols[] = $r['Field'];
    }
}

$has_enhanced = (
    in_array('pet_breed', $existing_cols) && 
    in_array('birthdate', $existing_cols) && 
    in_array('medical_history', $existing_cols) && 
    in_array('pet_profile_pic', $existing_cols)
);

// --- EDIT MODE LOGIC ---
$is_edit_mode = false;
$pet_to_edit = null;
if (isset($_GET['edit_id'])) {
    $is_edit_mode = true;
    $pet_id = $_GET['edit_id'];
    
    if ($has_enhanced) {
        $stmt = $conn->prepare("SELECT * FROM pets WHERE id = ? AND username = ?");
    } else {
        $stmt = $conn->prepare("SELECT id, pet_name, pet_species, pet_age FROM pets WHERE id = ? AND username = ?");
    }
    
    $stmt->bind_param('is', $pet_id, $username);
    $stmt->execute();
    $pet_to_edit = $stmt->get_result()->fetch_assoc();

    if (!$pet_to_edit) {
        header("Location: your_pet.php?error=petnotfound");
        exit();
    }
}

// Enhanced file upload helper with validation
function upload_file($file_field, $target_dir = '../uploads/pets/') {
    if (empty($_FILES[$file_field]['name']) || $_FILES[$file_field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Use relative path for uploads/pets directory
    $target_dir = '../uploads/pets/';

    // Create upload directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $_FILES[$file_field]['type'];
    if (!in_array($file_type, $allowed_types)) {
        return null;
    }

    // Validate file size (max 5MB)
    if ($_FILES[$file_field]['size'] > 5 * 1024 * 1024) {
        return null;
    }

    // Generate unique filename
    $ext = strtolower(pathinfo($_FILES[$file_field]['name'], PATHINFO_EXTENSION));
    $filename = 'pet_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $target = $target_dir . $filename;

    if (move_uploaded_file($_FILES[$file_field]['tmp_name'], $target)) {
        return '../uploads/pets/' . $filename;
    }
    return null;
}

// --- Handle Pet Add/Update ---
if (isset($_POST['add_pet'])) {
    $pet_name = $_POST['pet_name'];
    $pet_breed = $_POST['pet_breed'] ?? '';
    $pet_species = $_POST['pet_species'] ?? '';
    $pet_gender = $_POST['pet_gender'] ?? 'Male';
    $birthdate = $_POST['birthdate'] ?? '';
    $medical_history = $_POST['medical_history'] ?? '';
    $medical_condition = $_POST['medical_condition'] ?? '';

    // Handle vaccines
    $vaccines = [];
    if (!empty($_POST['vaccine_type']) && is_array($_POST['vaccine_type'])) {
        foreach ($_POST['vaccine_type'] as $i => $type) {
            $date = $_POST['vaccine_date'][$i] ?? '';
            if ($type && $date) {
                $vaccines[] = ['type' => $type, 'date' => $date];
            }
        }
    }
    $vaccines_json = !empty($vaccines) ? json_encode($vaccines) : null;

    // Handle file uploads
    $pet_profile_pic_path = upload_file('pet_profile_pic');

    // Handle profile picture removal
    if (isset($_POST['remove_profile_pic']) && $_POST['remove_profile_pic'] == 1) {
      $pet_profile_pic_path = null;
    } elseif ($pet_profile_pic_path) {
      // New file uploaded
      $pet_profile_pic_path = $pet_profile_pic_path;
    } elseif ($is_edit_mode && isset($pet_to_edit['pet_profile_pic'])) {
      // Keep existing file
      $pet_profile_pic_path = $pet_to_edit['pet_profile_pic'];
    } else {
      $pet_profile_pic_path = null;
    }

    // Handle database operations
    if ($has_enhanced) {
        if (isset($_POST['pet_id'])) {
            // UPDATE existing pet
            $pet_id = $_POST['pet_id'];

            // Calculate age from birthdate for display
            $ageYears = '';
            if (!empty($birthdate)) {
                try {
                    $birth = new DateTime($birthdate);
                    $now = new DateTime();
                    $diff = $now->diff($birth);
                    $ageYears = $diff->y; // Store just the years for compatibility
                } catch (Exception $e) {
                    $ageYears = '';
                }
            }

            // Build dynamic SQL for update - include pet_profile_pic
            $sql = "UPDATE pets SET pet_name=?, pet_breed=?, pet_species=?, pet_gender=?, birthdate=?, pet_age=?, medical_history=?, medical_condition=?, pet_profile_pic=? WHERE id=? AND username=?";
            $params = [$pet_name, $pet_breed, $pet_species, $pet_gender, $birthdate, $ageYears, $medical_history, $medical_condition, $pet_profile_pic_path, $pet_id, $username];
            $types = "sssssssssis";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $_SESSION['error'] = 'Database error: ' . $conn->error;
                header("Location: pet.php?edit_id=" . $pet_id);
                exit();
            }

            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'updated';
                header("Location: your_pet.php?success=updated");
                exit();
            } else {
                $_SESSION['error'] = 'Failed to update pet: ' . $stmt->error;
                header("Location: pet.php?edit_id=" . $pet_id);
                exit();
            }
        } else {
            // INSERT new pet
            // Calculate age from birthdate
            $ageYears = '';
            if (!empty($birthdate)) {
                try {
                    $birth = new DateTime($birthdate);
                    $now = new DateTime();
                    $diff = $now->diff($birth);
                    $ageYears = $diff->y;
                } catch (Exception $e) {
                    $ageYears = '';
                }
            }

            $sql = "INSERT INTO pets (username, pet_name, pet_breed, pet_species, pet_gender, birthdate, pet_age, medical_history, vaccines, medical_condition, pet_profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                $_SESSION['error'] = 'Database error: ' . $conn->error;
                header("Location: pet.php");
                exit();
            }

            $stmt->bind_param('sssssssssss', $username, $pet_name, $pet_breed, $pet_species, $pet_gender, $birthdate, $ageYears, $medical_history, $vaccines_json, $medical_condition, $pet_profile_pic_path);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'added';
                header("Location: your_pet.php?success=added");
                exit();
            } else {
                $_SESSION['error'] = 'Failed to add pet: ' . $stmt->error;
                header("Location: pet.php");
                exit();
            }
        }
    } else {
        // Basic schema handling (if enhanced columns don't exist)
        if (isset($_POST['pet_id'])) {
            // UPDATE basic pet
            $pet_id = $_POST['pet_id'];
            $sql = "UPDATE pets SET pet_name=?, pet_species=? WHERE id=? AND username=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssis', $pet_name, $pet_species, $pet_id, $username);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'updated';
                header("Location: your_pet.php?success=updated");
                exit();
            } else {
                $_SESSION['error'] = 'Failed to update pet: ' . $stmt->error;
                header("Location: pet.php?edit_id=" . $pet_id);
                exit();
            }
        } else {
            // INSERT basic pet
            $sql = "INSERT INTO pets (username, pet_name, pet_species) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sss', $username, $pet_name, $pet_species);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'added';
                header("Location: your_pet.php?success=added");
                exit();
            } else {
                $_SESSION['error'] = 'Failed to add pet: ' . $stmt->error;
                header("Location: pet.php");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>FURRiendly | Pets</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/pet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/notifications.css">
</head>
<body>
  <header>
    <nav class="navbar">
      <div class="nav-left"><h4>FURRiendly</h4></div>
      <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="events.php">Events</a></li>
        <li><a href="contact.php">Contact</a></li>
      </ul>
      <div class="auth-butt">
        <?php if ($isLoggedIn && $user): ?>
            <div class="user-dropdown">
                <a href="#" class="profile-link">
                    <img src="<?php echo htmlspecialchars(!empty($user['profile_pic']) ? $user['profile_pic'] : '../images/default-avatar.png'); ?>" alt="Profile">
                    <span class="profile-name"><?php echo htmlspecialchars($user['name'] ?? $username); ?></span>
                </a>
                <div class="dropdown-content">
                    <a href="dashboard.php">Edit Profile</a>
                    <a href="your_pet.php">Pet Profile</a>
                    <a href="myhosting.php">My Hosting</a>
                    <hr>
                    <a href="../php/logout.php">Logout</a>
                </div>
            </div>
        <?php endif; ?>
      </div>
    </nav>
  </header>

  <main class="pet-main">
    <div class="back-container">
        <a href="your_pet.php" class="back-btn">
            <span class="back-arrow">←</span> Back to My Pets
        </a>
    </div>

    <div class="pet-container">
      <h1>🐾 <?php echo $is_edit_mode ? 'Edit Pet' : 'Add New Pet'; ?></h1>

      <?php if (isset($_SESSION['error'])): ?>
        <div class="error-msg"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
      <?php endif; ?>

      <?php if (isset($_GET['success'])): ?>
        <div class="success-msg">
          <?php 
          if ($_GET['success'] === 'added') echo 'Pet added successfully!';
          elseif ($_GET['success'] === 'updated') echo 'Pet updated successfully!';
          else echo 'Pet saved successfully!';
          ?>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" class="pet-form" oninput="updateAgePreview()">
        <!-- Pet Picture Upload -->
        <div class="pet-pic-container">
            <img src="<?php
              if ($is_edit_mode && !empty($pet_to_edit['pet_profile_pic']) && file_exists($pet_to_edit['pet_profile_pic'])) {
                echo htmlspecialchars($pet_to_edit['pet_profile_pic']);
              } else {
                echo '../images/default-pet-avatar.png';
              }
          ?>" id="petPicPreview" class="pet-profile-pic" alt="Pet Profile Picture Preview">
          <label for="pet_profile_pic" class="upload-btn-label">Upload Pet Picture</label>
          <input type="file" name="pet_profile_pic" id="pet_profile_pic" accept="image/*" onchange="previewPetImage(event)">
          <p class="file-help">Max size: 5MB. Allowed: JPG, PNG, GIF, WebP</p>

          <?php if ($is_edit_mode && !empty($pet_to_edit['pet_profile_pic'])): ?>
            <div class="remove-pic-option">
                <label>
                    <input type="checkbox" name="remove_profile_pic" value="1"> Remove current picture
                </label>
            </div>
          <?php endif; ?>
        </div>
        <?php if ($is_edit_mode): ?>
            <input type="hidden" name="pet_id" value="<?php echo $pet_to_edit['id']; ?>">
        <?php endif; ?>

        <!-- Pet Name -->
        <div class="form-group">
          <label for="pet_name">Pet Name *</label>
          <input type="text" name="pet_name" id="pet_name" placeholder="Enter pet name" value="<?php echo htmlspecialchars($pet_to_edit['pet_name'] ?? ''); ?>" required>
        </div>

        <?php if ($has_enhanced): ?>
        <!-- Age and Gender -->
        <div class="form-row">
          <div class="form-group half">
            <label for="age_preview">Age (Auto-calculated)</label>
            <input type="text" id="agePreview" name="age_preview" placeholder="Will calculate from birthdate" readonly>
          </div>
          <div class="form-group half">
            <label for="pet_gender">Gender *</label>
            <select id="pet_gender" name="pet_gender" required>
              <option value="Male" <?php if(($pet_to_edit['pet_gender'] ?? '') == 'Male') echo 'selected'; ?>>Male</option>
              <option value="Female" <?php if(($pet_to_edit['pet_gender'] ?? '') == 'Female') echo 'selected'; ?>>Female</option>
            </select>
          </div>
        </div>

        <!-- Birthdate -->
        <div class="form-group">
          <label for="birthdate">Birthdate</label>
          <input id="birthdate" type="date" name="birthdate" value="<?php echo htmlspecialchars($pet_to_edit['birthdate'] ?? ''); ?>" onchange="updateAgePreview()">
        </div>

        <!-- Species and Breed -->
        <div class="form-group">
          <label for="pet_species">Species</label>
          <input type="text" name="pet_species" id="pet_species" placeholder="e.g., Dog, Cat, Rabbit" value="<?php echo htmlspecialchars($pet_to_edit['pet_species'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label for="pet_breed">Breed</label>
          <input type="text" name="pet_breed" id="pet_breed" placeholder="e.g., Golden Retriever, Persian" value="<?php echo htmlspecialchars($pet_to_edit['pet_breed'] ?? ''); ?>">
        </div>

        <!-- Medical History -->
        <div class="form-group">
          <div class="medical-history-header">
            <label for="medical_history">Medical History</label>
            <button type="button" id="openMedicalPopupBtn" class="medical-record-btn">Add Medical Record</button>
          </div>
          <textarea id="medicalHistoryTextarea" name="medical_history" rows="5" placeholder="Medical history will be populated automatically when you add records below."><?php echo htmlspecialchars($pet_to_edit['medical_history'] ?? ''); ?></textarea>
        </div>

        <!-- Hidden container for vaccine data -->
        <div id="hiddenVaccineInputs">
            <?php if ($is_edit_mode && !empty($pet_to_edit['vaccines'])):
                $saved_vaccines = json_decode($pet_to_edit['vaccines'], true);
                if (is_array($saved_vaccines)) {
                    foreach ($saved_vaccines as $vaccine): ?>
                        <input type="hidden" name="vaccine_type[]" value="<?php echo htmlspecialchars($vaccine['type']); ?>">
                        <input type="hidden" name="vaccine_date[]" value="<?php echo htmlspecialchars($vaccine['date']); ?>">
            <?php   endforeach;
                }
            endif; ?>
        </div>
        <textarea name="medical_condition" id="hiddenMedicalConditions" style="display:none;"><?php echo htmlspecialchars($pet_to_edit['medical_condition'] ?? ''); ?></textarea>

        <?php else: ?>
        <!-- Basic form for older schema -->
        <div class="form-group">
          <label for="pet_species">Species *</label>
          <input type="text" name="pet_species" id="pet_species" placeholder="e.g., Dog, Cat, Rabbit" value="<?php echo htmlspecialchars($pet_to_edit['pet_species'] ?? ''); ?>" required>
        </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" name="add_pet" class="submit-btn">
                <?php echo $is_edit_mode ? 'Save Changes' : 'Add Pet'; ?>
            </button>
        </div>
      </form>

    </div>

    <!-- The Medical Record Popup -->
    <div id="medicalRecordPopup" class="modal">
      <div class="modal-content">
        <span class="close-modal popup-close">&times;</span>
        <h3>Add Medical Details</h3>

        <!-- Vaccine Section -->
        <div class="popup-section">
          <h4>Vaccinations</h4>
          <div class="popup-form-group">
            <label for="popupVaccineType">Vaccination Type</label>
            <input type="text" id="popupVaccineType" placeholder="e.g., Rabies">
          </div>
          <div class="popup-form-group">
            <label for="popupVaccineDate">Date</label>
            <input type="date" id="popupVaccineDate">
          </div>
          <button type="button" id="addVaccineToListBtn" class="popup-add-btn">Add Vaccine to Record</button>
          <div id="vaccineListDisplay" class="list-display-box"></div>
        </div>

        <!-- Medical Condition Section -->
        <div class="popup-section">
          <h4>Medical Conditions</h4>
          <div class="popup-form-group">
            <label for="popupCondition">Condition</label>
            <input type="text" id="popupCondition" placeholder="e.g., Allergy">
          </div>
          <button type="button" id="addConditionToListBtn" class="popup-add-btn">Add Condition to Record</button>
          <div id="conditionListDisplay" class="list-display-box"></div>
        </div>

        <a href="#" class="popup-close popup-back-link">Back</a>
      </div>
    </div>

  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const popup = document.getElementById('medicalRecordPopup');
      const openBtn = document.getElementById('openMedicalPopupBtn');
      const closeElements = document.querySelectorAll('.popup-close');

      // Popup Controls
      if (openBtn) {
        openBtn.onclick = () => { popup.style.display = 'block'; };
      }
      closeElements.forEach(el => el.onclick = () => { popup.style.display = 'none'; });
      window.onclick = (event) => { if (event.target == popup) popup.style.display = 'none'; };

      // Vaccine Logic
      const vaccineTypeInput = document.getElementById('popupVaccineType');
      const vaccineDateInput = document.getElementById('popupVaccineDate');
      const addVaccineBtn = document.getElementById('addVaccineToListBtn');
      const vaccineListDisplay = document.getElementById('vaccineListDisplay');
      const hiddenVaccineContainer = document.getElementById('hiddenVaccineInputs');
      const medicalHistoryTextarea = document.getElementById('medicalHistoryTextarea');

      if (addVaccineBtn) {
        addVaccineBtn.onclick = () => {
          const type = vaccineTypeInput.value.trim();
          const date = vaccineDateInput.value;
          if (!type || !date) {
            alert('Please provide both vaccine type and date.');
            return;
          }

          const displayItem = document.createElement('div');
          displayItem.className = 'list-display-item';
          displayItem.textContent = `${type} (Date: ${date})`;
          vaccineListDisplay.appendChild(displayItem);

          medicalHistoryTextarea.value += `\n- Vaccine: ${type} (Date: ${date})`;

          const hiddenInputs = document.createElement('div');
          hiddenInputs.innerHTML = `
            <input type="hidden" name="vaccine_type[]" value="${type}">
            <input type="hidden" name="vaccine_date[]" value="${date}">
          `;
          hiddenVaccineContainer.appendChild(hiddenInputs);

          vaccineTypeInput.value = '';
          vaccineDateInput.value = '';
          vaccineTypeInput.focus();
        };
      }

      // Medical Condition Logic
      const conditionInput = document.getElementById('popupCondition');
      const addConditionBtn = document.getElementById('addConditionToListBtn');
      const conditionListDisplay = document.getElementById('conditionListDisplay');
      const hiddenMedicalConditions = document.getElementById('hiddenMedicalConditions');

      if (addConditionBtn) {
        addConditionBtn.onclick = () => {
          const condition = conditionInput.value.trim();
          if (!condition) return;

          conditionListDisplay.innerHTML += `<div class="list-display-item">${condition}</div>`;
          hiddenMedicalConditions.value += condition + '\n';
          medicalHistoryTextarea.value += `\n- Condition: ${condition}`;
          conditionInput.value = '';
          conditionInput.focus();
        };
      }

      <?php if ($is_edit_mode): ?>
        updateAgePreview();
      <?php endif; ?>
    });

    function updateAgePreview() {
      const bd = document.getElementById('birthdate');
      if (!bd) return;

      const bdValue = bd.value;
      const out = document.getElementById('agePreview');
      if (!bdValue) {
        out.value = '';
        return;
      }

      const birth = new Date(bdValue);
      const now = new Date();
      let years = now.getFullYear() - birth.getFullYear();
      let months = now.getMonth() - birth.getMonth();
      if (months < 0) {
        years--;
        months += 12;
      }
      out.value = years + ' yrs, ' + months + ' mos';
    }

    function previewPetImage(event) {
      const reader = new FileReader();
      reader.onload = function(){
        const output = document.getElementById('petPicPreview');
        output.src = reader.result;
      };
      reader.readAsDataURL(event.target.files[0]);
    }
  </script>
  <script src="js/notifications.js"></script>
</body>
</html>
