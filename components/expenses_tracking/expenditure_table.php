<?php
// components/expenses_tracking/expenditure_table.php

class ExpenditureTable {
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
    
    private function getDetailedExpenditureData() {
        // Skip if transaction type is income only
        if ($this->transaction_type === 'income_only') {
            return [];
        }
        
        $query_parts = [];
        $params = [];
        $types = '';
        
        // Business Expenses
        if ($this->category === 'all' || $this->category === 'business_expenses') {
            $query_parts[] = "
                SELECT 
                    CAST('Business Expenses' AS CHAR(50)) COLLATE utf8mb4_general_ci as expense_type,
                    CAST(e.category AS CHAR(100)) COLLATE utf8mb4_general_ci as expense_category,
                    CAST(COALESCE(e.description, 'No description') AS CHAR(500)) COLLATE utf8mb4_general_ci as expense_description,
                    CAST(COALESCE(e.reference_no, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as reference_no,
                    ABS(e.amount) as amount,
                    e.date as transaction_date,
                    CAST(COALESCE(e.payment_method, 'Cash') AS CHAR(50)) COLLATE utf8mb4_general_ci as payment_mode,
                    CAST(COALESCE(e.receipt_no, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as receipt_no,
                    CAST(CONCAT(u.firstname, ' ', u.lastname) AS CHAR(255)) COLLATE utf8mb4_general_ci as created_by,
                    CAST(e.status AS CHAR(20)) COLLATE utf8mb4_general_ci as status,
                    CAST(COALESCE(e.remarks, 'Business expense') AS CHAR(500)) COLLATE utf8mb4_general_ci as remarks
                FROM expenses e
                JOIN user u ON e.created_by = u.user_id
                WHERE DATE(e.date) BETWEEN ? AND ?
                AND e.status = 'completed'
            ";
            $params = array_merge($params, [$this->start_date, $this->end_date]);
            $types .= 'ss';
        }
        
        // Loan Disbursements
        if ($this->category === 'all' || $this->category === 'loan_disbursements') {
            $query_parts[] = "
                SELECT 
                    CAST('Loan Disbursements' AS CHAR(50)) COLLATE utf8mb4_general_ci as expense_type,
                    CAST('Financial Operations' AS CHAR(100)) COLLATE utf8mb4_general_ci as expense_category,
                    CAST(COALESCE(
                        t.description, 
                        CONCAT('Loan disbursement to ', ca.first_name, ' ', ca.last_name),
                        'Loan disbursement'
                    ) AS CHAR(500)) COLLATE utf8mb4_general_ci as expense_description,
                    CAST(COALESCE(t.receipt_no, t.receipt_number, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as reference_no,
                    t.amount,
                    t.date as transaction_date,
                    CAST(COALESCE(t.payment_mode, 'Cash') AS CHAR(50)) COLLATE utf8mb4_general_ci as payment_mode,
                    CAST(COALESCE(t.receipt_no, t.receipt_number, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as receipt_no,
                    CAST(COALESCE(t.served_by, 'System') AS CHAR(255)) COLLATE utf8mb4_general_ci as created_by,
                    CAST('Completed' AS CHAR(20)) COLLATE utf8mb4_general_ci as status,
                    CAST('Loan disbursement to client' AS CHAR(500)) COLLATE utf8mb4_general_ci as remarks
                FROM transactions t
                LEFT JOIN client_accounts ca ON t.account_id = ca.account_id
                WHERE DATE(t.date) BETWEEN ? AND ?
                AND t.type = 'Loan Disbursement'
            ";
            $params = array_merge($params, [$this->start_date, $this->end_date]);
            $types .= 'ss';
        }
        
        // Group Withdrawals
        if ($this->category === 'all' || $this->category === 'group_withdrawals') {
            $query_parts[] = "
                SELECT 
                    CAST('Group Withdrawals' AS CHAR(50)) COLLATE utf8mb4_general_ci as expense_type,
                    CAST('Client Services' AS CHAR(100)) COLLATE utf8mb4_general_ci as expense_category,
                    CAST(CONCAT('Group withdrawal for ', lg.group_name, ' - ', ca.first_name, ' ', ca.last_name) AS CHAR(500)) COLLATE utf8mb4_general_ci as expense_description,
                    CAST(lg.group_reference AS CHAR(50)) COLLATE utf8mb4_general_ci as reference_no,
                    gw.amount,
                    gw.date_withdrawn as transaction_date,
                    CAST(COALESCE(gw.payment_mode, 'Cash') AS CHAR(50)) COLLATE utf8mb4_general_ci as payment_mode,
                    CAST(COALESCE(gw.receipt_no, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as receipt_no,
                    CAST(CONCAT(u.firstname, ' ', u.lastname) AS CHAR(255)) COLLATE utf8mb4_general_ci as created_by,
                    CAST('Completed' AS CHAR(20)) COLLATE utf8mb4_general_ci as status,
                    CAST('Group member withdrawal' AS CHAR(500)) COLLATE utf8mb4_general_ci as remarks
                FROM group_withdrawals gw
                JOIN lato_groups lg ON gw.group_id = lg.group_id
                JOIN client_accounts ca ON gw.account_id = ca.account_id
                JOIN user u ON gw.served_by = u.user_id
                WHERE DATE(gw.date_withdrawn) BETWEEN ? AND ?
            ";
            $params = array_merge($params, [$this->start_date, $this->end_date]);
            $types .= 'ss';
        }
        
        // Individual Withdrawals
        if ($this->category === 'all' || $this->category === 'individual_withdrawals') {
            $query_parts[] = "
                SELECT 
                    CAST('Individual Withdrawals' AS CHAR(50)) COLLATE utf8mb4_general_ci as expense_type,
                    CAST('Client Services' AS CHAR(100)) COLLATE utf8mb4_general_ci as expense_category,
                    CAST(CONCAT('Individual withdrawal for ', ca.first_name, ' ', ca.last_name) AS CHAR(500)) COLLATE utf8mb4_general_ci as expense_description,
                    CAST(ca.shareholder_no AS CHAR(50)) COLLATE utf8mb4_general_ci as reference_no,
                    s.amount,
                    s.date as transaction_date,
                    CAST(COALESCE(s.payment_mode, 'Cash') AS CHAR(50)) COLLATE utf8mb4_general_ci as payment_mode,
                    CAST(COALESCE(s.receipt_number, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as receipt_no,
                    CAST(COALESCE(s.served_by, 'Unknown') AS CHAR(255)) COLLATE utf8mb4_general_ci as created_by,
                    CAST('Completed' AS CHAR(20)) COLLATE utf8mb4_general_ci as status,
                    CAST('Individual client withdrawal' AS CHAR(500)) COLLATE utf8mb4_general_ci as remarks
                FROM savings s
                JOIN client_accounts ca ON s.account_id = ca.account_id
                WHERE DATE(s.date) BETWEEN ? AND ?
                AND s.type = 'Withdrawal'
            ";
            $params = array_merge($params, [$this->start_date, $this->end_date]);
            $types .= 'ss';
        }
        
        // Business Group Withdrawals
        if ($this->category === 'all' || $this->category === 'business_group_withdrawals') {
            $query_parts[] = "
                SELECT 
                    CAST('Business Group Withdrawals' AS CHAR(50)) COLLATE utf8mb4_general_ci as expense_type,
                    CAST('Business Operations' AS CHAR(100)) COLLATE utf8mb4_general_ci as expense_category,
                    CAST(CONCAT('Business withdrawal for ', bg.group_name) AS CHAR(500)) COLLATE utf8mb4_general_ci as expense_description,
                    CAST(COALESCE(bg.reference_name, CAST(bg.account_id AS CHAR)) AS CHAR(50)) COLLATE utf8mb4_general_ci as reference_no,
                    bgt.amount,
                    bgt.date as transaction_date,
                    CAST(COALESCE(bgt.payment_mode, 'Cash') AS CHAR(50)) COLLATE utf8mb4_general_ci as payment_mode,
                    CAST(COALESCE(bgt.receipt_no, 'N/A') AS CHAR(50)) COLLATE utf8mb4_general_ci as receipt_no,
                    CAST(CONCAT(u.firstname, ' ', u.lastname) AS CHAR(255)) COLLATE utf8mb4_general_ci as created_by,
                    CAST('Completed' AS CHAR(20)) COLLATE utf8mb4_general_ci as status,
                    CAST(COALESCE(bgt.description, 'Business group withdrawal') AS CHAR(500)) COLLATE utf8mb4_general_ci as remarks
                FROM business_group_transactions bgt
                JOIN business_groups bg ON bgt.group_id = bg.group_id
                JOIN user u ON bgt.served_by = u.user_id
                WHERE DATE(bgt.date) BETWEEN ? AND ?
                AND bgt.type = 'Withdrawal'
            ";
            $params = array_merge($params, [$this->start_date, $this->end_date]);
            $types .= 'ss';
        }
        
        // Float Management
        if ($this->category === 'all' || $this->category === 'float_management') {
            $query_parts[] = "
                SELECT 
                    CAST('Float Management' AS CHAR(50)) COLLATE utf8mb4_general_ci as expense_type,
                    CAST('System Operations' AS CHAR(100)) COLLATE utf8mb4_general_ci as expense_category,
                    CAST(CASE 
                        WHEN fm.type = 'offload' THEN 'Cash float offload'
                        ELSE 'Cash float addition'
                    END AS CHAR(500)) COLLATE utf8mb4_general_ci as expense_description,
                    CAST(fm.receipt_no AS CHAR(50)) COLLATE utf8mb4_general_ci as reference_no,
                    fm.amount,
                    fm.date_created as transaction_date,
                    CAST('Cash' AS CHAR(50)) COLLATE utf8mb4_general_ci as payment_mode,
                    CAST(fm.receipt_no AS CHAR(50)) COLLATE utf8mb4_general_ci as receipt_no,
                    CAST(CONCAT(u.firstname, ' ', u.lastname) AS CHAR(255)) COLLATE utf8mb4_general_ci as created_by,
                    CAST('Completed' AS CHAR(20)) COLLATE utf8mb4_general_ci as status,
                    CAST('Float management operation' AS CHAR(500)) COLLATE utf8mb4_general_ci as remarks
                FROM float_management fm
                JOIN user u ON fm.user_id = u.user_id
                WHERE DATE(fm.date_created) BETWEEN ? AND ?
                AND fm.type = 'offload'
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
        $expenditure_data = $this->getDetailedExpenditureData();
        $total_expenditure = array_sum(array_column($expenditure_data, 'amount'));
        
        ob_start();
        ?>
        <!-- Detailed Expenditure Table -->
        <div class="col-xl-12 col-lg-12">
            <div class="card mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-danger">
                        Detailed Expenditure Tracking
                        <?php if ($this->category !== 'all'): ?>
                            - <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $this->category))); ?>
                        <?php endif; ?>
                    </h6>
                    <span class="badge badge-danger">Total: KSh <?php echo number_format($total_expenditure, 2); ?></span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="expenditureTable" width="100%" cellspacing="0">
                            <thead class="bg-danger text-white">
                                <tr>
                                    <th>Date</th>
                                    <th>Expense Type</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Reference No</th>
                                    <th>Amount</th>
                                    <th>Payment Mode</th>
                                    <th>Receipt No</th>
                                    <th>Created By</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($expenditure_data)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3"></i><br>
                                            No expenditure records found for the selected criteria.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($expenditure_data as $expense): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($expense['transaction_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo match($expense['expense_type']) {
                                                        'Business Expenses' => 'primary',
                                                        'Loan Disbursements' => 'info',
                                                        'Group Withdrawals' => 'secondary',
                                                        'Individual Withdrawals' => 'success',
                                                        'Business Group Withdrawals' => 'dark',
                                                        'Float Management' => 'warning',
                                                        default => 'light'
                                                    }; ?>">
                                                    <?php echo htmlspecialchars($expense['expense_type']); ?>
                                                </span>
                                            </td>
                                            <td><small><?php echo htmlspecialchars($expense['expense_category']); ?></small></td>
                                            <td class="text-truncate" style="max-width: 200px;" 
                                                title="<?php echo htmlspecialchars($expense['expense_description']); ?>">
                                                <?php echo htmlspecialchars(substr($expense['expense_description'], 0, 40) . (strlen($expense['expense_description']) > 40 ? '...' : '')); ?>
                                            </td>
                                            <td><code><?php echo htmlspecialchars($expense['reference_no'] ?? 'N/A'); ?></code></td>
                                            <td class="text-right font-weight-bold text-danger">
                                                KSh <?php echo number_format($expense['amount'], 2); ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-outline-secondary">
                                                    <?php echo htmlspecialchars($expense['payment_mode'] ?? 'Cash'); ?>
                                                </span>
                                            </td>
                                            <td><small><?php echo htmlspecialchars($expense['receipt_no'] ?? 'N/A'); ?></small></td>
                                            <td><small><?php echo htmlspecialchars($expense['created_by']); ?></small></td>
                                            <td>
                                                <span class="badge badge-<?php echo $expense['status'] === 'Completed' ? 'success' : 'warning'; ?>">
                                                    <?php echo htmlspecialchars($expense['status']); ?>
                                                </span>
                                            </td>
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
        // Initialize Expenditure Table with custom pagination
        var expenditureTableData = [];
        var expenditureCurrentPage = 1;
        var expenditureRowsPerPage = 10;
        var expenditureFilteredData = [];
        
        function initializeExpenditureTable() {
            var table = document.getElementById('expenditureTable');
            if (!table) return;
            
            var tbody = table.querySelector('tbody');
            var rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Skip empty state row
            rows = rows.filter(row => !row.querySelector('td[colspan=\"10\"]'));
            
            // Store all rows data
            expenditureTableData = rows.map(row => {
                return {
                    element: row,
                    data: Array.from(row.cells).map(cell => cell.textContent.trim())
                };
            });
            
            expenditureFilteredData = [...expenditureTableData];
            
            // Add search functionality
            addExpenditureTableSearch();
            
            // Add pagination
            addExpenditureTablePagination();
            
            // Display first page
            displayExpenditureTablePage(1);
        }
        
        function addExpenditureTableSearch() {
            var searchHtml = '<div class=\"row mb-3\"><div class=\"col-md-6\"><div class=\"input-group\"><input type=\"text\" class=\"form-control\" id=\"expenditureTableSearch\" placeholder=\"Search expenditure records...\"><div class=\"input-group-append\"><span class=\"input-group-text\"><i class=\"fas fa-search\"></i></span></div></div></div></div>';
            document.getElementById('expenditureTable').insertAdjacentHTML('beforebegin', searchHtml);
            
            document.getElementById('expenditureTableSearch').addEventListener('keyup', function() {
                searchExpenditureTable(this.value);
            });
        }
        
        function searchExpenditureTable(searchTerm) {
            expenditureFilteredData = expenditureTableData.filter(row => {
                return row.data.some(cell => 
                    cell.toLowerCase().includes(searchTerm.toLowerCase())
                );
            });
            
            expenditureCurrentPage = 1;
            displayExpenditureTablePage(1);
        }
        
        function displayExpenditureTablePage(page) {
            expenditureCurrentPage = page;
            var startIndex = (page - 1) * expenditureRowsPerPage;
            var endIndex = startIndex + expenditureRowsPerPage;
            
            var tbody = document.getElementById('expenditureTable').querySelector('tbody');
            tbody.innerHTML = '';
            
            var pageData = expenditureFilteredData.slice(startIndex, endIndex);
            
            if (pageData.length === 0 && expenditureFilteredData.length === 0) {
                tbody.innerHTML = '<tr><td colspan=\"10\" class=\"text-center text-muted py-4\"><i class=\"fas fa-inbox fa-2x mb-2\"></i><br>No expenditure records found</td></tr>';
            } else {
                pageData.forEach(row => {
                    tbody.appendChild(row.element.cloneNode(true));
                });
            }
            
            updateExpenditureTablePagination();
        }
        
        function addExpenditureTablePagination() {
            var paginationHtml = '<div class=\"row mt-3\"><div class=\"col-md-12\"><nav><ul class=\"pagination justify-content-center\" id=\"expenditureTablePagination\"></ul></nav><div id=\"expenditureTableInfo\" class=\"text-center mt-2 text-muted\"></div></div></div>';
            document.getElementById('expenditureTable').parentNode.insertAdjacentHTML('afterend', paginationHtml);
        }
        
        function updateExpenditureTablePagination() {
            var totalRows = expenditureFilteredData.length;
            var totalPages = Math.ceil(totalRows / expenditureRowsPerPage);
            var pagination = document.getElementById('expenditureTablePagination');
            pagination.innerHTML = '';
            
            if (totalPages <= 1) {
                var infoDiv = document.getElementById('expenditureTableInfo');
                if (totalRows > 0) {
                    infoDiv.innerHTML = 'Showing all ' + totalRows + ' entries';
                }
                return;
            }
            
            // Previous button
            var prevLi = document.createElement('li');
            prevLi.className = 'page-item' + (expenditureCurrentPage === 1 ? ' disabled' : '');
            prevLi.innerHTML = '<a class=\"page-link\" href=\"#\" onclick=\"event.preventDefault(); if(' + expenditureCurrentPage + ' > 1) displayExpenditureTablePage(' + (expenditureCurrentPage - 1) + ')\">&laquo;</a>';
            pagination.appendChild(prevLi);
            
            // Page numbers
            var startPage = Math.max(1, expenditureCurrentPage - 2);
            var endPage = Math.min(totalPages, expenditureCurrentPage + 2);
            
            for (var i = startPage; i <= endPage; i++) {
                var li = document.createElement('li');
                li.className = 'page-item' + (i === expenditureCurrentPage ? ' active' : '');
                li.innerHTML = '<a class=\"page-link\" href=\"#\" onclick=\"event.preventDefault(); displayExpenditureTablePage(' + i + ')\">' + i + '</a>';
                pagination.appendChild(li);
            }
            
            // Next button
            var nextLi = document.createElement('li');
            nextLi.className = 'page-item' + (expenditureCurrentPage === totalPages ? ' disabled' : '');
            nextLi.innerHTML = '<a class=\"page-link\" href=\"#\" onclick=\"event.preventDefault(); if(' + expenditureCurrentPage + ' < ' + totalPages + ') displayExpenditureTablePage(' + (expenditureCurrentPage + 1) + ')\">&raquo;</a>';
            pagination.appendChild(nextLi);
            
            // Update info display
            var startRow = totalRows > 0 ? ((expenditureCurrentPage - 1) * expenditureRowsPerPage) + 1 : 0;
            var endRow = Math.min(expenditureCurrentPage * expenditureRowsPerPage, totalRows);
            var infoDiv = document.getElementById('expenditureTableInfo');
            infoDiv.innerHTML = 'Showing ' + startRow + ' to ' + endRow + ' of ' + totalRows + ' entries';
        }
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeExpenditureTable);
        } else {
            initializeExpenditureTable();
        }
        ";
    }
}
?>