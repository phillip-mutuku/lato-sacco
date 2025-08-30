<?php
    date_default_timezone_set("Africa/Nairobi");
    require_once '../helpers/session.php';
    require_once '../config/class.php';
    $db = new db_class();

    // Check if user is logged in and is either an admin or manager
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
        $_SESSION['error_msg'] = "Unauthorized access";
        header('Location: index.php');
        exit();
    }

    // Get filter parameters
    $transaction_type = isset($_GET['transaction_type']) ? $_GET['transaction_type'] : 'all';
    $date_range = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

    // Build the base query with dynamic date filtering
    $transactions_query = "
        SELECT 
            e.*,
            ec.category as main_category,
            u.username as created_by_name,
            CASE 
                WHEN e.status = 'received' THEN ABS(e.amount)
                ELSE -ABS(e.amount)
            END as signed_amount
        FROM expenses e 
        LEFT JOIN expenses_categories ec ON e.category = ec.name 
        LEFT JOIN user u ON e.created_by = u.user_id
        WHERE 1=1";

    // Add transaction type filter
    if ($transaction_type !== 'all') {
        if ($transaction_type === 'expenses') {
            $transactions_query .= " AND e.status = 'completed'";
        } elseif ($transaction_type === 'received') {
            $transactions_query .= " AND e.status = 'received'";
        }
    }

    // Add date range filter
    if ($date_range !== 'all') {
        $today = date('Y-m-d');
        switch ($date_range) {
            case 'today':
                $transactions_query .= " AND DATE(e.date) = '$today'";
                break;
            case 'week':
                $week_start = date('Y-m-d', strtotime('-1 week'));
                $transactions_query .= " AND DATE(e.date) >= '$week_start'";
                break;
            case 'month':
                $month_start = date('Y-m-d', strtotime('first day of this month'));
                $transactions_query .= " AND DATE(e.date) >= '$month_start'";
                break;
            case 'year':
                $year_start = date('Y-m-d', strtotime('first day of january this year'));
                $transactions_query .= " AND DATE(e.date) >= '$year_start'";
                break;
            case 'custom':
                if ($start_date && $end_date) {
                    $transactions_query .= " AND DATE(e.date) BETWEEN '$start_date' AND '$end_date'";
                }
                break;
        }
    }

    $transactions_query .= " ORDER BY e.date DESC, e.created_at DESC";

    try {
        $transactions = $db->conn->query($transactions_query);
        if ($transactions === false) {
            throw new Exception("Query failed: " . $db->conn->error);
        }
    } catch (Exception $e) {
        error_log("Error fetching transactions: " . $e->getMessage());
        $transactions = null;
    }

    // Calculate totals for the filtered data
    $total_expenses = 0;
    $total_received = 0;
    $category_totals = [];
    
    if ($transactions && $transactions->num_rows > 0) {
        $data = $transactions->fetch_all(MYSQLI_ASSOC);
        foreach ($data as $row) {
            if ($row['status'] === 'received') {
                $total_received += $row['amount'];
            } else {
                $total_expenses += abs($row['amount']);
            }
            
            // Track category totals
            $category = $row['main_category'];
            if (!isset($category_totals[$category])) {
                $category_totals[$category] = 0;
            }
            $category_totals[$category] += $row['signed_amount'];
        }
        // Reset pointer for later use
        $transactions->data_seek(0);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lato Management System - Manage Expenses</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    <style>
        .modal-lg { max-width: 80% !important; }
        .form-group label { font-weight: bold; }
        .expense-details { background-color: #f8f9fc; padding: 15px; border-radius: 5px; }
        .receipt { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; }
        @media print { .no-print { display: none; } }
        
        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        .container-fluid .card{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }

        .modal-body .form-group label {
            font-weight: 600;
            color: #4e73df;
        }

        .text-danger {
            color: #e74a3b !important;
        }
        
        .filter-section {
            background-color: #f8f9fc;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .summary-card {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            box-shadow: rgb(204, 219, 232) 3px 3px 6px 0px inset, rgba(255, 255, 255, 0.5) -3px -3px 6px 1px inset;
        }
        
        .date-range-picker {
            display: none;
        }
        
        .date-range-picker.active {
            display: block;
        }

        @media print {
            .statement-print-area {
                display: block;
                width: 100%;
                padding: 20px;
            }
            .summary-table, .category-table, .transaction-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .summary-table td, .category-table td, 
            .transaction-table th, .transaction-table td {
                padding: 8px;
                border: 1px solid #ddd;
            }
            .transaction-table th {
                background-color: #f8f9fc !important;
                -webkit-print-color-adjust: exact;
            }
            .text-success { color: #28a745 !important; }
            .text-danger { color: #dc3545 !important; }
            .no-print { display: none !important; }
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #51087E;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Import Sidebar -->
        <?php require_once '../components/includes/sidebar.php'; ?>

        <div class="container-fluid pt-4">
            
            <!-- Stats Section Component -->
            <?php require_once '../components/manage_expenses/stats_section.php'; ?>

            <!-- Transactions Table Component -->
            <?php require_once '../components/manage_expenses/transactions_table.php'; ?>

        </div>
        
        <!-- Footer -->
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; Lato Management System <?php echo date("Y")?></span>
                </div>
            </div>
        </footer>
        <!-- End of Footer -->

    </div>

    <!-- Modals Section Component -->
    <?php require_once '../components/manage_expenses/modals_section.php'; ?>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Scripts -->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>
    <script src="../public/js/jquery.easing.js"></script>
    <script src="../public/js/sb-admin-2.js"></script>
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>

    <script>
$(document).ready(function() {
    
    // Toast Notification System
    window.showToast = function(message, type = 'success') {
        const toastContainer = document.getElementById('toastContainer');
        const toastId = 'toast_' + Date.now();
        
        const toastHtml = `
            <div class="toast ${type}" id="${toastId}">
                <div class="toast-header">
                    <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                    <button type="button" class="toast-close" onclick="hideToast('${toastId}')">&times;</button>
                </div>
                <div class="toast-body">${message}</div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        // Show toast
        setTimeout(() => {
            document.getElementById(toastId).classList.add('show');
        }, 100);
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            hideToast(toastId);
        }, 5000);
    };

    window.hideToast = function(toastId) {
        const toast = document.getElementById(toastId);
        if (toast) {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }
    };

    // Enhanced Select Component
    class EnhancedSelect {
        constructor(container) {
            this.container = container;
            this.select = container.querySelector('select');
            this.dropdown = container.querySelector('.dropdown-container');
            this.searchInput = container.querySelector('.search-input');
            this.optionsContainer = container.querySelector('.options-container');
            this.isOpen = false;
            this.options = Array.from(this.select.options).filter(opt => opt.value !== '');
            
            this.init();
        }
        
        init() {
            this.buildOptions();
            this.bindEvents();
        }
        
        buildOptions() {
            this.optionsContainer.innerHTML = '';
            this.options.forEach(option => {
                const optionDiv = document.createElement('div');
                optionDiv.className = 'option-item';
                optionDiv.textContent = option.text;
                optionDiv.dataset.value = option.value;
                optionDiv.addEventListener('click', () => this.selectOption(option.value, option.text));
                this.optionsContainer.appendChild(optionDiv);
            });
        }
        
        bindEvents() {
            // Click on select to toggle dropdown
            this.select.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
            });
            
            // Search functionality
            this.searchInput.addEventListener('input', (e) => {
                this.filterOptions(e.target.value);
            });
            
            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!this.container.contains(e.target)) {
                    this.close();
                }
            });
            
            // Prevent dropdown from closing when clicking inside
            this.dropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
        
        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }
        
        open() {
            // Close other open dropdowns
            document.querySelectorAll('.enhanced-select .dropdown-container.show').forEach(dropdown => {
                if (dropdown !== this.dropdown) {
                    dropdown.classList.remove('show');
                }
            });
            
            this.isOpen = true;
            this.dropdown.classList.add('show');
            this.searchInput.value = '';
            this.searchInput.focus();
            this.filterOptions('');
        }
        
        close() {
            this.isOpen = false;
            this.dropdown.classList.remove('show');
        }
        
        selectOption(value, text) {
            this.select.value = value;
            this.select.dispatchEvent(new Event('change', { bubbles: true }));
            this.close();
            
            // Remove validation error if present
            this.select.classList.remove('is-invalid');
        }
        
        filterOptions(searchTerm) {
            const term = searchTerm.toLowerCase();
            this.optionsContainer.querySelectorAll('.option-item').forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(term) ? 'block' : 'none';
            });
        }
        
        reset() {
            this.select.selectedIndex = 0;
            this.close();
        }
    }
    
    // Initialize enhanced selects
    function initializeEnhancedSelects() {
        document.querySelectorAll('.enhanced-select').forEach(container => {
            if (!container.enhancedSelect) {
                container.enhancedSelect = new EnhancedSelect(container);
            }
        });
    }
    
    // Initialize on page load
    initializeEnhancedSelects();

    // Initialize DataTable
    $('#transactionTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']],
        responsive: true,
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: {
            paginate: {
                previous: '<i class="fas fa-chevron-left"></i>',
                next: '<i class="fas fa-chevron-right"></i>'
            }
        }
    });

    // Handle date range picker visibility
    $('#dateRangeSelect').change(function() {
        if ($(this).val() === 'custom') {
            $('.date-range-picker').addClass('active');
        } else {
            $('.date-range-picker').removeClass('active');
        }
    });

    // Form validation and submission - Simplified
    $('#expenseForm, #receivedForm').on('submit', function(e) {
        const form = $(this);
        let isValid = true;
        
        // Simple validation - check required fields
        form.find('[required]').each(function() {
            if (!$(this).val() || $(this).val() === '') {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            alert('Please fill in all required fields');
            e.preventDefault();
            return false;
        }
        
        // If validation passes, let the form submit normally
        // Don't preventDefault here - let it go to the server
    });

    // Clear validation on input change
    $('.form-control').on('change keyup', function() {
        $(this).removeClass('is-invalid');
    });

    // Print receipt handler
    $('.print-receipt').on('click', function() {
        const transaction = $(this).data('transaction');
        const isReceived = transaction.status === 'received';
        const amount = parseFloat(transaction.amount);
        const formattedAmount = amount.toLocaleString(undefined, {minimumFractionDigits: 2});
        
        const receiptHtml = `
            <div class="receipt-header text-center">
                <h4>LATO SACCO LTD</h4>
                <p>${isReceived ? 'Money Received' : 'Expense'} Receipt</p>
                <hr>
            </div>
            <div class="receipt-body">
                <p><strong>Receipt No:</strong> ${transaction.receipt_no}</p>
                <p><strong>Date:</strong> ${new Date(transaction.date).toLocaleString()}</p>
                <p><strong>Category:</strong> ${transaction.main_category}</p>
                <p><strong>Expense Name:</strong> ${transaction.expense_name || transaction.category}</p>
                <p><strong>Description:</strong> ${transaction.description || 'N/A'}</p>
                <p><strong>Amount:</strong> KSh ${formattedAmount}</p>
                <p><strong>Payment Method:</strong> ${transaction.payment_method}</p>
                <p><strong>Status:</strong> ${isReceived ? 'Received' : 'Expense'}</p>
                <p><strong>Remarks:</strong> ${transaction.remarks || 'N/A'}</p>
                <p><strong>Created By:</strong> ${transaction.created_by_name}</p>
            </div>
            <div class="receipt-footer mt-4 pt-2 text-center" style="border-top: 1px solid #ddd;">
                <p class="mb-1">Generated on ${new Date().toLocaleString()}</p>
                <p class="mb-0">Thank you for your business</p>
            </div>
        `;
        
        $('#receiptContent').html(receiptHtml);
        $('#receiptModal').modal('show');
    });

    // Delete transaction handler - Updated to use modal
    let deleteTransactionId = null;
    $('.delete-transaction').on('click', function(e) {
        e.preventDefault();
        deleteTransactionId = $(this).data('id');
        const receiptNo = $(this).data('receipt-no');
        
        $('#deleteReceiptNo').text(receiptNo);
        $('#deleteConfirmModal').modal('show');
    });

    // Handle actual deletion when confirmed
    $('#confirmDeleteBtn').on('click', function() {
        if (deleteTransactionId) {
            $('#deleteConfirmModal').modal('hide');
            $('body').append('<div class="loading-overlay"><div class="spinner"></div></div>');
            
            $.ajax({
                url: '../controllers/delete_transaction.php',
                method: 'POST',
                data: { transaction_id: deleteTransactionId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Transaction deleted successfully', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast('Error: ' + (response.error || 'Failed to delete transaction'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete Error:', xhr.responseText);
                    showToast('Error: Failed to delete transaction', 'error');
                },
                complete: function() {
                    $('.loading-overlay').remove();
                    deleteTransactionId = null;
                }
            });
        }
    });

    // Print receipt function
    window.printReceipt = function() {
        const printWindow = window.open('', '_blank');
        const printContent = document.getElementById('receiptContent').innerHTML;
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Print Receipt</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .receipt { max-width: 400px; margin: 20px auto; padding: 20px; }
                    .text-center { text-align: center; }
                    hr { border: 1px solid #ddd; }
                </style>
            </head>
            <body>
                <div class="receipt">
                    ${printContent}
                </div>
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    };

    // Generate report function
    window.generateReport = function() {
        $('body').append('<div class="loading-overlay"><div class="spinner"></div></div>');
        
        const transaction_type = $('select[name="transaction_type"]').val();
        const date_range = $('#dateRangeSelect').val();
        const start_date = $('input[name="start_date"]').val();
        const end_date = $('input[name="end_date"]').val();

        if (date_range === 'custom' && (!start_date || !end_date)) {
            $('.loading-overlay').remove();
            showToast('Please select both start and end dates for custom range', 'error');
            return;
        }
        
        const params = new URLSearchParams({
            transaction_type: transaction_type,
            date_range: date_range,
            start_date: start_date || '',
            end_date: end_date || ''
        });

        window.location.href = '../controllers/generate_expenses_report.php?' + params.toString();
        
        setTimeout(() => {
            $('.loading-overlay').remove();
        }, 2000);
    };

    // Initialize modals - Enhanced
    $('#addExpenseModal, #addReceivedModal').on('show.bs.modal', function() {
        $(this).find('form')[0].reset();
        $(this).find('.is-invalid').removeClass('is-invalid');
        
        // Re-initialize enhanced selects
        setTimeout(() => {
            initializeEnhancedSelects();
        }, 100);
    });

    // Show PHP session messages as toasts
    <?php if (isset($_SESSION['success_msg'])): ?>
    setTimeout(() => {
        showToast('<?php echo addslashes($_SESSION['success_msg']); ?>', 'success');
    }, 500);
    <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_msg'])): ?>
    setTimeout(() => {
        showToast('<?php echo addslashes($_SESSION['error_msg']); ?>', 'error');
    }, 500);
    <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>

});
    </script>

</body>
</html>