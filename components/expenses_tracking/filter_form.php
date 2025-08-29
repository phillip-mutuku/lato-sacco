<?php
// components/expenses_tracking/filter_form.php

class FilterForm {
    private $db;
    private $start_date;
    private $end_date;
    private $category;
    private $transaction_type;
    
    public function __construct($database, $start_date, $end_date, $category, $transaction_type = 'all') {
        $this->db = $database;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->category = $category;
        $this->transaction_type = $transaction_type;
    }
    
    public function render() {
        ob_start();
        ?>
        <!-- Enhanced Filter Section -->
        <div class="card mb-4 filter-section no-print">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-filter"></i> Filter Transactions
                </h6>
            </div>
            <div class="card-body">
                <form id="filterForm" method="GET">
                    <!-- Date Range Row -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="start_date" class="font-weight-bold">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo htmlspecialchars($this->start_date); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="font-weight-bold">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo htmlspecialchars($this->end_date); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="transaction_type" class="font-weight-bold">Transaction Type</label>
                            <select class="form-control" id="transaction_type" name="transaction_type">
                                <option value="all" <?php echo $this->transaction_type === 'all' ? 'selected' : ''; ?>>All Transactions</option>
                                <option value="income_only" <?php echo $this->transaction_type === 'income_only' ? 'selected' : ''; ?>>Income Only</option>
                                <option value="expenditure_only" <?php echo $this->transaction_type === 'expenditure_only' ? 'selected' : ''; ?>>Expenditure Only</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="category" class="font-weight-bold">Category</label>
                            <select class="form-control" id="category" name="category">
                                <option value="all" <?php echo $this->category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                                
                                <!-- Income Categories -->
                                <optgroup label="--- INCOME SOURCES ---">
                                    <option value="individual_savings" <?php echo $this->category === 'individual_savings' ? 'selected' : ''; ?>>Individual Savings</option>
                                    <option value="loan_repayments" <?php echo $this->category === 'loan_repayments' ? 'selected' : ''; ?>>Loan Repayments</option>
                                    <option value="group_savings" <?php echo $this->category === 'group_savings' ? 'selected' : ''; ?>>Group Savings</option>
                                    <option value="business_group_savings" <?php echo $this->category === 'business_group_savings' ? 'selected' : ''; ?>>Business Group Savings</option>
                                    <option value="withdrawal_fees" <?php echo $this->category === 'withdrawal_fees' ? 'selected' : ''; ?>>Withdrawal Fees</option>
                                    <option value="income_receipts" <?php echo $this->category === 'income_receipts' ? 'selected' : ''; ?>>Income/Receipts</option>
                                </optgroup>
                                
                                <!-- Expenditure Categories -->
                                <optgroup label="--- EXPENDITURE TYPES ---">
                                    <option value="business_expenses" <?php echo $this->category === 'business_expenses' ? 'selected' : ''; ?>>Business Expenses</option>
                                    <option value="loan_disbursements" <?php echo $this->category === 'loan_disbursements' ? 'selected' : ''; ?>>Loan Disbursements</option>
                                    <option value="individual_withdrawals" <?php echo $this->category === 'individual_withdrawals' ? 'selected' : ''; ?>>Individual Withdrawals</option>
                                    <option value="group_withdrawals" <?php echo $this->category === 'group_withdrawals' ? 'selected' : ''; ?>>Group Withdrawals</option>
                                    <option value="business_group_withdrawals" <?php echo $this->category === 'business_group_withdrawals' ? 'selected' : ''; ?>>Business Group Withdrawals</option>
                                    <option value="float_management" <?php echo $this->category === 'float_management' ? 'selected' : ''; ?>>Float Management</option>
                                </optgroup>
                                
                                <!-- Expense Categories from Database -->
                                <optgroup label="--- EXPENSE CATEGORIES ---">
                                    <?php 
                                    $categories_query = "SELECT DISTINCT category FROM expenses_categories ORDER BY category";
                                    $categories_result = $this->db->conn->query($categories_query);
                                    while($cat = $categories_result->fetch_assoc()): 
                                    ?>
                                        <option value="expense_<?php echo htmlspecialchars($cat['category']); ?>" 
                                                <?php echo $this->category === 'expense_'.$cat['category'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <!-- Action Buttons Row -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="btn-group" role="group">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="clearAllFilters()">
                                    <i class="fas fa-times-circle"></i> Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function getJavaScript() {
        return "
        $(document).ready(function() {
            // Date validation
            function validateDates() {
                var startDate = new Date($('#start_date').val());
                var endDate = new Date($('#end_date').val());
                
                if (startDate > endDate) {
                    alert('Start date cannot be later than end date');
                    return false;
                }
                return true;
            }
            
            // Auto-apply filters on change
            $('#category, #transaction_type').change(function() {
                if (validateDates()) {
                    $('#filterForm').submit();
                }
            });
            
            // Form submission handler
            $('#filterForm').on('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Date change handlers with debouncing
            $('#start_date, #end_date').change(function() {
                if (validateDates()) {
                    clearTimeout(window.dateChangeTimeout);
                    window.dateChangeTimeout = setTimeout(function() {
                        $('#filterForm').submit();
                    }, 800);
                }
            });
        });
        
        // Clear all filters
        function clearAllFilters() {
            // Set default date range (current month)
            var today = new Date();
            var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            $('#start_date').val(formatDate(firstDay));
            $('#end_date').val(formatDate(today));
            
            // Reset select elements to 'all'
            $('#category, #transaction_type').val('all');
            
            // Submit form
            $('#filterForm').submit();
        }
        
        // Format date for input field
        function formatDate(date) {
            return date.getFullYear() + '-' + 
                   String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(date.getDate()).padStart(2, '0');
        }
        ";
    }
}
?>