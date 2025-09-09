<?php
require_once('../helpers/session.php'); 
require_once('../models/accountModel.php');

/**
 * AccountController Class
 * 
 * Handles individual account operations, loan repayments, and single loan operations
 * For account-level summaries and dashboard data, use AccountSummaryController
 */
class AccountController {
    private $model;

    public function __construct() {
        $this->model = new AccountModel();
    }

    public function getModel() {
        return $this->model;
    }

    // =====================================
    // ACCOUNT CRUD OPERATIONS
    // =====================================

    /**
     * Create a new account
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
     */
    public function getAllAccounts() {
        return $this->model->getAllAccounts();
    }

    /**
     * Get account by ID
     */
    public function getAccountById($accountId) {
        try {
            $accountDetails = $this->model->getAccountById($accountId);
            return $accountDetails;
        } catch (Exception $e) {
            error_log("Error in getAccountById: " . $e->getMessage());
            throw $e;
        }
    }

    // =====================================
    // DATA RETRIEVAL OPERATIONS
    // =====================================

    /**
     * Get account transactions
     */
    public function getAccountTransactions($accountId, $accountType = 'all') {
        return $this->model->getAccountTransactions($accountId, $accountType);
    }

    /**
     * Get account loans
     */
    public function getAccountLoans($accountId, $accountType = 'all') {
        return $this->model->getAccountLoans($accountId, $accountType);
    }

    /**
     * Get account savings
     */
    public function getAccountSavings($accountId, $accountType = 'all') {
        return $this->model->getAccountSavings($accountId, $accountType);
    }

    /**
     * Get savings data for charting
     */
    public function getSavingsData($accountId, $filter, $startDate = null, $endDate = null) {
        return $this->model->getSavingsData($accountId, $filter, $startDate, $endDate);
    }

    /**
     * Get transaction data for charting
     */
    public function getTransactionData($accountId, $filter) {
        return $this->model->getTransactionData($accountId, $filter);
    }

    // =====================================
    // SAVINGS AND WITHDRAWAL OPERATIONS
    // =====================================

    /**
     * Add savings to an account
     */
        public function addSavings($accountId, $amount, $paymentMode, $accountType, $receiptNumber, $servedBy) {
        try {
            // Start database transaction
            $this->model->beginTransaction();
            
            // Double-check for duplicate receipt (database level)
            $duplicateCheck = $this->model->checkDuplicateReceipt($receiptNumber, $accountId);
            if ($duplicateCheck['exists']) {
                throw new Exception("Receipt number already exists");
            }
            
            // Process the savings
            $result = $this->model->addSavings(
                $accountId,
                $amount,
                $paymentMode,
                $accountType,
                $receiptNumber,
                $servedBy
            );
            
            if ($result['status'] !== 'success') {
                throw new Exception($result['message']);
            }
            
            // Commit the transaction
            $this->model->commitTransaction();
            
            return $result;
            
        } catch (Exception $e) {
            // Rollback on any error
            $this->model->rollbackTransaction();
            
            error_log("Error in addSavings: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process withdrawal from account
     */
    public function withdraw($accountId, $amount, $paymentMode, $accountType, $receiptNumber, $withdrawalFee, $servedBy) {
        $result = $this->model->withdraw($accountId, $amount, $paymentMode, $accountType, $receiptNumber, $withdrawalFee, $servedBy);
        return $result;
    }

    // =====================================
    // INDIVIDUAL LOAN OPERATIONS
    // =====================================

    /**
     * Get loan details for repayment (SINGLE LOAN)
     */
    public function getLoanDetailsForRepayment($loanId) {
        try {
            $result = $this->model->getLoanDetailsForRepayment($loanId);
            
            if (!$result) {
                throw new Exception("Loan not found or error retrieving details");
            }
            
            return [
                'status' => 'success',
                'loan' => $result
            ];
            
        } catch (Exception $e) {
            error_log("Error in getLoanDetailsForRepayment: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get next due amount for specific loan
     */
    public function getLoanNextDueAmount($loanId) {
        $loanDetails = $this->model->getLoanDetailsForRepayment($loanId);
        if ($loanDetails) {
            $dueAmount = $this->model->getLoanNextDueAmount($loanId);
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

    /**
     * Process loan repayment for specific loan
     */
    public function repayLoan($accountId, $loanId, $repayAmount, $paymentMode, $servedBy, $receiptNumber) {
        error_log("Repay Loan Request Received: accountId=$accountId, loanId=$loanId, repayAmount=$repayAmount");
    
        try {
            if (empty($accountId) || empty($loanId) || empty($repayAmount) || empty($paymentMode) || empty($receiptNumber)) {
                throw new Exception("Missing required parameters");
            }
    
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
     * Get loan repayments for account
     */
    public function getLoanRepayments($accountId) {
        return $this->model->getLoanRepayments($accountId);
    }

    /**
     * Get repayment details by ID
     */
    public function getRepaymentDetails($repaymentId) {
        return $this->model->getRepaymentDetails($repaymentId);
    }

    /**
     * Delete loan repayment
     */
    public function deleteRepayment($repaymentId, $loanId, $deletedAmount) {
        return $this->model->deleteRepayment($repaymentId, $loanId, $deletedAmount);
    }

    /**
     * Get account repayments (for table reload)
     */
    public function getAccountRepayments($accountId) {
        $repayments = $this->model->getLoanRepayments($accountId);
        return [
            'status' => 'success',
            'repayments' => $repayments
        ];
    }

    // =====================================
    // LOAN SCHEDULE OPERATIONS
    // =====================================

    /**
     * Get loan schedule for specific loan
     */
    public function getLoanSchedule($loanId) {
        $schedule = $this->model->getLoanSchedule($loanId);
        if ($schedule) {
            return ['status' => 'success', 'schedule' => $schedule];
        }
        return ['status' => 'error', 'message' => 'Loan schedule not found'];
    }

    /**
     * Create loan schedule for new loan
     */
    public function createLoanSchedule($loanId) {
        $result = $this->model->createLoanSchedule($loanId);
        if ($result) {
            return ['status' => 'success', 'message' => 'Loan schedule created successfully'];
        }
        return ['status' => 'error', 'message' => 'Failed to create loan schedule'];
    }

    // =====================================
    // RECEIPT AND DETAILS OPERATIONS
    // =====================================

    /**
     * Get savings details for receipt
     */
    public function getSavingsDetails($savingsId) {
        return $this->model->getSavingsDetails($savingsId);
    }

    /**
     * Get available balance for withdrawal
     */
    public function getAvailableBalance($accountId, $accountType) {
        return $this->model->getAvailableBalance($accountId, $accountType);
    }

    /**
     * Get transaction receipt details
     */
    public function getTransactionReceipt($transactionId, $type) {
        return $this->model->getTransactionReceipt($transactionId, $type);
    }

    // =====================================
    // SHAREHOLDER NUMBER OPERATIONS
    // =====================================

    /**
     * Get next available shareholder number
     */
    public function getNextShareholderNumber() {
        return $this->model->getNextShareholderNumber();
    }

    /**
     * Check if shareholder number exists
     */
    public function checkShareholderNumberExists($shareholder_no) {
        return $this->model->checkShareholderNumberExists($shareholder_no);
    }

   
/**
     * Delete savings or withdrawal record and related transactions
     */
    public function deleteSavingsRecord($recordId, $recordType, $accountId) {
        try {
            // Validate input parameters
            if (empty($recordId) || empty($recordType) || empty($accountId)) {
                throw new Exception("Missing required parameters for deletion");
            }

            // Validate record type
            if (!in_array($recordType, ['Savings', 'Withdrawal'])) {
                throw new Exception("Invalid record type. Must be 'Savings' or 'Withdrawal'");
            }

            // Call model method to delete the record
            $result = $this->model->deleteSavingsRecord($recordId, $recordType, $accountId);
            
            if ($result['status'] === 'success') {
                return [
                    'status' => 'success',
                    'message' => $recordType . ' record deleted successfully',
                    'deletedRecord' => $result['deletedRecord'] ?? null
                ];
            } else {
                throw new Exception($result['message']);
            }

        } catch (Exception $e) {
            error_log("Error in deleteSavingsRecord: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle AJAX request for deleting savings record
     */
    private function handleDeleteSavingsRecord() {
        try {
            // Start session if not already started
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            // Debug: Log all session data
            error_log("Delete request - Full session data: " . print_r($_SESSION, true));
            error_log("Delete request - Session role: " . ($_SESSION['role'] ?? 'not set'));
            error_log("Delete request - Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
            error_log("Delete request - Session status: " . session_status());
            
            // Check if user is logged in first
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("Unauthorized: User not logged in. Session may have expired.");
            }
            
            // Check if user is admin or manager
            if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
                throw new Exception("Unauthorized: Only administrators and managers can delete records. Current role: " . ($_SESSION['role'] ?? 'not set'));
            }

            $recordId = $_POST['recordId'] ?? null;
            $recordType = $_POST['recordType'] ?? null;
            $accountId = $_POST['accountId'] ?? null;

            if (empty($recordId) || empty($recordType) || empty($accountId)) {
                throw new Exception('Missing required parameters');
            }

            // Validate that the record belongs to the specified account
            $recordDetails = $this->model->getSavingsRecordDetails($recordId);
            if (!$recordDetails || $recordDetails['account_id'] != $accountId) {
                throw new Exception('Record not found or does not belong to the specified account');
            }

            // Perform the deletion
            $result = $this->deleteSavingsRecord($recordId, $recordType, $accountId);
            
            header('Content-Type: application/json');
            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Error in handleDeleteSavingsRecord: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // =====================================
    // AJAX HANDLERS
    // =====================================

    /**
     * Handle all AJAX actions for account operations
     */
    public function handleAction() {
        error_log("AccountController handleAction called");
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
                error_log("UpdateAccount caseentered");
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
            case 'deleteSavingsRecord':
                $this->handleDeleteSavingsRecord();
                break;
            default:
                echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
                break;
        }
    }

    // =====================================
    // PRIVATE AJAX HANDLERS
    // =====================================

    private function handleCreateAccount() {
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

        $accountTypes = isset($_POST['account_type']) ? $_POST['account_type'] : [];
        if (is_string($_POST['account_type'])) {
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
            // Start session if not already started for user verification
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            // Security check - ensure user is logged in
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("Unauthorized: User not logged in");
            }
            
            // Log the request for debugging
            error_log("AddSavings request from user " . $_SESSION['user_id'] . ": " . json_encode($_POST));
            
            // Extract and validate required parameters
            $accountId = filter_var($_POST['accountId'] ?? null, FILTER_VALIDATE_INT);
            $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
            $paymentMode = trim($_POST['paymentMode'] ?? '');
            $accountType = trim($_POST['accountType'] ?? '');
            $receiptNumber = trim($_POST['receiptNumber'] ?? '');
            $servedBy = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Unknown';
            
            // Comprehensive validation
            $errors = [];
            
            if (!$accountId || $accountId <= 0) {
                $errors[] = "Valid Account ID is required";
            }
            
            if (!$amount || $amount <= 0) {
                $errors[] = "Valid amount is required";
            }
            
            if (empty($paymentMode)) {
                $errors[] = "Payment mode is required";
            }
            
            if (empty($accountType)) {
                $errors[] = "Account type is required";
            }
            
            if (empty($receiptNumber)) {
                $errors[] = "Receipt number is required";
            }
            
            // Validate receipt number format (adjust regex as needed)
            if (!empty($receiptNumber) && !preg_match('/^[A-Za-z0-9\-_]+$/', $receiptNumber)) {
                $errors[] = "Receipt number contains invalid characters";
            }
            
            // Check for reasonable amount limits (adjust as needed)
            if ($amount && ($amount > 10000000 || $amount < 0.01)) {
                $errors[] = "Amount must be between 0.01 and 10,000,000";
            }
            
            if (!empty($errors)) {
                throw new Exception("Validation failed: " . implode(", ", $errors));
            }
            
            // Check for duplicate receipt number BEFORE processing
            $duplicateCheck = $this->model->checkDuplicateReceipt($receiptNumber, $accountId);
            if ($duplicateCheck['exists']) {
                throw new Exception("Receipt number '{$receiptNumber}' already exists for this account");
            }
            
            // Verify account exists and is active
            $account = $this->model->getAccountById($accountId);
            if (!$account) {
                throw new Exception("Account not found with ID: {$accountId}");
            }
            
            // Process the savings transaction
            $result = $this->addSavings(
                $accountId,
                $amount,
                $paymentMode,
                $accountType,
                $receiptNumber,
                $servedBy
            );
            
            // Verify the result
            if (!$result || $result['status'] !== 'success') {
                throw new Exception($result['message'] ?? 'Failed to process savings transaction');
            }
            
            // Log successful transaction
            error_log("Savings transaction successful: Receipt {$receiptNumber}, Amount {$amount}, Account {$accountId}");
            
            // Return proper JSON response
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            http_response_code(200);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Savings added successfully',
                'data' => [
                    'savingsId' => $result['savingsId'] ?? null,
                    'receiptNumber' => $receiptNumber,
                    'amount' => $amount,
                    'accountType' => $accountType,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ], JSON_UNESCAPED_UNICODE);
            
            exit; // Critical: prevent any additional output
            
        } catch (Exception $e) {
            // Log the error with full context
            error_log("AddSavings error: " . $e->getMessage() . " | POST data: " . json_encode($_POST) . " | User: " . ($_SESSION['user_id'] ?? 'unknown'));
            
            // Return proper error response
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            http_response_code(400); // Bad Request
            
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
            
            exit; // Critical: prevent any additional output
        }
    }

    private function handleWithdraw() {
        try {
            $accountId = $_POST['accountId'] ?? null;
            $amount = $_POST['amount'] ?? null;
            $withdrawalFee = $_POST['withdrawalFee'] ?? 0;
            $accountType = $_POST['accountType'] ?? null;
            $receiptNumber = $_POST['receiptNumber'] ?? null;
            $paymentMode = $_POST['paymentMode'] ?? null;
            $servedBy = $_SESSION['user_name'] ?? 'System';
    
            if (!$accountId || !$amount || !$accountType || !$receiptNumber || !$paymentMode) {
                throw new Exception('Missing required parameters');
            }
    
            $amount = floatval($amount);
            $withdrawalFee = floatval($withdrawalFee);
    
            if ($amount <= 0) {
                throw new Exception('Invalid withdrawal amount');
            }
    
            $totalDeduction = $amount + $withdrawalFee;
    
            $result = $this->withdraw(
                $accountId,
                $totalDeduction,
                $paymentMode,
                $accountType,
                $receiptNumber,
                $withdrawalFee,
                $servedBy
            );
    
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Fixed handleRepayLoan method with duplicate prevention
     */
    private function handleRepayLoan() {
        try {
            // Start session if not already started
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            // Security check - ensure user is logged in
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("Unauthorized: User not logged in");
            }
            
            // Log the request for debugging
            error_log("RepayLoan request from user " . $_SESSION['user_id'] . ": " . json_encode($_POST));
            
            // Extract and validate parameters
            $accountId = filter_var($_POST['accountId'] ?? null, FILTER_VALIDATE_INT);
            $loanId = filter_var($_POST['loanId'] ?? null, FILTER_VALIDATE_INT);
            $repayAmount = filter_var($_POST['repayAmount'] ?? null, FILTER_VALIDATE_FLOAT);
            $paymentMode = trim($_POST['paymentMode'] ?? '');
            $receiptNumber = trim($_POST['receiptNumber'] ?? '');
            $servedBy = $_POST['served_by'] ?? $_SESSION['user_id'] ?? 'System';
            
            // Comprehensive validation
            $errors = [];
            
            if (!$accountId || $accountId <= 0) {
                $errors[] = "Valid Account ID is required";
            }
            
            if (!$loanId || $loanId <= 0) {
                $errors[] = "Valid Loan ID is required";
            }
            
            if (!$repayAmount || $repayAmount <= 0) {
                $errors[] = "Valid repayment amount is required";
            }
            
            if (empty($paymentMode)) {
                $errors[] = "Payment mode is required";
            }
            
            if (empty($receiptNumber)) {
                $errors[] = "Receipt number is required";
            }
            
            // Validate receipt number format
            if (!empty($receiptNumber) && !preg_match('/^[A-Za-z0-9\-_]+$/', $receiptNumber)) {
                $errors[] = "Receipt number contains invalid characters";
            }
            
            // Check for reasonable amount limits
            if ($repayAmount && ($repayAmount > 10000000 || $repayAmount < 0.01)) {
                $errors[] = "Repayment amount must be between 0.01 and 10,000,000";
            }
            
            if (!empty($errors)) {
                throw new Exception("Validation failed: " . implode(", ", $errors));
            }
            
            // Check for duplicate receipt number BEFORE processing
            $duplicateCheck = $this->model->checkDuplicateReceipt($receiptNumber, $accountId);
            if ($duplicateCheck['exists']) {
                throw new Exception("Receipt number '{$receiptNumber}' already exists for this account");
            }
            
            // Verify loan exists and belongs to account
            $loan = $this->model->getLoanDetailsForRepayment($loanId);
            if (!$loan || $loan['account_id'] != $accountId) {
                throw new Exception("Loan not found or does not belong to this account");
            }
            
            // Check if loan is disbursed
            if ($loan['status'] < 2) {
                throw new Exception("Loan must be disbursed before accepting repayments");
            }
            
            // Process the loan repayment
            $result = $this->repayLoan(
                $accountId,
                $loanId,
                $repayAmount,
                $paymentMode,
                $servedBy,
                $receiptNumber
            );
            
            // Verify the result
            if (!$result || $result['status'] !== 'success') {
                throw new Exception($result['message'] ?? 'Failed to process loan repayment');
            }
            
            // Log successful transaction
            error_log("Loan repayment successful: Receipt {$receiptNumber}, Amount {$repayAmount}, Loan {$loanId}");
            
            // Return proper JSON response
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            http_response_code(200);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Loan repayment processed successfully',
                'data' => [
                    'repaymentId' => $result['repaymentId'] ?? null,
                    'receiptNumber' => $receiptNumber,
                    'amount' => $repayAmount,
                    'loanId' => $loanId,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ], JSON_UNESCAPED_UNICODE);
            
            exit; // Critical: prevent any additional output
            
        } catch (Exception $e) {
            // Log the error with full context
            error_log("RepayLoan error: " . $e->getMessage() . " | POST data: " . json_encode($_POST) . " | User: " . ($_SESSION['user_id'] ?? 'unknown'));
            
            // Return proper error response
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            http_response_code(400);
            
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
            
            exit; // Critical: prevent any additional output
        }
    }

    private function handleGetLoanSchedule() {
        $loanId = $_GET['loanId'] ?? '';
        if (empty($loanId)) {
            echo json_encode(['status' => 'error', 'message' => 'Loan ID is required']);
            return;
        }
        $result = $this->getLoanSchedule($loanId);
        echo json_encode($result);
    }

    private function handleGetAccountRepayments() {
        $accountId = $_GET['accountId'] ?? '';
        if (empty($accountId)) {
            echo json_encode(['status' => 'error', 'message' => 'Account ID is required']);
            return;
        }
        $result = $this->getAccountRepayments($accountId);
        echo json_encode($result);
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

    private function handleGetAccountDetails() {
        $accountId = $_GET['accountId'] ?? '';
        $account = $this->getAccountById($accountId);
        $loans = $this->getAccountLoans($accountId);
        $savings = $this->getAccountSavings($accountId);
        $transactions = $this->getAccountTransactions($accountId);
        
        if ($account) {
            echo json_encode([
                'status' => 'success',
                'account' => $account,
                'loans' => $loans,
                'savings' => $savings,
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
            
            $result = $this->getLoanDetailsForRepayment($loanId);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Error in handleGetLoanDetails: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    private function handleGetAvailableBalance() {
        try {
            $accountId = $_GET['accountId'] ?? null;
            $accountType = $_GET['accountType'] ?? null;

            if (!$accountId || !$accountType) {
                throw new Exception("Missing required parameters");
            }

            $result = $this->getAvailableBalance($accountId, $accountType);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    private function handleGetTransactionReceipt() {
        try {
            $transactionId = $_GET['transactionId'] ?? null;
            $type = $_GET['type'] ?? null;

            if (!$transactionId || !$type) {
                throw new Exception("Missing required parameters");
            }

            $result = $this->getTransactionReceipt($transactionId, $type);
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
            
            $result = $this->deleteRepayment($repaymentId, $loanId, $amount);
            echo json_encode($result);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    private function handleGetNextShareholderNo() {
        try {
            $result = $this->getNextShareholderNumber();
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

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
            
            $result = $this->checkShareholderNumberExists($shareholder_no);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // =====================================
    // UTILITY METHODS
    // =====================================

    /**
     * Validate and sanitize input data
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