<?php
require_once('../models/accountModel.php');

/**
 * AccountController Class
 * 
 * This class handles all account-related operations and serves as an intermediary
 * between the view (UI) and the model (database operations).
 */
class AccountController {
    private $model;

    /**
     * Constructor
     * Initializes the AccountModel
     */
    public function __construct() {
        $this->model = new AccountModel();
    }

    public function getModel() {
        return $this->model;
    }

    /**
     * Create a new account
     * 
     * @param array $accountData Array containing account details
     * @return array Status and message of the operation
     */
    public function createAccount($accountData) {
        $result = $this->model->createAccount($accountData);
        if (!$result) {
            return ['status' => 'error', 'message' => 'Error creating account: ' . $this->model->getLastError()];
        }
        return ['status' => 'success', 'message' => 'Account created successfully'];
    }

    /**
     * Update an existing account
     * 
     * @param array $accountData Array containing updated account details
     * @return array Status and message of the operation
     */
    public function updateAccount($accountData) {
        $result = $this->model->updateAccount($accountData);
        if (!$result) {
            return ['status' => 'error', 'message' => 'Error updating account: ' . $this->model->getLastError()];
        }
        return ['status' => 'success', 'message' => 'Account updated successfully'];
    }

    /**
     * Delete an account
     * 
     * @param int $accountId ID of the account to be deleted
     * @return array Status and message of the operation
     */
    public function deleteAccount($accountId) {
        $result = $this->model->deleteAccount($accountId);
        if (!$result) {
            return ['status' => 'error', 'message' => 'Error deleting account: ' . $this->model->getLastError()];
        }
        return ['status' => 'success', 'message' => 'Account deleted successfully'];
    }

    /**
     * Get all accounts
     * 
     * @return array Array of all accounts
     */
    public function getAllAccounts() {
        return $this->model->getAllAccounts();
    }

    /**
     * Get account transactions
     * 
     * @param int $accountId ID of the account
     * @param string $accountType Filter by account type
     * @return array Array of transactions
     */
    public function getAccountTransactions($accountId, $accountType = 'all') {
        return $this->model->getAccountTransactions($accountId, $accountType);
    }

    /**
     * Get account loans
     * 
     * @param int $accountId ID of the account
     * @param string $accountType Filter by account type
     * @return array Array of loans
     */
    public function getAccountLoans($accountId, $accountType = 'all') {
        return $this->model->getAccountLoans($accountId, $accountType);
    }

    /**
     * Get account savings
     * 
     * @param int $accountId ID of the account
     * @param string $accountType Filter by account type
     * @return array Array of savings records
     */
    public function getAccountSavings($accountId, $accountType = 'all') {
        return $this->model->getAccountSavings($accountId, $accountType);
    }

    /**
     * Get total savings for an account
     * 
     * @param int $accountId ID of the account
     * @param string $accountType Filter by account type
     * @return float Total savings amount
     */
    public function getTotalSavings($accountId, $accountType = 'all') {
        return $this->model->getTotalSavings($accountId, $accountType);
    }

    /**
     * Get total withdrawals for an account
     * 
     * @param int $accountId ID of the account
     * @param string $accountType Filter by account type
     * @return float Total withdrawals amount
     */
    public function getTotalWithdrawals($accountId, $accountType = 'all') {
        return $this->model->getTotalWithdrawals($accountId, $accountType);
    }

    /**
     * Get total loans for an account
     * 
     * @param int $accountId ID of the account
     * @param string $accountType Filter by account type
     * @return float Total loans amount
     */
    public function getTotalLoans($accountId, $accountType = 'all') {
        return $this->model->getTotalLoans($accountId, $accountType);
    }

    /**
     * Add savings to an account
     * 
     * @param int $accountId ID of the account
     * @param float $amount Amount to be added as savings
     * @param string $paymentMode Mode of payment
     * @return array Status and message of the operation
     */
    public function addSavings($accountId, $amount, $paymentMode) {
        $result = $this->model->addSavings($accountId, $amount, $paymentMode);
        if (!$result) {
            return ['status' => 'error', 'message' => $this->model->getLastError()];
        }
        return ['status' => 'success', 'message' => 'Savings added successfully'];
    }

    /**
     * Get savings data for charting
     * 
     * @param int $accountId ID of the account
     * @param string $filter Time filter for data
     * @param string|null $startDate Start date for custom filter
     * @param string|null $endDate End date for custom filter
     * @return array Array of savings data
     */
    public function getSavingsData($accountId, $filter, $startDate = null, $endDate = null) {
        return $this->model->getSavingsData($accountId, $filter, $startDate, $endDate);
    }

    /**
     * Get transaction data for charting
     * 
     * @param int $accountId ID of the account
     * @param string $filter Filter for transaction type
     * @return array Array of transaction data
     */
    public function getTransactionData($accountId, $filter) {
        return $this->model->getTransactionData($accountId, $filter);
    }

    /**
     * Add a new loan
     * 
     * @param int $accountId ID of the account
     * @param float $amount Loan amount
     * @param int $loanType Type of loan
     * @param int $status Loan status
     * @param string $purpose Purpose of the loan
     * @param int $planId ID of the loan plan
     * @return array Status and message of the operation
     */
    public function addLoan($accountId, $amount, $loanType, $status, $purpose, $planId) {
        $result = $this->model->addLoan($accountId, $amount, $loanType, $status, $purpose, $planId);
        if (!$result) {
            return ['status' => 'error', 'message' => 'Error adding loan: ' . $this->model->getLastError()];
        }
        return ['status' => 'success', 'message' => 'Loan added successfully', 'loanId' => $result];
    }

    /**
     * Update loan status
     * 
     * @param int $loanId ID of the loan
     * @param int $status New status of the loan
     * @return array Status and message of the operation
     */
    public function updateLoanStatus($loanId, $status) {
        $result = $this->model->updateLoanStatus($loanId, $status);
        if (!$result) {
            return ['status' => 'error', 'message' => 'Error updating loan status: ' . $this->model->getLastError()];
        }
        return ['status' => 'success', 'message' => 'Loan status updated successfully'];
    }

    /**
     * Repay a loan
     * 
     * @param int $accountId ID of the account
     * @param int $loanId ID of the loan
     * @param float $repayAmount Amount to repay
     * @return array Status and message of the operation
     */
    public function repayLoan($accountId, $loanId, $repayAmount, $paymentMode, $servedBy, $receiptNumber) {
        error_log("Repay Loan Request Received: accountId=$accountId, loanId=$loanId, repayAmount=$repayAmount, paymentMode=$paymentMode, servedBy=$servedBy, receiptNumber=$receiptNumber");
    
        try {
            // Validate input
            if (empty($accountId) || empty($loanId) || empty($repayAmount) || empty($paymentMode) || empty($receiptNumber)) {
                throw new Exception("Missing required parameters");
            }
    
            // Process repayment
            $result = $this->model->repayLoan($accountId, $loanId, $repayAmount, $paymentMode, $servedBy, $receiptNumber);
    
            if ($result['status'] === 'success') {
                error_log("Repay Loan Success: " . json_encode($result));
                return $result;
            } else {
                throw new Exception($result['message']);
            }
        } catch (Exception $e) {
            error_log("Repay Loan Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get loan due date
     * 
     * @param int $loanId ID of the loan
     * @return string|null Due date of the loan or null if not found
     */
    public function getLoanDueDate($loanId) {
        return $this->model->getLoanDueDate($loanId);
    }

    /**
     * Get loan by ID
     * 
     * @param int $loanId ID of the loan
     * @return array|bool Loan details array or false if not found
     */
    public function getLoanById($loanId) {
        return $this->model->getLoanById($loanId);
    }

    /**
     * Get next loan due date
     * 
     * @param int $loanId ID of the loan
     * @return string|null Next due date of the loan or null if not found
     */
    public function getNextLoanDueDate($loanId) {
        return $this->model->getNextLoanDueDate($loanId);
    }

    /**
     * Get loan details including outstanding balance and next due date
     * 
     * @param int $loanId ID of the loan
     * @return array Array containing loan details, outstanding balance, and next due date
     */
    public function getLoanDetails($loanId) {
        try {
            // Get base loan details
            $stmt = $this->conn->prepare("
                SELECT 
                    l.loan_id, 
                    l.ref_no, 
                    l.amount,
                    l.interest_rate,
                    l.monthly_payment,
                    l.total_payable,
                    l.status,
                    l.date_applied,
                    l.next_payment_date,
                    (l.amount - COALESCE((SELECT SUM(amount_repaid) FROM loan_repayments WHERE loan_id = l.loan_id), 0)) as outstanding_balance
                FROM loan l
                WHERE l.loan_id = ?
            ");
            
            $stmt->bind_param("i", $loanId);
            $stmt->execute();
            $loan = $stmt->get_result()->fetch_assoc();
            
            if (!$loan) {
                throw new Exception("Loan not found.");
            }
            
            // Get the oldest unpaid or partially paid installment from loan_schedule
            $scheduleStmt = $this->conn->prepare("
                SELECT * FROM loan_schedule 
                WHERE loan_id = ? 
                AND (status = 'unpaid' OR status = 'partial')
                ORDER BY due_date ASC 
                LIMIT 1
            ");
            
            $scheduleStmt->bind_param("i", $loanId);
            $scheduleStmt->execute();
            $nextDue = $scheduleStmt->get_result()->fetch_assoc();
            
            if ($nextDue) {
                // Use the full amount from the schedule, not the adjusted amount
                $loan['next_due_amount'] = floatval($nextDue['amount']);
                $loan['next_due_date'] = $nextDue['due_date'];
                $loan['is_overdue'] = (strtotime($nextDue['due_date']) < time());
                
                // Handle partial payments
                if ($nextDue['status'] === 'partial' && $nextDue['repaid_amount'] > 0) {
                    $loan['partial_paid'] = floatval($nextDue['repaid_amount']);
                    $loan['next_due_amount'] = floatval($nextDue['amount']) - floatval($nextDue['repaid_amount']);
                }
            } else {
                $loan['next_due_amount'] = 0;
                $loan['next_due_date'] = null;
                $loan['is_overdue'] = false;
            }
            
            // Log for debugging
            error_log("Loan details for ID $loanId: " . json_encode($loan));
            
            return ['status' => 'success', 'loan' => $loan];
            
        } catch (Exception $e) {
            error_log("Error in getLoanDetails: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    //loan schedule
    public function getDueAmount($loanId) {
        $loanDetails = $this->model->getLoanDetailsForRepayment($loanId);
        if ($loanDetails) {
            $dueAmount = $this->model->getDueAmount($loanId);
            return [
                'status' => 'success',
                'dueAmount' => $dueAmount,
                'outstandingBalance' => $loanDetails['outstanding_balance'],
                'refNo' => $loanDetails['ref_no']
            ];
        } else {
            return ['status' => 'error', 'message' => 'Loan not found'];
        }
    }
    
    public function getLoanRepayments($accountId) {
        return $this->model->getLoanRepayments($accountId);
    }
    
    public function getRepaymentDetails($repaymentId) {
        return $this->model->getRepaymentDetails($repaymentId);
    }
    
    public function getSavingsDetails($savingsId) {
        return $this->model->getSavingsDetails($savingsId);
    }

    public function getLoanSchedule($loanId) {
        $schedule = $this->model->getLoanSchedule($loanId);
        if ($schedule) {
            return ['status' => 'success', 'schedule' => $schedule];
        }
        return ['status' => 'error', 'message' => 'Loan schedule not found'];
    }

    //reload loan repayments table 
    public function getAccountRepayments($accountId) {
        $repayments = $this->model->getLoanRepayments($accountId);
        return [
            'status' => 'success',
            'repayments' => $repayments
        ];
    }

    //filtered date
    public function getOutstandingPrincipalForAccount($accountId, $accountType = 'all') {
        return $this->getModel()->getTotalOutstandingPrincipal($accountId, $accountType);
    }
    
    // Update the getFilteredSummary method
    public function getAccountById($accountId) {
        try {
            $accountDetails = $this->model->getAccountById($accountId);
            if ($accountDetails) {
                // Get total loan amount
                $totalLoanAmount = $this->model->getTotalLoanAmount($accountId);
                $accountDetails['total_loan_amount'] = $totalLoanAmount;
                
                // Log for debugging
                error_log("Account details for ID $accountId: " . json_encode($accountDetails));
            }
            return $accountDetails;
        } catch (Exception $e) {
            error_log("Error in getAccountById: " . $e->getMessage());
            throw $e;
        }
    }


    /**
 * Get total outstanding loans using the corrected calculation
 * This gets the current principal balance from loan schedule
 */
public function getTotalOutstandingLoans($accountId, $accountType = 'all') {
    return $this->model->getTotalOutstandingLoans($accountId, $accountType);
}

/**
 * Get active loans count
 */
public function getActiveLoansCount($accountId, $accountType = 'all') {
    return $this->model->getActiveLoansCount($accountId, $accountType);
}

/**
 * Get total group savings
 */
public function getTotalGroupSavings($accountId, $accountType = 'all') {
    return $this->model->getTotalGroupSavings($accountId, $accountType);
}

    // Updated getFilteredSummary method with withdrawal support
    public function getFilteredSummary($accountId, $accountType) {
    try {
        $totalSavings = $this->model->getFilteredTotalSavings($accountId, $accountType);
        $totalWithdrawals = $this->model->getTotalWithdrawals($accountId, $accountType);
        $outstandingLoans = $this->model->getTotalOutstandingLoans($accountId, $accountType);
        $activeLoansCount = $this->model->getActiveLoansCount($accountId, $accountType);
        $totalGroupSavings = $this->model->getTotalGroupSavings($accountId, $accountType);
        
        return [
            'status' => 'success',
            'totalSavings' => $totalSavings,
            'totalWithdrawals' => $totalWithdrawals,
            'outstandingLoans' => $outstandingLoans,
            'activeLoansCount' => $activeLoansCount,
            'totalGroupSavings' => $totalGroupSavings
        ];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}


    // Update the handleGetFilteredSummary method
    private function handleGetFilteredSummary() {
    try {
        $accountId = $_GET['accountId'] ?? null;
        $accountType = $_GET['accountType'] ?? 'all';

        if (!$accountId) {
            throw new Exception("Account ID is required");
        }

        $summary = $this->getFilteredSummary($accountId, $accountType);
        
        if ($summary['status'] === 'success') {
            echo json_encode([
                'status' => 'success',
                'totalSavings' => $summary['totalSavings'],
                'totalWithdrawals' => $summary['totalWithdrawals'],
                'outstandingLoans' => $summary['outstandingLoans'],
                'activeLoansCount' => $summary['activeLoansCount'],
                'totalGroupSavings' => $summary['totalGroupSavings']
            ]);
        } else {
            echo json_encode($summary);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}


/**
 * Helper method to get all current stats for an account
 */
public function getAllAccountStats($accountId, $accountType = 'all') {
    try {
        return [
            'totalSavings' => $this->model->getFilteredTotalSavings($accountId, $accountType),
            'totalWithdrawals' => $this->model->getTotalWithdrawals($accountId, $accountType),
            'outstandingLoans' => $this->model->getTotalOutstandingLoans($accountId, $accountType),
            'activeLoansCount' => $this->model->getActiveLoansCount($accountId, $accountType),
            'totalGroupSavings' => $this->model->getTotalGroupSavings($accountId, $accountType)
        ];
    } catch (Exception $e) {
        error_log("Error getting account stats: " . $e->getMessage());
        return null;
    }
}

    // Add this helper method to your AccountModel class
    public function getConnection() {
        return $this->conn;
    }

    //getaccountsummary
    private function getAccountSummary($accountId) {
        $totalSavings = $this->model->getTotalSavings($accountId);
        $totalLoans = $this->model->getTotalLoans($accountId);
        $netBalance = $totalSavings - $totalLoans;
    
        return [
            'totalSavings' => $totalSavings,
            'totalLoans' => $totalLoans,
            'netBalance' => $netBalance
        ];
    }

    //handle withdraw
    private function handleWithdraw() {
        try {
            // Validate required parameters
            $accountId = $_POST['accountId'] ?? null;
            $amount = $_POST['amount'] ?? null;
            $withdrawalFee = $_POST['withdrawalFee'] ?? 0;
            $accountType = $_POST['accountType'] ?? null;
            $receiptNumber = $_POST['receiptNumber'] ?? null;
            $paymentMode = $_POST['paymentMode'] ?? null;
            $servedBy = $_SESSION['user_name'] ?? 'System';
    
            // Validate required fields
            if (!$accountId || !$amount || !$accountType || !$receiptNumber || !$paymentMode) {
                throw new Exception('Missing required parameters');
            }
    
            // Convert to proper numeric values
            $amount = floatval($amount);
            $withdrawalFee = floatval($withdrawalFee);
    
            if ($amount <= 0) {
                throw new Exception('Invalid withdrawal amount');
            }
    
            // Calculate total amount to be deducted (withdrawal amount + fee)
            $totalDeduction = $amount + $withdrawalFee;
    
            // Process withdrawal
            $result = $this->model->withdraw(
                $accountId,
                $totalDeduction,
                $paymentMode,
                $accountType,
                $receiptNumber,
                $withdrawalFee,
                $servedBy
            );
    
            if ($result['status'] === 'success') {
                // Get updated totals after the withdrawal
                $newTotalSavings = $this->model->getFilteredTotalSavings($accountId, $accountType);
                $newNetBalance = $newTotalSavings - $this->model->getFilteredTotalLoans($accountId, $accountType);
    
                // Include updated totals in the response
                $result['newTotalSavings'] = $newTotalSavings;
                $result['newNetBalance'] = $newNetBalance;
            }
    
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function handleGetAvailableBalance() {
        try {
            $accountId = $_GET['accountId'] ?? null;
            $accountType = $_GET['accountType'] ?? null;

            if (!$accountId || !$accountType) {
                throw new Exception("Missing required parameters");
            }

            $result = $this->model->getAvailableBalance($accountId, $accountType);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function handleGetTransactionReceipt() {
        try {
            $transactionId = $_GET['transactionId'] ?? null;
            $type = $_GET['type'] ?? null;

            if (!$transactionId || !$type) {
                throw new Exception("Missing required parameters");
            }

            $result = $this->model->getTransactionReceipt($transactionId, $type);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }


    private function handleDeleteRepayment() {
    try {
        $repaymentId = $_POST['repaymentId'] ?? null;
        $loanId = $_POST['loanId'] ?? null;
        $amount = $_POST['amount'] ?? null;
        
        if (!$repaymentId || !$loanId || !$amount) {
            throw new Exception('Missing required parameters');
        }
        
        $result = $this->model->deleteRepayment($repaymentId, $loanId, $amount);
        echo json_encode($result);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

/**
     * Handle getting next shareholder number
     */
    private function handleGetNextShareholderNo() {
        try {
            $result = $this->model->getNextShareholderNumber();
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle checking if shareholder number exists
     */
    private function handleCheckShareholderNo() {
        try {
            $shareholder_no = $_POST['shareholder_no'] ?? '';
            
            if (empty($shareholder_no)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Shareholder number is required'
                ]);
                return;
            }
            
            $result = $this->model->checkShareholderNumberExists($shareholder_no);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
 * Get fully paid loans for an account
 * 
 * @param int $accountId ID of the account
 * @param string $accountType Filter by account type
 * @return array Array of fully paid loans with summary
 */
public function getFullyPaidLoans($accountId, $accountType = 'all') {
    try {
        $loans = $this->model->getFullyPaidLoans($accountId, $accountType);
        $summary = $this->model->getFullyPaidLoansSummary($accountId, $accountType);
        
        return [
            'status' => 'success',
            'loans' => $loans,
            'summary' => $summary
        ];
    } catch (Exception $e) {
        error_log("Error in getFullyPaidLoans: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Get fully paid loan schedule with payment history
 * 
 * @param int $loanId ID of the loan
 * @return array Loan schedule with payment details
 */
public function getFullyPaidLoanSchedule($loanId) {
    try {
        $schedule = $this->model->getFullyPaidLoanSchedule($loanId);
        $loanDetails = $this->model->getFullyPaidLoanDetails($loanId);
        
        if (!$schedule || !$loanDetails) {
            throw new Exception("Loan schedule or details not found");
        }
        
        return [
            'status' => 'success',
            'schedule' => $schedule,
            'loan_details' => $loanDetails
        ];
    } catch (Exception $e) {
        error_log("Error in getFullyPaidLoanSchedule: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Handle getting fully paid loans
 */
private function handleGetFullyPaidLoans() {
    try {
        $accountId = $_GET['accountId'] ?? null;
        $accountType = $_GET['accountType'] ?? 'all';

        if (!$accountId) {
            throw new Exception("Account ID is required");
        }

        $result = $this->getFullyPaidLoans($accountId, $accountType);
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Error in handleGetFullyPaidLoans: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handle getting fully paid loan schedule
 */
private function handleGetFullyPaidLoanSchedule() {
    try {
        $loanId = $_GET['loan_id'] ?? null;

        if (!$loanId) {
            throw new Exception("Loan ID is required");
        }

        $result = $this->getFullyPaidLoanSchedule($loanId);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

// handleAction method
public function handleAction() {
    error_log("HandleAction method called");
    error_log("GET data: " . print_r($_GET, true));
    error_log("POST data: " . print_r($_POST, true));

    $action = $_GET['action'] ?? 'unknown';
    error_log("Received action: " . $action);

    switch ($action) {
        case 'create':
            error_log("CreateAccount case entered");
            $this->handleCreateAccount();
            break;
        case 'update':
            error_log("UpdateAccount case entered");
            $this->handleUpdateAccount();
            break;
        case 'get':
            error_log("GetAccount case entered");
            $this->handleGetAccount();
            break;
        case 'delete':
            error_log("DeleteAccount case entered");
            $this->handleDeleteAccount();
            break;
        case 'addSavings':
            error_log("AddSavings case entered");
            $this->handleAddSavings();
            break;
        case 'withdraw':
            $this->handleWithdraw();
            break;
        case 'repayLoan':
            error_log("RepayLoan case entered");
            $this->handleRepayLoan();
            break;
        case 'addLoan':
            error_log("Addloan case entered");
            $this->handleAddLoan();
            break;
        case 'updateLoanStatus':
            error_log("Updateloanstatus case entered");
            $this->handleUpdateLoanStatus();
            break;
        case 'getLoanSchedule':
            $this->handleGetLoanSchedule();
            break;
        case 'getAccountRepayments':
            $this->handleGetAccountRepayments();
            break;
        case 'getAccountDetails':
            error_log("getaccountdetails case entered");
            $this->handleGetAccountDetails();
            break;
        case 'getDueAmount':
            $this->handleGetDueAmount();
            break;
        case 'getRepaymentDetails':
            $this->handleGetRepaymentDetails();
            break;
        case 'getSavingsData':
            error_log("getsavings case entered");
            $this->handleGetSavingsData();
            break;
        case 'getTransactionData':
            error_log("gettransactions case entered");
            $this->handleGetTransactionData();
            break;
        case 'getLoanDetails':
            error_log("getloanstatus case entered");
            $this->handleGetLoanDetails();
            break;
        case 'getSavingsDetails':
            $this->handleGetSavingsDetails();
            break;
        case 'getFilteredSummary':
            $this->handleGetFilteredSummary();
            break;
        case 'getAvailableBalance':
            $this->handleGetAvailableBalance();
            break;
        case 'deleteRepayment':
            $this->handleDeleteRepayment();
            break;
        case 'getTransactionReceipt':
            $this->handleGetTransactionReceipt();
            break;
        case 'getNextShareholderNo':
            $this->handleGetNextShareholderNo();
            break;
        case 'checkShareholderNo':
            $this->handleCheckShareholderNo();
            break;
        // NEW CASES FOR FULLY PAID LOANS
        case 'getFullyPaidLoans':
            error_log("getFullyPaidLoans case entered");
            $this->handleGetFullyPaidLoans();
            break;
        case 'getFullyPaidLoanSchedule':
            error_log("getFullyPaidLoanSchedule case entered");
            $this->handleGetFullyPaidLoanSchedule();
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
}

    private function handleCreateAccount() {
        // Validate required fields
        $requiredFields = ['first_name', 'last_name', 'shareholder_no', 'national_id', 'phone', 'account_type'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Please fill in all required fields'
                ]);
                return;
            }
        }
        // Handle multiple account types
    $accountTypes = isset($_POST['account_type']) ? $_POST['account_type'] : [];
    if (is_string($_POST['account_type'])) {
        // If it's a string (single value), convert to array
        $accountTypes = [$_POST['account_type']];
    }
    
    if (empty($accountTypes)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please select at least one account type'
        ]);
        return;
    }

    $accountData = [
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'shareholder_no' => $_POST['shareholder_no'],
        'national_id' => $_POST['national_id'],
        'phone' => $_POST['phone'],
        'email' => $_POST['email'] ?? '',
        'location' => $_POST['location'] ?? '',
        'division' => $_POST['division'] ?? '',
        'village' => $_POST['village'] ?? '',
        'account_type' => $accountTypes
    ];

    $result = $this->createAccount($accountData);
    echo json_encode($result);
}

    private function handleGetAccount() {
        $accountId = $_POST['account_id'] ?? '';
        if (empty($accountId)) {
            echo json_encode(['status' => 'error', 'message' => 'Account ID is required']);
            return;
        }
        
        $account = $this->getAccountById($accountId);
        
        if ($account) {
            echo json_encode([
                'status' => 'success',
                'data' => $account
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Account not found'
            ]);
        }
    }

    private function handleUpdateAccount() {
        try {
            // Validate required fields
            $requiredFields = [
                'account_id',
                'first_name',
                'last_name',
                'national_id',
                'phone_number',
                'division',
                'village',
                'account_type'
            ];
    
            foreach ($requiredFields as $field) {
                if (!isset($_POST[$field]) || empty($_POST[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
    
            // Handle multiple account types
            $accountTypes = isset($_POST['account_type']) ? $_POST['account_type'] : [];
            if (is_string($_POST['account_type'])) {
                $accountTypes = [$_POST['account_type']];
            }
    
            if (empty($accountTypes)) {
                throw new Exception('Please select at least one account type');
            }
    
            $accountData = [
                'account_id' => $_POST['account_id'],
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'shareholder_no' => $_POST['shareholder_no'],
                'national_id' => $_POST['national_id'],
                'phone_number' => $_POST['phone_number'],
                'email' => $_POST['email'] ?? '',
                'location' => $_POST['location'] ?? '',
                'division' => $_POST['division'],
                'village' => $_POST['village'],
                'account_type' => $accountTypes
            ];
    
            $result = $this->updateAccount($accountData);
            echo json_encode($result);
    
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    private function handleDeleteAccount() {
        $accountId = $_POST['account_id'] ?? '';
        $result = $this->deleteAccount($accountId);
        echo json_encode($result);
    }

    private function handleAddSavings() {
        try {
            // Validate required parameters
            $accountId = $_POST['accountId'] ?? null;
            $amount = $_POST['amount'] ?? null;
            $paymentMode = $_POST['paymentMode'] ?? null;
            $accountType = $_POST['accountType'] ?? null;
            $receiptNumber = $_POST['receiptNumber'] ?? null;
            $servedBy = $_SESSION['user_name'] ?? 'Unknown';
    
            // Check for missing required fields
            if (!$accountId || !$amount || !$paymentMode || !$accountType || !$receiptNumber) {
                throw new Exception('Missing required parameters');
            }
    
            // Validate amount is numeric and positive
            if (!is_numeric($amount) || $amount <= 0) {
                throw new Exception('Invalid amount');
            }
    
            // Add the savings
            $result = $this->model->addSavings(
                $accountId,
                floatval($amount),
                $paymentMode,
                $accountType,
                $receiptNumber,
                $servedBy
            );
    
            if ($result['status'] === 'success' && isset($result['savingsId'])) {
                // Get receipt details for printing
                ReceiptDetails($result['savingsId']);
                $result['receiptDetails'] = $receiptDetails;
            }
    
            // Ensure proper JSON response
            header('Content-Type: application/json');
            echo json_encode($result);
    
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    //loan schedule 
    private function handleGetLoanSchedule() {
        $loanId = $_GET['loanId'] ?? '';
        if (empty($loanId)) {
            echo json_encode(['status' => 'error', 'message' => 'Loan ID is required']);
            return;
        }
        $result = $this->getLoanSchedule($loanId);
        echo json_encode($result);
    }

    //loan repayment
    private function handleGetDueAmount() {
        $loanId = $_GET['loanId'] ?? '';
        if (empty($loanId)) {
            echo json_encode(['status' => 'error', 'message' => 'Loan ID is required']);
            return;
        }
        $dueAmount = $this->model->getDueAmount($loanId);
        echo json_encode(['status' => 'success', 'dueAmount' => $dueAmount]);
    }

    private function handleGetRepaymentDetails() {
        $repaymentId = $_GET['repaymentId'] ?? '';
        if (empty($repaymentId)) {
            echo json_encode(['status' => 'error', 'message' => 'Repayment ID is required']);
            return;
        }
        $result = $this->getRepaymentDetails($repaymentId);
        echo json_encode($result);
    }
    
private function handleGetSavingsDetails() {
    $savingsId = $_GET['savingsId'] ?? '';
    if (empty($savingsId)) {
        echo json_encode(['status' => 'error', 'message' => 'Savings ID is required']);
        return;
    }
    $result = $this->getSavingsDetails($savingsId);
    echo json_encode($result);
}

//handle reload accountrepayments table
private function handleGetAccountRepayments() {
    $accountId = $_GET['accountId'] ?? '';
    if (empty($accountId)) {
        echo json_encode(['status' => 'error', 'message' => 'Account ID is required']);
        return;
    }
    $result = $this->getAccountRepayments($accountId);
    echo json_encode($result);
}

// Update these methods in your AccountController class:
private function updateLoanSchedule($loanId, $repayAmount, $paymentDate) {
    try {
        // Use the model to get schedule entries
        $scheduleEntries = $this->model->getLoanScheduleEntries($loanId);
        if (!$scheduleEntries) {
            throw new Exception("Could not retrieve loan schedule");
        }
        
        $remainingRepayment = $repayAmount;
        $today = new DateTime();
        
        foreach ($scheduleEntries as $schedule) {
            if ($remainingRepayment <= 0) break;
            
            $dueDate = new DateTime($schedule['due_date']);
            $dueAmount = $schedule['amount'];
            $currentRepaidAmount = $schedule['repaid_amount'];
            $remainingDueAmount = $dueAmount - $currentRepaidAmount;
            
            // Calculate default amount if payment is late
            $defaultAmount = 0;
            if ($today > $dueDate && $schedule['status'] != 'paid') {
                $defaultAmount = $remainingDueAmount;
            }
            
            // Calculate how much of this installment can be paid
            $paymentForThisInstallment = min($remainingRepayment, $remainingDueAmount);
            $newRepaidAmount = $currentRepaidAmount + $paymentForThisInstallment;
            
            // Determine new status
            $newStatus = 'unpaid';
            if ($newRepaidAmount >= $dueAmount) {
                $newStatus = 'paid';
                $defaultAmount = 0;
            } elseif ($newRepaidAmount > 0) {
                $newStatus = 'partial';
            }
            
            // Update schedule entry using model method
            $updateData = [
                'loan_id' => $loanId,
                'due_date' => $schedule['due_date'],
                'repaid_amount' => $newRepaidAmount,
                'default_amount' => $defaultAmount,
                'status' => $newStatus,
                'paid_date' => $newStatus == 'paid' ? $paymentDate : null
            ];
            
            $this->model->updateLoanScheduleEntry($updateData);
            
            $remainingRepayment -= $paymentForThisInstallment;
        }
        
        // Check if loan is fully paid
        $unpaidCount = $this->model->getUnpaidInstallmentCount($loanId);
        
        if ($unpaidCount == 0) {
            $this->model->updateLoanStatus($loanId, 3); // 3 = completed
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating loan schedule: " . $e->getMessage());
        return false;
    }
}

private function handleRepayLoan() {
    try {
        // Get form data
        $accountId = $_POST['accountId'] ?? null;
        $loanId = $_POST['loanId'] ?? null;
        $repayAmount = $_POST['repayAmount'] ?? null;
        $paymentMode = $_POST['paymentMode'] ?? null;
        $receiptNumber = $_POST['receiptNumber'] ?? null;
        
        // Log the request data for debugging
        error_log("Repay Loan Request: " . json_encode($_POST));
        
        // Validate required fields
        if (empty($accountId) || empty($loanId) || empty($repayAmount) || empty($paymentMode) || empty($receiptNumber)) {
            throw new Exception("Missing required parameters");
        }
        
        // Validate amount is numeric and positive
        $repayAmount = floatval($repayAmount);
        if ($repayAmount <= 0) {
            throw new Exception("Invalid repayment amount");
        }
        
        // Get the user name
        $servedBy = $_POST['served_by'] ?? $_SESSION['user_id'] ?? 'System';
        
        // Process the loan repayment
        $result = $this->model->repayLoan(
            $accountId,
            $loanId,
            $repayAmount,
            $paymentMode,
            $servedBy,
            $receiptNumber
        );
        
        // Send the JSON response
        header('Content-Type: application/json');
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Repay Loan Error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

    private function handleAddLoan() {
        $accountId = $_POST['accountId'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $loanType = $_POST['loanType'] ?? '';
        $status = $_POST['status'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        $planId = $_POST['planId'] ?? '';
        $result = $this->addLoan($accountId, $amount, $loanType, $status, $purpose, $planId);
        echo json_encode($result);
    }

    private function handleUpdateLoanStatus() {
        $loanId = $_POST['loanId'] ?? '';
        $status = $_POST['status'] ?? '';
        $result = $this->updateLoanStatus($loanId, $status);
        echo json_encode($result);
    }

    private function handleGetAccountDetails() {
        $accountId = $_GET['accountId'] ?? '';
        $account = $this->getAccountById($accountId);
        $loans = $this->getAccountLoans($accountId);
        $savings = $this->getAccountSavings($accountId);
        $totalSavings = $this->getTotalSavings($accountId);
        $totalLoans = $this->getTotalLoans($accountId);
        $transactions = $this->getAccountTransactions($accountId);
        
        if ($account) {
            echo json_encode([
                'status' => 'success',
                'account' => $account,
                'loans' => $loans,
                'savings' => $savings,
                'totalSavings' => $totalSavings,
                'totalLoans' => $totalLoans,
                'transactions' => $transactions
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Account not found']);
        }
    }

    private function handleGetSavingsData() {
        $accountId = $_GET['accountId'] ?? '';
        $filter = $_GET['filter'] ?? '';
        $startDate = $_GET['startDate'] ?? null;
        $endDate = $_GET['endDate'] ?? null;
        $data = $this->getSavingsData($accountId, $filter, $startDate, $endDate);
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    private function handleGetTransactionData() {
        $accountId = $_GET['accountId'] ?? '';
        $filter = $_GET['filter'] ?? '';
        $data = $this->getTransactionData($accountId, $filter);
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    private function handleGetLoanDetails() {
        try {
            $loanId = $_GET['loanId'] ?? '';
            if (empty($loanId)) {
                throw new Exception("Loan ID is required");
            }
            
            // Use the model method to get loan details
            $result = $this->model->getLoanDetailsForRepayment($loanId);
            
            if (!$result) {
                throw new Exception("Loan not found or error retrieving details");
            }
            
            // Process the result and send response
            echo json_encode([
                'status' => 'success',
                'loan' => $result
            ]);
            
        } catch (Exception $e) {
            error_log("Error in handleGetLoanDetails: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate and sanitize input data
     * 
     * @param mixed $data Input data to be validated
     * @param string $type Type of validation to perform
     * @return mixed Sanitized data or false if validation fails
     */
    private function validateInput($data, $type) {
        switch ($type) {
            case 'int':
                return filter_var($data, FILTER_VALIDATE_INT);
            case 'float':
                return filter_var($data, FILTER_VALIDATE_FLOAT);
            case 'email':
                return filter_var($data, FILTER_VALIDATE_EMAIL);
            case 'string':
                return filter_var($data, FILTER_SANITIZE_STRING);
            default:
                return false;
        }
    }

    /**
     * Log errors for debugging purposes
     * 
     * @param string $message Error message to log
     */
    private function logError($message) {
        error_log("AccountController Error: " . $message);
    }
}

// If the script is accessed directly, instantiate and handle the action
if (php_sapi_name() !== 'cli') {
    $controller = new AccountController();
    $controller->handleAction();
}
?>
                