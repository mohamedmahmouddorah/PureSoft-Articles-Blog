<?php
session_start();
include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = (int)$_GET['id'];
$current_user_id = $_SESSION['user_id'] ?? 0;
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Fetch User Info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) die("User not found!");

// Fetch User's Articles
$articles_stmt = $conn->prepare("SELECT * FROM articles WHERE author = ? ORDER BY created_at DESC");
$articles_stmt->bind_param("s", $user['username']); // Assuming author column stores username. If it stores ID, change this.
$articles_stmt->execute();
$articles = $articles_stmt->get_result();
$article_count = $articles->num_rows;

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile: <?php echo htmlspecialchars($user['username']); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="bi bi-house-door-fill"></i> </a>
        <div class="navbar-nav ms-auto">
            <a href="users.php" class="nav-link">Users</a>
            <a href="index.php" class="nav-link">Home</a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    
    <div class="row">
        <!-- Sidebar / User Info -->
        <div class="col-lg-4 mb-4">
            <div class="glass-card text-center p-4">
                 <div class="avatar mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem;">
                    <?php echo mb_substr($user['username'], 0, 1, 'UTF-8'); ?>
                </div>
                <h3 class="mb-1"><?php echo htmlspecialchars($user['username']); ?></h3>
                
                <div class="badge bg-secondary mb-3">
                    <?php echo $user['role'] === 'admin' ? 'Admin' : 'Member'; ?>
                </div>

                <div class="d-flex justify-content-center gap-3 text-start mt-3">
                    <div class="text-center">
                        <h5 class="mb-0 text-info"><?php echo $article_count; ?></h5>
                        <small class="text-muted">Articles</small>
                    </div>
                     <!-- We could add comment count if we query it -->
                </div>

                <hr class="border-secondary my-4">
                
                <div class="text-end text-muted small">
                    <p class="mb-2"><i class="bi bi-calendar-check me-2"></i> Joined: <?php echo date('Y/m/d', strtotime($user['created_at'])); ?></p>
                    <p class="mb-2"><i class="bi bi-envelope me-2"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
        </div>

        <!-- Main Content / Articles -->
        <div class="col-lg-8">
            <h3 class="mb-4 text-white"><i class="bi bi-collection-fill text-warning me-2"></i> Articles by <?php echo htmlspecialchars($user['username']); ?></h3>
            
            <?php if ($article_count > 0): ?>
                <?php while($row = $articles->fetch_assoc()): ?>
                    <div class="glass-card p-4 mb-3">
                        <h4 class="h5">
                            <a href="view_article.php?id=<?php echo $row['id']; ?>" class="text-white text-decoration-none">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </a>
                        </h4>
                        <div class="text-muted small mb-2">
                             <i class="bi bi-calendar-event"></i> <?php echo date('Y/m/d', strtotime($row['created_at'])); ?>
                        </div>
                        <p class="text-white-50 small mb-3">
                             <?php echo mb_substr(strip_tags($row['content']), 0, 120) . '...'; ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="view_article.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info">Read More</a>
                            
                            <?php if ($is_admin || ($current_user_id == $user_id)): ?>
                                <div>
                                    <a href="edit_article.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning ms-1"><i class="bi bi-pencil"></i></a>
                                    <a href="delete_article.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="glass-card text-center p-5">
                    <i class="bi bi-journal-x fs-1 text-muted mb-3 d-block"></i>
                    <p class="text-muted">This user hasn't posted any articles yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
