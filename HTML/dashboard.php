<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/db_connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: auth.php");
    exit();
}

$username = $_SESSION['username'];
$user = null;
$isAdmin = false;

// Fetch user info
$stmt = $conn->prepare("SELECT name, email, profile_pic, is_admin FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user is admin
if (isset($user['is_admin']) && $user['is_admin'] == 1) {
    $isAdmin = true;
}

// Enhanced file upload helper with validation
function upload_profile_pic($file_field) {
    if (empty($_FILES[$file_field]['name']) || $_FILES[$file_field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Use absolute path for uploads directory
    $target_dir = $_SERVER['DOCUMENT_ROOT'] . '/furriendly-main/uploads/';

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

    // Validate file size (max 2MB for profile pics)
    if ($_FILES[$file_field]['size'] > 2 * 1024 * 1024) {
        return null;
    }

    // Generate unique filename
    $ext = strtolower(pathinfo($_FILES[$file_field]['name'], PATHINFO_EXTENSION));
    $filename = 'profile_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $target_file = $target_dir . $filename;

    if (move_uploaded_file($_FILES[$file_field]['tmp_name'], $target_file)) {
        return '/furriendly-main/uploads/' . $filename;
    }
    return null;
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    // Handle profile picture upload
    $profile_pic_path = upload_profile_pic('profile_pic');
    
    // Check if phone column exists in users table
    $check_phone = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
    $has_phone = $check_phone && $check_phone->num_rows > 0;
    
    if ($has_phone) {
        $phone = $_POST['phone'] ?? '';
        
        if ($profile_pic_path) {
            // Update with profile picture and phone
            $sql = "UPDATE users SET name=?, email=?, phone=?, bio=?, profile_pic=? WHERE username=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssss', $name, $email, $phone, $bio, $profile_pic_path, $username);
        } else {
            // Update without profile picture but with phone
            $sql = "UPDATE users SET name=?, email=?, phone=?, bio=? WHERE username=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssss', $name, $email, $phone, $bio, $username);
        }
    } else {
        // Phone column doesn't exist
        if ($profile_pic_path) {
            // Update with profile picture (no phone)
            $sql = "UPDATE users SET name=?, email=?, bio=?, profile_pic=? WHERE username=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssss', $name, $email, $bio, $profile_pic_path, $username);
        } else {
            // Update without profile picture (no phone)
            $sql = "UPDATE users SET name=?, email=?, bio=? WHERE username=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssss', $name, $email, $bio, $username);
        }
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating profile: " . $conn->error;
    }
}

// Re-fetch updated user info
$stmt = $conn->prepare("SELECT name, email, profile_pic, bio, is_admin FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FURRiendly</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/notifications.css">
    <style>
        .profile-pic-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0077b6;
            margin-bottom: 10px;
        }
        .upload-btn-label {
            display: inline-block;
            background: #0077b6;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px 0;
        }
        .upload-btn-label:hover {
            background: #0056b3;
        }
        .file-help {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
        }
        input[type="file"] {
            display: none;
        }
        .dashboard-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .btn-primary {
            background: #0077b6;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
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

                <!-- Show only if user is admin -->
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

    <main class="dashboard-body">
        <div class="dashboard-container">
            <h2>Edit Profile</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-msg"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-msg"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <!-- Profile Picture Upload -->
                <div class="profile-pic-container">
                    <img src="<?php echo htmlspecialchars(!empty($user['profile_pic']) ? $user['profile_pic'] : '../images/default-avatar.png'); ?>" id="profilePicPreview" class="profile-pic" alt="Profile Picture Preview">
                    <label for="profile_pic" class="upload-btn-label">Change Profile Picture</label>
                    <input type="file" name="profile_pic" id="profile_pic" accept="image/*" onchange="previewProfileImage(event)">
                    <p class="file-help">Max size: 2MB. Allowed: JPG, PNG, GIF, WebP</p>
                </div>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea name="bio" id="bio" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
            </form>
        </div>
    </main>

    <script src="js/notifications.js"></script>
    <script>
        function previewProfileImage(event) {
            const reader = new FileReader();
            reader.onload = function(){
                const output = document.getElementById('profilePicPreview');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>
