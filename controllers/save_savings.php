<?php
require_once '../config/class.php';
$db = new db_class();

if(isset($_POST['save_savings'])) {
    $account_id = filter_input(INPUT_POST, 'account_id', FILTER_SANITIZE_NUMBER_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $payment_mode = filter_input(INPUT_POST, 'payment_mode', FILTER_SANITIZE_STRING);

    $result = $db->add_savings($account_id, $amount, $payment_mode);

    if ($result !== false) {
        $_SESSION['success'] = "Savings added successfully.";
    } else {
        $_SESSION['error'] = "Failed to add savings. Please try again.";
    }

    header("Location: ../views/view_account.php?id=" . $account_id);
    exit();
} else {
    header("Location: ../views/account.php");
    exit();
}
?>