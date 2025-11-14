<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/db_connect.php'; // ✅ Add this to access database

// Redirect if already logged in
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    // Check if user has completed their profile
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!empty($user['name']) && !empty($user['email'])) {
        header("Location: ../html/index.php"); // ✅ Go to homepage if profile is complete
    } else {
        header("Location: ../html/dashboard.php"); // 🚧 Go to dashboard if incomplete
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FURRiendly | Log In / Sign Up</title>
    <link rel="stylesheet" href="../style.css" />
    <link rel="stylesheet" href="css/notifications.css" />
</head>
<body class="auth-body">
  <div class="auth-container">
    <h2>Welcome to FURRiendly!</h2>

    <?php if (isset($_GET['error'])): ?>
      <div class="error-msg"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php elseif (isset($_GET['success'])): ?>
      <div class="success-msg"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <h3>Log In</h3>
    <form action="../php/login.php" method="POST">
        <input type="text" name="username" placeholder="Username" required />
        <input type="password" name="password" placeholder="Password" required />
        <button type="submit" name="loginBtn">Log In</button>
    </form>

    <div class="auth-buttt">
      <a href="signup.php">Create an Account</a>
      <a href="index.php">← Back to Home</a>
    </div>
  </div>
    <script src="js/notifications.js"></script>
</body>
</html>
