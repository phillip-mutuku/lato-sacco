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
    header('Location: index.php');
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
        .summary-card {
            background: linear-gradient(45deg, #51087E, #224abe);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .summary-card:hover {
            transform: translateY(-5px);
        }
        .summary-card h4 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        .summary-card p {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <a style="background-color: #51087E; color: white;" href="business_groups.php" class="btn btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Business Groups
                    </a>
                    
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo $db->user_acc($_SESSION['user_id']); ?>
                                </span>
                                <img class="img-profile rounded-circle" src="../public/image/logo.jpg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($groupDetails): ?>
                        <h1 class="h3 mb-4 text-gray-800"><?php echo htmlspecialchars($groupDetails['group_name']); ?></h1>

                        <!-- Financial Summary -->
                       <!-- Update the Financial Summary section -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="summary-card">
                                        <h4>Total Deposits</h4>
                                        <p>KSh <?php echo number_format($totalDeposits, 2); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="summary-card">
                                        <h4>Total Withdrawals</h4>
                                        <p>KSh <?php echo number_format($totalWithdrawals, 2); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="summary-card">
                                        <h4>Total Withdrawal Fees</h4>
                                        <p>KSh <?php echo number_format($totalFees, 2); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="summary-card">
                                        <h4>Net Balance</h4>
                                        <p>KSh <?php echo number_format($netBalance, 2); ?></p>
                                    </div>
                                </div>
                            </div>


                        <!-- Group Details -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 style="color: #51087E;" class="m-0 font-weight-bold">Group Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6 class="font-weight-bold">Group Name</h6>
                                        <p><?php echo htmlspecialchars($groupDetails['group_name']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="font-weight-bold">Reference Number</h6>
                                        <p><?php echo $groupDetails['reference_name'] ? htmlspecialchars($groupDetails['reference_name']) : '<span class="text-muted">Not assigned</span>'; ?></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6 class="font-weight-bold">Chairperson</h6>
                                        <p>Name: <?php echo htmlspecialchars($groupDetails['chairperson_name']); ?><br>
                                        ID: <?php echo htmlspecialchars($groupDetails['chairperson_id_number']); ?><br>
                                        Phone: <?php echo htmlspecialchars($groupDetails['chairperson_phone']); ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="font-weight-bold">Secretary</h6>
                                        <p>Name: <?php echo htmlspecialchars($groupDetails['secretary_name']); ?><br>
                                        ID: <?php echo htmlspecialchars($groupDetails['secretary_id_number']); ?><br>
                                        Phone: <?php echo htmlspecialchars($groupDetails['secretary_phone']); ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="font-weight-bold">Treasurer</h6>
                                        <p>Name: <?php echo htmlspecialchars($groupDetails['treasurer_name']); ?><br>
                                        ID: <?php echo htmlspecialchars($groupDetails['treasurer_id_number']); ?><br>
                                        Phone: <?php echo htmlspecialchars($groupDetails['treasurer_phone']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>






<!--Transactions history-->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 style="color: #51087E;" class="m-0 font-weight-bold">Transactions History</h5>
                <div class="btn-group">
                    <button class="btn btn-success" data-toggle="modal" data-target="#addSavingsModal">
                        <i class="fas fa-plus"></i> Add Savings
                    </button>
                    <button class="btn btn-warning" data-toggle="modal" data-target="#withdrawModal">
                        <i class="fas fa-minus"></i> Withdraw
                    </button>
                </div>
            </div>


                <!--print statement-->
    <div class="card mb-4">
    <div class="card-header">
    </div>
    <div class="card-body">
        <form id="statementForm" class="row align-items-end">
            <div class="col-md-4">
                <label>From Date</label>
                <input type="date" name="from_date" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label>To Date</label>
                <input type="date" name="to_date" class="form-control" required>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-warning" id="printStatement">
                    <i class="fas fa-print"></i> Print Statement
                </button>
            </div>
        </form>
    </div>
    </div>




            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Receipt No</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo date("Y-m-d H:i", strtotime($transaction['date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $transaction['type'] === 'Savings' ? 'success' : 
                                                ($transaction['type'] === 'Withdrawal' ? 'warning' : 'info'); 
                                        ?>">
                                            <?php echo htmlspecialchars($transaction['type']); ?>
                                        </span>
                                    </td>
                                    <td>KSh <?php echo number_format($transaction['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['receipt_no']); ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm print-receipt" 
                                                data-id="<?php echo $transaction['transaction_id']; ?>"
                                                data-type="<?php echo $transaction['type']; ?>">
                                            <i class="fas fa-print"></i> Print Receipt
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Lato Management System <?php echo date("Y")?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Add Savings Modal -->
    <div class="modal fade" id="addSavingsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: #51087E;">
                    <h5 class="modal-title text-white">Add Savings</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addSavingsForm">
                    <div class="modal-body">
                        <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                        <div class="form-group">
                            <label>Receipt Number</label>
                            <input type="text" name="receipt_no" class="form-control" required>
                            <small class="text-muted">This must be unique</small>
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" name="amount" class="form-control" required step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label>Payment Mode</label>
                            <select name="payment_mode" class="form-control" required>
                                <option value="Cash">Cash</option>
                                <option value="M-Pesa">M-Pesa</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Withdraw Modal -->
    <div class="modal fade" id="withdrawModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: #51087E;">
                    <h5 class="modal-title text-white">Withdraw Funds</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="withdrawForm">
                    <div class="modal-body">
                        <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                        <div class="form-group">
                            <label>Receipt Number</label>
                            <input type="text" name="receipt_no" class="form-control" required>
                            <small class="text-muted">This must be unique</small>
                        </div>
                        <div class="form-group">
                                <label>Available Balance</label>
                                <input type="text" id="availableBalance" class="form-control" readonly 
                                    value="KSh <?php echo number_format($netBalance, 2); ?>">
                            </div>
                        <div class="form-group">
                            <label>Withdrawal Amount</label>
                            <input type="number" name="amount" class="form-control" required step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label>Withdrawal Fee</label>
                            <input type="number" name="withdrawal_fee" class="form-control" required step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label>Payment Mode</label>
                            <select name="payment_mode" class="form-control" required>
                                <option value="Cash">Cash</option>
                                <option value="M-Pesa">M-Pesa</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-warning">Withdraw</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>
    <script src="../public/js/sb-admin-2.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        let transactionsTable = $('#transactionsTable').DataTable({
        order: [[0, 'desc']],
        dom: 'Bfrtip',
        buttons: ['excel', 'pdf']
    });


     // Print Statement Handler
     $('#printStatement').click(function() {
        const fromDate = $('input[name="from_date"]').val();
        const toDate = $('input[name="to_date"]').val();

        if (!fromDate || !toDate) {
            alert('Please select both start and end dates');
            return;
        }

        $.ajax({
            url: '../controllers/businessGroupController.php',
            method: 'POST',
            data: {
                action: 'getStatementData',
                group_id: '<?php echo $groupId; ?>',
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
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error generating statement');
            }
        });
    });




        // Add Savings Form Handler
        $('#addSavingsForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '../controllers/businessGroupController.php',
                method: 'POST',
                data: $(this).serialize() + '&action=addSavings&served_by=<?php echo $_SESSION['user_id']; ?>',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error occurred while processing savings');
                }
            });
        });

        // Withdraw Form Handler
        $('#withdrawForm').on('submit', function(e) {
                e.preventDefault();
                const withdrawalAmount = parseFloat($('input[name="amount"]').val());
                const availableBalance = parseFloat($('#availableBalance').val().replace('KSh ', '').replace(/,/g, ''));

                if (withdrawalAmount > availableBalance) {
                    alert('Withdrawal amount cannot exceed available balance');
                    return;
                }

            $.ajax({
                url: '../controllers/businessGroupController.php',
                method: 'POST',
                data: $(this).serialize() + '&action=withdraw&served_by=<?php echo $_SESSION['user_id']; ?>',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error occurred while processing withdrawal');
                }
            });
        });

        // Print Receipt Handler
        $('.print-receipt').click(function() {
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
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error generating receipt');
        }
    });
});



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



//statement

function generateStatementHTML(data, fromDate, toDate) {
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
                        background-color: #f2f2f2;
                    }
                    .summary {
                        margin-top: 20px;
                        border-top: 2px solid #333;
                        padding-top: 10px;
                    }
                    @media print {
                        .no-print {
                            display: none;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="statement">
                    <div class="header">
                        <h2>Lato Sacco LTD</h2>
                        <h3>Transaction Statement</h3>
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
                    <div class="footer">
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
    });
    </script>
</body>
</html>