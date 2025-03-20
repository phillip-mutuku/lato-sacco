<?php
require_once '../config/class.php';
session_start();

if(isset($_POST['confirm'])){
    $db = new db_class();
    $username = $_POST['username'];
    $password = $_POST['password'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $role = $_POST['role'];
    
    if($db->add_user($username, $password, $firstname, $lastname, $role)){
        $_SESSION['success'] = "User added successfully";
    } else {
        $_SESSION['error'] = "Failed to add user";
    }
    
    header("Location: ../models/user.php");
    exit();
}
?>