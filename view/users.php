<?php
session_start();
include 'db.php';

// Check login status
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Fetch users based on role
if ($is_admin) {
    // Admin sees all users
    $users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
    
    // Stats for Admin
    $user_count = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
    $article_count = $conn->query("SELECT COUNT(*) as c FROM articles")->fetch_assoc()['c'];
    $comment_count = $conn->query("SELECT COUNT(*) as c FROM comments")->fetch_assoc()['c'];
} else {
    // Normal users see only active users
    $users = $conn->query("SELECT * FROM users WHERE is_active = 1 ORDER BY created_at DESC");
}
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
                    <a href="register.php" class="btn btn-primary btn-sm ms-2 glow-on-hover">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    
    <?php if ($is_admin): ?>
        <h2 class="text-center mb-4 text-warning">Admin Dashboard</h2>
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="glass-card text-center p-4">
                    <h3>Users</h3>
                    <p class="display-4 fw-bold text-info"><?php echo $user_count; ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4">
                    <h3>Articles</h3>
                    <p class="display-4 fw-bold text-warning"><?php echo $article_count; ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4">
                    <h3>Comments</h3>
                    <p class="display-4 fw-bold text-success"><?php echo $comment_count; ?></p>
                </div>
            </div>
        </div>
        
        <h3 class="mb-4 text-white">Manage Users</h3>
        <div class="glass-card p-4 mb-5">
             <div class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-2" style="width: 30px; height: 30px; font-size: 0.8rem; display:flex; justify-content:center; align-items:center;">
                                        <?php echo mb_substr($user['username'], 0, 1, 'UTF-8'); ?>
                                    </div>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="profile.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-info">View</a>
                                    
                                    <?php if ($user['role'] !== 'admin'): ?>
                                        <form action="process_admin.php" method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button class="btn btn-sm btn-outline-warning">
                                                <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        <form action="process_admin.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Admin</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
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
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
