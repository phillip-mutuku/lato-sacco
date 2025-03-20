<?php
require_once '../config/class.php';
require_once '../helpers/session.php';

if (isset($_GET['user_id'])) {
    $db = new db_class();
    $user_id = $_GET['user_id'];
    
    try {
        // First check if the user can be safely deleted
        $dependencies = $db->check_user_dependencies($user_id);
        
        if (!$dependencies['can_delete']) {
            // If user has dependencies, store the information in session
            $_SESSION['delete_user_id'] = $user_id;
            $_SESSION['user_dependencies'] = $dependencies;
            header("Location: ../models/user.php?show_reassign=1");
            exit();
        }
        
        // If no dependencies, proceed with deletion
        if ($db->delete_user($user_id)) {
            $_SESSION['success_msg'] = "User deleted successfully";
        } else {
            $_SESSION['error_msg'] = "Failed to delete user";
        }
        
    } catch (Exception $e) {
        $_SESSION['error_msg'] = $e->getMessage();
    }
    
    header("Location: ../models/user.php");
    exit();
}

// Handle reassignment and deletion
if (isset($_POST['reassign_and_delete'])) {
    $db = new db_class();
    $user_id = $_POST['user_id'];
    $new_officer_id = $_POST['new_officer_id'];
    
    try {
        // Start transaction
        $db->conn->begin_transaction();
        
        // Reassign groups
        if (!$db->reassign_groups($user_id, $new_officer_id)) {
            throw new Exception("Failed to reassign groups");
        }
        
        // Delete the user
        if (!$db->delete_user($user_id)) {
            throw new Exception("Failed to delete user");
        }
        
        $db->conn->commit();
        $_SESSION['success_msg'] = "User deleted and groups reassigned successfully";
        
    } catch (Exception $e) {
        $db->conn->rollback();
        $_SESSION['error_msg'] = $e->getMessage();
    }
    
    header("Location: ../models/user.php");
    exit();
}
?>