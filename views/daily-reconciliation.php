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

    // Initialize all variables
    $total_group_savings = 0;
    $total_group_withdrawals = 0;
    $total_business_savings = 0;
    $total_business_withdrawals = 0;
    $total_payments = 0;
    $total_repayments = 0;
    $total_expenses = 0;
    $total_withdrawal_fees = 0;

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

    // Handle float reset
    if (isset($_POST['reset_float'])) {
        $user_id = $_SESSION['user_id'];
        $current_datetime = date('Y-m-d H:i:s');
        
        // Calculate current closing float before reset
        $closing_float = calculateCurrentClosingFloat($db);
        
        // Insert into float history with unique timestamp
        $history_query = "INSERT INTO float_history (date, closing_float, reset_by, created_at) 
                         VALUES (?, ?, ?, ?)";
        $stmt = $db->conn->prepare($history_query);
        $current_date = date('Y-m-d');
        $stmt->bind_param("sdis", $current_date, $closing_float, $user_id, $current_datetime);
        $stmt->execute();
        
        // Clear ALL float management records
        $reset_query = "DELETE FROM float_management";
        $db->conn->query($reset_query);
        
        $_SESSION['success_msg'] = "Float has been reset successfully! Closing amount of KSh " . number_format($closing_float, 2) . " saved to history.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Handle float transaction filtering
    $float_start_date = isset($_GET['float_start_date']) ? $_GET['float_start_date'] . " 00:00:00" : date('Y-m-d 00:00:00');
    $float_end_date = isset($_GET['float_end_date']) ? $_GET['float_end_date'] . " 23:59:59" : date('Y-m-d 23:59:59');
    $float_type = isset($_GET['float_type']) ? $_GET['float_type'] : 'all';

    // Get float totals for the filtered period (for float transactions display)
    $float_query = "SELECT 
                COALESCE(SUM(CASE WHEN type = 'add' THEN amount ELSE 0 END), 0) as total_added,
                COALESCE(SUM(CASE WHEN type = 'offload' THEN amount ELSE 0 END), 0) as total_offloaded
              FROM float_management 
              WHERE date_created BETWEEN ? AND ?";
    
    if ($float_type !== 'all') {
        $float_query .= " AND type = ?";
    }
    
    $stmt = $db->conn->prepare($float_query);
    if ($float_type !== 'all') {
        $stmt->bind_param("sss", $float_start_date, $float_end_date, $float_type);
    } else {
        $stmt->bind_param("ss", $float_start_date, $float_end_date);
    }
    $stmt->execute();
    $float_result = $stmt->get_result()->fetch_assoc();
    
    $filtered_opening_float = $float_result['total_added'];
    $filtered_total_offloaded = $float_result['total_offloaded'];

    // Get float transactions with filtering
    $float_transactions_query = "SELECT f.*, u.username as served_by 
              FROM float_management f 
              LEFT JOIN user u ON f.user_id = u.user_id 
              WHERE f.date_created BETWEEN ? AND ?";
    
    if ($float_type !== 'all') {
        $float_transactions_query .= " AND f.type = ?";
    }
    
    $float_transactions_query .= " ORDER BY f.date_created DESC";
    
    $stmt = $db->conn->prepare($float_transactions_query);
    if ($float_type !== 'all') {
        $stmt->bind_param("sss", $float_start_date, $float_end_date, $float_type);
    } else {
        $stmt->bind_param("ss", $float_start_date, $float_end_date);
    }
    $stmt->execute();
    $float_transactions = $stmt->get_result();

    // For current day float totals (this is what shows in the cards)
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
    $today_float_result = $stmt->get_result()->fetch_assoc();
    
    $opening_float = $today_float_result['total_added'];
    $total_offloaded = $today_float_result['total_offloaded'];

    // Handle transaction filtering for other transactions
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

    // Business Group Savings Query
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
    } else if ($row['type'] == 'Withdrawal Fee') {
        $total_withdrawal_fees += $row['amount'];
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
    if (isset($row['withdrawal_fee']) && $row['withdrawal_fee'] > 0) {
        $total_withdrawal_fees += $row['withdrawal_fee'];
    }
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
        if (isset($row['withdrawal_fee']) && $row['withdrawal_fee'] > 0) {
            $total_withdrawal_fees += $row['withdrawal_fee'];
        }
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
        if (isset($row['withdrawal_fee']) && $row['withdrawal_fee'] > 0) {
            $total_withdrawal_fees += $row['withdrawal_fee'];
        }
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
    $total_inflows = $total_group_savings + $total_business_savings + $total_repayments + $total_individual_savings + $total_withdrawal_fees;
    $total_outflows = $total_group_withdrawals + $total_business_withdrawals + $total_payments + $total_expenses + $total_individual_withdrawals;
    
    // Calculate closing float: Opening Float + Money In - Money Out - Total Offloaded
    $closing_float = $opening_float + $total_inflows - $total_outflows - $total_offloaded;
    
    // Recalculate net position
    $net_position = $total_inflows - $total_outflows;

    // Function to calculate current closing float
        function calculateCurrentClosingFloat($db) {
        // Get current day float data
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        
        $float_query = "SELECT 
                    COALESCE(SUM(CASE WHEN type = 'add' THEN amount ELSE 0 END), 0) as total_added,
                    COALESCE(SUM(CASE WHEN type = 'offload' THEN amount ELSE 0 END), 0) as total_offloaded
                  FROM float_management 
                  WHERE date_created BETWEEN ? AND ?";
        $stmt = $db->conn->prepare($float_query);
        $stmt->bind_param("ss", $today_start, $today_end);
        $stmt->execute();
        $float_result = $stmt->get_result()->fetch_assoc();
        
        $opening = $float_result['total_added'];
        $offloaded = $float_result['total_offloaded'];
        
        // Get today's transaction totals
        $total_money_in = 0;
        $total_money_out = 0;
        $total_fees = 0;
        
        // Group savings
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM group_savings WHERE date_saved BETWEEN ? AND ?";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("ss", $today_start, $today_end);
        $stmt->execute();
        $total_money_in += $stmt->get_result()->fetch_assoc()['total'];
        
        // Business savings
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM business_group_transactions WHERE type = 'Savings' AND date BETWEEN ? AND ?";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("ss", $today_start, $today_end);
        $stmt->execute();
        $total_money_in += $stmt->get_result()->fetch_assoc()['total'];
        
        // Individual savings
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM savings WHERE type = 'Savings' AND date BETWEEN ? AND ?";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("ss", $today_start, $today_end);
        $stmt->execute();
        $total_money_in += $stmt->get_result()->fetch_assoc()['total'];
        
        // Loan repayments
        $query = "SELECT COALESCE(SUM(amount_repaid), 0) as total FROM loan_repayments WHERE date_paid BETWEEN ? AND ?";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("ss", $today_start, $today_end);
        $stmt->execute();
        $total_money_in += $stmt->get_result()->fetch_assoc()['total'];
        
        // Calculate withdrawal fees from business transactions (Withdrawal Fee type)
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM business_group_transactions WHERE type = 'Withdrawal Fee' AND date BETWEEN ? AND ?";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("ss", $today_start, $today_end);
        $stmt->execute();
        $total_fees += $stmt->get_result()->fetch_assoc()['total'];
        
        // Add fees to money in (since fees are profit)
        $total_money_in += $total_fees;
        
        // Calculate money out - Group withdrawals
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM group_withdrawals WHERE date_withdrawn BETWEEN ? AND ?";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("ss", $today_start, $today_end);
        $stmt->execute();
        $total_money_out += $stmt->get_result()->fetch_assoc()['total'];
        
        // Business withdrawals (only withdrawal amounts, not fees)
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM business_group_transactions WHERE type = 'Withdrawal' AND date BETWEEN ? AND ?";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("ss", $today_start, $today_end);
        $stmt->execute();
        $total_money_out += $stmt->get_result()->fetch_assoc()['total'];
        
        // Individual withdrawals
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM savings WHERE type = 'Withdrawal' AND date BETWEEN ? AND ?";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("ss", $today_start, $today_end);
        $stmt->execute();
        $total_money_out += $stmt->get_result()->fetch_assoc()['total'];
        
        // Loan disbursements (payments)
        $query = "SELECT COALESCE(SUM(pay_amount), 0) as total FROM payment WHERE date_created BETWEEN ? AND ?";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("ss", $today_start, $today_end);
        $stmt->execute();
        $total_money_out += $stmt->get_result()->fetch_assoc()['total'];
        
        // Expenses
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE date BETWEEN ? AND ?";
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("ss", $today_start, $today_end);
        $stmt->execute();
        $total_money_out += $stmt->get_result()->fetch_assoc()['total'];
        
        // Return calculated closing float
        return $opening + $total_money_in - $total_money_out - $offloaded;
    }
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
        :root {
            --primary-color: #51087E;
        }

        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15);
            border: none;
            border-radius: 0.35rem;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.25rem 2rem 0 rgba(33, 40, 50, 0.2);
        }

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
            flex-wrap: wrap;
        }

        .action-buttons button {
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .page-title {
            color: #51087E;
            font-weight: 700;
            margin-bottom: 2rem;
        }

        .generate-report-btn {
            background: linear-gradient(135deg, #51087E 0%, #6a1b99 100%);
            border: none;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .generate-report-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(81, 8, 126, 0.3);
            color: white;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Include Sidebar and Header -->
        <?php include '../components/includes/sidebar.php'; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="page-title mb-0">Daily Reconciliation</h1>
                <button class="btn generate-report-btn" onclick="generateReport()">
                    <i class="fas fa-download fa-sm"></i> Generate Report
                </button>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success_msg'] ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success_msg']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_msg'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error_msg'] ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error_msg']); ?>
            <?php endif; ?>

            <!-- Float Management Component -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold" style="color: #51087E;">
                        Float Management - <?= date('M d, Y') ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="float-card">
                                <div class="float-title">Opening Float</div>
                                <div class="float-amount">KSh <?= number_format($opening_float, 2) ?></div>
                                <small class="text-muted">Today's added floats</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="float-card">
                                <div class="float-title">Total Offloaded</div>
                                <div class="float-amount">KSh <?= number_format($total_offloaded, 2) ?></div>
                                <small class="text-muted">Today's removed float</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="float-card">
                                <div class="float-title">Money In/Out Impact</div>
                                <div class="float-amount" style="color: <?= $net_position >= 0 ? '#28a745' : '#dc3545' ?>">
                                    KSh <?= number_format($net_position, 2) ?>
                                </div>
                                <small class="text-muted">
                                    In: <?= number_format($total_inflows - $total_withdrawal_fees, 2) ?> | 
                                    Out: <?= number_format($total_outflows, 2) ?> |
                                    Fee: <?= number_format($total_withdrawal_fees, 2) ?>
                                </small>
                            </div>
                        </div>
                        <div
                        <div class="col-md-3">
                            <div class="float-card">
                                <div class="float-title">Closing Float</div>
                                <div class="float-amount" id="closingFloatAmount" style="color: #51087E; font-weight: bold;">
                                    KSh <?= number_format($closing_float, 2) ?>
                                </div>
                                <button class="btn btn-info mt-3 w-100" onclick="showClosingFloatHistory()">
                                    <i class="fas fa-history"></i> View History
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Formula Display -->
                    <div class="alert alert-info mb-4">
                        <strong>Today's Closing Float Formula:</strong> 
                        Opening Float (<?= number_format($opening_float, 2) ?>) + 
                        Money In (<?= number_format($total_inflows - $total_withdrawal_fees, 2) ?>) + 
                        Fees (<?= number_format($total_withdrawal_fees, 2) ?>) - 
                        Money Out (<?= number_format($total_outflows, 2) ?>) - 
                        Total Offloaded (<?= number_format($total_offloaded, 2) ?>) = 
                        <strong>KSh <?= number_format($closing_float, 2) ?></strong>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button class="btn btn-success" data-toggle="modal" data-target="#addFloatModal">
                            <i class="fas fa-plus"></i> Add Float
                        </button>
                        <button class="btn btn-warning" data-toggle="modal" data-target="#offloadFloatModal">
                            <i class="fas fa-minus"></i> Offload Float
                        </button>
                        <button class="btn btn-danger" onclick="confirmResetFloat()">
                            <i class="fas fa-redo"></i> Reset Float
                        </button>
                    </div>
                </div>
            </div>

            <!-- Float Transactions Component -->
            <?php include '../components/reconciliation/float_transactions.php'; ?>

            <!-- Transactions Filter Component -->
            <?php include '../components/reconciliation/transactions_filter.php'; ?>

        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- End of Main Content -->

    <!-- Reset Float Confirmation Modal -->
    <div class="modal fade" id="resetFloatModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #dc3545;">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-exclamation-triangle"></i> Confirm Float Reset
                    </h5>
                    <button class="close text-white" type="button" data-dismiss="modal">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Warning!</strong> This action will:
                        <ul class="mt-2 mb-0">
                            <li>Save current closing float (KSh <?= number_format($closing_float, 2) ?>) to history</li>
                            <li>Reset ALL float transactions to start fresh</li>
                            <li>Set Opening Float, Total Offloaded, and Closing Float to 0</li>
                            <li>This action cannot be undone</li>
                        </ul>
                    </div>
                    <p><strong>Note:</strong> Float values persist until reset, allowing multi-day tracking if needed.</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="reset_float" class="btn btn-danger">
                            <i class="fas fa-redo"></i> Yes, Reset Float
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Closing Float History Modal -->
    <div class="modal fade" id="closingFloatHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #51087E;">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-history"></i> Closing Float History
                    </h5>
                    <button class="close text-white" type="button" data-dismiss="modal">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="historyContent">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p>Loading history...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Close</button>
                    <button class="btn btn-primary" onclick="exportFloatHistory()" style="background-color: #51087E;">
                        <i class="fas fa-download"></i> Export History
                    </button>
                </div>
            </div>
        </div>
    </div>

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
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Adding float increases today's opening float amount.
                        </div>
                        <div class="form-group">
                            <label>Receipt Number</label>
                            <input type="text" name="receipt_no" class="form-control" required 
                                   placeholder="Enter receipt number">
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">KSh</span>
                                </div>
                                <input type="number" step="0.01" name="amount" class="form-control" required 
                                       placeholder="0.00" min="0.01">
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
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Offloading reduces the available float amount.
                        </div>
                        <div class="form-group">
                            <label>Receipt Number</label>
                            <input type="text" name="receipt_no" class="form-control" required 
                                   placeholder="Enter receipt number">
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">KSh</span>
                                </div>
                                <input type="number" step="0.01" name="amount" class="form-control" required 
                                       placeholder="0.00" min="0.01" max="<?= $opening_float ?>">
                            </div>
                            <small class="text-muted">Available to offload: KSh <?= number_format($opening_float - $total_offloaded, 2) ?></small>
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

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
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

    <!-- Bootstrap core JavaScript-->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>
    <script src="../public/js/jquery.easing.js"></script>
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>
    <script src="../public/js/sb-admin-2.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTables and other functionality
        initializeDataTables();
        setupEventHandlers();
    });

    function initializeDataTables() {
        // Initialize DataTables for Money In
        if ($('#moneyInTable').length) {
            window.moneyInTable = $('#moneyInTable').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 10,
                "responsive": true,
                "footerCallback": function(row, data, start, end, display) {
                    var api = this.api();
                    var totalAmount = 0;
                    api.column(3, { page: 'current', search: 'applied' }).data().each(function(value) {
                        var numValue = parseFloat(value.toString().replace(/,/g, ''));
                        if (!isNaN(numValue)) {
                            totalAmount += numValue;
                        }
                    });
                    $(api.column(3).footer()).html('<strong>' + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2}) + '</strong>');
                }
            });
        }

        // Initialize DataTables for Money Out
        if ($('#moneyOutTable').length) {
            window.moneyOutTable = $('#moneyOutTable').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 10,
                "responsive": true,
                "footerCallback": function(row, data, start, end, display) {
                    var api = this.api();
                    var totalAmount = 0;
                    var totalFees = 0;
                    
                    api.column(3, { page: 'current', search: 'applied' }).data().each(function(value) {
                        var numValue = parseFloat(value.toString().replace(/,/g, ''));
                        if (!isNaN(numValue)) {
                            totalAmount += numValue;
                        }
                    });
                    
                    api.column(4, { page: 'current', search: 'applied' }).data().each(function(value) {
                        var numValue = parseFloat(value.toString().replace(/,/g, ''));
                        if (!isNaN(numValue)) {
                            totalFees += numValue;
                        }
                    });
                    
                    $(api.column(3).footer()).html('<strong>' + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2}) + '</strong>');
                    $(api.column(4).footer()).html('<strong>' + totalFees.toLocaleString('en-US', {minimumFractionDigits: 2}) + '</strong>');
                }
            });
        }

        // Initialize Float Transactions DataTable
        if ($('#floatTransactionsTable').length) {
            window.floatTable = $('#floatTransactionsTable').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 25,
                "responsive": true
            });
        }
    }

    function setupEventHandlers() {
        // Form submissions
        $('#transactionFilterForm, #floatFilterForm').on('submit', function(e) {
            showLoadingSpinner();
        });

        // Float form submissions
        $('#addFloatForm, #offloadFloatForm').on('submit', function() {
            showLoadingSpinner();
        });

        // Receipt printing
        $(document).on('click', '.print-receipt', function(e) {
            e.preventDefault();
            const data = $(this).data();
            
            $('#floatReceiptNo').text(data.receipt || 'N/A');
            $('#floatReceiptAmount').text('KSh ' + parseFloat(data.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
            $('#floatReceiptType').text(data.type || 'N/A');
            $('#floatReceiptDate').text(new Date(data.date).toLocaleString() || 'N/A');
            $('#floatReceiptServedBy').text(data.served || 'N/A');
            $('#printDate').text(new Date().toLocaleString());
            
            $('#floatReceiptModal').modal('show');
        });

        // Validation for offload amount
        $('#offloadFloatForm input[name="amount"]').on('input', function() {
            const maxAmount = parseFloat($(this).attr('max'));
            const currentAmount = parseFloat($(this).val());
            
            if (currentAmount > maxAmount) {
                $(this).addClass('is-invalid');
                $(this).siblings('.invalid-feedback').remove();
                $(this).after('<div class="invalid-feedback">Amount cannot exceed available float</div>');
            } else {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').remove();
            }
        });
    }

    // Float Management Functions
    function confirmResetFloat() {
        $('#resetFloatModal').modal('show');
    }

    function showClosingFloatHistory() {
        $('#closingFloatHistoryModal').modal('show');
        loadClosingFloatHistory();
    }

    function loadClosingFloatHistory() {
        $.ajax({
            url: '../controllers/get_float_history.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayFloatHistory(response.data, response.summary);
                } else {
                    $('#historyContent').html('<div class="alert alert-warning">Failed to load history: ' + response.message + '</div>');
                }
            },
            error: function() {
                $('#historyContent').html('<div class="alert alert-danger">Error loading float history. Please try again.</div>');
            }
        });
    }

    function displayFloatHistory(data, summary) {
        if (data.length === 0) {
            $('#historyContent').html('<div class="alert alert-info">No float history found.</div>');
            return;
        }

        let historyHtml = `
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead style="background-color: #51087E; color: white;">
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Closing Float (KSh)</th>
                            <th>Reset By</th>
                        </tr>
                    </thead>
                    <tbody>`;

        data.forEach(function(record) {
            historyHtml += `
                <tr>
                    <td>${record.formatted_date}</td>
                    <td>${record.formatted_time}</td>
                    <td class="text-right font-weight-bold">${record.closing_float_formatted}</td>
                    <td>${record.reset_by_full_name}</td>
                </tr>`;
        });

        historyHtml += `</tbody></table></div>`;

        // Add summary statistics
        if (summary && summary.total_resets > 0) {
            historyHtml += `
                <div class="mt-3">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h6>Total Resets</h6>
                                    <h4>${summary.total_resets}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6>Average Closing</h6>
                                    <h4>KSh ${summary.average_closing.toLocaleString('en-US', {minimumFractionDigits: 2})}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h6>Days Tracked</h6>
                                    <h4>${summary.unique_days}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h6>Highest Closing</h6>
                                    <h4>KSh ${summary.highest_closing.toLocaleString('en-US', {minimumFractionDigits: 2})}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h6>Lowest Closing</h6>
                                    <h4>KSh ${summary.lowest_closing.toLocaleString('en-US', {minimumFractionDigits: 2})}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
        }

        $('#historyContent').html(historyHtml);
    }

    function exportFloatHistory() {
        window.open('../controllers/export_float_history.php', '_blank');
    }

    function filterMoneyIn() {
        const searchTerm = $('#moneyInSearch').val();
        const transactionType = $('#moneyInType').val();
        
        var table = window.moneyInTable;
        
        $.fn.dataTable.ext.search = [];
        
        if (transactionType) {
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    if (settings.nTable.id !== 'moneyInTable') {
                        return true;
                    }
                    
                    var row = table.row(dataIndex).node();
                    return $(row).hasClass(transactionType);
                }
            );
        }
        
        if (searchTerm) {
            table.search(searchTerm);
        } else {
            table.search('');
        }
        
        table.draw();
        
        if (transactionType) {
            $.fn.dataTable.ext.search.pop();
        }
    }

    function filterMoneyOut() {
        const searchTerm = $('#moneyOutSearch').val();
        const transactionType = $('#moneyOutType').val();
        
        var table = window.moneyOutTable;
        
        $.fn.dataTable.ext.search = [];
        
        if (transactionType) {
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    if (settings.nTable.id !== 'moneyOutTable') {
                        return true;
                    }
                    
                    var row = table.row(dataIndex).node();
                    return $(row).hasClass(transactionType);
                }
            );
        }
        
        if (searchTerm) {
            table.search(searchTerm);
        } else {
            table.search('');
        }
        
        table.draw();
        
        if (transactionType) {
            $.fn.dataTable.ext.search.pop();
        }
    }

    function showLoadingSpinner() {
        $('.loading-overlay').remove();
        
        $('body').append(`
            <div class="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.9); display: flex; justify-content: center; align-items: center; z-index: 9999;">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        `);
        
        setTimeout(function() {
            $('.loading-overlay').fadeOut();
        }, 10000);
    }

    function generateReport() {
        const startDate = $('#transactionStartDate').val();
        const endDate = $('#transactionEndDate').val();
        window.location.href = `../controllers/generate_reconciliation_report.php?start_date=${startDate}&end_date=${endDate}`;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    function formatDateTime(dateTimeString) {
        const date = new Date(dateTimeString);
        return date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function printFloatReceipt() {
        var printContent = document.getElementById('floatReceiptContent').innerHTML;
        
        var printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Float Transaction Receipt</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .receipt { max-width: 400px; margin: 0 auto; }
                    .receipt-header { text-align: center; margin-bottom: 20px; }
                    .receipt-details table { width: 100%; }
                    .receipt-details td { padding: 5px; }
                    .receipt-footer { text-align: center; margin-top: 20px; }
                    hr { border: 1px solid #ccc; }
                    @media print {
                        body { margin: 0; }
                        .receipt { max-width: none; }
                    }
                </style>
            </head>
            <body>
                <div class="receipt">${printContent}</div>
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.print();
        printWindow.close();
        
        $('#floatReceiptModal').modal('hide');
    }
</script>

</body>
</html>