<?php
// components/expenses_tracking/income_table.php

class IncomeTable {
    private $db;
    private $start_date;
    private $end_date;
    private $category;
    private $transaction_type;
    
    public function __construct($db, $start_date, $end_date, $category = 'all', $transaction_type = 'all') {
        $this->db = $db;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->category = $category;
        $this->transaction_type = $transaction_type;
    }
    
    private function getDetailedIncomeData() {
        // Skip if transaction type is expenditure only
        if ($this->transaction_type === 'expenditure_only') {
            return [];
        }
        
        $query_parts = [];
        $params = [];
        $types = '';
        
        // Individual Savings
        if ($this->category === 'all' || $this->category === 'individual_savings') {
            $query_parts[] = "
                SELECT 
                    CAST('Individual Savings' AS CHAR(50)) COLLATE utf8mb4_general_ci as source_type,
                    CAST(CONCAT(COALESCE(ca.first_name, ''), ' ', COALESCE(ca.last_name, '')) AS CHAR(255)) COLLATE utf8mb4_general_ci as source_name,
                    CAST(COALESCE(ca.shareholder_no, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as reference_no,
                    s.amount,
                    s.date as transaction_date,
                    CAST(COALESCE(s.payment_mode, 'Cash') AS CHAR(50)) COLLATE utf8mb4_general_ci as payment_mode,
                    CAST(COALESCE(s.receipt_number, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as receipt_number,
                    CAST(COALESCE(s.served_by, 'System') AS CHAR(255)) COLLATE utf8mb4_general_ci as served_by
                FROM savings s
                LEFT JOIN client_accounts ca ON s.account_id = ca.account_id
                WHERE DATE(s.date) BETWEEN ? AND ?
                AND s.type = 'Savings'
            ";
            $params = array_merge($params, [$this->start_date, $this->end_date]);
            $types .= 'ss';
        }
        
        // Loan Repayments
        if ($this->category === 'all' || $this->category === 'loan_repayments') {
            $query_parts[] = "
                SELECT 
                    CAST('Loan Repayments' AS CHAR(50)) COLLATE utf8mb4_general_ci as source_type,
                    CAST(CONCAT(COALESCE(ca.first_name, ''), ' ', COALESCE(ca.last_name, '')) AS CHAR(255)) COLLATE utf8mb4_general_ci as source_name,
                    CAST(COALESCE(l.ref_no, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as reference_no,
                    lr.amount_repaid as amount,
                    lr.date_paid as transaction_date,
                    CAST(COALESCE(lr.payment_mode, 'Cash') AS CHAR(50)) COLLATE utf8mb4_general_ci as payment_mode,
                    CAST(COALESCE(lr.receipt_number, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as receipt_number,
                    CAST(COALESCE(lr.served_by, 'System') AS CHAR(255)) COLLATE utf8mb4_general_ci as served_by
                FROM loan_repayments lr
                LEFT JOIN loan l ON lr.loan_id = l.loan_id
                LEFT JOIN client_accounts ca ON l.account_id = ca.account_id
                WHERE DATE(lr.date_paid) BETWEEN ? AND ?
            ";
            $params = array_merge($params, [$this->start_date, $this->end_date]);
            $types .= 'ss';
        }
        
        // Group Savings
        if ($this->category === 'all' || $this->category === 'group_savings') {
            $query_parts[] = "
                SELECT 
                    CAST('Group Savings' AS CHAR(50)) COLLATE utf8mb4_general_ci as source_type,
                    CAST(COALESCE(lg.group_name, 'Unknown Group') AS CHAR(255)) COLLATE utf8mb4_general_ci as source_name,
                    CAST(COALESCE(lg.group_reference, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as reference_no,
                    gs.amount,
                    gs.date_saved as transaction_date,
                    CAST(COALESCE(gs.payment_mode, 'Cash') AS CHAR(50)) COLLATE utf8mb4_general_ci as payment_mode,
                    CAST(COALESCE(gs.receipt_no, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as receipt_number,
                    CAST(CONCAT(COALESCE(u.firstname, 'Unknown'), ' ', COALESCE(u.lastname, '')) AS CHAR(255)) COLLATE utf8mb4_general_ci as served_by
                FROM group_savings gs
                LEFT JOIN lato_groups lg ON gs.group_id = lg.group_id
                LEFT JOIN user u ON gs.served_by = u.user_id
                WHERE DATE(gs.date_saved) BETWEEN ? AND ?
            ";
            $params = array_merge($params, [$this->start_date, $this->end_date]);
            $types .= 'ss';
        }
        
        // Business Group Savings
        if ($this->category === 'all' || $this->category === 'business_group_savings') {
            $query_parts[] = "
                SELECT 
                    CAST('Business Group Savings' AS CHAR(50)) COLLATE utf8mb4_general_ci as source_type,
                    CAST(COALESCE(bg.group_name, 'Unknown Group') AS CHAR(255)) COLLATE utf8mb4_general_ci as source_name,
                    CAST(COALESCE(bg.reference_name, CAST(bg.account_id AS CHAR)) AS CHAR(50)) COLLATE utf8mb4_general_ci as reference_no,
                    bgt.amount,
                    bgt.date as transaction_date,
                    CAST(COALESCE(bgt.payment_mode, 'Cash') AS CHAR(50)) COLLATE utf8mb4_general_ci as payment_mode,
                    CAST(COALESCE(bgt.receipt_no, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as receipt_number,
                    CAST(CONCAT(COALESCE(u.firstname, 'Unknown'), ' ', COALESCE(u.lastname, '')) AS CHAR(255)) COLLATE utf8mb4_general_ci as served_by
                FROM business_group_transactions bgt
                LEFT JOIN business_groups bg ON bgt.group_id = bg.group_id
                LEFT JOIN user u ON bgt.served_by = u.user_id
                WHERE DATE(bgt.date) BETWEEN ? AND ?
                AND bgt.type = 'Savings'
            ";
            $params = array_merge($params, [$this->start_date, $this->end_date]);
            $types .= 'ss';
        }
        
        // Withdrawal Fees
        if ($this->category === 'all' || $this->category === 'withdrawal_fees') {
            // Individual withdrawal fees
            $query_parts[] = "
                SELECT 
                    CAST('Withdrawal Fees (Individual)' AS CHAR(50)) COLLATE utf8mb4_general_ci as source_type,
                    CAST(CONCAT(COALESCE(ca.first_name, ''), ' ', COALESCE(ca.last_name, '')) AS CHAR(255)) COLLATE utf8mb4_general_ci as source_name,
                    CAST(COALESCE(ca.shareholder_no, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as reference_no,
                    s.withdrawal_fee as amount,
                    s.date as transaction_date,
                    CAST(COALESCE(s.payment_mode, 'Cash') AS CHAR(50)) COLLATE utf8mb4_general_ci as payment_mode,
                    CAST(COALESCE(s.receipt_number, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as receipt_number,
                    CAST(COALESCE(s.served_by, 'System') AS CHAR(255)) COLLATE utf8mb4_general_ci as served_by
                FROM savings s
                LEFT JOIN client_accounts ca ON s.account_id = ca.account_id
                WHERE DATE(s.date) BETWEEN ? AND ?
                AND s.type = 'Withdrawal'
                AND s.withdrawal_fee > 0
            ";
            $params = array_merge($params, [$this->start_date, $this->end_date]);
            $types .= 'ss';
            
            // Payment withdrawal fees
            $query_parts[] = "
                SELECT 
                    CAST('Withdrawal Fees (Payment)' AS CHAR(50)) COLLATE utf8mb4_general_ci as source_type,
                    CAST(CONCAT(COALESCE(ca.first_name, ''), ' ', COALESCE(ca.last_name, '')) AS CHAR(255)) COLLATE utf8mb4_general_ci as source_name,
                    CAST(COALESCE(l.ref_no, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as reference_no,
                    p.withdrawal_fee as amount,
                    p.date_created as transaction_date,
                    CAST('Cash' AS CHAR(50)) COLLATE utf8mb4_general_ci as payment_mode,
                    CAST(COALESCE(p.receipt_no, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as receipt_number,
                    CAST(COALESCE(u.username, 'System') AS CHAR(255)) COLLATE utf8mb4_general_ci as served_by
                FROM payment p
                LEFT JOIN loan l ON p.loan_id = l.loan_id
                LEFT JOIN client_accounts ca ON l.account_id = ca.account_id
                LEFT JOIN user u ON p.user_id = u.user_id
                WHERE DATE(p.date_created) BETWEEN ? AND ?
                AND p.withdrawal_fee > 0
            ";
            $params = array_merge($params, [$this->start_date, $this->end_date]);
            $types .= 'ss';
            
            // Business group fees
            $query_parts[] = "
                SELECT 
                    CAST('Business Group Fees' AS CHAR(50)) COLLATE utf8mb4_general_ci as source_type,
                    CAST(COALESCE(bg.group_name, 'Unknown Group') AS CHAR(255)) COLLATE utf8mb4_general_ci as source_name,
                    CAST(COALESCE(bg.reference_name, CAST(bg.account_id AS CHAR)) AS CHAR(50)) COLLATE utf8mb4_general_ci as reference_no,
                    bgt.amount,
                    bgt.date as transaction_date,
                    CAST(COALESCE(bgt.payment_mode, 'Cash') AS CHAR(50)) COLLATE utf8mb4_general_ci as payment_mode,
                    CAST(COALESCE(bgt.receipt_no, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as receipt_number,
                    CAST(CONCAT(COALESCE(u.firstname, 'Unknown'), ' ', COALESCE(u.lastname, '')) AS CHAR(255)) COLLATE utf8mb4_general_ci as served_by
                FROM business_group_transactions bgt
                LEFT JOIN business_groups bg ON bgt.group_id = bg.group_id
                LEFT JOIN user u ON bgt.served_by = u.user_id
                WHERE DATE(bgt.date) BETWEEN ? AND ?
                AND bgt.type = 'Withdrawal Fee'
            ";
            $params = array_merge($params, [$this->start_date, $this->end_date]);
            $types .= 'ss';
        }
        
        // Income/Receipts
        if ($this->category === 'all' || $this->category === 'income_receipts') {
            $query_parts[] = "
                SELECT 
                    CAST('Income/Receipts' AS CHAR(50)) COLLATE utf8mb4_general_ci as source_type,
                    CAST(COALESCE(e.category, 'Other Income') AS CHAR(255)) COLLATE utf8mb4_general_ci as source_name,
                    CAST(COALESCE(e.receipt_no, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as reference_no,
                    e.amount,
                    e.date as transaction_date,
                    CAST(COALESCE(e.payment_method, 'Cash') AS CHAR(50)) COLLATE utf8mb4_general_ci as payment_mode,
                    CAST(COALESCE(e.receipt_no, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as receipt_number,
                    CAST(CONCAT(COALESCE(u.firstname, 'Unknown'), ' ', COALESCE(u.lastname, '')) AS CHAR(255)) COLLATE utf8mb4_general_ci as served_by
                FROM expenses e
                LEFT JOIN user u ON e.created_by = u.user_id
                WHERE DATE(e.date) BETWEEN ? AND ?
                AND e.status = 'received'
            ";
            $params = array_merge($params, [$this->start_date, $this->end_date]);
            $types .= 'ss';
        }
        
        // If no query parts, return empty
        if (empty($query_parts)) {
            return [];
        }
        
        // Combine all parts with UNION ALL
        $final_query = implode(' UNION ALL ', $query_parts) . " ORDER BY transaction_date DESC";
        
        $stmt = $this->db->conn->prepare($final_query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function render() {
        $income_data = $this->getDetailedIncomeData();
        $total_income = array_sum(array_column($income_data, 'amount'));
        
        ob_start();
        ?>
        <!-- Detailed Income Table -->
        <div class="col-xl-12 col-lg-12">
            <div class="card mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">
                        Detailed Income Tracking
                        <?php if ($this->category !== 'all'): ?>
                            - <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $this->category))); ?>
                        <?php endif; ?>
                    </h6>
                    <span class="badge badge-success">Total: KSh <?php echo number_format($total_income, 2); ?></span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="incomeTable" width="100%" cellspacing="0">
                            <thead class="bg-success text-white">
                                <tr>
                                    <th>Date</th>
                                    <th>Source Type</th>
                                    <th>Client/Group Name</th>
                                    <th>Reference No</th>
                                    <th>Amount</th>
                                    <th>Payment Mode</th>
                                    <th>Receipt No</th>
                                    <th>Served By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($income_data)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3"></i><br>
                                            No income records found for the selected criteria.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($income_data as $income): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($income['transaction_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo match($income['source_type']) {
                                                        'Individual Savings' => 'primary',
                                                        'Loan Repayments' => 'info',
                                                        'Group Savings' => 'secondary',
                                                        'Business Group Savings' => 'dark',
                                                        'Income/Receipts' => 'success',
                                                        default => 'warning'
                                                    }; ?>">
                                                    <?php echo htmlspecialchars($income['source_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($income['source_name']); ?></td>
                                            <td><code><?php echo htmlspecialchars($income['reference_no'] ?? 'N/A'); ?></code></td>
                                            <td class="text-right font-weight-bold text-success">
                                                KSh <?php echo number_format($income['amount'], 2); ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-outline-secondary">
                                                    <?php echo htmlspecialchars($income['payment_mode'] ?? 'Cash'); ?>
                                                </span>
                                            </td>
                                            <td><small><?php echo htmlspecialchars($income['receipt_number'] ?? 'N/A'); ?></small></td>
                                            <td><small><?php echo htmlspecialchars($income['served_by']); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function getJavaScript() {
        return "
        // Initialize Income Table with custom pagination
        var incomeTableData = [];
        var incomeCurrentPage = 1;
        var incomeRowsPerPage = 10;
        var incomeFilteredData = [];
        
        function initializeIncomeTable() {
            var table = document.getElementById('incomeTable');
            if (!table) return;
            
            var tbody = table.querySelector('tbody');
            var rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Skip empty state row
            rows = rows.filter(row => !row.querySelector('td[colspan=\"8\"]'));
            
            // Store all rows data
            incomeTableData = rows.map(row => {
                return {
                    element: row,
                    data: Array.from(row.cells).map(cell => cell.textContent.trim())
                };
            });
            
            incomeFilteredData = [...incomeTableData];
            
            // Add search functionality
            addIncomeTableSearch();
            
            // Add pagination
            addIncomeTablePagination();
            
            // Display first page
            displayIncomeTablePage(1);
        }
        
        function addIncomeTableSearch() {
            var searchHtml = '<div class=\"row mb-3\"><div class=\"col-md-6\"><div class=\"input-group\"><input type=\"text\" class=\"form-control\" id=\"incomeTableSearch\" placeholder=\"Search income records...\"><div class=\"input-group-append\"><span class=\"input-group-text\"><i class=\"fas fa-search\"></i></span></div></div></div></div>';
            document.getElementById('incomeTable').insertAdjacentHTML('beforebegin', searchHtml);
            
            document.getElementById('incomeTableSearch').addEventListener('keyup', function() {
                searchIncomeTable(this.value);
            });
        }
        
        function searchIncomeTable(searchTerm) {
            incomeFilteredData = incomeTableData.filter(row => {
                return row.data.some(cell => 
                    cell.toLowerCase().includes(searchTerm.toLowerCase())
                );
            });
            
            incomeCurrentPage = 1;
            displayIncomeTablePage(1);
        }
        
        function displayIncomeTablePage(page) {
            incomeCurrentPage = page;
            var startIndex = (page - 1) * incomeRowsPerPage;
            var endIndex = startIndex + incomeRowsPerPage;
            
            var tbody = document.getElementById('incomeTable').querySelector('tbody');
            tbody.innerHTML = '';
            
            var pageData = incomeFilteredData.slice(startIndex, endIndex);
            
            if (pageData.length === 0 && incomeFilteredData.length === 0) {
                tbody.innerHTML = '<tr><td colspan=\"8\" class=\"text-center text-muted py-4\"><i class=\"fas fa-inbox fa-2x mb-2\"></i><br>No income records found</td></tr>';
            } else {
                pageData.forEach(row => {
                    tbody.appendChild(row.element.cloneNode(true));
                });
            }
            
            updateIncomeTablePagination();
        }
        
        function addIncomeTablePagination() {
            var paginationHtml = '<div class=\"row mt-3\"><div class=\"col-md-12\"><nav><ul class=\"pagination justify-content-center\" id=\"incomeTablePagination\"></ul></nav><div id=\"incomeTableInfo\" class=\"text-center mt-2 text-muted\"></div></div></div>';
            document.getElementById('incomeTable').parentNode.insertAdjacentHTML('afterend', paginationHtml);
        }
        
        function updateIncomeTablePagination() {
            var totalRows = incomeFilteredData.length;
            var totalPages = Math.ceil(totalRows / incomeRowsPerPage);
            var pagination = document.getElementById('incomeTablePagination');
            
            if (!pagination) return;
            
            pagination.innerHTML = '';
            
            if (totalPages <= 1) {
                var infoDiv = document.getElementById('incomeTableInfo');
                if (infoDiv && totalRows > 0) {
                    infoDiv.innerHTML = 'Showing all ' + totalRows + ' entries';
                }
                return;
            }
            
            // Previous button
            var prevLi = document.createElement('li');
            prevLi.className = 'page-item' + (incomeCurrentPage === 1 ? ' disabled' : '');
            var prevA = document.createElement('a');
            prevA.className = 'page-link';
            prevA.href = '#';
            prevA.innerHTML = '&laquo;';
            prevA.style.cursor = 'pointer';
            prevA.onclick = function(e) {
                e.preventDefault();
                if (incomeCurrentPage > 1) {
                    displayIncomeTablePage(incomeCurrentPage - 1);
                }
            };
            prevLi.appendChild(prevA);
            pagination.appendChild(prevLi);
            
            // Page numbers
            var startPage = Math.max(1, incomeCurrentPage - 2);
            var endPage = Math.min(totalPages, incomeCurrentPage + 2);
            
            for (var i = startPage; i <= endPage; i++) {
                var li = document.createElement('li');
                li.className = 'page-item' + (i === incomeCurrentPage ? ' active' : '');
                var a = document.createElement('a');
                a.className = 'page-link';
                a.href = '#';
                a.innerHTML = i;
                a.style.cursor = 'pointer';
                a.onclick = (function(pageNum) {
                    return function(e) {
                        e.preventDefault();
                        displayIncomeTablePage(pageNum);
                    };
                })(i);
                li.appendChild(a);
                pagination.appendChild(li);
            }
            
            // Next button
            var nextLi = document.createElement('li');
            nextLi.className = 'page-item' + (incomeCurrentPage === totalPages ? ' disabled' : '');
            var nextA = document.createElement('a');
            nextA.className = 'page-link';
            nextA.href = '#';
            nextA.innerHTML = '&raquo;';
            nextA.style.cursor = 'pointer';
            nextA.onclick = function(e) {
                e.preventDefault();
                if (incomeCurrentPage < totalPages) {
                    displayIncomeTablePage(incomeCurrentPage + 1);
                }
            };
            nextLi.appendChild(nextA);
            pagination.appendChild(nextLi);
            
            // Update info display
            var startRow = totalRows > 0 ? ((incomeCurrentPage - 1) * incomeRowsPerPage) + 1 : 0;
            var endRow = Math.min(incomeCurrentPage * incomeRowsPerPage, totalRows);
            var infoDiv = document.getElementById('incomeTableInfo');
            if (infoDiv) {
                infoDiv.innerHTML = 'Showing ' + startRow + ' to ' + endRow + ' of ' + totalRows + ' entries';
            }
        }
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeIncomeTable);
        } else {
            initializeIncomeTable();
        }
        ";
    }
}
?>