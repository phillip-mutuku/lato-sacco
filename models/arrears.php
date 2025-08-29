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

    // Calculate total defaulters - clients with overdue unpaid/partial loans
    $defaulters_query = "SELECT COUNT(DISTINCT l.account_id) as total_defaulters
    FROM loan_schedule ls
    JOIN loan l ON ls.loan_id = l.loan_id
    WHERE ls.due_date < CURDATE() 
    AND ls.status IN ('unpaid', 'partial')
    AND l.status IN (1, 2)"; // Only active/disbursed loans
    
    $total_defaulters = $db->conn->query($defaulters_query)->fetch_assoc()['total_defaulters'];

    // Calculate total defaulted amount - sum of all overdue amounts from unpaid/partial installments
    $total_defaulted_query = "SELECT 
        COALESCE(SUM(
            CASE 
                WHEN ls.status = 'unpaid' THEN ls.amount
                WHEN ls.status = 'partial' THEN (ls.amount - COALESCE(ls.repaid_amount, 0))
                ELSE 0
            END
        ), 0) as total_defaulted
    FROM loan_schedule ls
    JOIN loan l ON ls.loan_id = l.loan_id
    WHERE ls.due_date < CURDATE() 
    AND ls.status IN ('unpaid', 'partial')
    AND l.status IN (1, 2)"; // Only active/disbursed loans
    
    $total_defaulted = $db->conn->query($total_defaulted_query)->fetch_assoc()['total_defaulted'];

    // Get defaulted loans with client details - showing overdue installments
    $arrears_query = "SELECT 
                        l.ref_no, 
                        CONCAT(ca.first_name, ' ', ca.last_name) as client_name,
                        ls.amount as expected_amount,
                        COALESCE(ls.repaid_amount, 0) as repaid_amount,
                        CASE 
                            WHEN ls.status = 'unpaid' THEN ls.amount
                            WHEN ls.status = 'partial' THEN (ls.amount - COALESCE(ls.repaid_amount, 0))
                            ELSE 0
                        END as default_amount,
                        ls.due_date,
                        l.loan_id,
                        ls.status,
                        DATEDIFF(CURDATE(), ls.due_date) as days_overdue,
                        l.amount as loan_amount
                    FROM loan_schedule ls
                    JOIN loan l ON ls.loan_id = l.loan_id
                    JOIN client_accounts ca ON l.account_id = ca.account_id
                    WHERE ls.due_date < CURDATE() 
                    AND ls.status IN ('unpaid', 'partial')
                    AND l.status IN (1, 2)"; // Only active/disbursed loans

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

    // Function to trigger loan schedule updates for all loans (optional - can be run periodically)
    function updateAllLoanSchedules($db) {
        // Get all active loans that need schedule updates
        $loan_query = "SELECT DISTINCT l.loan_id 
                      FROM loan l 
                      LEFT JOIN loan_schedule ls ON l.loan_id = ls.loan_id 
                      WHERE l.status IN (1, 2) 
                      AND (ls.loan_id IS NULL OR ls.default_amount = 0)";
        
        $result = $db->conn->query($loan_query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Call the loan schedule update endpoint for each loan
                // This ensures all schedules have proper default calculations
                $loan_id = $row['loan_id'];
                
                // You could make a curl request to get_loan_schedule.php here
                // or include the logic directly
                
                // For now, we'll just log that this loan needs updating
                error_log("Loan ID $loan_id may need schedule update");
            }
        }
    }

    // Optionally trigger schedule updates (uncomment if needed)
    // updateAllLoanSchedules($db);

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
        .update-schedules-btn {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        .update-schedules-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
        
        /* DataTable Custom Styling */
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
                        <button id="updateSchedulesBtn" class="update-schedules-btn mr-2">
                            <i class="fas fa-sync-alt fa-sm"></i> Update Schedules
                        </button>
                        <a href="#" id="generateReportBtn" class="d-none d-sm-inline-block btn btn-sm btn-warning shadow-sm">
                            <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
                        </a>
                    </div>
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
                            <h6 style="color: #51087E;" class="m-0 font-weight-bold">Overdue Loan Installments</h6>
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
                                        <th>Overdue Amount</th>
                                        <th>Due Date</th>
                                        <th>Days Overdue</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($arrears_result && $arrears_result->num_rows > 0) {
                                        while($row = $arrears_result->fetch_assoc()): 
                                            $statusClass = $row['status'] == 'unpaid' ? 'badge-danger' : 'badge-warning';
                                            $statusText = ucfirst($row['status']);
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['ref_no']); ?></strong>
                                                <br>
                                                <small class="text-muted">Loan: KSh <?php echo number_format($row['loan_amount'], 2); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                            <td>KSh <?php echo number_format($row['expected_amount'], 2); ?></td>
                                            <td>KSh <?php echo number_format($row['repaid_amount'], 2); ?></td>
                                            <td class="warning-amount">KSh <?php echo number_format($row['default_amount'], 2); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-danger status-badge">
                                                    <?php echo $row['days_overdue']; ?> days
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $statusClass; ?> status-badge">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info view-schedule" 
                                                        data-loan-id="<?php echo $row['loan_id']; ?>"
                                                        data-toggle="tooltip" title="View Loan Schedule">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                    ?>
                                        <tr>
                                            <td colspan="9" class="text-center">
                                                <div class="py-4">
                                                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                                                    <h5 class="text-success">No Overdue Payments Found</h5>
                                                    <p class="text-muted">All loan payments are up to date!</p>
                                                </div>
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

    <!-- Loan Schedule Modal -->
    <div class="modal fade" id="loanScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Loan Payment Schedule</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="scheduleContent"></div>
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
            // Initialize DataTable with simple configuration like loan products page
            $('#arrearsTable').DataTable({
                "order": [[5, "desc"]], // Order by overdue amount descending
                "pageLength": 10,
                "responsive": true,
                "language": {
                    "emptyTable": "No overdue payments found"
                }
            });

            // Handle update schedules button
            $('#updateSchedulesBtn').click(function() {
                const btn = $(this);
                const originalText = btn.html();
                
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
                
                $.ajax({
                    url: '../controllers/update_all_loan_schedules.php',
                    type: 'GET',
                    success: function(response) {
                        if(response.status === 'success') {
                            toastr.success('Loan schedules updated successfully');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            toastr.error(response.message || 'Failed to update schedules');
                        }
                    },
                    error: function() {
                        toastr.error('Error updating loan schedules');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // View loan schedule
            $('.view-schedule').click(function() {
                const loanId = $(this).data('loan-id');
                
                $.ajax({
                    url: '../controllers/get_loan_schedule.php',
                    type: 'GET',
                    data: { loan_id: loanId },
                    success: function(response) {
                        if(response.status === 'success') {
                            let scheduleHtml = '<div class="table-responsive">';
                            scheduleHtml += '<table class="table table-bordered">';
                            scheduleHtml += '<thead class="thead-light">';
                            scheduleHtml += '<tr><th>Due Date</th><th>Principal</th><th>Interest</th><th>Total Due</th><th>Paid</th><th>Balance</th><th>Status</th></tr>';
                            scheduleHtml += '</thead><tbody>';
                            
                            response.schedule.forEach(function(item) {
                                const statusClass = item.status === 'paid' ? 'success' : 
                                                  (item.status === 'partial' ? 'warning' : 'danger');
                                scheduleHtml += `<tr class="table-${statusClass}">
                                    <td>${new Date(item.due_date).toLocaleDateString()}</td>
                                    <td>KSh ${item.principal}</td>
                                    <td>KSh ${item.interest}</td>
                                    <td>KSh ${item.amount}</td>
                                    <td>KSh ${item.repaid_amount}</td>
                                    <td>KSh ${item.balance}</td>
                                    <td><span class="badge badge-${statusClass}">${item.status}</span></td>
                                </tr>`;
                            });
                            
                            scheduleHtml += '</tbody></table></div>';
                            $('#scheduleContent').html(scheduleHtml);
                            $('#loanScheduleModal').modal('show');
                        } else {
                            toastr.error(response.message || 'Failed to load schedule');
                        }
                    },
                    error: function() {
                        toastr.error('Error loading loan schedule');
                    }
                });
            });

            // Make payment functionality
            $('.make-payment').click(function() {
                const loanId = $(this).data('loan-id');
                // Redirect to payment page or open payment modal
                window.location.href = `../views/loan_repayment.php?loan_id=${loanId}`;
            });

            // Auto-update defaulters list every 5 minutes
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

            // Handle report generation
            $('#generateReportBtn').click(function(e) {
                e.preventDefault();
                
                var startDate = $('input[name="start_date"]').val() || '<?php echo date('Y-m-01'); ?>';
                var endDate = $('input[name="end_date"]').val() || '<?php echo date('Y-m-t'); ?>';
                var filterType = $('#filter_type').val();
                
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

                var url = '../controllers/generate_arrears_report.php?start_date=' + startDate + '&end_date=' + endDate;
                window.location.href = url;
            });

            // Toggle sidebar functionality
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