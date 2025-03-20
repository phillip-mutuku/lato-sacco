<?php
require_once '../config/config.php';
require_once '../controllers/AccountController.php';

$db = new db_connect();
$controller = new AccountController($db->connect());

if (isset($_GET['id'])) {
    $accountId = $_GET['id'];
    $accountDetails = $controller->getAccountById($accountId);

    if ($accountDetails) {
        echo json_encode($accountDetails);
    } else {
        echo json_encode(['error' => 'Account not found']);
    }
} else {
    echo json_encode(['error' => 'No account ID provided']);
}
?>
