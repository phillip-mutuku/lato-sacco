<?php
    date_default_timezone_set("Africa/Nairobi");
    require_once '../helpers/session.php';
    require_once '../config/class.php';
    $db = new db_class(); 

    // Check if user is logged in and is either an admin or manager
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
        $_SESSION['error_msg'] = "Unauthorized access";
        header('Location: ../views/index.php');
        exit();
    }

    // Check if user is admin for delete functionality
    $is_admin = ($_SESSION['role'] === 'admin');

    // Get filter parameters
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

    // Set default date range if not provided (current month)
    if (empty($start_date)) {
        $start_date = date('Y-m-01');
    }
    if (empty($end_date)) {
        $end_date = date('Y-m-t');
    }

    // Function to update loan schedules and calculate defaults properly
    function updateLoanSchedules($db) {
        // Get only DISBURSED loans (status >= 2)
        $loans_query = "SELECT l.loan_id, l.amount, l.loan_term, l.meeting_date, l.date_created, lp.interest_rate, l.status
                        FROM loan l
                        JOIN loan_products lp ON l.loan_product_id = lp.id
                        WHERE l.status >= 2"; // Only disbursed loans
        
        $loans_result = $db->conn->query($loans_query);
        
        if ($loans_result && $loans_result->num_rows > 0) {
            while ($loan = $loans_result->fetch_assoc()) {
                $loan_id = $loan['loan_id'];
                $total_amount = floatval($loan['amount']);
                $term = intval($loan['loan_term']);
                $interest_rate = floatval($loan['interest_rate']);
                $monthly_principal = round($total_amount / $term, 2);

                // Get existing repayments
                $repayment_query = "SELECT due_date, repaid_amount, paid_date, status FROM loan_schedule WHERE loan_id = ?";
                $repayment_stmt = $db->conn->prepare($repayment_query);
                $repayment_stmt->bind_param("i", $loan_id);
                $repayment_stmt->execute();
                $repayment_result = $repayment_stmt->get_result();
                $repayments = $repayment_result->fetch_all(MYSQLI_ASSOC);

                // Start from meeting date or loan date
                $payment_date = new DateTime($loan['meeting_date'] ?? $loan['date_created']);
                $payment_date->modify('+1 month');

                // Generate schedule
                $remaining_principal = $total_amount;

                for ($i = 0; $i < $term; $i++) {
                    $interest = round($remaining_principal * ($interest_rate / 100), 2);
                    $due_amount = $monthly_principal + $interest;
                    $due_date = $payment_date->format('Y-m-d');

                    // Check if this payment exists in the schedule
                    $repaid_amount = 0;
                    $paid_date = null;
                    $existing_status = 'unpaid';

                    foreach ($repayments as $repayment) {
                        if ($repayment['due_date'] == $due_date) {
                            $repaid_amount = floatval($repayment['repaid_amount']);
                            $paid_date = $repayment['paid_date'];
                            $existing_status = $repayment['status'];
                            break;
                        }
                    }

                    // CRITICAL: Do not override status if it's already marked as 'paid'
                    // This prevents deleted arrears from coming back
                    if ($existing_status === 'paid') {
                        $status = 'paid';
                        $default_amount = 0;
                    } else {
                        // Calculate status for non-paid entries
                        $status = (abs($repaid_amount - $due_amount) <= 0.50) ? 'paid' : (($repaid_amount > 0) ? 'partial' : 'unpaid');
                        
                        // Calculate default amount - only if past due date AND not paid
                        $default_amount = 0;
                        $today = new DateTime();
                        if ($today > new DateTime($due_date) && $status !== 'paid') {
                            $default_amount = max(0, $due_amount - $repaid_amount);
                        }
                    }

                    // Update or insert schedule entry
                    $upsert_stmt = $db->conn->prepare("
                        INSERT INTO loan_schedule 
                        (loan_id, due_date, principal, interest, amount, repaid_amount, default_amount, status, paid_date) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        principal = VALUES(principal),
                        interest = VALUES(interest),
                        amount = VALUES(amount),
                        repaid_amount = VALUES(repaid_amount),
                        default_amount = CASE 
                            WHEN status = 'paid' THEN 0 
                            ELSE VALUES(default_amount) 
                        END,
                        status = CASE 
                            WHEN status = 'paid' THEN 'paid' 
                            ELSE VALUES(status) 
                        END,
                        paid_date = CASE 
                            WHEN status = 'paid' THEN paid_date 
                            ELSE VALUES(paid_date) 
                        END
                    ");
                    
                    $upsert_stmt->bind_param(
                        "isdddddss",
                        $loan_id,
                        $due_date,
                        $monthly_principal,
                        $interest,
                        $due_amount,
                        $repaid_amount,
                        $default_amount,
                        $status,
                        $paid_date
                    );
                    
                    $upsert_stmt->execute();

                    // Update balances for next iteration
                    $remaining_principal -= $monthly_principal;
                    $payment_date->modify('+1 month');
                }
            }
        }
    }

    // Update loan schedules before calculating statistics
    try {
        updateLoanSchedules($db);
    } catch (Exception $e) {
        error_log("Failed to update loan schedules: " . $e->getMessage());
    }

    // Calculate total defaulters - clients with overdue unpaid/partial loans (ANY overdue amount)
    $defaulters_query = "SELECT COUNT(DISTINCT l.account_id) as total_defaulters
        FROM loan_schedule ls
        JOIN loan l ON ls.loan_id = l.loan_id
        WHERE ls.due_date < CURDATE() 
        AND ls.status IN ('unpaid', 'partial')
        AND l.status >= 2
        AND ls.default_amount > 0";

    $total_defaulters = $db->conn->query($defaulters_query)->fetch_assoc()['total_defaulters'];

    // Total defaulted amount query
    $total_defaulted_query = "SELECT 
        COALESCE(SUM(ls.default_amount), 0) as total_defaulted
    FROM loan_schedule ls
    JOIN loan l ON ls.loan_id = l.loan_id
    WHERE ls.due_date < CURDATE() 
    AND ls.status IN ('unpaid', 'partial')
    AND l.status >= 2
    AND ls.default_amount > 0";

    $total_defaulted = $db->conn->query($total_defaulted_query)->fetch_assoc()['total_defaulted'];

    // Get defaulted loans with client details
    $arrears_query = "SELECT 
                    l.ref_no, 
                    CONCAT(ca.first_name, ' ', ca.last_name) as client_name,
                    ls.amount as expected_amount,
                    COALESCE(ls.repaid_amount, 0) as repaid_amount,
                    ls.default_amount,
                    ls.due_date,
                    l.loan_id,
                    ls.status,
                    DATEDIFF(CURDATE(), ls.due_date) as days_overdue,
                    l.amount as loan_amount,
                    ca.phone_number,
                    ls.principal,
                    ls.interest,
                    l.status as loan_status
                FROM loan_schedule ls
                JOIN loan l ON ls.loan_id = l.loan_id
                JOIN client_accounts ca ON l.account_id = ca.account_id
                WHERE ls.due_date < CURDATE() 
                AND ls.status IN ('unpaid', 'partial')
                AND l.status >= 2
                AND ls.default_amount > 0
                AND ls.due_date BETWEEN ? AND ?
                ORDER BY ls.due_date DESC, ls.default_amount DESC";

    $stmt = $db->conn->prepare($arrears_query);
    $stmt->bind_param("ss", $start_date, $end_date);

    if (!$stmt->execute()) {
        die('Query failed: ' . $db->conn->error);
    }

    $arrears_result = $stmt->get_result();

    // Calculate filtered period statistics
    $filtered_defaulters_query = "SELECT 
    COUNT(DISTINCT l.account_id) as filtered_defaulters,
    COALESCE(SUM(ls.default_amount), 0) as filtered_defaulted
    FROM loan_schedule ls
    JOIN loan l ON ls.loan_id = l.loan_id
    WHERE ls.due_date < CURDATE() 
    AND ls.status IN ('unpaid', 'partial')
    AND l.status >= 2
    AND ls.default_amount > 0
    AND ls.due_date BETWEEN ? AND ?";

    $filtered_stmt = $db->conn->prepare($filtered_defaulters_query);
    $filtered_stmt->bind_param("ss", $start_date, $end_date);
    $filtered_stmt->execute();
    $filtered_result = $filtered_stmt->get_result()->fetch_assoc();
    $filtered_defaulters = $filtered_result['filtered_defaulters'];
    $filtered_defaulted = $filtered_result['filtered_defaulted'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lato Management System - Arrears Management</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    <style>
        .stats-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #51087E;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #51087E;
            margin-bottom: 5px;
        }
        .stats-label {
            color: #666;
            font-size: 1rem;
        }
        .warning-amount {
            color: #e74a3b;
            font-weight: bold;
        }
        .filter-section {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .container-fluid .card {
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }
        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
        
        .dataTables_wrapper {
            margin-top: 20px;
        }
        
        .dataTables_length select,
        .dataTables_filter input {
            border: 1px solid #d1d3e2;
            border-radius: 0.35rem;
            padding: 0.375rem 0.75rem;
        }
        
        .dataTables_filter input {
            margin-left: 0.5rem;
        }
        
        .dataTables_info {
            padding-top: 0.85rem;
        }
        
        .page-link {
            color: #51087E;
            border-color: #51087E;
        }
        
        .page-item.active .page-link {
            background-color: #51087E;
            border-color: #51087E;
        }
        
        .page-link:hover {
            color: #51087E;
            background-color: #e9ecef;
            border-color: #51087E;
        }

        .period-stats {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .btn-delete-arrear {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        #toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }
        
        .toast-notification {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-bottom: 10px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            animation: slideInRight 0.3s ease-out;
            border-left: 4px solid;
        }
        
        .toast-notification.success {
            border-left-color: #28a745;
        }
        
        .toast-notification.error {
            border-left-color: #dc3545;
        }
        
        .toast-notification.warning {
            border-left-color: #ffc107;
        }
        
        .toast-notification.info {
            border-left-color: #17a2b8;
        }
        
        .toast-icon {
            font-size: 24px;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .toast-notification.success .toast-icon {
            color: #28a745;
        }
        
        .toast-notification.error .toast-icon {
            color: #dc3545;
        }
        
        .toast-notification.warning .toast-icon {
            color: #ffc107;
        }
        
        .toast-notification.info .toast-icon {
            color: #17a2b8;
        }
        
        .toast-content {
            flex-grow: 1;
        }
        
        .toast-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
            color: #333;
        }
        
        .toast-message {
            font-size: 13px;
            color: #666;
            margin: 0;
        }
        
        .toast-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #999;
            cursor: pointer;
            padding: 0;
            margin-left: 12px;
            line-height: 1;
            flex-shrink: 0;
        }
        
        .toast-close:hover {
            color: #333;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .toast-notification.removing {
            animation: slideOutRight 0.3s ease-out forwards;
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <!-- Import Sidebar -->
        <?php require_once '../components/includes/sidebar.php'; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid pt-4">

            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Arrears Management</h1>
                <div>
                    <a href="#" id="exportExcelBtn" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm mr-2">
                        <i class="fas fa-file-excel fa-sm text-white-50"></i> Export to Excel
                    </a>
                    <a href="#" id="generateReportBtn" class="d-none d-sm-inline-block btn btn-sm btn-warning shadow-sm">
                        <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
                    </a>
                </div>
            </div>

            <!-- Overall Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-6 col-md-6">
                    <div class="stats-card" style="background: linear-gradient(45deg, #51087E, #1a237e);">
                        <div class="row align-items-center">
                            <div class="col-3">
                                <div class="stats-icon text-white">
                                    <i class="fas fa-users-slash fa-3x"></i>
                                </div>
                            </div>
                            <div class="col-9 text-right">
                                <div class="stats-number text-white"><?php echo number_format($total_defaulters); ?></div>
                                <div class="stats-label text-white-50">Total Defaulters (All Time)</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-md-6">
                    <div class="stats-card" style="background: linear-gradient(45deg, #d32f2f, #f44336);">
                        <div class="row align-items-center">
                            <div class="col-3">
                                <div class="stats-icon text-white">
                                    <i class="fas fa-money-bill-wave fa-3x"></i>
                                </div>
                            </div>
                            <div class="col-9 text-right">
                                <div class="stats-number text-white">KSh <?php echo number_format($total_defaulted, 2); ?></div>
                                <div class="stats-label text-white-50">Total Amount in Arrears (All Time)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" id="filterForm" class="row align-items-end">
                    <div class="col-md-4 mb-3">
                        <label class="mb-2"><strong>Start Date:</strong></label>
                        <input type="date" class="form-control" name="start_date" id="start_date"
                               value="<?php echo htmlspecialchars($start_date); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="mb-2"><strong>End Date:</strong></label>
                        <input type="date" class="form-control" name="end_date" id="end_date"
                               value="<?php echo htmlspecialchars($end_date); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <button type="submit" class="btn btn-warning btn-block">
                            <i class="fas fa-filter"></i> Apply Filter
                        </button>
                    </div>
                    <div class="col-12">
                        <small class="text-muted">
                            <strong>Current Period:</strong> 
                            <?php echo date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)); ?>
                        </small>
                    </div>
                </form>
            </div>

            <!-- Filtered Period Statistics -->
            <div class="period-stats">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-users"></i> Period Defaulters: <?php echo number_format($filtered_defaulters); ?></h5>
                    </div>
                    <div class="col-md-6 text-right">
                        <h5><i class="fas fa-money-bill"></i> Period Arrears: KSh <?php echo number_format($filtered_defaulted, 2); ?></h5>
                    </div>
                </div>
            </div>

            <!-- Arrears Table -->
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 style="color: #51087E;" class="m-0 font-weight-bold">
                        Overdue Loan Installments 
                        <span class="text-muted">(<?php echo date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)); ?>)</span>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="arrearsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Loan Reference</th>
                                    <th>Client Name</th>
                                    <th>Phone Number</th>
                                    <th>Principal</th>
                                    <th>Interest</th>
                                    <th>Expected Amount</th>
                                    <th>Repaid Amount</th>
                                    <th>Overdue Amount</th>
                                    <th>Due Date</th>
                                    <th>Days Overdue</th>
                                    <th>Status</th>
                                    <?php if ($is_admin): ?>
                                    <th>Action</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($arrears_result && $arrears_result->num_rows > 0) {
                                    $total_period_arrears = 0;
                                    while($row = $arrears_result->fetch_assoc()): 
                                        $statusClass = $row['status'] == 'unpaid' ? 'badge-danger' : 'badge-warning';
                                        $statusText = ucfirst($row['status']);
                                        $overdueClass = $row['days_overdue'] > 90 ? 'table-danger' : 
                                                       ($row['days_overdue'] > 30 ? 'table-warning' : '');
                                        $total_period_arrears += $row['default_amount'];
                                ?>
                                    <tr class="<?php echo $overdueClass; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['ref_no']); ?></strong>
                                            <br>
                                            <small class="text-muted">Principal: KSh <?php echo number_format($row['loan_amount'], 2); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone_number'] ?? 'N/A'); ?></td>
                                        <td>KSh <?php echo number_format($row['principal'], 2); ?></td>
                                        <td>KSh <?php echo number_format($row['interest'], 2); ?></td>
                                        <td>KSh <?php echo number_format($row['expected_amount'], 2); ?></td>
                                        <td>KSh <?php echo number_format($row['repaid_amount'], 2); ?></td>
                                        <td class="warning-amount">KSh <?php echo number_format($row['default_amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $row['days_overdue'] > 90 ? 'badge-danger' : ($row['days_overdue'] > 30 ? 'badge-warning' : 'badge-info'); ?> status-badge">
                                                <?php echo $row['days_overdue']; ?> days
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $statusClass; ?> status-badge">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <?php if ($is_admin): ?>
                                        <td>
                                            <button class="btn btn-danger btn-sm btn-delete-arrear" 
                                                    data-loan-id="<?php echo $row['loan_id']; ?>"
                                                    data-due-date="<?php echo $row['due_date']; ?>"
                                                    data-ref-no="<?php echo htmlspecialchars($row['ref_no']); ?>"
                                                    data-client-name="<?php echo htmlspecialchars($row['client_name']); ?>"
                                                    title="Delete this arrear">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php 
                                    endwhile;
                                } else {
                                ?>
                                    <tr>
                                        <td colspan="<?php echo $is_admin ? '12' : '11'; ?>" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                                                <h5 class="text-success">No Overdue Payments Found</h5>
                                                <p class="text-muted">All loan payments are up to date for the selected period!</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php 
                                }
                                ?>
                            </tbody>
                            <?php if (isset($total_period_arrears) && $total_period_arrears > 0): ?>
                            <tfoot>
                                <tr class="table-info font-weight-bold">
                                    <td colspan="7" class="text-right">Period Total:</td>
                                    <td class="warning-amount">KSh <?php echo number_format($total_period_arrears, 2); ?></td>
                                    <td colspan="<?php echo $is_admin ? '4' : '3'; ?>"></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
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
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
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
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteArrearModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">Confirm Delete Arrear</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to permanently delete this arrear?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This will mark the payment as paid and remove it permanently from the arrears list.
                    </div>
                    <p><strong>Loan Reference:</strong> <span id="delete-loan-ref"></span></p>
                    <p><strong>Client:</strong> <span id="delete-client-name"></span></p>
                    <p><strong>Due Date:</strong> <span id="delete-due-date"></span></p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger" id="confirmDeleteArrear">Delete Permanently</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript -->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>

    <!-- Core plugin JavaScript -->
    <script src="../public/js/jquery.easing.js"></script>

    <!-- Page level plugins -->
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>

    <!-- Custom scripts -->
    <script>
        // Toast Notification System
        function showToast(type, title, message, duration = 5000) {
            const toastContainer = $('#toast-container');
            
            const icons = {
                'success': 'fa-check-circle',
                'error': 'fa-exclamation-circle',
                'warning': 'fa-exclamation-triangle',
                'info': 'fa-info-circle'
            };
            
            const icon = icons[type] || icons['info'];
            
            const toastId = 'toast-' + Date.now();
            const toast = $(`
                <div id="${toastId}" class="toast-notification ${type}">
                    <div class="toast-icon">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="toast-content">
                        <div class="toast-title">${title}</div>
                        <p class="toast-message">${message}</p>
                    </div>
                    <button class="toast-close" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);
            
            toastContainer.append(toast);
            
            toast.find('.toast-close').click(function() {
                removeToast(toastId);
            });
            
            if (duration > 0) {
                setTimeout(function() {
                    removeToast(toastId);
                }, duration);
            }
            
            return toastId;
        }
        
        function removeToast(toastId) {
            const toast = $('#' + toastId);
            if (toast.length) {
                toast.addClass('removing');
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }
        }
        
        function showSuccessToast(message, duration) {
            return showToast('success', 'Success!', message, duration);
        }
        
        function showErrorToast(message, duration) {
            return showToast('error', 'Error!', message, duration);
        }
        
        function showWarningToast(message, duration) {
            return showToast('warning', 'Warning!', message, duration);
        }
        
        function showInfoToast(message, duration) {
            return showToast('info', 'Information', message, duration);
        }

        $(document).ready(function() {
            // Check for PHP session messages
            <?php if (isset($_SESSION['success_msg'])): ?>
                showSuccessToast('<?php echo addslashes($_SESSION['success_msg']); ?>', 5000);
                <?php unset($_SESSION['success_msg']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_msg'])): ?>
                showErrorToast('<?php echo addslashes($_SESSION['error_msg']); ?>', 7000);
                <?php unset($_SESSION['error_msg']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['warning_msg'])): ?>
                showWarningToast('<?php echo addslashes($_SESSION['warning_msg']); ?>', 6000);
                <?php unset($_SESSION['warning_msg']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['info_msg'])): ?>
                showInfoToast('<?php echo addslashes($_SESSION['info_msg']); ?>', 5000);
                <?php unset($_SESSION['info_msg']); ?>
            <?php endif; ?>
            
            // Initialize DataTable
            var arrearsTable = $('#arrearsTable').DataTable({
                "order": [[8, "asc"]],
                "pageLength": 25,
                "responsive": true,
                "language": {
                    "emptyTable": "No overdue payments found for the selected period"
                },
                "columnDefs": [
                    { "orderable": false, "targets": [<?php echo $is_admin ? '10, 11' : '10'; ?>] }
                ]
            });

            // Form validation
            $('#filterForm').submit(function(e) {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                
                if (!startDate || !endDate) {
                    e.preventDefault();
                    showWarningToast('Please select both start and end dates', 5000);
                    return false;
                }
                
                if (new Date(startDate) > new Date(endDate)) {
                    e.preventDefault();
                    showErrorToast('Start date cannot be later than end date', 5000);
                    return false;
                }
            });

            // Handle Excel export
            $('#exportExcelBtn').click(function(e) {
                e.preventDefault();
                
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                
                if (!startDate || !endDate) {
                    showWarningToast('Please select date range first', 5000);
                    return;
                }
                
                var url = '../controllers/export_arrears.php?start_date=' + startDate + '&end_date=' + endDate;
                
                var btn = $(this);
                var originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);
                
                showInfoToast('Generating Excel file... Your download will begin shortly.', 3000);
                
                window.location.href = url;
                
                setTimeout(function() {
                    btn.html(originalText).prop('disabled', false);
                    showSuccessToast('Excel file has been generated and downloaded successfully', 4000);
                }, 2000);
            });

            // Handle report generation
            $('#generateReportBtn').click(function(e) {
                e.preventDefault();
                
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                
                if (!startDate || !endDate) {
                    showWarningToast('Please select date range first', 5000);
                    return;
                }
                
                var url = '../controllers/generate_arrears_report.php?start_date=' + startDate + '&end_date=' + endDate;
                
                var btn = $(this);
                var originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i> Generating...').prop('disabled', true);
                
                showInfoToast('Generating report... Please wait.', 3000);
                
                window.location.href = url;
                
                setTimeout(function() {
                    btn.html(originalText).prop('disabled', false);
                    showSuccessToast('Report has been generated successfully', 4000);
                }, 2000);
            });

            <?php if ($is_admin): ?>
            // Delete arrear functionality
            var deleteLoanId, deleteDueDate;
            
            $('#arrearsTable').on('click', '.btn-delete-arrear', function() {
                deleteLoanId = $(this).data('loan-id');
                deleteDueDate = $(this).data('due-date');
                var refNo = $(this).data('ref-no');
                var clientName = $(this).data('client-name');
                
                $('#delete-loan-ref').text(refNo);
                $('#delete-client-name').text(clientName);
                $('#delete-due-date').text(deleteDueDate);
                $('#deleteArrearModal').modal('show');
            });
            
            $('#confirmDeleteArrear').click(function() {
                    var btn = $(this);
                    var originalText = btn.html();
                    btn.html('<i class="fas fa-spinner fa-spin"></i> Deleting...').prop('disabled', true);
                    
                    $.ajax({
                        url: '../controllers/delete_arrear.php',
                        type: 'POST',
                        data: {
                            loan_id: deleteLoanId,
                            due_date: deleteDueDate
                        },
                        dataType: 'json',
                        success: function(response) {
                            $('#deleteArrearModal').modal('hide');
                            
                            if (response.status === 'success') {
                                showSuccessToast(
                                    response.message || 'Arrear permanently deleted and marked as paid',
                                    5000
                                );
                                
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                showErrorToast(
                                    response.message || 'Failed to delete arrear. Please try again.',
                                    7000
                                );
                                btn.html(originalText).prop('disabled', false);
                            }
                        },
                        error: function(xhr, status, error) {
                            $('#deleteArrearModal').modal('hide');
                            
                            let errorMessage = 'Failed to delete arrear. Please try again.';
                            
                            console.log('XHR Status:', xhr.status);
                            console.log('Response Text:', xhr.responseText);
                            console.log('Error:', error);
                            
                            try {
                                const errorResponse = JSON.parse(xhr.responseText);
                                if (errorResponse.message) {
                                    errorMessage = errorResponse.message;
                                }
                            } catch (e) {
                                // If response is not JSON, show part of the response
                                if (xhr.responseText) {
                                    // Extract meaningful error from HTML if present
                                    const tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = xhr.responseText;
                                    const errorText = tempDiv.textContent || tempDiv.innerText || '';
                                    if (errorText.length > 0 && errorText.length < 200) {
                                        errorMessage = errorText.substring(0, 150);
                                    } else {
                                        errorMessage = 'Server error occurred. Please check console for details.';
                                    }
                                }
                            }
                            
                            showErrorToast(errorMessage, 7000);
                            btn.html(originalText).prop('disabled', false);
                        }
                    });
                });
            <?php endif; ?>

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>