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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lato Management System - Pending Loan Approvals</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    <style>
        .modal-lg { max-width: 80% !important; }
        .form-group label { font-weight: bold; }
        html, body { overflow-x: hidden; }
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
        .container-fluid .card {
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }



        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1051;
        }
        
        /* Custom styles for toasts */
        .toast {
            min-width: 250px;
        }
        
        .toast-header {
            color: #fff;
        }
        
        .toast-header.bg-success {
            background-color: #28a745 !important;
        }
        
        .toast-header.bg-danger {
            background-color: #dc3545 !important;
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
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $db->user_acc($_SESSION['user_id'])?></span>
                                <img class="img-profile rounded-circle" src="../public/image/logo.jpg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->


                  <!-- Toast Container -->
    <div aria-live="polite" aria-atomic="true" class="toast-container">
        <!-- Toasts will be inserted here -->
    </div>

                <!-- Begin Page Content -->
                <div class="container-fluid pt-4">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Pending Loan Approvals</h1>
                    </div>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Reference No</th>
                                            <th>Payee Name</th>
                                            <th>Shareholder No</th>
                                            <th>Loan Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                        $pending_loans = $db->get_pending_loans();
                                        while($loan = $pending_loans->fetch_array()){
                                    ?>
                                    <tr>
                                        <td><?php echo $loan['ref_no']?></td>
                                        <td><?php echo $loan['last_name'] . ", " . $loan['first_name']?></td>
                                        <td><?php echo $loan['shareholder_no']?></td>
                                        <td><?php echo number_format($loan['amount'], 2)?></td>
                                        <td>
                                            <button class="btn btn-success btn-sm approve-loan" data-loan-id="<?php echo $loan['loan_id']?>">Approve</button>
                                            <button class="btn btn-danger btn-sm deny-loan" data-loan-id="<?php echo $loan['loan_id']?>">Deny</button>
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
            // Initialize DataTable
            $('#dataTable').DataTable();



        // Function to show a toast notification
        function showToast(message, type) {
            var toastId = 'toast-' + Date.now();
            var toastHtml = `
                <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">
                    <div class="toast-header bg-${type} text-white">
                        <strong class="mr-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            $('.toast-container').append(toastHtml);
            $(`#${toastId}`).toast('show');
        }

        // Approve loan
        $('.approve-loan').click(function() {
            var loanId = $(this).data('loan-id');
            if (confirm('Are you sure you want to approve this loan?')) {
                $.ajax({
                    url: '../controllers/update_loan_status.php',
                    type: 'POST',
                    data: { loan_id: loanId, status: 1 },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showToast('Loan approved successfully', 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showToast('Error approving loan: ' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        showToast('Error approving loan', 'danger');
                    }
                });
            }
        });

        // Deny loan
        $('.deny-loan').click(function() {
            var loanId = $(this).data('loan-id');
            if (confirm('Are you sure you want to deny this loan?')) {
                $.ajax({
                    url: '../controllers/update_loan_status.php',
                    type: 'POST',
                    data: { loan_id: loanId, status: 4 },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showToast('Loan denied successfully', 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showToast('Error denying loan: ' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        showToast('Error denying loan', 'danger');
                    }
                });
            }
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

            // Close any open menu accordions when window is resized below 768px
            $(window).resize(function() {
                if ($(window).width() < 768) {
                    $('.sidebar .collapse').collapse('hide');
                };
                
                // Toggle the side navigation when window is resized below 480px
                if ($(window).width() < 480 && !$(".sidebar").hasClass("toggled")) {
                    $("body").addClass("sidebar-toggled");
                    $(".sidebar").addClass("toggled");
                    $('.sidebar .collapse').collapse('hide');
                };
            });

        });
    </script>
</body>
</html>