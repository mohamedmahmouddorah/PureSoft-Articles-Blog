<?php
session_start();
include 'db.php';

if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'] ?? 'user';

    $check_stmt = $conn->prepare("SELECT is_active FROM users WHERE id = ? LIMIT 1");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $active_row = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if (!$active_row || (int)($active_row['is_active'] ?? 1) !== 1) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    if ($user_role === 'admin') {
        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->bind_param("i", $id);
    } else {
        // Ensure the user owns the comment
        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
    }
    
    if ($stmt->execute()) {
        // Go back to the previous page (referer) or index
        if (isset($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            header("Location: index.php");
        }
    } else {
        die("Error deleting comment or permission denied.");
    }
} else {
    header("Location: index.php");
}
?>
