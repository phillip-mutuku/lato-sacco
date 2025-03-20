<?php
require_once '../config/class.php';
$db = new db_class();

if(isset($_POST['update'])){
    $id = $_POST['id'];
    $loan_type = $_POST['loan_type'];
    $interest_rate = $_POST['interest_rate'];
    
    $result = $db->update_loan_product($id, $loan_type, $interest_rate);
    
    if($result === true){
        echo "<script>alert('Loan product updated successfully!');</script>";
    } else {
        echo "<script>alert('Failed to update loan product. Please try again.');</script>";
    }
    
    echo "<script>window.location='../views/loan_plan.php';</script>";
}
?>