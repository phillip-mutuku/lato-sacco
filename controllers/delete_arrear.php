<?php
// Prevent any output before JSON
ob_start();

require_once '../helpers/session.php';
require_once '../config/class.php';

// Clear any previous output
ob_end_clean();

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access. Only administrators can delete arrears.'
    ]);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['loan_id']) || !isset($_POST['due_date'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameters'
    ]);
    exit();
}

$db = new db_class();
$loan_id = intval($_POST['loan_id']);
$due_date = trim($_POST['due_date']);

try {
    // Start transaction
    $db->conn->begin_transaction();
    
    // First, get the details of the arrear being deleted (for logging)
    $check_query = "SELECT ls.*, l.ref_no, ca.first_name, ca.last_name, ca.shareholder_no
                    FROM loan_schedule ls
                    JOIN loan l ON ls.loan_id = l.loan_id
                    JOIN client_accounts ca ON l.account_id = ca.account_id
                    WHERE ls.loan_id = ? AND ls.due_date = ?";
    
    $check_stmt = $db->conn->prepare($check_query);
    if (!$check_stmt) {
        throw new Exception("Failed to prepare check query: " . $db->conn->error);
    }
    
    $check_stmt->bind_param("is", $loan_id, $due_date);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Arrear not found for loan_id: $loan_id and due_date: $due_date");
    }
    
    $arrear = $result->fetch_assoc();
    
    // Check current status
    if ($arrear['status'] === 'paid') {
        throw new Exception("This arrear is already marked as paid");
    }
    
    // CRITICAL FIX: Permanently mark as paid with full amount
    $update_query = "UPDATE loan_schedule 
                     SET status = 'paid', 
                         default_amount = 0, 
                         repaid_amount = amount,
                         paid_date = CURDATE()
                     WHERE loan_id = ? AND due_date = ? AND status != 'paid'";
    
    $update_stmt = $db->conn->prepare($update_query);
    if (!$update_stmt) {
        throw new Exception("Failed to prepare update query: " . $db->conn->error);
    }
    
    $update_stmt->bind_param("is", $loan_id, $due_date);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to execute update: " . $update_stmt->error);
    }
    
    $affected_rows = $update_stmt->affected_rows;
    
    if ($affected_rows === 0) {
        throw new Exception("No arrear was updated. It may have already been deleted or marked as paid.");
    }
    
    // Log the action
    $admin_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown';
    $log_message = "Admin ($admin_username) deleted arrear: Loan #" . $arrear['ref_no'] . 
                   " for " . $arrear['first_name'] . " " . $arrear['last_name'] . 
                   " (Shareholder: " . $arrear['shareholder_no'] . ")" .
                   " - Due Date: " . $due_date . 
                   " - Amount: KSh " . number_format($arrear['default_amount'], 2);
    
    // Only log if addNotification method exists
    if (method_exists($db, 'addNotification')) {
        $db->addNotification($log_message, 'arrear_deleted', $loan_id);
    }
    
    // Check if all payments are now complete for this loan
    $check_complete = "SELECT COUNT(*) as unpaid_count 
                       FROM loan_schedule 
                       WHERE loan_id = ? AND status != 'paid'";
    
    $check_stmt = $db->conn->prepare($check_complete);
    if (!$check_stmt) {
        throw new Exception("Failed to prepare completion check: " . $db->conn->error);
    }
    
    $check_stmt->bind_param("i", $loan_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result()->fetch_assoc();
    
    $loan_completed = false;
    
    // If all payments are complete, update loan status to fully paid (status = 3)
    if ($check_result['unpaid_count'] == 0) {
        $complete_loan = "UPDATE loan SET status = 3 WHERE loan_id = ?";
        $complete_stmt = $db->conn->prepare($complete_loan);
        
        if (!$complete_stmt) {
            throw new Exception("Failed to prepare loan completion: " . $db->conn->error);
        }
        
        $complete_stmt->bind_param("i", $loan_id);
        
        if (!$complete_stmt->execute()) {
            throw new Exception("Failed to update loan status: " . $complete_stmt->error);
        }
        
        $loan_completed = true;
        
        // Log loan completion
        if (method_exists($db, 'addNotification')) {
            $db->addNotification(
                "Loan #" . $arrear['ref_no'] . " marked as fully paid after arrear deletion by " . $admin_username, 
                'loan_completed', 
                $loan_id
            );
        }
    }
    
    // Commit transaction
    $db->conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Arrear permanently deleted and marked as paid' . ($loan_completed ? '. Loan is now fully paid.' : ''),
        'loan_id' => $loan_id,
        'due_date' => $due_date,
        'loan_completed' => $loan_completed
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($db) && isset($db->conn)) {
        $db->conn->rollback();
    }
    
    // Log the error
    error_log("Error deleting arrear (loan_id: $loan_id, due_date: $due_date): " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>