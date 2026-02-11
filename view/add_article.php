<?php
session_start();
include 'db.php';

// Protect Page
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title   = $_POST['title'];
    $content = $_POST['content'];
    $author  = $_SESSION['username']; 

    $stmt = $conn->prepare("INSERT INTO articles (title, content, author) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $content, $author);
    
    if ($stmt->execute()) {
        header("Location: index.php"); 
    } else {
        $error = "An error occurred during posting! Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Article</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="glass-card p-4" style="width: 100%; max-width: 500px;">
        <h2 class="text-center mb-4">➕ New Article</h2>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto align-items-center">
                <a href="index.php" class="nav-link">Home</a>
                <a href="users.php" class="nav-link">Users</a>
                <a href="logout.php" class="btn btn-danger btn-sm ms-3 glow-on-hover">Logout</a>
            </div>
        </div>
        <form method="POST">
            <div class="mb-3">
                <input type="text" class="form-control" name="title" placeholder="Article Title" required>
            </div>
            <div class="mb-3">
                <textarea name="content" class="form-control" rows="6" placeholder="Write something amazing..." required></textarea>
            </div>
            <button type="submit" class="btn btn-success w-100">Publish Article</button>
            <a href="index.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>