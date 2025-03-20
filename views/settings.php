<?php
// Set timezone
date_default_timezone_set("Africa/Nairobi");

// Include required files and initialize the database connection
require_once '../helpers/session.php';
require_once '../config/class.php';
require_once '../models/settings_handler.php';
$db = new db_class();

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: index.php');
    exit();
}

$settings = getSettings();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Settings - Lato Management System</title>

    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <!-- Font Awesome Icons -->
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Custom styles -->
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        html, body {
            overflow-x: hidden;
        }
        .settings-card {
            border: none;
            border-radius: 8px;
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
        }
        .settings-card .card-header {
            color: #51087E;
            font-weight: bold;
            border-radius: 8px 8px 0 0;
        }
        .form-group label {
            font-weight: 600;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #51087E;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
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
            #accordionSidebar {
                width: 100px;
            }
            #content-wrapper {
                margin-left: 100px;
                width: calc(100% - 100px);
            }
            .topbar {
                left: 100px;
            }
            .sidebar .nav-item .nav-link span {
                display: none;
            }
        }
    </style>
</head>

<body id="page-top" class="<?php echo $settings['dark_mode'] == '1' ? 'bg-dark text-white' : ''; ?>">

    <!-- Page Wrapper -->
    <div id="wrapper">

         <!-- Sidebar -->
         <ul style="background: #51087E;"  class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
                <div class="sidebar-brand-text mx-3">LATO SACCO</div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item">
                <a class="nav-link" href="home.php">
                    <i class="fas fa-fw fa-home"></i>
                    <span>Home</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Management
            </div>

            <li class="nav-item">
                <a class="nav-link" href="../models/loan.php">
                <i class="fas fa-fw fas fa-comment-dollar"></i>
                    <span>New Loan</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../models/pending_approval.php">
                <i class="fas fa-fw fas fa-comment-dollar"></i>
                    <span>Pending Approval</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/disbursement.php">
                    <i class="fas fa-fw fas fa-coins"></i>
                    <span>Disbursements</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="daily-reconciliation.php">
                    <i class="fas fa-fw fa-balance-scale"></i>
                    <span>Daily Reconciliation</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="expenses_tracking.php">
                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                    <span>Expenses Tracking</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="manage_expenses.php">
                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    <span>Manage Expenses</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="../models/arrears.php">
                <i class="fas fa-users-slash fa-2x text-gray-300"></i>
                    <span>Arrears</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="receipts.php">
                <i class="fas fa-receipt fa-2x"></i>
                    <span>Receipts</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="account.php">
                <i class="fas fa-fw fa-user"></i>
                    <span>Client Accounts</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Wekeza Groups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/business_groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Business Groups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/loan_plan.php">
                    <i class="fas fa-fw fa-piggy-bank"></i>
                    <span>Loan Products</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                System
            </div>

            <li class="nav-item">
                <a class="nav-link" href="../models/user.php">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Users</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="announcements.php">
                    <i class="fas fa-fw fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="notifications.php">
                    <i class="fas fa-fw fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="backup.php">
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
                                    <?php echo $db->user_acc($_SESSION['user_id']); ?>
                                </span>
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

                <!-- Begin Page Content -->
                <div class="container-fluid pt-4">

                 <!-- Alert Container -->
    <div id="alertContainer" class="alert-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; width: 300px;"></div>

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Settings</h1>

                    <div class="row">
                        <!-- General Settings Card -->
                        <div class="col-lg-6">
                            <div class="card settings-card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold">General Settings</h6>
                                </div>
                                <div class="card-body">
                                    <form id="generalSettingsForm">
                                        <div class="form-group">
                                            <label for="companyName">Company Name</label>
                                            <input type="text" class="form-control" id="companyName" name="companyName" value="<?php echo $settings['company_name']; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="systemEmail">System Email</label>
                                            <input type="email" class="form-control" id="systemEmail" name="systemEmail" value="<?php echo $settings['system_email']; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="defaultCurrency">Default Currency</label>
                                            <select class="form-control" id="defaultCurrency" name="defaultCurrency" required>
                                                <option value="USD" <?php echo $settings['default_currency'] == 'USD' ? 'selected' : ''; ?>>USD</option>
                                                <option value="EUR" <?php echo $settings['default_currency'] == 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                                <option value="GBP" <?php echo $settings['default_currency'] == 'GBP' ? 'selected' : ''; ?>>GBP</option>
                                                <option value="KSh" <?php echo $settings['default_currency'] == 'KSh' ? 'selected' : ''; ?>>KSh</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-warning">Save General Settings</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Appearance Settings Card -->
                        <div class="col-lg-6">
                            <div class="card settings-card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold">Appearance Settings</h6>
                                </div>
                                <div class="card-body">
                                    <form id="appearanceSettingsForm">
                                        <div class="form-group">
                                            <label class="d-block">Dark Mode</label>
                                            <label class="switch">
                                                <input type="checkbox" id="darkModeToggle" name="darkMode" <?php echo $settings['dark_mode'] == '1' ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                        <button type="submit" class="btn btn-warning">Save Appearance Settings</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Security Settings Card -->
                        <div class="col-lg-6">
                            <div class="card settings-card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold">Security Settings</h6>
                                </div>
                                <div class="card-body">
                                    <form id="securitySettingsForm">
                                        <div class="form-group">
                                            <label for="sessionTimeout">Session Timeout (minutes)</label>
                                            <input type="number" class="form-control" id="sessionTimeout" name="sessionTimeout" value="<?php echo $settings['session_timeout']; ?>" required min="1">
                                        </div>
                                        <div class="form-group">
                                            <label for="maxLoginAttempts">Max Login Attempts</label>
                                            <input type="number" class="form-control" id="maxLoginAttempts" name="maxLoginAttempts" value="<?php echo $settings['max_login_attempts']; ?>" required min="1">
                                        </div>
                                        <div class="form-group">
                                            <label class="d-block">Two-Factor Authentication</label>
                                            <label class="switch">
                                                <input type="checkbox" id="twoFactorAuthToggle" name="twoFactorAuth" <?php echo $settings['two_factor_auth'] == '1' ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                        <button type="submit" class="btn btn-warning">Save Security Settings</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- System Settings Card -->
                        <div class="col-lg-6">
                            <div class="card settings-card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold">System Settings</h6>
                                </div>
                                <div class="card-body">
                                    <form id="systemSettingsForm">
                                        <div class="form-group">
                                            <label class="d-block">Maintenance Mode</label>
                                            <label class="switch">
                                                <input type="checkbox" id="maintenanceModeToggle" name="maintenanceMode" <?php echo $settings['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                        <div class="form-group">
                                            <label for="backupFrequency">Automatic Backup Frequency</label>
                                            <select class="form-control" id="backupFrequency" name="backupFrequency" required>
                                                <option value="daily" <?php echo $settings['backup_frequency'] == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                                <option value="weekly" <?php echo $settings['backup_frequency'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                                <option value="monthly" <?php echo $settings['backup_frequency'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="backupTime">Backup Time (24-hour format)</label>
                                            <input type="time" class="form-control" id="backupTime" name="backupTime" value="<?php echo $settings['backup_time']; ?>" required>
                                        </div>
                                        <button type="submit" class="btn btn-warning">Save System Settings</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- End of Main Content -->

                <!-- Footer -->
                <footer class="sticky-footer bg-white">
                    <div class="container my-auto">
                        <div class="copyright text-center my-auto">
                            <span>© 2024 Lato Management System. All rights reserved.</span>
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
                        <a class="btn btn-primary" href="../views/logout.php">Logout</a>
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

        <script>
$(document).ready(function() {
    function showAlert(message, type) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        var alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${icon} mr-2"></i>
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        var $alert = $(alertHtml);
        $('#alertContainer').append($alert);
        
        // Auto-hide the alert after 5 seconds
        setTimeout(function() {
            $alert.alert('close');
        }, 5000);
    }

    // General Settings Form Submission
    $('#generalSettingsForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: '../models/settings_handler.php',
            type: 'POST',
            data: $(this).serialize() + '&action=general',
            dataType: 'json',
            success: function(response) {
                showAlert(response.message, response.success ? 'success' : 'error');
                if (response.success) {
                    // Update relevant parts of the page if needed
                }
            },
            error: function() {
                showAlert('An error occurred. Please try again.', 'error');
            }
        });
    });

    // Appearance Settings Form Submission
    $('#appearanceSettingsForm').submit(function(e) {
        e.preventDefault();
        var darkMode = $('#darkModeToggle').is(':checked') ? 1 : 0;
        $.ajax({
            url: '../models/settings_handler.php',
            type: 'POST',
            data: { action: 'appearance', darkMode: darkMode },
            dataType: 'json',
            success: function(response) {
                showAlert(response.message, response.success ? 'success' : 'error');
                if (response.success) {
                    $('body').toggleClass('bg-dark text-white', darkMode === 1);
                }
            },
            error: function() {
                showAlert('An error occurred. Please try again.', 'error');
            }
        });
    });

    // Security Settings Form Submission
    $('#securitySettingsForm').submit(function(e) {
        e.preventDefault();
        var twoFactorAuth = $('#twoFactorAuthToggle').is(':checked') ? 1 : 0;
        $.ajax({
            url: '../models/settings_handler.php',
            type: 'POST',
            data: $(this).serialize() + '&action=security&twoFactorAuth=' + twoFactorAuth,
            dataType: 'json',
            success: function(response) {
                showAlert(response.message, response.success ? 'success' : 'error');
                if (response.success) {
                    // Update relevant parts of the page if needed
                }
            },
            error: function() {
                showAlert('An error occurred. Please try again.', 'error');
            }
        });
    });

    // System Settings Form Submission
    $('#systemSettingsForm').submit(function(e) {
        e.preventDefault();
        var maintenanceMode = $('#maintenanceModeToggle').is(':checked') ? 1 : 0;
        $.ajax({
            url: '../models/settings_handler.php',
            type: 'POST',
            data: $(this).serialize() + '&action=system&maintenanceMode=' + maintenanceMode,
            dataType: 'json',
            success: function(response) {
                showAlert(response.message, response.success ? 'success' : 'error');
                if (response.success) {
                    if (maintenanceMode === 1) {
                        showAlert('Maintenance mode is now active. Non-admin users will be redirected.', 'success');
                    }
                    showAlert('Backup settings updated. The new schedule will take effect immediately.', 'success');
                }
            },
            error: function() {
                showAlert('An error occurred. Please try again.', 'error');
            }
        });
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

            // Prevent the content wrapper from scrolling when the fixed side navigation hovered over
            $('body.fixed-nav .sidebar').on('mousewheel DOMMouseScroll wheel', function(e) {
                if ($(window).width() > 768) {
                    var e0 = e.originalEvent,
                        delta = e0.wheelDelta || -e0.detail;
                    this.scrollTop += (delta < 0 ? 1 : -1) * 30;
                    e.preventDefault();
                }
            });

            // Scroll to top button appear
            $(document).on('scroll', function() {
                var scrollDistance = $(this).scrollTop();
                if (scrollDistance > 100) {
                    $('.scroll-to-top').fadeIn();
                } else {
                    $('.scroll-to-top').fadeOut();
                }
            });

            // Smooth scrolling using jQuery easing
            $(document).on('click', 'a.scroll-to-top', function(e) {
                var $anchor = $(this);
                $('html, body').stop().animate({
                    scrollTop: ($($anchor.attr('href')).offset().top)
                }, 1000, 'easeInOutExpo');
                e.preventDefault();
            });
        });
        </script>

    </body>

</html>