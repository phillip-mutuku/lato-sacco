<?php
// controllers/check_receipt.php
require_once '../config/class.php';

header('Content-Type: application/json');

if (isset($_POST['receipt_no'])) {
    $db = new db_class();
    $receipt_no = $_POST['receipt_no'];
    
    // Check if receipt number exists
    $query = $db->conn->prepare("SELECT COUNT(*) as count FROM payment WHERE receipt_no = ?");
    $query->bind_param("s", $receipt_no);
    $query->execute();
    $result = $query->get_result();
    $count = $result->fetch_assoc()['count'];
    
    echo json_encode(['exists' => $count > 0]);
} else {
    echo json_encode(['error' => 'No receipt number provided']);
}
?>