<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('location:../views/index.php');
}
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = 'Guest';
}
?>
