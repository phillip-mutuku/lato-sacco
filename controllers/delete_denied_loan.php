<?php
session_start();
require_once '../config/class.php';

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loan_id'])) {
    $loan_id = intval($_POST['loan_id']);
    $db = new db_class();
    
    try {
        // Start transaction
        $db->conn->begin_transaction();
        
        // First, get loan details for logging purposes
        $loan_details = $db->get_loan($loan_id);
        if (!$loan_details) {
            throw new Exception('Loan not found');
        }
        
        // Check if loan is still pending (status = 0)
        if ($loan_details['status'] != 0) {
            throw new Exception('Can only delete pending loans');
        }
        
        // Delete related records first to maintain referential integrity
        
        // 1. Delete loan schedule entries
        $delete_schedule = $db->conn->prepare("DELETE FROM loan_schedule WHERE loan_id = ?");
        $delete_schedule->bind_param("i", $loan_id);
        if (!$delete_schedule->execute()) {
            throw new Exception('Failed to delete loan schedule');
        }
        
        // 2. Delete any payment records (though there shouldn't be any for pending loans)
        $delete_payments = $db->conn->prepare("DELETE FROM payment WHERE loan_id = ?");
        $delete_payments->bind_param("i", $loan_id);
        if (!$delete_payments->execute()) {
            throw new Exception('Failed to delete payment records');
        }
        
        // 3. Delete the main loan record
        if (!$db->delete_loan($loan_id)) {
            throw new Exception('Failed to delete loan record');
        }
        
        // Log the denial action for audit purposes
        $client_name = $loan_details['guarantor_name']; // You might want to get actual client name
        $log_message = "Loan application denied and deleted - Ref: " . $loan_details['ref_no'] . 
                      ", Amount: " . $loan_details['amount'] . 
                      ", User: " . $_SESSION['firstname'] . " " . $_SESSION['lastname'];
        
        // Add notification (if you have a notifications system)
        $db->addNotification($log_message, 'loan_denied', $loan_id);
        
        // Commit transaction
        $db->conn->commit();
        
        // Clean any remaining output buffer content
        ob_clean();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Loan application has been denied and removed from the system'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->conn->rollback();
        
        error_log("Error deleting denied loan: " . $e->getMessage());
        
        // Clean any remaining output buffer content
        ob_clean();
        
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to deny loan: ' . $e->getMessage()
        ]);
    }
} else {
    // Clean any remaining output buffer content
    ob_clean();
    
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

// End output buffering and send the response
ob_end_flush();
?>