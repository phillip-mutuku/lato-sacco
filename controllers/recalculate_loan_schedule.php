<?php
require_once('../helpers/session.php'); 
require_once('../config/config.php');

/**
 * RecalculateLoanScheduleController
 * 
 * Recalculates loan schedule status, repaid amounts, defaults, and paid dates
 * based on actual loan_repayments records in the database
 */

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Unauthorized: User not logged in");
    }
    
    // Get loan_id from request
    $loanId = filter_var($_POST['loan_id'] ?? $_GET['loan_id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$loanId || $loanId <= 0) {
        throw new Exception("Valid Loan ID is required");
    }
    
    // Connect to database
    $db = db_connect::getInstance();
    $conn = $db->connect();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Step 1: Verify loan exists and is disbursed
    $loanQuery = $conn->prepare("
        SELECT loan_id, ref_no, amount, status 
        FROM loan 
        WHERE loan_id = ?
    ");
    
    if (!$loanQuery) {
        throw new Exception("Failed to prepare loan query: " . $conn->error);
    }
    
    $loanQuery->bind_param("i", $loanId);
    $loanQuery->execute();
    $loanData = $loanQuery->get_result()->fetch_assoc();
    
    if (!$loanData) {
        throw new Exception("Loan not found with ID: $loanId");
    }
    
    if ($loanData['status'] < 2) {
        throw new Exception("Loan must be disbursed (status >= 2) before recalculating schedule");
    }
    
    error_log("=== RECALCULATE LOAN SCHEDULE STARTED ===");
    error_log("Loan ID: $loanId, Ref No: {$loanData['ref_no']}");
    
    // Step 2: Get all schedule entries in chronological order
    $scheduleQuery = $conn->prepare("
        SELECT 
            id,
            due_date,
            principal,
            interest,
            amount,
            balance,
            COALESCE(repaid_amount, 0) as repaid_amount,
            COALESCE(default_amount, 0) as default_amount,
            status,
            paid_date
        FROM loan_schedule 
        WHERE loan_id = ? 
        ORDER BY due_date ASC, id ASC
    ");
    
    if (!$scheduleQuery) {
        throw new Exception("Failed to prepare schedule query: " . $conn->error);
    }
    
    $scheduleQuery->bind_param("i", $loanId);
    $scheduleQuery->execute();
    $scheduleEntries = $scheduleQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($scheduleEntries)) {
        throw new Exception("No loan schedule found for loan ID: $loanId");
    }
    
    error_log("Found " . count($scheduleEntries) . " schedule entries");
    
    // Step 3: Get ALL loan repayments for this loan
    $repaymentsQuery = $conn->prepare("
        SELECT 
            id,
            amount_repaid,
            date_paid,
            payment_mode,
            receipt_number,
            served_by
        FROM loan_repayments 
        WHERE loan_id = ? 
        ORDER BY date_paid ASC, id ASC
    ");
    
    if (!$repaymentsQuery) {
        throw new Exception("Failed to prepare repayments query: " . $conn->error);
    }
    
    $repaymentsQuery->bind_param("i", $loanId);
    $repaymentsQuery->execute();
    $repayments = $repaymentsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    
    error_log("Found " . count($repayments) . " repayment records");
    
    // Step 4: Calculate total repaid amount from actual repayments
    $totalRepaid = 0;
    foreach ($repayments as $repayment) {
        $totalRepaid += floatval($repayment['amount_repaid']);
    }
    
    error_log("Total amount repaid: KSh $totalRepaid");
    
    // Step 5: Recalculate schedule entries based on actual repayments
    $remainingPayment = $totalRepaid;
    $updatedEntries = [];
    $today = date('Y-m-d');
    
    // FIXED: Keep already-paid installments as paid, only recalculate unpaid ones
    foreach ($scheduleEntries as $entry) {
        $entryId = $entry['id'];
        $dueDate = $entry['due_date'];
        $dueAmount = floatval($entry['amount']);
        $currentStatus = $entry['status'];
        $currentRepaidAmount = floatval($entry['repaid_amount']);
        
        // If already marked as paid in current schedule, keep it paid
        if ($currentStatus === 'paid' && abs($currentRepaidAmount - $dueAmount) <= 0.01) {
            $newRepaidAmount = $dueAmount;
            $newStatus = 'paid';
            $newPaidDate = $entry['paid_date'] ?? $today;
            $newDefaultAmount = 0;
            
            // Deduct from remaining payment
            $remainingPayment -= $dueAmount;
        } 
        // Otherwise, apply remaining payment to this entry if available
        else if ($remainingPayment > 0.01) {
            $paymentForThisEntry = min($remainingPayment, $dueAmount);
            $newRepaidAmount = $paymentForThisEntry;
            $remainingPayment -= $paymentForThisEntry;
            
            // Determine status based on amount paid
            if (abs($newRepaidAmount - $dueAmount) <= 0.01) {
                // Fully paid
                $newStatus = 'paid';
                $newRepaidAmount = $dueAmount; // Ensure exact amount
                
                // Find the date this installment was paid from repayments
                $newPaidDate = null;
                foreach ($repayments as $repayment) {
                    if (abs(floatval($repayment['amount_repaid']) - $dueAmount) <= 0.01) {
                        $newPaidDate = $repayment['date_paid'];
                        break;
                    }
                }
                
                // If we couldn't determine paid date, use today
                if (!$newPaidDate) {
                    $newPaidDate = $today;
                }
                
            } elseif ($newRepaidAmount > 0.01) {
                // Partially paid
                $newStatus = 'partial';
                $newPaidDate = null;
            } else {
                // Unpaid
                $newStatus = 'unpaid';
                $newPaidDate = null;
            }
        }
        // No payment remaining
        else {
            $newRepaidAmount = 0;
            $newStatus = 'unpaid';
            $newPaidDate = null;
        }
        
        // Calculate default amount for overdue unpaid/partial installments
        $newDefaultAmount = 0;
        if ($newStatus !== 'paid' && strtotime($dueDate) < strtotime($today)) {
            $newDefaultAmount = $dueAmount - $newRepaidAmount;
        }
        
        $updatedEntries[] = [
            'id' => $entryId,
            'due_date' => $dueDate,
            'new_repaid_amount' => $newRepaidAmount,
            'new_status' => $newStatus,
            'new_paid_date' => $newPaidDate,
            'new_default_amount' => $newDefaultAmount
        ];
        
        error_log("Entry $entryId (due: $dueDate): Repaid: KSh $newRepaidAmount, Status: $newStatus, Paid Date: " . ($newPaidDate ?? 'NULL'));
    }
    
    // Step 6: Update all schedule entries with recalculated values
    $updateStmt = $conn->prepare("
        UPDATE loan_schedule 
        SET repaid_amount = ?,
            status = ?,
            paid_date = ?,
            default_amount = ?
        WHERE id = ?
    ");
    
    if (!$updateStmt) {
        throw new Exception("Failed to prepare update statement: " . $conn->error);
    }
    
    $updatedCount = 0;
    foreach ($updatedEntries as $update) {
        $updateStmt->bind_param(
            "dssdi",
            $update['new_repaid_amount'],
            $update['new_status'],
            $update['new_paid_date'],
            $update['new_default_amount'],
            $update['id']
        );
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update schedule entry ID {$update['id']}: " . $updateStmt->error);
        }
        
        if ($updateStmt->affected_rows > 0) {
            $updatedCount++;
        }
    }
    
    error_log("Updated $updatedCount schedule entries");
    
    // Step 7: Recalculate loan status based on updated schedule
    $statusCheckStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_installments,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_installments,
            SUM(amount - COALESCE(repaid_amount, 0)) as remaining_amount
        FROM loan_schedule 
        WHERE loan_id = ?
    ");
    
    if (!$statusCheckStmt) {
        throw new Exception("Failed to prepare status check: " . $conn->error);
    }
    
    $statusCheckStmt->bind_param("i", $loanId);
    $statusCheckStmt->execute();
    $statusResult = $statusCheckStmt->get_result()->fetch_assoc();
    
    $totalInstallments = intval($statusResult['total_installments']);
    $paidInstallments = intval($statusResult['paid_installments']);
    $remainingAmount = floatval($statusResult['remaining_amount'] ?? 0);
    
    error_log("Status check: $paidInstallments/$totalInstallments paid, Remaining: KSh $remainingAmount");
    
    // Update loan status
    $newLoanStatus = null;
    if ($paidInstallments === $totalInstallments && $remainingAmount <= 0.01) {
        // Fully paid
        $newLoanStatus = 3;
        error_log("✓ Loan should be marked as FULLY PAID (status = 3)");
    } elseif ($loanData['status'] == 3 && ($paidInstallments < $totalInstallments || $remainingAmount > 0.01)) {
        // Was marked as fully paid but has unpaid installments
        $newLoanStatus = 2;
        error_log("✓ Loan changed to DISBURSED (status = 2) - has unpaid installments");
    }
    
    if ($newLoanStatus !== null) {
        $updateLoanStmt = $conn->prepare("
            UPDATE loan SET status = ? WHERE loan_id = ?
        ");
        
        if ($updateLoanStmt) {
            $updateLoanStmt->bind_param("ii", $newLoanStatus, $loanId);
            $updateLoanStmt->execute();
            error_log("✓ Updated loan status to: $newLoanStatus");
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    error_log("=== RECALCULATE LOAN SCHEDULE COMPLETED SUCCESSFULLY ===");
    
    // Return success response with updated schedule
    echo json_encode([
        'status' => 'success',
        'message' => 'Loan schedule recalculated successfully',
        'details' => [
            'loan_id' => $loanId,
            'loan_ref_no' => $loanData['ref_no'],
            'total_installments' => $totalInstallments,
            'paid_installments' => $paidInstallments,
            'entries_updated' => $updatedCount,
            'total_repaid' => $totalRepaid,
            'remaining_amount' => $remainingAmount,
            'loan_status' => $newLoanStatus ?? $loanData['status'],
            'fully_paid' => ($paidInstallments === $totalInstallments && $remainingAmount <= 0.01)
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    error_log("=== RECALCULATE LOAN SCHEDULE FAILED ===");
    error_log("ERROR: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>