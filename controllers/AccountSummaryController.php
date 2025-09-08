<?php
require_once('../models/AccountSummaryModel.php');

/**
 * AccountSummaryController Class
 * 
 * Handles all account-level summary operations and dashboard statistics
 * This controller is specifically for account overview data, not individual loan operations
 */
class AccountSummaryController {
    private $model;

    public function __construct() {
        $this->model = new AccountSummaryModel();
    }

    public function getModel() {
        return $this->model;
    }

    /**
     * Get comprehensive account summary for dashboard
     */
    public function getAccountSummary($accountId, $accountType = 'all') {
        try {
            if (!$accountId) {
                throw new Exception("Account ID is required");
            }

            $summary = $this->model->getAccountSummary($accountId, $accountType);
            
            return [
                'status' => 'success',
                'data' => $summary
            ];
        } catch (Exception $e) {
            error_log("Error in getAccountSummary: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get account total savings
     */
    public function getAccountTotalSavings($accountId, $accountType = 'all') {
        try {
            return $this->model->getAccountTotalSavings($accountId, $accountType);
        } catch (Exception $e) {
            error_log("Error in getAccountTotalSavings: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get account total withdrawals
     */
    public function getAccountTotalWithdrawals($accountId, $accountType = 'all') {
        try {
            return $this->model->getAccountTotalWithdrawals($accountId, $accountType);
        } catch (Exception $e) {
            error_log("Error in getAccountTotalWithdrawals: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get account total outstanding loans
     */
    public function getAccountTotalOutstandingLoans($accountId, $accountType = 'all') {
        try {
            return $this->model->getAccountTotalOutstandingLoans($accountId, $accountType);
        } catch (Exception $e) {
            error_log("Error in getAccountTotalOutstandingLoans: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get account active loans count
     */
    public function getAccountActiveLoansCount($accountId, $accountType = 'all') {
        try {
            return $this->model->getAccountActiveLoansCount($accountId, $accountType);
        } catch (Exception $e) {
            error_log("Error in getAccountActiveLoansCount: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get account total group savings
     */
    public function getAccountTotalGroupSavings($accountId, $accountType = 'all') {
        try {
            return $this->model->getAccountTotalGroupSavings($accountId, $accountType);
        } catch (Exception $e) {
            error_log("Error in getAccountTotalGroupSavings: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get account net balance
     */
    public function getAccountNetBalance($accountId, $accountType = 'all') {
        try {
            return $this->model->getAccountNetBalance($accountId, $accountType);
        } catch (Exception $e) {
            error_log("Error in getAccountNetBalance: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get fully paid loans for an account
     */
    public function getAccountFullyPaidLoans($accountId, $accountType = 'all') {
        try {
            if (!$accountId) {
                throw new Exception("Account ID is required");
            }

            $loans = $this->model->getAccountFullyPaidLoans($accountId, $accountType);
            $summary = $this->model->getAccountFullyPaidLoansSummary($accountId, $accountType);
            
            return [
                'status' => 'success',
                'loans' => $loans,
                'summary' => $summary
            ];
        } catch (Exception $e) {
            error_log("Error in getAccountFullyPaidLoans: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle AJAX request for account summary
     */
    private function handleGetAccountSummary() {
        try {
            $accountId = $_GET['accountId'] ?? null;
            $accountType = $_GET['accountType'] ?? 'all';

            if (!$accountId) {
                throw new Exception("Account ID is required");
            }

            $summary = $this->getAccountSummary($accountId, $accountType);
            
            if ($summary['status'] === 'success') {
                echo json_encode([
                    'status' => 'success',
                    'totalSavings' => $summary['data']['total_savings'],
                    'totalWithdrawals' => $summary['data']['total_withdrawals'],
                    'outstandingLoans' => $summary['data']['outstanding_loans'],
                    'activeLoansCount' => $summary['data']['active_loans_count'],
                    'totalGroupSavings' => $summary['data']['total_group_savings'],
                    'totalLoanAmount' => $summary['data']['total_loan_amount']
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
     * Handle AJAX request for fully paid loans
     */
    private function handleGetAccountFullyPaidLoans() {
        try {
            $accountId = $_GET['accountId'] ?? null;
            $accountType = $_GET['accountType'] ?? 'all';

            if (!$accountId) {
                throw new Exception("Account ID is required");
            }

            $result = $this->getAccountFullyPaidLoans($accountId, $accountType);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Error in handleGetAccountFullyPaidLoans: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

 /**
 * Get fully paid loan schedule details
 */
public function getFullyPaidLoanSchedule($loanId) {
    try {
        if (!$loanId) {
            throw new Exception("Loan ID is required");
        }

        // Validate loan ID is numeric
        if (!is_numeric($loanId)) {
            throw new Exception("Invalid loan ID format");
        }

        $loanId = intval($loanId);
        
        if ($loanId <= 0) {
            throw new Exception("Invalid loan ID");
        }

        $result = $this->model->getFullyPaidLoanSchedule($loanId);
        
        // Add additional validation
        if ($result['status'] === 'success') {
            // Ensure we have required data
            if (empty($result['loan_details'])) {
                throw new Exception("Loan details not found");
            }
            
            if (empty($result['schedule'])) {
                error_log("Warning: No schedule data found for loan ID: " . $loanId);
                // Don't throw error, just log warning
            }
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error in getFullyPaidLoanSchedule: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Handle AJAX request for fully paid loan schedule
 */
private function handleGetFullyPaidLoanSchedule() {
    try {
        $loanId = $_GET['loan_id'] ?? null;

        if (!$loanId) {
            throw new Exception("Loan ID is required");
        }

        // Additional logging for debugging
        error_log("Fetching fully paid loan schedule for loan ID: " . $loanId);
        
        $result = $this->getFullyPaidLoanSchedule($loanId);
        
        // Log the result for debugging
        if ($result['status'] === 'success') {
            error_log("Successfully retrieved schedule for loan ID: " . $loanId . 
                     " with " . count($result['schedule']) . " payment entries");
        } else {
            error_log("Failed to retrieve schedule for loan ID: " . $loanId . 
                     " Error: " . $result['message']);
        }
        
        header('Content-Type: application/json');
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Error in handleGetFullyPaidLoanSchedule: " . $e->getMessage());
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

    /**
     * Handle all AJAX actions for account summary operations
     */
    public function handleAction() {
        error_log("AccountSummaryController handleAction called");
        error_log("GET data: " . print_r($_GET, true));

        $action = $_GET['action'] ?? 'unknown';
        error_log("Received action: " . $action);

        switch ($action) {
            case 'getAccountSummary':
                error_log("getAccountSummary case entered");
                $this->handleGetAccountSummary();
                break;
            case 'getAccountFullyPaidLoans':
                error_log("getAccountFullyPaidLoans case entered");
                $this->handleGetAccountFullyPaidLoans();
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

    /**
     * Validate and sanitize input data
     */
    private function validateInput($data, $type) {
        switch ($type) {
            case 'int':
                return filter_var($data, FILTER_VALIDATE_INT);
            case 'float':
                return filter_var($data, FILTER_VALIDATE_FLOAT);
            case 'string':
                return filter_var($data, FILTER_SANITIZE_STRING);
            default:
                return false;
        }
    }

    /**
     * Log errors for debugging
     */
    private function logError($message) {
        error_log("AccountSummaryController Error: " . $message);
    }
}

// If the script is accessed directly, instantiate and handle the action
if (php_sapi_name() !== 'cli') {
    $controller = new AccountSummaryController();
    $controller->handleAction();
}
?>