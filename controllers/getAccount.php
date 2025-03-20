<?php
require_once '../models/AccountModel.php';
require_once '../config/db.php';

$db = new Database();
$accountModel = new AccountModel($db->connect());

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account_id = $_POST['id'];
    echo json_encode($accountModel->getAccountById($account_id));
}
?>