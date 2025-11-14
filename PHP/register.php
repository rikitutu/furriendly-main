<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "db_connect.php";

if (isset($_POST['signupBtn'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        header("Location: ../html/signup.php?error=All fields are required");
        exit();
    }

    if ($password !== $confirm_password) {
        header("Location: ../html/signup.php?error=Passwords do not match");
        exit();
    }

    // Check if username already exists
    $checkUser = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $checkUser->bind_param("s", $username);
    $checkUser->execute();
    $result = $checkUser->get_result();

    if ($result->num_rows > 0) {
        header("Location: ../html/signup.php?error=Username already taken");
        exit();
    }

    // Hash password before saving
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into database
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hashedPassword);

    if ($stmt->execute()) {
        header("Location: ../html/auth.php?success=Account created! Please log in.");
        exit();
    } else {
        header("Location: ../html/signup.php?error=Something went wrong. Try again.");
        exit();
    }
}
?>
