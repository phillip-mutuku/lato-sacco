<?php
require_once '../helpers/session.php';
require_once '../config/class.php';

// Set header for JSON response
header('Content-Type: application/json');

// Check if user is logged in and authorized
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Check if request is AJAX and has loan_id
if (!isset($_POST['ajax']) || !isset($_POST['loan_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit();
}

$db = new db_class();
$loan_id = intval($_POST['loan_id']);
$user_id = $_SESSION['user_id'];

try {
    // Get loan details before deletion
    $loan = $db->get_loan($loan_id);
    
    if (!$loan) {
        echo json_encode([
            'success' => false,
            'message' => 'Loan not found'
        ]);
        exit();
    }
    
    // Check if loan is pending disbursement (status = 1)
    // These are approved loans waiting for disbursement and CAN be deleted
    if ($loan['status'] != 1) {
        $status_messages = [
            0 => 'pending approval (not yet approved)',
            2 => 'already disbursed',
            3 => 'completed',
            4 => 'denied'
        ];
        
        $status_text = isset($status_messages[$loan['status']]) 
            ? $status_messages[$loan['status']] 
            : 'in a non-deletable state';
        
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete this loan. It is $status_text. Only loans waiting for disbursement can be deleted."
        ]);
        exit();
    }
    
    // Check if any payments have been made (safety check)
    $payment_check = $db->conn->prepare("SELECT COUNT(*) as payment_count FROM payment WHERE loan_id = ?");
    $payment_check->bind_param("i", $loan_id);
    $payment_check->execute();
    $result = $payment_check->get_result()->fetch_assoc();
    
    if ($result['payment_count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete loan with existing payment records'
        ]);
        exit();
    }
    
    // Get full loan details for logging
    $loan_details_query = $db->conn->prepare("
        SELECT l.*, 
               CONCAT(ca.first_name, ' ', ca.last_name) as borrower_name,
               ca.shareholder_no
        FROM loan l
        INNER JOIN client_accounts ca ON l.account_id = ca.account_id
        WHERE l.loan_id = ?
    ");
    $loan_details_query->bind_param("i", $loan_id);
    $loan_details_query->execute();
    $loan_details = $loan_details_query->get_result()->fetch_assoc();
    
    if (!$loan_details) {
        echo json_encode([
            'success' => false,
            'message' => 'Unable to retrieve loan details'
        ]);
        exit();
    }
    
    // Start transaction for safe deletion
    $db->conn->begin_transaction();
    
    try {
        // Delete loan schedule entries first (foreign key constraint)
        $delete_schedule = $db->conn->prepare("DELETE FROM loan_schedule WHERE loan_id = ?");
        $delete_schedule->bind_param("i", $loan_id);
        if (!$delete_schedule->execute()) {
            throw new Exception("Failed to delete loan schedule");
        }
        
        // Delete the loan record
        $delete_loan = $db->conn->prepare("DELETE FROM loan WHERE loan_id = ?");
        $delete_loan->bind_param("i", $loan_id);
        if (!$delete_loan->execute()) {
            throw new Exception("Failed to delete loan record");
        }
        
        // Log the deletion for audit trail (if you have an audit_log table)
        $log_query = $db->conn->prepare("
            INSERT INTO audit_log (action, details, user_id, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        if ($log_query) {
            $action = 'loan_deleted';
            $details = json_encode([
                'loan_id' => $loan_id,
                'ref_no' => $loan_details['ref_no'],
                'borrower_name' => $loan_details['borrower_name'],
                'shareholder_no' => $loan_details['shareholder_no'],
                'amount' => $loan_details['amount'],
                'status' => 'Pending Disbursement (Status 1)',
                'deleted_by' => $_SESSION['username'] ?? 'Unknown',
                'reason' => 'Deleted from disbursement page',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $log_query->bind_param("ssi", $action, $details, $user_id);
            $log_query->execute();
        }
        
        // Commit transaction
        $db->conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Loan deleted successfully',
            'loan_ref' => $loan_details['ref_no'],
            'borrower_name' => $loan_details['borrower_name']
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $db->conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in delete_pending_loan.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while deleting the loan: ' . $e->getMessage()
    ]);
}
?>