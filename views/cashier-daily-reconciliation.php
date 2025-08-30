<?php
    date_default_timezone_set("Africa/Nairobi");
    require_once '../helpers/session.php';
    require_once '../config/class.php';
    $db = new db_class();

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'cashier')) {
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
        :root {
            --primary-color: #51087E;
        }

        /* Dashboard Cards */
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

        /* Ensure content is always visible */
        .container-fluid {
            overflow: visible !important;
            min-width: 0 !important;
        }

        .row {
            margin-right: 0 !important;
            margin-left: 0 !important;
        }

        .col-xl-3, .col-xl-6, .col-xl-8, .col-xl-4, .col-lg-6, .col-lg-7, .col-lg-5, .col-md-6 {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
            min-width: 0 !important;
        }

        /* Page title styling */
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

        /* Fullscreen styles */
        .fullscreen-active {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            background: white;
        }

        .fullscreen-active .sidebar {
            display: none;
        }

        .fullscreen-active #content-wrapper {
            margin-left: 0;
            width: 100%;
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

            <!-- Float Management Component -->
            <?php include '../components/reconciliation/float_management.php'; ?>

            <!-- Float Transactions Component -->
            <?php include '../components/reconciliation/float_transactions.php'; ?>

            <!-- Transactions Filter Component -->
            <?php include '../components/reconciliation/transactions_filter.php'; ?>

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

    <!-- Core plugin JavaScript-->
    <script src="../public/js/jquery.easing.js"></script>

    <!-- Page level plugins -->
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../public/js/sb-admin-2.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Enhanced Sidebar Toggle Functionality
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-toggled');
            document.querySelector('.sidebar').classList.toggle('toggled');
        }

        // Sidebar toggle buttons
        const sidebarToggle = document.querySelector('#sidebarToggle');
        const sidebarToggleTop = document.querySelector('#sidebarToggleTop');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }

        if (sidebarToggleTop) {
            sidebarToggleTop.addEventListener('click', toggleSidebar);
        }

        // Fullscreen Toggle Functionality
        const fullscreenToggle = document.querySelector('#fullscreenToggle');
        if (fullscreenToggle) {
            fullscreenToggle.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch(err => {
                        console.log(`Error attempting to enable fullscreen: ${err.message}`);
                    });
                    this.innerHTML = '<i class="fas fa-compress-arrows-alt fa-fw"></i>';
                } else {
                    document.exitFullscreen();
                    this.innerHTML = '<i class="fas fa-expand-arrows-alt fa-fw"></i>';
                }
            });
        }

        // Responsive behavior
        function handleResize() {
            if (window.innerWidth < 768) {
                document.body.classList.add('sidebar-toggled');
                document.querySelector('.sidebar').classList.add('toggled');
                // Collapse any open accordions
                document.querySelectorAll('.sidebar .collapse.show').forEach(collapse => {
                    collapse.classList.remove('show');
                });
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize(); // Call on load

        // Enhanced dropdown animations
        document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const dropdown = this.nextElementSibling;
                if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                    dropdown.classList.add('animated--grow-in');
                }
            });
        });

        // Card hover effects
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 0.25rem 2rem 0 rgba(33, 40, 50, 0.2)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15)';
            });
        });

        // Smooth scrolling
        document.querySelectorAll('a.scroll-to-top').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });

        // Show/hide scroll to top button
        window.addEventListener('scroll', function() {
            const scrollButton = document.querySelector('.scroll-to-top');
            if (window.pageYOffset > 100) {
                scrollButton.style.display = 'block';
            } else {
                scrollButton.style.display = 'none';
            }
        });
    });

    function generateReport() {
        const startDate = document.querySelector('input[name="start_date"]') ? document.querySelector('input[name="start_date"]').value : '';
        const endDate = document.querySelector('input[name="end_date"]') ? document.querySelector('input[name="end_date"]').value : '';
        window.location.href = `../controllers/generate_reconciliation_report.php?start_date=${startDate}&end_date=${endDate}`;
    }
    </script>
</body>
</html>