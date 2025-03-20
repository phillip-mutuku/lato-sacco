<?php
    date_default_timezone_set("Africa/Nairobi");
    require_once '../helpers/session.php';
    require_once '../config/class.php';
    $db = new db_class();

// Check if user is logged in and is a cashier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: index.php');
    exit();
}

    // Initialize all variables
    $total_group_savings = 0;
    $total_group_withdrawals = 0;
    $total_business_savings = 0;
    $total_business_withdrawals = 0;
    $total_payments = 0;
    $total_repayments = 0;
    $total_expenses = 0;

    // Initialize data arrays
    $group_savings_data = [];
    $group_withdrawals_data = [];
    $business_savings_data = [];
    $business_withdrawals_data = [];
    $payments_data = [];
    $repayments_data = [];
    $expenses_data = [];

    // Handle float management
    if (isset($_POST['add_float'])) {
        $receipt_no = $_POST['receipt_no'];
        $amount = $_POST['amount'];
        $user_id = $_SESSION['user_id'];
        
        $query = "INSERT INTO float_management (receipt_no, amount, type, user_id, date_created) 
                 VALUES (?, ?, 'add', ?, NOW())";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("sdi", $receipt_no, $amount, $user_id);
        $stmt->execute();
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['offload_float'])) {
        $receipt_no = $_POST['receipt_no'];
        $amount = $_POST['amount'];
        $user_id = $_SESSION['user_id'];
        
        $query = "INSERT INTO float_management (receipt_no, amount, type, user_id, date_created) 
                 VALUES (?, ?, 'offload', ?, NOW())";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("sdi", $receipt_no, $amount, $user_id);
        $stmt->execute();
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Get float totals for today
    $today_start = date('Y-m-d 00:00:00');
    $today_end = date('Y-m-d 23:59:59');
    
    $query = "SELECT 
                COALESCE(SUM(CASE WHEN type = 'add' THEN amount ELSE 0 END), 0) as total_added,
                COALESCE(SUM(CASE WHEN type = 'offload' THEN amount ELSE 0 END), 0) as total_offloaded
              FROM float_management 
              WHERE date_created BETWEEN ? AND ?";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("ss", $today_start, $today_end);
    $stmt->execute();
    $float_result = $stmt->get_result()->fetch_assoc();
    
    $opening_float = $float_result['total_added'];
    $total_offloaded = $float_result['total_offloaded'];
    $closing_float = $opening_float - $total_offloaded;

    // Get float transactions
    $query = "SELECT f.*, u.username as served_by 
              FROM float_management f 
              LEFT JOIN user u ON f.user_id = u.user_id 
              WHERE f.date_created BETWEEN ? AND ?
              ORDER BY f.date_created DESC";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("ss", $today_start, $today_end);
    $stmt->execute();
    $float_transactions = $stmt->get_result();

    // Handle transaction filtering
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] . " 00:00:00" : $today_start;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] . " 23:59:59" : $today_end;

    // Group Savings Query
    $group_savings_query = "SELECT gs.*, lg.group_name, u.username as served_by_name
                           FROM group_savings gs 
                           LEFT JOIN lato_groups lg ON gs.group_id = lg.group_id 
                           LEFT JOIN user u ON gs.served_by = u.user_id
                           WHERE gs.date_saved BETWEEN ? AND ?
                           ORDER BY gs.date_saved DESC";
    $stmt = $db->conn->prepare($group_savings_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $group_savings_data[] = $row;
        $total_group_savings += $row['amount'];
    }

    // Group Withdrawals Query
    $business_savings_query = "SELECT 
    bgt.transaction_id,
    bgt.group_id,
    bgt.type,
    bgt.amount,
    bgt.description,
    bgt.receipt_no,
    bgt.payment_mode,
    bgt.date,
    bgt.served_by,
    bg.group_name,
    u.username as served_by_name
FROM business_group_transactions bgt
LEFT JOIN business_groups bg ON bgt.group_id = bg.group_id
LEFT JOIN user u ON bgt.served_by = u.user_id
WHERE bgt.type = 'Savings' 
AND DATE(bgt.date) BETWEEN ? AND ?
ORDER BY bgt.date DESC";

$stmt = $db->conn->prepare($business_savings_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $business_savings_data[] = $row;
    $total_business_savings += $row['amount'];
}

// Business Group Withdrawals from business_group_transactions
$business_withdrawals_query = "SELECT 
    bgt.transaction_id,
    bgt.group_id,
    bgt.type,
    bgt.amount,
    bgt.description,
    bgt.receipt_no,
    bgt.payment_mode,
    bgt.date,
    bgt.served_by,
    bg.group_name,
    u.username as served_by_name,
    CASE 
        WHEN bgt.type = 'Withdrawal Fee' THEN bgt.amount 
        ELSE 0 
    END as withdrawal_fee
FROM business_group_transactions bgt
LEFT JOIN business_groups bg ON bgt.group_id = bg.group_id
LEFT JOIN user u ON bgt.served_by = u.user_id
WHERE (bgt.type = 'Withdrawal' OR bgt.type = 'Withdrawal Fee')
AND DATE(bgt.date) BETWEEN ? AND ?
ORDER BY bgt.date DESC";

$stmt = $db->conn->prepare($business_withdrawals_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $business_withdrawals_data[] = $row;
    if ($row['type'] == 'Withdrawal') {
        $total_business_withdrawals += $row['amount'];
    }
}

// Group Withdrawals updated query
$group_withdrawals_query = "SELECT 
    gw.*,
    lg.group_name,
    u.username as served_by_name
FROM group_withdrawals gw
LEFT JOIN lato_groups lg ON gw.group_id = lg.group_id
LEFT JOIN user u ON gw.served_by = u.user_id
WHERE DATE(gw.date_withdrawn) BETWEEN ? AND ?
ORDER BY gw.date_withdrawn DESC";

$stmt = $db->conn->prepare($group_withdrawals_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $group_withdrawals_data[] = $row;
    $total_group_withdrawals += $row['amount'];
}

// Individual Savings Transactions
$savings_query = "SELECT 
    s.*,
    a.first_name as account_name,
    u.username as served_by_name
FROM savings s
LEFT JOIN client_accounts a ON s.account_id = a.account_id
LEFT JOIN user u ON s.served_by = u.user_id
WHERE DATE(s.date) BETWEEN ? AND ?
ORDER BY s.date DESC";

$stmt = $db->conn->prepare($savings_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$savings_data = [];
$total_individual_savings = 0;
$total_individual_withdrawals = 0;

while ($row = $result->fetch_assoc()) {
    $savings_data[] = $row;
    if ($row['type'] == 'Savings') {
        $total_individual_savings += $row['amount'];
    } else if ($row['type'] == 'Withdrawal') {
        $total_individual_withdrawals += $row['amount'];
    }
}



  

   

    // Loan Payments Query
    $payments_query = "SELECT p.*, l.ref_no, u.username as disbursed_by 
                      FROM payment p 
                      LEFT JOIN loan l ON p.loan_id = l.loan_id 
                      LEFT JOIN user u ON p.user_id = u.user_id
                      WHERE p.date_created BETWEEN ? AND ?
                      ORDER BY p.date_created DESC";
    $stmt = $db->conn->prepare($payments_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $payments_data[] = $row;
        $total_payments += $row['pay_amount'];
    }

    // Loan Repayments Query
    $repayments_query = "SELECT lr.*, l.ref_no, u.username as served_by_name
                        FROM loan_repayments lr 
                        LEFT JOIN loan l ON lr.loan_id = l.loan_id 
                        LEFT JOIN user u ON lr.served_by = u.user_id
                        WHERE lr.date_paid BETWEEN ? AND ?
                        ORDER BY lr.date_paid DESC";
    $stmt = $db->conn->prepare($repayments_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $repayments_data[] = $row;
        $total_repayments += $row['amount_repaid'];
    }

    // Expenses Query
    $expenses_query = "SELECT e.*, u.username as created_by_name
                      FROM expenses e 
                      LEFT JOIN user u ON e.created_by = u.user_id
                      WHERE e.date BETWEEN ? AND ?
                      ORDER BY e.date DESC";
    $stmt = $db->conn->prepare($expenses_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $expenses_data[] = $row;
        $total_expenses += $row['amount'];
    }

    // Calculate total inflows and outflows
    $total_inflows = $total_group_savings + $total_business_savings + $total_repayments + $total_individual_savings;

    $total_outflows = $total_group_withdrawals + $total_business_withdrawals + $total_payments + $total_expenses + $total_individual_withdrawals;
    
    // Recalculate net position
    $net_position = $total_inflows - $total_outflows;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Daily Reconciliation - Lato Management System</title>
    
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    
    <style>
        .float-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fc 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: rgb(204, 219, 232) 3px 3px 6px 0px inset, rgba(255, 255, 255, 0.5) -3px -3px 6px 1px inset;
            transition: transform 0.3s ease;
        }
        
        .float-card:hover {
            transform: translateY(-5px);
        }
        
        .float-title {
            color: #51087E;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .float-amount {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin: 25px 0;
        }
        
        .action-buttons button {
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link {
            color: #51087E;
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 10px 10px 0 0;
        }
        
        .nav-tabs .nav-link.active {
            color: #fff;
            background-color: #51087E;
            border-color: #51087E;
        }
        
        .table thead th {
            background-color: #f8f9fc;
            color: #51087E;
            font-weight: 600;
            border-bottom: 2px solid #51087E;
        }
        
        .transaction-summary {
            background-color: #f8f9fc;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .transaction-summary h6 {
            color: #51087E;
            margin-bottom: 10px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .receipt {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .receipt-details {
            margin-bottom: 20px;
        }
        
        .receipt-footer {
            text-align: center;
            font-size: 14px;
            color: #666;
        }

        .tab-pane {
            padding: 20px;
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-top: 0;
            border-radius: 0 0 10px 10px;
        }

        .row .card{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }



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


    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <ul style="background: #51087E;"  class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
                <div class="sidebar-brand-text mx-3">LATO SACCO</div>
            </a>

            <hr class="sidebar-divider my-0">

  
            <li class="nav-item">
                <a class="nav-link" href="cashier.php">
                    <i class="fas fa-fw fa-home"></i>
                    <span>Home</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Management
            </div>

            <li class="nav-item">
                <a class="nav-link" href="../models/cashier_disbursement.php">
                    <i class="fas fa-fw fas fa-coins"></i>
                    <span>Disbursements</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="cashier-daily-reconciliation.php">
                    <i class="fas fa-fw fa-balance-scale"></i>
                    <span>Daily Reconciliation</span>
                </a>
            </li>


            <li class="nav-item active">
                <a class="nav-link" href="cashier_manage_expenses.php">
                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    <span>Manage Expenses</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="../models/cashier_arrears.php">
                <i class="fas fa-users-slash fa-2x text-gray-300"></i>
                    <span>Arrears</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="cashier-account.php">
                <i class="fas fa-fw fa-user"></i>
                    <span>Client Accounts</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/cashier_groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Wekeza Groups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/cashier_business_groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Business Groups</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                System
            </div>

            <li class="nav-item active">
                <a class="nav-link" href="cashier_announcements.php">
                    <i class="fas fa-fw fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </li>
        </ul>
        <!-- End of Sidebar -->




        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    
                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $db->user_acc($_SESSION['user_id'])?></span>
                                <img class="img-profile rounded-circle" src="../public/image/logo.jpg">
                            </a>
                            <!-- Dropdown - User Information -->
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
                <div class="container-fluid pt-4">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Daily Reconciliation</h1>
                        <button class="btn btn-warning" onclick="generateReport()">
                            <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
                        </button>
                    </div>

                    <!-- Float Management Section -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="float-card">
                                <div class="float-title">Opening Float</div>
                                <div class="float-amount">KSh <?= number_format($opening_float, 2) ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="float-card">
                                <div class="float-title">Total Offloaded</div>
                                <div class="float-amount">KSh <?= number_format($total_offloaded, 2) ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="float-card">
                                <div class="float-title">Closing Float</div>
                                <div class="float-amount" id="closingFloatAmount">KSh <?= number_format($closing_float, 2) ?></div>
                                <button class="btn btn-warning mt-3 w-100" onclick="calculateClosingFloat()">
                                    <i class="fas fa-calculator"></i> Calculate
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons mb-4">
                        <button class="btn btn-success" data-toggle="modal" data-target="#addFloatModal">
                            <i class="fas fa-plus"></i> Add Float
                        </button>
                        <button class="btn btn-danger" data-toggle="modal" data-target="#offloadFloatModal">
                            <i class="fas fa-minus"></i> Offload Float
                        </button>
                    </div>

                    <!-- Float Transactions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold" style="color: #51087E;">Float Transactions</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="floatTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Receipt No</th>
                                            <th>Amount</th>
                                            <th>Transaction Type</th>
                                            <th>Date</th>
                                            <th>Served By</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($float_transactions as $transaction): ?>
                                        <tr>
                                            <td><?= $transaction['receipt_no'] ?></td>
                                            <td>KSh <?= number_format($transaction['amount'], 2) ?></td>
                                            <td><?= ucfirst($transaction['type']) ?> Float</td>
                                            <td><?= date('M d, Y H:i', strtotime($transaction['date_created'])) ?></td>
                                            <td><?= $transaction['served_by'] ?></td>
                                            <td>
                                                <button class="btn btn-warning btn-sm print-receipt" 
                                                        data-receipt="<?= $transaction['receipt_no'] ?>"
                                                        data-amount="<?= $transaction['amount'] ?>"
                                                        data-type="<?= ucfirst($transaction['type']) ?> Float"
                                                        data-date="<?= $transaction['date_created'] ?>"
                                                        data-served="<?= $transaction['served_by'] ?>">
                                                    <i class="fas fa-print"></i> Print
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Filter Section -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold" style="color: #51087E;">Transaction Filter</h6>
                        </div>
                        <div class="card-body">
                            <form id="filterForm" method="GET" class="mb-4">
                                <div class="row">
                                    <div class="col-md-5">
                                        <label>Start Date</label>
                                        <input type="date" name="start_date" class="form-control" 
                                               value="<?= isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-md-5">
                                        <label>End Date</label>
                                        <input type="date" name="end_date" class="form-control"
                                               value="<?= isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Filtered Results -->
                            <ul class="nav nav-tabs" id="transactionTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="groupSavings-tab" data-toggle="tab" href="#groupSavings" role="tab">
                                        Group Savings
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="groupWithdrawals-tab" data-toggle="tab" href="#groupWithdrawals" role="tab">
                                        Group Withdrawals
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="businessSavings-tab" data-toggle="tab" href="#businessSavings" role="tab">
                                        Business Savings
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="businessWithdrawals-tab" data-toggle="tab" href="#businessWithdrawals" role="tab">
                                        Business Withdrawals
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="payments-tab" data-toggle="tab" href="#payments" role="tab">
                                        Loan Disbursements
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="repayments-tab" data-toggle="tab" href="#repayments" role="tab">
                                        Loan Repayments
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="expenses-tab" data-toggle="tab" href="#expenses" role="tab">
                                        Expenses
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="individual-savings-tab" data-toggle="tab" href="#individualSavings" role="tab">
                                        Individual Savings
                                    </a>
                                </li>
                                </ul>

                            <div class="tab-content mt-3" id="transactionTabContent">
                                <!-- Group Savings Tab -->
                                <div class="tab-pane fade show active" id="groupSavings" role="tabpanel">
                                    <div class="transaction-summary">
                                        <h6>Total Group Savings: <span class="text-success">KSh <?= number_format($total_group_savings, 2) ?></span></h6>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered transactionTable">
                                            <thead>
                                                <tr>
                                                    <th>Group Name</th>
                                                    <th>Amount</th>
                                                    <th>Payment Mode</th>
                                                    <th>Receipt No</th>
                                                    <th>Date</th>
                                                    <th>Served By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($group_savings_data as $row): ?>
                                                <tr>
                                                    <td><?= $row['group_name'] ?></td>
                                                    <td>KSh <?= number_format($row['amount'], 2) ?></td>
                                                    <td><?= $row['payment_mode'] ?></td>
                                                    <td><?= $row['receipt_no'] ?></td>
                                                    <td><?= date('M d, Y', strtotime($row['date_saved'])) ?></td>
                                                    <td><?= $row['served_by_name'] ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Group Withdrawals Tab -->
                                <div class="tab-pane fade" id="groupWithdrawals" role="tabpanel">
                                    <div class="transaction-summary">
                                        <h6>Total Group Withdrawals: <span class="text-danger">KSh <?= number_format($total_group_withdrawals, 2) ?></span></h6>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered transactionTable">
                                            <thead>
                                                <tr>
                                                    <th>Group Name</th>
                                                    <th>Amount</th>
                                                    <th>Payment Mode</th>
                                                    <th>Receipt No</th>
                                                    <th>Date</th>
                                                    <th>Served By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($group_withdrawals_data as $row): ?>
                                                <tr>
                                                    <td><?= $row['group_name'] ?></td>
                                                    <td>KSh <?= number_format($row['amount'], 2) ?></td>
                                                    <td><?= $row['payment_mode'] ?></td>
                                                    <td><?= $row['receipt_no'] ?></td>
                                                    <td><?= date('M d, Y', strtotime($row['date_withdrawn'])) ?></td>
                                                    <td><?= $row['served_by_name'] ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Business Group Savings Tab -->
                                <div class="tab-pane fade" id="businessSavings" role="tabpanel">
                                    <div class="transaction-summary">
                                        <h6>Total Business Group Savings: <span class="text-success">KSh <?= number_format($total_business_savings, 2) ?></span></h6>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered transactionTable">
                                            <thead>
                                                <tr>
                                                    <th>Group Name</th>
                                                    <th>Amount</th>
                                                    <th>Payment Mode</th>
                                                    <th>Receipt No</th>
                                                    <th>Date</th>
                                                    <th>Served By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($business_savings_data as $row): ?>
                                                <tr>
                                                    <td><?= $row['group_name'] ?></td>
                                                    <td>KSh <?= number_format($row['amount'], 2) ?></td>
                                                    <td><?= $row['payment_mode'] ?></td>
                                                    <td><?= $row['receipt_no'] ?></td>
                                                    <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                                    <td><?= $row['served_by_name'] ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Business Group Withdrawals Tab -->
                                <div class="tab-pane fade" id="businessWithdrawals" role="tabpanel">
                                    <div class="transaction-summary">
                                        <h6>Total Business Group Withdrawals: <span class="text-danger">KSh <?= number_format($total_business_withdrawals, 2) ?></span></h6>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered transactionTable">
                                            <thead>
                                                <tr>
                                                    <th>Group Name</th>
                                                    <th>Amount</th>
                                                    <th>Withdrawal Fee</th>
                                                    <th>Payment Mode</th>
                                                    <th>Receipt No</th>
                                                    <th>Date</th>
                                                    <th>Served By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($business_withdrawals_data as $row): ?>
                                                <tr>
                                                    <td><?= $row['group_name'] ?></td>
                                                    <td>KSh <?= number_format($row['amount'], 2) ?></td>
                                                    <td>KSh <?= number_format($row['withdrawal_fee'], 2) ?></td>
                                                    <td><?= $row['payment_mode'] ?></td>
                                                    <td><?= $row['receipt_no'] ?></td>
                                                    <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                                    <td><?= $row['served_by_name'] ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Loan Payments Tab -->
                                <div class="tab-pane fade" id="payments" role="tabpanel">
                                    <div class="transaction-summary">
                                        <h6>Total Loan Disbursements: <span class="text-primary">KSh <?= number_format($total_payments, 2) ?></span></h6>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered transactionTable">
                                            <thead>
                                                <tr>
                                                    <th>Loan Ref No</th>
                                                    <th>Payee</th>
                                                    <th>Amount</th>
                                                    <th>Receipt No</th>
                                                    <th>Withdrawal Fee</th>
                                                    <th>Date</th>
                                                    <th>Disbursed By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($payments_data as $row): ?>
                                                <tr>
                                                    <td><?= $row['ref_no'] ?></td>
                                                    <td><?= $row['payee'] ?></td>
                                                    <td>KSh <?= number_format($row['pay_amount'], 2) ?></td>
                                                    <td><?= $row['receipt_no'] ?></td>
                                                    <td>KSh <?= number_format($row['withdrawal_fee'], 2) ?></td>
                                                    <td><?= date('M d, Y', strtotime($row['date_created'])) ?></td>
                                                    <td><?= $row['disbursed_by'] ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Loan Repayments Tab -->
                                <div class="tab-pane fade" id="repayments" role="tabpanel">
                                    <div class="transaction-summary">
                                        <h6>Total Loan Repayments: <span class="text-success">KSh <?= number_format($total_repayments, 2) ?></span></h6>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered transactionTable">
                                            <thead>
                                                <tr>
                                                    <th>Loan Ref No</th>
                                                    <th>Amount Repaid</th>
                                                    <th>Payment Mode</th>
                                                    <th>Receipt Number</th>
                                                    <th>Date Paid</th>
                                                    <th>Served By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($repayments_data as $row): ?>
                                                <tr>
                                                    <td><?= $row['ref_no'] ?></td>
                                                    <td>KSh <?= number_format($row['amount_repaid'], 2) ?></td>
                                                    <td><?= $row['payment_mode'] ?></td>
                                                    <td><?= $row['receipt_number'] ?></td>
                                                    <td><?= date('M d, Y', strtotime($row['date_paid'])) ?></td>
                                                    <td><?= $row['served_by_name'] ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!---Individuals transactions tab--->
                                <div class="tab-pane fade" id="individualSavings" role="tabpanel">
                                        <div class="transaction-summary">
                                            <h6>Total Individual Savings: <span class="text-success">KSh <?= number_format($total_individual_savings, 2) ?></span></h6>
                                            <h6>Total Individual Withdrawals: <span class="text-danger">KSh <?= number_format($total_individual_withdrawals, 2) ?></span></h6>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered transactionTable">
                                                <thead>
                                                    <tr>
                                                        <th>Account Name</th>
                                                        <th>Type</th>
                                                        <th>Amount</th>
                                                        <th>Withdrawal Fee</th>
                                                        <th>Payment Mode</th>
                                                        <th>Receipt Number</th>
                                                        <th>Date</th>
                                                        <th>Served By</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($savings_data as $row): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row['account_name']) ?></td>
                                                        <td><?= htmlspecialchars($row['type']) ?></td>
                                                        <td>KSh <?= number_format($row['amount'], 2) ?></td>
                                                        <td>KSh <?= number_format($row['withdrawal_fee'], 2) ?></td>
                                                        <td><?= htmlspecialchars($row['payment_mode']) ?></td>
                                                        <td><?= htmlspecialchars($row['receipt_number']) ?></td>
                                                        <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                                        <td><?= htmlspecialchars($row['served_by_name']) ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>


                                <!-- Expenses Tab -->
                                <div class="tab-pane fade" id="expenses" role="tabpanel">
                                    <div class="transaction-summary">
                                        <h6>Total Expenses: <span class="text-danger">KSh <?= number_format($total_expenses, 2) ?></span></h6>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered transactionTable">
                                            <thead>
                                                <tr>
                                                    <th>Category</th>
                                                    <th>Description</th>
                                                    <th>Amount</th>
                                                    <th>Reference No</th>
                                                    <th>Receipt No</th>
                                                    <th>Payment Method</th>
                                                    <th>Date</th>
                                                    <th>Created By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($expenses_data as $row): ?>
                                                <tr>
                                                    <td><?= $row['category'] ?></td>
                                                    <td><?= $row['description'] ?></td>
                                                    <td>KSh <?= number_format($row['amount'], 2) ?></td>
                                                    <td><?= $row['reference_no'] ?></td>
                                                    <td><?= $row['receipt_no'] ?></td>
                                                    <td><?= $row['payment_method'] ?></td>
                                                    <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                                    <td><?= $row['created_by_name'] ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

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
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Add Float Modal -->
    <div class="modal fade" id="addFloatModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #51087E;">
                    <h5 class="modal-title text-white">Add Float</h5>
                    <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form method="POST" id="addFloatForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Receipt Number</label>
                            <input type="text" name="receipt_no" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">KSh</span>
                                </div>
                                <input type="number" step="0.01" name="amount" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_float" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> Add Float
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Offload Float Modal -->
    <div class="modal fade" id="offloadFloatModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #51087E;">
                    <h5 class="modal-title text-white">Offload Float</h5>
                    <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form method="POST" id="offloadFloatForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Receipt Number</label>
                            <input type="text" name="receipt_no" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">KSh</span>
                                </div>
                                <input type="number" step="0.01" name="amount" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="offload_float" class="btn btn-danger">
                            <i class="fas fa-minus-circle"></i> Offload Float
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Receipt Print Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #51087E;">
                    <h5 class="modal-title text-white">Transaction Receipt</h5>
                    <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="receipt" id="receiptContent">
                        <div class="receipt-header">
                            <img src="../public/image/mylogo.png" alt="Lato Management System Logo" style="width: 150px;">
                            <h4 class="mt-3">Transaction Receipt</h4>
                            <p class="text-muted">Lato Sacco LTD</p>
                        </div>
                        <div class="receipt-details">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Receipt No:</strong></td>
                                    <td id="receiptNo"></td>
                                </tr>
                                <tr>
                                    <td><strong>Amount:</strong></td>
                                    <td id="receiptAmount"></td>
                                </tr>
                                <tr>
                                    <td><strong>Transaction Type:</strong></td>
                                    <td id="receiptType"></td>
                                </tr>
                                <tr>
                                    <td><strong>Date:</strong></td>
                                    <td id="receiptDate"></td>
                                </tr>
                                <tr>
                                    <td><strong>Served By:</strong></td>
                                    <td id="receiptServedBy"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="receipt-footer">
                            <hr>
                            <p class="mb-1">Thank you for choosing Lato Sacco LTD</p>
                            <p class="small text-muted">This is a computer generated receipt</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Close</button>
                    <button class="btn btn-primary" onclick="printReceipt()">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">Ready to Leave?</h5>
                    <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
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

    <!-- Bootstrap core JavaScript-->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../public/js/jquery.easing.js"></script>

    <!-- Page level plugins -->
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>

    <!-- Custom scripts -->
    <script>
        $(document).ready(function() {
            // Initialize all DataTables
            $('.transactionTable').each(function() {
                $(this).DataTable({
                    "order": [[4, "desc"]],
                    "pageLength": 25,
                    "responsive": true,
                    "language": {
                        "search": "Search: ",
                        "lengthMenu": "Show _MENU_ entries per page",
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                        "infoEmpty": "Showing 0 to 0 of 0 entries",
                        "infoFiltered": "(filtered from _MAX_ total entries)"
                    }
                });
            });

            // Handle float form submissions
            $('#addFloatForm, #offloadFloatForm').on('submit', function() {
                showLoadingSpinner();
            });

            // Handle receipt printing
            $('.print-receipt').click(function() {
                const data = $(this).data();
                $('#receiptNo').text(data.receipt);
                $('#receiptAmount').text('KSh ' + parseFloat(data.amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#receiptType').text(data.type);
                $('#receiptDate').text(new Date(data.date).toLocaleString());
                $('#receiptServedBy').text(data.served);
                $('#receiptModal').modal('show');
            });

            // Handle filter form submission
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                const startDate = $('input[name="start_date"]').val();
                const endDate = $('input[name="end_date"]').val();

                if (!startDate || !endDate) {
                    alert('Please select both start and end dates');
                    return false;
                }

                if (startDate > endDate) {
                    alert('Start date cannot be later than end date');
                    return false;
                }

                showLoadingSpinner();
                this.submit();
            });
        });

        function calculateClosingFloat() {
            const openingFloat = parseFloat('<?= $opening_float ?>');
            const totalOffloaded = parseFloat('<?= $total_offloaded ?>');
            const closingFloat = openingFloat - totalOffloaded;
            
            $('#closingFloatAmount').text('KSh ' + closingFloat.toLocaleString('en-US', {minimumFractionDigits: 2}));
            
            // Animate the calculation
            $('#closingFloatAmount').fadeOut(200).fadeIn(200);
        }

        function printReceipt() {
            const printContents = document.getElementById('receiptContent').innerHTML;
            const originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            
            // Reinitialize event handlers
            $(document).ready();
        }

        function showLoadingSpinner() {
            $('body').append(`
                <div class="loading-overlay">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            `);
        }

        function generateReport() {
            const startDate = $('input[name="start_date"]').val();
            const endDate = $('input[name="end_date"]').val();
            window.location.href = `../controllers/generate_reconciliation_report.php?start_date=${startDate}&end_date=${endDate}`;
        }
    </script>
</body>
</html>