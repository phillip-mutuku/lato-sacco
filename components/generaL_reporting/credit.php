<?php
// components/general_reporting/credit.php

class CreditCalculator {
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
     * Get all credit items (expenses by category)
     */
    public function getCreditData() {
        $credit_items = [];
        
        // Get all expense categories
        $categories = $this->getExpenseCategories();
        
        foreach ($categories as $category) {
            $opening_balance = $this->getCategoryOpeningBalance($category['name']);
            $period_expenses = $this->getCategoryPeriodExpenses($category['name']);
            $closing_balance = $opening_balance + $period_expenses;
            
            $credit_items[] = [
                'main_category' => $category['category'],
                'category' => $category['name'],
                'opening_balance' => $opening_balance,
                'transactions' => $period_expenses,
                'closing_balance' => $closing_balance
            ];
        }
        
        return $credit_items;
    }
    
    /**
     * Get all expense categories
     */
    private function getExpenseCategories() {
        $query = "SELECT name, category FROM expenses_categories ORDER BY category, name";
        $result = $this->db->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get opening balance for a category (expenses before start date)
     */
    private function getCategoryOpeningBalance($category_name) {
        $query = "
            SELECT COALESCE(SUM(ABS(amount)), 0) as opening_expenses
            FROM expenses
            WHERE category = ?
            AND status = 'completed'
            AND DATE(date) <= ?
        ";
        
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param('ss', $category_name, $this->opening_date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return floatval($result['opening_expenses']);
    }
    
    /**
     * Get expenses during the period for a category
     */
    private function getCategoryPeriodExpenses($category_name) {
        $query = "
            SELECT COALESCE(SUM(ABS(amount)), 0) as period_expenses
            FROM expenses
            WHERE category = ?
            AND status = 'completed'
            AND DATE(date) BETWEEN ? AND ?
        ";
        
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param('sss', $category_name, $this->start_date, $this->end_date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return floatval($result['period_expenses']);
    }
    
    /**
     * Calculate total credit
     */
    public function getTotalCredit() {
        $credit_data = $this->getCreditData();
        $total = 0;
        
        foreach ($credit_data as $item) {
            $total += $item['closing_balance'];
        }
        
        return $total;
    }
    
    /**
     * Render credit table HTML
     */
    public function renderCreditTable() {
        $credit_data = $this->getCreditData();
        $total_opening = 0;
        $total_transactions = 0;
        $total_closing = 0;
        
        ob_start();
        ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="creditTable">
                <thead class="bg-danger text-white">
                    <tr>
                        <th rowspan="2" class="align-middle">CREDIT</th>
                        <th colspan="3" class="text-center">BALANCES AS OF <?php echo strtoupper(date('d/m/Y', strtotime($this->opening_date))); ?></th>
                        <th colspan="3" class="text-center">TRANSACTIONS</th>
                        <th colspan="3" class="text-center">BALANCES AS OF <?php echo strtoupper(date('d/m/Y', strtotime($this->end_date))); ?></th>
                    </tr>
                    <tr>
                        <th>Main Category</th>
                        <th>Expense Name</th>
                        <th class="text-right">Amount (KSh)</th>
                        <th>Main Category</th>
                        <th>Expense Name</th>
                        <th class="text-right">Amount (KSh)</th>
                        <th>Main Category</th>
                        <th>Expense Name</th>
                        <th class="text-right">Amount (KSh)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($credit_data as $item): ?>
                        <?php 
                            $total_opening += $item['opening_balance'];
                            $total_transactions += $item['transactions'];
                            $total_closing += $item['closing_balance'];
                        ?>
                        <tr>
                            <td></td>
                            <!-- Opening Balance -->
                            <td><?php echo htmlspecialchars($item['main_category']); ?></td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td class="text-right"><?php echo number_format($item['opening_balance'], 2); ?></td>
                            <!-- Transactions -->
                            <td><?php echo htmlspecialchars($item['main_category']); ?></td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td class="text-right <?php echo $item['transactions'] > 0 ? 'text-danger font-weight-bold' : ''; ?>">
                                <?php echo number_format($item['transactions'], 2); ?>
                            </td>
                            <!-- Closing Balance -->
                            <td><?php echo htmlspecialchars($item['main_category']); ?></td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td class="text-right font-weight-bold"><?php echo number_format($item['closing_balance'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- Total Row -->
                    <tr class="bg-danger text-white font-weight-bold">
                        <td colspan="3" class="text-right">TOTAL CREDIT:</td>
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