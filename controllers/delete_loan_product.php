<?php
require_once '../config/class.php';
$db = new db_class();

if(isset($_GET['id'])){
    $id = $_GET['id'];
    
    $result = $db->delete_loan_product($id);
    
    if($result === true){
        echo "<script>alert('Loan product deleted successfully!');</script>";
    } else {
        echo "<script>alert('Failed to delete loan product. Please try again.');</script>";
    }
    
    echo "<script>window.location='../views/loan_plan.php';</script>";
}
?>