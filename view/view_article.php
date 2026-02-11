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

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$article_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'] ?? 0;
$current_user = $_SESSION['username'] ?? '';
$user_role = $_SESSION['role'] ?? 'user';

// Fetch Article
// Join with users table to get the author's ID (assuming author column in articles matches username in users)
// Note: If author name changed, this link might break. Ideally should use user_id in articles.
$stmt = $conn->prepare("SELECT a.*, u.id as author_id FROM articles a LEFT JOIN users u ON a.author = u.username WHERE a.id = ?");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();

if (!$article) die("المقال غير موجود!");

// Fetch Comments
$c_stmt = $conn->prepare("
    SELECT c.*, u.username 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.article_id = ? 
    ORDER BY c.created_at DESC
");
$c_stmt->bind_param("i", $article_id);
$c_stmt->execute();
$comments = $c_stmt->get_result();

$comments_by_id = [];
$children = [];
while ($row = $comments->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['user_id'] = (int)$row['user_id'];
    $row['parent_id'] = $row['parent_id'] !== null ? (int)$row['parent_id'] : null;
    $comments_by_id[$row['id']] = $row;

    $parent_key = $row['parent_id'] ? $row['parent_id'] : 0;
    if (!isset($children[$parent_key])) {
        $children[$parent_key] = [];
    }
    $children[$parent_key][] = $row['id'];
}

foreach ($children as $parent_key => $ids) {
    usort($children[$parent_key], function ($a, $b) use ($comments_by_id) {
        return strtotime($comments_by_id[$b]['created_at']) <=> strtotime($comments_by_id[$a]['created_at']);
    });
}

function render_comment_tree($comment_id, $comments_by_id, $children, $article_id, $user_id, $user_role, $level = 0) {
    $comment = $comments_by_id[$comment_id];
    $is_reply = $level > 0;

    $avatar = mb_substr($comment['username'], 0, 1, 'UTF-8');
    $author = htmlspecialchars($comment['username']);
    $date = date('Y/m/d - H:i', strtotime($comment['created_at']));
    $text = nl2br(htmlspecialchars($comment['comment']));

    // Simplified Structure for LTR
    echo '<div class="comment-card" id="comment-' . (int)$comment['id'] . '">';
    
    // Avatar
    echo '<div class="avatar">' . $avatar . '</div>';
    
    // Content Wrapper
    echo '<div class="comment-content-wrapper">';
    
    // Bubble
    echo '<div class="comment-bubble">';
    echo '<a href="profile.php?id=' . (int)$comment['user_id'] . '" class="comment-author">' . $author . '</a>';
    echo '<div class="comment-text">' . $text . '</div>';
    echo '</div>'; // End bubble

    // Actions & Meta
    echo '<div class="comment-actions">';
    // Date
    echo '<span class="comment-date" style="margin-right: 10px; font-size: 0.8rem; opacity: 0.7;">' . $date . '</span>';
    
    // Actions Row
    if ($user_id) {
        echo '<button type="button" onclick="toggleReply(' . (int)$comment['id'] . ')" class="reply-btn fw-bold text-white-50">Reply</button>';
        
        if ($user_id == (int)$comment['user_id']) {
             echo '<button type="button" onclick="toggleEdit(' . (int)$comment['id'] . ')" class="comment-action-link text-warning" style="background:none; border:none; padding:0; margin-left:10px;">Edit</button>';
        }
    }

    $can_manage = $user_id && (($user_id == (int)$comment['user_id']) || $user_role === 'admin');
    if ($can_manage) {
        // echo '<a href="edit_comment.php?id=' . (int)$comment['id'] . '" class="comment-action-link comment-action-edit text-info">Edit</a>'; // Removed old link
        echo '<a href="delete_comment.php?id=' . (int)$comment['id'] . '" class="comment-action-link comment-action-delete text-danger" onclick="return confirm(\'Delete comment?\')" style="margin-left:10px;">Delete</a>';
    }
    echo '</div>'; // End actions

    // Edit Form (Hidden by default)
    if ($user_id == (int)$comment['user_id']) {
        echo '<div id="edit-form-' . (int)$comment['id'] . '" class="reply-form">';
        echo '<form action="process_edit_comment.php" method="POST">';
        echo '<input type="hidden" name="comment_id" value="' . (int)$comment['id'] . '">';
        echo '<div class="input-group mt-2">';
        echo '<textarea name="comment" class="form-control" rows="1" required>' . htmlspecialchars($comment['comment']) . '</textarea>';
        echo '<button type="submit" class="btn btn-warning">Update</button>';
        echo '<button type="button" class="btn btn-secondary" onclick="toggleEdit(' . (int)$comment['id'] . ')">Cancel</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
    }

    // Reply Form
    if ($user_id) {
        echo '<div id="reply-form-' . (int)$comment['id'] . '" class="reply-form">';
        echo '<form action="process_comment.php" method="POST">';
        echo '<input type="hidden" name="article_id" value="' . (int)$article_id . '">';
        echo '<input type="hidden" name="parent_id" value="' . (int)$comment['id'] . '">';
        echo '<div class="input-group mt-2">';
        echo '<textarea name="comment" class="form-control" rows="1" placeholder="Write a reply..." required></textarea>';
        echo '<button type="submit" class="btn btn-primary">Reply</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
    }

    // Recursion for replies
    if (!empty($children[$comment_id])) {
        echo '<div class="reply-list">';
        foreach ($children[$comment_id] as $child_id) {
            render_comment_tree($child_id, $comments_by_id, $children, $article_id, $user_id, $user_role, $level + 1);
        }
        echo '</div>';
    }

    echo '</div>'; 
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <style>
        .article-meta { color: #aaa; font-size: 0.9em; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .reply-form { display: none; margin-top: 15px; animation: fadeIn 0.3s; }
    </style>
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
                <a href="index.php" class="nav-link">Home</a>
                <?php if ($user_id): ?>
                    <a href="add_article.php" class="nav-link">Create Article</a>
                    <a href="users.php" class="nav-link">Users</a>
                    <a href="logout.php" class="btn btn-danger btn-sm ms-3 glow-on-hover">Logout</a>
                <?php else: ?>
                    <a href="users.php" class="nav-link">Users</a>
                    <a href="login.php" class="btn btn-info btn-sm ms-3 glow-on-hover">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<div style="height: 80px;"></div>

<div class="container">
    <div class="glass-card p-4">
        <h1 class="mb-3"><?php echo htmlspecialchars($article['title']); ?></h1>
        <div class="article-meta text-center">
            <span class="text-white-50">By:</span> 
            <?php if ($article['author_id']): ?>
                <a href="profile.php?id=<?php echo $article['author_id']; ?>" class="text-info fw-bold text-decoration-none"><?php echo htmlspecialchars($article['author']); ?></a>
            <?php else: ?>
                <span class="text-info fw-bold"><?php echo htmlspecialchars($article['author']); ?></span>
            <?php endif; ?>
            <span class="mx-2">|</span>
            <span class="text-white-50"><?php echo date('d M Y, h:i A', strtotime($article['created_at'])); ?></span>
        </div>
        <div class="content mb-4" style="line-height: 1.8; white-space: pre-wrap; font-size: 1.1em; color: #f0f0f0;"><?php echo htmlspecialchars($article['content']); ?></div>

        <?php if ($current_user == $article['author']): ?>
            <div class="pt-3 border-top border-secondary d-flex justify-content-end gap-2">
                <a href="edit_article.php?id=<?php echo $article['id']; ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                <a href="delete_article.php?id=<?php echo $article['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete article?')">🗑️ Delete</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Comments Section -->
    <div class="glass-card p-4">
        <h3 class="mb-4 border-bottom border-secondary pb-2 d-inline-block">Comments</h3>

        <?php if ($user_id): ?>
            <form action="process_comment.php" method="POST" class="mb-5">
                <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
                <div class="mb-3 custom-input-wrapper">
                    <i class="bi bi-chat-text custom-input-icon" style="top: 25px; transform: none;"></i>
                    <textarea name="comment" class="form-control" rows="3" placeholder="Share your thoughts..." required style="padding-left: 45px;"></textarea>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4">Post Comment</button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-dark text-center" role="alert" style="background: rgba(255,255,255,0.1); border: none; color: #ccc;">
                You must <a href="login.php" class="alert-link text-info">login</a> to comment.
            </div>
        <?php endif; ?>

        <div class="comments-list">
            <?php $root_ids = $children[0] ?? []; ?>

             <?php if (empty($root_ids)): ?>
                <p class="text-center text-muted">No comments yet. Be the first to share!</p>
            <?php else: ?>
                <?php foreach($root_ids as $root_id): ?>
                    <?php render_comment_tree($root_id, $comments_by_id, $children, $article_id, $user_id, $user_role, 0); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleReply(id) {
        var form = document.getElementById('reply-form-' + id);
        if (form.style.display === "none" || form.style.display === "") {
            form.style.display = "block";
        } else {
            form.style.display = "none";
        }
    }

    function toggleEdit(id) {
        var form = document.getElementById('edit-form-' + id);
        if (form.style.display === "none" || form.style.display === "") {
            form.style.display = "block";
        } else {
            form.style.display = "none";
        }
    }
</script>
</body>
</html>
