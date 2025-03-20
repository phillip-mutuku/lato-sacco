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
    public function getAccountTransactions($accountId) {
        $stmt = $this->conn->prepare("SELECT * FROM transactions WHERE account_id = ? ORDER BY date DESC");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get account loans
    public function getAccountLoans($accountId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT l.*, 
                       (l.amount - COALESCE(SUM(lr.amount_repaid), 0)) as outstanding_balance
                FROM loan l
                LEFT JOIN loan_repayments lr ON l.loan_id = lr.loan_id
                WHERE l.account_id = ?
                GROUP BY l.loan_id
                HAVING outstanding_balance > 0 OR l.status != 3
            ");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $result = $stmt->get_result();
            $loans = $result->fetch_all(MYSQLI_ASSOC);
            
            // Debug information
            error_log("Fetched loans for account $accountId: " . print_r($loans, true));
            
            return $loans;
        } catch (Exception $e) {
            error_log("Error in getAccountLoans: " . $e->getMessage());
            return false;
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

public function getFilteredTotalLoans($accountId, $accountType) {
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

    // Get account savings
    public function getAccountSavings($accountId, $accountType = 'all') {
        $query = "SELECT * FROM savings WHERE account_id = ?";
        $params = [$accountId];
        $types = "i";
        
        if ($accountType !== 'all') {
            $query .= " AND account_type = ?";
            $params[] = $accountType;
            $types .= "s";
        }
        
        $query .= " ORDER BY date DESC";
        
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
    public function getTotalLoans($accountId) {
        $stmt = $this->conn->prepare("
            SELECT SUM(l.amount) as total_loan_amount,
                   SUM(COALESCE(lr.total_repaid, 0)) as total_repaid
            FROM loan l
            LEFT JOIN (
                SELECT loan_id, SUM(amount_repaid) as total_repaid
                FROM loan_repayments
                GROUP BY loan_id
            ) lr ON l.loan_id = lr.loan_id
            WHERE l.account_id = ?
        ");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $totalLoanAmount = $result['total_loan_amount'] ?? 0;
        $totalRepaid = $result['total_repaid'] ?? 0;
        
        return $totalLoanAmount - $totalRepaid;
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
            SELECT lr.*, l.ref_no as loan_ref_no
            FROM loan_repayments lr
            JOIN loan l ON lr.loan_id = l.loan_id
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
            
            // Debug log
            error_log("Loan details for loan ID $loanId: " . json_encode($loanDetails));
            
            if (!$loanDetails) {
                error_log("No loan found with ID: $loanId");
                return false;
            }
            
            // Add next payment information if available - specifically for this loan
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
                    $loanDetails['next_due_amount'] = $schedule['amount'] - ($schedule['repaid_amount'] ?? 0);
                    $loanDetails['next_due_date'] = $schedule['due_date'];
                    $loanDetails['is_overdue'] = (strtotime($schedule['due_date']) < time());
                    
                    // Debug log
                    error_log("Next payment for loan ID $loanId: " . json_encode([
                        'due_amount' => $loanDetails['next_due_amount'],
                        'due_date' => $loanDetails['next_due_date'],
                        'is_overdue' => $loanDetails['is_overdue']
                    ]));
                }
            }
            
            return $loanDetails;
            
        } catch (Exception $e) {
            error_log("Error in getLoanDetailsForRepayment: " . $e->getMessage());
            return false;
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

public function getTotalOutstandingPrincipal($accountId) {
    try {
        // Get the balance of the most recent unpaid/partial installment for active loans
        $query = "
            SELECT 
                COALESCE(MIN(ls.balance), 0) as outstanding_principal
            FROM loan l
            JOIN loan_schedule ls ON l.loan_id = ls.loan_id
            WHERE l.account_id = ?
                AND l.status IN (1, 2)  -- Active/Released loans only
                AND ls.status IN ('unpaid', 'partial')
            GROUP BY l.loan_id
            ORDER BY ls.due_date ASC
            LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $accountId);
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
                    AND status IN (1, 2)  -- Active/Released loans only
                    AND NOT EXISTS (
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
            
            $newStmt->bind_param("i", $accountId);
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
        
        // Verify the loan exists
        $loanQuery = $this->conn->prepare("SELECT loan_id, account_id, ref_no FROM loan WHERE loan_id = ?");
        $loanQuery->bind_param("i", $loanId);
        $loanQuery->execute();
        $loanResult = $loanQuery->get_result();
        
        if ($loanResult->num_rows === 0) {
            error_log("Loan not found with ID: $loanId");
            return ['status' => 'error', 'message' => "Loan not found with ID: $loanId"];
        }
        
        $loanData = $loanResult->fetch_assoc();
        $loanRefNo = $loanData['ref_no'];
        
        $this->conn->begin_transaction();
        
        try {
            // Get loan details
            $loanDetails = $this->getLoanDetailsForRepayment($loanId);
            if (!$loanDetails) {
                throw new Exception("Error retrieving loan details for loan ID: $loanId");
            }
            
            // Get next unpaid schedule
            $scheduleStmt = $this->conn->prepare("
                SELECT * FROM loan_schedule 
                WHERE loan_id = ? 
                AND (status = 'unpaid' OR status = 'partial')
                ORDER BY due_date ASC LIMIT 1
            ");
            
            $scheduleStmt->bind_param("i", $loanId);
            $scheduleStmt->execute();
            $schedule = $scheduleStmt->get_result()->fetch_assoc();
            
            if (!$schedule) {
                // If no schedule found, just record the payment
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
                
                // Add transaction record - FIXED: removed loan_id column
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
                    'message' => 'Loan repayment recorded successfully',
                    'repaymentDetails' => $this->getRepaymentDetails($repaymentId)
                ];
            }
            
            // Calculate amounts for scheduled loans
            $dueAmount = $schedule['amount'];
            $currentRepaidAmount = $schedule['repaid_amount'] ?? 0;
            $remainingDue = $dueAmount - $currentRepaidAmount;
            $newBalance = $schedule['balance'];
            
            // Calculate default amount if payment is late
            $defaultAmount = 0;
            if (strtotime($schedule['due_date']) < strtotime(date('Y-m-d'))) {
                $defaultAmount = max(0, $remainingDue - $repayAmount);
            }
            
            // Update schedule entry with explicit status values
            $newRepaidAmount = $currentRepaidAmount + $repayAmount;
            
            // Use explicitly defined status values
            $newStatus = 'unpaid'; // default
            if ($newRepaidAmount >= $dueAmount) {
                $newStatus = 'paid';
            } elseif ($newRepaidAmount > 0) {
                $newStatus = 'partial';
            }
            
            // Debug the status values
            error_log("Setting status to: '$newStatus'");
            error_log("Schedule ID: " . $schedule['id']);
            
            // Simplified update query to isolate the issue
            $updateQuery = "
                UPDATE loan_schedule 
                SET repaid_amount = ?,
                    default_amount = ?,
                    status = ?,
                    balance = ?
                WHERE id = ?
            ";
            
            $updateScheduleStmt = $this->conn->prepare($updateQuery);
            if (!$updateScheduleStmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            
            $updateScheduleStmt->bind_param(
                "ddsdi",
                $newRepaidAmount,
                $defaultAmount,
                $newStatus,
                $newBalance,
                $schedule['id']
            );
            
            if (!$updateScheduleStmt->execute()) {
                error_log("MySQL Error: " . $updateScheduleStmt->error);
                throw new Exception("Failed to update loan schedule: " . $updateScheduleStmt->error);
            }
            
            // Handle the paid_date separately to avoid potential issues
            if ($newStatus === 'paid' || $newStatus === 'partial') {
                $paidDateQuery = "
                    UPDATE loan_schedule 
                    SET paid_date = CURRENT_TIMESTAMP
                    WHERE id = ?
                ";
                $paidDateStmt = $this->conn->prepare($paidDateQuery);
                $paidDateStmt->bind_param("i", $schedule['id']);
                $paidDateStmt->execute();
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
            
            // Add transaction record - FIXED: removed loan_id column
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
                'newNetBalance' => $this->getNetBalance($accountId),
                'repaymentDetails' => $this->getRepaymentDetails($repaymentId)
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
        // Calculate the total amount to be deducted (amount + fee)
        $totalAmount = $amount + $withdrawalFee;
        $availableBalance = $this->getFilteredTotalSavings($accountId, $accountType);

        // Check if the available balance is sufficient
        if ($totalAmount > $availableBalance) {
            throw new Exception("Insufficient funds for withdrawal");
        }

        // Get served by username
        $userStmt = $this->conn->prepare("SELECT username FROM user WHERE user_id = ?");
        $userStmt->bind_param("i", $servedBy);
        $userStmt->execute();
        $userResult = $userStmt->get_result()->fetch_assoc();
        $servedBy = $userResult ? $userResult['username'] : 'Unknown';

        // Record the withdrawal transaction with the total deduction (including fee)
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

        // Record only the withdrawal amount (fee is handled separately in another entry)
        $stmt->bind_param("idsssds", 
            $accountId,
            $amount,
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

        // Deduct the total amount (withdrawal + fee) from the total savings
        $updateBalanceStmt = $this->conn->prepare("
            UPDATE client_accounts 
            SET total_savings = total_savings - ?
            WHERE account_id = ?
        ");
        $updateBalanceStmt->bind_param("di", $totalAmount, $accountId);

        if (!$updateBalanceStmt->execute()) {
            throw new Exception("Error updating total savings: " . $updateBalanceStmt->error);
        }

        // Add transactions separately for clarity
        $this->addTransaction(
            $accountId,
            'Withdrawal',
            -$amount,
            "Withdrawal from $accountType account",
            $receiptNumber
        );

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

        return [
            'status' => 'success',
            'message' => 'Withdrawal processed successfully',
            'details' => $this->getReceiptDetails($withdrawalId, 'Withdrawal')
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

    

    public function getLastError() {
        return $this->lastError;
    }

    // Close the database connection
    public function __destruct() {
        $this->conn->close();
    }
}
?>