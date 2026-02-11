<?php
session_start();
include 'db.php';

// Check login status (optional, but good for tracking)
$is_logged_in = isset($_SESSION['user_id']);

// Fetch all active users
$users = $conn->query("SELECT * FROM users WHERE is_active = 1 ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>
    <!-- Bootstrap 5 LTR CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="bi bi-house-door-fill"></i> </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto align-items-center">
                <?php if ($is_logged_in): ?>
                    <span class="nav-link text-info">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                    <a href="add_article.php" class="nav-link">Create Article</a>
                    <a href="users.php" class="nav-link active">Users</a>
                    <a href="logout.php" class="btn btn-danger btn-sm ms-3 glow-on-hover">Logout</a>
                <?php else: ?>
                    <a href="users.php" class="nav-link active">Users</a>
                    <a href="login.php" class="btn btn-info btn-sm ms-3 glow-on-hover">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <h2 class="text-center mb-5 text-white">Our Community</h2>
    
    <div class="row">
        <?php while($user = $users->fetch_assoc()): ?>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="glass-card text-center p-4 h-100">
                    <div class="avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?php echo mb_substr($user['username'], 0, 1, 'UTF-8'); ?>
                    </div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($user['username']); ?></h4>
                    <p class="text-muted small mb-3">Joined in <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                    <a href="profile.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-info btn-sm w-100">View Profile</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
