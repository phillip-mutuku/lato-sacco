<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../helpers/session.php';
require_once '../config/class.php';

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: ../views/index.php');
    exit();
}

$db = new db_class();

// Import required components
require_once '../components/general_reporting/financial_utilities.php';
require_once '../components/general_reporting/debit.php';
require_once '../components/general_reporting/credit.php';
require_once '../components/general_reporting/reports_filter.php';

// Get filter parameters
$filters = FinancialUtilities::initializeFilters();
$start_date = $filters['start_date'];
$end_date = $filters['end_date'];
$filter_type = $filters['filter_type'];
$opening_date = $filters['opening_date'];

// Initialize calculators
$debitCalc = new DebitCalculator($db, $start_date, $end_date, $opening_date);
$creditCalc = new CreditCalculator($db, $start_date, $end_date, $opening_date);

// Calculate totals
$total_debit = $debitCalc->getTotalDebit();
$total_credit = $creditCalc->getTotalCredit();
$net_position = $total_debit - $total_credit;
$is_profit = $net_position > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lato Management System - General Financial Report</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    <style>
        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        .container-fluid .card {
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }
        
        .table th {
            font-size: 0.85rem;
            padding: 0.5rem;
            vertical-align: middle;
        }
        
        .table td {
            font-size: 0.8rem;
            padding: 0.5rem;
        }
        
        .summary-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .debit-card {
            border-left-color: #28a745;
        }
        
        .credit-card {
            border-left-color: #dc3545;
        }
        
        .profit-card {
            border-left-color: #007bff;
        }
        
        .loss-card {
            border-left-color: #ffc107;
        }
        
        .export-buttons {
            margin-bottom: 1rem;
        }
        
        .period-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .table {
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <!-- Import Sidebar -->
        <?php require_once '../components/includes/sidebar.php'; ?>
        
            <!-- Main Content -->
            <div id="content">
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-chart-bar"></i> General Financial Report
                        </h1>
                        <div>
                            <span class="badge badge-primary period-badge">
                                <?php echo FinancialUtilities::getPeriodDescription($start_date, $end_date); ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Success/Error Messages -->
                    <?php if (isset($_SESSION['success_msg'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_msg'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Filter Form -->
                    <div class="no-print">
                        <?php echo ReportsFilter::renderFilterForm($start_date, $end_date, $filter_type); ?>
                    </div>
                    
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <?php if ($filter_type === 'all' || $filter_type === 'debit'): ?>
                        <div class="col-xl-<?php echo $filter_type === 'all' ? '4' : '6'; ?> col-md-6 mb-4">
                            <div class="card summary-card debit-card h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Debit (Income)
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo FinancialUtilities::formatCurrency($total_debit); ?>
                                            </div>
                                            <small class="text-muted">Loan Interest + Withdrawal Fees</small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-plus-circle fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($filter_type === 'all' || $filter_type === 'credit'): ?>
                        <div class="col-xl-<?php echo $filter_type === 'all' ? '4' : '6'; ?> col-md-6 mb-4">
                            <div class="card summary-card credit-card h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Total Credit (Expenses)
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo FinancialUtilities::formatCurrency($total_credit); ?>
                                            </div>
                                            <small class="text-muted">All Business Expenses</small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-minus-circle fa-2x text-danger"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($filter_type === 'all'): ?>
                        <div class="col-xl-4 col-md-12 mb-4">
                            <div class="card summary-card <?php echo $is_profit ? 'profit-card' : 'loss-card'; ?> h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold <?php echo $is_profit ? 'text-primary' : 'text-warning'; ?> text-uppercase mb-1">
                                                Net Position (<?php echo $is_profit ? 'Profit' : 'Loss'; ?>)
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold <?php echo $is_profit ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo FinancialUtilities::formatCurrency($net_position); ?>
                                            </div>
                                            <small class="text-muted">Debit - Credit</small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-<?php echo $is_profit ? 'arrow-up' : 'arrow-down'; ?> fa-2x <?php echo $is_profit ? 'text-success' : 'text-danger'; ?>"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Export Buttons -->
                    <div class="row mb-3 no-print">
                        <div class="col-md-12">
                            <div class="btn-group export-buttons" role="group">
                                <a href="../controllers/generate_general_report_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&filter_type=<?php echo $filter_type; ?>" 
                                   class="btn btn-danger" target="_blank">
                                    <i class="fas fa-file-pdf"></i> Export to PDF
                                </a>
                                <a href="../controllers/generate_general_report_excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&filter_type=<?php echo $filter_type; ?>" 
                                   class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Export to Excel
                                </a>
                                <button onclick="window.print()" class="btn btn-info">
                                    <i class="fas fa-print"></i> Print Report
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Debit Table -->
                    <?php if ($filter_type === 'all' || $filter_type === 'debit'): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-success">
                            <h6 class="m-0 font-weight-bold text-white">
                                <i class="fas fa-plus-circle"></i> DEBIT - Income Sources
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php echo $debitCalc->renderDebitTable(); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Credit Table -->
                    <?php if ($filter_type === 'all' || $filter_type === 'credit'): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-danger">
                            <h6 class="m-0 font-weight-bold text-white">
                                <i class="fas fa-minus-circle"></i> CREDIT - Expense Categories
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php echo $creditCalc->renderCreditTable(); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Final Summary (only for 'all' filter) -->
                    <?php if ($filter_type === 'all'): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-primary">
                            <h6 class="m-0 font-weight-bold text-white">
                                <i class="fas fa-calculator"></i> Financial Summary
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr class="bg-light">
                                            <td class="font-weight-bold">Total Debit (Income):</td>
                                            <td class="text-right text-success font-weight-bold">
                                                <?php echo FinancialUtilities::formatCurrency($total_debit); ?>
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td class="font-weight-bold">Total Credit (Expenses):</td>
                                            <td class="text-right text-danger font-weight-bold">
                                                <?php echo FinancialUtilities::formatCurrency($total_credit); ?>
                                            </td>
                                        </tr>
                                        <tr class="<?php echo $is_profit ? 'bg-success' : 'bg-warning'; ?> text-white">
                                            <td class="font-weight-bold">
                                                NET POSITION (<?php echo $is_profit ? 'PROFIT' : 'LOSS'; ?>):
                                            </td>
                                            <td class="text-right font-weight-bold">
                                                <?php echo FinancialUtilities::formatCurrency($net_position); ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if ($is_profit): ?>
                                <div class="alert alert-success mt-3" role="alert">
                                    <i class="fas fa-check-circle"></i> 
                                    <strong>Profitable Period!</strong> The organization generated a profit of 
                                    <strong><?php echo FinancialUtilities::formatCurrency($net_position); ?></strong> 
                                    during this period.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mt-3" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    <strong>Loss Period!</strong> The organization incurred a loss of 
                                    <strong><?php echo FinancialUtilities::formatCurrency(abs($net_position)); ?></strong> 
                                    during this period.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                <!-- End of Main Content -->
            </div>
            <!-- End of Content -->
            
            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Lato Management System <?php echo date("Y"); ?></span>
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
    
    <!-- Bootstrap core JavaScript-->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>
    
    <!-- Core plugin JavaScript-->
    <script src="../public/js/jquery.easing.js"></script>
    
    <!-- Custom scripts for all pages-->
    <script src="../public/js/sb-admin-2.js"></script>
    
    <!-- Page level plugins -->
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTables for better table viewing
        if ($('#debitTable').length) {
            $('#debitTable').DataTable({
                "paging": false,
                "searching": false,
                "info": false,
                "ordering": false
            });
        }
        
        if ($('#creditTable').length) {
            $('#creditTable').DataTable({
                "paging": false,
                "searching": false,
                "info": false,
                "ordering": false
            });
        }
        
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
            }
            
            // Toggle the side navigation when window is resized below 480px
            if ($(window).width() < 480 && !$(".sidebar").hasClass("toggled")) {
                $("body").addClass("sidebar-toggled");
                $(".sidebar").addClass("toggled");
                $('.sidebar .collapse').collapse('hide');
            }
        });
    });
    </script>
</body>
</html>