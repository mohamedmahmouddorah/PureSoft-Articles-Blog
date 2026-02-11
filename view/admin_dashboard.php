<?php
session_start();
include 'db.php';

// Check if admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch Stats
$stats = [];
$stats['users'] = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$stats['articles'] = $conn->query("SELECT COUNT(*) FROM articles")->fetch_row()[0];
$stats['comments'] = $conn->query("SELECT COUNT(*) FROM comments")->fetch_row()[0];

// Fetch Users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

// Fetch Articles
$articles = $conn->query("SELECT a.*, u.username as author_name FROM articles a LEFT JOIN users u ON a.author = u.username ORDER BY a.created_at DESC");

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <style>
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--glass-border);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--highlight-color);
        }
        .table-glass {
            color: white;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 10px;
            overflow: hidden;
        }
        .table-glass th, .table-glass td {
            border-color: rgba(255, 255, 255, 0.1);
            padding: 12px 15px;
            vertical-align: middle;
        }
        .table-glass th {
            background: rgba(0, 0, 0, 0.2);
            font-weight: 600;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="bi bi-house-door-fill"></i> Admin Panel</a>
        <div class="navbar-nav ms-auto">
            <a href="index.php" class="btn btn-outline-light btn-sm">Back to Home</a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    
    <div class="row mb-5">
        <div class="col-md-4">
            <div class="stat-card">
                <i class="bi bi-people fs-1 mb-2 text-info"></i>
                <div class="stat-number"><?php echo $stats['users']; ?></div>
                <div class="text-muted">Users</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <i class="bi bi-file-text fs-1 mb-2 text-warning"></i>
                <div class="stat-number"><?php echo $stats['articles']; ?></div>
                <div class="text-muted">Articles</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <i class="bi bi-chat-dots fs-1 mb-2 text-success"></i>
                <div class="stat-number"><?php echo $stats['comments']; ?></div>
                <div class="text-muted">Comments</div>
            </div>
        </div>
    </div>

    <!-- Users Management -->
    <div class="glass-card p-4 mb-5">
        <h3 class="mb-4"><i class="bi bi-people-fill text-info"></i> User Management</h3>
        <div class="table-responsive">
            <table class="table table-glass mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
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
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                    <?php echo $user['role']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Banned'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                    <form action="process_admin.php" method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="toggle_active">
                                        <button type="submit" class="btn btn-sm btn-outline-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>">
                                            <?php echo $user['is_active'] ? '<i class="bi bi-slash-circle"></i> Ban' : '<i class="bi bi-check-circle"></i> Activate'; ?>
                                        </button>
                                    </form>
                                    
                                    <form action="process_admin.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user? This will delete all their articles and comments!');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="delete_user">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Articles Management -->
    <div class="glass-card p-4">
        <h3 class="mb-4"><i class="bi bi-file-text-fill text-warning"></i> Article Management</h3>
        <div class="table-responsive">
            <table class="table table-glass mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($art = $articles->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $art['id']; ?></td>
                            <td>
                                <a href="view_article.php?id=<?php echo $art['id']; ?>" class="text-white text-decoration-none">
                                    <?php echo htmlspecialchars($art['title']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($art['author']); ?></td>
                            <td><?php echo date('Y/m/d', strtotime($art['created_at'])); ?></td>
                            <td>
                                <a href="edit_article.php?id=<?php echo $art['id']; ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                <a href="delete_article.php?id=<?php echo $art['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this article?');"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
