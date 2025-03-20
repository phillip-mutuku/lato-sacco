<?php
require_once '../config/config.php';
require_once '../controllers/AccountController.php';

$db = new db_connect();
$controller = new AccountController($db->connect());

if (isset($_GET['id'])) {
    $accountId = $_GET['id'];
    $controller->deleteAccount($accountId);
    header("Location: account.php");
    exit();
} else {
    echo "No account ID provided.";
}
?>
