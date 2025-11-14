<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/db_connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../html/auth.php?error=Please log in first.");
    exit();
}

$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    $event_id = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;

    if (empty($content)) {
        $_SESSION['error'] = 'Post content cannot be empty.';
        header("Location: ../html/index.php");
        exit();
    }

    // Handle image upload
    $image_path = null;
    if (!empty($_FILES['post_image']['name'])) {
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . '/furriendly-main/uploads/posts/';
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['post_image']['type'];
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error'] = 'Invalid image type. Only JPG, PNG, GIF, and WebP are allowed.';
            header("Location: ../html/index.php");
            exit();
        }

        if ($_FILES['post_image']['size'] > 5 * 1024 * 1024) {
            $_SESSION['error'] = 'Image size too large. Maximum 5MB allowed.';
            header("Location: ../html/index.php");
            exit();
        }

        $ext = strtolower(pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION));
        $filename = 'post_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_file)) {
            $image_path = '/furriendly-main/uploads/posts/' . $filename;
        } else {
            $_SESSION['error'] = 'Failed to upload image.';
            header("Location: ../html/index.php");
            exit();
        }
    }

    // Insert post
    $stmt = $conn->prepare("INSERT INTO posts (username, content, image_path, event_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('sssi', $username, $content, $image_path, $event_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Post created successfully!';
    } else {
        $_SESSION['error'] = 'Failed to create post.';
    }

    header("Location: ../html/index.php");
    exit();
}
?>
