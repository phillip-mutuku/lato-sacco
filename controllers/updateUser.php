<?php
require_once '../config/class.php';
require_once '../helpers/session.php';

if(isset($_POST['update'])){
    $db = new db_class();
    
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $role = $_POST['role'];
    
    // Attempt to update the user
    if($db->update_user($user_id, $username, $password, $firstname, $lastname, $role)){
        $_SESSION['success_msg'] = "User has been updated successfully";
    } else {
        $_SESSION['error_msg'] = "Failed to update user";
    }
    
    // Redirect back to user.php
    header("Location: ../models/user.php");
    exit();
} else {
    $_SESSION['error_msg'] = "Unauthorized access";
    header("Location: ../models/user.php");
    exit();
}
?>