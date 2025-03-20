<?php
    date_default_timezone_set("Africa/Nairobi");
    require_once '../helpers/session.php';
    require_once '../config/class.php';
    $db = new db_class(); 


// Check if user is logged in and is a cashier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: index.php');
    exit();
}

             // Get the current hour to determine the greeting
             $current_hour = date('H');
             if ($current_hour < 12) {
                 $greeting = "Good morning";
             } elseif ($current_hour < 18) {
                 $greeting = "Good afternoon";
             } else {
                 $greeting = "Good evening";
             }
         
         // Get the user's name
         $user_name = $db->user_acc($_SESSION['user_id']);
         $first_name = explode(' ', $user_name)[0];



    // Fetch total receipts and disbursements
    $total_receipts = $db->conn->query("SELECT COUNT(*) as count FROM `receipts`")->fetch_assoc()['count'];
    $current_month = date('Y-m');
    $total_payments = $db->conn->query("SELECT SUM(pay_amount) as total FROM `payment` WHERE DATE_FORMAT(date_created, '%Y-%m') = '$current_month'")->fetch_assoc()['total'] ?? 0;

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $receipt_type = $_POST['receipt_type'];
        $posting_date = $_POST['posting_date'];
        echo "Debug: POST received<br>";
        echo "Debug: POST data: " . print_r($_POST, true) . "<br>";
        echo "Debug: FILES data: " . print_r($_FILES, true) . "<br>";
        
        // File upload handling
        $uploaded_files = [];
        if (!empty($_FILES['files']['name'][0])) {
            $upload_dir = "../uploads/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['files']['name'] as $key => $name) {
                $tmp_name = $_FILES['files']['tmp_name'][$key];
                $error = $_FILES['files']['error'][$key];
                
                if ($error === UPLOAD_ERR_OK) {
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    $new_name = uniqid() . '.' . $extension;
                    $destination = $upload_dir . $new_name;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $uploaded_files[] = $destination;
                    }
                }
            }
        }
        
        // Insert into database
        $files_json = json_encode($uploaded_files);
        $stmt = $db->conn->prepare("INSERT INTO receipts (type, posting_date, files) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $receipt_type, $posting_date, $files_json);
        
        if ($stmt->execute()) {
            $success_message = "Receipt posted successfully!";
            // Refresh total receipts count
            $total_receipts = $db->conn->query("SELECT COUNT(*) as count FROM `receipts`")->fetch_assoc()['count'];
        } else {
            $error_message = "Error posting receipt: " . $db->conn->error;
        }
        $stmt->close();
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Cashier Dashboard - Lato Management System</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <style>
                #alertContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            width: 300px;
        }
        .alert {
            margin-bottom: 10px;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 100%;
        }
        .row .card{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }

        .container-fluid .card{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
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
                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                   
                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $user_name; ?></span>
                                <img class="img-profile rounded-circle"
                                    src="../public/image/logo.jpg">
                            </a>
                            <!-- Dropdown - User Information -->
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
                <div id="alertContainer"></div>
                          <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800"><?php echo $greeting . ", " . $first_name; ?></h1>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Total Receipts Card -->
                        <div class="col-xl-6 col-md-6 mb-4">
                            <div class="card border-left-primary h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div style="color: #51087E;" class="text-xs font-weight-bold text-uppercase mb-1">Total Receipts Posted</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_receipts; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-receipt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Disbursements Card -->
                        <div class="col-xl-6 col-md-6 mb-4">
                            <div class="card border-left-success h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Disbursements for <?php echo date('F'); ?></div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">KSh <?php echo $total_payments; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Receipt Posting Form -->
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <h6 style="color: #51087E;" class="m-0 font-weight-bold">Post New Receipt</h6>
                        </div>
                        <div class="card-body">
                            <?php if (isset($success_message)): ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo $success_message; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($error_message)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            <form id="receiptForm" method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <label for="receipt_type">Receipt Type</label>
        <select class="form-control" id="receipt_type" name="receipt_type" required>
            <option value="">Select Receipt Type</option>
            <option value="withdrawals">Withdrawals</option>
            <option value="deposits">Deposits</option>
            <option value="loan_repayments">Loan Repayments</option>
            <option value="reports">Reports</option>
            <option value="disbursements">Disbursements</option>
        </select>
    </div>
    <div class="form-group">
        <label for="posting_date">Posting Date</label>
        <input type="date" class="form-control" id="posting_date" name="posting_date" value="<?php echo date('Y-m-d'); ?>" required>
    </div>
    <div class="form-group">
        <label for="files">Upload Files</label>
        <input type="file" id="files" name="files[]" multiple accept=".jpg,.jpeg,.png,.pdf,.docx,.xlsx">
        <small class="form-text text-muted">Accepted file types: jpg, jpeg, png, pdf, docx, xlsx</small>
    </div>
    <button style="background-color: #51087E; color: white;" type="submit" class="btn">Post Receipt</button>
</form>

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
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                    <a class="btn btn-danger" href="logout.php">Logout</a>
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

                        // Add this new function for showing alerts
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


            // File upload functionality
    const fileInput = document.getElementById('files');


    fileInput.addEventListener('change', updateFileList);

    function updateFileList() {
        const fileList = Array.from(fileInput.files).map(file => file.name).join(', ');
    }

    // Form submission
    $('#receiptForm').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);

                $.ajax({
                    url: 'cashier.php',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        console.log(response); // Log the entire response
                        if (response.includes("Receipt posted successfully!")) {
                            showAlert("Receipt posted successfully!", "success");
                            setTimeout(function() {
                                location.reload();
                            }, 2000); // Reload after 2 seconds
                        } else {
                            showAlert("Error posting receipt. Please check the console for details.", "error");
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        showAlert("An error occurred. Please check the console for details.", "error");
                    },
                    cache: false,
                    contentType: false,
                    processData: false
                });
            });
        });
    </script>
</body>
</html>