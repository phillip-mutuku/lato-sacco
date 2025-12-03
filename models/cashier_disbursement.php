<?php
    date_default_timezone_set("Africa/Nairobi");
    require_once '../helpers/session.php';
    require_once '../config/class.php';
    $db = new db_class(); 

    // Check if user is logged in and is either an admin or manager
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'cashier')) {
        $_SESSION['error_msg'] = "Unauthorized access";
        header('Location: ../views/index.php');
        exit();
    }

    // Initialize filter conditions
    $status_filter = isset($_GET['status']) && $_GET['status'] !== '' ? intval($_GET['status']) : null;
    
    // Build date filters - separate for pending and disbursed
    $pending_date_where = "1=1";
    $disbursed_date_where = "1=1";
    $date_params = array();
    $date_types = "";

    // Handle date filters
    if (!empty($_GET['start_date']) || !empty($_GET['end_date'])) {
        $pending_date_parts = array();
        $disbursed_date_parts = array();
        
        if (!empty($_GET['start_date'])) {
            // For pending loans, use date_applied
            $pending_date_parts[] = "DATE(l.date_applied) >= ?";
            // For disbursed loans, use payment date_created
            $disbursed_date_parts[] = "DATE(p.date_created) >= ?";
            $date_params[] = $_GET['start_date'];
            $date_types .= "s";
        }
        
        if (!empty($_GET['end_date'])) {
            // For pending loans, use date_applied
            $pending_date_parts[] = "DATE(l.date_applied) <= ?";
            // For disbursed loans, use payment date_created
            $disbursed_date_parts[] = "DATE(p.date_created) <= ?";
            $date_params[] = $_GET['end_date'];
            $date_types .= "s";
        }
        
        if (!empty($pending_date_parts)) {
            $pending_date_where = implode(' AND ', $pending_date_parts);
        }
        
        if (!empty($disbursed_date_parts)) {
            $disbursed_date_where = implode(' AND ', $disbursed_date_parts);
        }
    }

    // OPTIMIZED SUMMARY QUERY
    $summary_data = array(
        'total_disbursed_loans' => 0,
        'total_pending_amount' => 0,
        'total_disbursed_amount' => 0,
        'total_withdrawal_fees' => 0
    );

    // Query 1: Get pending loans total - only if showing pending
    if ($status_filter === null || $status_filter == 1) {
        $pending_query = "SELECT COALESCE(SUM(l.amount), 0) as total_pending 
                          FROM loan l 
                          WHERE l.status = 1 AND {$pending_date_where}";
        
        $stmt = $db->conn->prepare($pending_query);
        if (!empty($date_params)) {
            $stmt->bind_param($date_types, ...$date_params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $summary_data['total_pending_amount'] = $result['total_pending'];
        $stmt->close();
    }

    // Query 2: Get disbursed loans data - only if showing disbursed
    if ($status_filter === null || $status_filter == 3) {
        $disbursed_query = "SELECT 
            COUNT(DISTINCT p.loan_id) as total_loans,
            COALESCE(SUM(p.pay_amount), 0) as total_amount,
            COALESCE(SUM(p.withdrawal_fee), 0) as total_fees
            FROM payment p 
            INNER JOIN loan l ON p.loan_id = l.loan_id 
            WHERE l.status = 3 AND {$disbursed_date_where}";
        
        $stmt = $db->conn->prepare($disbursed_query);
        if (!empty($date_params)) {
            $stmt->bind_param($date_types, ...$date_params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $summary_data['total_disbursed_loans'] = $result['total_loans'];
        $summary_data['total_disbursed_amount'] = $result['total_amount'];
        $summary_data['total_withdrawal_fees'] = $result['total_fees'];
        $stmt->close();
    }

    // Always get pending loans for the modal dropdown
    $pending_loans_for_modal = null;
    $pending_loans_for_modal_query = "SELECT l.loan_id, l.ref_no
                                      FROM loan l 
                                      WHERE l.status = 1
                                      ORDER BY l.loan_id DESC";
    $stmt = $db->conn->prepare($pending_loans_for_modal_query);
    $stmt->execute();
    $pending_loans_for_modal = $stmt->get_result();

    // Get pending loans for table display - only if needed
    $pending_loans = null;
    if ($status_filter === null || $status_filter == 1) {
        $pending_loans_query = "SELECT l.loan_id, l.ref_no, l.amount, l.loan_term, l.monthly_payment,
                               c.first_name, c.last_name,
                               l.date_applied as approval_date
                               FROM loan l 
                               INNER JOIN client_accounts c ON l.account_id = c.account_id 
                               WHERE l.status = 1 AND {$pending_date_where}
                               ORDER BY l.loan_id DESC";
        
        $stmt = $db->conn->prepare($pending_loans_query);
        if (!empty($date_params)) {
            $stmt->bind_param($date_types, ...$date_params);
        }
        $stmt->execute();
        $pending_loans = $stmt->get_result();
    }

    // Get disbursed loans - only if needed
    $tbl_payment = null;
    if ($status_filter === null || $status_filter == 3) {
        $disbursed_loans_query = "SELECT p.payment_id, p.loan_id, p.payee, p.pay_amount, 
                                 p.withdrawal_fee, p.date_created,
                                 l.ref_no, l.status, l.loan_term, l.monthly_payment, 
                                 u.username as disbursed_by 
                                 FROM payment p 
                                 INNER JOIN loan l ON p.loan_id = l.loan_id 
                                 LEFT JOIN user u ON p.user_id = u.user_id
                                 WHERE l.status = 3 AND {$disbursed_date_where}
                                 ORDER BY p.date_created DESC";
        
        $stmt = $db->conn->prepare($disbursed_loans_query);
        if (!empty($date_params)) {
            $stmt->bind_param($date_types, ...$date_params);
        }
        $stmt->execute();
        $tbl_payment = $stmt->get_result();
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Lato Management System - Disbursement</title>

    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    <link href="../public/css/select2.css" rel="stylesheet">

    <style>
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button{ 
            -webkit-appearance: none; 
        }
        
        .container-fluid .card{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }
        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        
        /* Enhanced Filtered Results Summary Styling */
        .filtered-results-section {
            background: linear-gradient(135deg, #51087E 0%, #6B46C1 50%, #8B5CF6 100%);
            border-radius: 16px;
            padding: 30px 20px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(81, 8, 126, 0.15);
        }

        .filtered-results-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .filtered-results-header {
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 25px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 2;
        }

        .summary-cards-container {
            position: relative;
            z-index: 2;
        }

        .summary-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 16px;
            padding: 25px 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-height: 140px;
        }

        .summary-card:hover {
            transform: translateY(-4px);
            background: rgba(255, 255, 255, 0.18);
        }

        .summary-card h4 {
            margin: 0 0 15px 0;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.95);
        }

        .summary-card p {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: #ffffff;
            line-height: 1.2;
        }

        .summary-card.card-success {
            background: rgba(28, 200, 138, 0.15);
            border-left: 4px solid #1cc88a;
        }

        .summary-card.card-warning {
            background: rgba(246, 194, 62, 0.15);
            border-left: 4px solid #f6c23e;
        }

        .summary-card.card-info {
            background: rgba(54, 185, 204, 0.15);
            border-left: 4px solid #36b9cc;
        }

        .summary-card.card-primary {
            background: rgba(78, 115, 223, 0.15);
            border-left: 4px solid #4e73df;
        }

        .summary-card-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 2.5rem;
            color: rgba(255, 255, 255, 0.2);
        }

        .summary-card small {
            margin-top: 8px;
            display: block;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.75rem;
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

        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1055;
            max-width: 350px;
        }
        
        .toast {
            min-width: 300px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .toast-header {
            color: #fff;
            border-bottom: none;
            font-weight: 600;
        }
        
        .toast-header.bg-success {
            background: linear-gradient(45deg, #28a745, #20c997) !important;
        }
        
        .toast-header.bg-danger {
            background: linear-gradient(45deg, #dc3545, #e74c3c) !important;
        }
        
        .toast-header.bg-warning {
            background: linear-gradient(45deg, #ffc107, #fd7e14) !important;
        }
        
        .toast-body {
            padding: 1rem;
            color: #495057;
            font-size: 0.9rem;
        }

        .payment-terms {
            font-size: 0.85em;
            color: #495057;
        }
        .payment-terms .term-months {
            font-weight: bold;
            color: #007bff;
        }

        .total-row {
            background-color: #f8f9fa !important;
            font-weight: bold;
            border-top: 2px solid #007bff !important;
        }
        .total-amount {
            color: #007bff;
            font-size: 1.1em;
            font-weight: bold;
        }

        .btn-delete-loan {
            background: linear-gradient(45deg, #dc3545, #c82333);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-delete-loan:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        @media (max-width: 768px) {
            .summary-card {
                min-height: 120px;
            }
            
            .summary-card p {
                font-size: 1.4rem;
            }
            
            .filtered-results-section {
                padding: 20px 15px;
            }
            
            .filtered-results-header {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
         <!-- Import Sidebar -->
            <?php require_once '../components/includes/cashier_sidebar.php'; ?>

                <!-- Toast Container -->
                <div class="toast-container" id="toastContainer"></div>

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
                <label>Start Date</label>
                <input type="date" class="form-control" name="start_date" id="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
            </div>
            
            <div class="col-md-3">
                <label>End Date</label>
                <input type="date" class="form-control" name="end_date" id="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
            </div>
            
            <div class="col-md-3">
                <label>Status</label>
                <select class="form-control" name="status" id="status">
                    <option value="">All Status</option>
                    <option value="1" <?php echo isset($_GET['status']) && $_GET['status'] == '1' ? 'selected' : ''; ?>>Pending Disbursement</option>
                    <option value="3" <?php echo isset($_GET['status']) && $_GET['status'] == '3' ? 'selected' : ''; ?>>Disbursed</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <button type="submit" class="btn btn-warning btn-block">Apply Filter</button>
                <a href="cashier_disbursement.php" class="btn btn-secondary btn-block mt-2">Reset</a>
            </div>
        </form>
    </div>
</div>

 <!--Filtered results cards - 2x2 GRID-->
<div class="row mb-4">
    <div class="col-12">
        <div class="filtered-results-section">
            <h6 class="filtered-results-header">
                <i class="fas fa-chart-bar mr-2"></i>
                Filtered Results Summary
            </h6>
            <div class="summary-cards-container">
                <div class="row">
                    <div class="col-lg-6 col-md-6">
                        <div class="summary-card card-success">
                            <div class="summary-card-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h4>Total Disbursed Loans</h4>
                            <p><?= number_format($summary_data['total_disbursed_loans']) ?> Loans</p>
                            <small><i class="fas fa-info-circle"></i> Count of disbursed loans</small>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <div class="summary-card card-info">
                            <div class="summary-card-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <h4>Total Disbursed Amount</h4>
                            <p>KSh <?= number_format($summary_data['total_disbursed_amount'], 2) ?></p>
                            <small><i class="fas fa-info-circle"></i> Sum of all disbursed amounts</small>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 col-md-6">
                        <div class="summary-card card-warning">
                            <div class="summary-card-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h4>Total Pending Loans</h4>
                            <p>KSh <?= number_format($summary_data['total_pending_amount'], 2) ?></p>
                            <small><i class="fas fa-info-circle"></i> Awaiting disbursement</small>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <div class="summary-card card-primary">
                            <div class="summary-card-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <h4>Total Withdrawal Fees</h4>
                            <p>KSh <?= number_format($summary_data['total_withdrawal_fees'], 2) ?></p>
                            <small><i class="fas fa-info-circle"></i> Total fees collected</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

                    <!-- New Disbursement Button -->
                    <div class="row">
                        <button class="ml-3 mb-3 btn btn-lg btn-warning" data-toggle="modal" data-target="#addModal"><span class="fa fa-plus"></span> New Disbursement</button>
                    </div>

                    <?php if ($status_filter === null || $status_filter == 1): ?>
                    <!-- Loans Waiting for Disbursement -->
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <h6 style="color: #51087E;" class="m-0 font-weight-bold">Loans Waiting for Disbursement</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="pendingLoansTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Ref No</th>
                                            <th>Borrower</th>
                                            <th>Amount</th>
                                            <th>Payment Terms</th>
                                            <th>Date Applied</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($pending_loans && $pending_loans->num_rows > 0):
                                            $pending_total = 0;
                                            $pending_count = 0;
                                            while($loan = $pending_loans->fetch_assoc()): 
                                                $pending_total += $loan['amount'];
                                                $pending_count++;
                                        ?>
                                        <tr id="loan-row-<?= $loan['loan_id'] ?>">
                                            <td><?= $loan['ref_no'] ?></td>
                                            <td><?= $loan['first_name'] . ' ' . $loan['last_name'] ?></td>
                                            <td class="loan-amount">KSh <?= number_format($loan['amount'], 2) ?></td>
                                            <td>
                                                <div class="payment-terms">
                                                    <span class="term-months"><?= $loan['loan_term'] ?> months</span>
                                                </div>
                                            </td>
                                            <td><?= $loan['approval_date'] ? date('M d, Y', strtotime($loan['approval_date'])) : 'Not set' ?></td>
                                            <td>
                                                <button class="btn btn-success btn-sm disburse-loan mb-1" 
                                                        data-loan-id="<?= $loan['loan_id'] ?>" 
                                                        data-ref-no="<?= $loan['ref_no'] ?>"
                                                        data-borrower="<?= $loan['first_name'] . ' ' . $loan['last_name'] ?>"
                                                        data-amount="<?= $loan['amount'] ?>">
                                                    <i class="fas fa-money-bill-wave"></i> Disburse
                                                </button>
                                                <button class="btn btn-danger btn-sm btn-delete-loan mb-1" 
                                                        data-loan-id="<?= $loan['loan_id'] ?>" 
                                                        data-ref-no="<?= $loan['ref_no'] ?>"
                                                        data-borrower="<?= $loan['first_name'] . ' ' . $loan['last_name'] ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                            $pending_total = 0;
                                            $pending_count = 0;
                                        endif;
                                        ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="total-row">
                                            <td colspan="2" class="text-right"><strong>Total Pending:</strong></td>
                                            <td class="total-amount">KSh <?= number_format($pending_total, 2) ?></td>
                                            <td colspan="3" class="text-center">
                                                <span class="badge badge-warning">
                                                    <?= $pending_count ?> loan(s) waiting
                                                </span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($status_filter === null || $status_filter == 3): ?>
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
                                    <th>Payment Terms</th>
                                    <th>Withdrawal Fee</th>
                                    <th>Disbursement Date</th>
                                    <th>Status</th>
                                    <th>Disbursed By</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    if ($tbl_payment && $tbl_payment->num_rows > 0):
                                        $i=1;
                                        $disbursed_total = 0;
                                        while($fetch=$tbl_payment->fetch_array()){
                                            $disbursed_total += $fetch['pay_amount'];
                                ?>
                                    <tr>
                                        <td><?php echo $i++?></td>
                                        <td><?php echo $fetch['ref_no']?></td>
                                        <td><?php echo $fetch['payee']?></td>
                                        <td class="disbursed-amount"><?php echo "KSh ".number_format($fetch['pay_amount'], 2)?></td>
                                        <td>
                                            <div class="payment-terms">
                                                <span class="term-months"><?php echo $fetch['loan_term']?> months</span>
                                            </div>
                                        </td>
                                        <td><?php echo "KSh ".number_format($fetch['withdrawal_fee'], 2)?></td>
                                        <td><?php echo date('M d, Y', strtotime($fetch['date_created']))?></td>
                                        <td><?php echo $fetch['status'] == 3 ? 'Disbursed' : 'In Progress'?></td>
                                        <td><?php echo $fetch['disbursed_by']?></td>
                                        <td>
                                            <button class="btn btn-warning btn-sm print-receipt" 
                                                    data-toggle="modal" 
                                                    data-target="#receiptModal"
                                                    data-ref-no="<?php echo $fetch['ref_no']?>"
                                                    data-payee="<?php echo $fetch['payee']?>"
                                                    data-amount="<?php echo $fetch['pay_amount']?>"
                                                    data-date="<?php echo $fetch['date_created']?>"
                                                    data-status="<?php echo $fetch['status'] == 3 ? 'Disbursed' : 'In Progress'?>"
                                                    data-disbursed-by="<?php echo $fetch['disbursed_by']?>">
                                                <i class="fas fa-print"></i> Print Receipt
                                            </button>
                                        </td>
                                    </tr>
                                <?php
                                        }
                                    else:
                                        $i=1;
                                        $disbursed_total = 0;
                                    endif;
                                ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="3" class="text-right"><strong>Total Disbursed:</strong></td>
                                    <td class="total-amount">KSh <?= number_format($disbursed_total, 2) ?></td>
                                    <td colspan="6" class="text-center">
                                        <span class="badge badge-success">
                                            <?= $i-1 ?> loan(s) disbursed
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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
        </div>
    </div>

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
    </a>

    <!-- Disburse Loan Modal-->
    <div class="modal fade" id="addModal" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="../controllers/cashier_save_payment.php" id="disbursementForm">
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
                                        if ($pending_loans_for_modal && $pending_loans_for_modal->num_rows > 0) {
                                            while($fetch=$pending_loans_for_modal->fetch_array()){
                                    ?>
                                        <option value="<?php echo $fetch['loan_id']?>"><?php echo $fetch['ref_no']?></option>
                                    <?php
                                            }
                                        }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group col-xl-7 col-md-7">
                                <label>Receipt Number</label>
                                <input type="text" name="receipt_no" class="form-control" required>
                            </div>
                        </div>
                        <div id="formField"></div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="save" class="btn btn-success" id="disburseBtn">
                            <i class="fas fa-money-bill-wave"></i> Disburse
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteLoanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle"></i> Confirm Loan Deletion
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this loan application?</p>
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> This action cannot be undone!
                    </div>
                    <div>
                        <p><strong>Loan Reference:</strong> <span id="deleteRefNo"></span></p>
                        <p><strong>Borrower:</strong> <span id="deleteBorrower"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash"></i> Yes, Delete Loan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Disbursement Receipt</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
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
                    <button class="close" type="button" data-dismiss="modal">
                        <span>×</span>
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

    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>
    <script src="../public/js/jquery.easing.js"></script>
    <script src="../public/js/select2.js"></script>
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>
    <script src="../public/js/sb-admin-2.js"></script>
    
    <script>
        function showToast(message, type, title, duration = 5000) {
            var toastId = 'toast-' + Date.now();
            var iconClass = type === 'success' ? 'fas fa-check-circle' : 
                           type === 'error' || type === 'danger' ? 'fas fa-exclamation-circle' : 
                           type === 'warning' ? 'fas fa-exclamation-triangle' : 'fas fa-info-circle';
            var titleText = title || (type === 'success' ? 'Success' : type === 'error' || type === 'danger' ? 'Error' : type === 'warning' ? 'Warning' : 'Information');
            type = type === 'error' ? 'danger' : type;
            
            var toastHtml = `
                <div id="${toastId}" class="toast" data-delay="${duration}">
                    <div class="toast-header bg-${type} text-white">
                        <i class="${iconClass} mr-2"></i>
                        <strong class="mr-auto">${titleText}</strong>
                        <small class="text-light">${new Date().toLocaleTimeString()}</small>
                        <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="toast-body">${message}</div>
                </div>
            `;
            
            $('#toastContainer').append(toastHtml);
            $(`#${toastId}`).toast('show').on('hidden.bs.toast', function () {
                $(this).remove();
            });
        }

        function generateReport() {
            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();
            var status = $('#status').val();
            var params = [];
            
            if (startDate) params.push('start_date=' + encodeURIComponent(startDate));
            if (endDate) params.push('end_date=' + encodeURIComponent(endDate));
            if (status) params.push('status=' + encodeURIComponent(status));
            
            var url = '../controllers/generate_report.php' + (params.length > 0 ? '?' + params.join('&') : '');
            showToast('Generating report...', 'info', 'Report Generation', 3000);
            window.open(url, '_blank');
        }

        function printReceipt() {
            var printContents = document.querySelector('.receipt').innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            location.reload();
        }

        $(document).ready(function() {
            // Initialize DataTables only if tables exist
            <?php if ($status_filter === null || $status_filter == 1): ?>
            $('#pendingLoansTable').DataTable({
                "pageLength": 10,
                "order": [[0, "asc"]]
            });
            <?php endif; ?>

            <?php if ($status_filter === null || $status_filter == 3): ?>
            $('#dataTable').DataTable({
                "pageLength": 10,
                "order": [[0, "desc"]]
            });
            <?php endif; ?>
            
            $('.ref_no').select2({
                placeholder: 'Select an option',
                width: '100%'
            });
            
            $('#ref_no').on('change', function(){
                if($('#ref_no').val() == ""){
                    $('#formField').empty();
                }else{
                    $('#formField').load("../helpers/get_field.php?loan_id="+$(this).val(), function() {
                        var totalDisbursement = parseFloat($('#totalDisbursement').text().replace('KSh ', '').replace(',', ''));
                        $('input[name="disbursement"]').val(totalDisbursement.toFixed(2));
                    });
                }
            });

            $(document).on('click', '.disburse-loan', function() {
                var loanId = $(this).data('loan-id');
                $('#ref_no').val(loanId).trigger('change');
                $('#addModal').modal('show');
                showToast('Preparing disbursement...', 'info', 'Disbursement Setup');
            });

            var loanToDelete = null;

            $(document).on('click', '.btn-delete-loan', function() {
                loanToDelete = {
                    id: $(this).data('loan-id'),
                    refNo: $(this).data('ref-no'),
                    borrower: $(this).data('borrower')
                };
                $('#deleteRefNo').text(loanToDelete.refNo);
                $('#deleteBorrower').text(loanToDelete.borrower);
                $('#deleteLoanModal').modal('show');
            });

            $('#confirmDeleteBtn').on('click', function() {
                if (!loanToDelete) return;
                var $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');

                $.ajax({
                    url: '../controllers/delete_pending_loan.php',
                    type: 'POST',
                    data: { loan_id: loanToDelete.id, ajax: 1 },
                    dataType: 'json',
                    success: function(response) {
                        $btn.prop('disabled', false).html('<i class="fas fa-trash"></i> Yes, Delete Loan');
                        if (response.success) {
                            $('#deleteLoanModal').modal('hide');
                            showToast('Loan has been successfully deleted.', 'success', 'Loan Deleted', 5000);
                            setTimeout(function() { location.reload(); }, 1500);
                        } else {
                            showToast(response.message || 'Failed to delete loan.', 'error', 'Delete Failed');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).html('<i class="fas fa-trash"></i> Yes, Delete Loan');
                        showToast('An error occurred.', 'error', 'Delete Failed');
                    }
                });
            });

            $('#disbursementForm').on('submit', function(e) {
                e.preventDefault();
                var disburseBtn = $('#disburseBtn');
                disburseBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                
                $.ajax({
                    url: '../controllers/save_payment.php',
                    type: 'POST',
                    data: $(this).serialize() + '&save=1&ajax=1',
                    dataType: 'json',
                    success: function(response) {
                        disburseBtn.prop('disabled', false).html('<i class="fas fa-money-bill-wave"></i> Disburse');
                        if (response && response.success) {
                            $('#addModal').modal('hide');
                            showToast('Loan successfully disbursed!', 'success', 'Disbursement Successful', 5000);
                            setTimeout(function() { location.reload(); }, 2000);
                        } else {
                            showToast('Failed to disburse loan: ' + (response.message || 'Unknown error'), 'error', 'Disbursement Failed');
                        }
                    },
                    error: function() {
                        disburseBtn.prop('disabled', false).html('<i class="fas fa-money-bill-wave"></i> Disburse');
                        showToast('Network error occurred.', 'error', 'Connection Error');
                    }
                });
            });

            $('.print-receipt').click(function() {
                $('#receiptRefNo').text($(this).data('ref-no'));
                $('#receiptPayee').text($(this).data('payee'));
                $('#receiptAmount').text(parseFloat($(this).data('amount')).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#receiptDate').text(new Date($(this).data('date')).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }));
                $('#receiptStatus').text($(this).data('status'));
                $('#receiptDisbursedBy').text($(this).data('disbursed-by'));
            });

            $("#sidebarToggleTop").on('click', function() {
                $("body").toggleClass("sidebar-toggled");
                $(".sidebar").toggleClass("toggled");
            });
        });
    </script>
</body>
</html>