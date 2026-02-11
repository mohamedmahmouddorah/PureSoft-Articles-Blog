<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
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

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

// Fetch Comment
$stmt = $conn->prepare("SELECT * FROM comments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$comment_data = $result->fetch_assoc();

if (!$comment_data) {
    die("Comment not found.");
}

$can_manage = ($user_id == (int)$comment_data['user_id']) || ($user_role === 'admin');
if (!$can_manage) {
    die("Not authorized to edit this comment.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_comment = trim($_POST['comment']);
    
    if (!empty($new_comment)) {
        $update_stmt = $conn->prepare("UPDATE comments SET comment = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_comment, $id);
        $update_stmt->execute();
        
        header("Location: view_article.php?id=" . $comment_data['article_id']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> edit comment</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="glass-card p-4" style="width: 100%; max-width: 500px;">
        <h2 class="text-center mb-4">edit comment</h2>
        <form method="POST">
            <div class="mb-3">
                <textarea name="comment" class="form-control" rows="5" required><?php echo htmlspecialchars($comment_data['comment']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-warning w-100">save changes </button>
            <a href="view_article.php?id=<?php echo $comment_data['article_id']; ?>" class="btn btn-secondary w-100 mt-2">cancel</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
