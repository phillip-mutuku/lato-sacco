<?php
    date_default_timezone_set("Africa/Nairobi");
    require_once '../helpers/session.php';
    require_once '../config/class.php';
    $db = new db_class(); 

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'officer') {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: ../views/index.php');
    exit();
}


    // Initialize filter conditions
    $loan_where_clause = "1=1";
    $payment_where_clause = "1=1";
    $loan_params = array();
    $payment_params = array();
    $loan_types = "";
    $payment_types = "";

    if (isset($_GET['quick_filter'])) {
        switch ($_GET['quick_filter']) {
            case 'today':
                $loan_where_clause .= " AND DATE(l.date_applied) = CURDATE()";
                $payment_where_clause .= " AND DATE(p.date_created) = CURDATE()";
                break;
            case 'yesterday':
                $loan_where_clause .= " AND DATE(l.date_applied) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                $payment_where_clause .= " AND DATE(p.date_created) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $loan_where_clause .= " AND YEARWEEK(l.date_applied, 1) = YEARWEEK(CURDATE(), 1)";
                $payment_where_clause .= " AND YEARWEEK(p.date_created, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $loan_where_clause .= " AND YEAR(l.date_applied) = YEAR(CURDATE()) AND MONTH(l.date_applied) = MONTH(CURDATE())";
                $payment_where_clause .= " AND YEAR(p.date_created) = YEAR(CURDATE()) AND MONTH(p.date_created) = MONTH(CURDATE())";
                break;
            case 'custom':
                if (!empty($_GET['start_date'])) {
                    $loan_where_clause .= " AND DATE(l.date_applied) >= ?";
                    $payment_where_clause .= " AND DATE(p.date_created) >= ?";
                    $loan_params[] = $_GET['start_date'];
                    $payment_params[] = $_GET['start_date'];
                    $loan_types .= "s";
                    $payment_types .= "s";
                }
                if (!empty($_GET['end_date'])) {
                    $loan_where_clause .= " AND DATE(l.date_applied) <= ?";
                    $payment_where_clause .= " AND DATE(p.date_created) <= ?";
                    $loan_params[] = $_GET['end_date'];
                    $payment_params[] = $_GET['end_date'];
                    $loan_types .= "s";
                    $payment_types .= "s";
                }
                break;
        }
    }

    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $loan_where_clause .= " AND l.status = ?";
        $loan_params[] = $_GET['status'];
        $loan_types .= "i";
    }

    // Calculate total pending disbursements with filters
    $total_pending_query = "SELECT COALESCE(SUM(l.amount), 0) as total 
                           FROM loan l 
                           WHERE l.status = 1";
    if ($loan_where_clause !== "1=1") {
        $total_pending_query .= " AND " . str_replace("l.", "", $loan_where_clause);
    }
    $stmt = $db->conn->prepare($total_pending_query);
    if (!empty($loan_params)) {
        $stmt->bind_param($loan_types, ...$loan_params);
    }
    $stmt->execute();
    $total_pending = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Calculate total disbursements today with filters
    $total_disbursements_query = "SELECT COALESCE(SUM(p.pay_amount), 0) as total 
                                 FROM payment p 
                                 WHERE DATE(p.date_created) = CURDATE()";
    if ($payment_where_clause !== "1=1") {
        $total_disbursements_query .= " AND " . str_replace("p.", "", $payment_where_clause);
    }
    $stmt = $db->conn->prepare($total_disbursements_query);
    if (!empty($payment_params)) {
        $stmt->bind_param($payment_types, ...$payment_params);
    }
    $stmt->execute();
    $total_disbursements_today = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Calculate filtered totals
    $filtered_totals_query = "SELECT 
        COUNT(*) as total_loans,
        COALESCE(SUM(CASE WHEN l.status = 1 THEN l.amount ELSE 0 END), 0) as pending_amount,
        COALESCE(SUM(CASE WHEN l.status = 3 THEN l.amount ELSE 0 END), 0) as disbursed_amount,
        COALESCE(SUM(l.amount), 0) as total_amount
        FROM loan l 
        WHERE " . str_replace("l.", "", $loan_where_clause);
    $stmt = $db->conn->prepare($filtered_totals_query);
    if (!empty($loan_params)) {
        $stmt->bind_param($loan_types, ...$loan_params);
    }
    $stmt->execute();
    $filtered_totals = $stmt->get_result()->fetch_assoc();

    // Get pending loans with filters
    $pending_loans_query = "SELECT l.*, c.first_name, c.last_name 
                           FROM loan l 
                           INNER JOIN client_accounts c ON l.account_id = c.account_id 
                           WHERE l.status = 1";
    if ($loan_where_clause !== "1=1") {
        $pending_loans_query .= " AND " . $loan_where_clause;
    }
    $stmt = $db->conn->prepare($pending_loans_query);
    if (!empty($loan_params)) {
        $stmt->bind_param($loan_types, ...$loan_params);
    }
    $stmt->execute();
    $pending_loans = $stmt->get_result();

    // Get all disbursed loans with filters
    $disbursed_loans_query = "SELECT p.*, l.ref_no, l.status, u.username as disbursed_by 
                             FROM payment p 
                             INNER JOIN loan l ON p.loan_id = l.loan_id 
                             LEFT JOIN user u ON p.user_id = u.user_id
                             WHERE 1=1";
    if ($payment_where_clause !== "1=1") {
        $disbursed_loans_query .= " AND " . $payment_where_clause;
    }
    $disbursed_loans_query .= " ORDER BY p.date_created DESC";
    $stmt = $db->conn->prepare($disbursed_loans_query);
    if (!empty($payment_params)) {
        $stmt->bind_param($payment_types, ...$payment_params);
    }
    $stmt->execute();
    $tbl_payment = $stmt->get_result();

    // Debug information
    if (isset($_GET['debug'])) {
        echo "<pre>";
        echo "Loan Where Clause: " . $loan_where_clause . "\n";
        echo "Loan Params: " . print_r($loan_params, true) . "\n";
        echo "Loan Types: " . $loan_types . "\n";
        echo "Payment Where Clause: " . $payment_where_clause . "\n";
        echo "Payment Params: " . print_r($payment_params, true) . "\n";
        echo "Payment Types: " . $payment_types . "\n";
        echo "</pre>";
    }


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <style>
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button{ 
            -webkit-appearance: none; 
        }
    </style>
    
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Lato Management System - Growing with you</title>

    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    <link href="../public/css/select2.css" rel="stylesheet">

    <style>
        .container-fluid .card{
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
        .summary-card {
            background-color: #f8f9fc;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border-radius: 8px;
        }
        .summary-card h4 {
            margin: 0;
            color: #4e73df;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .summary-card p {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 10px 0 0;
            color: #5a5c69;
        }
        .receipt {
            font-family: Arial, sans-serif;
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .receipt-header img {
            max-width: 100px;
        }
        .receipt-details {
            margin-bottom: 20px;
        }
        .receipt-details p {
            margin: 5px 0;
        }
        .receipt-footer {
            text-align: center;
            font-size: 0.9em;
            color: #777;
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
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <ul style="background: #51087E;"  class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
                <div class="sidebar-brand-text mx-3">LATO SACCO</div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item">
                <a class="nav-link" href="../views/field-officer.php">
                    <i class="fas fa-fw fa-home"></i>
                    <span>Home</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Management
            </div>

            <li class="nav-item">
                <a class="nav-link" href="officer_loan.php">
                <i class="fas fa-fw fas fa-comment-dollar"></i>
                    <span>New Loan</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="officer_disbursement.php">
                    <i class="fas fa-fw fas fa-coins"></i>
                    <span>Disbursements</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="officer_arrears.php">
                <i class="fas fa-users-slash fa-2x text-gray-300"></i>
                    <span>Arrears</span>
                </a>
            </li>


            <li class="nav-item">
                <a class="nav-link" href="officer_groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Wekeza Groups</span>
                </a>
            </li>


            <hr class="sidebar-divider">

                <div class="sidebar-heading">
                    System
                </div>

                <li class="nav-item active">
                    <a class="nav-link" href="../views/officer_announcements.php">
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
                                <img class="img-profile rounded-circle"
                                    src="../public/image/logo.jpg">
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
                        <h1 class="h3 mb-0 text-gray-800">Manage Loan Disbursement</h1>
                        <button class="btn btn-warning" onclick="generateReport()">
                        <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
                        </button>
                    </div>


    <!--filtering-->
    <div class="card mb-4">
    <div class="card-header py-3">
        <h6 style="color: #51087E;" class="m-0 font-weight-bold">Filter Disbursements</h6>
    </div>
    <div class="card-body">
        <form id="filterForm" method="GET" class="row align-items-end">
            <div class="col-md-3">
                <label>Quick Filters</label>
                <select class="form-control" id="quickFilter" name="quick_filter">
                    <option value="">Select Filter</option>
                    <option value="today" <?php echo isset($_GET['quick_filter']) && $_GET['quick_filter'] == 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="yesterday" <?php echo isset($_GET['quick_filter']) && $_GET['quick_filter'] == 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                    <option value="week" <?php echo isset($_GET['quick_filter']) && $_GET['quick_filter'] == 'week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="month" <?php echo isset($_GET['quick_filter']) && $_GET['quick_filter'] == 'month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="custom" <?php echo isset($_GET['quick_filter']) && $_GET['quick_filter'] == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                </select>
            </div>
            
            <div class="col-md-3 custom-date-range" style="display: none;">
                <label>Start Date</label>
                <input type="date" class="form-control" name="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
            </div>
            
            <div class="col-md-3 custom-date-range" style="display: none;">
                <label>End Date</label>
                <input type="date" class="form-control" name="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
            </div>
            
            <div class="col-md-3">
                <label>Status</label>
                <select class="form-control" name="status">
                    <option value="">All Status</option>
                    <option value="1" <?php echo isset($_GET['status']) && $_GET['status'] == '1' ? 'selected' : ''; ?>>Pending Disbursement</option>
                    <option value="3" <?php echo isset($_GET['status']) && $_GET['status'] == '3' ? 'selected' : ''; ?>>Disbursed</option>
                </select>
            </div>
            
            <div class="col-md-3 mt-4">
                <button type="submit" class="btn btn-warning">Apply Filter</button>
                <a href="disbursement.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!--Filtered results cards-->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header py-3">
                <h6 style="color: #51087E;" class="m-0 font-weight-bold">Filtered Results Summary</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="summary-card border-left-primary">
                            <h4 class="text-primary">Total Loans</h4>
                            <p><?= number_format($filtered_totals['total_loans']) ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card border-left-warning">
                            <h4 class="text-warning">Pending Amount</h4>
                            <p>KSh <?= number_format($filtered_totals['pending_amount'], 2) ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card border-left-success">
                            <h4 class="text-success">Disbursed Amount</h4>
                            <p>KSh <?= number_format($filtered_totals['disbursed_amount'], 2) ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card border-left-info">
                            <h4 class="text-info">Total Amount</h4>
                            <p>KSh <?= number_format($filtered_totals['total_amount'], 2) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-6 col-md-6">
                            <div class="summary-card border-left-warning">
                                <h4 class="text-warning">Total Pending Disbursements</h4>
                                <p>KSh <?= number_format($total_pending, 2) ?></p>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                        <div class="summary-card border-left-success">
                            <h4 class="text-success">Total Disbursements Today</h4>
                            <p id="totalDisbursements">KSh <?= number_format($total_disbursements_today, 2) ?></p>
                        </div>
                    </div>
                    </div>



                    <!-- Disbursed Loans List -->
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <h6 style="color: #51087E;" class="m-0 font-weight-bold">Disbursed Loans List</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Loan Reference No.</th>
                                    <th>Payee</th>
                                    <th>Amount</th>
                                    <th>Withdrawal Fee</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Disbursed By</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $i=1;
                                    while($fetch=$tbl_payment->fetch_array()){
                                ?>
                                    <tr>
                                        <td><?php echo $i++?></td>
                                        <td><?php echo $fetch['ref_no']?></td>
                                        <td><?php echo $fetch['payee']?></td>
                                        <td><?php echo "KSh ".number_format($fetch['pay_amount'], 2)?></td>
                                        <td><?php echo "KSh ".number_format($fetch['withdrawal_fee'], 2)?></td>
                                        <td><?php echo date('M d, Y', strtotime($fetch['date_created']))?></td>
                                        <td><?php echo $fetch['status'] == 3 ? 'Completed' : 'In Progress'?></td>
                                        <td><?php echo $fetch['disbursed_by']?></td>
                                        <td>
                                            <button class="btn btn-warning btn-sm print-receipt" 
                                                    data-toggle="modal" 
                                                    data-target="#receiptModal"
                                                    data-ref-no="<?php echo $fetch['ref_no']?>"
                                                    data-payee="<?php echo $fetch['payee']?>"
                                                    data-amount="<?php echo $fetch['pay_amount']?>"
                                                    data-date="<?php echo $fetch['date_created']?>"
                                                    data-status="<?php echo $fetch['status'] == 3 ? 'Completed' : 'In Progress'?>"
                                                    data-disbursed-by="<?php echo $fetch['disbursed_by']?>">
                                                <i class="fas fa-print"></i> Print Receipt
                                            </button>
                                        </td>
                                    </tr>
                                <?php
                                    }
                                ?>
                            </tbody>
                        </table>

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

    <!-- Disburse Loan Modal-->
    <div class="modal fade" id="addModal" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="../controllers/save_payment.php">
                <div class="modal-content">
                    <div style="background-color: #51087E;" class="modal-header">
                        <h5 class="modal-title text-white">Loan Disbursement Form</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-xl-5 col-md-5">
                                <label>Reference no</label>
                                <br />
                                <select name="loan_id" class="ref_no" id="ref_no" required="required" style="width:100%;">
                                    <option value=""></option>
                                    <?php
                                        $tbl_loan=$db->display_loan();
                                        while($fetch=$tbl_loan->fetch_array()){
                                            if($fetch['status'] == 1){
                                    ?>
                                        <option value="<?php echo $fetch['loan_id']?>"><?php echo $fetch['ref_no']?></option>
                                    <?php
                                            }
                                        }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                            <label>Receipt Number</label>
                            <input type="text" name="receipt_no" class="form-control" required>
                        </div>
                        </div>
                        <div id="formField"></div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="save" class="btn btn-success">Disburse</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">Disbursement Receipt</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="receipt">
                        <div class="receipt-header">
                            <img src="../public/image/mylogo.png" alt="Lato Management System Logo">
                        </div>
                        <div class="receipt-details">
                            <p><strong>Loan Reference No:</strong> <span id="receiptRefNo"></span></p>
                            <p><strong>Payee:</strong> <span id="receiptPayee"></span></p>
                            <p><strong>Amount:</strong> KSh <span id="receiptAmount"></span></p>
                            <p><strong>Date:</strong> <span id="receiptDate"></span></p>
                            <p><strong>Status:</strong> <span id="receiptStatus"></span></p>
                            <p><strong>Disbursed By:</strong> <span id="receiptDisbursedBy"></span></p>
                        </div>
                        <div class="receipt-footer">
                            <p>Thank you for choosing Lato Sacco LTD</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning" onclick="printReceipt()">Print</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">System Information</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Are you sure you want to logout?</div>
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
    <script src="../public/js/select2.js"></script>

    <!-- Page level plugins -->
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../public/js/sb-admin-2.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable();
            $('#pendingLoansTable').DataTable();
            
            $('.ref_no').select2({
                placeholder: 'Select an option'
            });
            
            $('#ref_no').on('change', function(){
                if($('#ref_no').val()== ""){
                    $('#formField').empty();
                }else{
                    $('#formField').empty();
                    $('#formField').load("../helpers/get_field.php?loan_id="+$(this).val(), function() {
                        // Auto-fill the disbursement amount
                        var totalDisbursement = parseFloat($('#totalDisbursement').text().replace('KSh ', '').replace(',', ''));
                        $('input[name="disbursement"]').val(totalDisbursement.toFixed(2));
                    });
                }
            });


  

    // Handle quick filter changes
    // Handle initial state
    function handleCustomDateRange() {
        const filterValue = $('#quickFilter').val();
        const customDateRange = $('.custom-date-range');
        
        if (filterValue === 'custom') {
            customDateRange.show();
            // Make date fields required
            customDateRange.find('input[type="date"]').prop('required', true);
        } else {
            customDateRange.hide();
            // Remove required attribute and clear values
            customDateRange.find('input[type="date"]')
                         .prop('required', false)
                         .val('');
        }
    }

    // Set initial state
    handleCustomDateRange();

    // Handle filter changes
    $('#quickFilter').change(handleCustomDateRange);

    // Form validation
    $('#filterForm').submit(function(e) {
        if ($('#quickFilter').val() === 'custom') {
            const startDate = $('input[name="start_date"]').val();
            const endDate = $('input[name="end_date"]').val();
            
            if (!startDate || !endDate) {
                e.preventDefault();
                alert('Please select both start and end dates for custom range');
                return false;
            }
            
            if (startDate > endDate) {
                e.preventDefault();
                alert('Start date cannot be later than end date');
                return false;
            }
        }
    });

    // Add loading indicator
    $('#filterForm').on('submit', function() {
        $('body').append('<div class="loading-overlay"><div class="spinner"></div></div>');
    });




            $('.disburse-loan').click(function() {
                var loanId = $(this).data('loan-id');
                var refNo = $(this).data('ref-no');
                $('#ref_no').val(loanId).trigger('change');
            });

            $('.print-receipt').click(function() {
            $('#receiptRefNo').text($(this).data('ref-no'));
            $('#receiptPayee').text($(this).data('payee'));
            $('#receiptAmount').text($(this).data('amount').toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#receiptDate').text(new Date($(this).data('date')).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }));
            $('#receiptStatus').text($(this).data('status'));
            $('#receiptDisbursedBy').text($(this).data('disbursed-by'));
        });

            // Toggle the side navigation
            $("#sidebarToggleTop").on('click', function(e) {
                $("body").toggleClass("sidebar-toggled");
                $(".sidebar").toggleClass("toggled");
                if ($(".sidebar").hasClass("toggled")) {
                    $('.sidebar .collapse').collapse('hide');
                    $("#content-wrapper").css({"margin-left": "100px", "width": "calc(100% - 100px)"});
                    $(".topbar").css("left", "100px");
                } else {
                    $("#content-wrapper").css({"margin-left": "225px", "width": "calc(100% - 225px)"});
                    $(".topbar").css("left", "225px");
                }
            });

            // Close any open menu accordions when window is resized below 768px
            $(window).resize(function() {
                if ($(window).width() < 768) {
                    $('.sidebar .collapse').collapse('hide');
                };
                
                // Toggle the side navigation when window is resized below 480px
                if ($(window).width() < 480 && !$(".sidebar").hasClass("toggled")) {
                    $("body").addClass("sidebar-toggled");
                    $(".sidebar").addClass("toggled");
                    $('.sidebar .collapse').collapse('hide');
                };
            });
        });

        function generateReport() {
            window.location.href = '../controllers/generate_report.php';
        }

        function printReceipt() {
            var printContents = document.querySelector('.receipt').innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
        }
    </script>
</body>
</html>