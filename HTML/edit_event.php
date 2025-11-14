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
$event_id = $_GET['event_id'] ?? null;

if (!$event_id) {
    header('Location: myhosting.php');
    exit();
}

// Fetch event details and verify ownership
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ? AND host_username = ? AND status = 'rejected'");
$stmt->bind_param("is", $event_id, $username);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    header('Location: myhosting.php');
    exit();
}

// Fetch user info for header
$user_stmt = $conn->prepare("SELECT name, profile_pic FROM users WHERE username = ?");
$user_stmt->bind_param("s", $username);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Decode services
$services = json_decode($event['services'], true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit & Resubmit Event - FURRiendly</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/edit_event.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/notifications.css">
    <style>
        .file-requirement {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            color: #856404;
        }
        
        .file-requirement h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        
        .current-files {
            background: #e7f5ff;
            border: 1px solid #a7e7ff;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .current-files h4 {
            margin: 0 0 10px 0;
            color: #0077b6;
        }
        
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #d1ecf1;
        }
        
        .file-item:last-child {
            border-bottom: none;
        }
    </style>
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

    <main class="add-event-main">
        <div class="back-container">
            <a href="myhosting.php" class="back-btn">
                <span class="back-arrow">←</span> Back to My Hosting
            </a>
        </div>

        <div class="add-event-container">
            <h1>Edit & Resubmit Event</h1>
            
            <?php if (!empty($event['rejection_reason'])): ?>
                <div class="disclaimer" style="background: #fff5f5; border-color: #ff6b6b;">
                    <h4 style="color: #dc3545; margin-top: 0;">📋 Rejection Reason</h4>
                    <p style="color: #856404; margin-bottom: 0;"><?php echo htmlspecialchars($event['rejection_reason']); ?></p>
                </div>
            <?php endif; ?>

            <div class="file-requirement">
                <h4>📁 File Re-upload Required</h4>
                <p>Since your event was rejected, you need to re-upload all required documents. Please review and update all files below.</p>
            </div>

            <form action="process_event.php?edit=<?php echo $event_id; ?>" method="POST" enctype="multipart/form-data" class="event-form">
                
                <!-- Event Information Section -->
                <section class="form-section">
                    <h2>Event Information</h2>
                    
                    <div class="form-group">
                        <label for="event_title">Event Title *</label>
                        <input type="text" id="event_title" name="event_title" value="<?php echo htmlspecialchars($event['event_title']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="event_date">Date *</label>
                            <input type="date" id="event_date" name="event_date" value="<?php echo $event['event_date']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="start_time">Start Time *</label>
                            <input type="time" id="start_time" name="start_time" value="<?php echo $event['start_time']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_time">End Time *</label>
                            <input type="time" id="end_time" name="end_time" value="<?php echo $event['end_time']; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location">Location *</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Services *</label>
                        <div class="services-container">
                            <div class="default-services">
                                <h4>Select Services:</h4>
                                <div class="service-buttons">
                                    <button type="button" class="service-btn" data-service="Grooming">Grooming</button>
                                    <button type="button" class="service-btn" data-service="Checkup">Checkup</button>
                                    <button type="button" class="service-btn" data-service="Dental">Dental Care</button>
                                    <button type="button" class="service-btn" data-service="Vaccination">Vaccination</button>
                                    <button type="button" class="service-btn" data-service="Feeding">Feeding</button>
                                    <button type="button" class="service-btn" data-service="Training">Training</button>
                                    <button type="button" class="service-btn" data-service="Microchipping">Microchipping</button>
                                </div>
                            </div>

                            <div class="custom-service-input">
                                <label for="custom_service">Other Service:</label>
                                <div class="service-input-group">
                                    <input type="text" id="custom_service" class="service-input" placeholder="Enter custom service">
                                    <button type="button" class="add-service-btn" onclick="addCustomService()">Add Service</button>
                                </div>
                            </div>

                            <div id="services-list" class="services-list"></div>
                            <input type="hidden" id="services_data" name="services" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                    </div>
                </section>

                <!-- Personal Information Section -->
                <section class="form-section">
                    <h2>Personal Information</h2>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($event['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="contact_number">Contact Number *</label>
                        <input type="tel" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($event['contact_number']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Position *</label>
                        <select id="position" name="position" required onchange="toggleIdUpload()">
                            <option value="Government Official" <?php echo $event['position'] === 'Government Official' ? 'selected' : ''; ?>>Government Official</option>
                            <option value="Veterinarian" <?php echo $event['position'] === 'Veterinarian' ? 'selected' : ''; ?>>Veterinarian</option>
                            <option value="Furr Parent" <?php echo $event['position'] === 'Furr Parent' ? 'selected' : ''; ?>>Furr Parent</option>
                        </select>
                    </div>

                    <!-- Current Files Display -->
                    <div class="current-files">
                        <h4>📎 Currently Uploaded Files</h4>
                        <p>These are your previously uploaded files. You must re-upload all required documents.</p>
                        <?php
                        $file_labels = [
                            'valid_id' => 'Valid ID',
                            'permit' => 'Permit or Authorization',
                            'veterinarians_list' => 'List of Veterinarians and Partners',
                            'safety_plan' => 'Safety and Cleanliness Plan',
                            'id_upload' => 'Additional ID Upload'
                        ];
                        
                        foreach ($file_labels as $key => $label):
                            if (!empty($event[$key])):
                        ?>
                            <div class="file-item">
                                <span><?php echo $label; ?>:</span>
                                <a href="../uploads/events/<?php echo htmlspecialchars($event[$key]); ?>" target="_blank" style="color: #0077b6; text-decoration: none;">
                                    📄 View File
                                </a>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>

                    <div id="id-upload-section" class="upload-section" style="display: <?php echo ($event['position'] === 'Government Official' || $event['position'] === 'Veterinarian') ? 'block' : 'none'; ?>;">
                        <div class="form-group">
                            <label>Upload your ID *</label>
                            <div class="file-upload-btn" onclick="document.getElementById('id_upload').click()">
                                <span class="upload-icon">📄</span>
                                <span class="upload-text">Choose ID File</span>
                            </div>
                            <input type="file" id="id_upload" name="id_upload" accept=".jpg,.jpeg,.png,.pdf" style="display: none;" onchange="updateFileName('id_upload', 'id_upload_name')">
                            <span id="id_upload_name" class="file-name">No file chosen</span>
                        </div>
                    </div>

                    <div class="upload-section">
                        <div class="form-group">
                            <label>Upload Valid ID *</label>
                            <div class="file-upload-btn" onclick="document.getElementById('valid_id').click()">
                                <span class="upload-icon">🆔</span>
                                <span class="upload-text">Choose Valid ID</span>
                            </div>
                            <input type="file" id="valid_id" name="valid_id" accept=".jpg,.jpeg,.png,.pdf" required style="display: none;" onchange="updateFileName('valid_id', 'valid_id_name')">
                            <span id="valid_id_name" class="file-name">No file chosen</span>
                        </div>

                        <div class="form-group">
                            <label>Permit or Authorization (From local government) *</label>
                            <div class="file-upload-btn" onclick="document.getElementById('permit').click()">
                                <span class="upload-icon">📋</span>
                                <span class="upload-text">Choose Permit File</span>
                            </div>
                            <input type="file" id="permit" name="permit" accept=".jpg,.jpeg,.png,.pdf" required style="display: none;" onchange="updateFileName('permit', 'permit_name')">
                            <span id="permit_name" class="file-name">No file chosen</span>
                        </div>

                        <div class="form-group">
                            <label>List of Veterinarians and Partners (PDF) *</label>
                            <div class="file-upload-btn" onclick="document.getElementById('veterinarians_list').click()">
                                <span class="upload-icon">👥</span>
                                <span class="upload-text">Choose Veterinarians List</span>
                            </div>
                            <input type="file" id="veterinarians_list" name="veterinarians_list" accept=".pdf" required style="display: none;" onchange="updateFileName('veterinarians_list', 'veterinarians_list_name')">
                            <span id="veterinarians_list_name" class="file-name">No file chosen</span>
                        </div>

                        <div class="form-group">
                            <label>Safety and Cleanliness Plan *</label>
                            <div class="file-upload-btn" onclick="document.getElementById('safety_plan').click()">
                                <span class="upload-icon">🧼</span>
                                <span class="upload-text">Choose Safety Plan</span>
                            </div>
                            <input type="file" id="safety_plan" name="safety_plan" accept=".jpg,.jpeg,.png,.pdf" required style="display: none;" onchange="updateFileName('safety_plan', 'safety_plan_name')">
                            <span id="safety_plan_name" class="file-name">No file chosen</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="appeal_message">Changes Made (Optional)</label>
                        <textarea id="appeal_message" name="appeal_message" rows="3" placeholder="Briefly describe the changes you made to address the rejection reason..."></textarea>
                    </div>
                </section>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">Resubmit Event for Approval</button>
                </div>
            </form>
        </div>
    </main>

    <script src="js/notifications.js"></script>
    <script src="js/add_event.js"></script>
    <script>
        // Initialize services from PHP
        let services = <?php echo json_encode($services); ?>;

        function updateServicesList() {
            const servicesList = document.getElementById('services-list');
            servicesList.innerHTML = '';

            if (services.length === 0) {
                servicesList.innerHTML = '<div class="no-services">No services selected yet. Select services above or add custom service.</div>';
            } else {
                services.forEach((service, index) => {
                    const serviceTag = document.createElement('div');
                    serviceTag.className = 'service-tag';
                    serviceTag.innerHTML = `
                        ${service}
                        <button type="button" class="remove-service" onclick="removeServiceByIndex(${index})">×</button>
                    `;
                    servicesList.appendChild(serviceTag);
                });
            }

            // Update hidden input for services
            let existingInput = document.querySelector('input[name="services"]');
            if (existingInput) {
                existingInput.remove();
            }

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'services';
            hiddenInput.value = JSON.stringify(services);
            document.querySelector('.services-container').appendChild(hiddenInput);
        }

        function removeService(service) {
            const index = services.indexOf(service);
            if (index > -1) {
                services.splice(index, 1);
                updateServicesList();
            }
        }

        function removeServiceByIndex(index) {
            services.splice(index, 1);
            updateServicesList();
        }

        function addCustomService() {
            const serviceInput = document.getElementById('custom_service');
            const service = serviceInput.value.trim();

            if (service && !services.includes(service)) {
                services.push(service);
                updateServicesList();
                serviceInput.value = '';
            } else if (!service) {
                alert('Please enter a custom service.');
            }
        }

        function toggleIdUpload() {
            const position = document.getElementById('position').value;
            const idUploadSection = document.getElementById('id-upload-section');
            const idUploadInput = document.getElementById('id_upload');

            if (position === 'Government Official' || position === 'Veterinarian') {
                idUploadSection.style.display = 'block';
                idUploadInput.required = true;
            } else {
                idUploadSection.style.display = 'none';
                idUploadInput.required = false;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateServicesList();
            toggleIdUpload();
        });
    </script>
</body>
</html>
