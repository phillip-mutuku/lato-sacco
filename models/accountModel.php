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
 * BULLETPROOF: Process loan repayment with full data integrity
 */
public function repayLoan($accountId, $loanId, $repayAmount, $paymentMode, $servedBy, $receiptNumber) {
    error_log("=== LOAN REPAYMENT STARTED ===");
    error_log("Loan ID: $loanId, Amount: $repayAmount, Receipt: $receiptNumber");
    
    try {
        // Start transaction
        $this->conn->begin_transaction();
        
        // Step 1: Validate loan exists and is disbursed
        $loanQuery = $this->conn->prepare("
            SELECT l.loan_id, l.account_id, l.ref_no, l.status, l.amount as loan_amount
            FROM loan l
            WHERE l.loan_id = ?
        ");
        
        if (!$loanQuery) {
            throw new Exception("Failed to prepare loan query: " . $this->conn->error);
        }
        
        $loanQuery->bind_param("i", $loanId);
        $loanQuery->execute();
        $loanData = $loanQuery->get_result()->fetch_assoc();
        
        if (!$loanData) {
            throw new Exception("Loan not found with ID: $loanId");
        }
        
        if ($loanData['status'] < 2) {
            throw new Exception('Loan must be disbursed (status >= 2) before accepting repayments');
        }
        
        if ($loanData['account_id'] != $accountId) {
            throw new Exception("Loan does not belong to account ID: $accountId");
        }
        
        // Step 2: Validate repayment amount
        $repayAmount = floatval($repayAmount);
        if ($repayAmount <= 0) {
            throw new Exception("Invalid repayment amount: $repayAmount");
        }
        
        // Step 3: Get ALL schedule entries in strict chronological order
        $scheduleQuery = $this->conn->prepare("
            SELECT 
                id,
                due_date,
                principal,
                interest,
                amount,
                balance,
                COALESCE(repaid_amount, 0) as repaid_amount,
                status,
                paid_date
            FROM loan_schedule 
            WHERE loan_id = ? 
            ORDER BY due_date ASC, id ASC
        ");
        
        if (!$scheduleQuery) {
            throw new Exception("Failed to prepare schedule query: " . $this->conn->error);
        }
        
        $scheduleQuery->bind_param("i", $loanId);
        $scheduleQuery->execute();
        $scheduleEntries = $scheduleQuery->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($scheduleEntries)) {
            throw new Exception("No loan schedule found for loan ID: $loanId");
        }
        
        error_log("Found " . count($scheduleEntries) . " schedule entries");
        
        // Step 4: Calculate total outstanding
        $totalOutstanding = 0;
        foreach ($scheduleEntries as $entry) {
            $dueAmount = floatval($entry['amount']);
            $alreadyPaid = floatval($entry['repaid_amount']);
            $stillOwed = $dueAmount - $alreadyPaid;
            if ($stillOwed > 0.01) {
                $totalOutstanding += $stillOwed;
            }
        }
        
        error_log("Total outstanding before payment: KSh $totalOutstanding");
        
        if ($totalOutstanding <= 0.01) {
            throw new Exception("Loan is already fully paid. No outstanding balance.");
        }
        
        // Allow overpayment but warn
        if ($repayAmount > $totalOutstanding + 0.01) {
            error_log("WARNING: Repayment amount (KSh $repayAmount) exceeds outstanding (KSh $totalOutstanding)");
        }
        
        // Step 5: Apply payment chronologically
        $remainingPayment = $repayAmount;
        $today = date('Y-m-d');
        $updatedEntries = [];
        
        foreach ($scheduleEntries as $entry) {
            if ($remainingPayment <= 0.01) {
                break;
            }
            
            $entryId = $entry['id'];
            $dueDate = $entry['due_date'];
            $dueAmount = floatval($entry['amount']);
            $currentlyPaid = floatval($entry['repaid_amount']);
            
            $stillOwed = $dueAmount - $currentlyPaid;
            
            if ($stillOwed <= 0.01) {
                continue;
            }
            
            $paymentForThisEntry = min($remainingPayment, $stillOwed);
            $newRepaidAmount = $currentlyPaid + $paymentForThisEntry;
            
            // Determine new status
            $newStatus = 'unpaid';
            $paidDate = null;
            
            if (abs($newRepaidAmount - $dueAmount) <= 0.01) {
                $newStatus = 'paid';
                $paidDate = $today;
                $newRepaidAmount = $dueAmount;
            } elseif ($newRepaidAmount > 0.01) {
                $newStatus = 'partial';
            }
            
            // Calculate default amount
            $newDefaultAmount = 0;
            if ($newStatus !== 'paid' && strtotime($dueDate) < strtotime($today)) {
                $newDefaultAmount = $dueAmount - $newRepaidAmount;
            }
            
            $updatedEntries[] = [
                'id' => $entryId,
                'due_date' => $dueDate,
                'new_repaid_amount' => $newRepaidAmount,
                'payment_applied' => $paymentForThisEntry,
                'new_status' => $newStatus,
                'paid_date' => $paidDate,
                'default_amount' => $newDefaultAmount
            ];
            
            $remainingPayment -= $paymentForThisEntry;
            
            error_log("  Entry $entryId: Applied KSh $paymentForThisEntry, Status: $newStatus");
        }
        
        if (empty($updatedEntries)) {
            throw new Exception("No schedule entries available for payment");
        }
        
        error_log("Updated " . count($updatedEntries) . " entries, Remaining: KSh $remainingPayment");
        
        // Step 6: Update schedule entries - WITH ERROR CHECKING
        $updateStmt = $this->conn->prepare("
            UPDATE loan_schedule 
            SET repaid_amount = ?,
                status = ?,
                paid_date = ?,
                default_amount = ?
            WHERE id = ?
        ");
        
        if (!$updateStmt) {
            throw new Exception("Failed to prepare update statement: " . $this->conn->error);
        }
        
        foreach ($updatedEntries as $update) {
            $updateStmt->bind_param(
                "dssdi",
                $update['new_repaid_amount'],
                $update['new_status'],
                $update['paid_date'],
                $update['default_amount'],
                $update['id']
            );
            
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update schedule entry ID {$update['id']}: " . $updateStmt->error);
            }
        }
        
        // Step 7: Insert repayment record
        $repaymentStmt = $this->conn->prepare("
            INSERT INTO loan_repayments (
                loan_id, 
                amount_repaid, 
                date_paid, 
                payment_mode, 
                served_by, 
                receipt_number
            ) VALUES (?, ?, NOW(), ?, ?, ?)
        ");
        
        if (!$repaymentStmt) {
            throw new Exception("Failed to prepare repayment insert: " . $this->conn->error);
        }
        
        $repaymentStmt->bind_param(
            "idsss",
            $loanId,
            $repayAmount,
            $paymentMode,
            $servedBy,
            $receiptNumber
        );
        
        if (!$repaymentStmt->execute()) {
            throw new Exception("Failed to insert repayment record: " . $repaymentStmt->error);
        }
        
        $repaymentId = $this->conn->insert_id;
        error_log("Repayment record created with ID: $repaymentId");
        
        // Step 8: Add transaction record (safely)
        $description = "Loan repayment for loan ref #" . $loanData['ref_no'];
        
        // Use simple transaction insert instead of addTransactionSafe to avoid method call issues
        $transactionStmt = $this->conn->prepare("
            INSERT INTO transactions (
                account_id,
                type,
                amount,
                description,
                receipt_number,
                date
            ) VALUES (?, 'Loan Repayment', ?, ?, ?, NOW())
        ");
        
        if ($transactionStmt) {
            $transactionStmt->bind_param("idss", $accountId, $repayAmount, $description, $receiptNumber);
            $transactionStmt->execute();
            error_log("Transaction record created");
        } else {
            error_log("WARNING: Could not create transaction record: " . $this->conn->error);
        }
        
        // Step 9: Check if loan is fully paid
        $statusCheckStmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_installments,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_installments,
                SUM(amount - COALESCE(repaid_amount, 0)) as total_remaining
            FROM loan_schedule 
            WHERE loan_id = ?
        ");
        
        if (!$statusCheckStmt) {
            throw new Exception("Failed to prepare status check: " . $this->conn->error);
        }
        
        $statusCheckStmt->bind_param("i", $loanId);
        $statusCheckStmt->execute();
        $statusResult = $statusCheckStmt->get_result()->fetch_assoc();
        
        $totalInstallments = intval($statusResult['total_installments']);
        $paidInstallments = intval($statusResult['paid_installments']);
        $totalRemaining = floatval($statusResult['total_remaining'] ?? 0);
        
        error_log("Status: $paidInstallments/$totalInstallments paid, Remaining: KSh $totalRemaining");
        
        // Update loan status if fully paid
        if ($paidInstallments === $totalInstallments && $totalRemaining <= 0.01) {
            $updateLoanStmt = $this->conn->prepare("UPDATE loan SET status = 3 WHERE loan_id = ?");
            
            if ($updateLoanStmt) {
                $updateLoanStmt->bind_param("i", $loanId);
                $updateLoanStmt->execute();
                error_log("✓ Loan marked as FULLY PAID (status = 3)");
            }
        }
        
        // Commit transaction
        $this->conn->commit();
        
        error_log("=== LOAN REPAYMENT COMPLETED SUCCESSFULLY ===");
        
        return [
            'status' => 'success',
            'message' => 'Loan repayment processed successfully',
            'repaymentId' => $repaymentId,
            'details' => [
                'amount_paid' => $repayAmount,
                'installments_updated' => count($updatedEntries),
                'remaining_balance' => max(0, $totalRemaining - $repayAmount),
                'loan_fully_paid' => ($paidInstallments === $totalInstallments && $totalRemaining <= 0.01)
            ]
        ];
        
    } catch (Exception $e) {
        $this->conn->rollback();
        error_log("=== LOAN REPAYMENT FAILED ===");
        error_log("ERROR: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
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
 * FULLY FIXED: Delete loan repayment and properly reset schedule
 */
public function deleteRepayment($repaymentId, $loanId, $deletedAmount) {
    $this->conn->begin_transaction();
    
    try {
        error_log("=== DELETE REPAYMENT STARTED ===");
        error_log("Repayment ID: $repaymentId, Loan ID: $loanId, Amount: $deletedAmount");
        
        // Step 1: Get repayment details BEFORE deletion
        $getRepaymentStmt = $this->conn->prepare("
            SELECT lr.*, l.ref_no as loan_ref_no, l.status as loan_status,
                   lr.date_paid as repayment_date
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
        $receiptNumber = $repaymentDetails['receipt_number'];
        $repaymentDate = $repaymentDetails['repayment_date'];
        
        // Step 2: Get ALL schedule entries in chronological order
        $allScheduleStmt = $this->conn->prepare("
            SELECT id, due_date, amount, principal, interest, 
                   COALESCE(repaid_amount, 0) as repaid_amount, 
                   status, paid_date
            FROM loan_schedule 
            WHERE loan_id = ? 
            ORDER BY due_date ASC, id ASC
        ");
        $allScheduleStmt->bind_param("i", $loanId);
        $allScheduleStmt->execute();
        $allEntries = $allScheduleStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        error_log("Total schedule entries: " . count($allEntries));
        
        // Step 3: Identify which entries were affected by THIS specific repayment
        // We do this by simulating the original repayment process
        $remainingDeletionAmount = $deletedAmountFloat;
        $affectedEntries = [];
        
        foreach ($allEntries as $entry) {
            // Stop when we've accounted for all the deleted amount
            if ($remainingDeletionAmount <= 0.01) {
                break;
            }
            
            $entryId = $entry['id'];
            $dueDate = $entry['due_date'];
            $dueAmount = floatval($entry['amount']);
            $currentRepaidAmount = floatval($entry['repaid_amount']);
            
            // Skip entries with no repayment
            if ($currentRepaidAmount <= 0.01) {
                error_log("  Entry $entryId (due: $dueDate) - No payment, skipping");
                continue;
            }
            
            // This entry has a payment - determine if this repayment contributed to it
            // We need to check if the paid_date matches or is close to the repayment date
            // OR if this is one of the chronologically next unpaid/partial entries
            
            // Calculate how much of this entry's payment could be from our deleted repayment
            $maxDeductible = min($remainingDeletionAmount, $currentRepaidAmount);
            
            // Determine if we should deduct from this entry
            // Logic: Deduct from entries that have payments until we've accounted for the full deletion amount
            if ($maxDeductible > 0.01) {
                $newRepaidAmount = $currentRepaidAmount - $maxDeductible;
                
                // Ensure no negative values
                if ($newRepaidAmount < 0) {
                    $newRepaidAmount = 0;
                }
                
                // Calculate new status
                $newStatus = 'unpaid';
                $newPaidDate = null;
                
                if ($newRepaidAmount > 0.01) {
                    // There's still some payment remaining
                    if (abs($newRepaidAmount - $dueAmount) <= 0.01) {
                        // Still fully paid (this can happen if multiple repayments paid same installment)
                        $newStatus = 'paid';
                        $newRepaidAmount = $dueAmount;
                        $newPaidDate = $entry['paid_date']; // Keep the original paid date
                    } else {
                        // Now partially paid
                        $newStatus = 'partial';
                    }
                } else {
                    // No payment remaining - mark as unpaid
                    $newStatus = 'unpaid';
                    $newRepaidAmount = 0;
                }
                
                // Calculate default amount for overdue unpaid/partial installments
                $today = date('Y-m-d');
                $newDefaultAmount = 0;
                if ($newStatus !== 'paid' && strtotime($dueDate) < strtotime($today)) {
                    $newDefaultAmount = $dueAmount - $newRepaidAmount;
                }
                
                $affectedEntries[] = [
                    'id' => $entryId,
                    'due_date' => $dueDate,
                    'old_repaid_amount' => $currentRepaidAmount,
                    'new_repaid_amount' => $newRepaidAmount,
                    'deducted_amount' => $maxDeductible,
                    'new_status' => $newStatus,
                    'new_paid_date' => $newPaidDate,
                    'new_default_amount' => $newDefaultAmount
                ];
                
                $remainingDeletionAmount -= $maxDeductible;
                
                error_log("  Entry $entryId (due: $dueDate): Deducting KSh $maxDeductible");
                error_log("    Old: KSh $currentRepaidAmount, New: KSh $newRepaidAmount, Status: $newStatus");
            }
        }
        
        // Warning if we couldn't account for all the deletion amount
        if ($remainingDeletionAmount > 0.01) {
            error_log("⚠ WARNING: Could not fully account for deleted amount. Remaining: KSh $remainingDeletionAmount");
            // This might happen if the schedule was manually modified after the repayment
        }
        
        error_log("Identified " . count($affectedEntries) . " entries to update");
        
        if (empty($affectedEntries)) {
            throw new Exception("No schedule entries were affected by this repayment. Cannot proceed with deletion.");
        }
        
        // Step 4: Update ALL affected schedule entries
        $updateScheduleStmt = $this->conn->prepare("
            UPDATE loan_schedule 
            SET repaid_amount = ?, 
                status = ?, 
                paid_date = ?,
                default_amount = ?
            WHERE id = ?
        ");
        
        if (!$updateScheduleStmt) {
            throw new Exception("Failed to prepare update statement: " . $this->conn->error);
        }
        
        $updatedCount = 0;
        foreach ($affectedEntries as $affectedEntry) {
            $updateScheduleStmt->bind_param(
                "dssdi",
                $affectedEntry['new_repaid_amount'],
                $affectedEntry['new_status'],
                $affectedEntry['new_paid_date'],
                $affectedEntry['new_default_amount'],
                $affectedEntry['id']
            );
            
            if (!$updateScheduleStmt->execute()) {
                throw new Exception("Failed to update schedule entry ID {$affectedEntry['id']}: " . $updateScheduleStmt->error);
            }
            
            if ($updateScheduleStmt->affected_rows > 0) {
                $updatedCount++;
                error_log("✓ UPDATED Schedule ID {$affectedEntry['id']}: " . 
                         "KSh {$affectedEntry['old_repaid_amount']} -> KSh {$affectedEntry['new_repaid_amount']}, " .
                         "Status: {$affectedEntry['new_status']}");
            } else {
                error_log("⚠ No changes for Schedule ID {$affectedEntry['id']} (already correct)");
            }
        }
        
        error_log("Total schedule entries updated: $updatedCount / " . count($affectedEntries));
        
        // Step 5: Delete the repayment record
        $deleteRepaymentStmt = $this->conn->prepare("
            DELETE FROM loan_repayments WHERE id = ?
        ");
        $deleteRepaymentStmt->bind_param("i", $repaymentId);
        
        if (!$deleteRepaymentStmt->execute()) {
            throw new Exception("Failed to delete repayment record: " . $deleteRepaymentStmt->error);
        }
        
        error_log("✓ DELETED repayment record ID: $repaymentId");
        
        // Step 6: Delete associated transactions
        $deleteTransactionStmt = $this->conn->prepare("
            DELETE FROM transactions 
            WHERE receipt_number = ? 
            AND type = 'Loan Repayment'
            AND account_id IN (SELECT account_id FROM loan WHERE loan_id = ?)
        ");
        $deleteTransactionStmt->bind_param("si", $receiptNumber, $loanId);
        $deleteTransactionStmt->execute();
        
        $deletedTransactions = $deleteTransactionStmt->affected_rows;
        error_log("✓ DELETED $deletedTransactions transaction record(s)");
        
        // Step 7: Recalculate loan status
        $statusCheckStmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_installments,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_installments,
                SUM(amount - COALESCE(repaid_amount, 0)) as remaining_amount
            FROM loan_schedule 
            WHERE loan_id = ?
        ");
        $statusCheckStmt->bind_param("i", $loanId);
        $statusCheckStmt->execute();
        $statusResult = $statusCheckStmt->get_result()->fetch_assoc();
        
        $totalInstallments = intval($statusResult['total_installments']);
        $paidInstallments = intval($statusResult['paid_installments']);
        $remainingAmount = floatval($statusResult['remaining_amount'] ?? 0);
        
        error_log("Loan status: $paidInstallments/$totalInstallments paid, Remaining: KSh $remainingAmount");
        
        // Update loan status
        $newLoanStatus = null;
        if ($paidInstallments === $totalInstallments && $remainingAmount <= 0.01) {
            // Fully paid
            $newLoanStatus = 3;
            error_log("Loan should be marked as FULLY PAID (status 3)");
        } elseif ($repaymentDetails['loan_status'] == 3 && $paidInstallments < $totalInstallments) {
            // Was marked as fully paid but now has unpaid installments after deletion
            $newLoanStatus = 2;
            error_log("Loan should be changed to DISBURSED (status 2) - has unpaid installments after deletion");
        }
        
        if ($newLoanStatus !== null) {
            $updateLoanStmt = $this->conn->prepare("
                UPDATE loan SET status = ? WHERE loan_id = ?
            ");
            $updateLoanStmt->bind_param("ii", $newLoanStatus, $loanId);
            $updateLoanStmt->execute();
            error_log("✓ UPDATED loan status to: $newLoanStatus");
        }
        
        // Commit transaction
        $this->conn->commit();
        
        error_log("=== DELETE REPAYMENT COMPLETED SUCCESSFULLY ===");
        
        return [
            'status' => 'success',
            'message' => 'Repayment deleted and loan schedule updated successfully',
            'details' => [
                'repayment_id' => $repaymentId,
                'deleted_amount' => $deletedAmountFloat,
                'schedule_entries_affected' => count($affectedEntries),
                'schedule_entries_updated' => $updatedCount,
                'transactions_deleted' => $deletedTransactions,
                'total_installments' => $totalInstallments,
                'paid_installments' => $paidInstallments,
                'remaining_unpaid' => ($totalInstallments - $paidInstallments),
                'remaining_amount' => $remainingAmount,
                'affected_due_dates' => array_column($affectedEntries, 'due_date')
            ]
        ];
        
    } catch (Exception $e) {
        $this->conn->rollback();
        error_log("=== DELETE REPAYMENT FAILED ===");
        error_log("ERROR: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
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
 * FIXED: Create loan schedule - prevents duplicates
 */
public function createLoanSchedule($loanId) {
    try {
        $this->conn->begin_transaction();
        
        // Get loan details
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
        
        // CRITICAL: Check if schedule already exists
        $existingCheckStmt = $this->conn->prepare("
            SELECT COUNT(*) as count FROM loan_schedule WHERE loan_id = ?
        ");
        $existingCheckStmt->bind_param("i", $loanId);
        $existingCheckStmt->execute();
        $existingCount = $existingCheckStmt->get_result()->fetch_assoc()['count'];
        
        if ($existingCount > 0) {
            error_log("WARNING: Schedule already exists for loan $loanId. Deleting old schedule...");
            // Delete existing schedule to prevent duplicates
            $deleteStmt = $this->conn->prepare("DELETE FROM loan_schedule WHERE loan_id = ?");
            $deleteStmt->bind_param("i", $loanId);
            $deleteStmt->execute();
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
        
        $insertStmt = $this->conn->prepare("
            INSERT INTO loan_schedule (
                loan_id, due_date, principal, interest, 
                amount, balance, repaid_amount, default_amount, status, paid_date
            ) VALUES (?, ?, ?, ?, ?, ?, 0, 0, 'unpaid', NULL)
        ");
        
        $insertedCount = 0;
        
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
            
            $insertedCount++;
            
            $principalsPaid += $monthlyPrincipal;
            $remainingBalance -= $monthlyPrincipal;
            if ($remainingBalance < 0) $remainingBalance = 0;
            
            $paymentDate->modify('+1 month');
        }
        
        error_log("Created $insertedCount schedule entries for loan $loanId");
        
        $this->conn->commit();
        return true;
        
    } catch (Exception $e) {
        $this->conn->rollback();
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