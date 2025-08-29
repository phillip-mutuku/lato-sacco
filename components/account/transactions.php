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

/* Transaction Filters */
.transaction-filters {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    align-items: center;
    background: #fff;
    padding: 20px;
    border-radius: 0.35rem;
    box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
    border: 0;
}

.transaction-filters .custom-select {
    min-width: 200px;
    max-width: 250px;
    padding: 8px 30px 8px 12px;
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    font-size: 0.875rem;
    background-color: #fff;
    color: #6c757d;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    appearance: none;
}

.transaction-filters .custom-select:focus {
    outline: none;
    border-color: #51087E;
    box-shadow: 0 0 0 0.2rem rgba(81, 8, 126, 0.1);
}

.date-inputs {
    display: flex;
    gap: 10px;
    align-items: center;
}

.date-inputs input {
    padding: 8px 12px;
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    font-size: 0.875rem;
    color: #6c757d;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.date-inputs input:focus {
    outline: none;
    border-color: #51087E;
    box-shadow: 0 0 0 0.2rem rgba(81, 8, 126, 0.1);
}

.date-inputs span {
    color: #6c757d;
    font-size: 0.875rem;
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

/* Badge Styles */
.badge-modern {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    display: inline-block;
}

.badge-success { 
    background-color: #1cc88a; 
    color: #fff; 
}

.badge-warning { 
    background-color: #f6c23e; 
    color: #fff; 
}

.badge-danger { 
    background-color: #e74a3b; 
    color: #fff; 
}

.badge-info { 
    background-color: #36b9cc; 
    color: #fff; 
}

.badge-secondary { 
    background-color: #6c757d; 
    color: #fff; 
}

.badge-primary { 
    background-color: #4e73df; 
    color: #fff; 
}

/* Button Styles */
.btn-success-modern {
    background-color: #1cc88a;
    border-color: #1cc88a;
    color: #fff;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 400;
    line-height: 1.5;
    border-radius: 0.35rem;
    transition: all 0.15s ease-in-out;
    border: 1px solid transparent;
    display: inline-block;
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
}

.btn-success-modern:focus {
    box-shadow: 0 0 0 0.2rem rgba(28, 200, 138, 0.25);
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

/* Responsive adjustments */
@media (max-width: 768px) {
    .transactions-section .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .transaction-filters {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }

    .transaction-filters .custom-select {
        max-width: 100%;
    }

    .date-inputs {
        justify-content: center;
        flex-wrap: wrap;
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
    </div>
    
    <div class="transaction-filters">
        <select id="transactionFilter" class="custom-select">
            <option value="all">All Transactions</option>
            <option value="week">Last Week</option>
            <option value="month">Last Month</option>
            <option value="year">Last Year</option>
            <option value="custom">Custom Date Range</option>
        </select>
        
        <select id="transactionType" class="custom-select">
            <option value="all">All Types</option>
            <option value="Savings">Savings</option>
            <option value="Withdrawal">Withdrawals</option>
            <option value="Loan Repayment">Loan Repayments</option>
            <option value="Group Savings">Group Savings</option>
            <option value="Business Savings">Business Savings</option>
            <option value="Interest">Interest</option>
            <option value="Fees">Fees</option>
        </select>
        
        <div class="date-inputs" id="customDateInputs" style="display:none;">
            <input type="date" id="startDate" placeholder="Start Date">
            <span>to</span>
            <input type="date" id="endDate" placeholder="End Date">
        </div>
        
        <button id="printStatement" class="btn btn-success-modern">
            <i class="fas fa-print"></i> Print Statement
        </button>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?= date("Y-m-d", strtotime($transaction['date'])) ?></td>
                                    <td data-type="<?= htmlspecialchars($transaction['type']) ?>">
                                        <span class="badge badge-pill <?= getTransactionBadgeClass($transaction['type']) ?>">
                                            <?= htmlspecialchars($transaction['type']) ?>
                                        </span>
                                    </td>
                                    <td>KSh <?= number_format($transaction['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($transaction['description']) ?></td>
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

<?php
// Helper function for transaction badge colors
function getTransactionBadgeClass($type) {
    switch(strtolower($type)) {
        case 'savings':
        case 'group savings':
        case 'business savings':
            return 'badge-success';
        case 'withdrawal':
            return 'badge-warning';
        case 'loan repayment':
            return 'badge-info';
        case 'interest':
            return 'badge-primary';
        case 'fees':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}
?>

<script>
$(document).ready(function() {
    // Initialize DataTable for transactions when section becomes active
    $(document).on('sectionChanged', function(event, section) {
        if (section === 'transactions') {
            setTimeout(() => {
                if (!$.fn.DataTable.isDataTable('#transactionTable')) {
                    $('#transactionTable').DataTable({
                        responsive: true,
                        pageLength: 10,
                        order: [[0, 'desc']],
                        scrollX: true,
                        autoWidth: false,
                        language: {
                            search: "Search:",
                            lengthMenu: "Show _MENU_ entries",
                            info: "Showing _START_ to _END_ of _TOTAL_ entries"
                        },
                        columnDefs: [
                            { targets: '_all', className: 'text-nowrap' }
                        ]
                    });
                }
            }, 100);
        }
    });

    // Transaction filter handling
    $('#transactionFilter').change(function() {
        const filter = $(this).val();
        if (filter === 'custom') {
            $('#customDateInputs').show();
        } else {
            $('#customDateInputs').hide();
            filterTransactions();
        }
    });

    // Transaction type filter handling
    $('#transactionType').change(function() {
        filterTransactions();
    });

    $('#startDate, #endDate').change(function() {
        if ($('#startDate').val() && $('#endDate').val()) {
            filterTransactions();
        }
    });

    function filterTransactions() {
        if (!$.fn.DataTable.isDataTable('#transactionTable')) {
            return;
        }

        const table = $('#transactionTable').DataTable();
        const dateFilter = $('#transactionFilter').val();
        const typeFilter = $('#transactionType').val();

        // Clear existing search
        $.fn.dataTable.ext.search = [];

        // Add date filter
        if (dateFilter !== 'all') {
            let startDate, endDate;
            const now = new Date();

            switch(dateFilter) {
                case 'week':
                    startDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                    endDate = now;
                    break;
                case 'month':
                    startDate = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
                    endDate = now;
                    break;
                case 'year':
                    startDate = new Date(now.getFullYear() - 1, now.getMonth(), now.getDate());
                    endDate = now;
                    break;
                case 'custom':
                    startDate = new Date($('#startDate').val());
                    endDate = new Date($('#endDate').val());
                    break;
            }

            if (startDate && endDate) {
                $.fn.dataTable.ext.search.push(
                    function(settings, data, dataIndex) {
                        const dateStr = data[0];
                        const rowDate = new Date(dateStr);
                        return (rowDate >= startDate && rowDate <= endDate);
                    }
                );
            }
        }

        // Add type filter
        if (typeFilter !== 'all') {
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    // Get the actual row element to extract the data-type attribute
                    const row = $('#transactionTable').DataTable().row(dataIndex).node();
                    const typeCell = $(row).find('td[data-type]');
                    const typeText = typeCell.length > 0 ? typeCell.attr('data-type') : '';
                    return typeText === typeFilter;
                }
            );
        }

        table.draw();
    }

    // Print Statement
    $('#printStatement').click(function() {
        const filter = $('#transactionFilter').val();
        const typeFilter = $('#transactionType').val();
        let startDate, endDate;
        
        switch(filter) {
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
        let totalAmount = 0;
        
        if ($.fn.DataTable.isDataTable('#transactionTable')) {
            const table = $('#transactionTable').DataTable();
            table.rows({ search: 'applied' }).every(function() {
                const rowData = this.data();
                const row = this.node();
                transactions.push(rowData);
                
                // Get the actual transaction type from the data-type attribute
                const typeCell = $(row).find('td[data-type]');
                const transactionType = typeCell.length > 0 ? typeCell.attr('data-type') : '';
                
                // Extract amount from "KSh X,XXX.XX" format
                const amountText = rowData[2].replace(/[^\d.-]/g, '');
                const amount = parseFloat(amountText) || 0;
                totalAmount += amount;
            });
        } else {
            // Fallback: get all table rows
            $('#transactionTable tbody tr:visible').each(function() {
                const row = [];
                $(this).find('td').each(function() {
                    row.push($(this).text());
                });
                if (row.length > 0) {
                    transactions.push(row);
                    const amountText = row[2].replace(/[^\d.-]/g, '');
                    const amount = parseFloat(amountText) || 0;
                    totalAmount += amount;
                }
            });
        }

        const typeFilterText = typeFilter === 'all' ? 'All Types' : typeFilter;
        const statementWindow = window.open('', '_blank', 'width=800,height=600');
        const content = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Account Statement</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                    th { background-color: #51087E; color: white; }
                    .total-row { background-color: #f8f9fa; font-weight: bold; border-top: 2px solid #51087E; }
                    .total-amount { color: #51087E; font-size: 1.1em; }
                    .footer { text-align: center; margin-top: 20px; border-top: 2px solid #333; padding-top: 10px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>Lato Sacco LTD</h2>
                    <h3>Account Statement</h3>
                    <p><strong>Account:</strong> <?= htmlspecialchars($accountDetails['first_name'] . ' ' . $accountDetails['last_name']) ?></p>
                    <p><strong>Period:</strong> ${startDate || 'Beginning'} to ${endDate || 'Present'}</p>
                    <p><strong>Transaction Type:</strong> ${typeFilterText}</p>
                    <p><strong>Total Transactions:</strong> ${transactions.length}</p>
                </div>
                <table>
                    <thead>
                        <tr><th>Date</th><th>Type</th><th>Amount</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        ${transactions.map(trans => {
                            // Extract plain text from badge if it exists
                            const typeText = $(trans[1]).text() || trans[1];
                            return `<tr><td>${trans[0]}</td><td>${typeText}</td><td>${trans[2]}</td><td>${trans[3]}</td></tr>`;
                        }).join('')}
                        <tr class="total-row">
                            <td colspan="2" style="text-align: right;"><strong>Total Amount:</strong></td>
                            <td class="total-amount"><strong>KSh ${totalAmount.toLocaleString('en-KE', {minimumFractionDigits: 2})}</strong></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
                <div class="footer">
                    <p>Statement generated on: ${new Date().toLocaleString('en-KE')}</p>
                    <p>This statement shows ${transactions.length} transactions with a total value of KSh ${totalAmount.toLocaleString('en-KE', {minimumFractionDigits: 2})}</p>
                </div>
            </body>
            </html>
        `;
        
        statementWindow.document.write(content);
        statementWindow.document.close();
        setTimeout(() => statementWindow.print(), 500);
    }
});
</script>