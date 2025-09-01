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

    // Get filter parameters
    $filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'month';
    $custom_start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $custom_end = isset($_GET['end_date']) ? $_GET['end_date'] : '';

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
            $start_date = !empty($custom_start) ? $custom_start : date('Y-m-01');
            $end_date = !empty($custom_end) ? $custom_end : date('Y-m-t');
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

    // Get defaulted loans with client details - showing overdue installments for the filtered period
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
                        l.amount as loan_amount,
                        ca.phone_number
                    FROM loan_schedule ls
                    JOIN loan l ON ls.loan_id = l.loan_id
                    JOIN client_accounts ca ON l.account_id = ca.account_id
                    WHERE ls.due_date < CURDATE() 
                    AND ls.status IN ('unpaid', 'partial')
                    AND l.status IN (1, 2)
                    AND ls.due_date BETWEEN ? AND ?
                    ORDER BY ls.due_date DESC";

    $stmt = $db->conn->prepare($arrears_query);
    $stmt->bind_param("ss", $start_date, $end_date);

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
        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
        
        .date-range-group {
            display: none;
        }
        
        .date-range-group.show {
            display: inline-block;
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
        <!-- Include Sidebar and Header -->
        <?php include '../components/includes/officer_sidebar.php'; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid pt-4">

            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Arrears Management</h1>
                <div>
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
                <form method="GET" id="filterForm" class="row align-items-end">
                    <div class="col-md-3 mb-3">
                        <label class="mb-2"><strong>Filter Period:</strong></label>
                        <select class="form-control" name="filter_type" id="filter_type">
                            <option value="week" <?php echo $filter_type == 'week' ? 'selected' : ''; ?>>
                                This Week
                            </option>
                            <option value="month" <?php echo $filter_type == 'month' ? 'selected' : ''; ?>>
                                This Month
                            </option>
                            <option value="year" <?php echo $filter_type == 'year' ? 'selected' : ''; ?>>
                                This Year
                            </option>
                            <option value="custom" <?php echo $filter_type == 'custom' ? 'selected' : ''; ?>>
                                Custom Range
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 date-range-group <?php echo $filter_type == 'custom' ? 'show' : ''; ?>">
                        <label class="mb-2">Start Date:</label>
                        <input type="date" class="form-control" name="start_date" 
                               value="<?php echo $filter_type == 'custom' ? $start_date : ''; ?>">
                    </div>
                    <div class="col-md-3 mb-3 date-range-group <?php echo $filter_type == 'custom' ? 'show' : ''; ?>">
                        <label class="mb-2">End Date:</label>
                        <input type="date" class="form-control" name="end_date" 
                               value="<?php echo $filter_type == 'custom' ? $end_date : ''; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
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
                                    <th>Expected Amount</th>
                                    <th>Repaid Amount</th>
                                    <th>Overdue Amount</th>
                                    <th>Due Date</th>
                                    <th>Days Overdue</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($arrears_result && $arrears_result->num_rows > 0) {
                                    while($row = $arrears_result->fetch_assoc()): 
                                        $statusClass = $row['status'] == 'unpaid' ? 'badge-danger' : 'badge-warning';
                                        $statusText = ucfirst($row['status']);
                                        $overdueClass = $row['days_overdue'] > 90 ? 'table-danger' : 
                                                       ($row['days_overdue'] > 30 ? 'table-warning' : '');
                                ?>
                                    <tr class="<?php echo $overdueClass; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['ref_no']); ?></strong>
                                            <br>
                                            <small class="text-muted">Principal: KSh <?php echo number_format($row['loan_amount'], 2); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone_number'] ?? 'N/A'); ?></td>
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
                                                <p class="text-muted">All loan payments are up to date for the selected period!</p>
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

        <!-- Footer -->
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; Lato Management System <?php echo date("Y")?></span>
                </div>
            </div>
        </footer>
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
            // Initialize DataTable
            $('#arrearsTable').DataTable({
                "order": [[6, "asc"]], // Order by due date ascending (oldest first)
                "pageLength": 25,
                "responsive": true,
                "language": {
                    "emptyTable": "No overdue payments found for the selected period"
                },
                "columnDefs": [
                    { "orderable": false, "targets": [8] } // Make status column non-orderable
                ]
            });

            // Handle filter type change
            $('#filter_type').change(function() {
                var filterType = $(this).val();
                if (filterType === 'custom') {
                    $('.date-range-group').addClass('show');
                    $('input[name="start_date"]').attr('required', true);
                    $('input[name="end_date"]').attr('required', true);
                } else {
                    $('.date-range-group').removeClass('show');
                    $('input[name="start_date"]').removeAttr('required').val('');
                    $('input[name="end_date"]').removeAttr('required').val('');
                    // Auto-submit for non-custom filters
                    setTimeout(function() {
                        $('#filterForm').submit();
                    }, 100);
                }
            });

            // Handle form submission with validation
            $('#filterForm').submit(function(e) {
                if ($('#filter_type').val() === 'custom') {
                    var startDate = $('input[name="start_date"]').val();
                    var endDate = $('input[name="end_date"]').val();
                    
                    if (!startDate || !endDate) {
                        e.preventDefault();
                        alert('Please select both start and end dates for custom range');
                        return false;
                    }
                    
                    if (new Date(startDate) > new Date(endDate)) {
                        e.preventDefault();
                        alert('Start date cannot be later than end date');
                        return false;
                    }
                }
            });

            // Handle report generation
            $('#generateReportBtn').click(function(e) {
                e.preventDefault();
                
                // Get current filter parameters
                var filterType = $('#filter_type').val();
                var startDate = '';
                var endDate = '';
                
                if (filterType === 'custom') {
                    startDate = $('input[name="start_date"]').val();
                    endDate = $('input[name="end_date"]').val();
                    
                    if (!startDate || !endDate) {
                        alert('Please select both start and end dates for custom range');
                        return;
                    }
                    
                    if (new Date(startDate) > new Date(endDate)) {
                        alert('Start date cannot be later than end date');
                        return;
                    }
                }
                
                // Build URL with parameters
                var url = '../controllers/generate_arrears_report.php?filter_type=' + filterType;
                if (filterType === 'custom' && startDate && endDate) {
                    url += '&start_date=' + startDate + '&end_date=' + endDate;
                }
                
                // Show loading state
                var btn = $(this);
                var originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i> Generating...');
                
                // Trigger download
                window.location.href = url;
                
                // Reset button after short delay
                setTimeout(function() {
                    btn.html(originalText);
                }, 2000);
            });

            // Auto-update defaulters list every 5 minutes
            function updateDefaulters() {
                $.ajax({
                    url: '../controllers/check_defaults.php',
                    type: 'GET',
                    success: function(response) {
                        try {
                            if(typeof response === 'string') {
                                response = JSON.parse(response);
                            }
                            if(response.refresh) {
                                location.reload();
                            }
                        } catch(e) {
                            // Silent fail for auto-update
                        }
                    },
                    error: function() {
                        // Silent fail for auto-update
                    }
                });
            }
            setInterval(updateDefaulters, 300000); // 5 minutes

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>