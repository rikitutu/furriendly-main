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

    // Check if user owns the post
    $stmt = $conn->prepare("SELECT username, image_path FROM posts WHERE id = ?");
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();

    if (!$post || $post['username'] !== $username) {
        $_SESSION['error'] = 'You can only delete your own posts.';
        header("Location: ../html/index.php");
        exit();
    }

    // Delete image file if exists
    if (!empty($post['image_path'])) {
        $image_path = $_SERVER['DOCUMENT_ROOT'] . $post['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Delete post
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param('i', $post_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Post deleted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to delete post.';
    }

    header("Location: ../html/index.php");
    exit();
}
?>
