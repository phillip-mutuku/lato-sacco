<?php
// Set timezone
date_default_timezone_set("Africa/Nairobi");

// Include required files and initialize the database connection
require_once '../helpers/session.php';
require_once '../config/class.php';
$db = new db_class();

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'cashier')) {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: index.php');
    exit();
}

// Get the next shareholder number
$query = "SELECT MAX(CAST(shareholder_no AS UNSIGNED)) AS max_no FROM client_accounts";
$result = $db->conn->query($query);
$row = $result->fetch_assoc();
$next_shareholder_no = str_pad(($row['max_no'] + 1), 3, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lato Management System - Growing with you</title>

    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <!-- Font Awesome Icons -->
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Custom styles -->
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    <link href="../public/css/select2.css" rel="stylesheet">
    
    <style>
        .container-fluid .card{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
        }
        .modal-body .form-group {
            margin-bottom: 15px;
        }
        .modal-body .form-control {
            width: 100%;
        }

        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        .select2-container--default .select2-selection--multiple {
            min-height: 38px;
        }

       
/* Enhanced Input Group Styles */
        .input-group {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            width: 100%;
        }

        .input-group .form-control {
            position: relative;
            flex: 1 1 auto;
            width: 1%;
            min-width: 0;
            margin-bottom: 0;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            border-right: 0;
        }

        .input-group-append {
            margin-left: 0;
            display: flex;
        }

        .input-group-append .btn {
            position: relative;
            z-index: 2;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-left: 1px solid #ced4da;
            background-color: #f8f9fa;
            border-color: #ced4da;
            color: #6c757d;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.5;
            transition: all 0.15s ease-in-out;
        }

        .input-group-append .btn:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
            color: #495057;
        }

        .input-group-append .btn:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            z-index: 3;
        }

        .input-group .form-control:focus {
            z-index: 3;
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        /* Loading animation for the button */
        .btn-loading {
            pointer-events: none;
            opacity: 0.6;
        }

        .btn-loading .fas {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Ensure proper form control sizing */
        .form-group .input-group .form-control {
            height: calc(1.5em + 0.75rem + 2px);
        }

        .form-group .input-group .btn {
            height: calc(1.5em + 0.75rem + 2px);
        }

        /* Remove any conflicting styles */
        .input-group > .form-control:not(:last-child) {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group > .input-group-append:last-child > .btn:not(:last-child):not(.dropdown-toggle) {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group > .input-group-append:last-child > .btn:last-child {
            border-top-right-radius: 0.25rem;
            border-bottom-right-radius: 0.25rem;
        }
        
        /* Toast Container Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        }

        .toast {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-bottom: 10px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            overflow: hidden;
            border-left: 4px solid #28a745;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast.toast-error {
            border-left-color: #dc3545;
        }

        .toast.toast-success {
            border-left-color: #28a745;
        }

        .toast.toast-warning {
            border-left-color: #ffc107;
        }

        .toast-header {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.03);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .toast-icon {
            margin-right: 8px;
            font-size: 16px;
        }

        .toast-title {
            font-weight: 600;
            font-size: 14px;
            flex: 1;
            margin: 0;
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
            color: #666;
        }

        .toast-close:hover {
            color: #000;
        }

        .toast-body {
            padding: 12px 16px;
            font-size: 13px;
            color: #666;
        }

        .toast-success .toast-icon {
            color: #28a745;
        }

        .toast-error .toast-icon {
            color: #dc3545;
        }

        .toast-warning .toast-icon {
            color: #ffc107;
        }

        .toast-success .toast-title {
            color: #28a745;
        }

        .toast-error .toast-title {
            color: #dc3545;
        }

        .toast-warning .toast-title {
            color: #ffc107;
        }
    </style>
</head>

<body id="page-top">
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Page Wrapper -->
    <div id="wrapper">
   <!-- Include Sidebar and Header -->
    <?php include '../components/includes/cashier_sidebar.php'; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid pt-4">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Manage Clients' Accounts</h1>
                        <button class="btn btn-warning" data-toggle="modal" data-target="#addAccountModal">Add New Account</button>
                    </div>

                    <!-- Search and Filters Section -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <form class="form-inline" method="GET">
                                <input type="text" class="form-control mr-2" name="search_query" placeholder="Search by Shareholder No or National ID" value="<?php echo isset($_GET['search_query']) ? $_GET['search_query'] : ''; ?>">
                                <button style="background-color: #51087E; color: white;" type="submit" class="btn">Search</button>
                                <a href="account.php" class="btn btn-warning ml-2">Refresh</a>
                            </form>
                        </div>
                    </div>

                    <!-- Account Table -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Shareholder No</th>
                                            <th>National ID</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Phone Number</th>
                                            <th>Division</th>
                                            <th>Village</th>
                                            <th>Account Type</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    // Search and filter logic
                                    $search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';
                                    $query = "SELECT * FROM `client_accounts` WHERE `shareholder_no` LIKE '%$search_query%' OR `national_id` LIKE '%$search_query%'";
                                    $tbl_accounts = $db->conn->query($query);
                                    $i = 1;
                                    while ($fetch = $tbl_accounts->fetch_array()) {
                                        // Account types will be stored as comma-separated string
                                        $account_types = explode(', ', $fetch['account_type']);
                                        // Clean up any empty values and trim whitespace
                                        $account_types = array_filter(array_map('trim', $account_types));
                                        // Join them back with line breaks for better readability
                                        $formatted_account_types = implode(', ', $account_types);
                                    ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo $fetch['shareholder_no']; ?></td>
                                        <td><?php echo $fetch['national_id']; ?></td>
                                        <td><?php echo $fetch['first_name']; ?></td>
                                        <td><?php echo $fetch['last_name']; ?></td>
                                        <td><?php echo $fetch['phone_number']; ?></td>
                                        <td><?php echo $fetch['division']; ?></td>
                                        <td><?php echo $fetch['village']; ?></td>
                                        <td><?php echo $formatted_account_types; ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $fetch['account_id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    Action
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $fetch['account_id']; ?>">
                                                    <a class="dropdown-item" href="#" onclick="viewAccount(<?php echo $fetch['account_id']; ?>)">View</a>
                                                    <a class="dropdown-item bg-warning text-white" href="#" onclick="editAccount(<?php echo $fetch['account_id']; ?>)">Edit</a>
                                                    <a class="dropdown-item bg-danger text-white" href="#" onclick="deleteAccount(<?php echo $fetch['account_id']; ?>)">Delete</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- End of Main Content -->

                <!-- Add Account Modal -->
                <div class="modal fade" id="addAccountModal" tabindex="-1" role="dialog" aria-labelledby="addAccountModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div style="background: #51087E; color: white;" class="modal-header">
                                <h5 class="modal-title" id="addAccountModalLabel">Enter Client's Details</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span style="color: white;" aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="addAccountForm">
                                    <div class="form-group">
                                        <label for="first_name">First Name</label>
                                        <input type="text" class="form-control" name="first_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="last_name">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="shareholder_no">Shareholder No</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="shareholder_no" id="shareholder_no" required>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" id="suggestShareholderNo" title="Suggest next shareholder number">
                                                    <i class="fas fa-magic"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Click the magic wand to auto-suggest the next number</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="national_id">National ID</label>
                                        <input type="number" class="form-control" name="national_id" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="text" class="form-control" name="phone" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="location">Location</label>
                                        <input type="text" class="form-control" name="location" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="division">Division</label>
                                        <input type="text" class="form-control" name="division" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="village">Village</label>
                                        <input type="text" class="form-control" name="village" required>
                                    </div>
                                    <div class="form-group">
                                    <label for="account_type">Account Types</label>
                                    <select name="account_type[]" class="form-control" multiple required>
                                        <option value="Mumbi account">Mumbi account - current and fixed deposit (4% p.a)</option>
                                        <option value="Tusome account">Tusome account</option>
                                        <option value="Jijenge account">Jijenge account</option>
                                        <option value="Loan savings account">Loan savings account - 3x member loan</option>
                                        <option value="Shares account">Shares account</option>
                                        <option value="Wekeza savings account">Wekeza savings account</option>
                                        <option value="Burial fund account">Burial fund account</option>
                                        <option value="Business group account">Business group account</option>
                                        <option value="Divided account">Divided account</option>
                                    </select>
                                </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-warning" onclick="addAccount()">Add Account</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Account Modal -->
                <div class="modal fade" id="updateAccountModal" tabindex="-1" role="dialog" aria-labelledby="updateAccountModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div style="background: #51087E; color: white;" class="modal-header">
                                <h5 class="modal-title" id="updateAccountModalLabel">Update Client's Details</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span style="color: white;" aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="updateAccountForm">
                                    <input type="hidden" name="account_id" id="account_id">
                                    <div class="form-group">
                                        <label for="update_first_name">First Name</label>
                                        <input type="text" class="form-control" id="update_first_name" name="first_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="update_last_name">Last Name</label>
                                        <input type="text" class="form-control" id="update_last_name" name="last_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="update_shareholder_no">Shareholder No</label>
                                        <input type="text" class="form-control" id="update_shareholder_no" name="shareholder_no" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="update_national_id">National ID</label>
                                        <input type="number" class="form-control" id="update_national_id" name="national_id" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="update_phone_number">Phone Number</label>
                                        <input type="text" class="form-control" id="update_phone_number" name="phone_number" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="update_email">Email</label>
                                        <input type="email" class="form-control" id="update_email" name="email">
                                    </div>
                                    <div class="form-group">
                                        <label for="update_location">Location</label>
                                        <input type="text" class="form-control" id="update_location" name="location">
                                    </div>
                                    <div class="form-group">
                                        <label for="update_division">Division</label>
                                        <input type="text" class="form-control" id="update_division" name="division" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="update_village">Village</label>
                                        <input type="text" class="form-control" id="update_village" name="village" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="update_account_type">Account Types</label>
                                        <select id="update_account_type" name="account_type[]" class="form-control" multiple required>
                                            <option value="Mumbi account">Mumbi account - current and fixed deposit (4% p.a)</option>
                                            <option value="Tusome account">Tusome account</option>
                                            <option value="Jijenge account">Jijenge account</option>
                                            <option value="Loan savings account">Loan savings account - 3x member loan</option>
                                            <option value="Shares account">Shares account</option>
                                            <option value="Wekeza savings account">Wekeza savings account</option>
                                            <option value="Burial fund account">Burial fund account</option>
                                            <option value="Business group account">Business group account</option>
                                            <option value="Divided account">Divided account</option>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-warning" onclick="updateAccount()">Update Account</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Deletion</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to delete this account? This action cannot be undone.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logout Modal -->
                <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="logoutModalLabel">Logout</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to logout?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <a class="btn btn-danger" href="logout.php">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <footer class="sticky-footer bg-white">
                    <div class="container my-auto">
                        <div class="copyright text-center my-auto">
                            <span>© 2024 Lato Management System. All rights reserved.</span>
                        </div>
                    </div>
                </footer>
            <!-- End of Content Wrapper -->
        </div>
        <!-- End of Page Wrapper -->
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../public/js/jquery.easing.js"></script>
    <script src="../public/js/select2.js"></script>

    <!-- Page level plugins -->
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../public/js/sb-admin-2.js"></script>

    <script>
    // Toast notification system
    class ToastManager {
        constructor() {
            this.container = document.getElementById('toastContainer');
            this.toastCount = 0;
        }

        show(message, type = 'success', title = null, duration = 5000) {
            const toastId = 'toast-' + (++this.toastCount);
            
            // Set default titles and icons based on type
            const config = {
                success: { title: title || 'Success', icon: 'fas fa-check-circle' },
                error: { title: title || 'Error', icon: 'fas fa-exclamation-circle' },
                warning: { title: title || 'Warning', icon: 'fas fa-exclamation-triangle' },
                info: { title: title || 'Information', icon: 'fas fa-info-circle' }
            };

            const toastConfig = config[type] || config.info;

            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="toast-header">
                    <i class="${toastConfig.icon} toast-icon"></i>
                    <h6 class="toast-title">${toastConfig.title}</h6>
                    <button class="toast-close" onclick="toastManager.hide('${toastId}')">&times;</button>
                </div>
                <div class="toast-body">${message}</div>
            `;

            this.container.appendChild(toast);

            // Trigger animation
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);

            // Auto hide
            if (duration > 0) {
                setTimeout(() => {
                    this.hide(toastId);
                }, duration);
            }

            return toastId;
        }

        hide(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        }

        success(message, title = null, duration = 5000) {
            return this.show(message, 'success', title, duration);
        }

        error(message, title = null, duration = 7000) {
            return this.show(message, 'error', title, duration);
        }

        warning(message, title = null, duration = 6000) {
            return this.show(message, 'warning', title, duration);
        }

        info(message, title = null, duration = 5000) {
            return this.show(message, 'info', title, duration);
        }
    }

    // Initialize toast manager
    const toastManager = new ToastManager();

    $(document).ready(function () {
        $('#dataTable').DataTable();
        
        // Initialize Select2 for both add and update forms
        $('select[name="account_type[]"]').select2({
            placeholder: 'Select account types',
            width: '100%'
        });

        // Reinitialize Select2 when update modal is shown
        $('#updateAccountModal').on('shown.bs.modal', function () {
            $('#update_account_type').select2({
                placeholder: 'Select account types',
                width: '100%',
                dropdownParent: $('#updateAccountModal')
            });
        });

        // Initialize Select2
        $('#update_account_type').select2({
            placeholder: 'Select account types',
            width: '100%',
            dropdownParent: $('#updateAccountModal')
        });

        // Add form submit handler to prevent default form submission
        $('#updateAccountForm').on('submit', function(e) {
            e.preventDefault();
            updateAccount();
        });

        // Add event listener for the suggest button
        $('#suggestShareholderNo').on('click', function() {
            suggestNextShareholderNumber();
        });
        
        // Optional: Auto-suggest when the input gets focus for the first time
        $('#shareholder_no').one('focus', function() {
            if ($(this).val() === '') {
                suggestNextShareholderNumber();
            }
        });

        // Add debounced validation on input change
        let shareholderValidationTimeout;
        $('#shareholder_no').on('input', function() {
            const value = $(this).val();
            clearTimeout(shareholderValidationTimeout);
            
            if (value) {
                shareholderValidationTimeout = setTimeout(() => {
                    validateShareholderNumber(value);
                }, 500); // Wait 500ms after user stops typing
            } else {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').remove();
            }
        });
    });

    function suggestNextShareholderNumber() {
        const button = $('#suggestShareholderNo');
        const input = $('#shareholder_no');
        
        // Add loading state
        button.addClass('btn-loading');
        button.find('.fas').removeClass('fa-magic').addClass('fa-spinner');
        
        $.ajax({
            url: '../controllers/accountController.php?action=getNextShareholderNo',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Set the suggested number in the input
                    input.val(response.next_number);
                    
                    // Focus and select the text so user can easily modify it
                    input.focus().select();
                    
                    // Show success toast
                    toastManager.success(`Next available number: ${response.next_number}`, 'Auto-Suggested', 2000);
                } else {
                    toastManager.error(response.message || 'Could not fetch next shareholder number');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching next shareholder number:', error);
                toastManager.error('Error fetching next shareholder number. Please try again.');
            },
            complete: function() {
                // Remove loading state
                button.removeClass('btn-loading');
                button.find('.fas').removeClass('fa-spinner').addClass('fa-magic');
            }
        });
    }

    // Optional: Add validation to prevent duplicate shareholder numbers
    function validateShareholderNumber(shareholderNo) {
        if (!shareholderNo) return;
        
        $.ajax({
            url: '../controllers/accountController.php?action=checkShareholderNo',
            method: 'POST',
            data: { shareholder_no: shareholderNo },
            dataType: 'json',
            success: function(response) {
                const input = $('#shareholder_no');
                if (response.exists) {
                    input.addClass('is-invalid');
                    // Show error message
                    if (!input.siblings('.invalid-feedback').length) {
                        input.after('<div class="invalid-feedback">This shareholder number already exists</div>');
                    }
                } else {
                    input.removeClass('is-invalid');
                    input.siblings('.invalid-feedback').remove();
                }
            }
        });
    }

    function addAccount() {
        // Validate required fields
        var required = ['first_name', 'last_name', 'shareholder_no', 'national_id', 'phone', 'account_type[]'];
        var isValid = true;
        
        required.forEach(function(field) {
            var input = $('[name="' + field + '"]');
            if (!input.val() || (input.is('select') && input.val().length === 0)) {
                input.addClass('is-invalid');
                isValid = false;
            } else {
                input.removeClass('is-invalid');
            }
        });

        if (!isValid) {
            toastManager.error('Please fill in all required fields');
            return;
        }

        $.ajax({
            url: '../controllers/accountController.php?action=create',
            method: 'POST',
            data: $('#addAccountForm').serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    toastManager.success('Account added successfully!');
                    $('#addAccountModal').modal('hide');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastManager.error(response.message || 'Error adding account. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Add account error:', xhr.responseText);
                toastManager.error('Error adding account. Please try again.');
            }
        });
    }

    function viewAccount(accountId) {
        window.open('cashier_view_account.php?id=' + accountId, '_blank');
    }

    // Function to load data and open modal
    function editAccount(accountId) {
        $.ajax({
            url: '../controllers/accountController.php?action=get',
            method: 'POST',
            data: { account_id: accountId },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    var account = response.data;
                    
                    // Populate the form fields
                    $('#account_id').val(account.account_id);
                    $('#update_first_name').val(account.first_name);
                    $('#update_last_name').val(account.last_name);
                    $('#update_shareholder_no').val(account.shareholder_no);
                    $('#update_national_id').val(account.national_id);
                    $('#update_phone_number').val(account.phone_number);
                    $('#update_email').val(account.email);
                    $('#update_location').val(account.location);
                    $('#update_division').val(account.division);
                    $('#update_village').val(account.village);
                    
                    // Handle account types
                    var accountTypes = account.account_type.split(',').map(function(item) {
                        return item.trim();
                    });
                    $('#update_account_type').val(accountTypes).trigger('change');
                    
                    // Open the modal
                    $('#updateAccountModal').modal('show');
                } else {
                    toastManager.error(response.message || 'Error fetching account details.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Edit account error:', xhr.responseText);
                toastManager.error('Error fetching account details. Please try again.');
            }
        });
    }

    // Function to save the updated data
    function updateAccount() {
        // Validate all fields are filled
        var isValid = true;
        var requiredFields = [
            'account_id', 'first_name', 'last_name', 'shareholder_no',
            'national_id', 'phone_number', 'division', 'village'
        ];

        // Check each required field
        requiredFields.forEach(function(field) {
            var input = $('[name="' + field + '"]');
            if (!input.val()) {
                isValid = false;
                input.addClass('is-invalid');
            } else {
                input.removeClass('is-invalid');
            }
        });

        // Check account types
        if ($('#update_account_type').val().length === 0) {
            isValid = false;
            $('#update_account_type').next('.select2-container').addClass('is-invalid');
        } else {
            $('#update_account_type').next('.select2-container').removeClass('is-invalid');
        }

        if (!isValid) {
            toastManager.error('Please fill in all required fields');
            return;
        }

        $.ajax({
            url: '../controllers/accountController.php?action=update',
            method: 'POST',
            data: $('#updateAccountForm').serialize(),
            dataType: 'json',
            success: function(response) {
                console.log('Server response:', response);
                if(response.status === 'success') {
                    toastManager.success('Account updated successfully!');
                    $('#updateAccountModal').modal('hide');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastManager.error(response.message || 'Error updating account. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Update error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                toastManager.error('Error updating account. Please try again.');
            }
        });
    }

    function deleteAccount(accountId) {
        $('#deleteConfirmModal').modal('show');
        $('#confirmDelete').data('id', accountId);
    }

    $('#confirmDelete').on('click', function() {
        var accountId = $(this).data('id');
        
        $.ajax({
            url: '../controllers/accountController.php?action=delete',
            method: 'POST',
            data: { account_id: accountId },
            dataType: 'json',
            success: function(response) {
                console.log('Delete response:', response);
                
                // Handle both JSON response and plain text response for backward compatibility
                if (typeof response === 'object') {
                    if (response.status === 'success') {
                        toastManager.success('Account deleted successfully!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastManager.error(response.message || 'Error deleting account. Please try again.');
                    }
                } else if (typeof response === 'string') {
                    // Handle plain text response
                    if (response.toLowerCase().includes('success')) {
                        toastManager.success('Account deleted successfully!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastManager.error('Error deleting account. Please try again.');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                // Try to parse the response text
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        toastManager.success('Account deleted successfully!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastManager.error(response.message || 'Error deleting account. Please try again.');
                    }
                } catch (e) {
                    // If response is not JSON, check for success indicators
                    if (xhr.responseText && xhr.responseText.toLowerCase().includes('success')) {
                        toastManager.success('Account deleted successfully!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastManager.error('Error deleting account. Please try again.');
                    }
                }
            }
        });
        
        $('#deleteConfirmModal').modal('hide');
    });

    // Sidebar toggle functionality
    $(document).ready(function() {
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