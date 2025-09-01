<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../config/config.php';
require_once '../controllers/businessGroupController.php';
require_once '../helpers/session.php';
require_once '../config/class.php';
$db = new db_class();
$businessGroupController = new BusinessGroupController();

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: ../views/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Get group ID and details
$groupId = $_GET['id'] ?? null;
$error = null;
$groupDetails = null;
$totalDeposits = 0;
$totalWithdrawals = 0;
$withdrawalFees = 0;
$transactions = [];

if ($groupId) {
    try {
        $groupDetails = $businessGroupController->getBusinessGroupById($groupId);
        if (!$groupDetails) {
            throw new Exception("Business group not found.");
        }
        $totals = $businessGroupController->getTotals($groupId);
        $totalDeposits = $totals['total_deposits'];
        $totalWithdrawals = $totals['total_withdrawals'];
        $totalFees = $totals['total_fees'];
        $netBalance = $totals['net_balance'];
        $transactions = $businessGroupController->getTransactions($groupId);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Group Details - Lato Management System</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #51087E;
            --secondary-color: #f8f9fc;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
            --danger-color: #e74a3b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-color);
            overflow-x: hidden;
        }

        /* Content sections */
        .content-section {
            display: none;
            transition: all 0.3s ease;
        }

        .content-section.active {
            display: block !important;
        }

        /* Ensure container has proper margin for fixed header */
        .container-fluid {
            margin-top: 4.375rem;
            padding: 1.5rem;
        }

        /* Dashboard Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.deposits { border-left-color: var(--success-color); }
        .stat-card.withdrawals { border-left-color: var(--warning-color); }
        .stat-card.fees { border-left-color: var(--danger-color); }
        .stat-card.balance { border-left-color: var(--info-color); }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-card.deposits .stat-card-icon { background: var(--success-color); }
        .stat-card.withdrawals .stat-card-icon { background: var(--warning-color); }
        .stat-card.fees .stat-card-icon { background: var(--danger-color); }
        .stat-card.balance .stat-card-icon { background: var(--info-color); }

        .stat-card-content {
            flex: 1;
            text-align: right;
        }

        .stat-card-title {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card-value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin: 5px 0 0 0;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            border: none;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 20px;
            border-bottom: none;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        /* Tables */
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: background-color 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(81, 8, 126, 0.05);
        }

        .table tbody td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
        }

        /* Buttons */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: #3d065d;
            border-color: #3d065d;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 20px 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                justify-content: center;
            }
        }

        /* Modal Enhancements */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .modal-header {
            border-radius: 12px 12px 0 0;
            border-bottom: none;
        }

        .modal-footer {
            border-top: 1px solid #f1f1f1;
            border-radius: 0 0 12px 12px;
        }

        /* Badge Styles */
        .badge {
            padding: 8px 12px;
            font-size: 0.75rem;
            border-radius: 20px;
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Animation classes */
        .animated--grow-in {
            animation-name: growIn;
            animation-duration: 200ms;
            animation-timing-function: transform cubic-bezier(0.18, 1.25, 0.4, 1), opacity cubic-bezier(0.0, 0.0, 0.4, 1);
        }

        @keyframes growIn {
            0% {
                transform: scale(0.9);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            Error: <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($groupDetails): ?>
        <!-- Include Sidebar Component -->
        <?php include '../components/business_groups/sidebar.php'; ?>

        <!-- Include Dashboard & Information Component -->
        <?php include '../components/business_groups/dashboard_info.php'; ?>

        <!-- Include Transactions Component -->  
        <?php include '../components/business_groups/transactions.php'; ?>

        <!-- Logout Modal -->
        <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger">
                        <h5 class="modal-title text-white">Ready to Leave?</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <a class="btn btn-danger" href="../views/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- End of Content Wrapper -->
        </div>
    </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>
    <script src="../public/js/sb-admin-2.js"></script>

    <script>
$(document).ready(function() {
    // Initialize page content visibility
    console.log('Initializing business group sections...');
    
    // Hide all content sections first
    $('.content-section').hide().removeClass('active');
    
    // Show dashboard section by default
    $('#dashboard-section').addClass('active').show();
    
    // Set dashboard nav as active
    $('.nav-link[data-section="dashboard"]').addClass('active');
    $('.nav-link[data-section="dashboard"]').parent().addClass('active');
    
    console.log('Dashboard section initialized');

    // Initialize DataTable for transactions
    if ($('#transactionsTable').length) {
        $('#transactionsTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']],
            language: {
                search: "Search transactions:",
                lengthMenu: "Show _MENU_ transactions per page",
                info: "Showing _START_ to _END_ of _TOTAL_ transactions"
            }
        });
    }

    // Helper function to show messages
    window.showMessage = function(message, type) {
        var messageDiv = $('<div>')
            .addClass('alert')
            .addClass(type === 'success' ? 'alert-success' : 'alert-danger')
            .text(message)
            .css({
                'position': 'fixed',
                'top': '20px',
                'right': '20px',
                'z-index': 9999,
                'padding': '15px',
                'border-radius': '8px',
                'box-shadow': '0 0 20px rgba(0,0,0,0.2)',
                'max-width': '300px'
            });

        $('body').append(messageDiv);

        setTimeout(function() {
            messageDiv.fadeOut('slow', function() {
                $(this).remove();
            });
        }, 4000);
    };

    // Add Savings Form Handler
    $('#addSavingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        console.log('Submitting savings form...');
        
        const formData = $(this).serialize() + '&action=addSavings&served_by=<?= $_SESSION['user_id'] ?>';
        console.log('Form data:', formData);
        
        $.ajax({
            url: '../controllers/businessGroupController.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                console.log('Success response:', response);
                if (response.status === 'success') {
                    $('#addSavingsModal').modal('hide');
                    showMessage('Savings added successfully', 'success');
                    setTimeout(function() {
                        window.location.href = window.location.href;
                    }, 1500);
                } else {
                    showMessage('Error: ' + (response.message || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                showMessage('Error occurred while processing savings: ' + error, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Withdraw Form Handler
    $('#withdrawForm').on('submit', function(e) {
        e.preventDefault();
        
        const withdrawalAmount = parseFloat($('input[name="amount"]').val());
        const availableBalance = parseFloat($('#availableBalance').val().replace('KSh ', '').replace(/,/g, ''));

        if (withdrawalAmount > availableBalance) {
            showMessage('Withdrawal amount cannot exceed available balance', 'error');
            return;
        }
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        console.log('Submitting withdrawal form...');
        
        const formData = $(this).serialize() + '&action=withdraw&served_by=<?= $_SESSION['user_id'] ?>';
        console.log('Form data:', formData);

        $.ajax({
            url: '../controllers/businessGroupController.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                console.log('Success response:', response);
                if (response.status === 'success') {
                    $('#withdrawModal').modal('hide');
                    showMessage('Withdrawal processed successfully', 'success');
                    setTimeout(function() {
                        window.location.href = window.location.href;
                    }, 1500);
                } else {
                    showMessage('Error: ' + (response.message || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                showMessage('Error occurred while processing withdrawal: ' + error, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Print Statement Handler
    $('#printStatement').click(function() {
        const fromDate = $('input[name="from_date"]').val();
        const toDate = $('input[name="to_date"]').val();

        if (!fromDate || !toDate) {
            showMessage('Please select both start and end dates', 'error');
            return;
        }

        $.ajax({
            url: '../controllers/businessGroupController.php',
            method: 'POST',
            data: {
                action: 'getStatementData',
                group_id: '<?= $groupId ?>',
                from_date: fromDate,
                to_date: toDate
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const statementWindow = window.open('', '_blank');
                    const statementContent = generateStatementHTML(response.data, fromDate, toDate);
                    statementWindow.document.write(statementContent);
                    statementWindow.document.close();
                    setTimeout(() => statementWindow.print(), 500);
                } else {
                    showMessage('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                showMessage('Error generating statement', 'error');
            }
        });
    });

    // Print Receipt Handler
    $(document).on('click', '.print-receipt', function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: '../controllers/businessGroupController.php',
            method: 'POST',
            data: {
                action: 'getReceiptDetails',
                transaction_id: id
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const receiptWindow = window.open('', '_blank');
                    const receiptContent = generateReceiptHTML(response.data);
                    receiptWindow.document.write(receiptContent);
                    receiptWindow.document.close();
                    setTimeout(() => receiptWindow.print(), 500);
                } else {
                    showMessage('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                showMessage('Error generating receipt', 'error');
            }
        });
    });

    // Generate Receipt HTML function
    function generateReceiptHTML(data) {
        const type = data.type.charAt(0).toUpperCase() + data.type.slice(1);
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <title>${type} Receipt</title>
                <style>
                    body { 
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        padding: 20px;
                    }
                    .receipt {
                        max-width: 800px;
                        margin: 0 auto;
                        padding: 20px;
                        border: 1px solid #ddd;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                        border-bottom: 2px solid #333;
                        padding-bottom: 10px;
                    }
                    .details p {
                        margin: 10px 0;
                        border-bottom: 1px solid #eee;
                        padding-bottom: 5px;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 30px;
                        border-top: 2px solid #333;
                        padding-top: 10px;
                    }
                    @media print {
                        body { print-color-adjust: exact; }
                    }
                </style>
            </head>
            <body>
                <div class="receipt">
                    <div class="header">
                        <h2>Lato Sacco LTD</h2>
                        <h3>${type} Receipt</h3>
                    </div>
                    <div class="details">
                        <p><strong>Receipt No:</strong> ${data.receipt_no}</p>
                        <p><strong>Date:</strong> ${new Date(data.date).toLocaleString()}</p>
                        <p><strong>Group Name:</strong> ${data.group_name}</p>
                        <p><strong>Amount:</strong> KSh ${parseFloat(data.amount).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}</p>
                        ${data.withdrawal_fee ? `
                        <p><strong>Withdrawal Fee:</strong> KSh ${parseFloat(data.withdrawal_fee).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}</p>` : ''}
                        <p><strong>Payment Mode:</strong> ${data.payment_mode}</p>
                        <p><strong>Served By:</strong> ${data.served_by_name}</p>
                    </div>
                    <div class="footer">
                        <p>Thank you for your transaction!</p>
                        <p>Printed on: ${new Date().toLocaleString()}</p>
                    </div>
                </div>
            </body>
            </html>
        `;
    }

    // Generate Statement HTML function
    function generateStatementHTML(data, fromDate, toDate) {
        if (!data || !data.transactions) {
            console.error('Invalid data structure:', data);
            return '<p>Error: Invalid data received</p>';
        }

        let transactionsHTML = '';
        let totalDeposits = 0;
        let totalWithdrawals = 0;
        let totalFees = 0;

        data.transactions.forEach(transaction => {
            const amount = parseFloat(transaction.amount);
            if (transaction.type.includes('Savings')) {
                totalDeposits += amount;
            } else if (transaction.type.includes('Withdrawal')) {
                totalWithdrawals += amount;
                totalFees += parseFloat(transaction.withdrawal_fee || 0);
            }

            transactionsHTML += `
                <tr>
                    <td>${new Date(transaction.date).toLocaleDateString()}</td>
                    <td>${transaction.receipt_no}</td>
                    <td>${transaction.type}</td>
                    <td>KSh ${amount.toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}</td>
                    <td>${transaction.payment_mode}</td>
                    <td>${transaction.description}</td>
                </tr>
            `;
        });

        return `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Business Group Statement</title>
                <style>
                    body { 
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        padding: 20px;
                    }
                    .statement {
                        max-width: 1000px;
                        margin: 0 auto;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                        border-bottom: 2px solid #333;
                        padding-bottom: 10px;
                    }
                    .group-info {
                        margin-bottom: 20px;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 20px 0;
                    }
                    th, td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                    }
                    th {
                        background-color: #51087E;
                        color: white;
                    }
                    tr:nth-child(even) {
                        background-color: #f8f9fa;
                    }
                    .summary {
                        margin-top: 20px;
                        border-top: 2px solid #333;
                        padding-top: 10px;
                    }
                    @media print {
                        body { print-color-adjust: exact; }
                        th { background-color: #51087E !important; color: white !important; }
                    }
                </style>
            </head>
            <body>
                <div class="statement">
                    <div class="header">
                        <h2>Lato Sacco LTD</h2>
                        <h3>Business Group Transaction Statement</h3>
                    </div>
                    <div class="group-info">
                        <p><strong>Group Name:</strong> ${data.group_details.group_name}</p>
                        <p><strong>Period:</strong> ${new Date(fromDate).toLocaleDateString()} to ${new Date(toDate).toLocaleDateString()}</p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Receipt No</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Payment Mode</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${transactionsHTML}
                        </tbody>
                    </table>
                    <div class="summary">
                        <h4>Summary</h4>
                        <p><strong>Total Deposits:</strong> KSh ${totalDeposits.toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}</p>
                        <p><strong>Total Withdrawals:</strong> KSh ${totalWithdrawals.toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}</p>
                        <p><strong>Total Fees:</strong> KSh ${totalFees.toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}</p>
                        <p><strong>Net Movement:</strong> KSh ${(totalDeposits - totalWithdrawals - totalFees).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}</p>
                    </div>
                    <div class="footer" style="text-align: center; margin-top: 30px;">
                        <p>Generated on: ${new Date().toLocaleString()}</p>
                    </div>
                </div>
            </body>
            </html>
        `;
    }

    // Form validation
    $('input[name="amount"], input[name="withdrawal_fee"]').on('input', function() {
        this.value = this.value.replace(/[^0-9.]/, '');
    });

    // Clear form when modal closes
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
    });

    console.log('Business Group Details page initialized successfully');
});
</script>

</body>
</html>