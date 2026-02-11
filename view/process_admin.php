<?php
session_start();
include 'db.php';

// Check if admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=please_login");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
  
    header("Location: index.php?error=not_admin");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Toggle User Active Status
    if (isset($_POST['action']) && $_POST['action'] == 'toggle_active' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Prevent disabling yourself
        if ($user_id == $_SESSION['user_id']) {
            header("Location: admin_dashboard.php?msg=cannot_ban_self");
            exit();
        }

        $stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        header("Location: admin_dashboard.php?msg=status_updated");
        exit();
    }

    // Delete User
    if (isset($_POST['action']) && $_POST['action'] == 'delete_user' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Prevent deleting yourself
        if ($user_id == $_SESSION['user_id']) {
             header("Location: admin_dashboard.php?msg=cannot_delete_self");
             exit();
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        header("Location: admin_dashboard.php?msg=user_deleted");
        exit();
    }
}

header("Location: admin_dashboard.php");
exit();
?>
