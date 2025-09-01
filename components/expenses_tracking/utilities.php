<?php
// components/expenses_tracking/utilities.php

class ExpensesUtilities {
    
    // Validate dates
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    // Initialize filter variables with proper defaults
    public static function initializeFilters() {
        $start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) 
            ? $_GET['start_date'] 
            : date('Y-m-01');

        $end_date = isset($_GET['end_date']) && !empty($_GET['end_date'])
            ? $_GET['end_date']
            : date('Y-m-d');

        $category = isset($_GET['category']) && !empty($_GET['category'])
            ? $_GET['category']
            : 'all';

        $expense_id = isset($_GET['expense_id']) && !empty($_GET['expense_id'])
            ? $_GET['expense_id']
            : null;

        // Validate dates
        if (!self::validateDate($start_date) || !self::validateDate($end_date)) {
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-d');
        }
        
        return [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'category' => $category,
            'expense_id' => $expense_id
        ];
    }
    
    // Get expense categories
    public static function getExpenseCategories($db) {
        $query = "SELECT DISTINCT id, category, name FROM expenses_categories ORDER BY category, name";
        $result = $db->conn->query($query);
        $categories = [];
        while($row = $result->fetch_assoc()) {
            if (!isset($categories[$row['category']])) {
                $categories[$row['category']] = [];
            }
            $categories[$row['category']][] = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }
        return $categories;
    }
    
    // Format currency
    public static function formatCurrency($amount, $currency = 'KSh') {
        return $currency . ' ' . number_format($amount, 2);
    }
    
    // Calculate percentage
    public static function calculatePercentage($part, $whole) {
        if ($whole <= 0) return 0;
        return round(($part / $whole) * 100, 1);
    }
    
    // Generate CSS for styling
    public static function getCustomStyles() {
        return "
        <style>
        .financial-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .financial-card:hover {
            transform: translateY(-5px);
        }
        .income-card {
            border-left: 5px solid #28a745;
        }
        .expenditure-card {
            border-left: 5px solid #dc3545;
        }
        .net-position-card {
            border-left: 5px solid #17a2b8;
        }
        .profit-card {
            border-left: 5px solid #007bff;
        }
        .category-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .filter-section {
            background-color: #f8f9fc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .table-responsive {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .summary-icon {
            font-size: 2rem;
            opacity: 0.7;
        }
        @media print {
            .no-print {
                display: none;
            }
            .print-only {
                display: block;
            }
        }
        html, body {
            overflow-x: hidden;
        }
        </style>
        ";
    }
    
    // Generate JavaScript for DataTables (offline version)
    public static function getDataTablesScript() {
        return "
        <script>
        function initializeDataTables() {
            // Simple table enhancement without external dependencies
            $('.table').each(function() {
                var table = $(this);
                var headers = table.find('th');
                
                // Add sorting functionality
                headers.each(function(index) {
                    var header = $(this);
                    if (header.text().trim() !== '') {
                        header.css('cursor', 'pointer').on('click', function() {
                            sortTable(table, index);
                        });
                    }
                });
            });
        }
        
        function sortTable(table, columnIndex) {
            var tbody = table.find('tbody');
            var rows = tbody.find('tr').not(':last').toArray(); // Exclude footer
            var isAscending = !table.data('sort-asc-' + columnIndex);
            
            rows.sort(function(a, b) {
                var aVal = $(a).find('td').eq(columnIndex).text().trim();
                var bVal = $(b).find('td').eq(columnIndex).text().trim();
                
                // Check if values are numbers
                var aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
                var bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAscending ? aNum - bNum : bNum - aNum;
                }
                
                return isAscending ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            });
            
            tbody.empty().append(rows);
            table.data('sort-asc-' + columnIndex, isAscending);
            
            // Update sort indicators
            table.find('th').removeClass('sorted-asc sorted-desc');
            table.find('th').eq(columnIndex).addClass(isAscending ? 'sorted-asc' : 'sorted-desc');
        }
        
        // Add search functionality
        function addTableSearch(tableId) {
            var searchHtml = '<div class=\"mb-3\"><input type=\"text\" class=\"form-control\" placeholder=\"Search table...\" onkeyup=\"searchTable(this, \\'#' + tableId + '\\')\"></div>';
            $('#' + tableId).before(searchHtml);
        }
        
        function searchTable(input, tableSelector) {
            var filter = input.value.toUpperCase();
            var table = $(tableSelector);
            var rows = table.find('tbody tr').not(':last'); // Exclude footer
            
            rows.each(function() {
                var row = $(this);
                var text = row.text().toUpperCase();
                row.toggle(text.indexOf(filter) > -1);
            });
        }
        </script>
        ";
    }
    
    // Generate print functionality
    public static function getPrintScript() {
        return "
        <script>
        function printReport() {
            // Hide no-print elements
            $('.no-print').hide();
            
            // Create print window
            var printWindow = window.open('', '_blank');
            var printContent = document.documentElement.outerHTML;
            
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.print();
            
            // Show no-print elements again
            $('.no-print').show();
        }
        
        // Alternative print function that works better in some browsers
        function printReportDirect() {
            window.print();
        }
        </script>
        ";
    }
    
    // Security helper - sanitize input
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    // Check user permissions
    public static function checkPermissions($session) {
        if (!isset($session['user_id']) || ($session['role'] !== 'admin' && $session['role'] !== 'superadmin')) {
            $session['error_msg'] = "Unauthorized access";
            header('Location: index.php');
            exit();
        }
        return true;
    }
}
?>