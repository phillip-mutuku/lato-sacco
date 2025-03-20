<?php
// Set timezone
date_default_timezone_set("Africa/Nairobi");

// Include required files and initialize the database connection
require_once '../helpers/session.php';
require_once '../config/class.php';
$db = new db_class();

// Check if user is logged in and is a cashier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
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

 
        html, body {
            overflow-x: hidden;
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

        .select2-container--default .select2-selection--multiple {
    min-height: 38px;
}
  
    </style>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul style="background: #51087E;"  class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
                <div class="sidebar-brand-text mx-3">LATO SACCO</div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item">
                <a class="nav-link" href="cashier.php">
                    <i class="fas fa-fw fa-home"></i>
                    <span>Home</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Management
            </div>

            <li class="nav-item">
                <a class="nav-link" href="../models/cashier_disbursement.php">
                    <i class="fas fa-fw fas fa-coins"></i>
                    <span>Disbursements</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="cashier-daily-reconciliation.php">
                    <i class="fas fa-fw fa-balance-scale"></i>
                    <span>Daily Reconciliation</span>
                </a>
            </li>


            <li class="nav-item active">
                <a class="nav-link" href="cashier_manage_expenses.php">
                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    <span>Manage Expenses</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="../models/cashier_arrears.php">
                <i class="fas fa-users-slash fa-2x text-gray-300"></i>
                    <span>Arrears</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="cashier-account.php">
                <i class="fas fa-fw fa-user"></i>
                    <span>Client Accounts</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/cashier_groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Wekeza Groups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/cashier_business_groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Business Groups</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                System
            </div>

            <li class="nav-item active">
                <a class="nav-link" href="cashier_announcements.php">
                    <i class="fas fa-fw fa-bullhorn"></i>
                    <span>Announcements</span>
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
                                <a href="cashier-account.php" class="btn btn-warning ml-2">Refresh</a>
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
                                                    <a style="display: none;" class="dropdown-item bg-danger text-white" href="#" onclick="deleteAccount(<?php echo $fetch['account_id']; ?>)">Delete</a>
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
                                            <input type="text" class="form-control" name="shareholder_no" id="shareholder_no" required>
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

            </div>
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

    // Log form data when update button is clicked (for debugging)
    $('.btn-update-account').on('click', function() {
        console.log('Form values:', {
            accountId: $('#account_id').val(),
            firstName: $('#update_first_name').val(),
            lastName: $('#update_last_name').val(),
            accountType: $('#update_account_type').val()
        });
    });
    });



    function addAccount() {
        // Validate required fields
        var required = ['first_name', 'last_name', 'shareholder_no', 'national_id', 'phone', 'account_type[]'];
        var isValid = true;
        
        required.forEach(function(field) {
            var input = $('[name="' + field + '"]');
            if (!input.val()) {
                input.addClass('is-invalid');
                isValid = false;
            } else {
                input.removeClass('is-invalid');
            }
        });

        if (!isValid) {
            showErrorMessage('Please fill in all required fields');
            return;
        }

        $.ajax({
            url: '../controllers/accountController.php?action=create',
            method: 'POST',
            data: $('#addAccountForm').serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showSuccessMessage('Account added successfully!');
                    $('#addAccountModal').modal('hide');
                    location.reload();
                } else {
                    showErrorMessage(response.message || 'Error adding account. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                showErrorMessage('Error adding account. Please try again.');
                console.error(xhr.responseText);
            }
        });
    }

function showSuccessMessage(message) {
    showMessage(message, 'success');
}

function showErrorMessage(message) {
    showMessage(message, 'error');
}

function showMessage(message, type) {
    var messageDiv = $('<div>')
        .addClass('alert')
        .addClass(type === 'success' ? 'alert-success' : 'alert-danger')
        .text(message)
        .css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'z-index': 9999,
            'padding': '15px',
            'border-radius': '5px',
            'box-shadow': '0 0 10px rgba(0,0,0,0.2)'
        });

    $('body').append(messageDiv);

    setTimeout(function() {
        messageDiv.fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
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
                showErrorMessage(response.message || 'Error fetching account details.');
            }
        },
        error: function() {
            showErrorMessage('Error fetching account details. Please try again.');
        }
    });
}




// Function to save the updated data
function updateAccount() {
    // Get all form values first
    var formData = {
        account_id: $('#account_id').val(),
        first_name: $('#update_first_name').val(),
        last_name: $('#update_last_name').val(),
        shareholder_no: $('#update_shareholder_no').val(),
        national_id: $('#update_national_id').val(),
        phone_number: $('#update_phone_number').val(),
        division: $('#update_division').val(),
        village: $('#update_village').val(),
        account_type: $('#update_account_type').val()
    };

    // Debug log to see what values we're getting
    console.log('Form Data:', formData);

    // Validate all fields are filled
    var isValid = true;
    var emptyFields = [];

    // Check each field
    Object.entries(formData).forEach(([field, value]) => {
        if (!value || (Array.isArray(value) && value.length === 0)) {
            isValid = false;
            emptyFields.push(field);
            $(`[name="${field}"]`).addClass('is-invalid');
        } else {
            $(`[name="${field}"]`).removeClass('is-invalid');
        }
    });

    // If validation fails, show which fields are empty
    if (!isValid) {
        console.log('Empty fields:', emptyFields);
        showErrorMessage('Please fill in all required fields: ' + emptyFields.join(', '));
        return;
    }

    // If we get here, form is valid - proceed with AJAX
    $.ajax({
        url: '../controllers/accountController.php?action=update',
        method: 'POST',
        data: $('#updateAccountForm').serialize(),
        dataType: 'json',
        success: function(response) {
            console.log('Server response:', response); // Debug log
            if(response.status === 'success') {
                showSuccessMessage('Account updated successfully!');
                $('#updateAccountModal').modal('hide');
                location.reload();
            } else {
                showErrorMessage(response.message || 'Error updating account. Please try again.');
            }
        },
        error: function(xhr, status, error) {
            console.error('Update error:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            showErrorMessage('Error updating account. Please try again.');
        }
    });
}

// Helper function for error messages with more visibility
function showErrorMessage(message) {
    console.log('Error message:', message); 
    var messageDiv = $('<div>')
        .addClass('alert alert-danger alert-dismissible fade show')
        .css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'z-index': '9999',
            'max-width': '400px',
            'box-shadow': '0 0 10px rgba(0,0,0,0.2)'
        })
        .html(`
            <strong>Error:</strong><br>
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `);

    $('body').append(messageDiv);

    setTimeout(function() {
        messageDiv.alert('close');
    }, 7000);
}



// Helper function to validate form
function validateUpdateForm() {
    var isValid = true;
    var requiredFields = [
        'account_id',
        'first_name',
        'last_name',
        'shareholder_no',
        'national_id',
        'phone_number',
        'division',
        'village',
        'account_type[]'
    ];

    requiredFields.forEach(function(field) {
        var input = $('[name="' + field + '"]');
        if (!input.val()) {
            input.addClass('is-invalid');
            isValid = false;
        } else {
            input.removeClass('is-invalid');
        }
    });

    if (!isValid) {
        showErrorMessage('Please fill in all required fields');
    }

    return isValid;
}

// Helper functions for showing messages
function showSuccessMessage(message) {
    showMessage(message, 'success');
}


function showMessage(message, type) {
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    var messageDiv = $('<div>')
        .addClass('alert ' + alertClass + ' alert-dismissible fade show')
        .css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'z-index': '9999',
            'max-width': '300px'
        })
        .html(message + '<button type="button" class="close" data-dismiss="alert">&times;</button>');

    $('body').append(messageDiv);

    setTimeout(function() {
        messageDiv.alert('close');
    }, 5000);
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
            success: function(response) {
                if(response === 'success') {
                    alert('Account deleted successfully!');
                    location.reload();
                } else {
                    alert('Error deleting account. Please try again.');
                }
            },
            error: function() {
                alert('Error deleting account. Please try again.');
            }
        });
        $('#deleteConfirmModal').modal('hide');
    });
    </script>


<script>
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