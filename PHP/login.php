<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "db_connect.php";

if (isset($_POST['loginBtn'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        header("Location: ../html/auth.php?error=All fields are required");
        exit();
    }

    // 🔍 Check user by username
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // ✅ Verify password
        if (password_verify($password, $user['password'])) {
            
            // ✅ Set session values
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = isset($user['is_admin']) ? $user['is_admin'] : 0;

            // ✅ Redirect based on role
            if ($user['is_admin'] == 1) {
                // Admin → Admin Dashboard
                header("Location: ../html/index.php");
                exit();
            } else {
                // Normal user → Check if profile complete
                if (!empty($user['name']) && !empty($user['email'])) {
                    header("Location: ../html/index.php");
                } else {
                    header("Location: ../html/dashboard.php");
                }
                exit();
            }

        } else {
            header("Location: ../html/auth.php?error=Invalid password");
            exit();
        }

    } else {
        header("Location: ../html/auth.php?error=User not found");
        exit();
    }
}
?>