<?php
require_once('../config/config.php');

/**
 * AccountSummaryModel Class
 * 
 * Handles all account-level calculations and summaries
 * Used for dashboard statistics and overview data across all loans/savings for an account
 */
class AccountSummaryModel {
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

    /**
     * Get total savings for an account (filtered by account type)
     * Calculates: SUM(savings) - SUM(withdrawals)
     */
    public function getAccountTotalSavings($accountId, $accountType = 'all') {
        try {
            $query = "SELECT 
                COALESCE(SUM(
                    CASE 
                        WHEN type = 'Savings' THEN amount 
                        WHEN type = 'Withdrawal' THEN -amount 
                    END
                ), 0) as total_savings
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
            
            return floatval($result['total_savings'] ?? 0);
        } catch (Exception $e) {
            error_log("Error in getAccountTotalSavings: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total withdrawals for an account (including fees)
     */
    public function getAccountTotalWithdrawals($accountId, $accountType = 'all') {
        try {
            $query = "SELECT COALESCE(SUM(amount + COALESCE(withdrawal_fee, 0)), 0) as total_withdrawals 
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
            
            return floatval($result['total_withdrawals'] ?? 0);
        } catch (Exception $e) {
            error_log("Error in getAccountTotalWithdrawals: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total outstanding principal across ALL loans for account
     * This calculates the actual remaining principal amount (without interest)
     * based on loan schedule and payments made
     */
    public function getAccountTotalOutstandingLoans($accountId, $accountType = 'all') {
        try {
            // Get all active loans for this account
            $query = "
                SELECT 
                    l.loan_id,
                    l.amount as original_amount,
                    l.loan_term,
                    l.date_applied,
                    l.meeting_date,
                    COALESCE(lp.interest_rate, 0) as interest_rate
                FROM loan l
                LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
                WHERE l.account_id = ?
                AND l.status IN (1, 2)"; // Only active/released loans
            
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
            
            $loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $totalOutstanding = 0;
            
            foreach ($loans as $loan) {
                $loanOutstanding = $this->calculateLoanOutstandingPrincipal($loan);
                $totalOutstanding += $loanOutstanding;
            }
            
            return max(0, $totalOutstanding);
            
        } catch (Exception $e) {
            error_log("Error in getAccountTotalOutstandingLoans: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate outstanding principal for a specific loan
     * This method calculates the remaining principal based on:
     * 1. Original loan amount
     * 2. Monthly principal payments (original_amount / loan_term)
     * 3. Actual payments made from loan_repayments table
     */
    private function calculateLoanOutstandingPrincipal($loan) {
        try {
            $loanId = $loan['loan_id'];
            $originalAmount = floatval($loan['original_amount']);
            $loanTerm = intval($loan['loan_term']);
            
            if ($loanTerm <= 0) {
                return $originalAmount; // If no term specified, full amount is outstanding
            }
            
            // Calculate monthly principal payment (without interest)
            $monthlyPrincipal = $originalAmount / $loanTerm;
            
            // Get total amount repaid for this loan
            $repaymentQuery = "
                SELECT COALESCE(SUM(amount_repaid), 0) as total_repaid
                FROM loan_repayments 
                WHERE loan_id = ?";
            
            $stmt = $this->conn->prepare($repaymentQuery);
            if (!$stmt) {
                throw new Exception("Error preparing repayment query: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $loanId);
            if (!$stmt->execute()) {
                throw new Exception("Error executing repayment query: " . $stmt->error);
            }
            
            $result = $stmt->get_result()->fetch_assoc();
            $totalRepaid = floatval($result['total_repaid'] ?? 0);
            
            // Calculate how much principal has been paid
            // We need to determine what portion of payments went to principal vs interest
            $principalPaid = $this->calculatePrincipalPaidFromRepayments($loan, $totalRepaid);
            
            // Outstanding principal = Original principal - Principal paid
            $outstandingPrincipal = $originalAmount - $principalPaid;
            
            return max(0, $outstandingPrincipal);
            
        } catch (Exception $e) {
            error_log("Error calculating loan outstanding principal: " . $e->getMessage());
            return floatval($loan['original_amount'] ?? 0);
        }
    }

    /**
     * Calculate how much principal has been paid from total repayments
     * This follows the amortization schedule where early payments go more to interest
     */
    private function calculatePrincipalPaidFromRepayments($loan, $totalRepaid) {
        try {
            $originalAmount = floatval($loan['original_amount']);
            $loanTerm = intval($loan['loan_term']);
            $interestRate = floatval($loan['interest_rate']) / 100; // Convert percentage to decimal
            
            if ($loanTerm <= 0 || $totalRepaid <= 0) {
                return 0;
            }
            
            // Calculate monthly principal payment
            $monthlyPrincipal = $originalAmount / $loanTerm;
            
            // Generate the payment schedule to determine principal vs interest allocation
            $remainingPrincipal = $originalAmount;
            $principalPaid = 0;
            $remainingRepayment = $totalRepaid;
            
            for ($month = 1; $month <= $loanTerm && $remainingRepayment > 0; $month++) {
                // Calculate interest for this month on remaining principal
                $monthlyInterest = $remainingPrincipal * $interestRate;
                
                // Total payment due this month
                $monthlyPayment = $monthlyPrincipal + $monthlyInterest;
                
                if ($remainingRepayment >= $monthlyPayment) {
                    // Full payment made for this month
                    $principalPaid += $monthlyPrincipal;
                    $remainingPrincipal -= $monthlyPrincipal;
                    $remainingRepayment -= $monthlyPayment;
                } else {
                    // Partial payment - allocate to interest first, then principal
                    if ($remainingRepayment > $monthlyInterest) {
                        $principalPortionPaid = $remainingRepayment - $monthlyInterest;
                        $principalPaid += $principalPortionPaid;
                    }
                    // No more repayments to process
                    break;
                }
            }
            
            return $principalPaid;
            
        } catch (Exception $e) {
            error_log("Error calculating principal paid: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Alternative method: Calculate outstanding principal using loan_schedule table
     * This is more accurate if the loan_schedule is properly maintained
     */
    private function calculateOutstandingFromSchedule($loanId) {
        try {
            // Get the sum of unpaid principal from loan schedule
            $query = "
                SELECT COALESCE(SUM(principal), 0) as outstanding_principal
                FROM loan_schedule 
                WHERE loan_id = ? 
                AND status IN ('unpaid', 'partial')";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing schedule query: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $loanId);
            if (!$stmt->execute()) {
                throw new Exception("Error executing schedule query: " . $stmt->error);
            }
            
            $result = $stmt->get_result()->fetch_assoc();
            return floatval($result['outstanding_principal'] ?? 0);
            
        } catch (Exception $e) {
            error_log("Error calculating outstanding from schedule: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get count of active loans (status = 2, which means disbursed/active)
     */
    public function getAccountActiveLoansCount($accountId, $accountType = 'all') {
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
            error_log("Error in getAccountActiveLoansCount: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total group savings for a specific client from group transactions
     */
    public function getAccountTotalGroupSavings($accountId, $accountType = 'all') {
        try {
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
            
            return max(0, $groupSavings);
            
        } catch (Exception $e) {
            error_log("Error in getAccountTotalGroupSavings: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total original loan amounts (for reference/comparison)
     */
    public function getAccountTotalLoanAmount($accountId, $accountType = 'all') {
        try {
            $query = "
                SELECT COALESCE(SUM(l.amount), 0) as total_loan_amount
                FROM loan l
                WHERE l.account_id = ?
                AND l.status >= 0";
            
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
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            return floatval($result['total_loan_amount'] ?? 0);
        } catch (Exception $e) {
            error_log("Error in getAccountTotalLoanAmount: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get comprehensive account summary with all totals
     */
    public function getAccountSummary($accountId, $accountType = 'all') {
        try {
            return [
                'total_savings' => $this->getAccountTotalSavings($accountId, $accountType),
                'total_withdrawals' => $this->getAccountTotalWithdrawals($accountId, $accountType),
                'outstanding_loans' => $this->getAccountTotalOutstandingLoans($accountId, $accountType),
                'active_loans_count' => $this->getAccountActiveLoansCount($accountId, $accountType),
                'total_group_savings' => $this->getAccountTotalGroupSavings($accountId, $accountType),
                'total_loan_amount' => $this->getAccountTotalLoanAmount($accountId, $accountType)
            ];
        } catch (Exception $e) {
            error_log("Error in getAccountSummary: " . $e->getMessage());
            return [
                'total_savings' => 0,
                'total_withdrawals' => 0,
                'outstanding_loans' => 0,
                'active_loans_count' => 0,
                'total_group_savings' => 0,
                'total_loan_amount' => 0
            ];
        }
    }

    /**
     * Get net balance (savings - outstanding loans)
     */
    public function getAccountNetBalance($accountId, $accountType = 'all') {
        try {
            $totalSavings = $this->getAccountTotalSavings($accountId, $accountType);
            $outstandingLoans = $this->getAccountTotalOutstandingLoans($accountId, $accountType);
            
            return $totalSavings - $outstandingLoans;
        } catch (Exception $e) {
            error_log("Error in getAccountNetBalance: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get fully paid loans summary
     */
    public function getAccountFullyPaidLoansSummary($accountId, $accountType = 'all') {
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
            
            return [
                'total_loans' => intval($result['total_loans'] ?? 0),
                'total_principal' => floatval($result['total_principal'] ?? 0),
                'total_amount_paid' => floatval($result['total_amount_paid'] ?? 0),
                'total_interest_paid' => max(0, floatval($result['total_interest_paid'] ?? 0))
            ];
            
        } catch (Exception $e) {
            error_log("Error in getAccountFullyPaidLoansSummary: " . $e->getMessage());
            return [
                'total_loans' => 0,
                'total_principal' => 0,
                'total_amount_paid' => 0,
                'total_interest_paid' => 0
            ];
        }
    }

    /**
     * Get fully paid loans list
     */
    public function getAccountFullyPaidLoans($accountId, $accountType = 'all') {
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
                    COALESCE((
                        SELECT SUM(amount_repaid) 
                        FROM loan_repayments 
                        WHERE loan_id = l.loan_id
                    ), 0) as total_paid,
                    COALESCE((
                        SELECT SUM(amount_repaid) 
                        FROM loan_repayments 
                        WHERE loan_id = l.loan_id
                    ), 0) - l.amount as interest_paid,
                    COALESCE((
                        SELECT MAX(date_paid) 
                        FROM loan_repayments 
                        WHERE loan_id = l.loan_id
                    ), l.date_applied) as date_completed,
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
            return $result->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error in getAccountFullyPaidLoans: " . $e->getMessage());
            return [];
        }
    }

/**
 * Get fully paid loan schedule with complete payment history
 */
public function getFullyPaidLoanSchedule($loanId) {
    try {
        // First, get loan details with proper joins - Using actual loan table columns
        $loanQuery = "
            SELECT 
                l.loan_id,
                l.ref_no,
                l.amount,
                l.loan_term,
                l.date_applied,
                l.date_approved,
                l.date_released,
                l.next_payment_date,
                l.status,
                l.created_at,
                l.updated_at,
                COALESCE(lp.interest_rate, 0) as interest_rate,
                CONCAT(ca.first_name, ' ', COALESCE(ca.last_name, '')) as client_name,
                COALESCE((
                    SELECT MAX(date_paid) 
                    FROM loan_repayments 
                    WHERE loan_id = l.loan_id
                ), l.date_applied) as date_completed
            FROM loan l
            LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
            LEFT JOIN client_accounts ca ON l.account_id = ca.account_id
            WHERE l.loan_id = ? AND l.status = 3"; // Status 3 = Completed
        
        $loanStmt = $this->conn->prepare($loanQuery);
        if (!$loanStmt) {
            throw new Exception("Error preparing loan query: " . $this->conn->error);
        }
        
        $loanStmt->bind_param("i", $loanId);
        if (!$loanStmt->execute()) {
            throw new Exception("Error executing loan query: " . $loanStmt->error);
        }
        
        $loanDetails = $loanStmt->get_result()->fetch_assoc();
        
        if (!$loanDetails) {
            throw new Exception("Loan not found or not fully paid");
        }
        
        // Try to get schedule from loan_schedule table first (preferred method)
        $scheduleQuery = "
            SELECT 
                ls.id,
                ls.due_date,
                COALESCE(ls.principal, 0) as principal,
                COALESCE(ls.interest, 0) as interest,
                COALESCE(ls.amount, 0) as amount,
                COALESCE(ls.balance, 0) as balance,
                COALESCE(ls.repaid_amount, 0) as repaid_amount,
                COALESCE(ls.default_amount, 0) as default_amount,
                ls.status as schedule_status,
                ls.paid_date
            FROM loan_schedule ls
            WHERE ls.loan_id = ?
            ORDER BY ls.due_date";
        
        $scheduleStmt = $this->conn->prepare($scheduleQuery);
        if (!$scheduleStmt) {
            throw new Exception("Error preparing schedule query: " . $this->conn->error);
        }
        
        $scheduleStmt->bind_param("i", $loanId);
        if (!$scheduleStmt->execute()) {
            throw new Exception("Error executing schedule query: " . $scheduleStmt->error);
        }
        
        $scheduleResult = $scheduleStmt->get_result();
        $schedule = $scheduleResult->fetch_all(MYSQLI_ASSOC);
        
        // If no schedule found in loan_schedule table, generate it using loan_repayments
        if (empty($schedule)) {
            $schedule = $this->generateFullyPaidSchedule($loanId, $loanDetails);
        }
        
        // Format the schedule data for display
        $formattedSchedule = [];
        foreach ($schedule as $item) {
            $formattedSchedule[] = [
                'due_date' => $item['due_date'],
                'principal' => number_format(floatval($item['principal']), 2),
                'interest' => number_format(floatval($item['interest']), 2),
                'amount' => number_format(floatval($item['amount']), 2),
                'balance' => number_format(floatval($item['balance'] ?? 0), 2),
                'repaid_amount' => number_format(floatval($item['repaid_amount']), 2),
                'default_amount' => number_format(floatval($item['default_amount'] ?? 0), 2),
                'paid_date' => $item['paid_date'] ?? $item['due_date'],
                'status' => 'Paid'
            ];
        }
        
        return [
            'status' => 'success',
            'loan_details' => $loanDetails,
            'schedule' => $formattedSchedule
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
 * Generate fully paid loan schedule from loan_repayments
 */
private function generateFullyPaidSchedule($loanId, $loanDetails) {
    try {
        // Get all repayments for this loan from loan_repayments table
        $repaymentsQuery = "
            SELECT 
                date_paid,
                amount_repaid,
                date_paid as paid_date
            FROM loan_repayments
            WHERE loan_id = ?
            ORDER BY date_paid";
        
        $stmt = $this->conn->prepare($repaymentsQuery);
        if (!$stmt) {
            throw new Exception("Error preparing repayments query: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $loanId);
        if (!$stmt->execute()) {
            throw new Exception("Error executing repayments query: " . $stmt->error);
        }
        
        $repayments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($repayments)) {
            return [];
        }
        
        // Initialize loan parameters
        $totalAmount = floatval($loanDetails['amount']);
        $term = intval($loanDetails['loan_term']);
        $interestRate = floatval($loanDetails['interest_rate']);
        
        if ($term <= 0) {
            $term = count($repayments); // Use number of payments if term not set
        }
        
        $monthlyPrincipal = round($totalAmount / $term, 2);
        
        // Start from date_released if available, otherwise date_applied
        $startDate = $loanDetails['date_released'] ?? $loanDetails['date_applied'] ?? $loanDetails['created_at'];
        $paymentDate = new DateTime($startDate);
        $paymentDate->modify('+1 month');
        
        // Generate schedule
        $remainingPrincipal = $totalAmount;
        $remainingBalance = $totalAmount + ($totalAmount * ($interestRate / 100) * $term);
        $schedule = [];
        
        for ($i = 0; $i < count($repayments); $i++) {
            // Calculate interest on remaining principal
            $interest = round($remainingPrincipal * ($interestRate / 100), 2);
            $dueAmount = $monthlyPrincipal + $interest;
            $dueDate = $paymentDate->format('Y-m-d');
            
            // Get the actual repayment for this installment
            $repayment = $repayments[$i];
            $repaidAmount = floatval($repayment['amount_repaid']);
            $paidDate = $repayment['paid_date'];
            
            $schedule[] = [
                'due_date' => $dueDate,
                'principal' => $monthlyPrincipal,
                'interest' => $interest,
                'amount' => $dueAmount,
                'balance' => $remainingBalance,
                'repaid_amount' => $repaidAmount,
                'default_amount' => 0, // No defaults for fully paid loans
                'status' => 'paid',
                'paid_date' => $paidDate
            ];
            
            // Update balances for next iteration
            $remainingPrincipal -= $monthlyPrincipal;
            $remainingBalance -= $dueAmount;
            
            // Move to next month
            $paymentDate->modify('+1 month');
            
            // Safety check to prevent infinite loop
            if ($remainingPrincipal <= 0) {
                break;
            }
        }
        
        return $schedule;
        
    } catch (Exception $e) {
        error_log("Error generating fully paid schedule: " . $e->getMessage());
        return [];
    }
}

    public function getLastError() {
        return $this->lastError;
    }

    public function __destruct() {
        // Don't close or pool the connection in destructor
        // Let the db_connect class handle connection lifecycle
        $this->conn = null;
    }
}
?>