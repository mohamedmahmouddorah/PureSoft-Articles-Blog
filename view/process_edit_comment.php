<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $comment_id = (int)$_POST['comment_id'];
    $new_comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];
    
    // Get Article ID for redirection
    $stmt = $conn->prepare("SELECT article_id, user_id FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $article_id = $row['article_id'];
        $owner_id = $row['user_id'];

        // Check ownership
        if ($user_id !== $owner_id) {
             header("Location: view_article.php?id=" . $article_id . "&error=unauthorized");
             exit();
        }

        if (!empty($new_comment)) {
            $update_stmt = $conn->prepare("UPDATE comments SET comment = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_comment, $comment_id);
            
            if ($update_stmt->execute()) {
                header("Location: view_article.php?id=" . $article_id . "&success=comment_updated");
                exit();
            } else {
                 header("Location: view_article.php?id=" . $article_id . "&error=update_failed");
                 exit();
            }
        } else {
             header("Location: view_article.php?id=" . $article_id . "&error=empty_comment");
             exit();
        }

    } else {
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>
