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
    <title>Lato Management System - Growing with you</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">

    <style>
        .row .card{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }
        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            min-width: 300px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .toast.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .toast.warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }

        .toast-header {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
        }

        .toast-body {
            padding: 12px 15px;
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
        }

        .toast-close:hover {
            opacity: 1;
        }

        /* Modal improvements */
        .modal-header.bg-danger .close span,
        .modal-header.bg-warning .close span {
            color: white;
        }
    </style>
</head>

<body id="page-top">
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Import Sidebar -->
        <?php require_once '../components/includes/sidebar.php'; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid pt-4">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Manage Loan Products</h1>
            </div>

            <!-- Content Row -->
            <div class="row">
                <!-- Loan Product Form -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="../controllers/save_loan_product.php">
                                <div class="form-group">
                                    <label>Loan Product</label>
                                    <select class="form-control" name="loan_type" required>
                                        <option value="">Select Loan Product</option>
                                        <option value="Boresha">Boresha loan (business)</option>
                                        <option value="Pepea">Pepea loan (asset based)</option>
                                        <option value="Elimu">Elimu loan (education)</option>
                                        <option value="Afya">Afya loan (emergency)</option>
                                        <option value="Faida">Faida loan (livestock based)</option>
                                        <option value="Mkulima">Mkulima loan (agri based)</option>
                                        <option value="Fly">Usafi loan</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Interest Rate (% per month)</label>
                                    <input type="number" step="0.1" class="form-control" name="interest_rate" min="0" max="100" required>
                                </div>
                                <button type="submit" class="btn btn-warning btn-block" name="save">Save</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Loan Products Table -->
                <div class="col-xl-8 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Loan Product</th>
                                            <th>Interest Rate (%)</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $loan_products = $db->display_loan_products();
                                            while($product = $loan_products->fetch_array()){
                                        ?>
                                        <tr>
                                            <td><?php echo $product['loan_type']?></td>
                                            <td><?php echo $product['interest_rate']?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        Action
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item bg-warning text-white" href="#" data-toggle="modal" data-target="#updateProduct<?php echo $product['id']?>">Edit</a>
                                                        <a class="dropdown-item bg-danger text-white" href="#" onclick="confirmDelete(<?php echo $product['id']?>, '<?php echo $product['loan_type']?>')">Delete</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Update Loan Product Modal -->
                                        <div class="modal fade" id="updateProduct<?php echo $product['id']?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <form method="POST" action="../controllers/update_loan_product.php">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-warning">
                                                            <h5 class="modal-title text-white">Edit Loan Product</h5>
                                                            <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">×</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?php echo $product['id']?>">
                                                            <div class="form-group">
                                                                <label>Loan Product</label>
                                                                <select class="form-control" name="loan_type" required>
                                                                    <option value="Boresha" <?php echo ($product['loan_type'] == 'Boresha') ? 'selected' : ''; ?>>Boresha loan (business)</option>
                                                                    <option value="Pepea" <?php echo ($product['loan_type'] == 'Pepea') ? 'selected' : ''; ?>>Pepea loan (asset based)</option>
                                                                    <option value="Elimu" <?php echo ($product['loan_type'] == 'Elimu') ? 'selected' : ''; ?>>Elimu loan (education)</option>
                                                                    <option value="Afya" <?php echo ($product['loan_type'] == 'Afya') ? 'selected' : ''; ?>>Afya loan (emergency)</option>
                                                                    <option value="Faida" <?php echo ($product['loan_type'] == 'Faida') ? 'selected' : ''; ?>>Faida loan (livestock based)</option>
                                                                    <option value="Mkulima" <?php echo ($product['loan_type'] == 'Mkulima') ? 'selected' : ''; ?>>Mkulima loan (agri based)</option>
                                                                    <option value="Fly" <?php echo ($product['loan_type'] == 'Fly') ? 'selected' : ''; ?>>Usafi loan</option>
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Interest Rate (% per month)</label>
                                                                <input type="number" step="0.1" class="form-control" name="interest_rate" value="<?php echo $product['interest_rate']?>" min="0" max="100" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="update" class="btn btn-warning">Update</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        
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
                    <h5 class="modal-title text-white">System Information</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Are you sure you want to logout?</div>
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
            $('#dataTable').DataTable({
                "order": [[0, "asc"]]
            });
        });

        // Toast notification functions
        function showToast(message, type) {
            const toastContainer = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const toastId = 'toast_' + Date.now();
            toast.id = toastId;
            
            const icon = type === 'success' ? '✓' : type === 'error' ? '✗' : '⚠';
            const title = type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Warning';
            
            toast.innerHTML = `
                <div class="toast-header">
                    <span>${icon} ${title}</span>
                    <button type="button" class="toast-close" onclick="hideToast('${toastId}')">&times;</button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            
            toastContainer.appendChild(toast);
            
            // Show toast with animation
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                hideToast(toastId);
            }, 5000);
        }

        function hideToast(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }
        }

        // Delete confirmation with toast
        function confirmDelete(productId, productName) {
            // Create confirmation toast
            const confirmToast = document.createElement('div');
            confirmToast.className = 'toast warning';
            confirmToast.id = 'confirmToast';
            
            confirmToast.innerHTML = `
                <div class="toast-header">
                    <span>⚠ Confirm Deletion</span>
                    <button type="button" class="toast-close" onclick="hideToast('confirmToast')">&times;</button>
                </div>
                <div class="toast-body">
                    <p>Are you sure you want to delete "${productName}"?</p>
                    <div style="margin-top: 10px;">
                        <button class="btn btn-danger btn-sm" onclick="executeDelete(${productId})" style="margin-right: 5px;">Delete</button>
                        <button class="btn btn-secondary btn-sm" onclick="hideToast('confirmToast')">Cancel</button>
                    </div>
                </div>
            `;
            
            document.getElementById('toastContainer').appendChild(confirmToast);
            
            setTimeout(() => {
                confirmToast.classList.add('show');
            }, 100);
        }

        function executeDelete(productId) {
            hideToast('confirmToast');
            window.location.href = '../controllers/delete_loan_product.php?id=' + productId;
        }

        // Function to calculate reducing balance
        function calculateReducingBalance(principal, interestRate, term) {
            let monthlyRate = interestRate / 100 / 12;
            let monthlyPayment = principal * (monthlyRate * Math.pow(1 + monthlyRate, term)) / (Math.pow(1 + monthlyRate, term) - 1);
            
            let balance = principal;
            let totalInterest = 0;
            let schedule = [];

            for (let i = 1; i <= term; i++) {
                let interest = balance * monthlyRate;
                let principalPart = monthlyPayment - interest;
                balance -= principalPart;
                totalInterest += interest;

                schedule.push({
                    month: i,
                    payment: monthlyPayment,
                    principalPart: principalPart,
                    interestPart: interest,
                    balance: balance
                });
            }

            return {
                monthlyPayment: monthlyPayment,
                totalInterest: totalInterest,
                totalPayment: principal + totalInterest,
                schedule: schedule
            };
        }

        // Sidebar toggle functionality
        $(document).ready(function() {
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

            $(window).resize(function() {
                if ($(window).width() < 768) {
                    $('.sidebar .collapse').collapse('hide');
                };
                
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