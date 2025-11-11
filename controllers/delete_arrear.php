<?php
require_once '../helpers/session.php';
require_once '../config/class.php';

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
$due_date = $_POST['due_date'];

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
    $check_stmt->bind_param("is", $loan_id, $due_date);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Arrear not found");
    }
    
    $arrear = $result->fetch_assoc();
    
    // Update the loan schedule entry - mark as paid and clear default amount
    $update_query = "UPDATE loan_schedule 
                     SET status = 'paid', 
                         default_amount = 0, 
                         repaid_amount = amount,
                         paid_date = CURDATE()
                     WHERE loan_id = ? AND due_date = ?";
    
    $update_stmt = $db->conn->prepare($update_query);
    $update_stmt->bind_param("is", $loan_id, $due_date);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to update loan schedule: " . $update_stmt->error);
    }
    
    // Log the action
    $log_message = "Admin deleted arrear: Loan #" . $arrear['ref_no'] . 
                   " for " . $arrear['first_name'] . " " . $arrear['last_name'] . 
                   " (Shareholder: " . $arrear['shareholder_no'] . ")" .
                   " - Due Date: " . $due_date . 
                   " - Amount: KSh " . number_format($arrear['default_amount'], 2);
    
    $db->addNotification($log_message, 'arrear_deleted', $loan_id);
    
    // Check if all payments are now complete
    $check_complete = "SELECT COUNT(*) as unpaid_count 
                       FROM loan_schedule 
                       WHERE loan_id = ? AND status != 'paid'";
    
    $check_stmt = $db->conn->prepare($check_complete);
    $check_stmt->bind_param("i", $loan_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result()->fetch_assoc();
    
    // If all payments are complete, update loan status
    if ($check_result['unpaid_count'] == 0) {
        $complete_loan = "UPDATE loan SET status = 3 WHERE loan_id = ?";
        $complete_stmt = $db->conn->prepare($complete_loan);
        $complete_stmt->bind_param("i", $loan_id);
        $complete_stmt->execute();
        
        $db->addNotification("Loan #" . $arrear['ref_no'] . " marked as fully paid", 'loan_completed', $loan_id);
    }
    
    // Commit transaction
    $db->conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Arrear successfully deleted and marked as paid',
        'loan_id' => $loan_id,
        'due_date' => $due_date
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $db->conn->rollback();
    error_log("Error deleting arrear: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to delete arrear: ' . $e->getMessage()
    ]);
}
?>