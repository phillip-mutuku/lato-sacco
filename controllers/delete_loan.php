<?php
require_once '../config/class.php';
$db = new db_class();

if(isset($_GET['loan_id'])) {
    $loan_id = filter_input(INPUT_GET, 'loan_id', FILTER_SANITIZE_NUMBER_INT);

    $result = $db->delete_loan($loan_id);

    if ($result === true) {
        $_SESSION['success'] = "Loan deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete loan. Please try again.";
    }

    header("Location: ../models/loan.php");
    exit();
} else {
    header("Location: ../models/loan.php");
    exit();
}
?>