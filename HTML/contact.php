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
    <title>FURRiendly | Contact</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/notifications.css">
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

    <main class="content">
        <h2>Contact Us</h2>
        <p>If you have questions or feedback, please use the form below.</p>

        <form method="POST" action="contact.php" class="contact-form">
            <label for="name">Name</label>
            <input id="name" name="name" type="text" required>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>

            <label for="message">Message</label>
            <textarea id="message" name="message" rows="4" required></textarea>

            <button type="submit" name="send_message">Send Message</button>
        </form>

        <?php if (isset($_POST['send_message'])): ?>
            <?php
                // Basic form processing - simple email or DB storage can be added later
                $name = $_POST['name'];
                $email = $_POST['email'];
                $message = $_POST['message'];
            ?>
            <p class="success-msg">Thank you, <?php echo htmlspecialchars($name); ?>. Your message has been received.</p>
        <?php endif; ?>
    </main>
    <script src="js/notifications.js"></script>
</body>
</html>
