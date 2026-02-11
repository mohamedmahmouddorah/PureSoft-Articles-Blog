<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$check_stmt = $conn->prepare("SELECT is_active FROM users WHERE id = ? LIMIT 1");
$check_stmt->bind_param("i", $_SESSION['user_id']);
$check_stmt->execute();
$active_row = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if (!$active_row || (int)($active_row['is_active'] ?? 1) !== 1) {
    session_destroy();
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $res = mysqli_query($conn, "SELECT author FROM articles WHERE id=$id");
    $article = mysqli_fetch_assoc($res);
    
    if (!$article) die("Article not found!");

    if (isset($_SESSION['username']) && ($_SESSION['username'] == $article['author'] || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'))) {
        mysqli_query($conn, "DELETE FROM articles WHERE id=$id");
    }

}
header("Location: index.php");
exit();
?>