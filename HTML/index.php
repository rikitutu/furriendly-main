<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/db_connect.php';

$isLoggedIn = isset($_SESSION['username']);
$user = null;
$isAdmin = false;

if ($isLoggedIn) {
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT name, profile_pic, is_admin FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
}
require '../php/db_connect.php';

$isLoggedIn = isset($_SESSION['username']);
$user = null;
$isAdmin = false;

if ($isLoggedIn) {
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT name, profile_pic, is_admin FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Check if user is admin
    if (isset($user['is_admin']) && $user['is_admin'] == 1) {
        $isAdmin = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FURRiendly</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/community.css">
    <link rel="stylesheet" href="css/notifications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!--Navigation Bar-->
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

    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
            </div>
        </section>

        <!-- Community Board Section -->
        <section class="community-section">
            <div class="community-header">
                <h2>🐾 Community Board</h2>
                <p>Share your pet stories, connect with events, and engage with fellow pet enthusiasts!</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-msg" style="margin-bottom: 20px; padding: 10px; background: #fee; border: 1px solid #fcc; border-radius: 5px; color: #c33;">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-msg" style="margin-bottom: 20px; padding: 10px; background: #efe; border: 1px solid #cfc; border-radius: 5px; color: #363;">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if ($isLoggedIn): ?>
                <!-- Create Post Form -->
                <div class="create-post">
                    <h3>Share Something Special</h3>
                    <form id="createPostForm" class="post-form" enctype="multipart/form-data">
                        <textarea name="content" placeholder="What's on your mind? Share your pet's adventures, ask for advice, or just say hi!" required maxlength="1000"></textarea>

                        <div class="image-upload-section">
                            <label for="postImage">
                                <i class="fas fa-camera"></i> Add Photo (Optional)
                            </label>
                            <input type="file" name="post_image" id="postImage" accept="image/*">
                            <img id="imagePreview" class="image-preview" style="display: none;" alt="Preview">
                        </div>

                        <div class="event-link-section">
                            <select name="event_id">
                                <option value="">Select an event to link (optional)</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="submit-btn">Post</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="create-post">
                    <h3>Join the Conversation</h3>
                    <p>Please <a href="auth.php">log in</a> to share posts and connect with the community.</p>
                </div>
            <?php endif; ?>

            <!-- Posts Feed -->
            <div id="postsFeed" class="posts-feed">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    Loading posts...
                </div>
            </div>
        </section>

        <!-- Edit Post Modal -->
        <div id="editPostModal" class="edit-modal">
            <div class="edit-modal-content">
                <span class="close-edit-modal close">&times;</span>
                <h3>Edit Post</h3>
                <form id="editPostForm" class="post-form" enctype="multipart/form-data">
                    <input type="hidden" name="post_id">
                    <textarea name="content" placeholder="Update your post..." required maxlength="1000"></textarea>

                    <div class="image-upload-section">
                        <label for="editPostImage">
                            <i class="fas fa-camera"></i> Change Photo (Optional)
                        </label>
                        <input type="file" name="post_image" id="editPostImage" accept="image/*">
                        <img id="editImagePreview" class="image-preview" style="display: none;" alt="Preview">
                    </div>

                    <div class="event-link-section">
                        <select name="event_id">
                            <option value="">Select an event to link (optional)</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="submit-btn">Update Post</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Set current user for JavaScript
        window.currentUser = <?php echo $isLoggedIn ? json_encode($username) : 'null'; ?>;
    </script>
    <script src="js/notifications.js"></script>
    <script src="js/community.js"></script>
</body>
</html>
if (session_status() === PHP_SESSION_NONE) {
