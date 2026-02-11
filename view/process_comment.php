<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $article_id = (int)$_POST['article_id'];
    $user_id = $_SESSION['user_id'];

    $redirect = $_POST['redirect'] ?? '';
    $redirect_to = ($redirect === 'index.php') ? 'index.php' : ('view_article.php?id=' . $article_id);

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

    $comment = trim($_POST['comment']);
    $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (article_id, user_id, parent_id, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $article_id, $user_id, $parent_id, $comment);
        
        if ($stmt->execute()) {
            header("Location: " . $redirect_to);
            exit();
        } else {
            echo "Error adding comment.";
        }
    } else {
        header("Location: " . $redirect_to . "&error=empty");
    }
} else {
    header("Location: index.php");
}
?>
