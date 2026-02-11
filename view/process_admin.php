<?php
session_start();
include 'db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Toggle User Status
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Get current status
        $stmt = $conn->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $new_status = $row['is_active'] ? 0 : 1;
            
            $update_stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $new_status, $user_id);
            $update_stmt->execute();
        }
        
        header("Location: users.php");
        exit();
    }

    // Delete User
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Prevent deleting yourself
        if ($user_id === $_SESSION['user_id']) {
             header("Location: users.php?error=cannot_delete_self");
             exit();
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        header("Location: users.php");
        exit();
    }
}

header("Location: users.php");
exit();
?>
