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
    $post_id = (int)$_POST['post_id'];
    $content = trim($_POST['content']);
    $event_id = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;

    if (empty($content)) {
        $_SESSION['error'] = 'Post content cannot be empty.';
        header("Location: ../html/index.php");
        exit();
    }

    // Check if user owns the post
    $stmt = $conn->prepare("SELECT username FROM posts WHERE id = ?");
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();

    if (!$post || $post['username'] !== $username) {
        $_SESSION['error'] = 'You can only edit your own posts.';
        header("Location: ../html/index.php");
        exit();
    }

    // Handle image upload (optional update)
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

    // Update post
    if ($image_path) {
        $stmt = $conn->prepare("UPDATE posts SET content = ?, image_path = ?, event_id = ? WHERE id = ?");
        $stmt->bind_param('ssii', $content, $image_path, $event_id, $post_id);
    } else {
        $stmt = $conn->prepare("UPDATE posts SET content = ?, event_id = ? WHERE id = ?");
        $stmt->bind_param('sii', $content, $event_id, $post_id);
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Post updated successfully!';
    } else {
        $_SESSION['error'] = 'Failed to update post.';
    }

    header("Location: ../html/index.php");
    exit();
}
?>
