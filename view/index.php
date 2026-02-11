<?php
session_start();
include 'db.php'; 

if (isset($_SESSION['user_id'])) {
    $check_stmt = $conn->prepare("SELECT is_active FROM users WHERE id = ? LIMIT 1");
    $check_stmt->bind_param("i", $_SESSION['user_id']);
    $check_stmt->execute();
    $active_row = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if (!$active_row || (int)($active_row['is_active'] ?? 1) !== 1) {
        session_destroy();
    }
}

// Check login status
$is_logged_in = isset($_SESSION['user_id']);
$current_user = $_SESSION['username'] ?? '';

// Fetch articles
$sql = "SELECT * FROM articles ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error fetching articles: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Home</title>
    <!-- Bootstrap 5 CSS (LTR) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand logo" href="index.php"><i class="bi bi-house-door-fill"></i></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto align-items-center">
                <?php if ($is_logged_in): ?>
                   
                    <span class="nav-link text-info">Welcome, <a href="profile.php?id=<?php echo $_SESSION['user_id']; ?>" class="text-info text-decoration-none fw-bold"><?php echo htmlspecialchars($current_user); ?></a></span>
                    <a href="add_article.php" class="nav-link">Create Article</a>
                    <a href="users.php" class="nav-link">Users</a>
                     <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin_dashboard.php" class="btn btn-warning btn-sm me-2 fw-bold">Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-danger btn-sm ms-3 glow-on-hover">Logout</a>
                <?php else: ?>
                    <a href="users.php" class="nav-link">Users</a>
                    <a href="login.php" class="btn btn-info btn-sm ms-3 glow-on-hover">Login</a>
                    <a href="register.php" class="btn btn-primary btn-sm ms-2 glow-on-hover">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<div style="height: 80px;"></div> <!-- Spacer for fixed navbar -->

<div class="container">
    <h1 class="mb-4">Latest Articles</h1>
    
    <div class="row">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-12">
                    <div class="glass-card article-card p-4 mb-4">
                        <h2 class="h3 mb-3">
                            <a href="view_article.php?id=<?php echo $row['id']; ?>" class="text-info text-decoration-none">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </a>
                        </h2>
                        <div class="meta text-muted mb-3 pb-2 border-bottom border-secondary">
                            <i class="bi bi-person-fill"></i> 
                            <span class="text-white"><?php echo htmlspecialchars($row['author']); ?></span>
                            <span class="mx-2">|</span> 
                            <i class="bi bi-calendar-event"></i> <?php echo date('Y/m/d', strtotime($row['created_at'])); ?>
                        </div>
                        <p class="excerpt" style="color: #eee; line-height: 1.6;">
                            <?php echo substr(htmlspecialchars($row['content']), 0, 150) . '...'; ?>
                        </p>
                        <div class="text-end">
                             <a href="view_article.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info">Read More <i class="bi bi-arrow-right"></i></a>
                        </div>


                        <?php if ($is_logged_in): ?>
                            <div class="mt-4 pt-3 border-top border-secondary">
                                <form action="process_comment.php" method="POST" class="mb-3">
                                    <input type="hidden" name="article_id" value="<?php echo (int)$row['id']; ?>">
                                    <input type="hidden" name="redirect" value="index.php">
                                    <div class="input-group">
                                        <textarea name="comment" class="form-control" rows="1" placeholder="Write a comment..." required></textarea>
                                        <button type="submit" class="btn btn-primary">Post</button>
                                    </div>
                                </form>

                                <?php
                                    $com_stmt = $conn->prepare("
                                        SELECT c.*, u.username
                                        FROM comments c
                                        JOIN users u ON c.user_id = u.id
                                        WHERE c.article_id = ? AND c.parent_id IS NULL
                                        ORDER BY c.created_at DESC
                                        LIMIT 3
                                    ");
                                    $com_stmt->bind_param("i", $row['id']);
                                    $com_stmt->execute();
                                    $com_res = $com_stmt->get_result();
                                    $recent_comments = $com_res->fetch_all(MYSQLI_ASSOC);
                                    $com_stmt->close();
                                ?>

                                <?php if (!empty($recent_comments)): ?>
                                    <div class="feed-comments">
                                        <?php foreach ($recent_comments as $c): ?>
                                            <div class="feed-comment">
                                                <div class="feed-comment-header">
                                                    <span class="feed-comment-author"><?php echo htmlspecialchars($c['username']); ?></span>
                                                    <span class="feed-comment-date"><?php echo date('Y/m/d - H:i', strtotime($c['created_at'])); ?></span>
                                                </div>
                                                <div class="feed-comment-text"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-end mt-2">
                                        <a href="view_article.php?id=<?php echo (int)$row['id']; ?>" class="feed-comments-more">View all comments</a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted small">No comments yet.</div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($is_logged_in && $current_user == $row['author']): ?>
                            <div class="mt-3 pt-3 border-top border-secondary d-flex gap-2">
                                <a href="edit_article.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                                <a href="delete_article.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">🗑️ Delete</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="glass-card text-center p-5">
                    <p class="h4 mb-4 text-muted">No articles have been posted yet.</p>
                    <?php if ($is_logged_in): ?>
                        <a href="add_article.php" class="btn btn-success btn-lg">Create one now</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-info btn-lg">Login to post</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>