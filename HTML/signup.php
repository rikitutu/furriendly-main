<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['username'])) {
  header("Location: ../html/dashboard.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FURRiendly | Sign Up</title>
    <link rel="stylesheet" href="../style.css" />
    <link rel="stylesheet" href="css/notifications.css" />
</head>
<body class="auth-body">
  <div class="auth-container">
    <h2>Create an Account</h2>

    <!-- Display messages -->
    <?php if (isset($_GET['error'])): ?>
      <div class="error-msg"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php elseif (isset($_GET['success'])): ?>
      <div class="success-msg"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <!-- Signup Form -->
    <form action="../php/register.php" method="POST">
        <input type="text" name="username" placeholder="Username" required />
        <input type="password" name="password" placeholder="Password" required />
        <input type="password" name="confirm_password" placeholder="Confirm Password" required />
        <button type="submit" name="signupBtn">Create Account</button>
    </form>

    <div class="auth-buttt">
      <a href="auth.php">← Back to Log In</a>
      <a href="index.php">Home</a>
    </div>
  </div>
    <script src="js/notifications.js"></script>
</body>
</html>
