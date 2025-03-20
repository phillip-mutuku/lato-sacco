<?php
require_once '../config/class.php';
$db = new db_class();

if(isset($_POST['save'])){
    $loan_type = $_POST['loan_type'];
    $interest_rate = $_POST['interest_rate'];
    
    $result = $db->save_loan_product($loan_type, $interest_rate);
    
    if($result === true){
        echo "<script>alert('Loan product saved successfully!');</script>";
    } else {
        echo "<script>alert('Failed to save loan product. Please try again.');</script>";
    }
    
    echo "<script>window.location='../views/loan_plan.php';</script>";
}
?>