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
    <link href="../public/css/select2.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <style>
        .modal-lg { max-width: 80% !important; }
        .form-group label { font-weight: bold; }
        .expense-details { background-color: #f8f9fc; padding: 15px; border-radius: 5px; }
        .receipt { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; }
        @media print { .no-print { display: none; } }
        html, body {
            overflow-x: hidden;
        }
        #accordionSidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            width: 225px;
            transition: width 0.3s ease;
        }
        #content-wrapper {
            margin-left: 225px;
            width: calc(100% - 225px);
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        .topbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 225px;
            z-index: 1000;
            transition: left 0.3s ease;
        }
        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        @media (max-width: 768px) {
            #accordionSidebar {
                width: 100px;
            }
            #content-wrapper {
                margin-left: 100px;
                width: calc(100% - 100px);
            }
            .topbar {
                left: 100px;
            }
            .sidebar .nav-item .nav-link span {
                display: none;
            }
        }

        .container-fluid .card{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }

        .select2-container--default .select2-selection--single {
        height: calc(1.5em + .75rem + 2px);
        padding: .375rem .75rem;
        border: 1px solid #d1d3e2;
        border-radius: .35rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5;
        padding-left: 0;
        color: #6e707e;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(1.5em + .75rem + 2px);
    }

    .is-invalid + .select2-container .select2-selection--single {
        border-color: #e74a3b;
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
 <!-- Sidebar -->
 <ul style="background: #51087E;"  class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
                <div class="sidebar-brand-text mx-3">LATO SACCO</div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item">
                <a class="nav-link" href="home.php">
                    <i class="fas fa-fw fa-home"></i>
                    <span>Home</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Management
            </div>

            <li class="nav-item">
                <a class="nav-link" href="../models/loan.php">
                <i class="fas fa-fw fas fa-comment-dollar"></i>
                    <span>New Loan</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../models/pending_approval.php">
                <i class="fas fa-fw fas fa-comment-dollar"></i>
                    <span>Pending Approval</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/disbursement.php">
                    <i class="fas fa-fw fas fa-coins"></i>
                    <span>Disbursements</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="daily-reconciliation.php">
                    <i class="fas fa-fw fa-balance-scale"></i>
                    <span>Daily Reconciliation</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="expenses_tracking.php">
                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                    <span>Expenses Tracking</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="manage_expenses.php">
                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    <span>Manage Expenses</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="../models/arrears.php">
                <i class="fas fa-users-slash fa-2x text-gray-300"></i>
                    <span>Arrears</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="receipts.php">
                <i class="fas fa-receipt fa-2x"></i>
                    <span>Receipts</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="account.php">
                <i class="fas fa-fw fa-user"></i>
                    <span>Client Accounts</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Wekeza Groups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/business_groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Business Groups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/loan_plan.php">
                    <i class="fas fa-fw fa-piggy-bank"></i>
                    <span>Loan Products</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                System
            </div>

            <li class="nav-item">
                <a class="nav-link" href="../models/user.php">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Users</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="announcements.php">
                    <i class="fas fa-fw fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="notifications.php">
                    <i class="fas fa-fw fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="backup.php">
                    <i class="fas fa-fw fa-database"></i>
                    <span>Backup</span>
                </a>
            </li>
        </ul>
        <!-- End of Sidebar -->



        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $db->user_acc($_SESSION['user_id'])?></span>
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

                <div class="container-fluid pt-4">

                <!--Filtering-->
                <div class="filter-section">
                        <form id="filterForm" method="GET" class="row">
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Transaction Type</label>
                                <select name="transaction_type" class="form-control">
                                    <option value="all" <?php echo $transaction_type === 'all' ? 'selected' : ''; ?>>All Transactions</option>
                                    <option value="expenses" <?php echo $transaction_type === 'expenses' ? 'selected' : ''; ?>>Expenses Only</option>
                                    <option value="received" <?php echo $transaction_type === 'received' ? 'selected' : ''; ?>>Money Received Only</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Date Range</label>
                                <select name="date_range" class="form-control" id="dateRangeSelect">
                                    <option value="all" <?php echo $date_range === 'all' ? 'selected' : ''; ?>>All Time</option>
                                    <option value="today" <?php echo $date_range === 'today' ? 'selected' : ''; ?>>Today</option>
                                    <option value="week" <?php echo $date_range === 'week' ? 'selected' : ''; ?>>This Week</option>
                                    <option value="month" <?php echo $date_range === 'month' ? 'selected' : ''; ?>>This Month</option>
                                    <option value="year" <?php echo $date_range === 'year' ? 'selected' : ''; ?>>This Year</option>
                                    <option value="custom" <?php echo $date_range === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                </select>
                            </div>
                            <div class="col-md-4 date-range-picker <?php echo $date_range === 'custom' ? 'active' : ''; ?>">
                                <div class="row">
                                    <div class="col-6">
                                        <label class="font-weight-bold">Start Date</label>
                                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="font-weight-bold">End Date</label>
                                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mt-4">
                                <div class="d-flex">
                                    <button type="submit" class="btn btn-warning mr-2">Apply Filter</button>
                                    <button type="button" class="btn btn-success" onclick="generateReport()">
                                        <i class="fas fa-download"></i> Generate Report
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="summary-card">
                    <h6 class="font-weight-bold text-primary">Total Expenses</h6>
                    <h4 class="text-danger">KSh <?php echo number_format($total_expenses, 2); ?></h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <h6 class="font-weight-bold text-primary">Total Received</h6>
                    <h4 class="text-success">KSh <?php echo number_format($total_received, 2); ?></h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <h6 class="font-weight-bold text-primary">Net Balance</h6>
                    <h4 class="<?php echo ($total_received - $total_expenses) >= 0 ? 'text-success' : 'text-danger'; ?>">
                        KSh <?php echo number_format($total_received - $total_expenses, 2); ?>
                    </h4>
                </div>
            </div>
        </div>


    <!--Manage expenses and income begins-->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Manage Expenses & Income</h1>
                    </div>
                    
                    <div class="mb-2 d-flex justify-content-between">
                        <button class="btn btn-lg btn-warning" href="#" data-toggle="modal" data-target="#addExpenseModal">
                            <span class="fa fa-minus-circle"></span> Add New Expense
                        </button>
                        <button class="btn btn-lg btn-success" href="#" data-toggle="modal" data-target="#addReceivedModal">
                            <span class="fa fa-plus-circle"></span> Add Money Received
                        </button>
                    </div>

                    <div class="card mb-4">
                            <div class="card-header py-3">
                                <h6 style="color: #51087E;" class="m-0 font-weight-bold">Recent Transactions</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="transactionTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Receipt No</th>
                                                <th>Category</th>
                                                <th>Description</th>
                                                <th>Amount</th>
                                                <th>Payment Method</th>
                                                <th>Status</th>
                                                <th>Remarks</th>
                                                <th>Created By</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            if ($transactions && $transactions->num_rows > 0):
                                                while($transaction = $transactions->fetch_assoc()): 
                                            ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($transaction['date'])); ?></td>
                                                    <td><?php echo $transaction['receipt_no']; ?></td>
                                                    <td><?php echo $transaction['main_category']; ?></td>
                                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                                    <td class="<?php echo $transaction['status'] === 'received' ? 'text-success' : 'text-danger'; ?>">
                                                        KSh <?php echo number_format($transaction['signed_amount'], 2); ?>
                                                    </td>
                                                    <td><?php echo $transaction['payment_method']; ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php 
                                                            if ($transaction['status'] == 'received') {
                                                                echo 'success';
                                                            } elseif ($transaction['status'] == 'completed') {
                                                                echo 'danger';
                                                            } else {
                                                                echo 'warning';
                                                            }
                                                        ?>">
                                                            <?php 
                                                            if ($transaction['status'] == 'received') {
                                                                echo 'Received';
                                                            } elseif ($transaction['status'] == 'completed') {
                                                                echo 'Expense';
                                                            } else {
                                                                echo ucfirst($transaction['status']);
                                                            }
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($transaction['remarks']); ?></td>
                                                    <td><?php echo $transaction['created_by_name']; ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning print-receipt mr-2" 
                                                                data-transaction='<?php echo json_encode($transaction); ?>'>
                                                            <i class="fas fa-print"></i> Print
                                                        </button>
                                                        <button class="btn btn-sm btn-danger delete-transaction" 
                                                                data-receipt-no="<?php echo htmlspecialchars($transaction['receipt_no']); ?>"
                                                                data-id="<?php echo htmlspecialchars($transaction['id']); ?>">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php 
                                                endwhile;
                                            else:
                                            ?>
                                                <tr>
                                                    <td colspan="10" class="text-center">No transactions found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
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
                <!-- End of Footer -->


        </div>
    </div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="../controllers/save_expense.php" id="expenseForm">
            <div class="modal-content">
                <div style="background-color: #51087E;" class="modal-header">
                    <h5 class="modal-title text-white">Add New Expense</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category <span class="text-danger">*</span></label>
                                <select name="main_category" class="form-control select2" required>
                                    <option value="">Select Category</option>
                                    <?php 
                                    $categories_query = "SELECT DISTINCT category FROM expenses_categories ORDER BY category";
                                    $categories_result = $db->conn->query($categories_query);
                                    while($cat = $categories_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                            <?php echo htmlspecialchars($cat['category']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Expense Name <span class="text-danger">*</span></label>
                                <select name="category" class="form-control select2" required>
                                    <option value="">Select Expense Name</option>
                                    <?php 
                                    $names_query = "SELECT DISTINCT name FROM expenses_categories ORDER BY name";
                                    $names_result = $db->conn->query($names_query);
                                    while($name = $names_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($name['name']); ?>">
                                            <?php echo htmlspecialchars($name['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Amount (KSh) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control" required step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-control select2" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Mobile Money">Mobile Money</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <input type="text" name="status" value="completed" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Receipt No. <span class="text-danger">*</span></label>
                        <input type="text" name="receipt_no" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Enter expense description"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Enter additional remarks if any"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="save_expense" class="btn btn-warning">Save Expense</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Money Received Modal -->
<div class="modal fade" id="addReceivedModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="../controllers/save_expense.php" id="receivedForm">
            <div class="modal-content">
                <div style="background-color: #28a745;" class="modal-header">
                    <h5 class="modal-title text-white">Add Money Received</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category <span class="text-danger">*</span></label>
                                <select name="main_category" class="form-control select2" required>
                                    <option value="">Select Category</option>
                                    <?php 
                                    $categories_result->data_seek(0); // Reset pointer
                                    while($cat = $categories_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                            <?php echo htmlspecialchars($cat['category']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Income Source <span class="text-danger">*</span></label>
                                <select name="category" class="form-control select2" required>
                                    <option value="">Select Income Source</option>
                                    <?php 
                                    $names_result->data_seek(0); // Reset pointer
                                    while($name = $names_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($name['name']); ?>">
                                            <?php echo htmlspecialchars($name['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Amount (KSh) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control" required step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-control select2" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Mobile Money">Mobile Money</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <input type="text" name="status" value="received" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Receipt No. <span class="text-danger">*</span></label>
                        <input type="text" name="receipt_no" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Enter description"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Enter additional remarks if any"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="save_expense" class="btn btn-success">Save Transaction</button>
                </div>
            </div>
        </form>
    </div>
</div>



    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Expense Receipt</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="receiptContent" class="receipt"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning" onclick="printReceipt()">Print</button>
                </div>
            </div>
        </div>
    </div>


        <!-- Statement Print Template (hidden) -->
        <div id="statementTemplate" style="display: none;">
        <div class="statement-header">
            <h2>LATO SACCO LTD</h2>
            <h3>Transaction Statement</h3>
            <p>Period: <span id="statementPeriod"></span></p>
        </div>
        <div class="statement-summary">
            <h4>Summary</h4>
            <table class="summary-table">
                <tr>
                    <td>Total Expenses:</td>
                    <td>KSh <?php echo number_format($total_expenses, 2); ?></td>
                </tr>
                <tr>
                    <td>Total Received:</td>
                    <td>KSh <?php echo number_format($total_received, 2); ?></td>
                </tr>
                <tr>
                    <td>Net Balance:</td>
                    <td>KSh <?php echo number_format($total_received - $total_expenses, 2); ?></td>
                </tr>
            </table>
            
            <h4>Category Breakdown</h4>
            <table class="category-table">
                <?php foreach ($category_totals as $category => $total): ?>
                <tr>
                    <td><?php echo htmlspecialchars($category); ?>:</td>
                    <td>KSh <?php echo number_format($total, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <div class="statement-details">
            <h4>Transaction Details</h4>
            <table class="transaction-table">
                <!-- Transaction details will be populated via JavaScript -->
            </table>
        </div>
    </div>



    <!-- Scripts -->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>
    <script src="../public/js/jquery.easing.js"></script>
    <script src="../public/js/sb-admin-2.js"></script>
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>
    <script src="../public/js/select2.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
$(document).ready(function() {
    // Initialize DataTable
    $('#transactionTable').DataTable({
        pageLength: 25,
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

    // Initialize Select2 for all select elements
    $('.select2').select2({
        width: '100%',
        dropdownParent: function() {
            return $(this).closest('.modal');
        }
    });

    // Initialize Flatpickr date picker
    $('input[type="date"]').flatpickr({
        dateFormat: "Y-m-d",
        maxDate: "today"
    });

    // Handle date range picker visibility
    $('#dateRangeSelect').change(function() {
        if ($(this).val() === 'custom') {
            $('.date-range-picker').addClass('active');
        } else {
            $('.date-range-picker').removeClass('active');
        }
    });

    // Form validation and submission
    $('#expenseForm, #receivedForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        
        // Validate form
        let isValid = true;
        form.find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            alert('Please fill in all required fields');
            return false;
        }

        // Submit form normally
        form[0].submit();
    });

    // Clear validation on input change
    $('.form-control, .select2').on('change keyup', function() {
        $(this).removeClass('is-invalid');
    });

    // Print receipt handler
    $('.print-receipt').on('click', function() {
        const transaction = $(this).data('transaction');
        const isReceived = transaction.status === 'received';
        const amount = parseFloat(transaction.signed_amount);
        const formattedAmount = (amount >= 0 ? '+' : '') + amount.toLocaleString(undefined, {minimumFractionDigits: 2});
        
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
                <p><strong>Description:</strong> ${transaction.description || 'N/A'}</p>
                <p><strong>Amount:</strong> KSh ${formattedAmount}</p>
                <p><strong>Payment Method:</strong> ${transaction.payment_method}</p>
                <p><strong>Status:</strong> ${transaction.status}</p>
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

    // Delete transaction handler
    $('.delete-transaction').on('click', function(e) {
        e.preventDefault();
        const transactionId = $(this).data('id');
        const receiptNo = $(this).data('receipt-no');
        
        if (confirm(`Are you sure you want to delete transaction with receipt no: ${receiptNo}? This action cannot be undone.`)) {
            $('body').append('<div class="loading-overlay"><div class="spinner"></div></div>');
            
            $.ajax({
                url: '../controllers/delete_transaction.php',
                method: 'POST',
                data: { transaction_id: transactionId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Transaction deleted successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + (response.error || 'Failed to delete transaction'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete Error:', xhr.responseText);
                    alert('Error: Failed to delete transaction');
                },
                complete: function() {
                    $('.loading-overlay').remove();
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
            alert('Please select both start and end dates for custom range');
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

    // Initialize modals
    $('#addExpenseModal, #addReceivedModal').on('show.bs.modal', function() {
        $(this).find('form')[0].reset();
        $(this).find('select').val('').trigger('change');
    });
});
</script>



   <!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger" href="../views/logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Success Message Modal -->
<?php if (isset($_SESSION['success_msg'])): ?>
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white">Success</h5>
                <button class="close" type="button" data-dismiss="modal">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <?php 
                    echo $_SESSION['success_msg'];
                    unset($_SESSION['success_msg']);
                ?>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#successModal').modal('show');
    });
</script>
<?php endif; ?>

<!-- Error Message Modal -->
<?php if (isset($_SESSION['error_msg'])): ?>
<div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">Error</h5>
                <button class="close" type="button" data-dismiss="modal">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <?php 
                    echo $_SESSION['error_msg'];
                    unset($_SESSION['error_msg']);
                ?>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#errorModal').modal('show');
    });
</script>
<?php endif; ?>

 
</body>
</html>