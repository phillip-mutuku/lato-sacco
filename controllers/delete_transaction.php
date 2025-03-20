<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../helpers/session.php';
require_once '../config/class.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if transaction_id is provided
if (!isset($_POST['transaction_id']) || empty($_POST['transaction_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Transaction ID is required']);
    exit();
}

try {
    $db = new db_class();
    $transaction_id = intval($_POST['transaction_id']);
    
    // Verify transaction exists before deleting
    $check_query = "SELECT * FROM expenses WHERE id = ?";
    $stmt = $db->conn->prepare($check_query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->conn->error);
    }
    
    $stmt->bind_param("i", $transaction_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Transaction not found");
    }
    
    // Delete the transaction
    $delete_query = "DELETE FROM expenses WHERE id = ?";
    $delete_stmt = $db->conn->prepare($delete_query);
    if (!$delete_stmt) {
        throw new Exception("Prepare delete failed: " . $db->conn->error);
    }
    
    $delete_stmt->bind_param("i", $transaction_id);
    if (!$delete_stmt->execute()) {
        throw new Exception("Delete failed: " . $delete_stmt->error);
    }
    
    if ($delete_stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Transaction deleted successfully'
        ]);
    } else {
        throw new Exception("No transaction was deleted");
    }
    
} catch (Exception $e) {
    error_log("Delete transaction error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to delete transaction: ' . $e->getMessage()
    ]);
}
?>