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

        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        .container-fluid .card {
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }

        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1055;
            max-width: 350px;
        }
        
        /* Enhanced toast styles */
        .toast {
            min-width: 300px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .toast-header {
            color: #fff;
            border-bottom: none;
            font-weight: 600;
        }
        
        .toast-header.bg-success {
            background: linear-gradient(45deg, #28a745, #20c997) !important;
        }
        
        .toast-header.bg-danger {
            background: linear-gradient(45deg, #dc3545, #e74c3c) !important;
        }
        
        .toast-header.bg-warning {
            background: linear-gradient(45deg, #ffc107, #fd7e14) !important;
        }
        
        .toast-body {
            padding: 1rem;
            color: #495057;
            font-size: 0.9rem;
        }

        /* Payment terms styling */
        .payment-terms {
            font-size: 0.85em;
            color: #495057;
        }
        .payment-terms .term-months {
            font-weight: bold;
            color: #007bff;
        }
        .payment-terms .monthly-amount {
            font-weight: bold;
            color: #28a745;
        }

        /* Total row styling */
        .total-row {
            background-color: #f8f9fa !important;
            font-weight: bold;
            border-top: 2px solid #007bff !important;
        }
        .total-amount {
            color: #007bff;
            font-size: 1.1em;
            font-weight: bold;
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-spinner {
            color: white;
            font-size: 2rem;
        }
    </style>
</head>
<body id="page-top">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
    </div>

    <div id="wrapper">
       <!-- Import Sidebar -->
            <?php require_once '../components/includes/sidebar.php'; ?>

                <!-- Toast Container -->
                <div class="toast-container" id="toastContainer">
                    <!-- Toasts will be inserted here -->
                </div>

                <!-- Begin Page Content -->
                <div class="container-fluid pt-4">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Pending Loan Approvals</h1>
                        <div class="ml-auto">
                            <span class="text-muted">Total Pending: </span>
                            <span class="badge badge-primary badge-pill" id="totalCountBadge">0</span>
                        </div>
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
                                            <th>Payment Terms</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                        $pending_loans = $db->get_pending_loans();
                                        $total_amount = 0;
                                        $total_count = 0;
                                        while($loan = $pending_loans->fetch_array()){
                                            $total_amount += $loan['amount'];
                                            $total_count++;
                                    ?>
                                    <tr>
                                        <td><?php echo $loan['ref_no']?></td>
                                        <td><?php echo $loan['last_name'] . ", " . $loan['first_name']?></td>
                                        <td><?php echo $loan['shareholder_no']?></td>
                                        <td class="loan-amount"><?php echo number_format($loan['amount'], 2)?></td>
                                        <td>
                                            <div class="payment-terms">
                                                <div><span class="term-months"><?php echo $loan['loan_term']?> months</span></div>
                                                <div><span class="monthly-amount">KSh <?php echo number_format($loan['monthly_payment'], 2)?>/month</span></div>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn btn-success btn-sm approve-loan" data-loan-id="<?php echo $loan['loan_id']?>">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-danger btn-sm deny-loan" data-loan-id="<?php echo $loan['loan_id']?>">
                                                <i class="fas fa-times"></i> Deny
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                        }
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr class="total-row">
                                        <td colspan="3" class="text-right"><strong>Total Amount:</strong></td>
                                        <td class="total-amount" id="totalAmount">KSh <?php echo number_format($total_amount, 2)?></td>
                                        <td colspan="2" class="text-center">
                                            <span class="badge badge-info"><?php echo $total_count?> loan(s) pending</span>
                                        </td>
                                    </tr>
                                </tfoot>
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

        <!-- Confirmation Modal -->
        <div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header" id="confirmModalHeader">
                        <h5 class="modal-title text-white" id="confirmModalTitle">
                            <i class="fas fa-question-circle mr-2"></i>Confirm Action
                        </h5>
                        <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body" id="confirmModalBody">
                        <div class="text-center">
                            <div class="mb-3">
                                <i class="fas fa-user-circle fa-3x text-muted mb-2"></i>
                                <h6 class="font-weight-bold" id="clientNameDisplay"></h6>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Loan Amount</small>
                                    <div class="h5 text-primary" id="loanAmountDisplay"></div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Reference No</small>
                                    <div class="h6 text-secondary" id="refNoDisplay"></div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <p id="confirmationMessage" class="mb-0"></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 justify-content-center">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i> Cancel
                        </button>
                        <button class="btn" type="button" id="confirmActionBtn">
                            <i class="fas fa-check mr-1"></i> Confirm
                        </button>
                    </div>
                </div>
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
            // Initialize DataTable with custom configuration
            var table = $('#dataTable').DataTable({
                "footerCallback": function (row, data, start, end, display) {
                    var api = this.api();
                    
                    // Calculate total amount for visible/filtered rows
                    var total = 0;
                    var count = 0;
                    
                    api.column(3, { page: 'current' }).data().each(function (value, index) {
                        // Remove currency formatting and convert to number
                        var numValue = parseFloat(value.replace(/[^\d.-]/g, ''));
                        if (!isNaN(numValue)) {
                            total += numValue;
                            count++;
                        }
                    });
                    
                    // Update the footer
                    $('#totalAmount').html('KSh ' + total.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                    $('.total-row td:last-child').html('<span class="badge badge-info">' + count + ' loan(s) pending</span>');
                    $('#totalCountBadge').text(count);
                },
                "language": {
                    "search": "Search loans:",
                    "lengthMenu": "Show _MENU_ loans per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ pending loans",
                    "infoEmpty": "No pending loans found",
                    "infoFiltered": "(filtered from _MAX_ total loans)",
                    "emptyTable": "No pending loans available"
                },
                "pageLength": 10,
                "order": [[0, "desc"]]
            });

            // Update initial count
            $('#totalCountBadge').text($('#dataTable tbody tr').length);

            // Enhanced toast notification function
            function showToast(message, type, title, duration = 5000) {
                var toastId = 'toast-' + Date.now();
                var iconClass = '';
                var titleText = title || '';
                
                switch(type) {
                    case 'success':
                        iconClass = 'fas fa-check-circle';
                        titleText = titleText || 'Success';
                        break;
                    case 'danger':
                    case 'error':
                        iconClass = 'fas fa-exclamation-circle';
                        titleText = titleText || 'Error';
                        type = 'danger';
                        break;
                    case 'warning':
                        iconClass = 'fas fa-exclamation-triangle';
                        titleText = titleText || 'Warning';
                        break;
                    case 'info':
                        iconClass = 'fas fa-info-circle';
                        titleText = titleText || 'Information';
                        break;
                }
                
                var toastHtml = `
                    <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="${duration}">
                        <div class="toast-header bg-${type} text-white">
                            <i class="${iconClass} mr-2"></i>
                            <strong class="mr-auto">${titleText}</strong>
                            <small class="text-light">${new Date().toLocaleTimeString()}</small>
                            <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="toast-body">
                            ${message}
                        </div>
                    </div>
                `;
                
                $('#toastContainer').append(toastHtml);
                $(`#${toastId}`).toast('show');
                
                // Auto remove after hiding
                $(`#${toastId}`).on('hidden.bs.toast', function () {
                    $(this).remove();
                });
            }

            // Show loading overlay
            function showLoading() {
                $('#loadingOverlay').css('display', 'flex');
            }

            // Hide loading overlay
            function hideLoading() {
                $('#loadingOverlay').hide();
            }

            // Global variables for confirmation modal
            var currentLoanId = null;
            var currentAction = null;
            var currentButton = null;

            // Function to show custom confirmation modal
            function showConfirmationModal(action, loanId, button) {
                var row = button.closest('tr');
                var clientName = row.find('td:eq(1)').text().trim();
                var loanAmount = row.find('td:eq(3)').text().trim();
                var refNo = row.find('td:eq(0)').text().trim();
                
                currentLoanId = loanId;
                currentAction = action;
                currentButton = button;
                
                // Update modal content based on action
                var modalHeader = $('#confirmModalHeader');
                var modalTitle = $('#confirmModalTitle');
                var confirmBtn = $('#confirmActionBtn');
                var message = $('#confirmationMessage');
                
                if (action === 'approve') {
                    modalHeader.removeClass('bg-danger').addClass('bg-success');
                    modalTitle.html('<i class="fas fa-check-circle mr-2"></i>Approve Loan');
                    confirmBtn.removeClass('btn-danger').addClass('btn-success');
                    confirmBtn.html('<i class="fas fa-check mr-1"></i> Approve Loan');
                    message.html('Are you sure you want to <strong class="text-success">approve</strong> this loan application?');
                } else {
                    modalHeader.removeClass('bg-success').addClass('bg-danger');
                    modalTitle.html('<i class="fas fa-times-circle mr-2"></i>Deny Loan');
                    confirmBtn.removeClass('btn-success').addClass('btn-danger');
                    confirmBtn.html('<i class="fas fa-times mr-1"></i> Deny Loan');
                    message.html('Are you sure you want to <strong class="text-danger">deny</strong> this loan application?<br><small class="text-muted">This action cannot be undone.</small>');
                }
                
                // Update client details
                $('#clientNameDisplay').text(clientName);
                $('#loanAmountDisplay').text(loanAmount);
                $('#refNoDisplay').text(refNo);
                
                // Show modal
                $('#confirmationModal').modal('show');
            }

            // Handle confirmation modal confirm button
            $('#confirmActionBtn').click(function() {
                $('#confirmationModal').modal('hide');
                
                if (currentAction === 'approve') {
                    processLoanAction(currentLoanId, 1, currentButton, 'approve');
                } else {
                    processLoanAction(currentLoanId, 4, currentButton, 'deny');
                }
            });

            // Function to process loan action
            function processLoanAction(loanId, status, button, actionType) {
                var row = button.closest('tr');
                var clientName = row.find('td:eq(1)').text();
                var loanAmount = row.find('td:eq(3)').text();
                var actionText = actionType === 'approve' ? 'Approving' : 'Denying';
                var actionPast = actionType === 'approve' ? 'approved' : 'denied';
                
                button.prop('disabled', true).html(`<i class="fas fa-spinner fa-spin"></i> ${actionText}...`);
                showLoading();
                
                $.ajax({
                    url: '../controllers/update_loan_status.php',
                    type: 'POST',
                    data: { loan_id: loanId, status: status },
                    dataType: 'json',
                    timeout: 10000,
                    success: function(response) {
                        hideLoading();
                        if (response.success) {
                            var toastType = actionType === 'approve' ? 'success' : 'warning';
                            var toastTitle = actionType === 'approve' ? 'Loan Approved' : 'Loan Denied';
                            
                            showToast(
                                `Loan for ${clientName} (${loanAmount}) has been ${actionPast} successfully.`,
                                toastType,
                                toastTitle
                            );
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            var originalText = actionType === 'approve' ? 
                                '<i class="fas fa-check"></i> Approve' : 
                                '<i class="fas fa-times"></i> Deny';
                            button.prop('disabled', false).html(originalText);
                            showToast(
                                `Failed to ${actionType} loan: ${response.message || 'Unknown error occurred'}`,
                                'error',
                                `${actionType.charAt(0).toUpperCase() + actionType.slice(1)} Failed`
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        hideLoading();
                        var originalText = actionType === 'approve' ? 
                            '<i class="fas fa-check"></i> Approve' : 
                            '<i class="fas fa-times"></i> Deny';
                        button.prop('disabled', false).html(originalText);
                        
                        var errorMessage = 'Network error occurred. Please check your connection and try again.';
                        if (status === 'timeout') {
                            errorMessage = 'Request timed out. Please try again.';
                        }
                        
                        showToast(errorMessage, 'error', 'Connection Error');
                    }
                });
            }

            // Approve loan with custom modal
            $(document).on('click', '.approve-loan', function() {
                var loanId = $(this).data('loan-id');
                showConfirmationModal('approve', loanId, $(this));
            });

            // Deny loan with custom modal
            $(document).on('click', '.deny-loan', function() {
                var loanId = $(this).data('loan-id');
                showConfirmationModal('deny', loanId, $(this));
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

            // Show welcome message
            showToast('Pending loans loaded successfully. Use the search to filter by client name or reference number.', 'info', 'Welcome', 3000);
        });
    </script>
</body>
</html>