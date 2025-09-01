<?php
require_once('../config/config.php');

class AccountModel {
    private $conn;
    private $lastError;

    public function __construct() {
        $db = new db_connect();
        $this->conn = $db->connect();
        $this->lastError = '';

        if (!$this->conn) {
            die("Database connection error: " . $db->error);
        }
    }

    // Create a new client account
    public function createAccount($data) {
        try {
            // Validate required fields
            $requiredFields = ['first_name', 'last_name', 'shareholder_no', 'national_id', 'phone', 'account_type'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
    
            // Convert account types array to string
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




    // Update an existing client account
    public function updateAccount($data) {
        try {
            // Start transaction
            $this->conn->begin_transaction();
    
            // Validate account exists
            $checkStmt = $this->conn->prepare("SELECT account_id FROM client_accounts WHERE account_id = ?");
            $checkStmt->bind_param("i", $data['account_id']);
            $checkStmt->execute();
            if (!$checkStmt->get_result()->fetch_assoc()) {
                throw new Exception("Account not found");
            }
    
            // Convert account types array to string
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



    // Delete a client account
    public function deleteAccount($accountId) {
        $stmt = $this->conn->prepare("DELETE FROM client_accounts WHERE account_id = ?");
        $stmt->bind_param("i", $accountId);
        return $stmt->execute();
    }

    // Get a single account by ID
    public function getAccountById($account_id) {
        $stmt = $this->conn->prepare("SELECT * FROM client_accounts WHERE account_id = ?");
        $stmt->bind_param("i", $account_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Get all client accounts
    public function getAllAccounts() {
        $result = $this->conn->query("SELECT * FROM client_accounts ORDER BY last_name, first_name");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get account transactions
    public function getAccountTransactions($accountId, $accountType = 'all') {
    try {
        $query = "SELECT t.*, s.account_type
                  FROM transactions t
                  LEFT JOIN savings s ON t.receipt_number = s.receipt_number
                  WHERE t.account_id = ?";
        
        $params = [$accountId];
        $types = "i";
        
        if ($accountType !== 'all') {
            $query .= " AND (s.account_type = ? OR s.account_type IS NULL)";
            $params[] = $accountType;
            $types .= "s";
        }
        
        $query .= " ORDER BY t.date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error in getAccountTransactions: " . $e->getMessage());
        return [];
    }
}

    // Get account loans
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
        
        // Debug information
        error_log("Fetched loans for account $accountId (type: $accountType): " . print_r($loans, true));
        
        return $loans;
    } catch (Exception $e) {
        error_log("Error in getAccountLoans: " . $e->getMessage());
        return [];
    }
}

///filtered data
// In AccountModel.php, update or add these methods
public function getFilteredTotalSavings($accountId, $accountType) {
    try {
        $query = "SELECT 
            COALESCE(SUM(
                CASE 
                    WHEN type = 'Savings' THEN amount 
                    WHEN type = 'Withdrawal' THEN -amount 
                END
            ), 0) as total
            FROM savings 
            WHERE account_id = ?";
        $params = [$accountId];
        $types = "i";

        if ($accountType !== 'all') {
            $query .= " AND account_type = ?";
            $params[] = $accountType;
            $types .= "s";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Error in getFilteredTotalSavings: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get filtered total loans for an account (Outstanding loans only)
 * 
 * @param int $accountId ID of the account
 * @param string $accountType Filter by account type
 * @return float Total outstanding loans amount
 */
public function getFilteredTotalLoans($accountId, $accountType = 'all') {
    try {
        $query = "SELECT COALESCE(SUM(outstanding_balance), 0) as total
                 FROM loan 
                 WHERE account_id = ? AND status != 3";
        $params = [$accountId];
        $types = "i";

        if ($accountType !== 'all') {
            $query .= " AND account_type = ?";
            $params[] = $accountType;
            $types .= "s";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Error in getFilteredTotalLoans: " . $e->getMessage());
        return 0;
    }
}


/**
 * Get total outstanding loans (principal balance) from loan schedule
 * Fixed to handle negative values and ensure proper calculation
 */
public function getTotalOutstandingLoans($accountId, $accountType = 'all') {
    try {
        $query = "
            SELECT COALESCE(SUM(GREATEST(current_balance, 0)), 0) as total_outstanding
            FROM (
                SELECT 
                    l.loan_id,
                    GREATEST(COALESCE(
                        (SELECT ls.balance 
                         FROM loan_schedule ls 
                         WHERE ls.loan_id = l.loan_id 
                         AND ls.status IN ('unpaid', 'partial')
                         ORDER BY ls.due_date ASC 
                         LIMIT 1),
                        l.amount
                    ), 0) as current_balance
                FROM loan l
                WHERE l.account_id = ?
                AND l.status IN (1, 2)"; // Only active/released loans
        
        $params = [$accountId];
        $types = "i";
        
        if ($accountType !== 'all') {
            $query .= " AND l.account_type = ?";
            $params[] = $accountType;
            $types .= "s";
        }
        
        $query .= "
            ) as loan_balances";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        
        $result = $stmt->get_result()->fetch_assoc();
        $outstanding = floatval($result['total_outstanding'] ?? 0);
        
        // Ensure we never return negative values
        return max(0, $outstanding);
        
    } catch (Exception $e) {
        error_log("Error in getTotalOutstandingLoans: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get total group savings for a specific client from group transactions
 * Fixed to look in group_savings and group_withdrawals tables
 */
public function getTotalGroupSavings($accountId, $accountType = 'all') {
    try {
        // Get group savings from actual group tables, not account types
        $query = "
            SELECT COALESCE(
                (SELECT SUM(gs.amount) 
                 FROM group_savings gs
                 JOIN group_members gm ON gs.group_id = gm.group_id AND gs.account_id = gm.account_id
                 WHERE gm.account_id = ? AND gm.status = 'active'),
                0
            ) - COALESCE(
                (SELECT SUM(gw.amount)
                 FROM group_withdrawals gw
                 JOIN group_members gm ON gw.group_id = gm.group_id AND gw.account_id = gm.account_id
                 WHERE gm.account_id = ? AND gm.status = 'active'),
                0
            ) as total_group_savings";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        
        $stmt->bind_param("ii", $accountId, $accountId);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        
        $result = $stmt->get_result()->fetch_assoc();
        $groupSavings = floatval($result['total_group_savings'] ?? 0);
        
        // Ensure we never return negative values
        return max(0, $groupSavings);
        
    } catch (Exception $e) {
        error_log("Error in getTotalGroupSavings: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get count of active loans (status = 2, which means disbursed/active)
 */
public function getActiveLoansCount($accountId, $accountType = 'all') {
    try {
        $query = "
            SELECT COUNT(*) as active_count
            FROM loan l
            WHERE l.account_id = ? 
            AND l.status = 2"; // Status 2 = Active/Disbursed
        
        $params = [$accountId];
        $types = "i";
        
        if ($accountType !== 'all') {
            $query .= " AND l.account_type = ?";
            $params[] = $accountType;
            $types .= "s";
        }
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        
        $result = $stmt->get_result()->fetch_assoc();
        return intval($result['active_count'] ?? 0);
        
    } catch (Exception $e) {
        error_log("Error in getActiveLoansCount: " . $e->getMessage());
        return 0;
    }
}

// Get account savings with served_by user names
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

    // Get total savings for an account
    public function getTotalSavings($accountId, $accountType = 'all') {
        $query = "SELECT SUM(CASE WHEN type = 'Savings' THEN amount ELSE -amount END) as total 
                  FROM savings WHERE account_id = ?";
        $params = [$accountId];
        $types = "i";
        
        if ($accountType !== 'all') {
            $query .= " AND account_type = ?";
            $params[] = $accountType;
            $types .= "s";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] ?? 0;
    }

    // Get total outstanding loans for an account
    public function getTotalLoans($accountId, $accountType = 'all') {
    try {
        $query = "
            SELECT SUM(l.amount) as total_loan_amount,
                   SUM(COALESCE(lr.total_repaid, 0)) as total_repaid
            FROM loan l
            LEFT JOIN (
                SELECT loan_id, SUM(amount_repaid) as total_repaid
                FROM loan_repayments
                GROUP BY loan_id
            ) lr ON l.loan_id = lr.loan_id
            WHERE l.account_id = ?";
        
        $params = [$accountId];
        $types = "i";
        
        if ($accountType !== 'all') {
            $query .= " AND l.account_type = ?";
            $params[] = $accountType;
            $types .= "s";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $totalLoanAmount = $result['total_loan_amount'] ?? 0;
        $totalRepaid = $result['total_repaid'] ?? 0;
        
        return $totalLoanAmount - $totalRepaid;
    } catch (Exception $e) {
        error_log("Error in getTotalLoans: " . $e->getMessage());
        return 0;
    }
}


    
    // Add savings to an account
    public function addSavings($accountId, $amount, $paymentMode, $accountType, $receiptNumber, $servedBy) {
        $this->conn->begin_transaction();
        
        try {
            // Insert into savings table
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
                throw new Exception("Error preparing statement: " . $this->conn->error);
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
    
            // Add transaction record
            $description = "Savings deposit - $accountType";
            $transStmt = $this->conn->prepare("
                INSERT INTO transactions (
                    account_id,
                    type,
                    amount,
                    description,
                    receipt_number,
                    date
                ) VALUES (?, 'Savings', ?, ?, ?, NOW())
            ");
    
            if (!$transStmt) {
                throw new Exception("Error preparing transaction statement: " . $this->conn->error);
            }
    
            $transStmt->bind_param("idss", 
                $accountId,
                $amount,
                $description,
                $receiptNumber
            );
    
            if (!$transStmt->execute()) {
                throw new Exception("Error executing transaction insert: " . $transStmt->error);
            }
    
            $this->conn->commit();
            
            return [
                'status' => 'success',
                'message' => 'Savings added successfully',
                'savingsId' => $savingsId,
                'receiptDetails' => $this->getSavingsReceiptDetails($savingsId)['details']
            ];
    
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in addSavings: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    




private function getClientDetails($accountId) {
    $stmt = $this->conn->prepare("
        SELECT CONCAT(first_name, ' ', last_name) as name
        FROM client_accounts 
        WHERE account_id = ?
    ");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ?? ['name' => 'Unknown'];
}



// Add helper method to get client name
private function getClientName($accountId) {
    $stmt = $this->conn->prepare("
        SELECT CONCAT(first_name, ' ', last_name) as client_name 
        FROM client_accounts 
        WHERE account_id = ?
    ");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['client_name'] ?? 'Unknown';
}

//receipt details

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




// Add these methods to accountModel.php

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



    // Get filtered savings data
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

    // Get filtered transaction data
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


    // Add a new loan
    public function addLoan($accountId, $amount, $loanType, $status, $purpose, $planId) {
        $this->conn->begin_transaction();
    
        try {
            $columns = $this->conn->query("SHOW COLUMNS FROM loan");
            $accountColumn = 'account_id';
    
            while ($column = $columns->fetch_assoc()) {
                if (in_array($column['Field'], ['account_id', 'borrower_id', 'client_id'])) {
                    $accountColumn = $column['Field'];
                    break;
                }
            }
    
            // Modified SQL to include outstanding_balance
            $stmt = $this->conn->prepare("INSERT INTO loan ($accountColumn, amount, ltype_id, status, purpose, lplan_id, date_created, outstanding_balance) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
            $stmt->bind_param("idissid", $accountId, $amount, $loanType, $status, $purpose, $planId, $amount);
            $stmt->execute();
    
            $loanId = $this->conn->insert_id;
    
            $this->addTransaction($accountId, 'Loan', $amount, "Loan application", $loanId);
    
            // If the loan is immediately approved (status 2 or above), update net balance
            if ($status >= 2) {
                $this->updateNetBalance($accountId);
            }
    
            $this->conn->commit();
            return $loanId;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error adding loan: " . $e->getMessage());
            return false;
        }
    }


    // Update loan status
    public function updateLoanStatus($loanId, $status) {
        $stmt = $this->conn->prepare("UPDATE loan SET status = ? WHERE loan_id = ?");
        $stmt->bind_param("ii", $status, $loanId);
        return $stmt->execute();
    }

    // Get a loan by ID
    public function getLoanById($loanId) {
        $stmt = $this->conn->prepare("
            SELECT 
                l.loan_id, 
                l.ref_no, 
                l.loan_product_id,
                l.amount,
                l.interest_rate,
                l.monthly_payment,
                l.total_payable,
                l.status,
                l.date_applied,
                l.next_payment_date,
                (l.amount - COALESCE((SELECT SUM(amount) FROM transactions WHERE type = 'Loan Repayment' AND loan_id = l.loan_id), 0)) as outstanding_balance
            FROM loan l
            WHERE l.loan_id = ?
        ");
        $stmt->bind_param("i", $loanId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    //loan repayments
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


    
    public function getLoanDetails($loanId) {
        $stmt = $this->conn->prepare("
            SELECT l.*, 
                   (l.total_payable - COALESCE(SUM(lr.amount_repaid), 0)) as outstanding_balance,
                   (SELECT MIN(due_date) FROM loan_schedule WHERE loan_id = l.loan_id AND status = 'unpaid') as next_due_date
            FROM loan l
            LEFT JOIN loan_repayments lr ON l.loan_id = lr.loan_id
            WHERE l.loan_id = ?
            GROUP BY l.loan_id
        ");
        $stmt->bind_param("i", $loanId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }


    public function getLoanDetailsForRepayment($loanId) {
    try {
        // Get loan details with explicit account ID join
        $query = $this->conn->prepare("
            SELECT 
                l.*, 
                ca.first_name, 
                ca.last_name,
                (l.amount - COALESCE((SELECT SUM(amount_repaid) FROM loan_repayments WHERE loan_id = l.loan_id), 0)) as outstanding_balance
            FROM loan l
            JOIN client_accounts ca ON l.account_id = ca.account_id
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
        
        // Get next unpaid/partial payment information
        $scheduleQuery = $this->conn->prepare("
            SELECT * FROM loan_schedule 
            WHERE loan_id = ? 
            AND (status = 'unpaid' OR status = 'partial')
            ORDER BY due_date ASC LIMIT 1
        ");
        
        if ($scheduleQuery) {
            $scheduleQuery->bind_param("i", $loanId);
            $scheduleQuery->execute();
            $schedule = $scheduleQuery->get_result()->fetch_assoc();
            
            if ($schedule) {
                $dueAmount = floatval($schedule['amount']);
                $repaidAmount = floatval($schedule['repaid_amount'] ?? 0);
                $remainingAmount = $dueAmount - $repaidAmount;
                
                // Get accumulated defaults from all overdue unpaid/partial installments
                $defaultQuery = $this->conn->prepare("
                    SELECT SUM(default_amount) as total_defaults 
                    FROM loan_schedule 
                    WHERE loan_id = ? 
                    AND due_date <= CURDATE() 
                    AND status != 'paid'
                    AND default_amount > 0
                ");
                $defaultQuery->bind_param("i", $loanId);
                $defaultQuery->execute();
                $defaultResult = $defaultQuery->get_result()->fetch_assoc();
                $totalDefaults = floatval($defaultResult['total_defaults'] ?? 0);
                
                // Next due amount = remaining amount for current installment + accumulated defaults
                $loanDetails['next_due_amount'] = $remainingAmount + $totalDefaults;
                $loanDetails['next_due_date'] = $schedule['due_date'];
                $loanDetails['is_overdue'] = (strtotime($schedule['due_date']) < time());
                $loanDetails['accumulated_defaults'] = $totalDefaults;
                
                // Handle partial payments
                if ($schedule['status'] === 'partial' && $schedule['repaid_amount'] > 0) {
                    $loanDetails['partial_paid'] = floatval($schedule['repaid_amount']);
                }
                
                // Debug log
                error_log("Next payment for loan ID $loanId: " . json_encode([
                    'remaining_amount' => $remainingAmount,
                    'accumulated_defaults' => $totalDefaults,
                    'total_due_amount' => $loanDetails['next_due_amount'],
                    'due_date' => $loanDetails['next_due_date'],
                    'is_overdue' => $loanDetails['is_overdue']
                ]));
            } else {
                $loanDetails['next_due_amount'] = 0;
                $loanDetails['next_due_date'] = null;
                $loanDetails['is_overdue'] = false;
                $loanDetails['accumulated_defaults'] = 0;
            }
        }
        
        return $loanDetails;
        
    } catch (Exception $e) {
        error_log("Error in getLoanDetailsForRepayment: " . $e->getMessage());
        return false;
    }
}


public function deleteRepayment($repaymentId, $loanId, $deletedAmount) {
    $this->conn->begin_transaction();
    
    try {
        // First, get the repayment details before deletion
        $getRepaymentStmt = $this->conn->prepare("
            SELECT lr.*, l.ref_no as loan_ref_no
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
        
        // Delete the repayment record
        $deleteRepaymentStmt = $this->conn->prepare("DELETE FROM loan_repayments WHERE id = ?");
        $deleteRepaymentStmt->bind_param("i", $repaymentId);
        
        if (!$deleteRepaymentStmt->execute()) {
            throw new Exception("Failed to delete repayment record");
        }
        
        // Delete the associated transaction record
        $deleteTransactionStmt = $this->conn->prepare("
            DELETE FROM transactions 
            WHERE receipt_number = ? AND type = 'Loan Repayment' AND amount = ?
        ");
        $deleteTransactionStmt->bind_param("sd", $repaymentDetails['receipt_number'], $deletedAmount);
        $deleteTransactionStmt->execute();
        
        // Now we need to reverse the payment from the loan schedule
        // Get all schedule entries that were affected by this payment
        $scheduleStmt = $this->conn->prepare("
            SELECT * FROM loan_schedule 
            WHERE loan_id = ? 
            ORDER BY due_date ASC
        ");
        $scheduleStmt->bind_param("i", $loanId);
        $scheduleStmt->execute();
        $schedules = $scheduleStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Reverse the payment by reducing repaid amounts
        $remainingToReverse = floatval($deletedAmount);
        $today = date('Y-m-d');
        
        // Process schedules in reverse order (latest first) to undo the payment
        foreach (array_reverse($schedules) as $schedule) {
            if ($remainingToReverse <= 0) break;
            if (floatval($schedule['repaid_amount']) <= 0) continue;
            
            $currentRepaidAmount = floatval($schedule['repaid_amount']);
            $dueAmount = floatval($schedule['amount']);
            
            // Calculate how much to reverse from this installment
            $reverseAmount = min($remainingToReverse, $currentRepaidAmount);
            $newRepaidAmount = $currentRepaidAmount - $reverseAmount;
            
            // Calculate new status and default amount
            $newStatus = 'unpaid';
            $newDefaultAmount = 0;
            $paidDate = null;
            
            if (abs($newRepaidAmount - $dueAmount) <= 0.50) {
                $newStatus = 'paid';
                $paidDate = $schedule['paid_date']; // Keep original paid date if still fully paid
            } elseif ($newRepaidAmount > 0) {
                $newStatus = 'partial';
            }
            
            // Calculate default amount if past due and not fully paid
            if ($newStatus !== 'paid' && strtotime($schedule['due_date']) < strtotime($today)) {
                $newDefaultAmount = $dueAmount - $newRepaidAmount;
            }
            
            // Update the schedule entry
            $updateScheduleStmt = $this->conn->prepare("
                UPDATE loan_schedule 
                SET repaid_amount = ?,
                    default_amount = ?,
                    status = ?,
                    paid_date = ?
                WHERE id = ?
            ");
            
            $updateScheduleStmt->bind_param(
                "ddssi",
                $newRepaidAmount,
                $newDefaultAmount,
                $newStatus,
                $paidDate,
                $schedule['id']
            );
            
            if (!$updateScheduleStmt->execute()) {
                throw new Exception("Failed to update loan schedule: " . $updateScheduleStmt->error);
            }
            
            $remainingToReverse -= $reverseAmount;
        }
        
        // Check if loan status needs to be updated (if it was completed, it might need to go back to active)
        $unpaidCountStmt = $this->conn->prepare("
            SELECT COUNT(*) as unpaid_count
            FROM loan_schedule
            WHERE loan_id = ? AND status != 'paid'
        ");
        $unpaidCountStmt->bind_param("i", $loanId);
        $unpaidCountStmt->execute();
        $unpaidResult = $unpaidCountStmt->get_result()->fetch_assoc();
        
        // If there are unpaid installments and loan was marked as completed, revert to active
        if ($unpaidResult['unpaid_count'] > 0) {
            $updateLoanStmt = $this->conn->prepare("
                UPDATE loan 
                SET status = CASE 
                    WHEN status = 3 THEN 2  -- Change from completed to active/disbursed
                    ELSE status 
                END
                WHERE loan_id = ?
            ");
            $updateLoanStmt->bind_param("i", $loanId);
            $updateLoanStmt->execute();
        }
        
        $this->conn->commit();
        
        return [
            'status' => 'success',
            'message' => 'Repayment deleted successfully and loan schedule updated'
        ];
        
    } catch (Exception $e) {
        $this->conn->rollback();
        error_log("Error in deleteRepayment: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}
    


// Add this method to your AccountModel class
public function getConnection() {
    return $this->conn;
}


public function getTotalLoanAmount($accountId) {
    try {
        $query = "
            SELECT COALESCE(SUM(l.amount), 0) as total_loan_amount
            FROM loan l
            WHERE l.account_id = ?
            AND l.status >= 0
        ";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        // Log for debugging
        error_log("Total loan amount for account $accountId: " . ($result['total_loan_amount'] ?? 0));
        
        return $result['total_loan_amount'] ?? 0;
    } catch (Exception $e) {
        error_log("Error in getTotalLoanAmount: " . $e->getMessage());
        return 0;
    }
}

public function getTotalOutstandingPrincipal($accountId, $accountType = 'all') {
    try {
        // Get the balance of the most recent unpaid/partial installment for active loans
        $query = "
            SELECT 
                COALESCE(MIN(ls.balance), 0) as outstanding_principal
            FROM loan l
            JOIN loan_schedule ls ON l.loan_id = ls.loan_id
            WHERE l.account_id = ?
                AND l.status IN (1, 2)";  // Active/Released loans only
        
        $params = [$accountId];
        $types = "i";
        
        if ($accountType !== 'all') {
            $query .= " AND l.account_type = ?";
            $params[] = $accountType;
            $types .= "s";
        }
        
        $query .= " AND ls.status IN ('unpaid', 'partial')
                    GROUP BY l.loan_id
                    ORDER BY ls.due_date ASC
                    LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        // If no result found, try to get the initial loan amount for new loans
        if (!$row || $row['outstanding_principal'] == 0) {
            $newLoanQuery = "
                SELECT amount as outstanding_principal
                FROM loan
                WHERE account_id = ?
                    AND status IN (1, 2)";  // Active/Released loans only
            
            if ($accountType !== 'all') {
                $newLoanQuery .= " AND account_type = ?";
            }
            
            $newLoanQuery .= " AND NOT EXISTS (
                                SELECT 1 FROM loan_schedule 
                                WHERE loan_id = loan.loan_id 
                                AND status = 'paid'
                            )
                            ORDER BY date_created DESC
                            LIMIT 1";
            
            $newStmt = $this->conn->prepare($newLoanQuery);
            if (!$newStmt) {
                throw new Exception("Error preparing new loan statement: " . $this->conn->error);
            }
            
            $newStmt->bind_param($types, ...$params);
            if (!$newStmt->execute()) {
                throw new Exception("Error executing new loan query: " . $newStmt->error);
            }
            
            $newResult = $newStmt->get_result();
            $newRow = $newResult->fetch_assoc();
            
            return $newRow ? $newRow['outstanding_principal'] : 0;
        }
        
        return $row['outstanding_principal'];
    } catch (Exception $e) {
        error_log("Error in getTotalOutstandingPrincipal: " . $e->getMessage());
        error_log("SQL State: " . $this->conn->sqlstate);
        error_log("Error Code: " . $this->conn->errno);
        return 0;
    }
}
    
    // Add this method to get a single loan's current principal
    public function getLoanCurrentPrincipal($loanId) {
        try {
            $query = "
                SELECT ls.balance
                FROM loan_schedule ls
                WHERE ls.loan_id = ?
                AND ls.due_date = (
                    SELECT MIN(ls2.due_date)
                    FROM loan_schedule ls2
                    WHERE ls2.loan_id = ls.loan_id
                    AND ls2.status IN ('unpaid', 'partial')
                )
                LIMIT 1
            ";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $loanId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            return $result['balance'] ?? 0;
            
        } catch (Exception $e) {
            error_log("Error getting loan current principal: " . $e->getMessage());
            return 0;
        }
    }





  public function repayLoan($accountId, $loanId, $repayAmount, $paymentMode, $servedBy, $receiptNumber) {
    error_log("Model: Repay Loan - accountId=$accountId, loanId=$loanId, repayAmount=$repayAmount, paymentMode=$paymentMode, servedBy=$servedBy, receiptNumber=$receiptNumber");
    
    // Verify the loan exists and check status
    $loanQuery = $this->conn->prepare("SELECT loan_id, account_id, ref_no, status FROM loan WHERE loan_id = ?");
    $loanQuery->bind_param("i", $loanId);
    $loanQuery->execute();
    $loanResult = $loanQuery->get_result();
    
    if ($loanResult->num_rows === 0) {
        error_log("Loan not found with ID: $loanId");
        return ['status' => 'error', 'message' => "Loan not found with ID: $loanId"];
    }
    
    $loanData = $loanResult->fetch_assoc();
    
    // Check if loan is disbursed (status should be >= 2)
    if ($loanData['status'] < 2) {
        return ['status' => 'error', 'message' => 'Cannot process repayment. Loan must be disbursed first (status >= 2).'];
    }
    
    $loanRefNo = $loanData['ref_no'];
    
    $this->conn->begin_transaction();
    
    try {
        // Get all unpaid and partial schedule entries ordered by due date
        $scheduleStmt = $this->conn->prepare("
            SELECT * FROM loan_schedule 
            WHERE loan_id = ? 
            AND (status = 'unpaid' OR status = 'partial')
            ORDER BY due_date ASC
        ");
        
        $scheduleStmt->bind_param("i", $loanId);
        $scheduleStmt->execute();
        $schedules = $scheduleStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($schedules)) {
            // If no unpaid schedule found, just record the payment
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
            
            $repaymentId = $this->conn->insert_id;
            
            // Add transaction record
            $description = "Loan repayment for loan ref #" . $loanRefNo;
            $transactionStmt = $this->conn->prepare("
                INSERT INTO transactions (
                    account_id,
                    type,
                    amount,
                    description,
                    receipt_number,
                    payment_mode,
                    served_by,
                    date
                ) VALUES (?, 'Loan Repayment', ?, ?, ?, ?, ?, NOW())
            ");
            
            if (!$transactionStmt) {
                throw new Exception("Error preparing transaction statement: " . $this->conn->error);
            }
            
            $transactionStmt->bind_param("idssss", 
                $accountId,
                $repayAmount,
                $description,
                $receiptNumber,
                $paymentMode,
                $servedBy
            );
            
            if (!$transactionStmt->execute()) {
                throw new Exception("Error recording transaction: " . $transactionStmt->error);
            }
            
            $this->conn->commit();
            return [
                'status' => 'success',
                'message' => 'Loan repayment recorded successfully'
            ];
        }
        
        // Process payments starting with oldest unpaid installment
        $remainingPayment = $repayAmount;
        $today = date('Y-m-d');
        
        foreach ($schedules as $schedule) {
            if ($remainingPayment <= 0) break;
            
            $dueAmount = floatval($schedule['amount']);
            $currentRepaidAmount = floatval($schedule['repaid_amount'] ?? 0);
            $existingDefaultAmount = floatval($schedule['default_amount'] ?? 0);
            
            // Calculate total amount needed for this installment (due amount + existing defaults)
            $totalAmountNeeded = $dueAmount - $currentRepaidAmount;
            
            // Apply payment to this installment
            $paymentForThisInstallment = min($remainingPayment, $totalAmountNeeded);
            $newRepaidAmount = $currentRepaidAmount + $paymentForThisInstallment;
            
            // Calculate new default amount - only if past due date and not fully paid
            $newDefaultAmount = 0;
            if ($newRepaidAmount < $dueAmount && strtotime($schedule['due_date']) < strtotime($today)) {
                $newDefaultAmount = $dueAmount - $newRepaidAmount;
            }
            
            // Determine new status
            $newStatus = 'unpaid';
            $paidDate = null;
            
            if (abs($newRepaidAmount - $dueAmount) <= 0.50) {
                $newStatus = 'paid';
                $paidDate = $today;
                $newDefaultAmount = 0; // Clear defaults when fully paid
            } elseif ($newRepaidAmount > 0) {
                $newStatus = 'partial';
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
        }
        
        // Insert repayment record
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
        
        $repaymentId = $this->conn->insert_id;
        
        // Add transaction record
        $description = "Loan repayment for loan ref #" . $loanRefNo;
        $transactionStmt = $this->conn->prepare("
            INSERT INTO transactions (
                account_id,
                type,
                amount,
                description,
                receipt_number,
                payment_mode,
                served_by,
                date
            ) VALUES (?, 'Loan Repayment', ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$transactionStmt) {
            throw new Exception("Error preparing transaction statement: " . $this->conn->error);
        }
        
        $transactionStmt->bind_param("idssss", 
            $accountId,
            $repayAmount,
            $description,
            $receiptNumber,
            $paymentMode,
            $servedBy
        );
        
        if (!$transactionStmt->execute()) {
            throw new Exception("Error recording transaction: " . $transactionStmt->error);
        }
        
        // Check if loan is fully paid
        $unpaidStmt = $this->conn->prepare("
            SELECT COUNT(*) as unpaid_count
            FROM loan_schedule
            WHERE loan_id = ? AND status != 'paid'
        ");
        
        $unpaidStmt->bind_param("i", $loanId);
        $unpaidStmt->execute();
        $unpaidResult = $unpaidStmt->get_result()->fetch_assoc();
        
        if ($unpaidResult['unpaid_count'] == 0) {
            // Update loan status to completed (3)
            $updateLoanStmt = $this->conn->prepare("
                UPDATE loan SET status = 3 WHERE loan_id = ?
            ");
            $updateLoanStmt->bind_param("i", $loanId);
            $updateLoanStmt->execute();
        }
        
        $this->conn->commit();
        
        // Get updated balances
        $totalOutstandingPrincipal = $this->getTotalOutstandingPrincipal($accountId);
        
        return [
            'status' => 'success',
            'message' => 'Loan repayment successful',
            'newTotalOutstandingLoans' => $totalOutstandingPrincipal,
            'newNetBalance' => $this->getNetBalance($accountId)
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
    


// Updated withdrawal handling
public function withdraw($accountId, $amount, $paymentMode, $accountType, $receiptNumber, $withdrawalFee, $servedBy) {
    $this->conn->begin_transaction();

    try {
        // Calculate the actual withdrawal amount (total - fee)
        $withdrawalAmount = $amount - $withdrawalFee;
        $availableBalance = $this->getFilteredTotalSavings($accountId, $accountType);

        // Check if the available balance is sufficient
        if ($amount > $availableBalance) {
            throw new Exception("Insufficient funds for withdrawal. Available: KSh " . number_format($availableBalance, 2));
        }

        // Record the withdrawal transaction
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

        // Add transaction record for withdrawal
        $this->addTransaction(
            $accountId,
            'Withdrawal',
            -$withdrawalAmount,
            "Withdrawal from $accountType account",
            $receiptNumber
        );

        // Add separate transaction for withdrawal fee if applicable
        if ($withdrawalFee > 0) {
            $this->addTransaction(
                $accountId,
                'Withdrawal Fee',
                -$withdrawalFee,
                "Withdrawal fee for $accountType account",
                $receiptNumber
            );
        }

        $this->conn->commit();

        // Get withdrawal receipt details
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



    public function updateLoanSchedule($loanId, $repayAmount) {
        try {
            $this->conn->begin_transaction();
    
            // Get unpaid schedule entries ordered by due date
            $stmt = $this->conn->prepare("
                SELECT * FROM loan_schedule 
                WHERE loan_id = ? AND (status = 'unpaid' OR status = 'partial')
                ORDER BY due_date ASC
            ");
            $stmt->bind_param("i", $loanId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $remainingRepayment = $repayAmount;
            $today = date('Y-m-d');
            
            while ($schedule = $result->fetch_assoc()) {
                if ($remainingRepayment <= 0) break;
                
                $dueAmount = $schedule['amount'];
                $currentRepaidAmount = $schedule['repaid_amount'] ?? 0;
                $remainingDueAmount = $dueAmount - $currentRepaidAmount;
                
                // Calculate how much to apply to this installment
                $paymentForThisInstallment = min($remainingRepayment, $remainingDueAmount);
                $newRepaidAmount = $currentRepaidAmount + $paymentForThisInstallment;
                
                // Calculate default amount if payment is late
                $defaultAmount = 0;
                if (strtotime($schedule['due_date']) < strtotime($today)) {
                    $defaultAmount = $dueAmount - $newRepaidAmount;
                    if ($defaultAmount < 0) $defaultAmount = 0;
                }
                
                // Determine status
                $newStatus = 'unpaid';
                if ($newRepaidAmount >= $dueAmount) {
                    $newStatus = 'paid';
                    $defaultAmount = 0;
                } elseif ($newRepaidAmount > 0) {
                    $newStatus = 'partial';
                }
                
                // Update schedule entry
                $updateStmt = $this->conn->prepare("
                    UPDATE loan_schedule 
                    SET repaid_amount = ?,
                        default_amount = ?,
                        status = ?,
                        paid_date = CASE 
                            WHEN ? = 'paid' THEN NOW()
                            ELSE NULL 
                        END
                    WHERE id = ?
                ");
                
                $updateStmt->bind_param(
                    "ddssi",
                    $newRepaidAmount,
                    $defaultAmount,
                    $newStatus,
                    $newStatus,
                    $schedule['id']
                );
                
                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to update schedule entry: " . $updateStmt->error);
                }
                
                $remainingRepayment -= $paymentForThisInstallment;
            }
            
            // Check if loan is fully paid
            $checkStmt = $this->conn->prepare("
                SELECT COUNT(*) as unpaid_count
                FROM loan_schedule
                WHERE loan_id = ? AND status != 'paid'
            ");
            $checkStmt->bind_param("i", $loanId);
            $checkStmt->execute();
            $unpaidResult = $checkStmt->get_result()->fetch_assoc();
            
            if ($unpaidResult['unpaid_count'] == 0) {
                // Update loan status to completed (3)
                $updateLoanStmt = $this->conn->prepare("UPDATE loan SET status = 3 WHERE loan_id = ?");
                $updateLoanStmt->bind_param("i", $loanId);
                $updateLoanStmt->execute();
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error updating loan schedule: " . $e->getMessage());
            return false;
        }
    }
    
    // Also update the getLoanSchedule method:
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
                    END as paid_date
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
                    'paid_date' => $row['paid_date']
                );
            }
            
            return $schedule;
            
        } catch (Exception $e) {
            error_log("Error getting loan schedule: " . $e->getMessage());
            return false;
        }
    }






    // Add these methods to your AccountModel class:

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

public function createLoanSchedule($loanId) {
    try {
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
        
        // Calculate schedule parameters
        $totalLoanAmount = $loan['amount'];
        $loanTerm = $loan['loan_term'];
        $monthlyPrincipal = round($totalLoanAmount / $loanTerm, 2);
        $interestRate = $loan['interest_rate'] / 100 / 12; // Monthly rate
        
        // Start from meeting date or loan date
        $startDate = new DateTime($loan['meeting_date'] ?? $loan['date_created']);
        $paymentDate = clone $startDate;
        $paymentDate->modify('+1 month');
        
        // Track total principals paid
        $principalsPaid = 0;
        $remainingBalance = $totalLoanAmount;
        
        // Delete existing schedule
        $this->conn->query("DELETE FROM loan_schedule WHERE loan_id = $loanId");
        
        // Create new schedule
        $insertStmt = $this->conn->prepare("
            INSERT INTO loan_schedule (
                loan_id, due_date, principal, interest, 
                amount, balance, status
            ) VALUES (?, ?, ?, ?, ?, ?, 'unpaid')
        ");
        
        for ($i = 0; $i < $loanTerm; $i++) {
            // Calculate interest on remaining balance
            $interest = round(($totalLoanAmount - $principalsPaid) * $interestRate, 2);
            
            // Calculate due amount
            $dueAmount = $monthlyPrincipal + $interest;
            
            // Insert schedule entry
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
            
            // Update tracking variables
            $principalsPaid += $monthlyPrincipal;
            $remainingBalance -= $monthlyPrincipal;
            if ($remainingBalance < 0) $remainingBalance = 0;
            
            // Move to next month
            $paymentDate->modify('+1 month');
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error creating loan schedule: " . $e->getMessage());
        return false;
    }
}

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

public function beginTransaction() {
    return $this->conn->begin_transaction();
}

public function commitTransaction() {
    return $this->conn->commit();
}

public function rollbackTransaction() {
    return $this->conn->rollback();
}




    
    public function recalculateOutstandingBalance($loanId) {
        $query = $this->conn->prepare("
            SELECT 
                l.total_payable - COALESCE(SUM(lr.amount_repaid), 0) as outstanding_balance
            FROM 
                loan l
            LEFT JOIN 
                loan_repayments lr ON l.loan_id = lr.loan_id
            WHERE 
                l.loan_id = ?
        ");
        $query->bind_param("i", $loanId);
        $query->execute();
        $result = $query->get_result();
        $row = $result->fetch_assoc();
        
        $outstandingBalance = $row['outstanding_balance'];
        
        // Update the loan table with the new outstanding balance
        $updateQuery = $this->conn->prepare("UPDATE loan SET outstanding_balance = ? WHERE loan_id = ?");
        $updateQuery->bind_param("di", $outstandingBalance, $loanId);
        $updateQuery->execute();
        
        return $outstandingBalance;
    }


    private function recalculateTotalLoans($accountId) {
        $stmt = $this->conn->prepare("
            SELECT SUM(amount - COALESCE(
                (SELECT SUM(amount) FROM transactions WHERE type = 'Loan Repayment' AND loan_id = loan.loan_id), 0
            )) as total_loans
            FROM loan 
            WHERE account_id = ? AND status != 3
        ");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total_loans'] ?? 0;
    }


    public function getNetBalance($accountId) {
        $stmt = $this->conn->prepare("SELECT net_balance FROM client_accounts WHERE account_id = ?");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['net_balance'] ?? 0;
    }


    private function updateNetBalance($accountId) {
        $totalSavings = $this->getTotalSavings($accountId);
        $totalLoans = $this->getTotalOutstandingLoans($accountId);
        $netBalance = $totalSavings - $totalLoans;
    
        $stmt = $this->conn->prepare("UPDATE client_accounts SET net_balance = ? WHERE account_id = ?");
        $stmt->bind_param("di", $netBalance, $accountId);
        $stmt->execute();
    }



    // Get loan due date
    public function getLoanDueDate($loanId) {
        $stmt = $this->conn->prepare("SELECT DATE_ADD(date_created, INTERVAL loan_term MONTH) as due_date FROM loan WHERE loan_id = ?");
        $stmt->bind_param("i", $loanId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['due_date'] ?? null;
    }


    //get due amount
    public function getDueAmount($loanId) {
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
            error_log("Error in getDueAmount: " . $e->getMessage());
            return 0;
        }
    }


    // Get next loan due date
    public function getNextLoanDueDate($loanId) {
        $stmt = $this->conn->prepare("
            SELECT DATE_ADD(date_created, INTERVAL 
                CASE 
                    WHEN loan_term_type = 'days' THEN loan_term 
                    WHEN loan_term_type = 'weeks' THEN loan_term * 7 
                    WHEN loan_term_type = 'months' THEN loan_term * 30 
                    WHEN loan_term_type = 'years' THEN loan_term * 365 
                END DAY) as next_due_date 
            FROM loan 
            WHERE loan_id = ?");
        $stmt->bind_param("i", $loanId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['next_due_date'] ?? null;
    }


    /**
 * Get total withdrawals for an account
 * 
 * @param int $accountId ID of the account
 * @param string $accountType Filter by account type
 * @return float Total withdrawals amount
 */
public function getTotalWithdrawals($accountId, $accountType = 'all') {
    try {
        $query = "SELECT COALESCE(SUM(amount + COALESCE(withdrawal_fee, 0)), 0) as total 
                  FROM savings 
                  WHERE account_id = ? AND type = 'Withdrawal'";
        $params = [$accountId];
        $types = "i";
        
        if ($accountType !== 'all') {
            $query .= " AND account_type = ?";
            $params[] = $accountType;
            $types .= "s";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Error in getTotalWithdrawals: " . $e->getMessage());
        return 0;
    }
}
    // Get loan outstanding balance
    public function getLoanOutstandingBalance($loanId) {
        $stmt = $this->conn->prepare("
            SELECT amount - COALESCE(
                (SELECT SUM(amount) FROM transactions 
                WHERE type = 'Loan Repayment' AND loan_id = loan.loan_id), 0
            ) as outstanding_balance 
            FROM loan 
            WHERE loan_id = ?");
        $stmt->bind_param("i", $loanId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['outstanding_balance'] ?? 0;
    }

    /**
     * Get the next available shareholder number
     * @return array Result with status and next number
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
     * Check if a shareholder number already exists
     * @param string $shareholder_no The shareholder number to check
     * @return array Result with status and exists flag
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
 * Get fully paid loans for an account - Updated for your database structure
 * 
 * @param int $accountId ID of the account
 * @param string $accountType Filter by account type
 * @return array Array of fully paid loans
 */
public function getFullyPaidLoans($accountId, $accountType = 'all') {
    try {
        $query = "
            SELECT 
                l.loan_id,
                l.ref_no,
                COALESCE(l.loan_product_id, 'N/A') as loan_product_id,
                l.amount,
                COALESCE(l.interest_rate, 0) as interest_rate,
                l.date_applied,
                l.status,
                -- Calculate total paid from repayments
                COALESCE((
                    SELECT SUM(amount_repaid) 
                    FROM loan_repayments 
                    WHERE loan_id = l.loan_id
                ), 0) as total_paid,
                -- Calculate interest paid
                COALESCE((
                    SELECT SUM(amount_repaid) 
                    FROM loan_repayments 
                    WHERE loan_id = l.loan_id
                ), 0) - l.amount as interest_paid,
                -- Get completion date
                COALESCE((
                    SELECT MAX(date_paid) 
                    FROM loan_repayments 
                    WHERE loan_id = l.loan_id
                ), l.date_applied) as date_completed,
                -- Calculate duration
                COALESCE(TIMESTAMPDIFF(MONTH, l.date_applied, (
                    SELECT MAX(date_paid) 
                    FROM loan_repayments 
                    WHERE loan_id = l.loan_id
                )), 12) as duration_months
            FROM loan l
            WHERE l.account_id = ?
            AND l.status = 3"; // Status 3 = Completed
        
        $params = [$accountId];
        $types = "i";
        
        if ($accountType !== 'all') {
            $query .= " AND l.account_type = ?";
            $params[] = $accountType;
            $types .= "s";
        }
        
        $query .= " ORDER BY l.loan_id DESC";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $loans = $result->fetch_all(MYSQLI_ASSOC);
        
        return $loans;
        
    } catch (Exception $e) {
        error_log("Error in getFullyPaidLoans: " . $e->getMessage());
        return [];
    }
}

/**
 * Get summary statistics for fully paid loans - Updated version
 * 
 * @param int $accountId ID of the account
 * @param string $accountType Filter by account type
 * @return array Summary statistics
 */
public function getFullyPaidLoansSummary($accountId, $accountType = 'all') {
    try {
        $query = "
            SELECT 
                COUNT(l.loan_id) as total_loans,
                COALESCE(SUM(l.amount), 0) as total_principal,
                COALESCE(SUM((
                    SELECT SUM(amount_repaid) 
                    FROM loan_repayments lr 
                    WHERE lr.loan_id = l.loan_id
                )), SUM(l.amount)) as total_amount_paid,
                COALESCE(SUM((
                    SELECT SUM(amount_repaid) 
                    FROM loan_repayments lr 
                    WHERE lr.loan_id = l.loan_id
                )) - SUM(l.amount), 0) as total_interest_paid
            FROM loan l
            WHERE l.account_id = ?
            AND l.status = 3"; // Status 3 = Completed
        
        $params = [$accountId];
        $types = "i";
        
        if ($accountType !== 'all') {
            $query .= " AND l.account_type = ?";
            $params[] = $accountType;
            $types .= "s";
        }
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        
        $result = $stmt->get_result()->fetch_assoc();
        
        // Ensure all values are numeric and handle nulls
        return [
            'total_loans' => intval($result['total_loans'] ?? 0),
            'total_principal' => floatval($result['total_principal'] ?? 0),
            'total_amount_paid' => floatval($result['total_amount_paid'] ?? 0),
            'total_interest_paid' => max(0, floatval($result['total_interest_paid'] ?? 0))
        ];
        
    } catch (Exception $e) {
        error_log("Error in getFullyPaidLoansSummary: " . $e->getMessage());
        return [
            'total_loans' => 0,
            'total_principal' => 0,
            'total_amount_paid' => 0,
            'total_interest_paid' => 0
        ];
    }
}

/**
 * Get loan schedule for a fully paid loan
 * 
 * @param int $loanId ID of the loan
 * @return array|false Loan schedule or false on error
 */
public function getFullyPaidLoanSchedule($loanId) {
    try {
        $stmt = $this->conn->prepare("
            SELECT 
                ls.*,
                COALESCE(ls.repaid_amount, 0) as repaid_amount,
                COALESCE(ls.default_amount, 0) as default_amount,
                ls.paid_date
            FROM loan_schedule ls
            WHERE ls.loan_id = ? 
            ORDER BY ls.due_date ASC
        ");
        
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $loanId);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        
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
                'paid_date' => $row['paid_date']
            );
        }
        
        return $schedule;
        
    } catch (Exception $e) {
        error_log("Error in getFullyPaidLoanSchedule: " . $e->getMessage());
        return false;
    }
}

/**
 * Get details for a fully paid loan
 * 
 * @param int $loanId ID of the loan
 * @return array|false Loan details or false on error
 */
public function getFullyPaidLoanDetails($loanId) {
    try {
        $stmt = $this->conn->prepare("
            SELECT 
                l.*,
                CONCAT(ca.first_name, ' ', ca.last_name) as client_name,
                MAX(lr.date_paid) as date_completed,
                COUNT(lr.id) as total_payments_made,
                SUM(lr.amount_repaid) as total_amount_paid,
                (SUM(lr.amount_repaid) - l.amount) as total_interest_paid
            FROM loan l
            JOIN client_accounts ca ON l.account_id = ca.account_id
            LEFT JOIN loan_repayments lr ON l.loan_id = lr.loan_id
            WHERE l.loan_id = ? AND l.status = 3
            GROUP BY l.loan_id
        ");
        
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $loanId);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
        
    } catch (Exception $e) {
        error_log("Error in getFullyPaidLoanDetails: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a loan is fully paid
 * 
 * @param int $loanId ID of the loan
 * @return bool True if fully paid, false otherwise
 */
public function isLoanFullyPaid($loanId) {
    try {
        $stmt = $this->conn->prepare("
            SELECT 
                l.amount,
                COALESCE(SUM(lr.amount_repaid), 0) as total_paid,
                l.status
            FROM loan l
            LEFT JOIN loan_repayments lr ON l.loan_id = lr.loan_id
            WHERE l.loan_id = ?
            GROUP BY l.loan_id
        ");
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $loanId);
        if (!$stmt->execute()) {
            return false;
        }
        
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result) {
            return false;
        }
        
        // Check if status is completed (3) and total paid >= loan amount
        return ($result['status'] == 3 && floatval($result['total_paid']) >= floatval($result['amount']));
        
    } catch (Exception $e) {
        error_log("Error in isLoanFullyPaid: " . $e->getMessage());
        return false;
    }
}

/**
 * Get payment history for a fully paid loan
 * 
 * @param int $loanId ID of the loan
 * @return array Payment history
 */
public function getFullyPaidLoanPaymentHistory($loanId) {
    try {
        $stmt = $this->conn->prepare("
            SELECT 
                lr.*,
                u.firstname as served_by_name,
                DATE(lr.date_paid) as payment_date
            FROM loan_repayments lr
            LEFT JOIN user u ON lr.served_by = u.user_id
            WHERE lr.loan_id = ?
            ORDER BY lr.date_paid ASC
        ");
        
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $loanId);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error in getFullyPaidLoanPaymentHistory: " . $e->getMessage());
        return [];
    }
}

    // Add a transaction
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
     * Get receipt details for withdrawal
    * 
    * @param int $withdrawalId Withdrawal ID
    * @return array Receipt details
    */
    public function getReceiptDetails($withdrawalId, $type) {
        if ($type === 'Withdrawal') {
            return $this->getWithdrawalReceiptDetails($withdrawalId);
        } else {
            return $this->getSavingsReceiptDetails($withdrawalId);
        }
    }
    

    public function getLastError() {
        return $this->lastError;
    }

    // Close the database connection
    public function __destruct() {
        $this->conn->close();
    }
}
?>