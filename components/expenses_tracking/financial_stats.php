<?php
// components/expenses_tracking/financial_stats.php

class FinancialStats {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function calculateStats($start_date, $end_date, $category = 'all', $transaction_type = 'all') {
        $income_data = $this->getIncomeData($start_date, $end_date, $category, $transaction_type);
        $expenditure_data = $this->getExpenditureData($start_date, $end_date, $category, $transaction_type);
        $withdrawal_fees_data = $this->getWithdrawalFees($start_date, $end_date);
        $profit_data = $this->calculateTrueProfit($start_date, $end_date, $category, $transaction_type);
        
        $total_income = array_sum(array_column($income_data, 'amount'));
        $total_expenditure = array_sum(array_column($expenditure_data, 'total_amount'));
        $withdrawal_fees = $withdrawal_fees_data['total'];
        $net_position = $total_income - $total_expenditure;
        $total_profit = $profit_data['total_profit'];
        
        return [
            'income_data' => $income_data,
            'expenditure_data' => $expenditure_data,
            'withdrawal_fees_data' => $withdrawal_fees_data,
            'profit_data' => $profit_data,
            'total_income' => $total_income,
            'total_expenditure' => $total_expenditure,
            'withdrawal_fees' => $withdrawal_fees,
            'net_position' => $net_position,
            'total_profit' => $total_profit
        ];
    }
    
    private function getIncomeData($start_date, $end_date, $category = 'all', $transaction_type = 'all') {
        // Skip if transaction type is expenditure only
        if ($transaction_type === 'expenditure_only') {
            return [];
        }
        
        try {
            $query_parts = [];
            $params = [];
            $types = '';
            
            // Individual Savings
            if ($category === 'all' || $category === 'individual_savings') {
                $query_parts[] = "
                    SELECT 
                        'Individual Savings' as source,
                        SUM(amount) as amount,
                        'Financial Operations' as category
                    FROM savings 
                    WHERE DATE(date) BETWEEN ? AND ?
                    AND type = 'Savings'
                ";
                $params = array_merge($params, [$start_date, $end_date]);
                $types .= 'ss';
            }
            
            // Loan Repayments
            if ($category === 'all' || $category === 'loan_repayments') {
                $query_parts[] = "
                    SELECT 
                        'Loan Repayments' as source,
                        SUM(amount_repaid) as amount,
                        'Financial Operations' as category
                    FROM loan_repayments 
                    WHERE DATE(date_paid) BETWEEN ? AND ?
                ";
                $params = array_merge($params, [$start_date, $end_date]);
                $types .= 'ss';
            }
            
            // Group Savings
            if ($category === 'all' || $category === 'group_savings') {
                $query_parts[] = "
                    SELECT 
                        'Group Savings' as source,
                        SUM(amount) as amount,
                        'Financial Operations' as category
                    FROM group_savings 
                    WHERE DATE(date_saved) BETWEEN ? AND ?
                ";
                $params = array_merge($params, [$start_date, $end_date]);
                $types .= 'ss';
            }
            
            // Business Group Savings
            if ($category === 'all' || $category === 'business_group_savings') {
                $query_parts[] = "
                    SELECT 
                        'Business Group Savings' as source,
                        SUM(amount) as amount,
                        'Financial Operations' as category
                    FROM business_group_transactions 
                    WHERE DATE(date) BETWEEN ? AND ?
                    AND type = 'Savings'
                ";
                $params = array_merge($params, [$start_date, $end_date]);
                $types .= 'ss';
            }
            
            // Withdrawal Fees
            if ($category === 'all' || $category === 'withdrawal_fees') {
                $withdrawal_fees = $this->getWithdrawalFees($start_date, $end_date);
                if ($withdrawal_fees['total'] > 0) {
                    $query_parts[] = "
                        SELECT 
                            'Withdrawal Fees' as source,
                            " . $withdrawal_fees['total'] . " as amount,
                            'Financial Operations' as category
                    ";
                }
            }
            
            // Income/Receipts
            if ($category === 'all' || $category === 'income_receipts') {
                $query_parts[] = "
                    SELECT 
                        CONCAT('Income: ', e.category) as source,
                        SUM(e.amount) as amount,
                        ec.category as category
                    FROM expenses e
                    JOIN expenses_categories ec ON e.category = ec.name
                    WHERE DATE(e.date) BETWEEN ? AND ?
                    AND e.status = 'received'
                    GROUP BY e.category, ec.category
                ";
                $params = array_merge($params, [$start_date, $end_date]);
                $types .= 'ss';
            }
            
            // If no query parts, return empty
            if (empty($query_parts)) {
                return [];
            }
            
            // Combine all parts with UNION ALL
            $final_query = implode(' UNION ALL ', $query_parts);
            
            $stmt = $this->db->conn->prepare($final_query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return array_values($result);
        } catch (Exception $e) {
            error_log("Error in getIncomeData: " . $e->getMessage());
            return [];
        }
    }
    
    private function getExpenditureData($start_date, $end_date, $category = 'all', $transaction_type = 'all') {
        // Skip if transaction type is income only
        if ($transaction_type === 'income_only') {
            return [];
        }
        
        try {
            $query_parts = [];
            $params = [];
            $types = '';
            
            // Business Expenses
            if ($category === 'all' || $category === 'business_expenses') {
                $query_parts[] = "
                    SELECT 
                        'Business Expenses' as main_category,
                        'Business Expenses' as name,
                        SUM(ABS(amount)) as total_amount,
                        COUNT(*) as transaction_count,
                        GROUP_CONCAT(description) as descriptions,
                        MIN(date) as start_date,
                        MAX(date) as end_date
                    FROM expenses 
                    WHERE DATE(date) BETWEEN ? AND ?
                    AND status = 'completed'
                ";
                $params = array_merge($params, [$start_date, $end_date]);
                $types .= 'ss';
            }
            
            // Loan Disbursements
            if ($category === 'all' || $category === 'loan_disbursements') {
                $query_parts[] = "
                    SELECT 
                        'Loan Disbursements' as main_category,
                        'Loan Disbursements' as name,
                        SUM(amount) as total_amount,
                        COUNT(*) as transaction_count,
                        GROUP_CONCAT(description) as descriptions,
                        MIN(date) as start_date,
                        MAX(date) as end_date
                    FROM transactions 
                    WHERE DATE(date) BETWEEN ? AND ?
                    AND type = 'Loan Disbursement'
                ";
                $params = array_merge($params, [$start_date, $end_date]);
                $types .= 'ss';
            }
            
            // Individual Withdrawals
            if ($category === 'all' || $category === 'individual_withdrawals') {
                $query_parts[] = "
                    SELECT 
                        'Individual Withdrawals' as main_category,
                        'Individual Withdrawals' as name,
                        SUM(amount) as total_amount,
                        COUNT(*) as transaction_count,
                        'Client withdrawals' as descriptions,
                        MIN(date) as start_date,
                        MAX(date) as end_date
                    FROM savings 
                    WHERE DATE(date) BETWEEN ? AND ?
                    AND type = 'Withdrawal'
                ";
                $params = array_merge($params, [$start_date, $end_date]);
                $types .= 'ss';
            }
            
            // Group Withdrawals
            if ($category === 'all' || $category === 'group_withdrawals') {
                $query_parts[] = "
                    SELECT 
                        'Group Withdrawals' as main_category,
                        'Group Withdrawals' as name,
                        SUM(amount) as total_amount,
                        COUNT(*) as transaction_count,
                        'Group member withdrawals' as descriptions,
                        MIN(date_withdrawn) as start_date,
                        MAX(date_withdrawn) as end_date
                    FROM group_withdrawals 
                    WHERE DATE(date_withdrawn) BETWEEN ? AND ?
                ";
                $params = array_merge($params, [$start_date, $end_date]);
                $types .= 'ss';
            }
            
            // Business Group Withdrawals
            if ($category === 'all' || $category === 'business_group_withdrawals') {
                $query_parts[] = "
                    SELECT 
                        'Business Group Withdrawals' as main_category,
                        'Business Group Withdrawals' as name,
                        SUM(amount) as total_amount,
                        COUNT(*) as transaction_count,
                        GROUP_CONCAT(description) as descriptions,
                        MIN(date) as start_date,
                        MAX(date) as end_date
                    FROM business_group_transactions 
                    WHERE DATE(date) BETWEEN ? AND ?
                    AND type = 'Withdrawal'
                ";
                $params = array_merge($params, [$start_date, $end_date]);
                $types .= 'ss';
            }
            
            // Float Management
            if ($category === 'all' || $category === 'float_management') {
                $query_parts[] = "
                    SELECT 
                        'Float Management' as main_category,
                        'Float Management' as name,
                        SUM(amount) as total_amount,
                        COUNT(*) as transaction_count,
                        'Float operations' as descriptions,
                        MIN(date_created) as start_date,
                        MAX(date_created) as end_date
                    FROM float_management 
                    WHERE DATE(date_created) BETWEEN ? AND ?
                    AND type = 'offload'
                ";
                $params = array_merge($params, [$start_date, $end_date]);
                $types .= 'ss';
            }
            
            // If no query parts, return empty
            if (empty($query_parts)) {
                return [];
            }
            
            // Combine all parts with UNION ALL
            $final_query = implode(' UNION ALL ', $query_parts) . " ORDER BY total_amount DESC";
            
            $stmt = $this->db->conn->prepare($final_query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getExpenditureData: " . $e->getMessage());
            return [];
        }
    }
    
    private function getWithdrawalFees($start_date, $end_date) {
        try {
            $query = "
                SELECT 
                    source,
                    COALESCE(SUM(fee), 0) as fees
                FROM (
                    SELECT 
                        'Individual Withdrawals' as source,
                        withdrawal_fee as fee
                    FROM savings
                    WHERE DATE(date) BETWEEN ? AND ?
                    AND type = 'Withdrawal'
                    AND withdrawal_fee > 0
                    
                    UNION ALL
                    
                    SELECT 
                        'Business Group Withdrawals' as source,
                        amount as fee
                    FROM business_group_transactions
                    WHERE DATE(date) BETWEEN ? AND ?
                    AND type = 'Withdrawal Fee'
                    
                    UNION ALL
                    
                    SELECT 
                        'Loan Disbursement Fees' as source,
                        withdrawal_fee as fee
                    FROM payment
                    WHERE DATE(date_created) BETWEEN ? AND ?
                    AND withdrawal_fee > 0
                ) all_fees
                GROUP BY source
            ";
            
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("ssssss", 
                $start_date, $end_date,
                $start_date, $end_date,
                $start_date, $end_date
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing query: " . $stmt->error);
            }
            
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $total = 0;
            foreach ($result as $row) {
                $total += $row['fees'];
            }
            
            return [
                'total' => $total,
                'breakdown' => $result
            ];
            
        } catch (Exception $e) {
            error_log("Error in getWithdrawalFees: " . $e->getMessage());
            return ['total' => 0, 'breakdown' => []];
        }
    }
    
    private function calculateTrueProfit($start_date, $end_date, $category = 'all', $transaction_type = 'all') {
        try {
            $profit_sources = [];
            $params = [];
            $types = '';
            
            // 1. Interest from loan repayments (calculated from loan schedule)
            if ($category === 'all' || $category === 'loan_repayments') {
                $profit_sources[] = "
                    SELECT 
                        'Loan Interest' as profit_source,
                        COALESCE(SUM(
                            CASE 
                                WHEN lr.amount_repaid > 0 AND l.total_interest > 0 
                                THEN (lr.amount_repaid / l.total_payable) * l.total_interest
                                ELSE 0
                            END
                        ), 0) as profit_amount
                    FROM loan_repayments lr
                    JOIN loan l ON lr.loan_id = l.loan_id
                    WHERE DATE(lr.date_paid) BETWEEN ? AND ?
                    AND l.total_interest > 0
                ";
                $params = array_merge($params, [$start_date, $end_date]);
                $types .= 'ss';
            }
            
            // 2. All withdrawal fees (pure profit)
            $withdrawal_fees = $this->getWithdrawalFees($start_date, $end_date);
            if ($withdrawal_fees['total'] > 0) {
                $profit_sources[] = "
                    SELECT 
                        'Service Fees' as profit_source,
                        " . $withdrawal_fees['total'] . " as profit_amount
                ";
            }
            
            // 3. Income/Receipts (sales, services - pure profit)
            if ($category === 'all' || $category === 'income_receipts') {
                $profit_sources[] = "
                    SELECT 
                        'Sales & Services' as profit_source,
                        COALESCE(SUM(e.amount), 0) as profit_amount
                    FROM expenses e
                    JOIN expenses_categories ec ON e.category = ec.name
                    WHERE DATE(e.date) BETWEEN ? AND ?
                    AND e.status = 'received'
                ";
                $params = array_merge($params, [$start_date, $end_date]);
                $types .= 'ss';
            }
            
            // 4. Business Group Savings commission (if applicable)
            if ($category === 'all' || $category === 'business_group_savings') {
                $profit_sources[] = "
                    SELECT 
                        'Group Services' as profit_source,
                        COALESCE(SUM(amount * 0.005), 0) as profit_amount
                    FROM business_group_transactions
                    WHERE DATE(date) BETWEEN ? AND ?
                    AND type = 'Savings'
                ";
                $params = array_merge($params, [$start_date, $end_date]);
                $types .= 'ss';
            }
            
            if (empty($profit_sources)) {
                return ['total_profit' => 0, 'profit_breakdown' => []];
            }
            
            $final_query = implode(' UNION ALL ', $profit_sources);
            
            $stmt = $this->db->conn->prepare($final_query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $total_profit = 0;
            $profit_breakdown = [];
            
            foreach ($result as $row) {
                $profit_amount = (float)$row['profit_amount'];
                $total_profit += $profit_amount;
                if ($profit_amount > 0) {
                    $profit_breakdown[] = [
                        'source' => $row['profit_source'],
                        'amount' => $profit_amount
                    ];
                }
            }
            
            return [
                'total_profit' => $total_profit,
                'profit_breakdown' => $profit_breakdown
            ];
            
        } catch (Exception $e) {
            error_log("Error in calculateTrueProfit: " . $e->getMessage());
            return ['total_profit' => 0, 'profit_breakdown' => []];
        }
    }
    
    public function renderStatsCards($stats) {
        ob_start();
        ?>
        <!-- Summary Cards -->
        <div class="row">
            <!-- Income Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card financial-card income-card h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Total Income
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    KSh <?php echo number_format($stats['total_income'], 2); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expenditure Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card financial-card expenditure-card h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Total Expenditure
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    KSh <?php echo number_format($stats['total_expenditure'], 2); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-coins fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Net Position Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card financial-card net-position-card h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Net Position
                                </div>
                                <div class="h5 mb-0 font-weight-bold <?php echo $stats['net_position'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    KSh <?php echo number_format($stats['net_position'], 2); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-balance-scale fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profit Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card financial-card profit-card h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Profit
                                </div>
                                <div class="h5 mb-0 font-weight-bold <?php echo $stats['total_profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    KSh <?php echo number_format($stats['total_profit'], 2); ?>
                                </div>
                                <div class="text-xs text-muted mt-2">
                                    <div><strong>Profit Sources:</strong></div>
                                    <div class="small mt-1">
                                        <?php if (!empty($stats['profit_data']['profit_breakdown'])): ?>
                                            <?php foreach ($stats['profit_data']['profit_breakdown'] as $profit_source): ?>
                                                <div><?php echo htmlspecialchars($profit_source['source']); ?>: 
                                                    KSh <?php echo number_format($profit_source['amount'], 2); ?></div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div>No profit recorded for this period</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>