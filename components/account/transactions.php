<style>
/* Transactions Section Styles */
.transactions-section {
    margin-bottom: 25px;
}

.transactions-section .section-header {
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.transactions-section .section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #51087E;
    margin: 0;
}

.transactions-section .action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.transactions-section .table-container {
    background: #fff;
    border-radius: 0.35rem;
    overflow: hidden;
    box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
    border: 0;
}

.transactions-section .card-body {
    padding: 1.25rem;
}

.transactions-section .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.transactions-section .table {
    width: 100%;
    margin-bottom: 0;
    color: #858796;
    border-collapse: collapse;
}

.transactions-section .table thead th {
    background-color: #51087E;
    color: #fff;
    border-bottom: 1px solid #e3e6f0;
    border-top: none;
    font-weight: 600;
    padding: 0.75rem;
    vertical-align: middle;
    border-right: none;
    font-size: 0.875rem;
}

.transactions-section .table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #e3e6f0;
    border-bottom: none;
    border-right: none;
    font-size: 0.875rem;
}

.transactions-section .table tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.025);
}

.transactions-section .table-bordered {
    border: 1px solid #e3e6f0;
}

.transactions-section .table-bordered th,
.transactions-section .table-bordered td {
    border: 1px solid #e3e6f0;
}

/* Transaction Filters */
.transaction-filters {
    background: rgba(81, 8, 126, 0.05);
    border: 1px solid rgba(81, 8, 126, 0.1);
    border-radius: 0.5rem;
    padding: 20px;
    margin-bottom: 25px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 200px;
}

.filter-group label {
    font-weight: 600;
    color: #51087E;
    font-size: 0.9rem;
    margin: 0;
}

.filter-group select {
    padding: 10px 12px;
    border: 2px solid rgba(81, 8, 126, 0.2);
    border-radius: 0.35rem;
    background: #fff;
    font-size: 0.9rem;
    color: #333;
    transition: all 0.3s ease;
    width: 100%;
    min-width: 200px;
}

.filter-group select:focus {
    outline: none;
    border-color: #51087E;
    box-shadow: 0 0 0 3px rgba(81, 8, 126, 0.1);
}

.date-inputs {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
    min-width: 400px;
}

.date-input-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.date-input-group input {
    padding: 10px 12px;
    border: 2px solid rgba(81, 8, 126, 0.2);
    border-radius: 0.35rem;
    font-size: 0.9rem;
    color: #333;
    transition: all 0.3s ease;
    min-width: 150px;
}

.date-input-group input:focus {
    outline: none;
    border-color: #51087E;
    box-shadow: 0 0 0 3px rgba(81, 8, 126, 0.1);
}

.date-separator {
    color: #51087E;
    font-weight: 600;
    margin: 20px 5px 0 5px;
}

/* Button Styles */
.btn-success-modern {
    background-color: #1cc88a;
    border-color: #1cc88a;
    color: #fff;
    padding: 10px 20px;
    font-size: 0.9rem;
    font-weight: 500;
    line-height: 1.5;
    border-radius: 0.35rem;
    transition: all 0.15s ease-in-out;
    border: 1px solid transparent;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    text-decoration: none;
}

.btn-success-modern:hover {
    background-color: #13855c;
    border-color: #13855c;
    color: #fff;
    text-decoration: none;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(28, 200, 138, 0.3);
}

/* Transaction Type Badges */
.transaction-type {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}

.transaction-type.savings {
    background-color: rgba(28, 200, 138, 0.1);
    color: #1cc88a;
    border: 1px solid rgba(28, 200, 138, 0.2);
}

.transaction-type.withdrawal {
    background-color: rgba(246, 194, 62, 0.1);
    color: #f6c23e;
    border: 1px solid rgba(246, 194, 62, 0.2);
}

.transaction-type.loan {
    background-color: rgba(231, 74, 59, 0.1);
    color: #e74a3b;
    border: 1px solid rgba(231, 74, 59, 0.2);
}

.transaction-type.repayment {
    background-color: rgba(54, 185, 204, 0.1);
    color: #36b9cc;
    border: 1px solid rgba(54, 185, 204, 0.2);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 1.25rem;
    background: #fff;
    border-radius: 0.35rem;
    box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
    border: 0;
}

.empty-icon {
    font-size: 3.5rem;
    color: #d1d3e2;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.empty-text {
    color: #858796;
    font-size: 1rem;
    margin: 0;
}

/* Statistics Summary */
.transaction-summary {
    background: linear-gradient(135deg, #51087E 0%, #6B1FA0 100%);
    border-radius: 0.5rem;
    padding: 25px;
    margin-bottom: 25px;
    color: white;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 25px;
}

.summary-item {
    text-align: center;
}

.summary-label {
    font-size: 0.85rem;
    opacity: 0.85;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.summary-value {
    font-size: 1.4rem;
    font-weight: 700;
    line-height: 1.2;
}

/* DataTable Customizations */
.transactions-section .dataTables_wrapper .dataTables_length,
.transactions-section .dataTables_wrapper .dataTables_filter,
.transactions-section .dataTables_wrapper .dataTables_info,
.transactions-section .dataTables_wrapper .dataTables_paginate {
    margin-bottom: 0.5rem;
}

.transactions-section .dataTables_wrapper .dataTables_length select,
.transactions-section .dataTables_wrapper .dataTables_filter input {
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.transactions-section .dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.375rem 0.75rem;
    margin-left: 0.125rem;
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    background: #fff;
    color: #6c757d;
}

.transactions-section .dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #51087E !important;
    border-color: #51087E !important;
    color: white !important;
}

.transactions-section .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #eaecf4;
    border-color: #d1d3e2;
    color: #6c757d;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .transactions-section .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .transactions-section .action-buttons {
        width: 100%;
        justify-content: flex-start;
    }
    
    .transaction-filters {
        flex-direction: column;
        align-items: stretch;
        gap: 20px;
    }
    
    .filter-group {
        min-width: 100%;
    }
    
    .date-inputs {
        flex-direction: column;
        min-width: 100%;
        gap: 15px;
    }
    
    .date-input-group {
        width: 100%;
    }
    
    .date-input-group input {
        min-width: 100%;
    }
    
    .transaction-summary {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        padding: 20px;
    }
    
    .transactions-section .table {
        font-size: 0.8rem;
    }
    
    .transactions-section .table thead th,
    .transactions-section .table tbody td {
        padding: 0.5rem 0.375rem;
    }
}

@media (max-width: 480px) {
    .transactions-section .section-title {
        font-size: 1.25rem;
    }
    
    .transaction-summary {
        grid-template-columns: 1fr;
        gap: 15px;
        padding: 15px;
    }
    
    .summary-value {
        font-size: 1.2rem;
    }
    
    .empty-state {
        padding: 2rem 1rem;
    }
    
    .empty-icon {
        font-size: 2.5rem;
    }
}
</style>

<!-- Transactions Section -->
<div class="content-section transactions-section" id="transactions-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-exchange-alt"></i>
            Transactions
        </h2>
        <div class="action-buttons">
            <button id="printStatement" class="btn btn-success-modern">
                <i class="fas fa-print"></i> Print Statement
            </button>
        </div>
    </div>
    
    <!-- Transaction Summary -->
    <?php if (!empty($transactions)): ?>
    <div class="transaction-summary">
        <div class="summary-item">
            <div class="summary-label">Total Transactions</div>
            <div class="summary-value" id="totalTransactions"><?= count($transactions) ?></div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Date Range</div>
            <div class="summary-value" id="dateRange">All Time</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Total Credits</div>
            <div class="summary-value" id="totalCredits">KSh 0.00</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Total Debits</div>
            <div class="summary-value" id="totalDebits">KSh 0.00</div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Transaction Filters -->
    <div class="transaction-filters">
        <div class="filter-group">
            <label for="transactionFilter">Filter by Period:</label>
            <select id="transactionFilter">
                <option value="all">All Transactions</option>
                <option value="today">Today</option>
                <option value="week">Last Week</option>
                <option value="month">Last Month</option>
                <option value="quarter">Last Quarter</option>
                <option value="year">Last Year</option>
                <option value="custom">Custom Date Range</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="transactionTypeFilter">Filter by Type:</label>
            <select id="transactionTypeFilter">
                <option value="all">All Types</option>
                <option value="Savings">Savings</option>
                <option value="Withdrawal">Withdrawals</option>
                <option value="Loan Disbursement">Loan Disbursements</option>
                <option value="Loan Repayment">Loan Repayments</option>
            </select>
        </div>
        
        <div class="date-inputs" id="customDateInputs" style="display:none;">
            <div class="date-input-group">
                <label for="startDate">Start Date:</label>
                <input type="date" id="startDate">
            </div>
            <span class="date-separator">to</span>
            <div class="date-input-group">
                <label for="endDate">End Date:</label>
                <input type="date" id="endDate">
            </div>
        </div>
    </div>
    
    <?php if (!empty($transactions)): ?>
        <div class="card mb-4 table-container">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="transactionTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td data-order="<?= strtotime($transaction['date']) ?>"><?= date("Y-m-d H:i:s", strtotime($transaction['date'])) ?></td>
                                    <td>
                                        <?php
                                        $typeClass = '';
                                        switch(strtolower($transaction['type'])) {
                                            case 'savings':
                                                $typeClass = 'savings';
                                                break;
                                            case 'withdrawal':
                                                $typeClass = 'withdrawal';
                                                break;
                                            case 'loan disbursement':
                                                $typeClass = 'loan';
                                                break;
                                            case 'loan repayment':
                                                $typeClass = 'repayment';
                                                break;
                                            default:
                                                $typeClass = 'savings';
                                        }
                                        ?>
                                        <span class="transaction-type <?= $typeClass ?>">
                                            <?= htmlspecialchars($transaction['type']) ?>
                                        </span>
                                    </td>
                                    <td data-order="<?= $transaction['amount'] ?>">
                                        <?php
                                        $isCredit = in_array(strtolower($transaction['type']), ['savings', 'loan disbursement']);
                                        $amountClass = $isCredit ? 'text-success' : 'text-danger';
                                        $amountPrefix = $isCredit ? '+' : '-';
                                        ?>
                                        <span class="<?= $amountClass ?>" style="font-weight: 600;">
                                            <?= $amountPrefix ?>KSh <?= number_format($transaction['amount'], 2) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($transaction['description'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($transaction['reference'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-exchange-alt empty-icon"></i>
            <p class="empty-text">No transactions found for this account.</p>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    const ACCOUNT_ID = <?= $accountId ?>;
    let transactionTable;
    
    // Initialize DataTable for transactions when section becomes active
    $(document).on('sectionChanged', function(event, section) {
        if (section === 'transactions') {
            setTimeout(() => {
                if (!$.fn.DataTable.isDataTable('#transactionTable')) {
                    transactionTable = $('#transactionTable').DataTable({
                        responsive: true,
                        pageLength: 10,
                        order: [[0, 'desc']], // Order by date (newest first)
                        scrollX: true,
                        autoWidth: false,
                        language: {
                            search: "Search:",
                            lengthMenu: "Show _MENU_ entries",
                            info: "Showing _START_ to _END_ of _TOTAL_ entries"
                        },
                        columnDefs: [
                            { targets: [0, 2], className: 'text-nowrap' }, // Date and Amount columns
                            { targets: '_all', className: 'text-left' }
                        ],
                        drawCallback: function(settings) {
                            // Update summary statistics after table is drawn
                            updateTransactionSummary();
                        }
                    });
                    
                    // Initial summary calculation
                    updateTransactionSummary();
                }
            }, 100);
        }
    });

    // =====================================
    // TRANSACTION FILTERING
    // =====================================
    
    $('#transactionFilter').change(function() {
        const filter = $(this).val();
        if (filter === 'custom') {
            $('#customDateInputs').show();
            // Set default dates
            const today = new Date();
            const oneMonthAgo = new Date();
            oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);
            
            $('#endDate').val(today.toISOString().split('T')[0]);
            $('#startDate').val(oneMonthAgo.toISOString().split('T')[0]);
        } else {
            $('#customDateInputs').hide();
            filterTransactions(filter);
        }
    });

    $('#transactionTypeFilter').change(function() {
        filterTransactionsByType($(this).val());
    });

    $('#startDate, #endDate').change(function() {
        if ($('#startDate').val() && $('#endDate').val()) {
            filterTransactions('custom');
        }
    });

    function filterTransactions(filter) {
        if (!transactionTable) return;

        let startDate, endDate;
        const now = new Date();

        switch(filter) {
            case 'today':
                startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
                break;
            case 'week':
                startDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                endDate = now;
                break;
            case 'month':
                startDate = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
                endDate = now;
                break;
            case 'quarter':
                startDate = new Date(now.getFullYear(), now.getMonth() - 3, now.getDate());
                endDate = now;
                break;
            case 'year':
                startDate = new Date(now.getFullYear() - 1, now.getMonth(), now.getDate());
                endDate = now;
                break;
            case 'custom':
                startDate = new Date($('#startDate').val());
                endDate = new Date($('#endDate').val());
                endDate.setHours(23, 59, 59, 999); // End of selected day
                break;
            default:
                // Show all transactions
                $.fn.dataTable.ext.search.pop();
                transactionTable.draw();
                updateDateRangeDisplay('All Time');
                updateTransactionSummary();
                return;
        }

        // Apply date filter
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'transactionTable') return true;
                
                const dateStr = data[0]; // Date column
                const rowDate = new Date(dateStr);
                return (rowDate >= startDate && rowDate <= endDate);
            }
        );

        transactionTable.draw();
        
        // Update date range display
        updateDateRangeDisplay(formatDateRange(startDate, endDate));
        
        // Remove the filter after use
        $.fn.dataTable.ext.search.pop();
    }

    function filterTransactionsByType(type) {
        if (!transactionTable) return;

        if (type === 'all') {
            transactionTable.column(1).search('').draw();
        } else {
            transactionTable.column(1).search(type).draw();
        }
        
        updateTransactionSummary();
    }

    function updateDateRangeDisplay(range) {
        $('#dateRange').text(range);
    }

    function formatDateRange(startDate, endDate) {
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        return startDate.toLocaleDateString('en-US', options) + ' - ' + 
               endDate.toLocaleDateString('en-US', options);
    }

    // =====================================
    // TRANSACTION SUMMARY CALCULATION
    // =====================================
    
    function updateTransactionSummary() {
        if (!transactionTable) return;

        let totalCredits = 0;
        let totalDebits = 0;
        let visibleRows = 0;

        // Get visible rows after filtering
        transactionTable.rows({ search: 'applied' }).every(function() {
            const data = this.data();
            const type = data[1].toLowerCase();
            const amountText = data[2];
            
            // Extract amount from formatted text (remove HTML tags and currency symbols)
            const amountMatch = amountText.match(/[\d,]+\.?\d*/);
            const amount = amountMatch ? parseFloat(amountMatch[0].replace(/,/g, '')) : 0;
            
            visibleRows++;

            // Classify as credit or debit based on type
            if (type.includes('savings') || type.includes('loan disbursement')) {
                totalCredits += amount;
            } else {
                totalDebits += amount;
            }
        });

        // Update summary display
        $('#totalTransactions').text(visibleRows);
        $('#totalCredits').text('KSh ' + formatCurrency(totalCredits));
        $('#totalDebits').text('KSh ' + formatCurrency(totalDebits));
    }

    // =====================================
    // PRINT STATEMENT
    // =====================================
    
    $('#printStatement').click(function() {
        const filter = $('#transactionFilter').val();
        const typeFilter = $('#transactionTypeFilter').val();
        let startDate, endDate;
        
        // Determine date range for statement
        switch(filter) {
            case 'today':
                const today = new Date();
                startDate = today.toISOString().split('T')[0];
                endDate = startDate;
                break;
            case 'week':
                const weekAgo = new Date();
                weekAgo.setDate(weekAgo.getDate() - 7);
                startDate = weekAgo.toISOString().split('T')[0];
                endDate = new Date().toISOString().split('T')[0];
                break;
            case 'month':
                const monthAgo = new Date();
                monthAgo.setMonth(monthAgo.getMonth() - 1);
                startDate = monthAgo.toISOString().split('T')[0];
                endDate = new Date().toISOString().split('T')[0];
                break;
            case 'quarter':
                const quarterAgo = new Date();
                quarterAgo.setMonth(quarterAgo.getMonth() - 3);
                startDate = quarterAgo.toISOString().split('T')[0];
                endDate = new Date().toISOString().split('T')[0];
                break;
            case 'year':
                const yearAgo = new Date();
                yearAgo.setFullYear(yearAgo.getFullYear() - 1);
                startDate = yearAgo.toISOString().split('T')[0];
                endDate = new Date().toISOString().split('T')[0];
                break;
            case 'custom':
                startDate = $('#startDate').val();
                endDate = $('#endDate').val();
                if (!startDate || !endDate) {
                    if (typeof showToast === 'function') {
                        showToast('Please select both start and end dates', 'warning');
                    } else {
                        alert('Please select both start and end dates');
                    }
                    return;
                }
                break;
            default:
                startDate = null;
                endDate = null;
        }

        printStatement(startDate, endDate, typeFilter);
    });

    function printStatement(startDate, endDate, typeFilter) {
        // Get current visible transactions from table
        let transactions = [];
        let totalCredits = 0;
        let totalDebits = 0;
        
        if (transactionTable) {
            transactionTable.rows({ search: 'applied' }).every(function() {
                const rowData = this.data();
                transactions.push(rowData);
                
                // Calculate totals for summary
                const type = rowData[1].toLowerCase();
                const amountText = rowData[2];
                const amountMatch = amountText.match(/[\d,]+\.?\d*/);
                const amount = amountMatch ? parseFloat(amountMatch[0].replace(/,/g, '')) : 0;
                
                if (type.includes('savings') || type.includes('loan disbursement')) {
                    totalCredits += amount;
                } else {
                    totalDebits += amount;
                }
            });
        }

        const statementWindow = window.open('', '_blank', 'width=800,height=600');
        const content = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Transaction Statement</title>
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        margin: 20px; 
                        line-height: 1.4;
                        color: #333;
                    }
                    .header { 
                        text-align: center; 
                        margin-bottom: 30px; 
                        border-bottom: 3px solid #51087E; 
                        padding-bottom: 20px; 
                    }
                    .company-name {
                        font-size: 2rem;
                        font-weight: bold;
                        color: #51087E;
                        margin-bottom: 5px;
                    }
                    .statement-title {
                        font-size: 1.5rem;
                        color: #666;
                        margin-bottom: 20px;
                    }
                    .account-info {
                        background: #f8f9fc;
                        padding: 15px;
                        border-radius: 5px;
                        margin-bottom: 20px;
                        border-left: 4px solid #51087E;
                    }
                    .info-row {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 8px;
                    }
                    .info-label {
                        font-weight: 600;
                        color: #51087E;
                    }
                    .summary-section {
                        background: #e3f2fd;
                        padding: 15px;
                        border-radius: 5px;
                        margin-bottom: 20px;
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                        gap: 15px;
                    }
                    .summary-item {
                        text-align: center;
                    }
                    .summary-label {
                        font-size: 0.9rem;
                        color: #666;
                        margin-bottom: 5px;
                    }
                    .summary-value {
                        font-size: 1.2rem;
                        font-weight: bold;
                        color: #51087E;
                    }
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin: 20px 0; 
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    th, td { 
                        padding: 12px 8px; 
                        border: 1px solid #ddd; 
                        text-align: left; 
                        font-size: 0.9rem;
                    }
                    th { 
                        background-color: #51087E; 
                        color: white; 
                        font-weight: 600;
                        text-transform: uppercase;
                        font-size: 0.8rem;
                        letter-spacing: 0.5px;
                    }
                    tbody tr:nth-child(even) {
                        background-color: #f8f9fc;
                    }
                    tbody tr:hover {
                        background-color: #e8f0fe;
                    }
                    .amount-credit {
                        color: #1cc88a;
                        font-weight: 600;
                    }
                    .amount-debit {
                        color: #e74a3b;
                        font-weight: 600;
                    }
                    .transaction-type {
                        padding: 2px 6px;
                        border-radius: 10px;
                        font-size: 0.7rem;
                        font-weight: 600;
                        text-transform: uppercase;
                    }
                    .type-savings { background: #d4edda; color: #155724; }
                    .type-withdrawal { background: #fff3cd; color: #856404; }
                    .type-loan { background: #f8d7da; color: #721c24; }
                    .type-repayment { background: #d1ecf1; color: #0c5460; }
                    .footer { 
                        text-align: center; 
                        margin-top: 30px; 
                        border-top: 2px solid #51087E; 
                        padding-top: 15px;
                        color: #666;
                    }
                    .no-transactions {
                        text-align: center;
                        padding: 40px;
                        color: #666;
                        font-style: italic;
                    }
                    @media print {
                        body { margin: 0; }
                        .header { page-break-after: avoid; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="company-name">Lato Sacco LTD</div>
                    <div class="statement-title">Transaction Statement</div>
                </div>
                
                <div class="account-info">
                    <div class="info-row">
                        <span class="info-label">Account Holder:</span>
                        <span><?= htmlspecialchars($accountDetails['first_name'] . ' ' . $accountDetails['last_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Shareholder No:</span>
                        <span><?= htmlspecialchars($accountDetails['shareholder_no'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Statement Period:</span>
                        <span>${startDate && endDate ? startDate + ' to ' + endDate : 'All transactions'}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Transaction Type Filter:</span>
                        <span>${typeFilter !== 'all' ? typeFilter : 'All types'}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Generated On:</span>
                        <span>${new Date().toLocaleString('en-KE')}</span>
                    </div>
                </div>

                <div class="summary-section">
                    <div class="summary-item">
                        <div class="summary-label">Total Transactions</div>
                        <div class="summary-value">${transactions.length}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total Credits</div>
                        <div class="summary-value amount-credit">KSh ${formatCurrency(totalCredits)}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total Debits</div>
                        <div class="summary-value amount-debit">KSh ${formatCurrency(totalDebits)}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Net Amount</div>
                        <div class="summary-value">${totalCredits - totalDebits >= 0 ? '+' : ''}KSh ${formatCurrency(totalCredits - totalDebits)}</div>
                    </div>
                </div>
                
                ${transactions.length > 0 ? `
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${transactions.map(trans => {
                            const type = trans[1].toLowerCase();
                            let typeClass = 'type-savings';
                            if (type.includes('withdrawal')) typeClass = 'type-withdrawal';
                            else if (type.includes('loan disbursement')) typeClass = 'type-loan';
                            else if (type.includes('repayment')) typeClass = 'type-repayment';
                            
                            const isCredit = type.includes('savings') || type.includes('loan disbursement');
                            const amountClass = isCredit ? 'amount-credit' : 'amount-debit';
                            
                            return `
                            <tr>
                                <td>${trans[0]}</td>
                                <td><span class="transaction-type ${typeClass}">${trans[1].replace(/<[^>]*>/g, '')}</span></td>
                                <td class="${amountClass}">${trans[2].replace(/<[^>]*>/g, '')}</td>
                                <td>${trans[3] || 'N/A'}</td>
                                <td>${trans[4] || 'N/A'}</td>
                            </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
                ` : `
                <div class="no-transactions">
                    <p>No transactions found for the selected criteria.</p>
                </div>
                `}
                
                <div class="footer">
                    <p><strong>This is a computer-generated statement and does not require a signature.</strong></p>
                    <p>Generated on: ${new Date().toLocaleString('en-KE')} | Lato Sacco LTD Management System</p>
                </div>
            </body>
            </html>
        `;
        
        statementWindow.document.write(content);
        statementWindow.document.close();
        setTimeout(() => {
            statementWindow.print();
        }, 500);
    }

    // =====================================
    // UTILITY FUNCTIONS
    // =====================================
    
    function formatCurrency(amount) {
        return parseFloat(amount || 0).toLocaleString('en-KE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // =====================================
    // REAL-TIME UPDATES
    // =====================================
    
    // Listen for transaction updates
    $(document).on('savingsProcessed withdrawalProcessed loanRepaymentProcessed', function() {
        // Refresh the page to get updated transactions
        setTimeout(() => {
            location.reload();
        }, 2000);
    });

    // =====================================
    // KEYBOARD SHORTCUTS
    // =====================================
    
    $(document).keydown(function(e) {
        // Only work when transactions section is active
        if (!$('#transactions-section').hasClass('active')) return;
        
        // Ctrl + P for print
        if (e.ctrlKey && e.keyCode === 80) {
            e.preventDefault();
            $('#printStatement').click();
        }
        
        // Ctrl + F for filter focus
        if (e.ctrlKey && e.keyCode === 70) {
            e.preventDefault();
            $('#transactionFilter').focus();
        }
    });

    // =====================================
    // INITIALIZATION
    // =====================================
    
    // Set max date for date inputs to today
    const today = new Date().toISOString().split('T')[0];
    $('#startDate, #endDate').attr('max', today);
    
    // Set default end date to today
    $('#endDate').val(today);
    
    // Initialize with current month by default
    setTimeout(() => {
        if ($('#transactions-section').hasClass('active')) {
            $('#transactionFilter').val('month').trigger('change');
        }
    }, 500);

    // =====================================
    // ERROR PREVENTION
    // =====================================
    
    // Prevent any JavaScript errors that might cause browser dialogs
    window.addEventListener('error', function(event) {
        console.error('JavaScript Error in Transactions:', {
            message: event.message,
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno,
            error: event.error
        });
        event.preventDefault();
        return true;
    });

    // Handle promise rejections
    window.addEventListener('unhandledrejection', function(event) {
        console.error('Unhandled Promise Rejection in Transactions:', event.reason);
        event.preventDefault();
    });
});
</script>