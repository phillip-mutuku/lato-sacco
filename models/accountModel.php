<?php
require_once('../config/config.php');

/**
 * AccountModel Class
 * 
 * Handles individual account operations, loan repayments, and single loan operations
 * For account-level summaries and totals, use AccountSummaryModel
 */
class AccountModel {
    private $conn;
    private $lastError;

    public function __construct() {
    $db = db_connect::getInstance();
    $this->conn = $db->connect();
    $this->lastError = '';

    if (!$this->conn) {
        die("Database connection error: " . $db->error);
    }
}

    // =====================================
    // ACCOUNT CRUD OPERATIONS
    // =====================================

    /**
     * Create a new client account
     */
    public function createAccount($data) {
        try {
            $requiredFields = ['first_name', 'last_name', 'shareholder_no', 'national_id', 'phone', 'account_type'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
    
            $accountType = is_array($data['account_type']) ? implode(', ', $data['account_type']) : $data['account_type'];
    
            $stmt = $this->conn->prepare("INSERT INTO client_accounts (
                first_name, last_name, shareholder_no, national_id, 
                phone_number, email, location, division, village, account_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
            $stmt->bind_param("ssssssssss",
                $data['first_name'],
                $data['last_name'],
                $data['shareholder_no'],
                $data['national_id'],
                $data['phone'],
                $data['email'],
                $data['location'],
                $data['division'],
                $data['village'],
                $accountType
            );
    
            if (!$stmt->execute()) {
                throw new Exception("Error executing query: " . $stmt->error);
            }
    
            return ['status' => 'success', 'message' => 'Account created successfully'];
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return ['status' => 'error', 'message' => $this->lastError];
        }
    }

    /**
     * Update an existing client account
     */
    public function updateAccount($data) {
        try {
            $this->conn->begin_transaction();
    
            $checkStmt = $this->conn->prepare("SELECT account_id FROM client_accounts WHERE account_id = ?");
            $checkStmt->bind_param("i", $data['account_id']);
            $checkStmt->execute();
            if (!$checkStmt->get_result()->fetch_assoc()) {
                throw new Exception("Account not found");
            }
    
            $accountType = is_array($data['account_type']) ? implode(', ', $data['account_type']) : $data['account_type'];
    
            $stmt = $this->conn->prepare("
                UPDATE client_accounts SET 
                    first_name = ?,
                    last_name = ?,
                    shareholder_no = ?,
                    national_id = ?,
                    phone_number = ?,
                    email = ?,
                    location = ?,
                    division = ?,
                    village = ?,
                    account_type = ?
                WHERE account_id = ?
            ");
    
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
    
            $stmt->bind_param(
                "ssssssssssi",
                $data['first_name'],
                $data['last_name'],
                $data['shareholder_no'],
                $data['national_id'],
                $data['phone_number'],
                $data['email'],
                $data['location'],
                $data['division'],
                $data['village'],
                $accountType,
                $data['account_id']
            );
    
            if (!$stmt->execute()) {
                throw new Exception("Update failed: " . $stmt->error);
            }
    
            $this->conn->commit();
            return [
                'status' => 'success',
                'message' => 'Account updated successfully'
            ];
    
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->lastError = $e->getMessage();
            error_log("Update account error: " . $this->lastError);
            return [
                'status' => 'error',
                'message' => $this->lastError
            ];
        }
    }

    /**
     * Delete a client account
     */
    public function deleteAccount($accountId) {
        $stmt = $this->conn->prepare("DELETE FROM client_accounts WHERE account_id = ?");
        $stmt->bind_param("i", $accountId);
        return $stmt->execute();
    }

    /**
     * Get a single account by ID
     */
    public function getAccountById($account_id) {
        $stmt = $this->conn->prepare("SELECT * FROM client_accounts WHERE account_id = ?");
        $stmt->bind_param("i", $account_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get all client accounts
     */
    public function getAllAccounts() {
        $result = $this->conn->query("SELECT * FROM client_accounts ORDER BY last_name, first_name");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // =====================================
    // SAVINGS OPERATIONS
    // =====================================


   /**
 * Check if a transaction record already exists for this receipt and account
 * to prevent duplicate transaction entries
 */
public function checkDuplicateTransaction($receiptNumber, $accountId, $type, $amount) {
    try {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM transactions 
            WHERE receipt_number = ? 
            AND account_id = ? 
            AND type = ? 
            AND ABS(amount - ?) < 0.01
        ");
        
        $stmt->bind_param("sisd", $receiptNumber, $accountId, $type, $amount);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return ($result['count'] > 0);
        
    } catch (Exception $e) {
        error_log("Error checking duplicate transaction: " . $e->getMessage());
        return false;
    }
}

/**
 * Add transaction record only if it doesn't already exist
 */
private function addTransactionSafe($accountId, $type, $amount, $description, $receiptNumber) {
    try {
        // Check if transaction already exists
        if ($this->checkDuplicateTransaction($receiptNumber, $accountId, $type, $amount)) {
            // Transaction already exists, skip insertion
            error_log("Skipping duplicate transaction: Receipt $receiptNumber, Type $type, Amount $amount");
            return true;
        }
        
        // Insert new transaction
        $stmt = $this->conn->prepare("
            INSERT INTO transactions (
                account_id,
                type,
                amount,
                description,
                receipt_number,
                date
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
    
        $stmt->bind_param("isdss", 
            $accountId,
            $type,
            $amount,
            $description,
            $receiptNumber
        );
    
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Error in addTransactionSafe: " . $e->getMessage());
        return false;
    }
}

/**
 * Updated addSavings method with duplicate transaction prevention
 */
public function addSavings($accountId, $amount, $paymentMode, $accountType, $receiptNumber, $servedBy) {
    try {
        
        // Use database transaction for atomicity
        $this->conn->begin_transaction();
        
        // Insert savings record
        $stmt = $this->conn->prepare("
            INSERT INTO savings (
                account_id,
                amount,
                payment_mode,
                type,
                account_type,
                receipt_number,
                served_by,
                date
            ) VALUES (?, ?, ?, 'Savings', ?, ?, ?, NOW())
        ");
        
        if (!$stmt) {
            throw new Exception("Error preparing savings statement: " . $this->conn->error);
        }

        $stmt->bind_param("idssss", 
            $accountId,
            $amount,
            $paymentMode,
            $accountType,
            $receiptNumber,
            $servedBy
        );

        if (!$stmt->execute()) {
            throw new Exception("Error executing savings insert: " . $stmt->error);
        }

        $savingsId = $this->conn->insert_id;

        // Insert transaction record only if it doesn't already exist
        $description = "Savings deposit - $accountType";
        $this->addTransactionSafe(
            $accountId,
            'Savings',
            $amount,
            $description,
            $receiptNumber
        );

        // Commit transaction
        $this->conn->commit();
        
        return [
            'status' => 'success',
            'message' => 'Savings added successfully',
            'savingsId' => $savingsId,
            'receiptDetails' => $this->getSavingsReceiptDetails($savingsId)['details'] ?? null
        ];

    } catch (Exception $e) {
        // Rollback on error
        $this->conn->rollback();
        error_log("Error in addSavings: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Updated withdraw method with duplicate transaction prevention
 */
public function withdraw($accountId, $amount, $paymentMode, $accountType, $receiptNumber, $withdrawalFee, $servedBy) {
    $this->conn->begin_transaction();

    try {
        $withdrawalAmount = $amount - $withdrawalFee;

        $stmt = $this->conn->prepare("
            INSERT INTO savings (
                account_id,
                amount,
                payment_mode,
                type,
                account_type,
                receipt_number,
                withdrawal_fee,
                served_by,
                date
            ) VALUES (?, ?, ?, 'Withdrawal', ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param("idsssds", 
            $accountId,
            $withdrawalAmount,
            $paymentMode,
            $accountType,
            $receiptNumber,
            $withdrawalFee,
            $servedBy
        );

        if (!$stmt->execute()) {
            throw new Exception("Error executing withdrawal: " . $stmt->error);
        }

        $withdrawalId = $this->conn->insert_id;

        // Add withdrawal transaction safely (prevent duplicates)
        $this->addTransactionSafe(
            $accountId,
            'Withdrawal',
            -$withdrawalAmount,
            "Withdrawal from $accountType account",
            $receiptNumber
        );

        // Add withdrawal fee transaction safely (prevent duplicates)
        if ($withdrawalFee > 0) {
            $this->addTransactionSafe(
                $accountId,
                'Withdrawal Fee',
                -$withdrawalFee,
                "Withdrawal fee for $accountType account",
                $receiptNumber
            );
        }

        $this->conn->commit();

        $receiptDetails = $this->getWithdrawalReceiptDetails($withdrawalId);

        return [
            'status' => 'success',
            'message' => 'Withdrawal processed successfully',
            'withdrawalId' => $withdrawalId,
            'receiptDetails' => $receiptDetails['details'] ?? null
        ];

    } catch (Exception $e) {
        $this->conn->rollback();
        error_log("Withdrawal error: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

    // =====================================
    // TRANSACTION AND DATA RETRIEVAL
    // =====================================

    /**
     * Get account transactions
     */
   public function getAccountTransactions($accountId, $accountType = 'all') {
    try {
        // First, get all transactions for the account
        $query = "SELECT * FROM transactions WHERE account_id = ?";
        $params = [$accountId];
        $types = "i";
        
        $query .= " ORDER BY date DESC";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $transactions = $result->fetch_all(MYSQLI_ASSOC);
        
        // If filtering by account type, filter the results
        if ($accountType !== 'all') {
            $filteredTransactions = [];
            foreach ($transactions as $transaction) {
                if ($transaction['receipt_number']) {
                    // Check if this receipt has the required account type
                    $typeCheckStmt = $this->conn->prepare("
                        SELECT COUNT(*) as count 
                        FROM savings 
                        WHERE receipt_number = ? 
                        AND account_id = ? 
                        AND account_type = ?
                    ");
                    $typeCheckStmt->bind_param("sis", 
                        $transaction['receipt_number'], 
                        $accountId, 
                        $accountType
                    );
                    $typeCheckStmt->execute();
                    $typeResult = $typeCheckStmt->get_result()->fetch_assoc();
                    
                    if ($typeResult['count'] > 0) {
                        $filteredTransactions[] = $transaction;
                    }
                } else {
                    // Include transactions without receipt numbers (like loan disbursements)
                    $filteredTransactions[] = $transaction;
                }
            }
            $transactions = $filteredTransactions;
        }
        
        // Add account_type information for display (optional)
        foreach ($transactions as &$transaction) {
            if ($transaction['receipt_number']) {
                $typeQuery = $this->conn->prepare("
                    SELECT account_type 
                    FROM savings 
                    WHERE receipt_number = ? 
                    AND account_id = ? 
                    LIMIT 1
                ");
                $typeQuery->bind_param("si", $transaction['receipt_number'], $accountId);
                $typeQuery->execute();
                $typeResult = $typeQuery->get_result()->fetch_assoc();
                $transaction['account_type'] = $typeResult['account_type'] ?? null;
            } else {
                $transaction['account_type'] = null;
            }
        }
        
        error_log("getAccountTransactions: Found " . count($transactions) . " transactions for account $accountId");
        
        return $transactions;
        
    } catch (Exception $e) {
        error_log("Error in getAccountTransactions: " . $e->getMessage());
        return [];
    }
}

    /**
     * Get account loans
     */
    public function getAccountLoans($accountId, $accountType = 'all') {
        try {
            $query = "
                SELECT l.*, 
                       (l.amount - COALESCE(SUM(lr.amount_repaid), 0)) as outstanding_balance
                FROM loan l
                LEFT JOIN loan_repayments lr ON l.loan_id = lr.loan_id
                WHERE l.account_id = ?";
            
            $params = [$accountId];
            $types = "i";
            
            if ($accountType !== 'all') {
                $query .= " AND l.account_type = ?";
                $params[] = $accountType;
                $types .= "s";
            }
            
            $query .= " GROUP BY l.loan_id
                        HAVING outstanding_balance > 0 OR l.status != 3
                        ORDER BY l.date_applied DESC";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $loans = $result->fetch_all(MYSQLI_ASSOC);
            
            return $loans;
        } catch (Exception $e) {
            error_log("Error in getAccountLoans: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get account savings with served_by user names
     */
    public function getAccountSavings($accountId, $accountType = 'all') {
        $query = "SELECT s.*, u.username as served_by FROM savings s 
                  LEFT JOIN user u ON s.served_by = u.user_id 
                  WHERE s.account_id = ?";
        $params = [$accountId];
        $types = "i";
                 
        if ($accountType !== 'all') {
            $query .= " AND s.account_type = ?";
            $params[] = $accountType;
            $types .= "s";
        }
                 
        $query .= " ORDER BY s.date DESC";
                 
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =====================================
    // INDIVIDUAL LOAN OPERATIONS
    // =====================================

/**
 * FIXED: Get loan details for repayment with proper due amount calculation
 */
/**
 * FIXED: Get loan details for repayment with proper outstanding balance calculation
 */
public function getLoanDetailsForRepayment($loanId) {
    try {
        $query = $this->conn->prepare("
            SELECT 
                l.*, 
                ca.first_name, 
                ca.last_name,
                lp.interest_rate,
                -- FIXED: Calculate outstanding balance as remaining principal only (no interest)
                (l.amount - COALESCE((
                    SELECT SUM(principal) 
                    FROM loan_schedule 
                    WHERE loan_id = l.loan_id 
                    AND status = 'paid'
                ), 0)) as outstanding_balance
            FROM loan l
            JOIN client_accounts ca ON l.account_id = ca.account_id
            LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
            WHERE l.loan_id = ?
        ");
        
        $query->bind_param("i", $loanId);
        $query->execute();
        $result = $query->get_result();
        $loanDetails = $result->fetch_assoc();
        
        if (!$loanDetails) {
            error_log("No loan found with ID: $loanId");
            return false;
        }
        
        // Check if loan is disbursed
        if ($loanDetails['status'] < 2) {
            $loanDetails['current_due_amount'] = 0;
            $loanDetails['next_due_amount'] = 0;
            $loanDetails['current_due_date'] = null;
            $loanDetails['next_due_date'] = null;
            $loanDetails['is_overdue'] = false;
            $loanDetails['accumulated_defaults'] = 0;
            $loanDetails['message'] = 'Loan not yet disbursed';
            return $loanDetails;
        }
        
        // Get unpaid/partial installments from loan_schedule in chronological order
        $scheduleQuery = $this->conn->prepare("
            SELECT 
                *,
                (amount - COALESCE(repaid_amount, 0)) as remaining_amount
            FROM loan_schedule 
            WHERE loan_id = ? 
            AND (status = 'unpaid' OR status = 'partial')
            AND (amount - COALESCE(repaid_amount, 0)) > 0.01
            ORDER BY due_date ASC
        ");
        
        if ($scheduleQuery) {
            $scheduleQuery->bind_param("i", $loanId);
            $scheduleQuery->execute();
            $schedules = $scheduleQuery->get_result()->fetch_all(MYSQLI_ASSOC);
            
            if (!empty($schedules)) {
                $today = date('Y-m-d');
                
                // FIXED: Calculate outstanding balance from remaining unpaid principal in loan schedule
                $outstandingBalanceQuery = $this->conn->prepare("
                    SELECT SUM(principal) as remaining_principal
                    FROM loan_schedule 
                    WHERE loan_id = ? 
                    AND status != 'paid'
                ");
                $outstandingBalanceQuery->bind_param("i", $loanId);
                $outstandingBalanceQuery->execute();
                $balanceResult = $outstandingBalanceQuery->get_result()->fetch_assoc();
                $loanDetails['outstanding_balance'] = floatval($balanceResult['remaining_principal'] ?? 0);
                
                // Current due is the EXACT amount from the first unpaid installment
                $currentInstallment = $schedules[0];
                $currentDueAmount = floatval($currentInstallment['amount']); // Full installment amount (principal + interest)
                $currentDueDate = $currentInstallment['due_date'];
                $isOverdue = ($currentDueDate <= $today);
                
                // Next due is the EXACT amount from the second unpaid installment
                $nextDueAmount = 0;
                $nextDueDate = null;
                if (count($schedules) > 1) {
                    $nextInstallment = $schedules[1];
                    $nextDueAmount = floatval($nextInstallment['amount']);
                    $nextDueDate = $nextInstallment['due_date'];
                }
                
                // Handle partial payments - if already partially paid, show remaining amount
                $remainingForCurrent = floatval($currentInstallment['remaining_amount']);
                
                $loanDetails['current_due_amount'] = $remainingForCurrent; // Amount still owed on current installment
                $loanDetails['next_due_amount'] = $nextDueAmount; // Full amount of next installment
                $loanDetails['current_due_date'] = $currentDueDate;
                $loanDetails['next_due_date'] = $nextDueDate;
                $loanDetails['is_overdue'] = $isOverdue;
                
                // Calculate total accumulated defaults (all overdue unpaid amounts)
                $defaultQuery = $this->conn->prepare("
                    SELECT SUM(amount - COALESCE(repaid_amount, 0)) as total_defaults 
                    FROM loan_schedule 
                    WHERE loan_id = ? 
                    AND due_date <= CURDATE() 
                    AND status != 'paid'
                    AND (amount - COALESCE(repaid_amount, 0)) > 0.01
                ");
                $defaultQuery->bind_param("i", $loanId);
                $defaultQuery->execute();
                $defaultResult = $defaultQuery->get_result()->fetch_assoc();
                $loanDetails['accumulated_defaults'] = floatval($defaultResult['total_defaults'] ?? 0);
                
                // Add detailed installment info for debugging
                $loanDetails['current_installment_details'] = [
                    'full_amount' => floatval($currentInstallment['amount']),
                    'already_paid' => floatval($currentInstallment['repaid_amount'] ?? 0),
                    'remaining_amount' => $remainingForCurrent,
                    'principal' => floatval($currentInstallment['principal'] ?? 0),
                    'interest' => floatval($currentInstallment['interest'] ?? 0)
                ];
                
            } else {
                // No unpaid installments - loan fully paid
                $loanDetails['current_due_amount'] = 0;
                $loanDetails['next_due_amount'] = 0;
                $loanDetails['current_due_date'] = null;
                $loanDetails['next_due_date'] = null;
                $loanDetails['is_overdue'] = false;
                $loanDetails['accumulated_defaults'] = 0;
                $loanDetails['outstanding_balance'] = 0; // Set to 0 when fully paid
            }
        }
        
        return $loanDetails;
        
    } catch (Exception $e) {
        error_log("Error in getLoanDetailsForRepayment: " . $e->getMessage());
        return false;
    }
}


    /**
     * Get due amount for specific loan
     */
    public function getLoanNextDueAmount($loanId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT ls.amount as scheduled_amount, l.monthly_payment, l.outstanding_balance
                FROM loan l
                LEFT JOIN loan_schedule ls ON l.loan_id = ls.loan_id AND ls.status = 'unpaid'
                WHERE l.loan_id = ?
                ORDER BY ls.due_date ASC
                LIMIT 1
            ");
            $stmt->bind_param("i", $loanId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row) {
                $dueAmount = $row['scheduled_amount'] ?? $row['monthly_payment'];
                return min($dueAmount, $row['outstanding_balance']);
            } else {
                return 0;
            }
        } catch (Exception $e) {
            error_log("Error in getLoanNextDueAmount: " . $e->getMessage());
            return 0;
        }
    }

   /**
 * Fixed repayLoan method that prevents duplicate transaction records
 */
public function repayLoan($accountId, $loanId, $repayAmount, $paymentMode, $servedBy, $receiptNumber) {
    error_log("Model: Repay Loan - accountId=$accountId, loanId=$loanId, repayAmount=$repayAmount");
    
    // Validate loan exists and get loan details
    $loanQuery = $this->conn->prepare("SELECT loan_id, account_id, ref_no, status FROM loan WHERE loan_id = ?");
    $loanQuery->bind_param("i", $loanId);
    $loanQuery->execute();
    $loanResult = $loanQuery->get_result();
    
    if ($loanResult->num_rows === 0) {
        error_log("Loan not found with ID: $loanId");
        return ['status' => 'error', 'message' => "Loan not found with ID: $loanId"];
    }
    
    $loanData = $loanResult->fetch_assoc();
    
    if ($loanData['status'] < 2) {
        return ['status' => 'error', 'message' => 'Cannot process repayment. Loan must be disbursed first (status >= 2).'];
    }
    
    $loanRefNo = $loanData['ref_no'];
    
    $this->conn->begin_transaction();
    
    try {
        // CORRECTED: Get unpaid/partial schedule entries in strict chronological order
        $scheduleStmt = $this->conn->prepare("
            SELECT * FROM loan_schedule 
            WHERE loan_id = ? 
            AND (status = 'unpaid' OR status = 'partial')
            AND (amount - COALESCE(repaid_amount, 0)) > 0.01
            ORDER BY due_date ASC
        ");
        
        $scheduleStmt->bind_param("i", $loanId);
        $scheduleStmt->execute();
        $schedules = $scheduleStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($schedules)) {
            throw new Exception("No outstanding installments found for this loan");
        }
        
        $remainingPayment = floatval($repayAmount);
        $today = date('Y-m-d');
        $updatedInstallments = 0;
        
        // CORRECTED: Process payments strictly in chronological order
        foreach ($schedules as $schedule) {
            if ($remainingPayment <= 0.01) break; // Stop when payment is exhausted
            
            $dueAmount = floatval($schedule['amount']);
            $currentRepaidAmount = floatval($schedule['repaid_amount'] ?? 0);
            $stillOwed = $dueAmount - $currentRepaidAmount;
            
            if ($stillOwed <= 0.01) continue; // Skip if already fully paid
            
            $paymentForThisInstallment = min($remainingPayment, $stillOwed);
            $newRepaidAmount = $currentRepaidAmount + $paymentForThisInstallment;
            
            // CORRECTED: Determine status with proper rounding tolerance
            $newStatus = 'unpaid';
            $paidDate = null;
            
            if (abs($newRepaidAmount - $dueAmount) <= 0.01) { 
                // Fully paid (allowing for small rounding differences)
                $newStatus = 'paid';
                $paidDate = $today;
                $newRepaidAmount = $dueAmount; // Ensure exact amount
            } elseif ($newRepaidAmount > 0.01) {
                $newStatus = 'partial';
            }
            
            // CORRECTED: Calculate default amount properly
            $newDefaultAmount = 0;
            if ($newStatus !== 'paid' && strtotime($schedule['due_date']) < strtotime($today)) {
                $newDefaultAmount = max(0, $dueAmount - $newRepaidAmount);
            }
            
            // Update schedule entry
            $updateQuery = "
                UPDATE loan_schedule 
                SET repaid_amount = ?,
                    default_amount = ?,
                    status = ?,
                    paid_date = ?
                WHERE id = ?
            ";
            
            $updateScheduleStmt = $this->conn->prepare($updateQuery);
            if (!$updateScheduleStmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            
            $updateScheduleStmt->bind_param(
                "ddssi",
                $newRepaidAmount,
                $newDefaultAmount,
                $newStatus,
                $paidDate,
                $schedule['id']
            );
            
            if (!$updateScheduleStmt->execute()) {
                error_log("MySQL Error: " . $updateScheduleStmt->error);
                throw new Exception("Failed to update loan schedule: " . $updateScheduleStmt->error);
            }
            
            $remainingPayment -= $paymentForThisInstallment;
            $updatedInstallments++;
            
            error_log("Updated installment {$schedule['id']}: paid {$paymentForThisInstallment}, new total: {$newRepaidAmount}, status: {$newStatus}");
            
            // CORRECTED: If this installment is fully paid, move to next one
            if ($newStatus === 'paid') {
                error_log("Installment {$schedule['id']} fully paid, moving to next");
            } else {
                // If partial payment, stop here (don't move to next installment)
                error_log("Partial payment on installment {$schedule['id']}, remaining: " . ($dueAmount - $newRepaidAmount));
                break;
            }
        }
        
        // Insert loan repayment record
        $repaymentStmt = $this->conn->prepare("
            INSERT INTO loan_repayments (
                loan_id, amount_repaid, date_paid, payment_mode, served_by, receipt_number
            ) VALUES (?, ?, NOW(), ?, ?, ?)
        ");
        
        $repaymentStmt->bind_param("idsss", 
            $loanId,
            $repayAmount,
            $paymentMode,
            $servedBy,
            $receiptNumber
        );
        
        if (!$repaymentStmt->execute()) {
            throw new Exception("Failed to record loan repayment for loan ID: $loanId");
        }
        
        // Insert transaction record safely
        $description = "Loan repayment for loan ref #" . $loanRefNo;
        $this->addTransactionSafe(
            $accountId,
            'Loan Repayment',
            $repayAmount,
            $description,
            $receiptNumber
        );
        
        // CORRECTED: Check if loan is fully paid
        $unpaidStmt = $this->conn->prepare("
            SELECT COUNT(*) as unpaid_count, SUM(amount - COALESCE(repaid_amount, 0)) as total_remaining
            FROM loan_schedule
            WHERE loan_id = ? AND status != 'paid'
        ");
        
        $unpaidStmt->bind_param("i", $loanId);
        $unpaidStmt->execute();
        $unpaidResult = $unpaidStmt->get_result()->fetch_assoc();
        
        $unpaidCount = intval($unpaidResult['unpaid_count']);
        $totalRemaining = floatval($unpaidResult['total_remaining'] ?? 0);
        
        // Mark loan as completed if no significant amount remaining
        if ($unpaidCount == 0 || $totalRemaining <= 0.01) {
            $updateLoanStmt = $this->conn->prepare("
                UPDATE loan SET status = 3 WHERE loan_id = ?
            ");
            $updateLoanStmt->bind_param("i", $loanId);
            $updateLoanStmt->execute();
            error_log("Loan {$loanId} marked as fully paid (status 3)");
        }
        
        $this->conn->commit();
        
        return [
            'status' => 'success',
            'message' => 'Loan repayment successful',
            'repaymentId' => $this->conn->insert_id,
            'installments_updated' => $updatedInstallments,
            'remaining_payment' => $remainingPayment
        ];
        
    } catch (Exception $e) {
        $this->conn->rollback();
        error_log("Error in repayLoan: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

    /**
     * Get loan repayments for account
     */
    public function getLoanRepayments($accountId) {
        $stmt = $this->conn->prepare("
            SELECT 
                lr.*, 
                l.ref_no as loan_ref_no,
                COALESCE(u.firstname, lr.served_by, 'Unknown') as served_by_name
            FROM loan_repayments lr
            JOIN loan l ON lr.loan_id = l.loan_id
            LEFT JOIN user u ON lr.served_by = u.user_id
            WHERE l.account_id = ?
            ORDER BY lr.date_paid DESC
        ");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get repayment details by ID
     */
    public function getRepaymentDetails($repaymentId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT lr.*, l.ref_no as loan_ref_no,
                       ca.first_name, ca.last_name,
                       lr.date_paid,
                       u.username as served_by
                FROM loan_repayments lr
                JOIN loan l ON lr.loan_id = l.loan_id
                JOIN client_accounts ca ON l.account_id = ca.account_id
                LEFT JOIN user u ON lr.served_by = u.user_id
                WHERE lr.id = ?
            ");
            
            $stmt->bind_param("i", $repaymentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $repayment = $result->fetch_assoc();
            
            return [
                'status' => 'success',
                'repayment' => $repayment
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete loan repayment and update schedule
     */
public function deleteRepayment($repaymentId, $loanId, $deletedAmount) {
    $this->conn->begin_transaction();
    
    try {
        // Step 1: Get repayment details before deletion
        $getRepaymentStmt = $this->conn->prepare("
            SELECT lr.*, l.ref_no as loan_ref_no, l.status as loan_status
            FROM loan_repayments lr
            JOIN loan l ON lr.loan_id = l.loan_id
            WHERE lr.id = ?
        ");
        $getRepaymentStmt->bind_param("i", $repaymentId);
        $getRepaymentStmt->execute();
        $repaymentDetails = $getRepaymentStmt->get_result()->fetch_assoc();
        
        if (!$repaymentDetails) {
            throw new Exception("Repayment record not found");
        }
        
        $deletedAmountFloat = floatval($deletedAmount);
        $repaymentDate = $repaymentDetails['date_paid'];
        $receiptNumber = $repaymentDetails['receipt_number'];
        
        error_log("Deleting repayment ID: $repaymentId, Amount: $deletedAmountFloat, Date: $repaymentDate");
        
        // Step 2: Find all loan schedule entries that were affected by this specific repayment
        // We'll identify them by finding paid entries that match the repayment date
        $affectedScheduleStmt = $this->conn->prepare("
            SELECT id, due_date, amount, principal, interest, repaid_amount, status
            FROM loan_schedule 
            WHERE loan_id = ? 
            AND (paid_date = ? OR (status = 'paid' AND repaid_amount > 0))
            ORDER BY due_date ASC
        ");
        $affectedScheduleStmt->bind_param("is", $loanId, $repaymentDate);
        $affectedScheduleStmt->execute();
        $affectedEntries = $affectedScheduleStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        error_log("Found " . count($affectedEntries) . " potentially affected schedule entries");
        
        // Step 3: Reset affected schedule entries to unpaid status
        // Work backwards through the payment to identify which entries to reset
        $remainingDeletionAmount = $deletedAmountFloat;
        $entriesToReset = [];
        
        // Process affected entries in reverse chronological order (last paid first)
        $reversedEntries = array_reverse($affectedEntries);
        
        foreach ($reversedEntries as $entry) {
            if ($remainingDeletionAmount <= 0) break;
            
            $entryAmount = floatval($entry['amount']);
            $currentlyPaid = floatval($entry['repaid_amount']);
            
            if ($currentlyPaid > 0) {
                // This entry has payments that need to be reversed
                $amountToReset = min($remainingDeletionAmount, $currentlyPaid);
                $newPaidAmount = max(0, $currentlyPaid - $amountToReset);
                
                // Determine new status
                $newStatus = 'unpaid';
                $newPaidDate = null;
                
                if ($newPaidAmount > 0.01) {
                    $newStatus = (abs($newPaidAmount - $entryAmount) <= 0.01) ? 'paid' : 'partial';
                    if ($newStatus === 'paid') {
                        $newPaidDate = $entry['paid_date']; // Keep original paid date if still fully paid
                    }
                }
                
                // Calculate default amount
                $today = date('Y-m-d');
                $newDefaultAmount = 0;
                if ($newStatus !== 'paid' && strtotime($entry['due_date']) < strtotime($today)) {
                    $newDefaultAmount = $entryAmount - $newPaidAmount;
                }
                
                $entriesToReset[] = [
                    'id' => $entry['id'],
                    'due_date' => $entry['due_date'],
                    'new_repaid_amount' => $newPaidAmount,
                    'new_status' => $newStatus,
                    'new_paid_date' => $newPaidDate,
                    'new_default_amount' => $newDefaultAmount,
                    'reset_amount' => $amountToReset
                ];
                
                $remainingDeletionAmount -= $amountToReset;
                
                error_log("Entry {$entry['id']} (due: {$entry['due_date']}): Reset $amountToReset, New paid: $newPaidAmount, Status: $newStatus");
            }
        }
        
        // Step 4: Update loan schedule entries
        $updateScheduleStmt = $this->conn->prepare("
            UPDATE loan_schedule 
            SET repaid_amount = ?, 
                status = ?, 
                paid_date = ?,
                default_amount = ?
            WHERE id = ?
        ");
        
        foreach ($entriesToReset as $resetEntry) {
            $updateScheduleStmt->bind_param(
                "dssdi",
                $resetEntry['new_repaid_amount'],
                $resetEntry['new_status'],
                $resetEntry['new_paid_date'],
                $resetEntry['new_default_amount'],
                $resetEntry['id']
            );
            
            if (!$updateScheduleStmt->execute()) {
                throw new Exception("Failed to update schedule entry ID {$resetEntry['id']}: " . $updateScheduleStmt->error);
            }
        }
        
        // Step 5: Delete from loan_repayments table
        $deleteRepaymentStmt = $this->conn->prepare("DELETE FROM loan_repayments WHERE id = ?");
        $deleteRepaymentStmt->bind_param("i", $repaymentId);
        
        if (!$deleteRepaymentStmt->execute()) {
            throw new Exception("Failed to delete repayment record");
        }
        
        // Step 6: Delete from transactions table
        $deleteTransactionStmt = $this->conn->prepare("
            DELETE FROM transactions 
            WHERE receipt_number = ? 
            AND type = 'Loan Repayment' 
            AND ABS(amount - ?) < 0.01
        ");
        $deleteTransactionStmt->bind_param("sd", $receiptNumber, $deletedAmountFloat);
        $deleteTransactionStmt->execute();
        
        $deletedTransactions = $deleteTransactionStmt->affected_rows;
        error_log("Deleted $deletedTransactions transaction records");
        
        // Step 7: Update loan status if needed
        // Check if loan should no longer be marked as fully paid
        $unpaidCountStmt = $this->conn->prepare("
            SELECT COUNT(*) as unpaid_count 
            FROM loan_schedule 
            WHERE loan_id = ? AND status != 'paid'
        ");
        $unpaidCountStmt->bind_param("i", $loanId);
        $unpaidCountStmt->execute();
        $unpaidResult = $unpaidCountStmt->get_result()->fetch_assoc();
        $unpaidCount = intval($unpaidResult['unpaid_count']);
        
        if ($unpaidCount > 0 && $repaymentDetails['loan_status'] == 3) {
            // Loan was marked as fully paid but now has unpaid installments
            $updateLoanStmt = $this->conn->prepare("UPDATE loan SET status = 2 WHERE loan_id = ?");
            $updateLoanStmt->bind_param("i", $loanId);
            $updateLoanStmt->execute();
            error_log("Loan $loanId status changed from fully paid (3) to disbursed (2) due to unpaid installments");
        }
        
        $this->conn->commit();
        
        return [
            'status' => 'success',
            'message' => 'Repayment deleted successfully. Affected schedule entries have been reset to unpaid status.',
            'details' => [
                'repayment_id' => $repaymentId,
                'deleted_amount' => $deletedAmountFloat,
                'entries_reset' => count($entriesToReset),
                'transactions_deleted' => $deletedTransactions,
                'unpaid_installments_remaining' => $unpaidCount
            ]
        ];
        
    } catch (Exception $e) {
        $this->conn->rollback();
        error_log("Error in simplified deleteRepayment: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}



    // =====================================
    // LOAN SCHEDULE OPERATIONS
    // =====================================

    /**
     * Get loan schedule for specific loan
     */
   public function getLoanSchedule($loanId) {
    try {
        $stmt = $this->conn->prepare("
            SELECT 
                ls.*,
                COALESCE(ls.repaid_amount, 0) as repaid_amount,
                COALESCE(ls.default_amount, 0) as default_amount,
                CASE 
                    WHEN ls.status = 'paid' THEN ls.paid_date
                    ELSE NULL 
                END as paid_date,
                CASE 
                    WHEN ls.due_date <= CURDATE() AND ls.status != 'paid' THEN 1
                    ELSE 0
                END as is_overdue
            FROM loan_schedule ls
            WHERE ls.loan_id = ? 
            ORDER BY ls.due_date ASC
        ");
        
        $stmt->bind_param("i", $loanId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $schedule = array();
        while ($row = $result->fetch_assoc()) {
            $schedule[] = array(
                'due_date' => $row['due_date'],
                'principal' => number_format($row['principal'], 2),
                'interest' => number_format($row['interest'], 2),
                'amount' => number_format($row['amount'], 2),
                'balance' => number_format($row['balance'], 2),
                'repaid_amount' => number_format($row['repaid_amount'], 2),
                'default_amount' => number_format($row['default_amount'], 2),
                'status' => $row['status'],
                'paid_date' => $row['paid_date'],
                'is_overdue' => $row['is_overdue']
            );
        }
        
        return $schedule;
        
    } catch (Exception $e) {
        error_log("Error getting loan schedule: " . $e->getMessage());
        return false;
    }
}

    /**
     * Get loan schedule entries (raw data)
     */
    public function getLoanScheduleEntries($loanId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM loan_schedule 
            WHERE loan_id = ? 
            ORDER BY due_date ASC
        ");
        $stmt->bind_param("i", $loanId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Update loan schedule entry
     */
    public function updateLoanScheduleEntry($scheduleData) {
        $stmt = $this->conn->prepare("
            UPDATE loan_schedule 
            SET repaid_amount = ?,
                default_amount = ?,
                status = ?,
                paid_date = CASE WHEN ? = 'paid' THEN ? ELSE NULL END
            WHERE loan_id = ? AND due_date = ?
        ");
        
        $paidDate = $scheduleData['status'] === 'paid' ? $scheduleData['paid_date'] : null;
        
        $stmt->bind_param(
            "ddsssis",
            $scheduleData['repaid_amount'],
            $scheduleData['default_amount'],
            $scheduleData['status'],
            $scheduleData['status'],
            $paidDate,
            $scheduleData['loan_id'],
            $scheduleData['due_date']
        );
        
        return $stmt->execute();
    }

    /**
     * Create loan schedule for new loan
     */
    public function createLoanSchedule($loanId) {
        try {
            $loanQuery = "SELECT l.*, lp.interest_rate 
                         FROM loan l
                         JOIN loan_products lp ON l.loan_product_id = lp.id
                         WHERE l.loan_id = ?";
            
            $stmt = $this->conn->prepare($loanQuery);
            $stmt->bind_param("i", $loanId);
            $stmt->execute();
            $loan = $stmt->get_result()->fetch_assoc();
            
            if (!$loan) {
                throw new Exception("Loan not found");
            }
            
            $totalLoanAmount = $loan['amount'];
            $loanTerm = $loan['loan_term'];
            $monthlyPrincipal = round($totalLoanAmount / $loanTerm, 2);
            $interestRate = $loan['interest_rate'] / 100 / 12;
            
            $startDate = new DateTime($loan['meeting_date'] ?? $loan['date_created']);
            $paymentDate = clone $startDate;
            $paymentDate->modify('+1 month');
            
            $principalsPaid = 0;
            $remainingBalance = $totalLoanAmount;
            
            $this->conn->query("DELETE FROM loan_schedule WHERE loan_id = $loanId");
            
            $insertStmt = $this->conn->prepare("
                INSERT INTO loan_schedule (
                    loan_id, due_date, principal, interest, 
                    amount, balance, status
                ) VALUES (?, ?, ?, ?, ?, ?, 'unpaid')
            ");
            
            for ($i = 0; $i < $loanTerm; $i++) {
                $interest = round(($totalLoanAmount - $principalsPaid) * $interestRate, 2);
                $dueAmount = $monthlyPrincipal + $interest;
                $dueDate = $paymentDate->format('Y-m-d');
                
                $insertStmt->bind_param(
                    "isdddd",
                    $loanId,
                    $dueDate,
                    $monthlyPrincipal,
                    $interest,
                    $dueAmount,
                    $remainingBalance
                );
                
                if (!$insertStmt->execute()) {
                    throw new Exception("Failed to insert schedule entry: " . $insertStmt->error);
                }
                
                $principalsPaid += $monthlyPrincipal;
                $remainingBalance -= $monthlyPrincipal;
                if ($remainingBalance < 0) $remainingBalance = 0;
                
                $paymentDate->modify('+1 month');
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error creating loan schedule: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unpaid installment count for specific loan
     */
    public function getUnpaidInstallmentCount($loanId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as unpaid_count 
            FROM loan_schedule 
            WHERE loan_id = ? AND status != 'paid'
        ");
        $stmt->bind_param("i", $loanId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['unpaid_count'];
    }

    // =====================================
    // RECEIPT AND DETAILS OPERATIONS
    // =====================================

    /**
     * Get savings receipt details
     */
    public function getSavingsReceiptDetails($savingsId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT s.*, 
                       CONCAT(c.first_name, ' ', c.last_name) as client_name
                FROM savings s
                JOIN client_accounts c ON s.account_id = c.account_id
                WHERE s.saving_id = ?
            ");

            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $this->conn->error);
            }

            $stmt->bind_param("i", $savingsId);
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing query: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $details = $result->fetch_assoc();

            if (!$details) {
                throw new Exception("No receipt details found for savings ID: $savingsId");
            }

            return [
                'status' => 'success',
                'details' => [
                    'receiptNumber' => $details['receipt_number'],
                    'date' => $details['date'],
                    'clientName' => $details['client_name'],
                    'accountType' => $details['account_type'],
                    'amount' => $details['amount'],
                    'paymentMode' => $details['payment_mode'],
                    'servedBy' => $details['served_by']
                ]
            ];

        } catch (Exception $e) {
            error_log("Error in getSavingsReceiptDetails: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get withdrawal receipt details
     */
    public function getWithdrawalReceiptDetails($withdrawalId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT s.*, 
                       CONCAT(c.first_name, ' ', c.last_name) as client_name
                FROM savings s
                JOIN client_accounts c ON s.account_id = c.account_id
                WHERE s.saving_id = ? AND s.type = 'Withdrawal'
            ");

            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $this->conn->error);
            }

            $stmt->bind_param("i", $withdrawalId);
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing query: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $details = $result->fetch_assoc();

            if (!$details) {
                throw new Exception("No receipt details found for withdrawal ID: $withdrawalId");
            }

            $totalAmount = $details['amount'] + ($details['withdrawal_fee'] ?? 0);

            return [
                'status' => 'success',
                'details' => [
                    'receiptNumber' => $details['receipt_number'],
                    'date' => $details['date'],
                    'clientName' => $details['client_name'],
                    'accountType' => $details['account_type'],
                    'amount' => $details['amount'],
                    'withdrawalFee' => $details['withdrawal_fee'] ?? 0,
                    'totalAmount' => $totalAmount,
                    'paymentMode' => $details['payment_mode'],
                    'servedBy' => $details['served_by']
                ]
            ];

        } catch (Exception $e) {
            error_log("Error in getWithdrawalReceiptDetails: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get savings details for receipt
     */
    public function getSavingsDetails($savingsId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT s.*, 
                       CONCAT(ca.first_name, ' ', ca.last_name) as client_name,
                       u.username as served_by
                FROM savings s
                JOIN client_accounts ca ON s.account_id = ca.account_id
                LEFT JOIN user u ON s.served_by = u.user_id
                WHERE s.saving_id = ?
            ");
            
            $stmt->bind_param("i", $savingsId);
            $stmt->execute();
            $result = $stmt->get_result();
            $details = $result->fetch_assoc();
            
            if (!$details) {
                throw new Exception("Receipt details not found");
            }
            
            return [
                'status' => 'success',
                'details' => $details
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get available balance for account type
     */
    public function getAvailableBalance($accountId, $accountType) {
        try {
            $query = "SELECT 
                COALESCE(SUM(
                    CASE 
                        WHEN type = 'Savings' THEN amount 
                        WHEN type = 'Withdrawal' THEN -amount 
                    END
                ), 0) as balance
                FROM savings 
                WHERE account_id = ? AND account_type = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("is", $accountId, $accountType);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            return ['status' => 'success', 'balance' => $result['balance'] ?? 0];
        } catch (Exception $e) {
            error_log("Error in getAvailableBalance: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get transaction receipt
     */
    public function getTransactionReceipt($transactionId, $type) {
        try {
            $query = "SELECT 
                t.*,
                CONCAT(c.first_name, ' ', c.last_name) as client_name,
                t.receipt_number,
                s.account_type,
                s.payment_mode,
                s.served_by
                FROM transactions t
                JOIN client_accounts c ON t.account_id = c.account_id
                LEFT JOIN savings s ON t.receipt_number = s.receipt_number
                WHERE t.transaction_id = ? AND t.type = ?";
                
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("is", $transactionId, $type);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if (!$result) {
                throw new Exception("Receipt details not found");
            }
            
            return [
                'status' => 'success',
                'details' => [
                    'receiptNumber' => $result['receipt_number'],
                    'date' => $result['date'],
                    'clientName' => $result['client_name'],
                    'accountType' => $result['account_type'],
                    'amount' => $result['amount'],
                    'paymentMode' => $result['payment_mode'],
                    'transactionType' => $result['type'],
                    'servedBy' => $result['served_by']
                ]
            ];
        } catch (Exception $e) {
            error_log("Error in getTransactionReceipt: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // =====================================
    // UTILITY AND HELPER METHODS
    // =====================================

    /**
     * Get data for charts/graphs
     */
    public function getSavingsData($accountId, $filter, $startDate = null, $endDate = null) {
        $query = "SELECT amount, date FROM savings WHERE account_id = ?";
        $params = [$accountId];
        $types = "i";

        switch ($filter) {
            case 'week':
                $query .= " AND date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $query .= " AND date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $query .= " AND date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                break;
            case 'custom':
                $query .= " AND date BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
                $types .= "ss";
                break;
        }

        $query .= " ORDER BY date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get transaction data for charts
     */
    public function getTransactionData($accountId, $filter) {
        $query = "SELECT type, amount, date FROM transactions WHERE account_id = ?";
        $params = [$accountId];
        $types = "i";

        if ($filter != 'all') {
            $query .= " AND type = ?";
            $params[] = $filter;
            $types .= "s";
        }

        $query .= " ORDER BY date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get next available shareholder number
     */
    public function getNextShareholderNumber() {
        try {
            $query = "SELECT MAX(CAST(shareholder_no AS UNSIGNED)) AS max_no FROM client_accounts";
            $result = $this->conn->query($query);
            
            if ($result) {
                $row = $result->fetch_assoc();
                $next_number = str_pad(($row['max_no'] + 1), 3, '0', STR_PAD_LEFT);
                
                return [
                    'status' => 'success',
                    'next_number' => $next_number,
                    'message' => 'Next shareholder number generated successfully'
                ];
            } else {
                throw new Exception('Error fetching shareholder numbers: ' . $this->conn->error);
            }
        } catch (Exception $e) {
            error_log("Error in getNextShareholderNumber: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if shareholder number exists
     */
    public function checkShareholderNumberExists($shareholder_no) {
        try {
            $shareholder_no = trim($shareholder_no);
            
            if (empty($shareholder_no)) {
                return [
                    'status' => 'error',
                    'message' => 'Shareholder number is required'
                ];
            }
            
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM client_accounts WHERE shareholder_no = ?");
            $stmt->bind_param("s", $shareholder_no);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                
                return [
                    'status' => 'success',
                    'exists' => $row['count'] > 0,
                    'message' => $row['count'] > 0 ? 'Shareholder number already exists' : 'Shareholder number is available'
                ];
            } else {
                throw new Exception('Error checking shareholder number: ' . $stmt->error);
            }
            
        } catch (Exception $e) {
            error_log("Error in checkShareholderNumberExists: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Add transaction record
     */
    private function addTransaction($accountId, $type, $amount, $description, $receiptNumber) {
        $stmt = $this->conn->prepare("
            INSERT INTO transactions (
                account_id,
                type,
                amount,
                description,
                receipt_number,
                date
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
    
        $stmt->bind_param("isdss", 
            $accountId,
            $type,
            $amount,
            $description,
            $receiptNumber
        );
    
        return $stmt->execute();
    }


    /**
     * Get savings record details for validation
     */
    public function getSavingsRecordDetails($recordId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT s.*, 
                       CONCAT(ca.first_name, ' ', ca.last_name) as client_name
                FROM savings s
                JOIN client_accounts ca ON s.account_id = ca.account_id
                WHERE s.saving_id = ?
            ");
            
            $stmt->bind_param("i", $recordId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_assoc();
            
        } catch (Exception $e) {
            error_log("Error in getSavingsRecordDetails: " . $e->getMessage());
            return false;
        }
    }

  /**
     * Delete savings or withdrawal record and all related data
     */
    public function deleteSavingsRecord($recordId, $recordType, $accountId) {
        $this->conn->begin_transaction();
        
        try {
            // First, get the record details for validation
            $recordDetails = $this->getSavingsRecordDetails($recordId);
            
            if (!$recordDetails) {
                throw new Exception("Record not found with ID: $recordId");
            }

            // Validate that the record belongs to the specified account
            if ($recordDetails['account_id'] != $accountId) {
                throw new Exception("Record does not belong to the specified account");
            }

            // Validate record type matches
            if ($recordDetails['type'] !== $recordType) {
                throw new Exception("Record type mismatch. Expected: $recordType, Found: " . $recordDetails['type']);
            }

            $receiptNumber = $recordDetails['receipt_number'];
            $amount = $recordDetails['amount'];
            $accountType = $recordDetails['account_type'];

            // Step 1: Delete related transactions based on receipt number
            $deleteTransactionsStmt = $this->conn->prepare("
                DELETE FROM transactions 
                WHERE receipt_number = ? 
                AND account_id = ?
            ");
            
            $deleteTransactionsStmt->bind_param("si", $receiptNumber, $accountId);
            
            if (!$deleteTransactionsStmt->execute()) {
                throw new Exception("Failed to delete related transactions: " . $deleteTransactionsStmt->error);
            }

            $deletedTransactions = $deleteTransactionsStmt->affected_rows;

            // Step 2: Delete the savings/withdrawal record
            $deleteSavingsStmt = $this->conn->prepare("
                DELETE FROM savings 
                WHERE saving_id = ? 
                AND account_id = ? 
                AND type = ?
            ");
            
            $deleteSavingsStmt->bind_param("iis", $recordId, $accountId, $recordType);
            
            if (!$deleteSavingsStmt->execute()) {
                throw new Exception("Failed to delete savings record: " . $deleteSavingsStmt->error);
            }

            if ($deleteSavingsStmt->affected_rows === 0) {
                throw new Exception("No savings record was deleted. Record may not exist or belong to this account.");
            }

            $this->conn->commit();

            return [
                'status' => 'success',
                'message' => "$recordType record and related transactions deleted successfully",
                'deletedRecord' => [
                    'recordId' => $recordId,
                    'receiptNumber' => $receiptNumber,
                    'amount' => $amount,
                    'accountType' => $accountType,
                    'type' => $recordType,
                    'deletedTransactions' => $deletedTransactions
                ]
            ];

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in deleteSavingsRecord: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }



    /**
     * Validate if a record can be safely deleted
     */
    public function validateRecordDeletion($recordId, $accountId) {
        try {
            $recordDetails = $this->getSavingsRecordDetails($recordId);
            
            if (!$recordDetails) {
                return [
                    'canDelete' => false,
                    'reason' => 'Record not found'
                ];
            }

            if ($recordDetails['account_id'] != $accountId) {
                return [
                    'canDelete' => false,
                    'reason' => 'Record does not belong to specified account'
                ];
            }

            // Check if this is a very recent record (within last 24 hours might be safer to delete)
            $recordDate = strtotime($recordDetails['date']);
            $dayOld = time() - (24 * 60 * 60);
            
            if ($recordDate < $dayOld) {
                return [
                    'canDelete' => true,
                    'reason' => 'Record is older than 24 hours',
                    'warning' => 'Deleting older records may affect historical data accuracy'
                ];
            }

            return [
                'canDelete' => true,
                'reason' => 'Record can be safely deleted',
                'recordDetails' => $recordDetails
            ];

        } catch (Exception $e) {
            error_log("Error in validateRecordDeletion: " . $e->getMessage());
            return [
                'canDelete' => false,
                'reason' => 'Error validating record: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get count of related records that would be affected by deletion
     */
    public function getRelatedRecordsCount($receiptNumber, $accountId) {
        try {
            // Count related transactions
            $transactionStmt = $this->conn->prepare("
                SELECT COUNT(*) as transaction_count 
                FROM transactions 
                WHERE receipt_number = ? AND account_id = ?
            ");
            
            $transactionStmt->bind_param("si", $receiptNumber, $accountId);
            $transactionStmt->execute();
            $transactionResult = $transactionStmt->get_result()->fetch_assoc();

            return [
                'status' => 'success',
                'relatedTransactions' => $transactionResult['transaction_count'] ?? 0
            ];

        } catch (Exception $e) {
            error_log("Error in getRelatedRecordsCount: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Transaction management methods
     */
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }

    public function commitTransaction() {
        return $this->conn->commit();
    }

    public function rollbackTransaction() {
        return $this->conn->rollback();
    }

    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Get last error
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Close database connection
     */
    public function __destruct() {
    // Don't close or pool the connection in destructor
    // Let the db_connect class handle connection lifecycle
    $this->conn = null;
}
}
?>