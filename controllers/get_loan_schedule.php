<?php
require_once '../config/class.php';
$db = new db_class();

try {
    // Check if loan_id is provided
    if (!isset($_GET['loan_id'])) {
        throw new Exception('Loan ID not provided');
    }

    $loan_id = intval($_GET['loan_id']);
    
    // Get loan details
    $loan_query = "SELECT l.*, lp.interest_rate 
                   FROM loan l 
                   JOIN loan_products lp ON l.loan_product_id = lp.id 
                   WHERE l.loan_id = ?";
                  
    $loan_stmt = $db->conn->prepare($loan_query);
    if (!$loan_stmt) {
        throw new Exception("Failed to prepare loan query: " . $db->conn->error);
    }

    $loan_stmt->bind_param("i", $loan_id);
    if (!$loan_stmt->execute()) {
        throw new Exception("Failed to execute loan query: " . $loan_stmt->error);
    }

    $result = $loan_stmt->get_result();
    $loan = $result->fetch_assoc();

    if (!$loan) {
        throw new Exception("Loan not found");
    }

    // Get the repayment status and date
    $repayment_query = "SELECT due_date, repaid_amount, paid_date 
                        FROM loan_schedule 
                        WHERE loan_id = ?";
    $repayment_stmt = $db->conn->prepare($repayment_query);
    if (!$repayment_stmt) {
        throw new Exception("Failed to prepare repayment query: " . $db->conn->error);
    }

    $repayment_stmt->bind_param("i", $loan_id);
    if (!$repayment_stmt->execute()) {
        throw new Exception("Failed to execute repayment query: " . $repayment_stmt->error);
    }

    $repayment_result = $repayment_stmt->get_result();
    $repayments = $repayment_result->fetch_all(MYSQLI_ASSOC);

    // Initialize loan parameters - KEEP DECIMALS
    $total_amount = floatval($loan['amount']);
    $term = intval($loan['loan_term']);
    $interest_rate = floatval($loan['interest_rate']);
    $monthly_principal = round($total_amount / $term, 2); // Keep 2 decimal places

    // Start from meeting date or loan date
    $payment_date = new DateTime($loan['meeting_date'] ?? $loan['date_created']);
    $payment_date->modify('+1 month');

    // Generate schedule
    $remaining_principal = $total_amount;
    $remaining_balance = $total_amount + ($total_amount * ($interest_rate / 100) * $term);
    $schedule = array();

    // Store a lookup of schedule entries for the getLoanDetails method to use
    $lookup_data = [];

    for ($i = 0; $i < $term; $i++) {
        // Calculate interest on remaining principal - KEEP DECIMALS
        $interest = round($remaining_principal * ($interest_rate / 100), 2);
        $due_amount = $monthly_principal + $interest;
        $due_date = $payment_date->format('Y-m-d');

        // Check if this payment has been made
        $repaid_amount = 0;
        $paid_date = null;
        $status = 'unpaid';

        foreach ($repayments as $repayment) {
            if ($repayment['due_date'] == $due_date) {
                $repaid_amount = floatval($repayment['repaid_amount']);
                $paid_date = $repayment['paid_date'];
                // IMPROVED PAYMENT STATUS CHECK - Allow small differences
                $status = (abs($repaid_amount - $due_amount) <= 0.50) ? 'paid' : (($repaid_amount > 0) ? 'partial' : 'unpaid');
                break;
            }
        }

        // FIXED DEFAULT CALCULATION: Only calculate if past due date
        $default_amount = 0;
        $today = new DateTime();
        if ($today > new DateTime($due_date) && $status !== 'paid') {
            $default_amount = max(0, $due_amount - $repaid_amount);
        }

        // Add to lookup data for use by getLoanDetails method
        $lookup_data[] = [
            'due_date' => $due_date,
            'amount' => $due_amount,
            'repaid_amount' => $repaid_amount,
            'default_amount' => $default_amount,
            'status' => $status
        ];

        // Add to schedule array for response - KEEP DECIMALS
        $schedule[] = array(
            'due_date' => $due_date,
            'principal' => number_format($monthly_principal, 2), // Keep 2 decimals
            'interest' => number_format($interest, 2), // Keep 2 decimals
            'amount' => number_format($due_amount, 2), // Keep 2 decimals
            'balance' => number_format($remaining_balance, 2), // Keep 2 decimals
            'repaid_amount' => number_format($repaid_amount, 2), // Keep 2 decimals
            'default_amount' => number_format($default_amount, 2), // Keep 2 decimals
            'status' => $status,
            'paid_date' => $paid_date
        );

        // Update balances for next iteration
        $remaining_principal -= $monthly_principal;
        $remaining_balance -= $due_amount;
        
        // Move to next month
        $payment_date->modify('+1 month');
    }

    // Save the computed schedule to the database for use by other functions
    $db->conn->begin_transaction();
    
    try {
        // First delete any existing entries that aren't marked as paid
        $delete_stmt = $db->conn->prepare("DELETE FROM loan_schedule WHERE loan_id = ? AND status != 'paid'");
        $delete_stmt->bind_param("i", $loan_id);
        $delete_stmt->execute();
        
        // Then insert the newly calculated entries
        $insert_stmt = $db->conn->prepare("
            INSERT INTO loan_schedule 
            (loan_id, due_date, principal, interest, amount, balance, repaid_amount, default_amount, status, paid_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            principal = VALUES(principal),
            interest = VALUES(interest),
            amount = VALUES(amount),
            balance = VALUES(balance),
            default_amount = VALUES(default_amount),
            status = VALUES(status)
        ");
        
        foreach ($lookup_data as $index => $entry) {
            // Parse the formatted values back to raw numbers
            $principal = $monthly_principal;
            $interest_val = round(($total_amount - ($index * $monthly_principal)) * ($interest_rate / 100));
            $amount_val = $entry['amount'];
            $balance_val = $remaining_balance - ($index * $amount_val);
            $default_val = $entry['default_amount'];
            
            $insert_stmt->bind_param(
                "isdddddsss",
                $loan_id,
                $entry['due_date'],
                $principal,
                $interest_val,
                $amount_val,
                $balance_val,
                $entry['repaid_amount'],
                $default_val,
                $entry['status'],
                $entry['paid_date']
            );
            
            $insert_stmt->execute();
        }
        
        // CHECK IF LOAN IS FULLY PAID AND UPDATE STATUS
        $all_paid = true;
        foreach ($lookup_data as $entry) {
            if ($entry['status'] !== 'paid') {
                $all_paid = false;
                break;
            }
        }
        
        if ($all_paid) {
            $complete_loan_stmt = $db->conn->prepare("UPDATE loan SET status = 3 WHERE loan_id = ?");
            $complete_loan_stmt->bind_param("i", $loan_id);
            $complete_loan_stmt->execute();
        }
        
        $db->conn->commit();
    } catch (Exception $e) {
        $db->conn->rollback();
        error_log("Failed to update loan schedule in database: " . $e->getMessage());
        // Continue with response - this is not fatal
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'schedule' => $schedule,
        'loan_id' => $loan_id,
        'loan_details' => [
            'total_amount' => $total_amount,
            'interest_rate' => $interest_rate,
            'term' => $term,
            'monthly_principal' => $monthly_principal
        ]
    ]);

} catch (Exception $e) {
    error_log("Loan Schedule Error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}