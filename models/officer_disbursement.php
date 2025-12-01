<?php
    date_default_timezone_set("Africa/Nairobi");
    require_once '../helpers/session.php';
    require_once '../config/class.php';
    $db = new db_class(); 

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'officer')) {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: ../views/index.php');
    exit();
}

    // Initialize filter conditions
    $status_filter = isset($_GET['status']) && $_GET['status'] !== '' ? intval($_GET['status']) : null;
    
    // Build date filters for loans and payments separately
    $loan_date_where = "1=1";
    $payment_date_where = "1=1";
    $loan_date_params = array();
    $payment_date_params = array();
    $loan_date_types = "";
    $payment_date_types = "";

    // Handle date filters
    if (isset($_GET['quick_filter']) && !empty($_GET['quick_filter'])) {
        switch ($_GET['quick_filter']) {
            case 'today':
                $loan_date_where = "DATE(l.date_applied) = CURDATE()";
                $payment_date_where = "DATE(p.date_created) = CURDATE()";
                break;
            case 'yesterday':
                $loan_date_where = "DATE(l.date_applied) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                $payment_date_where = "DATE(p.date_created) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $loan_date_where = "YEARWEEK(l.date_applied, 1) = YEARWEEK(CURDATE(), 1)";
                $payment_date_where = "YEARWEEK(p.date_created, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $loan_date_where = "YEAR(l.date_applied) = YEAR(CURDATE()) AND MONTH(l.date_applied) = MONTH(CURDATE())";
                $payment_date_where = "YEAR(p.date_created) = YEAR(CURDATE()) AND MONTH(p.date_created) = MONTH(CURDATE())";
                break;
            case 'custom':
                $loan_parts = array();
                $payment_parts = array();
                if (!empty($_GET['start_date'])) {
                    $loan_parts[] = "DATE(l.date_applied) >= ?";
                    $payment_parts[] = "DATE(p.date_created) >= ?";
                    $loan_date_params[] = $_GET['start_date'];
                    $payment_date_params[] = $_GET['start_date'];
                    $loan_date_types .= "s";
                    $payment_date_types .= "s";
                }
                if (!empty($_GET['end_date'])) {
                    $loan_parts[] = "DATE(l.date_applied) <= ?";
                    $payment_parts[] = "DATE(p.date_created) <= ?";
                    $loan_date_params[] = $_GET['end_date'];
                    $payment_date_params[] = $_GET['end_date'];
                    $loan_date_types .= "s";
                    $payment_date_types .= "s";
                }
                if (!empty($loan_parts)) {
                    $loan_date_where = implode(' AND ', $loan_parts);
                    $payment_date_where = implode(' AND ', $payment_parts);
                }
                break;
        }
    }

    // OPTIMIZED SUMMARY QUERY - Separate calculations for better performance
    $summary_data = array(
        'total_disbursed_loans' => 0,
        'total_pending_amount' => 0,
        'total_disbursed_amount' => 0,
        'total_withdrawal_fees' => 0
    );

    // Query 1: Get pending loans total
    $pending_query = "SELECT COALESCE(SUM(l.amount), 0) as total_pending 
                      FROM loan l 
                      WHERE l.status = 1 AND {$loan_date_where}";
    if ($status_filter === null || $status_filter == 1) {
        $stmt = $db->conn->prepare($pending_query);
        if (!empty($loan_date_params)) {
            $stmt->bind_param($loan_date_types, ...$loan_date_params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $summary_data['total_pending_amount'] = $result['total_pending'];
    }

    // Query 2: Get disbursed loans data
    if ($status_filter === null || $status_filter != 1) {
        $disbursed_query = "SELECT 
            COUNT(DISTINCT p.loan_id) as total_loans,
            COALESCE(SUM(p.pay_amount), 0) as total_amount,
            COALESCE(SUM(p.withdrawal_fee), 0) as total_fees
            FROM payment p 
            INNER JOIN loan l ON p.loan_id = l.loan_id 
            WHERE {$payment_date_where}";
        
        $disbursed_params = $payment_date_params;
        $disbursed_types = $payment_date_types;
        
        if ($status_filter !== null && $status_filter != 1) {
            $disbursed_query .= " AND l.status = ?";
            $disbursed_params[] = $status_filter;
            $disbursed_types .= "i";
        }
        
        $stmt = $db->conn->prepare($disbursed_query);
        if (!empty($disbursed_params)) {
            $stmt->bind_param($disbursed_types, ...$disbursed_params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $summary_data['total_disbursed_loans'] = $result['total_loans'];
        $summary_data['total_disbursed_amount'] = $result['total_amount'];
        $summary_data['total_withdrawal_fees'] = $result['total_fees'];
    }

    // Get pending loans with optimized query
    $pending_loans_query = "SELECT l.loan_id, l.ref_no, l.amount, l.loan_term, l.monthly_payment,
                           c.first_name, c.last_name,
                           COALESCE(l.date_released, l.date_applied) as approval_date
                           FROM loan l 
                           INNER JOIN client_accounts c ON l.account_id = c.account_id 
                           WHERE l.status = 1";
    
    $pending_params = [];
    $pending_types = "";
    
    // Apply date filter to pending loans if no status filter or status is pending
    if ($status_filter === null || $status_filter == 1) {
        if ($loan_date_where !== "1=1") {
            $pending_loans_query .= " AND " . $loan_date_where;
            $pending_params = $loan_date_params;
            $pending_types = $loan_date_types;
        }
    } else {
        // If filtering by other status, show no pending loans
        $pending_loans_query .= " AND 1=0";
    }
    
    $pending_loans_query .= " ORDER BY l.loan_id DESC";
    $stmt = $db->conn->prepare($pending_loans_query);
    
    if (!empty($pending_params)) {
        $stmt->bind_param($pending_types, ...$pending_params);
    }
    
    $stmt->execute();
    $pending_loans = $stmt->get_result();

    // Get disbursed loans with optimized query
    $disbursed_loans_query = "SELECT p.payment_id, p.loan_id, p.payee, p.pay_amount, 
                             p.withdrawal_fee, p.date_created,
                             l.ref_no, l.status, l.loan_term, l.monthly_payment, 
                             u.username as disbursed_by 
                             FROM payment p 
                             INNER JOIN loan l ON p.loan_id = l.loan_id 
                             LEFT JOIN user u ON p.user_id = u.user_id
                             WHERE 1=1";
    
    $disbursed_params = [];
    $disbursed_types = "";
    
    // Apply status filter
    if ($status_filter === 1) {
        // If filtering for pending only, show no disbursed loans
        $disbursed_loans_query .= " AND 1=0";
    } else {
        if ($status_filter !== null) {
            $disbursed_loans_query .= " AND l.status = ?";
            $disbursed_params[] = $status_filter;
            $disbursed_types .= "i";
        }
        
        // Apply date filter to disbursed loans
        if ($payment_date_where !== "1=1") {
            $disbursed_loans_query .= " AND " . $payment_date_where;
            // Add payment date params to the end of disbursed params
            $disbursed_params = array_merge($disbursed_params, $payment_date_params);
            $disbursed_types .= $payment_date_types;
        }
    }
    
    $disbursed_loans_query .= " ORDER BY p.date_created DESC";
    $stmt = $db->conn->prepare($disbursed_loans_query);
    
    if (!empty($disbursed_params)) {
        $stmt->bind_param($disbursed_types, ...$disbursed_params);
    }
    
    $stmt->execute();
    $tbl_payment = $stmt->get_result();

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
            box-shadow: 
                0 20px 40px rgba(81, 8, 126, 0.15),
                0 10px 20px rgba(81, 8, 126, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        /* Background pattern overlay */
        .filtered-results-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Section header styling */
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

        /* Card container */
        .summary-cards-container {
            position: relative;
            z-index: 2;
        }

        /* Enhanced summary cards */
        .summary-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 16px;
            padding: 25px 20px;
            margin-bottom: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.1),
                0 4px 16px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* Card hover effects */
        .summary-card:hover {
            transform: translateY(-4px);
            background: rgba(255, 255, 255, 0.18);
            box-shadow: 
                0 16px 48px rgba(0, 0, 0, 0.15),
                0 8px 24px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.25);
        }

        /* Card shimmer effect */
        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.1),
                transparent
            );
            transition: left 0.6s;
        }

        .summary-card:hover::before {
            left: 100%;
        }

        /* Card headers */
        .summary-card h4 {
            margin: 0 0 15px 0;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.95);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        /* Card values */
        .summary-card p {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            line-height: 1.2;
        }

        /* Color-coded cards */
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

        /* Icon styling for cards */
        .summary-card-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 2.5rem;
            color: rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .summary-card:hover .summary-card-icon {
            color: rgba(255, 255, 255, 0.35);
            transform: scale(1.1) rotate(5deg);
        }

        /* Enhanced 2x2 Grid Layout */
        .summary-cards-container .row {
            margin-left: -10px;
            margin-right: -10px;
        }

        .summary-cards-container .col-lg-6 {
            padding-left: 10px;
            padding-right: 10px;
        }

        .summary-card {
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .summary-card small {
            margin-top: 8px;
            display: block;
            font-weight: 400;
        }

        /* Responsive adjustments for cards */
        @media (max-width: 991px) {
            .summary-card {
                min-height: 130px;
                margin-bottom: 15px;
            }
        }

        @media (max-width: 768px) {
            .summary-cards-container .col-md-6 {
                margin-bottom: 15px;
            }
            
            .summary-card {
                min-height: 120px;
            }
            
            .summary-card p {
                font-size: 1.4rem;
            }
            
            .filtered-results-section {
                padding: 20px 15px;
                border-radius: 12px;
            }
            
            .filtered-results-header {
                font-size: 1.3rem;
                margin-bottom: 20px;
            }

            .summary-card-icon {
                font-size: 2rem;
            }
        }

        /* Loading animation for cards */
        @keyframes cardFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .summary-card {
            animation: cardFadeIn 0.6s ease-out;
        }

        .summary-card:nth-child(1) { animation-delay: 0.1s; }
        .summary-card:nth-child(2) { animation-delay: 0.2s; }
        .summary-card:nth-child(3) { animation-delay: 0.3s; }
        .summary-card:nth-child(4) { animation-delay: 0.4s; }

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
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-overlay.active {
            display: flex;
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

        /* Toast container styles */
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

        /* Payment terms styling */
        .payment-terms {
            font-size: 0.85em;
            color: #495057;
        }
        .payment-terms .term-months {
            font-weight: bold;
            color: #007bff;
        }
        .payment-terms .monthly-amount {
            font-weight: bold;
            color: #28a745;
        }

        /* Total row styling */
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

        /* Delete button styling */
        .btn-delete-loan {
            background: linear-gradient(45deg, #dc3545, #c82333);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-delete-loan:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
            background: linear-gradient(45deg, #c82333, #bd2130);
        }

        /* Page loading optimization */
        .page-content {
            opacity: 0;
            transition: opacity 0.3s ease-in;
        }

        .page-content.loaded {
            opacity: 1;
        }
    </style>
</head>

<body id="page-top">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Page Wrapper -->
    <div id="wrapper">
         <!-- Import Sidebar -->
            <?php require_once '../components/includes/officer_sidebar.php'; ?>

                <!-- Toast Container -->
                <div class="toast-container" id="toastContainer">
                    <!-- Toasts will be inserted here -->
                </div>

                <!-- Begin Page Content -->
                <div class="container-fluid pt-4 page-content" id="pageContent">
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
                <a href="officer_disbursement.php" class="btn btn-secondary">Reset</a>
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
                    <!-- Row 1 -->
                    <div class="col-lg-6 col-md-6">
                        <div class="summary-card card-success">
                            <div class="summary-card-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h4>Total Disbursed Loans</h4>
                            <p><?= number_format($summary_data['total_disbursed_loans']) ?> Loans</p>
                            <small style="color: rgba(255,255,255,0.8); font-size: 0.75rem;">
                                <i class="fas fa-info-circle"></i> Count of disbursed loans
                            </small>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <div class="summary-card card-info">
                            <div class="summary-card-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <h4>Total Disbursed Amount</h4>
                            <p>KSh <?= number_format($summary_data['total_disbursed_amount'], 2) ?></p>
                            <small style="color: rgba(255,255,255,0.8); font-size: 0.75rem;">
                                <i class="fas fa-info-circle"></i> Sum of all disbursed amounts
                            </small>
                        </div>
                    </div>
                    
                    <!-- Row 2 -->
                    <div class="col-lg-6 col-md-6">
                        <div class="summary-card card-warning">
                            <div class="summary-card-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h4>Total Pending Loans</h4>
                            <p>KSh <?= number_format($summary_data['total_pending_amount'], 2) ?></p>
                            <small style="color: rgba(255,255,255,0.8); font-size: 0.75rem;">
                                <i class="fas fa-info-circle"></i> Awaiting disbursement
                            </small>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <div class="summary-card card-primary">
                            <div class="summary-card-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <h4>Total Withdrawal Fees</h4>
                            <p>KSh <?= number_format($summary_data['total_withdrawal_fees'], 2) ?></p>
                            <small style="color: rgba(255,255,255,0.8); font-size: 0.75rem;">
                                <i class="fas fa-info-circle"></i> Total fees collected
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

                    <!-- New Disbursement Button -->
                    <div class="row">
                        <button class="ml-3 mb-3 btn btn-lg btn-warning" href="#" data-toggle="modal" data-target="#addModal"><span class="fa fa-plus"></span> New Disbursement</button>
                    </div>

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
                                            <th>Date Approved</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
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
                                                    <div><span class="term-months"><?= $loan['loan_term'] ?> months</span></div>
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
                                        <?php endwhile; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="total-row">
                                            <td colspan="2" class="text-right"><strong>Total Pending:</strong></td>
                                            <td class="total-amount" id="pendingTotalAmount">KSh <?= number_format($pending_total, 2) ?></td>
                                            <td colspan="3" class="text-center">
                                                <span class="badge badge-warning" id="pendingCountBadge">
                                                    <?= $pending_count ?> loan(s) waiting
                                                </span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
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
                                    <th>Payment Terms</th>
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
                                                <div><span class="term-months"><?php echo $fetch['loan_term']?> months</span></div>
                                            </div>
                                        </td>
                                        <td><?php echo "KSh ".number_format($fetch['withdrawal_fee'], 2)?></td>
                                        <td><?php echo date('M d, Y', strtotime($fetch['date_created']))?></td>
                                        <td><?php echo $fetch['status'] == 2 ? 'Disbursed' : 'In Progress'?></td>
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
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="3" class="text-right"><strong>Total Disbursed:</strong></td>
                                    <td class="total-amount" id="disbursedTotalAmount">KSh <?= number_format($disbursed_total, 2) ?></td>
                                    <td colspan="6" class="text-center">
                                        <span class="badge badge-success" id="disbursedCountBadge">
                                            <?= $i-1 ?> loan(s) disbursed
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
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
            <form method="POST" action="../controllers/save_payment.php" id="disbursementForm">
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
                                        // Reset the result set for pending loans
                                        $pending_loans->data_seek(0);
                                        while($fetch=$pending_loans->fetch_array()){
                                    ?>
                                        <option value="<?php echo $fetch['loan_id']?>"><?php echo $fetch['ref_no']?></option>
                                    <?php
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
    <div class="modal fade" id="deleteLoanModal" tabindex="-1" role="dialog" aria-labelledby="deleteLoanModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteLoanModalLabel">
                        <i class="fas fa-exclamation-triangle"></i> Confirm Loan Deletion
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this loan application?</p>
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> This action cannot be undone!
                    </div>
                    <div id="deleteLoanDetails">
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
        // Show loading overlay function
        function showLoadingOverlay() {
            $('#loadingOverlay').addClass('active');
        }

        // Hide loading overlay function
        function hideLoadingOverlay() {
            $('#loadingOverlay').removeClass('active');
        }

        // Enhanced toast notification function - OUTSIDE document ready
        function showToast(message, type, title, duration = 5000) {
            var toastId = 'toast-' + Date.now();
            var iconClass = '';
            var titleText = title || '';
            
            switch(type) {
                case 'success':
                    iconClass = 'fas fa-check-circle';
                    titleText = titleText || 'Success';
                    break;
                case 'danger':
                case 'error':
                    iconClass = 'fas fa-exclamation-circle';
                    titleText = titleText || 'Error';
                    type = 'danger';
                    break;
                case 'warning':
                    iconClass = 'fas fa-exclamation-triangle';
                    titleText = titleText || 'Warning';
                    break;
                case 'info':
                    iconClass = 'fas fa-info-circle';
                    titleText = titleText || 'Information';
                    break;
            }
            
            var toastHtml = `
                <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="${duration}">
                    <div class="toast-header bg-${type} text-white">
                        <i class="${iconClass} mr-2"></i>
                        <strong class="mr-auto">${titleText}</strong>
                        <small class="text-light">${new Date().toLocaleTimeString()}</small>
                        <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            $('#toastContainer').append(toastHtml);
            $(`#${toastId}`).toast('show');
            
            $(`#${toastId}`).on('hidden.bs.toast', function () {
                $(this).remove();
            });
        }

        // Generate Report function - OUTSIDE document ready so it's globally accessible
        function generateReport() {
            var quickFilter = $('#quickFilter').val() || '';
            var startDate = $('input[name="start_date"]').val() || '';
            var endDate = $('input[name="end_date"]').val() || '';
            var status = $('select[name="status"]').val() || '';
            
            var params = [];
            
            if (quickFilter) {
                params.push('quick_filter=' + encodeURIComponent(quickFilter));
            }
            
            if (startDate) {
                params.push('start_date=' + encodeURIComponent(startDate));
            }
            
            if (endDate) {
                params.push('end_date=' + encodeURIComponent(endDate));
            }
            
            if (status) {
                params.push('status=' + encodeURIComponent(status));
            }
            
            var url = '../controllers/generate_report.php';
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            
            console.log('Generating report with URL:', url);
            
            var filterInfo = 'all records';
            if (quickFilter) {
                switch(quickFilter) {
                    case 'today': filterInfo = "today's records"; break;
                    case 'yesterday': filterInfo = "yesterday's records"; break;
                    case 'week': filterInfo = "this week's records"; break;
                    case 'month': filterInfo = "this month's records"; break;
                    case 'custom': filterInfo = "custom date range"; break;
                }
            }
            
            if (status) {
                var statusText = status == '1' ? 'pending loans only' : 'disbursed loans only';
                filterInfo += ' (' + statusText + ')';
            }
            
            showToast(`Generating comprehensive report for ${filterInfo}...`, 'info', 'Report Generation', 4000);
            
            window.open(url, '_blank');
        }

        // Print Receipt function - OUTSIDE document ready
        function printReceipt() {
            var printContents = document.querySelector('.receipt').innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            location.reload(); // Reload to restore page
        }

        // Document ready function
        $(document).ready(function() {
            // Hide loading overlay and show page content
            setTimeout(function() {
                hideLoadingOverlay();
                $('#pageContent').addClass('loaded');
            }, 100);

            // Initialize DataTables with optimized settings for performance
            var pendingTable = $('#pendingLoansTable').DataTable({
                "deferRender": true,
                "processing": false,
                "serverSide": false,
                "stateSave": false,
                "searching": true,
                "paging": true,
                "info": true,
                "lengthChange": true,
                "autoWidth": false,
                "footerCallback": function (row, data, start, end, display) {
                    var api = this.api();
                    var total = 0;
                    var count = 0;
                    
                    api.column(2, { page: 'current' }).data().each(function (value, index) {
                        var numValue = parseFloat(value.replace(/[^\d.-]/g, ''));
                        if (!isNaN(numValue)) {
                            total += numValue;
                            count++;
                        }
                    });
                    
                    $('#pendingTotalAmount').html('KSh ' + total.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                    $('#pendingCountBadge').html(count + ' loan(s) waiting');
                },
                "order": [[0, "asc"]],
                "pageLength": 10,
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
            });

            var disbursedTable = $('#dataTable').DataTable({
                "deferRender": true,
                "processing": false,
                "serverSide": false,
                "stateSave": false,
                "searching": true,
                "paging": true,
                "info": true,
                "lengthChange": true,
                "autoWidth": false,
                "footerCallback": function (row, data, start, end, display) {
                    var api = this.api();
                    var total = 0;
                    var count = 0;
                    
                    api.column(3, { page: 'current' }).data().each(function (value, index) {
                        var numValue = parseFloat(value.replace(/[^\d.-]/g, ''));
                        if (!isNaN(numValue)) {
                            total += numValue;
                            count++;
                        }
                    });
                    
                    $('#disbursedTotalAmount').html('KSh ' + total.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                    $('#disbursedCountBadge').html(count + ' loan(s) disbursed');
                },
                "order": [[0, "desc"]],
                "pageLength": 10,
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
            });
            
            $('.ref_no').select2({
                placeholder: 'Select an option',
                width: '100%'
            });

            // Update button text to indicate it respects filters
            function updateGenerateButtonText() {
                var quickFilter = $('#quickFilter').val();
                var status = $('select[name="status"]').val();
                var hasFilters = quickFilter || status || $('input[name="start_date"]').val() || $('input[name="end_date"]').val();
                
                var buttonText = hasFilters ? 
                    '<i class="fas fa-download fa-sm text-white-50"></i> Generate Filtered Report' : 
                    '<i class="fas fa-download fa-sm text-white-50"></i> Generate Report';
                    
                $('.btn-warning[onclick="generateReport()"]').html(buttonText);
            }

            // Update button text when filters change
            $('#quickFilter, select[name="status"], input[name="start_date"], input[name="end_date"]').on('change', function() {
                updateGenerateButtonText();
            });

            // Call on page load
            updateGenerateButtonText();
            
            $('#ref_no').on('change', function(){
                if($('#ref_no').val()== ""){
                    $('#formField').empty();
                }else{
                    $('#formField').empty();
                    $('#formField').load("../helpers/get_field.php?loan_id="+$(this).val(), function() {
                        var totalDisbursement = parseFloat($('#totalDisbursement').text().replace('KSh ', '').replace(',', ''));
                        $('input[name="disbursement"]').val(totalDisbursement.toFixed(2));
                    });
                }
            });

            // Fixed disburse button click handler - using event delegation
            $(document).on('click', '.disburse-loan', function() {
                var loanId = $(this).data('loan-id');
                var refNo = $(this).data('ref-no');
                var borrower = $(this).data('borrower');
                var amount = $(this).data('amount');
                
                // Pre-select the loan in the dropdown
                $('#ref_no').val(loanId).trigger('change');
                
                // Show the modal
                $('#addModal').modal('show');
                
                // Show info toast
                showToast(`Preparing disbursement for ${borrower} - ${refNo} (KSh ${amount.toLocaleString()})`, 'info', 'Disbursement Setup');
            });

            // DELETE LOAN FUNCTIONALITY
            var loanToDelete = null;

            // Handle delete button click
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

            // Handle confirm delete
            $('#confirmDeleteBtn').on('click', function() {
                if (!loanToDelete) return;

                var $btn = $(this);
                var originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');

                $.ajax({
                    url: '../controllers/delete_pending_loan.php',
                    type: 'POST',
                    data: {
                        loan_id: loanToDelete.id,
                        ajax: 1
                    },
                    dataType: 'json',
                    success: function(response) {
                        $btn.prop('disabled', false).html(originalHtml);
                        
                        if (response.success) {
                            $('#deleteLoanModal').modal('hide');
                            
                            showToast(
                                `Loan ${loanToDelete.refNo} for ${loanToDelete.borrower} has been successfully deleted.`,
                                'success',
                                'Loan Deleted',
                                5000
                            );

                            // Remove the row from table
                            $('#loan-row-' + loanToDelete.id).fadeOut(400, function() {
                                $(this).remove();
                                
                                // Update DataTable
                                pendingTable.row($(this)).remove().draw();
                                
                                // Reload page after a short delay to update totals
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            });

                            loanToDelete = null;
                        } else {
                            showToast(
                                response.message || 'Failed to delete loan. Please try again.',
                                'error',
                                'Delete Failed'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        $btn.prop('disabled', false).html(originalHtml);
                        
                        console.error('Delete Error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        
                        showToast(
                            'An error occurred while deleting the loan. Please try again.',
                            'error',
                            'Delete Failed'
                        );
                    }
                });
            });

            // Handle form submission with toast notifications
            $('#disbursementForm').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize() + '&save=1&ajax=1';
                var disburseBtn = $('#disburseBtn');
                var originalText = disburseBtn.html();
                
                var selectedOption = $('#ref_no option:selected');
                var refNo = selectedOption.text();
                var payeeName = $('input[name="payee"]').val() || 'Unknown';
                var amount = $('input[name="disbursement"]').val() || '0';
                
                disburseBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                
                $.ajax({
                    url: '../controllers/save_payment.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        disburseBtn.prop('disabled', false).html(originalText);
                        
                        if (response && response.success) {
                            $('#addModal').modal('hide');
                            showToast(
                                `Loan successfully disbursed to ${payeeName}! Amount: KSh ${parseFloat(amount).toLocaleString()} for Reference: ${refNo}`,
                                'success',
                                'Disbursement Successful',
                                7000
                            );
                            
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            var errorMessage = response && response.message ? response.message : 'Unknown error occurred during disbursement';
                            showToast(
                                `Failed to disburse loan: ${errorMessage}`,
                                'error',
                                'Disbursement Failed'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        disburseBtn.prop('disabled', false).html(originalText);
                        
                        console.log('AJAX Error Details:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText,
                            statusCode: xhr.status
                        });
                        
                        var errorMessage = 'Network error occurred. Please check your connection and try again.';
                        if (xhr.responseText) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                errorMessage = response.message || errorMessage;
                            } catch (e) {
                                console.log('Failed to parse JSON response:', xhr.responseText);
                            }
                        }
                        
                        showToast(errorMessage, 'error', 'Connection Error');
                    }
                });
            });

            // Handle quick filter changes
            function handleCustomDateRange() {
                const filterValue = $('#quickFilter').val();
                const customDateRange = $('.custom-date-range');
                
                if (filterValue === 'custom') {
                    customDateRange.show();
                    customDateRange.find('input[type="date"]').prop('required', true);
                } else {
                    customDateRange.hide();
                    customDateRange.find('input[type="date"]')
                                 .prop('required', false)
                                 .val('');
                }
            }

            handleCustomDateRange();
            $('#quickFilter').change(handleCustomDateRange);

            $('#filterForm').submit(function(e) {
                if ($('#quickFilter').val() === 'custom') {
                    const startDate = $('input[name="start_date"]').val();
                    const endDate = $('input[name="end_date"]').val();
                    
                    if (!startDate || !endDate) {
                        e.preventDefault();
                        showToast('Please select both start and end dates for custom range', 'warning', 'Validation Error');
                        return false;
                    }
                    
                    if (startDate > endDate) {
                        e.preventDefault();
                        showToast('Start date cannot be later than end date', 'warning', 'Validation Error');
                        return false;
                    }
                }
                
                // Show loading overlay when submitting filter
                showLoadingOverlay();
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

            $(window).resize(function() {
                if ($(window).width() < 768) {
                    $('.sidebar .collapse').collapse('hide');
                };
                
                if ($(window).width() < 480 && !$(".sidebar").hasClass("toggled")) {
                    $("body").addClass("sidebar-toggled");
                    $(".sidebar").addClass("toggled");
                    $('.sidebar .collapse').collapse('hide');
                };
            });

            // Show welcome toast
            showToast('Disbursement page loaded successfully. Ready to process loan disbursements.', 'info', 'Welcome', 3000);
        });
    </script>
</body>
</html>