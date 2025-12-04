<?php
// components/general_reporting/debit.php

class DebitCalculator {
    private $db;
    private $start_date;
    private $end_date;
    private $opening_date;
    
    public function __construct($database, $start_date, $end_date, $opening_date) {
        $this->db = $database;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->opening_date = $opening_date;
    }
    
    /**
     * Get all debit items (loan products interest + withdrawal fees)
     */
    public function getDebitData() {
        $debit_items = [];
        
        // 1. Get all loan products
        $loan_products = $this->getLoanProducts();
        
        foreach ($loan_products as $product) {
            $opening_balance = $this->getLoanProductOpeningBalance($product['id']);
            $period_interest = $this->getLoanProductPeriodInterest($product['id']);
            $closing_balance = $opening_balance + $period_interest;
            
            $debit_items[] = [
                'category' => 'Loan Interest',
                'name' => $product['loan_type'] . ' Interest',
                'opening_balance' => $opening_balance,
                'transactions' => $period_interest,
                'closing_balance' => $closing_balance
            ];
        }
        
        // 2. Get withdrawal fees
        $withdrawal_fees = $this->getWithdrawalFees();
        $debit_items[] = $withdrawal_fees;
        
        return $debit_items;
    }
    
    /**
     * Get all loan products from database
     */
    private function getLoanProducts() {
        $query = "SELECT id, loan_type, interest_rate FROM loan_products ORDER BY loan_type";
        $result = $this->db->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get opening balance for a loan product (interest earned before start date)
     */
    private function getLoanProductOpeningBalance($product_id) {
        $query = "
            SELECT COALESCE(SUM(ls.interest), 0) as opening_interest
            FROM loan_schedule ls
            JOIN loan l ON ls.loan_id = l.loan_id
            WHERE l.loan_product_id = ?
            AND ls.status = 'paid'
            AND DATE(ls.paid_date) <= ?
        ";
        
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param('is', $product_id, $this->opening_date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return floatval($result['opening_interest']);
    }
    
    /**
     * Get interest earned during the period for a loan product
     * CRITICAL: Only count interest when the specific monthly payment is marked as paid
     */
    private function getLoanProductPeriodInterest($product_id) {
        $query = "
            SELECT COALESCE(SUM(ls.interest), 0) as period_interest
            FROM loan_schedule ls
            JOIN loan l ON ls.loan_id = l.loan_id
            WHERE l.loan_product_id = ?
            AND ls.status = 'paid'
            AND DATE(ls.paid_date) BETWEEN ? AND ?
        ";
        
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param('iss', $product_id, $this->start_date, $this->end_date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return floatval($result['period_interest']);
    }
    
    /**
     * Get all withdrawal fees (opening, transactions, closing)
     */
    private function getWithdrawalFees() {
        $opening_fees = $this->getWithdrawalFeesBeforeDate($this->opening_date);
        $period_fees = $this->getWithdrawalFeesBetweenDates($this->start_date, $this->end_date);
        $closing_fees = $opening_fees + $period_fees;
        
        return [
            'category' => 'Service Fees',
            'name' => 'Withdrawal Fees',
            'opening_balance' => $opening_fees,
            'transactions' => $period_fees,
            'closing_balance' => $closing_fees
        ];
    }
    
    /**
     * Get withdrawal fees before a specific date
     */
    private function getWithdrawalFeesBeforeDate($date) {
        $total = 0;
        
        // Individual savings withdrawal fees
        $query1 = "
            SELECT COALESCE(SUM(withdrawal_fee), 0) as fees
            FROM savings
            WHERE DATE(date) <= ?
            AND type = 'Withdrawal'
            AND withdrawal_fee > 0
        ";
        $stmt1 = $this->db->conn->prepare($query1);
        $stmt1->bind_param('s', $date);
        $stmt1->execute();
        $result1 = $stmt1->get_result()->fetch_assoc();
        $total += floatval($result1['fees']);
        
        // Business group withdrawal fees
        $query2 = "
            SELECT COALESCE(SUM(amount), 0) as fees
            FROM business_group_transactions
            WHERE DATE(date) <= ?
            AND type = 'Withdrawal Fee'
        ";
        $stmt2 = $this->db->conn->prepare($query2);
        $stmt2->bind_param('s', $date);
        $stmt2->execute();
        $result2 = $stmt2->get_result()->fetch_assoc();
        $total += floatval($result2['fees']);
        
        // Loan disbursement withdrawal fees
        $query3 = "
            SELECT COALESCE(SUM(withdrawal_fee), 0) as fees
            FROM payment
            WHERE DATE(date_created) <= ?
            AND withdrawal_fee > 0
        ";
        $stmt3 = $this->db->conn->prepare($query3);
        $stmt3->bind_param('s', $date);
        $stmt3->execute();
        $result3 = $stmt3->get_result()->fetch_assoc();
        $total += floatval($result3['fees']);
        
        return $total;
    }
    
    /**
     * Get withdrawal fees between two dates
     */
    private function getWithdrawalFeesBetweenDates($start, $end) {
        $total = 0;
        
        // Individual savings withdrawal fees
        $query1 = "
            SELECT COALESCE(SUM(withdrawal_fee), 0) as fees
            FROM savings
            WHERE DATE(date) BETWEEN ? AND ?
            AND type = 'Withdrawal'
            AND withdrawal_fee > 0
        ";
        $stmt1 = $this->db->conn->prepare($query1);
        $stmt1->bind_param('ss', $start, $end);
        $stmt1->execute();
        $result1 = $stmt1->get_result()->fetch_assoc();
        $total += floatval($result1['fees']);
        
        // Business group withdrawal fees
        $query2 = "
            SELECT COALESCE(SUM(amount), 0) as fees
            FROM business_group_transactions
            WHERE DATE(date) BETWEEN ? AND ?
            AND type = 'Withdrawal Fee'
        ";
        $stmt2 = $this->db->conn->prepare($query2);
        $stmt2->bind_param('ss', $start, $end);
        $stmt2->execute();
        $result2 = $stmt2->get_result()->fetch_assoc();
        $total += floatval($result2['fees']);
        
        // Loan disbursement withdrawal fees
        $query3 = "
            SELECT COALESCE(SUM(withdrawal_fee), 0) as fees
            FROM payment
            WHERE DATE(date_created) BETWEEN ? AND ?
            AND withdrawal_fee > 0
        ";
        $stmt3 = $this->db->conn->prepare($query3);
        $stmt3->bind_param('ss', $start, $end);
        $stmt3->execute();
        $result3 = $stmt3->get_result()->fetch_assoc();
        $total += floatval($result3['fees']);
        
        return $total;
    }
    
    /**
     * Calculate total debit
     */
    public function getTotalDebit() {
        $debit_data = $this->getDebitData();
        $total = 0;
        
        foreach ($debit_data as $item) {
            $total += $item['closing_balance'];
        }
        
        return $total;
    }
    
    /**
     * Render debit table HTML
     */
    public function renderDebitTable() {
        $debit_data = $this->getDebitData();
        $total_opening = 0;
        $total_transactions = 0;
        $total_closing = 0;
        
        ob_start();
        ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="debitTable">
                <thead class="bg-success text-white">
                    <tr>
                        <th rowspan="2" class="align-middle">DEBIT</th>
                        <th colspan="3" class="text-center">BALANCES AS OF <?php echo strtoupper(date('d/m/Y', strtotime($this->opening_date))); ?></th>
                        <th colspan="3" class="text-center">TRANSACTIONS</th>
                        <th colspan="3" class="text-center">BALANCES AS OF <?php echo strtoupper(date('d/m/Y', strtotime($this->end_date))); ?></th>
                    </tr>
                    <tr>
                        <th>Category</th>
                        <th>Item</th>
                        <th class="text-right">Amount (KSh)</th>
                        <th>Category</th>
                        <th>Item</th>
                        <th class="text-right">Amount (KSh)</th>
                        <th>Category</th>
                        <th>Item</th>
                        <th class="text-right">Amount (KSh)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($debit_data as $item): ?>
                        <?php 
                            $total_opening += $item['opening_balance'];
                            $total_transactions += $item['transactions'];
                            $total_closing += $item['closing_balance'];
                        ?>
                        <tr>
                            <td></td>
                            <!-- Opening Balance -->
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="text-right"><?php echo number_format($item['opening_balance'], 2); ?></td>
                            <!-- Transactions -->
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="text-right <?php echo $item['transactions'] > 0 ? 'text-success font-weight-bold' : ''; ?>">
                                <?php echo number_format($item['transactions'], 2); ?>
                            </td>
                            <!-- Closing Balance -->
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="text-right font-weight-bold"><?php echo number_format($item['closing_balance'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- Total Row -->
                    <tr class="bg-success text-white font-weight-bold">
                        <td colspan="3" class="text-right">TOTAL DEBIT:</td>
                        <td class="text-right"><?php echo number_format($total_opening, 2); ?></td>
                        <td colspan="2" class="text-right">TOTAL:</td>
                        <td class="text-right"><?php echo number_format($total_transactions, 2); ?></td>
                        <td colspan="2" class="text-right">TOTAL:</td>
                        <td class="text-right"><?php echo number_format($total_closing, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>