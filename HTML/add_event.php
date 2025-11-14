<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/db_connect.php';

$isLoggedIn = isset($_SESSION['username']);
$user = null;

if ($isLoggedIn) {
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT name, profile_pic FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - FURRiendly</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/add_event.css">
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
                        <a href="events.php?filter=joined">Joined Events</a>
                    </div>
                </li>
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
                <?php else: ?>
                    <a href="auth.php" class="btn auth">Log-in/Sign-up</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="add-event-main">
        <div class="back-container">
            <a href="events.php" class="back-btn">
                <span class="back-arrow">←</span> Back
            </a>
        </div>

        <div class="add-event-container">
            <h1>Host an Event</h1>
            
            <form action="process_event.php" method="POST" enctype="multipart/form-data" class="event-form" id="eventForm">
                
                <!-- Event Information Section -->
                <section class="form-section">
                    <h2>What event will you be hosting?</h2>
                    
                    <div class="form-group">
                        <label for="event_title">Event Title *</label>
                        <input type="text" id="event_title" name="event_title" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="event_date">Date *</label>
                            <input type="date" id="event_date" name="event_date" required>
                            <small class="error-message" id="date-error"></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="start_time">Start Time *</label>
                            <input type="time" id="start_time" name="start_time" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_time">End Time *</label>
                            <input type="time" id="end_time" name="end_time" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location">Location *</label>
                        <input type="text" id="location" name="location" required>
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
                        <textarea id="description" name="description" rows="4" required></textarea>
                    </div>
                </section>

                <!-- Personal Information Section -->
                <section class="form-section">
                    <h2>Personal Information</h2>
                    
                    <div class="disclaimer">
                        <p>We value your privacy and are committed to protecting your personal information. Any data collected on this website will only be used for its intended purpose and will not be shared with third parties.</p>
                    </div>

                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>

                    <div class="form-group">
                        <label for="contact_number">Contact Number *</label>
                        <div class="phone-input-group">
                            <select id="country_code" name="country_code" class="country-code-select" onchange="formatPhoneNumber()">
                                <option value="+1">US +1</option>
                                <option value="+44">UK +44</option>
                                <option value="+61">AU +61</option>
                                <option value="+81">JP +81</option>
                                <option value="+82">KR +82</option>
                                <option value="+65">SG +65</option>
                                <option value="+60">MY +60</option>
                                <option value="+63" selected>PH +63</option>
                                <option value="+66">TH +66</option>
                                <option value="+84">VN +84</option>
                                <option value="+62">ID +62</option>
                                <option value="+91">IN +91</option>
                                <option value="+971">AE +971</option>
                                <option value="+966">SA +966</option>
                            </select>
                            <input type="tel" id="contact_number" name="contact_number" pattern="[0-9\s\-\(\)]+" required oninput="formatPhoneNumber()">
                        </div>
                        <small id="phone-format">Format: +63 XXX XXX XXXX</small>
                    </div>

                    <div class="form-group">
                        <label for="position">Position *</label>
                        <select id="position" name="position" required onchange="toggleIdUpload()">
                            <option value="">Select Position</option>
                            <option value="Government Official">Government Official</option>
                            <option value="Veterinarian">Veterinarian</option>
                            <option value="Furr Parent">Furr Parent</option>
                        </select>
                    </div>

                    <div id="id-upload-section" class="upload-section" style="display: none;">
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
                </section>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">Add Event</button>
                </div>
            </form>
        </div>
    </main>

    <script src="js/notifications.js"></script>
    <script src="js/add_event.js"></script>

    <?php if (isset($_SESSION['error'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('<?php echo addslashes($_SESSION['error']); ?>', 'error');
        });
    </script>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
</body>
</html>
