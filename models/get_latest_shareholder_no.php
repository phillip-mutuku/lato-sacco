<?php
require_once '../config/config.php';
require_once '../controllers/AccountController.php';

$db = new db_connect();
$controller = new AccountController($db->connect());

$lastAccount = $controller->getAccounts(1, 0);

if ($lastAccount && $lastAccount->num_rows > 0) {
    $lastShareholderNo = $lastAccount->fetch_assoc()['shareholder_no'];
    echo $lastShareholderNo;
} else {
    echo '000'; 
}
?>
