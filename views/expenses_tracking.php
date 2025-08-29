<?php
// views/expenses_tracking.php
date_default_timezone_set("Africa/Nairobi");
require_once '../helpers/session.php';
require_once '../config/class.php';

// Import components
require_once '../components/expenses_tracking/utilities.php';
require_once '../components/expenses_tracking/financial_stats.php';
require_once '../components/expenses_tracking/filter_form.php';
require_once '../components/expenses_tracking/income_table.php';
require_once '../components/expenses_tracking/expenditure_table.php';

$db = new db_class();

// Check permissions
ExpensesUtilities::checkPermissions($_SESSION);

// Initialize filters
$filters = ExpensesUtilities::initializeFilters();
$start_date = $filters['start_date'];
$end_date = $filters['end_date'];
$category = $filters['category'];
$transaction_type = isset($_GET['transaction_type']) && !empty($_GET['transaction_type']) 
    ? $_GET['transaction_type'] 
    : 'all';

// Initialize financial stats component
$financialStats = new FinancialStats($db);
$stats = $financialStats->calculateStats($start_date, $end_date, $category, $transaction_type);

// Initialize components
$filterForm = new FilterForm($db, $start_date, $end_date, $category, $transaction_type);
$incomeTable = new IncomeTable($db, $start_date, $end_date, $category, $transaction_type);
$expenditureTable = new ExpenditureTable($db, $start_date, $end_date, $category, $transaction_type);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lato Management System - Income & Expenditure</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    
    <?php echo ExpensesUtilities::getCustomStyles(); ?>
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Import Sidebar -->
        <?php require_once '../components/includes/sidebar.php'; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid pt-4">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Income & Expenditure</h1>
                        <div>
                            <button class="btn btn-sm btn-warning shadow-sm" onclick="generatePDFReport()">
                                <i class="fas fa-print fa-sm text-white-50"></i> Print Report
                            </button>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <?php echo $filterForm->render(); ?>

                    <!-- Summary Cards -->
                    <?php echo $financialStats->renderStatsCards($stats); ?>

                    <!-- Income and Expenditure Details -->
                    <div class="row">
                        <?php echo $incomeTable->render(); ?>
                    </div>
                    
                    <div class="row">
                        <?php echo $expenditureTable->render(); ?>
                    </div>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

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
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
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

    <!-- Core JavaScript - Local files only -->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>

    <?php echo ExpensesUtilities::getDataTablesScript(); ?>
    <?php echo ExpensesUtilities::getPrintScript(); ?>

    <!-- Component Scripts -->
    <script>
    $(document).ready(function() {
        // Filter form JavaScript
        <?php echo $filterForm->getJavaScript(); ?>
        
        // Income table JavaScript  
        <?php echo $incomeTable->getJavaScript(); ?>
        
        // Expenditure table JavaScript
        <?php echo $expenditureTable->getJavaScript(); ?>

        // Responsive sidebar handling
        $("#sidebarToggleTop").on('click', function(e) {
            e.preventDefault();
            $("body").toggleClass("sidebar-toggled");
            $(".sidebar").toggleClass("toggled");
        });

        // Handle window resize
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
        
        // Auto-collapse sidebar on mobile
        if ($(window).width() <= 768) {
            $("body").addClass("sidebar-toggled");
            $(".sidebar").addClass("toggled");
        }
    });
    
    // Generate PDF Report function
    function generatePDFReport() {
        // Get current form data
        var formData = $('#filterForm').serialize();
        
        // Open PDF report in new window
        window.open('expenses_tracking_report.php?' + formData, '_blank');
    }
    </script>
</body>
</html>