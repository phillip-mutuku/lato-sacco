<?php
    date_default_timezone_set("Africa/Nairobi");
    require_once '../helpers/session.php';
    require_once '../config/class.php';
    $db = new db_class(); 

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'officer')) {
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
    <title>Lato Management System - Loans</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    <link href="../public/css/select2.css" rel="stylesheet">
    <style>
        .modal-lg { max-width: 80% !important; }
        .form-group label { font-weight: bold; }
        .select2-container { width: 100% !important; }
        #loan_calculation_results { background-color: #f8f9fc; padding: 15px; border-radius: 5px; margin-top: 15px; }
        #loan_calculation_results h5 { margin-bottom: 15px; }
        #loan_calculation_results p { margin-bottom: 5px; }
        .modal-body .row { margin-bottom: 15px; }

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

        .container-fluid .card{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }
        
        /* Additional styles for DataTables performance optimization */
        .dataTables_processing {
            height: 60px !important;
            background: rgba(255, 255, 255, 0.9) !important;
            z-index: 100;
        }
        
        .badge {
            font-size: 85%;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 1.5rem;
            height: 1.5rem;
            vertical-align: text-bottom;
            border: 0.2em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border .75s linear infinite;
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
                <a class="nav-link" href="../views/field-officer.php">
                    <i class="fas fa-fw fa-home"></i>
                    <span>Home</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Management
            </div>

            <li class="nav-item">
                <a class="nav-link" href="officer_loan.php">
                <i class="fas fa-fw fas fa-comment-dollar"></i>
                    <span>New Loan</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="officer_disbursement.php">
                    <i class="fas fa-fw fas fa-coins"></i>
                    <span>Disbursements</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="officer_arrears.php">
                <i class="fas fa-users-slash fa-2x text-gray-300"></i>
                    <span>Arrears</span>
                </a>
            </li>


            <li class="nav-item">
                <a class="nav-link" href="officer_groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Wekeza Groups</span>
                </a>
            </li>


            <hr class="sidebar-divider">

                <div class="sidebar-heading">
                    System
                </div>

                <li class="nav-item active">
                    <a class="nav-link" href="../views/officer_announcements.php">
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

                <!-- Begin Page Content -->
                <div class="container-fluid pt-4">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Manage Loans</h1>
                        <div>
                            <button id="refreshLoansBtn" class="btn btn-sm btn-info mr-2">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <button class="btn btn-lg btn-warning" href="#" data-toggle="modal" data-target="#addModal">
                                <i class="fas fa-plus"></i> Create New Loan Application
                            </button>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="loansTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Reference No</th>
                                            <th>Date Applied</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded via AJAX -->
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
        
        <!-- Loan Details Modal -->
        <div class="modal fade" id="loanDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background: #51087E;">
                        <h5 class="modal-title text-white">Loan Details</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="loanDetailsLoading" class="text-center p-4">
                            <i class="fas fa-spinner fa-spin fa-3x"></i>
                            <p class="mt-2">Loading loan details...</p>
                        </div>
                        
                        <div id="loanDetailsContent" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="m-0 font-weight-bold">Client Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm table-borderless">
                                                <tbody>
                                                    <tr>
                                                        <td width="40%"><strong>Name:</strong></td>
                                                        <td id="loanClientName"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Phone Number:</strong></td>
                                                        <td id="loanClientPhone"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Location:</strong></td>
                                                        <td id="loanClientLocation"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="m-0 font-weight-bold">Client Pledges</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-sm mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Item</th>
                                                        <th>Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="clientPledgesTableBody">
                                                    <!-- Client pledges will be loaded here -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="m-0 font-weight-bold">Loan Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm table-borderless">
                                                <tbody>
                                                    <tr>
                                                        <td width="40%"><strong>Reference No:</strong></td>
                                                        <td id="loanRefNo"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Loan Product:</strong></td>
                                                        <td id="loanType"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Loan Term:</strong></td>
                                                        <td id="loanTerm"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Interest Rate:</strong></td>
                                                        <td id="loanInterestRate"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Amount:</strong></td>
                                                        <td id="loanAmount"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Total Payable:</strong></td>
                                                        <td id="loanTotalPayable"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Monthly Payment:</strong></td>
                                                        <td id="loanMonthlyPayment"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Meeting Date:</strong></td>
                                                        <td id="loanMeetingDate"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Status:</strong></td>
                                                        <td id="loanStatus"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <div id="paymentInfoSection" style="display: none;">
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">
                                                <h6 class="m-0 font-weight-bold">Payment Information</h6>
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-sm table-borderless">
                                                    <tbody>
                                                        <tr>
                                                            <td width="50%"><strong>Next Payment Date:</strong></td>
                                                            <td id="nextPaymentDate"></td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Monthly Amount:</strong></td>
                                                            <td id="monthlyAmount"></td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Penalty:</strong></td>
                                                            <td id="penaltyAmount"></td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Payable Amount:</strong></td>
                                                            <td id="totalPayableAmount" class="font-weight-bold"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="m-0 font-weight-bold">Guarantor Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm table-borderless">
                                                <tbody>
                                                    <tr>
                                                        <td width="40%"><strong>Name:</strong></td>
                                                        <td id="guarantorName"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>ID Number:</strong></td>
                                                        <td id="guarantorId"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Phone Number:</strong></td>
                                                        <td id="guarantorPhone"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Location:</strong></td>
                                                        <td id="guarantorLocation"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Sub-location:</strong></td>
                                                        <td id="guarantorSublocation"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Village:</strong></td>
                                                        <td id="guarantorVillage"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="m-0 font-weight-bold">Guarantor Pledges</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-sm mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Item</th>
                                                        <th>Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="guarantorPledgesTableBody">
                                                    <!-- Guarantor pledges will be loaded here -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-warning d-none" id="editLoanBtn">Edit Loan</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add Loan Modal-->
        <div class="modal fade" id="addModal" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form method="POST" action="../controllers/save_loan.php" id="loanForm">
                    <div class="modal-content">
                        <div style="background-color: #51087E;" class="modal-header">
                            <h5 class="modal-title text-white">New Loan Application</h5>
                            <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Step 1: Client Details -->
                            <div id="step1" class="form-step">
                                <h6 class="mb-3">Client Details</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Client</label>
                                            <select name="client" class="form-control client-select" required>
                                                <option value="">Select a client</option>
                                                <?php
                                                    $clients = $db->display_client_accounts();
                                                    while($client = $clients->fetch_array()){
                                                        echo "<option value='".$client['account_id']."'>".$client['last_name'].", ".$client['first_name']." (".$client['shareholder_no'].")</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Loan Product</label>
                                            <select name="loan_product_id" class="form-control loan-product-select" required>
                                                <option value="">Select a loan product</option>
                                                <?php
                                                    $loan_products = $db->get_loan_types();
                                                    foreach($loan_products as $product){
                                                        echo "<option value='".$product['id']."' data-interest='".$product['interest_rate']."'>".$product['loan_type']." (".$product['interest_rate']."% interest)</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Loan Amount</label>
                                            <input type="number" name="loan_amount" id="loan_amount" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Loan Term (months)</label>
                                            <input type="number" name="loan_term" id="loan_term" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Meeting Date</label>
                                    <input type="date" name="meeting_date" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Purpose</label>
                                    <textarea name="purpose" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Client Pledges</label>
                                    <div id="clientPledges">
                                        <div class="pledge-entry row mb-2">
                                            <div class="col-md-6">
                                                <input type="text" name="client_pledges[0][item]" class="form-control" placeholder="Item" required>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="number" name="client_pledges[0][value]" class="form-control" placeholder="Value" required>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-secondary mt-2" onclick="addPledge('client')">Add More Pledge</button>
                                </div>
                                <button type="button" class="btn btn-warning" onclick="showStep(2)">Next</button>
                            </div>

                            <!-- Step 2: Guarantor Details -->
                            <div id="step2" class="form-step" style="display: none;">
                                <h6 class="mb-3">Guarantor Details</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Full Name</label>
                                            <input type="text" name="guarantor_name" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>National ID</label>
                                            <input type="text" name="guarantor_id" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Phone Number</label>
                                            <input type="text" name="guarantor_phone" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Location</label>
                                            <input type="text" name="guarantor_location" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Sub-location</label>
                                            <input type="text" name="guarantor_sublocation" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Village</label>
                                            <input type="text" name="guarantor_village" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Guarantor Pledges</label>
                                    <div id="guarantorPledges">
                                        <div class="pledge-entry row mb-2">
                                            <div class="col-md-6">
                                                <input type="text" name="guarantor_pledges[0][item]" class="form-control" placeholder="Item" required>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="number" name="guarantor_pledges[0][value]" class="form-control" placeholder="Value" required>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-secondary mt-2" onclick="addPledge('guarantor')">Add More Pledge</button>
                                </div>
                                <div id="loan_calculation_results" class="mt-3 p-3 bg-light">
                                   <h6>Loan Calculation Results</h6>
                                   <div class="row">
                                       <div class="col-md-4">
                                           <p>Monthly Payment: <strong><span id="monthly_payment"></span></strong></p>
                                       </div>
                                       <div class="col-md-4">
                                           <p>Total Interest: <strong><span id="total_interest"></span></strong></p>
                                       </div>
                                       <div class="col-md-4">
                                           <p>Total Payment: <strong><span id="total_payment"></span></strong></p>
                                       </div>
                                   </div>
                               </div>
                               <button type="button" class="btn btn-secondary" onclick="showStep(1)">Back</button>
                               <button type="submit" name="save_loan" class="btn btn-warning">Save Loan</button>
                           </div>
                       </div>
                   </div>
               </form>
           </div>
       </div>
       
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
           <div class="modal-dialog modal-lg">
               <div class="modal-content">
                   <div class="modal-header bg-info">
                       <h5 class="modal-title text-white">Loan Schedule</h5>
                       <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                           <span aria-hidden="true">×</span>
                       </button>
                   </div>
                   <div class="modal-body">
                       <div class="table-responsive">
                           <table class="table table-bordered" id="scheduleTable">
                           <thead>
                               <tr>
                                   <th>Due Date</th>
                                   <th>Principal</th>
                                   <th>Interest</th>
                                   <th>Due Amount</th>
                                   <th>Balance</th>
                                   <th>Repaid Amount</th>
                                   <th>Default Amount</th>
                                   <th>Status</th>
                               </tr>
                           </thead>
                           <tbody id="scheduleTableBody">
                               <!-- Schedule data will be inserted here -->
                           </tbody>
                           </table>
                       </div>
                   </div>
               </div>
           </div>
       </div>

       <!-- Bootstrap core JavaScript-->
       <script src="../public/js/jquery.js"></script>
       <script src="../public/js/bootstrap.bundle.js"></script>

       <!-- Core plugin JavaScript-->
       <script src="../public/js/jquery.easing.js"></script>
       <script src="../public/js/select2.js"></script>

       <!-- Custom scripts for all pages-->
       <script src="../public/js/sb-admin-2.js"></script>

       <!-- Page level plugins -->
       <script src="../public/js/jquery.dataTables.js"></script>
       <script src="../public/js/dataTables.bootstrap4.js"></script>

       <script>
       $(document).ready(function() {
           // Initialize DataTable with server-side processing
           var loansTable = $('#loansTable').DataTable({
               processing: true,
               serverSide: true,
               ajax: {
                   url: '../controllers/get_paginated_loans.php',
                   type: 'POST'
               },
               columns: [
                   { data: 'client', orderable: false },
                   { data: 'ref_no' },
                   { data: 'date_applied' },
                   { data: 'status', orderable: false },
                   { data: 'actions', orderable: false }
               ],
               order: [[2, 'desc']], // Sort by date applied descending
               pageLength: 15,
               lengthMenu: [[10, 15, 25, 50, -1], [10, 15, 25, 50, "All"]],
               language: {
                   processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>'
               },
               drawCallback: function() {
                   // Reinitialize any JS elements after table redraws
                   $('.dropdown-toggle').dropdown();
               },
               initComplete: function() {
                   // Add custom search box above the table
                   $('#loansTable_filter').addClass('mb-2');
                   $('#loansTable_filter input').addClass('form-control');
               }
           });
           
           // Initialize Select2 for dropdowns
           $('.client-select').select2({
               placeholder: "Select a client",
               allowClear: true,
               dropdownParent: $('#addModal')
           });

           $('.loan-product-select').select2({
               placeholder: "Select a loan product",
               allowClear: true,
               dropdownParent: $('#addModal')
           });

           // Handle view details button click
           $(document).on('click', '.view-details', function() {
               var loanId = $(this).data('id');
               $('#loanDetailsModal').modal('show');
               loadLoanDetails(loanId);
           });


           
            $(document).on('click', 'a[data-toggle="modal"][data-target^="#updateloan"]', function(e) {
                e.preventDefault();
                var loanId = $(this).data('target').replace('#updateloan', '');
                if (!loanId) {
                    // Try to extract from href if not in data-target
                    loanId = $(this).attr('href').replace('#updateloan', '');
                }
                
                if (loanId) {
                    // Generate the modal if it doesn't exist
                    generateLoanModals(loanId);
                    
                    // Show the modal after a small delay to ensure it's fully created
                    setTimeout(function() {
                        $('#updateloan' + loanId).modal('show');
                    }, 100);
                }
            });

            $(document).on('click', 'a[data-toggle="modal"][data-target^="#deleteloan"]', function(e) {
                e.preventDefault();
                var loanId = $(this).data('target').replace('#deleteloan', '');
                if (!loanId) {
                    // Try to extract from href if not in data-target
                    loanId = $(this).attr('href').replace('#deleteloan', '');
                }
                
                if (loanId) {
                    // Generate the modal if it doesn't exist
                    generateLoanModals(loanId);
                    
                    // Show the modal after a small delay to ensure it's fully created
                    setTimeout(function() {
                        $('#deleteloan' + loanId).modal('show');
                    }, 100);
                }
            });
           
           // Handle edit loan button click
           $(document).on('click', '.edit-loan', function() {
               var loanId = $(this).data('id');
               $('#updateloan' + loanId).modal('show');
           });
           
           // Handle delete loan button click
           $(document).on('click', '.delete-loan', function() {
               var loanId = $(this).data('id');
               $('#deleteloan' + loanId).modal('show');
           });
           
           // Handle edit button in details modal
           $('#editLoanBtn').click(function() {
               var loanId = $(this).data('id');
               $('#loanDetailsModal').modal('hide');
               setTimeout(function() {
                   $('#updateloan' + loanId).modal('show');
               }, 500);
           });
           
           // Add refresh button functionality
           $('#refreshLoansBtn').click(function() {
               loansTable.ajax.reload();
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

       let clientPledgeCount = 1;
       let guarantorPledgeCount = 1;
       let editClientPledgeCounts = {};
       let editGuarantorPledgeCounts = {};

       function showStep(step) {
           document.querySelectorAll('.form-step').forEach(el => el.style.display = 'none');
           document.getElementById('step' + step).style.display = 'block';
           
           if (step === 2) {
               calculateLoan();
           }
       }

       function addPledge(type) {
           const container = document.getElementById(type + 'Pledges');
           const count = type === 'client' ? clientPledgeCount++ : guarantorPledgeCount++;
           
           const newEntry = document.createElement('div');
           newEntry.className = 'pledge-entry row mb-2';
           newEntry.innerHTML = `
               <div class="col-md-6">
                   <input type="text" name="${type}_pledges[${count}][item]" class="form-control" placeholder="Item" required>
               </div>
               <div class="col-md-6">
                   <input type="number" name="${type}_pledges[${count}][value]" class="form-control" placeholder="Value" required>
               </div>
           `;
           
           container.appendChild(newEntry);
       }

       function addEditPledge(type, loanId) {
           const container = document.getElementById(`edit${type.charAt(0).toUpperCase() + type.slice(1)}Pledges${loanId}`);
           
           // Initialize count if not exists
           if (type === 'client') {
               if (!editClientPledgeCounts[loanId]) {
                   editClientPledgeCounts[loanId] = container.children.length;
               }
               editClientPledgeCounts[loanId]++;
               count = editClientPledgeCounts[loanId];
           } else {
               if (!editGuarantorPledgeCounts[loanId]) {
                   editGuarantorPledgeCounts[loanId] = container.children.length;
               }
               editGuarantorPledgeCounts[loanId]++;
               count = editGuarantorPledgeCounts[loanId];
           }
           
           const newEntry = document.createElement('div');
           newEntry.className = 'pledge-entry row mb-2';
           newEntry.innerHTML = `
               <div class="col-md-6">
                   <input type="text" name="${type}_pledges[${count}][item]" class="form-control" placeholder="Item" required>
               </div>
               <div class="col-md-6">
                   <input type="number" name="${type}_pledges[${count}][value]" class="form-control" placeholder="Value" required>
               </div>
           `;
           
           container.appendChild(newEntry);
       }

       function calculateLoan() {
           const loanAmount = parseFloat(document.getElementById('loan_amount').value);
           const loanTerm = parseInt(document.getElementById('loan_term').value);
           const interestRate = parseFloat(document.querySelector('.loan-product-select option:checked').dataset.interest) / 100 / 12;
           
           if (isNaN(loanAmount) || isNaN(loanTerm) || isNaN(interestRate)) {
               alert('Please fill in all the loan details correctly.');
               return;
           }
           
           const monthlyPayment = (loanAmount * interestRate * Math.pow(1 + interestRate, loanTerm)) / 
                               (Math.pow(1 + interestRate, loanTerm) - 1);
           const totalPayment = monthlyPayment * loanTerm;
           const totalInterest = totalPayment - loanAmount;
           
           document.getElementById('monthly_payment').textContent = 'KSh ' + monthlyPayment.toFixed(2);
           document.getElementById('total_interest').textContent = 'KSh ' + totalInterest.toFixed(2);
           document.getElementById('total_payment').textContent = 'KSh ' + totalPayment.toFixed(2);
           
           document.getElementById('loan_calculation_results').style.display = 'block';
       }

       function calculateEditLoan(loanId) {
           const modal = document.getElementById(`updateloan${loanId}`);
           const loanAmount = parseFloat(modal.querySelector('[name="loan_amount"]').value);
           const loanTerm = parseInt(modal.querySelector('[name="loan_term"]').value);
           const interestRate = parseFloat(modal.querySelector('[name="loan_product_id"] option:checked').dataset.interest) / 100 / 12;
           
           if (isNaN(loanAmount) || isNaN(loanTerm) || isNaN(interestRate)) {
               alert('Please fill in all the loan details correctly.');
               return;
           }
           
           const monthlyPayment = (loanAmount * interestRate * Math.pow(1 + interestRate, loanTerm)) / 
                               (Math.pow(1 + interestRate, loanTerm) - 1);
           const totalPayment = monthlyPayment * loanTerm;
           const totalInterest = totalPayment - loanAmount;
           
           // Update hidden fields with calculated values
           modal.querySelector('[name="monthly_payment"]').value = monthlyPayment.toFixed(2);
           modal.querySelector('[name="total_payable"]').value = totalPayment.toFixed(2);
           modal.querySelector('[name="total_interest"]').value = totalInterest.toFixed(2);
           
           // Update display
           const resultsDiv = modal.querySelector('.calculation-results');
           if (resultsDiv) {
               resultsDiv.innerHTML = `
                   <p>Monthly Payment: KSh ${monthlyPayment.toFixed(2)}</p>
                   <p>Total Interest: KSh ${totalInterest.toFixed(2)}</p>
                   <p>Total Payment: KSh ${totalPayment.toFixed(2)}</p>
               `;
           }
       }

       function viewLoanSchedule(loanId) {
           $.ajax({
               url: '../controllers/get_loan_schedule.php',
               type: 'GET',
               data: { loan_id: loanId },
               dataType: 'json',
               success: function(data) {
                   if (data.error) {
                       console.error("Server error:", data.error);
                       alert("Error: " + data.error);
                       return;
                   }
                   
                   var tableBody = $('#scheduleTableBody');
                   tableBody.empty();
                   
                   $.each(data, function(index, schedule) {
                       var row = '<tr>' +
                           '<td>' + schedule.due_date + '</td>' +
                           '<td>KSh ' + schedule.principal + '</td>' +
                           '<td>KSh ' + schedule.interest + '</td>' +
                           '<td>KSh ' + schedule.amount + '</td>' +
                           '<td>KSh ' + schedule.balance + '</td>' +
                           '<td>KSh ' + (schedule.repaid_amount || '0.00') + '</td>' +
                           '<td>KSh ' + (schedule.default_amount || '0.00') + '</td>' +
                           '<td>' + 
                               '<span class="badge badge-' + 
                               (schedule.status === 'paid' ? 'success' : 
                                schedule.status === 'partial' ? 'warning' : 'danger') + 
                               '">' + schedule.status + '</span>' +
                           '</td>' +
                           '</tr>';
                       tableBody.append(row);
                   });
                   
                   $('#loanScheduleModal').modal('show');
               },
               error: function(xhr, status, error) {
                   console.error("AJAX Error:", status, error);
                   console.error("Response:", xhr.responseText);
                   alert("An error occurred while fetching the loan schedule. Please try again.");
               }
           });
       }

       // Function to load loan details
       function loadLoanDetails(loanId) {
           $('#loanDetailsLoading').show();
           $('#loanDetailsContent').hide();
           $('#editLoanBtn').data('id', loanId);
           
           $.ajax({
               url: '../controllers/get_loan_details.php',
               type: 'GET',
               data: { loan_id: loanId },
               dataType: 'json',
               success: function(response) {
                   $('#loanDetailsLoading').hide();
                   
                   if (response.error) {
                       alert(response.error);
                       return;
                   }
                   
                   var loan = response.loan;
                   var clientPledges = response.client_pledges;
                   var guarantorPledges = response.guarantor_pledges;
                   var paymentInfo = response.payment_info;
                   
                   // Format the loan status
                   var statusText = '';
                   var statusClass = '';
                   switch(parseInt(loan.status)) {
                       case 0:
                           statusClass = 'badge-warning';
                           statusText = 'Pending Approval';
                           break;
                       case 1:
                           statusClass = 'badge-info';
                           statusText = 'Approved';
                           break;
                       case 2:
                           statusClass = 'badge-primary';
                           statusText = 'Released';
                           break;
                       case 3:
                           statusClass = 'badge-success';
                           statusText = 'Completed';
                           break;
                       case 4:
                           statusClass = 'badge-danger';
                           statusText = 'Denied';
                           break;
                   }
                   
                   // Populate the details table
                   $('#loanClientName').text(loan.last_name + ', ' + loan.first_name + ' (' + loan.shareholder_no + ')');
                   $('#loanClientPhone').text(loan.phone_number);
                   $('#loanClientLocation').text(loan.location);
                   
                   $('#loanRefNo').text(loan.ref_no);
                   $('#loanType').text(loan.loan_type);
                   $('#loanTerm').text(loan.loan_term + ' months');
                   $('#loanInterestRate').text(loan.interest_rate + '%');
                   $('#loanAmount').text('KSh ' + parseFloat(loan.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
                   $('#loanTotalPayable').text('KSh ' + parseFloat(loan.total_payable).toLocaleString(undefined, {minimumFractionDigits: 2}));
                   $('#loanMonthlyPayment').text('KSh ' + parseFloat(loan.monthly_payment).toLocaleString(undefined, {minimumFractionDigits: 2}));
                   $('#loanMeetingDate').text(formatDate(loan.meeting_date));
                   $('#loanStatus').html('<span class="badge ' + statusClass + '">' + statusText + '</span>');
                   
                   // Update client pledges
                   var clientPledgesHtml = '';
                   if (clientPledges && clientPledges.length > 0) {
                       for (var i = 0; i < clientPledges.length; i++) {
                           clientPledgesHtml += '<tr>';
                           clientPledgesHtml += '<td>' + clientPledges[i].item + '</td>';
                           clientPledgesHtml += '<td>KSh ' + parseFloat(clientPledges[i].value).toLocaleString(undefined, {minimumFractionDigits: 2}) + '</td>';
                           clientPledgesHtml += '</tr>';
                       }
                   } else {
                       clientPledgesHtml = '<tr><td colspan="2" class="text-center">No pledges recorded</td></tr>';
                   }
                   $('#clientPledgesTableBody').html(clientPledgesHtml);
                   
                   // Update guarantor information
                   $('#guarantorName').text(loan.guarantor_name);
                   $('#guarantorId').text(loan.guarantor_id);
                   $('#guarantorPhone').text(loan.guarantor_phone);
                   $('#guarantorLocation').text(loan.guarantor_location);
                   $('#guarantorSublocation').text(loan.guarantor_sublocation);
                   $('#guarantorVillage').text(loan.guarantor_village);
                   
                   // Update guarantor pledges
                   var guarantorPledgesHtml = '';
                   if (guarantorPledges && guarantorPledges.length > 0) {
                       for (var i = 0; i < guarantorPledges.length; i++) {
                           guarantorPledgesHtml += '<tr>';
                           guarantorPledgesHtml += '<td>' + guarantorPledges[i].item + '</td>';
                           guarantorPledgesHtml += '<td>KSh ' + parseFloat(guarantorPledges[i].value).toLocaleString(undefined, {minimumFractionDigits: 2}) + '</td>';
                           guarantorPledgesHtml += '</tr>';
                       }
                   } else {
                       guarantorPledgesHtml = '<tr><td colspan="2" class="text-center">No pledges recorded</td></tr>';
                   }
                   $('#guarantorPledgesTableBody').html(guarantorPledgesHtml);
                   
                   // Show payment information if available
                   if (loan.status == 2 && paymentInfo) {
                       $('#paymentInfoSection').show();
                       $('#nextPaymentDate').text(paymentInfo.next_payment_date);
                       $('#monthlyAmount').text('KSh ' + parseFloat(paymentInfo.monthly_amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
                       $('#penaltyAmount').text('KSh ' + parseFloat(paymentInfo.penalty).toLocaleString(undefined, {minimumFractionDigits: 2}));
                       $('#totalPayableAmount').text('KSh ' + parseFloat(paymentInfo.payable_amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
                   } else {
                       $('#paymentInfoSection').hide();
                   }
                   
                   // Show or hide Edit button based on loan status
                   if (loan.status == 3) {
                       $('#editLoanBtn').hide();
                   } else {
                       $('#editLoanBtn').show();
                   }
                   
                   $('#loanDetailsContent').show();
               },
               error: function(xhr, status, error) {
                   $('#loanDetailsLoading').hide();
                   alert('Error loading loan details: ' + error);
                   console.error(xhr.responseText);
               }
           });
       }



function generateLoanModals(loanId) {
    if ($('#updateloan' + loanId).length > 0) {
        return; // Modal already exists
    }
    
    // Show a loading indicator
    if (!$('#modalLoadingOverlay').length) {
        $('body').append('<div id="modalLoadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; text-align:center; padding-top:20%;"><div class="spinner-border text-light" role="status"><span class="sr-only">Loading...</span></div></div>');
    }
    $('#modalLoadingOverlay').show();
    
    // Fetch loan details to populate the edit modal
    $.ajax({
        url: '../controllers/get_loan_details.php',
        type: 'GET',
        data: { loan_id: loanId },
        dataType: 'json',
        success: function(response) {
            if (response.error) {
                alert(response.error);
                $('#modalLoadingOverlay').hide();
                return;
            }
            
            var loan = response.loan;
            
            // Create the Edit Modal
            var editModal = $('<div class="modal fade" id="updateloan' + loanId + '" aria-hidden="true"></div>');
            var editModalContent = `
                <div class="modal-dialog modal-lg">
                    <form method="POST" action="../controllers/update_loan.php">
                        <div class="modal-content">
                            <div class="modal-header bg-warning">
                                <h5 class="modal-title text-white">Edit Loan</h5>
                                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="loan_id" value="${loanId}">
                                
                                <!-- Client Details -->
                                <h6 class="mb-3">Client Details</h6>
                                <div class="form-group">
                                    <label>Client</label>
                                    <select name="client" class="form-control edit-client-select" required>
                                        <!-- Options will be loaded via AJAX -->
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Loan Product</label>
                                    <select name="loan_product_id" class="form-control edit-loan-product-select" required>
                                        <!-- Options will be loaded via AJAX -->
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Loan Amount</label>
                                            <input type="number" name="loan_amount" class="form-control" value="${loan.amount}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Loan Term (months)</label>
                                            <input type="number" name="loan_term" class="form-control" value="${loan.loan_term}" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Meeting Date</label>
                                    <input type="date" name="meeting_date" class="form-control" value="${loan.meeting_date}" required>
                                </div>

                                <div class="form-group">
                                    <label>Purpose</label>
                                    <textarea name="purpose" class="form-control" required>${loan.purpose}</textarea>
                                </div>

                                <div class="form-group">
                                    <label>Client Pledges</label>
                                    <div id="editClientPledges${loanId}">
                                        <!-- Pledges will be added dynamically -->
                                    </div>
                                    <button type="button" class="btn btn-secondary mt-2" onclick="addEditPledge('client', ${loanId})">Add More Pledge</button>
                                </div>

                                <!-- Guarantor Details -->
                                <h6 class="mb-3 mt-4">Guarantor Details</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Full Name</label>
                                            <input type="text" name="guarantor_name" class="form-control" value="${loan.guarantor_name}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>National ID</label>
                                            <input type="text" name="guarantor_id" class="form-control" value="${loan.guarantor_id}" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Phone Number</label>
                                            <input type="text" name="guarantor_phone" class="form-control" value="${loan.guarantor_phone}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Location</label>
                                            <input type="text" name="guarantor_location" class="form-control" value="${loan.guarantor_location}" required>
                                        </div>
                                    </div>
                                </div>

                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Sub-location</label>
                                            <input type="text" name="guarantor_sublocation" class="form-control" value="${loan.guarantor_sublocation}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Village</label>
                                            <input type="text" name="guarantor_village" class="form-control" value="${loan.guarantor_village}" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Guarantor Pledges</label>
                                    <div id="editGuarantorPledges${loanId}">
                                        <!-- Pledges will be added dynamically -->
                                    </div>
                                    <button type="button" class="btn btn-secondary mt-2" onclick="addEditPledge('guarantor', ${loanId})">Add More Pledge</button>
                                </div>

                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control" required>
                                        <option value="0" ${loan.status == 0 ? 'selected' : ''}>Pending Approval</option>
                                        <option value="1" ${loan.status == 1 ? 'selected' : ''}>Approved</option>
                                        <option value="2" ${loan.status == 2 ? 'selected' : ''}>Released</option>
                                        <option value="3" ${loan.status == 3 ? 'selected' : ''}>Completed</option>
                                        <option value="4" ${loan.status == 4 ? 'selected' : ''}>Denied</option>
                                    </select>
                                </div>
                                
                                <!-- Hidden fields for calculation results -->
                                <input type="hidden" name="monthly_payment" value="${loan.monthly_payment}">
                                <input type="hidden" name="total_payable" value="${loan.total_payable}">
                                <input type="hidden" name="total_interest" value="${loan.total_interest}">
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                                <button type="submit" name="update" class="btn btn-warning d-none">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            `;
            editModal.html(editModalContent);
            $('body').append(editModal);
            
            // Create the Delete Modal
            var deleteModal = $('<div class="modal fade" id="deleteloan' + loanId + '" tabindex="-1" aria-hidden="true"></div>');
            var deleteModalContent = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger">
                            <h5 class="modal-title text-white">Confirm Deletion</h5>
                            <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">Are you sure you want to delete this loan record?</div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                            <a class="btn btn-danger d-none" href="../controllers/delete_loan.php?loan_id=${loanId}">Delete</a>
                        </div>
                    </div>
                </div>
            `;
            deleteModal.html(deleteModalContent);
            $('body').append(deleteModal);
            
            // Load client pledges
            var clientPledges = response.client_pledges;
            var clientPledgesContainer = $('#editClientPledges' + loanId);
            clientPledgesContainer.empty();
            
            if (clientPledges && clientPledges.length > 0) {
                for (var i = 0; i < clientPledges.length; i++) {
                    var pledgeHtml = `
                        <div class="pledge-entry row mb-2">
                            <div class="col-md-6">
                                <input type="text" name="client_pledges[${i}][item]" class="form-control" value="${clientPledges[i].item}" required>
                            </div>
                            <div class="col-md-6">
                                <input type="number" name="client_pledges[${i}][value]" class="form-control" value="${clientPledges[i].value}" required>
                            </div>
                        </div>
                    `;
                    clientPledgesContainer.append(pledgeHtml);
                }
                // Set the count for adding new pledges
                editClientPledgeCounts[loanId] = clientPledges.length;
            } else {
                // Add empty pledge form if none exists
                clientPledgesContainer.append(`
                    <div class="pledge-entry row mb-2">
                        <div class="col-md-6">
                            <input type="text" name="client_pledges[0][item]" class="form-control" placeholder="Item" required>
                        </div>
                        <div class="col-md-6">
                            <input type="number" name="client_pledges[0][value]" class="form-control" placeholder="Value" required>
                        </div>
                    </div>
                `);
                editClientPledgeCounts[loanId] = 1;
            }
            
            // Load guarantor pledges
            var guarantorPledges = response.guarantor_pledges;
            var guarantorPledgesContainer = $('#editGuarantorPledges' + loanId);
            guarantorPledgesContainer.empty();
            
            if (guarantorPledges && guarantorPledges.length > 0) {
                for (var i = 0; i < guarantorPledges.length; i++) {
                    var pledgeHtml = `
                        <div class="pledge-entry row mb-2">
                            <div class="col-md-6">
                                <input type="text" name="guarantor_pledges[${i}][item]" class="form-control" value="${guarantorPledges[i].item}" required>
                            </div>
                            <div class="col-md-6">
                                <input type="number" name="guarantor_pledges[${i}][value]" class="form-control" value="${guarantorPledges[i].value}" required>
                            </div>
                        </div>
                    `;
                    guarantorPledgesContainer.append(pledgeHtml);
                }
                // Set the count for adding new pledges
                editGuarantorPledgeCounts[loanId] = guarantorPledges.length;
            } else {
                // Add empty pledge form if none exists
                guarantorPledgesContainer.append(`
                    <div class="pledge-entry row mb-2">
                        <div class="col-md-6">
                            <input type="text" name="guarantor_pledges[0][item]" class="form-control" placeholder="Item" required>
                        </div>
                        <div class="col-md-6">
                            <input type="number" name="guarantor_pledges[0][value]" class="form-control" placeholder="Value" required>
                        </div>
                    </div>
                `);
                editGuarantorPledgeCounts[loanId] = 1;
            }
            
            // Load clients dropdown
            $.ajax({
                url: '../controllers/get_clients.php',
                type: 'GET',
                dataType: 'json',
                success: function(clients) {
                    var clientSelect = $('#updateloan' + loanId + ' .edit-client-select');
                    clientSelect.empty();
                    
                    $.each(clients, function(index, client) {
                        var selected = (client.account_id == loan.account_id) ? 'selected' : '';
                        clientSelect.append('<option value="' + client.account_id + '" ' + selected + '>' + client.last_name + ', ' + client.first_name + ' (' + client.shareholder_no + ')</option>');
                    });
                    
                    // Initialize Select2 for the client dropdown
                    clientSelect.select2({
                        dropdownParent: $('#updateloan' + loanId)
                    });
                }
            });
            
            // Load loan products dropdown
            $.ajax({
                url: '../controllers/get_loan_products.php',
                type: 'GET',
                dataType: 'json',
                success: function(products) {
                    var productSelect = $('#updateloan' + loanId + ' .edit-loan-product-select');
                    productSelect.empty();
                    
                    $.each(products, function(index, product) {
                        var selected = (product.id == loan.loan_product_id) ? 'selected' : '';
                        productSelect.append('<option value="' + product.id + '" data-interest="' + product.interest_rate + '" ' + selected + '>' + product.loan_type + ' (' + product.interest_rate + '% interest)</option>');
                    });
                    
                    // Initialize Select2 for the loan product dropdown
                    productSelect.select2({
                        dropdownParent: $('#updateloan' + loanId)
                    });
                    
                    // Hide loading overlay when everything is loaded
                    $('#modalLoadingOverlay').hide();
                }
            });
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            console.error("Response:", xhr.responseText);
            alert("An error occurred while loading loan details. Please try again.");
            $('#modalLoadingOverlay').hide();
        }
    });
}
       
       // Helper function to format dates
       function formatDate(dateString) {
           if (!dateString) return 'N/A';
           var date = new Date(dateString);
           var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
           return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear();
       }
       </script>
   </body>
</html>