<?php
    date_default_timezone_set("Africa/Nairobi");
    require_once '../helpers/session.php';
    require_once '../config/class.php';
    $db = new db_class(); 

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: ../views/index.php');
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

        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
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

    <!-- Import Sidebar -->
            <?php require_once '../components/includes/sidebar.php'; ?>

                <!-- Include Loans Table Component -->
                <?php require_once '../components/loans/loans_table.php'; ?>

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
        
        <!-- Include Modals -->
        <?php require_once '../components/loans/loan_details_modal.php'; ?>
        <?php require_once '../components/loans/loan_modals.php'; ?>

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
       <script src="../public/js/select2.js"></script>

       <!-- Custom scripts for all pages-->
       <script src="../public/js/sb-admin-2.js"></script>

       <!-- Page level plugins -->
       <script src="../public/js/jquery.dataTables.js"></script>
       <script src="../public/js/dataTables.bootstrap4.js"></script>

       <script>
       $(document).ready(function() {
           // Initialize DataTable with server-side processing for pagination
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
               pageLength: 10,
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

           // Updated modal event handlers with role-based restrictions checking
           $(document).on('click', 'a[data-toggle="modal"][data-target^="#updateloan"]', function(e) {
                e.preventDefault();
                
                // Check if the link is disabled (for manager restricted loans)
                if ($(this).hasClass('disabled')) {
                    showRestrictedActionMessage('edit');
                    return false;
                }
                
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
                
                // Check if the link is disabled (for manager restricted loans)
                if ($(this).hasClass('disabled')) {
                    showRestrictedActionMessage('delete');
                    return false;
                }
                
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
           
           // Handle edit button in details modal with role-based restrictions
           $('#editLoanBtn').click(function() {
               var loanId = $(this).data('id');
               
               // Check user role and loan status before allowing edit
               checkUserPermissions(loanId, function(canEdit) {
                   if (canEdit) {
                       $('#loanDetailsModal').modal('hide');
                       setTimeout(function() {
                           generateLoanModals(loanId);
                           setTimeout(function() {
                               $('#updateloan' + loanId).modal('show');
                           }, 100);
                       }, 500);
                   } else {
                       showRestrictedActionMessage('edit');
                   }
               });
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
       
       // Store user role for JavaScript access
       const userRole = '<?php echo $_SESSION['role']; ?>';

       // Function to show restricted action messages
       function showRestrictedActionMessage(action) {
           var message = '';
           if (userRole === 'manager') {
               message = 'You cannot ' + action + ' this loan. Only admins can modify approved, disbursed, or completed loans.';
           } else {
               message = 'You cannot ' + action + ' this loan due to your current permissions.';
           }
           
           // Create a temporary alert
           var alertHtml = '<div class="alert alert-warning alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">';
           alertHtml += '<i class="fas fa-exclamation-triangle"></i> ';
           alertHtml += message;
           alertHtml += '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
           alertHtml += '<span aria-hidden="true">&times;</span>';
           alertHtml += '</button>';
           alertHtml += '</div>';
           
           $('body').append(alertHtml);
           
           // Auto-remove after 5 seconds
           setTimeout(function() {
               $('.alert').fadeOut();
           }, 5000);
       }

       // Function to check user permissions before editing
       function checkUserPermissions(loanId, callback) {
           $.ajax({
               url: '../controllers/get_loan_details.php',
               type: 'GET',
               data: { loan_id: loanId },
               dataType: 'json',
               success: function(response) {
                   if (response.error) {
                       callback(false);
                       return;
                   }
                   
                   var loanStatus = parseInt(response.loan.status);
                   
                   // Check permissions based on user role
                   if (userRole === 'admin') {
                       // Admins can edit all loans
                       callback(true);
                   } else if (userRole === 'manager') {
                       // Managers can only edit pending (0) or denied (4) loans
                       callback(loanStatus === 0 || loanStatus === 4);
                   } else {
                       // Other roles have no edit permissions
                       callback(false);
                   }
               },
               error: function() {
                   callback(false);
               }
           });
       }

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

       // FIXED CALCULATION FUNCTION - Matches get_loan_schedule.php logic
       function calculateLoan() {
        const loanAmount = parseFloat(document.getElementById('loan_amount').value);
        const loanTerm = parseInt(document.getElementById('loan_term').value);
        const annualInterestRate = parseFloat(document.querySelector('.loan-product-select option:checked').dataset.interest);
        
        if (isNaN(loanAmount) || isNaN(loanTerm) || isNaN(annualInterestRate)) {
            alert('Please fill in all the loan details correctly.');
            return;
        }
        
        // Calculate using EXACT same logic as get_loan_schedule.php
        const monthlyPrincipal = Math.round((loanAmount / loanTerm) * 100) / 100; // Round to 2 decimals
        let totalInterest = 0;
        let remainingPrincipal = loanAmount;
        
        // Calculate interest for each month using declining balance
        for (let month = 1; month <= loanTerm; month++) {
            const monthlyInterest = Math.round((remainingPrincipal * (annualInterestRate / 100)) * 100) / 100;
            totalInterest += monthlyInterest;
            remainingPrincipal -= monthlyPrincipal;
        }
        
        // Monthly payment is principal + average interest per month
        const avgMonthlyInterest = totalInterest / loanTerm;
        const monthlyPayment = monthlyPrincipal + avgMonthlyInterest;
        const totalPayment = loanAmount + totalInterest;
        
        document.getElementById('monthly_payment').textContent = 'KSh ' + monthlyPayment.toFixed(2);
        document.getElementById('total_interest').textContent = 'KSh ' + totalInterest.toFixed(2);
        document.getElementById('total_payment').textContent = 'KSh ' + totalPayment.toFixed(2);
        
        document.getElementById('loan_calculation_results').style.display = 'block';
    }

    // CORRECTED CALCULATION FUNCTION FOR EDIT MODAL - Matches get_loan_schedule.php logic EXACTLY
    function calculateEditLoan(loanId) {
        const modal = document.getElementById(`updateloan${loanId}`);
        const loanAmount = parseFloat(modal.querySelector('[name="loan_amount"]').value);
        const loanTerm = parseInt(modal.querySelector('[name="loan_term"]').value);
        const annualInterestRate = parseFloat(modal.querySelector('[name="loan_product_id"] option:checked').dataset.interest);
        
        if (isNaN(loanAmount) || isNaN(loanTerm) || isNaN(annualInterestRate)) {
            alert('Please fill in all the loan details correctly.');
            return;
        }
        
        // Calculate using EXACT same logic as get_loan_schedule.php
        const monthlyPrincipal = Math.round((loanAmount / loanTerm) * 100) / 100; // Round to 2 decimals
        let totalInterest = 0;
        let remainingPrincipal = loanAmount;
        
        // Calculate interest for each month using declining balance
        for (let month = 1; month <= loanTerm; month++) {
            const monthlyInterest = Math.round((remainingPrincipal * (annualInterestRate / 100)) * 100) / 100;
            totalInterest += monthlyInterest;
            remainingPrincipal -= monthlyPrincipal;
        }
        
        // Monthly payment is principal + average interest per month
        const avgMonthlyInterest = totalInterest / loanTerm;
        const monthlyPayment = monthlyPrincipal + avgMonthlyInterest;
        const totalPayment = loanAmount + totalInterest;
        
        // Update hidden fields with calculated values
        modal.querySelector('[name="monthly_payment"]').value = monthlyPayment.toFixed(2);
        modal.querySelector('[name="total_payable"]').value = totalPayment.toFixed(2);
        modal.querySelector('[name="total_interest"]').value = totalInterest.toFixed(2);
        
        // Update display
        const resultsDiv = modal.querySelector('.calculation-results');
        if (resultsDiv) {
            resultsDiv.innerHTML = `
                <h6>Recalculated Loan Details</h6>
                <p>Monthly Payment: KSh ${monthlyPayment.toFixed(2)}</p>
                <p>Total Interest: KSh ${totalInterest.toFixed(2)}</p>
                <p>Total Payment: KSh ${totalPayment.toFixed(2)}</p>
            `;
        }
    }

       // Function to load loan details with role-based restrictions
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
                        var loanStatus = parseInt(loan.status);
                        
                        // Format the loan status
                        var statusText = '';
                        var statusClass = '';
                        switch(loanStatus) {
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
                                statusText = 'Disbursed';
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
                        $('#loanMeetingDate').text(formatDate(loan.meeting_date));
                        $('#loanStatus').html('<span class="badge ' + statusClass + '">' + statusText + '</span>');
                        
                        // Show Next Payment Date only for disbursed loans and if payment info is available
                        if (loan.status == 2 && paymentInfo && paymentInfo.next_payment_date) {
                            $('#nextPaymentDate').text(formatDate(paymentInfo.next_payment_date));
                            $('#nextPaymentRow').show();
                        } else {
                            $('#nextPaymentRow').hide();
                        }
                        
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
                        
                        // Show/hide Edit button based on user role and loan status
                        // Remove any existing edit disabled messages
                        $('#editDisabledMessage').remove();
                        
                        if (userRole === 'admin') {
                            // Admins can edit all loans
                            $('#editLoanBtn').show();
                        } else if (userRole === 'manager') {
                            // Managers can only edit pending (0) or denied (4) loans
                            if (loanStatus === 0 || loanStatus === 4) {
                                $('#editLoanBtn').show();
                            } else {
                                $('#editLoanBtn').hide();
                                
                                // Add a message about why edit is disabled for managers
                                var disabledMessage = '<div id="editDisabledMessage" class="alert alert-info mt-2">';
                                disabledMessage += '<i class="fas fa-info-circle"></i> ';
                                
                                switch(loanStatus) {
                                    case 1:
                                        disabledMessage += 'This loan has been approved and can only be edited by administrators.';
                                        break;
                                    case 2:
                                        disabledMessage += 'This loan has been disbursed and can only be edited by administrators.';
                                        break;
                                    case 3:
                                        disabledMessage += 'This loan has been completed and can only be edited by administrators.';
                                        break;
                                }
                                
                                disabledMessage += '</div>';
                                $('#loanDetailsContent').append(disabledMessage);
                            }
                        } else {
                            // Other roles cannot edit loans
                            $('#editLoanBtn').hide();
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
           
           // Fetch loan details to check permissions before allowing edit
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
                   var loanStatus = parseInt(loan.status);
                   
                   // Check if user can edit this loan based on role and status
                   if (userRole === 'manager' && loanStatus !== 0 && loanStatus !== 4) {
                       $('#modalLoadingOverlay').hide();
                       showRestrictedActionMessage('edit from this status');
                       return;
                   }
                   
                   // Create the Edit Modal
                   var editModal = $('<div class="modal fade" id="updateloan' + loanId + '" aria-hidden="true"></div>');
                   var editModalContent = `
                       <div class="modal-dialog modal-lg">
                           <form method="POST" action="../controllers/update_loan.php">
                               <div class="modal-content">
                                   <div class="modal-header bg-warning">
                                       <h5 class="modal-title text-white">Reschedule Loan</h5>
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
                                           <label>Status</label>`;
                   
                   // Role-based status options
                   if (userRole === 'admin') {
                       // Admins can set all statuses
                       editModalContent += `
                                           <select name="status" class="form-control" required>
                                               <option value="0" ${loan.status == 0 ? 'selected' : ''}>Pending Approval</option>
                                               <option value="1" ${loan.status == 1 ? 'selected' : ''}>Approved</option>
                                               <option value="2" ${loan.status == 2 ? 'selected' : ''}>Disbursed</option>
                                               <option value="3" ${loan.status == 3 ? 'selected' : ''}>Completed</option>
                                               <option value="4" ${loan.status == 4 ? 'selected' : ''}>Denied</option>
                                           </select>`;
                   } else if (userRole === 'manager') {
                       // Managers can only set pending and denied statuses
                       editModalContent += `
                                           <select name="status" class="form-control" required>
                                               <option value="0" ${loan.status == 0 ? 'selected' : ''}>Pending Approval</option>
                                               <option value="4" ${loan.status == 4 ? 'selected' : ''}>Denied</option>
                                           </select>
                                           <small class="form-text text-muted">Managers can only set loans to Pending or Denied status.</small>`;
                   }
                   
                   editModalContent += `
                                       </div>
                                       
                                       <!-- Hidden fields for calculation results -->
                                       <input type="hidden" name="monthly_payment" value="${loan.monthly_payment}">
                                       <input type="hidden" name="total_payable" value="${loan.total_payable}">
                                       <input type="hidden" name="total_interest" value="${loan.total_interest}">
                                       
                                       <!-- Calculation Results Display -->
                                       <div class="calculation-results bg-light p-3 mt-3" style="border-radius: 5px;">
                                           <h6>Current Loan Calculation</h6>
                                           <p>Monthly Payment: KSh ${parseFloat(loan.monthly_payment).toFixed(2)}</p>
                                           <p>Total Interest: KSh ${parseFloat(loan.total_interest || 0).toFixed(2)}</p>
                                           <p>Total Payment: KSh ${parseFloat(loan.total_payable).toFixed(2)}</p>
                                           <button type="button" class="btn btn-sm btn-info" onclick="calculateEditLoan(${loanId})">Recalculate</button>
                                       </div>
                                   </div>
                                   <div class="modal-footer">
                                       <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                                       <button type="submit" name="update" class="btn btn-warning">Update</button>
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
                               <div class="modal-body">
                                   Are you sure you want to delete this loan record?`;
                   
                   if (userRole === 'manager') {
                       deleteModalContent += `
                                   <div class="alert alert-warning mt-2">
                                       <small><i class="fas fa-exclamation-triangle"></i> As a manager, you can only delete pending or denied loans.</small>
                                   </div>`;
                   } else {
                       deleteModalContent += `
                                   <div class="alert alert-warning mt-2">
                                       <small><i class="fas fa-exclamation-triangle"></i> This action cannot be undone.</small>
                                   </div>`;
                   }
                   
                   deleteModalContent += `
                               </div>
                               <div class="modal-footer">
                                   <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                                   <a class="btn btn-danger" href="../controllers/delete_loan.php?loan_id=${loanId}">Delete</a>
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
                       editClientPledgeCounts[loanId] = clientPledges.length;
                   } else {
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
                       editGuarantorPledgeCounts[loanId] = guarantorPledges.length;
                   } else {
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
                           
                           productSelect.select2({
                               dropdownParent: $('#updateloan' + loanId)
                           });
                           
                           $('#modalLoadingOverlay').hide();
                       }
                   });
               },
               error: function(xhr, status, error) {
                   console.error("AJAX Error:", status, error);
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
       
       // Function to view loan schedule
       function viewLoanSchedule(loanId) {
           window.open('../controllers/get_loan_schedule.php?loan_id=' + loanId, '_blank');
       }
       </script>