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

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$res = mysqli_query($conn, "SELECT * FROM articles WHERE id=$id");
$article = mysqli_fetch_assoc($res);

if (!$article) die("Article not found!");

// Protection
// Protection
if (!isset($_SESSION['username']) || ($_SESSION['username'] != $article['author'] && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'))) {
    die("You are not allowed to edit this article!");
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    
    $stmt = $conn->prepare("UPDATE articles SET title=?, content=? WHERE id=?");
    $stmt->bind_param("ssi", $title, $content, $id);
    $stmt->execute();

    header("Location: index.php");
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Article</title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="glass-card p-4" style="width: 100%; max-width: 500px;">
        <h2 class="text-center mb-4">✏️ Edit Article</h2>
        
        <form method="POST">
            <div class="mb-3">
                <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($article['title']); ?>" required placeholder="Article Title">
            </div>
            <div class="mb-3">
                <textarea name="content" class="form-control" rows="6" required placeholder="Article Content..."><?php echo htmlspecialchars($article['content']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-warning w-100">Save Changes</button>
            <a href="index.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
        </form>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
