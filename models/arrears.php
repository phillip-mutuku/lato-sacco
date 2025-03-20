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

    // Get filter dates from request
    $filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'month';

    // Initialize dates based on filter type
    switch($filter_type) {
        case 'week':
            $start_date = date('Y-m-d', strtotime('monday this week'));
            $end_date = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
            break;
        case 'year':
            $start_date = date('Y-01-01');
            $end_date = date('Y-12-31');
            break;
        case 'custom':
            $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
            $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
            break;
        default:
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
    }




    // Calculate total defaulters
$defaulters_query = "SELECT COUNT(DISTINCT l.account_id) as total_defaulters
FROM loan_schedule ls
JOIN loan l ON ls.loan_id = l.loan_id
WHERE ls.default_amount > 0 
AND ls.due_date < CURDATE()";
$total_defaulters = $db->conn->query($defaulters_query)->fetch_assoc()['total_defaulters'];

// Calculate total defaulted amount
$total_defaulted_query = "SELECT SUM(default_amount) as total_defaulted
     FROM loan_schedule
     WHERE default_amount > 0
     AND due_date < CURDATE()";
$total_defaulted = $db->conn->query($total_defaulted_query)->fetch_assoc()['total_defaulted'];

// Get defaulted loans with client details
$arrears_query = "SELECT DISTINCT l.ref_no, 
                         CONCAT(ca.first_name, ' ', ca.last_name) as client_name,
                         ls.amount as expected_amount,
                         ls.default_amount,
                         ls.due_date,
                         l.loan_id,
                         ls.status,
                         COALESCE(ls.repaid_amount, 0) as repaid_amount,
                         DATEDIFF(CURDATE(), ls.due_date) as days_overdue
                  FROM loan_schedule ls
                  JOIN loan l ON ls.loan_id = l.loan_id
                  JOIN client_accounts ca ON l.account_id = ca.account_id
                  WHERE ls.default_amount > 0
                  AND ls.due_date < CURDATE()";

// Add date filtering if dates are set
if(isset($_GET['filter_type'])) {
    $stmt = $db->conn->prepare($arrears_query . " AND ls.due_date BETWEEN ? AND ? ORDER BY ls.due_date DESC");
    $stmt->bind_param("ss", $start_date, $end_date);
} else {
    $stmt = $db->conn->prepare($arrears_query . " ORDER BY ls.due_date DESC");
}

if (!$stmt->execute()) {
    die('Query failed: ' . $db->conn->error);
}

$arrears_result = $stmt->get_result();


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
            #accordionSidebar { width: 100px; }
            #content-wrapper {
                margin-left: 100px;
                width: calc(100% - 100px);
            }
            .topbar { left: 100px; }
            .sidebar .nav-item .nav-link span { display: none; }
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
                <a class="nav-link" href="../views/home.php">
                    <i class="fas fa-fw fa-home"></i>
                    <span>Home</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Management
            </div>

            <li class="nav-item">
                <a class="nav-link" href="loan.php">
                <i class="fas fa-fw fas fa-comment-dollar"></i>
                    <span>New Loan</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="pending_approval.php">
                <i class="fas fa-fw fas fa-comment-dollar"></i>
                    <span>Pending Approval</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="disbursement.php">
                    <i class="fas fa-fw fas fa-coins"></i>
                    <span>Disbursements</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="../views/daily-reconciliation.php">
                    <i class="fas fa-fw fa-balance-scale"></i>
                    <span>Daily Reconciliation</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="../views/expenses_tracking.php">
                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                    <span>Expenses Tracking</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="../views/manage_expenses.php">
                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    <span>Manage Expenses</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="arrears.php">
                <i class="fas fa-users-slash fa-2x text-gray-300"></i>
                    <span>Arrears</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../views/receipts.php">
                <i class="fas fa-receipt fa-2x"></i>
                    <span>Receipts</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../views/account.php">
                <i class="fas fa-fw fa-user"></i>
                    <span>Client Accounts</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Wekeza Groups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="business_groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Business Groups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="loan_plan.php">
                    <i class="fas fa-fw fa-piggy-bank"></i>
                    <span>Loan Products</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                System
            </div>

            <li class="nav-item">
                <a class="nav-link" href="user.php">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Users</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../views/settings.php">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="../views/announcements.php">
                    <i class="fas fa-fw fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../views/notifications.php">
                    <i class="fas fa-fw fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../views/backup.php">
                    <i class="fas fa-fw fa-database"></i>
                    <span>Backup</span>
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
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo $db->user_acc($_SESSION['user_id'])?>
                                </span>
                                <img class="img-profile rounded-circle" src="../public/image/logo.jpg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <!-- Begin Page Content -->
                <div class="container-fluid pt-4">

                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Arrears Management</h1>
                    <a href="#" id="generateReportBtn" class="d-none d-sm-inline-block btn btn-sm btn-warning shadow-sm">
                        <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
                    </a>
                </div>

                    <!-- Statistics Cards -->
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
                                        <div class="stats-label text-white-50">Total Defaulters</div>
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
                                        <div class="stats-label text-white-50">Total Amount in Arrears</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="filter-section">
                        <form method="GET" id="filterForm" class="form-inline">
                            <div class="form-group mr-3">
                                <label class="mr-2">Filter Type:</label>
                                <select class="form-control" name="filter_type" id="filter_type">
                                    <option value="custom" <?php echo $filter_type == 'custom' ? 'selected' : ''; ?>>
                                        Custom Range
                                    </option>
                                    <option value="week" <?php echo $filter_type == 'week' ? 'selected' : ''; ?>>
                                        This Week
                                    </option>
                                    <option value="month" <?php echo $filter_type == 'month' ? 'selected' : ''; ?>>
                                        This Month
                                    </option>
                                    <option value="year" <?php echo $filter_type == 'year' ? 'selected' : ''; ?>>
                                        This Year
                                    </option>
                                </select>
                            </div>
                            <div class="form-group mr-3 date-range" 
                                <?php echo $filter_type != 'custom' ? 'style="display:none;"' : ''; ?>>
                                <label class="mr-2">Start Date:</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="form-group mr-3 date-range" 
                                <?php echo $filter_type != 'custom' ? 'style="display:none;"' : ''; ?>>
                                <label class="mr-2">End Date:</label>
                                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <button type="submit" class="btn btn-warning">Apply Filter</button>
                        </form>
                    </div>

                    <!-- Arrears Table -->
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <h6 style="color: #51087E;" class="m-0 font-weight-bold">Defaulted Loans</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                            <table class="table table-bordered" id="arrearsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Loan Reference No</th>
                                        <th>Client Name</th>
                                        <th>Expected Amount</th>
                                        <th>Repaid Amount</th>
                                        <th>Default Amount</th>
                                        <th>Due Date</th>
                                        <th>Days Overdue</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($arrears_result && $arrears_result->num_rows > 0) {
                                        while($row = $arrears_result->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['ref_no']); ?></td>
                                            <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                            <td>KSh <?php echo number_format($row['expected_amount'], 2); ?></td>
                                            <td>KSh <?php echo number_format($row['repaid_amount'], 2); ?></td>
                                            <td class="warning-amount">KSh <?php echo number_format($row['default_amount'], 2); ?></td>
                                            <td><?php echo date('F d, Y', strtotime($row['due_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-danger">
                                                    <?php echo $row['days_overdue']; ?> days
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-danger">Defaulted</span>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                    ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No defaulted loans found</td>
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
        $(document).ready(function() {
            // Initialize DataTable with export buttons
            $('#arrearsTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                order: [[4, 'desc']],
                pageLength: 25,
                language: {
                    search: "Search records:"
                }
            });

            // Auto-update defaulters list
            function updateDefaulters() {
                $.ajax({
                    url: '../controllers/check_defaults.php',
                    type: 'GET',
                    success: function(response) {
                        if(response.refresh) {
                            location.reload();
                        }
                    }
                });
            }

            // Check for updates every 5 minutes
            setInterval(updateDefaulters, 300000);

            // Handle filter type change
            $('#filter_type').change(function() {
                var filterType = $(this).val();
                if (filterType === 'custom') {
                    $('.date-range').show();
                } else {
                    $('.date-range').hide();
                    $('#filterForm').submit();
                }
            });

            // Validate date range
            $('#filterForm').submit(function(e) {
                if ($('#filter_type').val() === 'custom') {
                    var startDate = new Date($('input[name="start_date"]').val());
                    var endDate = new Date($('input[name="end_date"]').val());
                    
                    if (startDate > endDate) {
                        e.preventDefault();
                        alert('Start date cannot be later than end date');
                        return false;
                    }
                }
            });

            ///handle reort generation

            $('#generateReportBtn').click(function(e) {
        e.preventDefault();
        
        // Get the current filter dates
        var startDate = $('input[name="start_date"]').val() || '<?php echo date('Y-m-01'); ?>';
        var endDate = $('input[name="end_date"]').val() || '<?php echo date('Y-m-t'); ?>';
        var filterType = $('#filter_type').val();
        
        // Adjust dates based on filter type
        switch(filterType) {
            case 'week':
                startDate = '<?php echo date('Y-m-d', strtotime('monday this week')); ?>';
                endDate = '<?php echo date('Y-m-d', strtotime('sunday this week')); ?>';
                break;
            case 'month':
                startDate = '<?php echo date('Y-m-01'); ?>';
                endDate = '<?php echo date('Y-m-t'); ?>';
                break;
            case 'year':
                startDate = '<?php echo date('Y-01-01'); ?>';
                endDate = '<?php echo date('Y-12-31'); ?>';
                break;
        }

        // Create the URL with parameters
        var url = '../controllers/generate_arrears_report.php?start_date=' + startDate + '&end_date=' + endDate;

        // Open in new window/tab or trigger download
        window.location.href = url;
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

            // Handle responsive behavior
            $(window).resize(function() {
                if ($(window).width() < 768) {
                    $('.sidebar .collapse').collapse('hide');
                }
                
                if ($(window).width() < 480 && !$(".sidebar").hasClass("toggled")) {
                    $("body").addClass("sidebar-toggled");
                    $(".sidebar").addClass("toggled");
                    $('.sidebar .collapse').collapse('hide');
                }
            });

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>