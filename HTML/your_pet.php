<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/db_connect.php';

if (!isset($_SESSION['username'])) {
    header('Location: auth.php');
    exit();
}

$username = $_SESSION['username'];
$user = null;
$isAdmin = false;

$stmt = $conn->prepare("SELECT name, profile_pic, is_admin FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (isset($user['is_admin']) && $user['is_admin'] == 1) {
    $isAdmin = true;
}

// Get user's pets
$pets_stmt = $conn->prepare("SELECT * FROM pets WHERE username = ?");
$pets_stmt->bind_param("s", $username);
$pets_stmt->execute();
$pets_result = $pets_stmt->get_result();
$pets = $pets_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Pets - FURRiendly</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/your_pet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/notifications.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-left">
                <h4>FURRiendly</h4>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li class="events-dropdown">
                    <a href="events.php" class="events-link">Events</a>
                    <div class="events-dropdown-content">
                        <a href="events.php?filter=upcoming">Upcoming Events</a>
                        <a href="joined_events.php">Joined Events</a>
                    </div>
                </li>
                <li><a href="contact.php">Contact</a></li>

                <?php if ($isAdmin): ?>
                    <li><a href="../html/ADMIN/admin_dashboard.php">Admin</a></li>
                <?php endif; ?>
            </ul>

            <div class="auth-butt">
                <?php if ($user): ?>
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
                <?php else: ?>
                    <a href="auth.php" class="btn auth">Log-in/Sign-up</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="your-pet-main">
        <div class="back-container">
            <a href="dashboard.php" class="back-btn">
                <span class="back-arrow">←</span> Back to Dashboard
            </a>
        </div>

        <div class="your-pet-container">
            <h1>My Pets</h1>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-msg"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-msg"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="pets-grid">
                <?php if (empty($pets)): ?>
                    <div class="no-pets">
                        <div class="no-pets-icon">🐾</div>
                        <h3>No Pets Registered</h3>
                        <p>You haven't registered any pets yet. Add your first pet to get started!</p>
                        <a href="pet.php" class="action-btn add-pet-btn">Add Your First Pet</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($pets as $pet): ?>
                    <div class="pet-card">
                        <div class="pet-header">
                            <img src="<?php echo htmlspecialchars(!empty($pet['pet_profile_pic']) ? $pet['pet_profile_pic'] : '../images/default-pet-avatar.png'); ?>"
                                 alt="<?php echo htmlspecialchars($pet['pet_name']); ?>"
                                 class="pet-avatar">
                            <div class="pet-info">
                                <h3 class="pet-name"><?php echo htmlspecialchars($pet['pet_name']); ?></h3>
                                <p class="pet-species"><?php echo htmlspecialchars($pet['pet_species']); ?></p>
                                <?php if (!empty($pet['pet_breed'])): ?>
                                    <p class="pet-breed"><?php echo htmlspecialchars($pet['pet_breed']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="pet-details">
                            <div class="detail-item">
                                <strong>Age:</strong> 
                                <?php 
                                if (!empty($pet['birthdate'])) {
                                    $birthdate = new DateTime($pet['birthdate']);
                                    $now = new DateTime();
                                    $age = $now->diff($birthdate);
                                    echo $age->y . ' years, ' . $age->m . ' months';
                                } else {
                                    echo 'Not specified';
                                }
                                ?>
                            </div>
                            <?php if (!empty($pet['pet_gender'])): ?>
                            <div class="detail-item">
                                <strong>Gender:</strong> <?php echo htmlspecialchars($pet['pet_gender']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($pet['medical_history'])): ?>
                            <div class="detail-item">
                                <strong>Medical History:</strong> 
                                <span class="medical-history"><?php echo htmlspecialchars($pet['medical_history']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($pet['medical_condition'])): ?>
                            <div class="detail-item">
                                <strong>Conditions:</strong> 
                                <span class="medical-condition"><?php echo htmlspecialchars($pet['medical_condition']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Medical Documents Section -->
                        <div class="medical-documents-section">
                            <h4>Medical Documents</h4>
                            <div class="documents-actions">
                                <button class="action-btn view-docs-btn" onclick="viewMedicalDocuments(<?php echo $pet['id']; ?>, '<?php echo htmlspecialchars($pet['pet_name']); ?>')">
                                    📁 View All Documents
                                </button>
                                <button class="action-btn upload-docs-btn" onclick="openUploadModal(<?php echo $pet['id']; ?>, '<?php echo htmlspecialchars($pet['pet_name']); ?>')">
                                    📤 Upload Document
                                </button>
                            </div>
                        </div>

                        <div class="pet-actions">
                            <a href="pet.php?edit_id=<?php echo $pet['id']; ?>" class="action-btn edit-btn">Edit</a>
                            <button class="action-btn delete-btn" onclick="deletePet(<?php echo $pet['id']; ?>)">Delete</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($pets)): ?>
                <div class="add-pet-section">
                    <a href="pet.php" class="action-btn add-pet-btn">Add Another Pet</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Medical Documents Modal -->
    <div id="medicalDocumentsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeMedicalDocumentsModal()">&times;</span>
            <h3>Medical Documents for <span id="modalPetName"></span></h3>
            <div id="documentsContainer">
                <!-- Documents will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Upload Document Modal -->
    <div id="uploadDocumentModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeUploadDocumentModal()">&times;</span>
            <h3>Upload Medical Document for <span id="uploadPetName"></span></h3>
            
            <div class="upload-form">
                <input type="hidden" id="uploadPetId">
                
                <div class="form-group">
                    <label for="documentDescription">Description (Optional)</label>
                    <textarea id="documentDescription" rows="3" placeholder="Brief description of the document"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Select PDF File *</label>
                    <div class="file-upload-area" id="documentUploadArea">
                        <div class="upload-placeholder">
                            <span class="upload-icon">📄</span>
                            <span class="upload-text">Click to upload PDF file</span>
                            <small class="upload-hint">Max file size: 10MB</small>
                        </div>
                        <input type="file" id="medicalDocument" accept=".pdf" style="display: none;">
                    </div>
                    <div id="documentFileName" class="file-name-display"></div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="submit-btn" onclick="uploadDocument()">Upload Document</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/notifications.js"></script>
    <script src="js/your_pet.js"></script>
</body>
</html>
