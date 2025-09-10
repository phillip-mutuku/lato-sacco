<style>
/* Repayments Section Styles */
.modal-header{
      background-color: #51087E;
}

.repayments-section {
    margin-bottom: 25px;
}

.repayments-section .section-header {
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.repayments-section .section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #51087E;
    margin: 0;
}

.repayments-section .action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.repayments-section .table-container {
    background: #fff;
    border-radius: 0.35rem;
    overflow: hidden;
    box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
    border: 0;
}

.repayments-section .card-body {
    padding: 1.25rem;
}

.repayments-section .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.repayments-section .table {
    width: 100%;
    margin-bottom: 0;
    color: #858796;
    border-collapse: collapse;
}

.repayments-section .table thead th {
    background-color: #51087E;
    color: #fff;
    border-bottom: 1px solid #e3e6f0;
    border-top: none;
    font-weight: 600;
    padding: 0.75rem;
    vertical-align: middle;
    border-right: none;
    font-size: 0.875rem;
}

.repayments-section .table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #e3e6f0;
    border-bottom: none;
    border-right: none;
    font-size: 0.875rem;
}

.repayments-section .table tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.025);
}

.repayments-section .table-bordered {
    border: 1px solid #e3e6f0;
}

.repayments-section .table-bordered th,
.repayments-section .table-bordered td {
    border: 1px solid #e3e6f0;
}

/* Button Styles */
.btn-success-modern {
    background-color: #1cc88a;
    border-color: #1cc88a;
    color: #fff;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 400;
    line-height: 1.5;
    border-radius: 0.35rem;
    transition: all 0.15s ease-in-out;
    border: 1px solid transparent;
    display: inline-block;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    text-decoration: none;
}

.btn-success-modern:hover {
    background-color: #13855c;
    border-color: #13855c;
    color: #fff;
    text-decoration: none;
}

.btn-success-modern:focus {
    box-shadow: 0 0 0 0.2rem rgba(28, 200, 138, 0.25);
}

.btn-primary-modern {
    background-color: #51087E;
    border-color: #51087E;
    color: #fff;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 400;
    line-height: 1.5;
    border-radius: 0.35rem;
    transition: all 0.15s ease-in-out;
    border: 1px solid transparent;
    display: inline-block;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    text-decoration: none;
}

.btn-primary-modern:hover {
    background-color: #3e0664;
    border-color: #3e0664;
    color: #fff;
    text-decoration: none;
}

.btn-primary-modern:focus {
    box-shadow: 0 0 0 0.2rem rgba(81, 8, 126, 0.25);
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.2rem;
}

/* Modal Styles */
.repayments-section .modal-content {
    border: 0;
    border-radius: 0.35rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.repayments-section .modal-header {
    background: #51087E;
    color: #fff;
    border-bottom: 1px solid #e3e6f0;
    border-top-left-radius: 0.35rem;
    border-top-right-radius: 0.35rem;
    padding: 1rem;
}

.repayments-section .modal-header .close {
    color: #fff;
    opacity: 0.8;
}

.repayments-section .modal-header .close:hover {
    opacity: 1;
    color: #fff;
}

.repayments-section .modal-body {
    padding: 1.5rem;
}

.repayments-section .modal-footer {
    border-top: 1px solid #e3e6f0;
    border-bottom-left-radius: 0.35rem;
    border-bottom-right-radius: 0.35rem;
    padding: 1rem;
}

.repayments-section .form-group label {
    font-weight: 600;
    color: #51087E;
    margin-bottom: 8px;
    display: block;
}

.repayments-section .form-control {
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.repayments-section .form-control:focus {
    border-color: #51087E;
    box-shadow: 0 0 0 0.2rem rgba(81, 8, 126, 0.1);
    outline: 0;
}

/* DataTables Integration */
.repayments-section .dataTables_wrapper {
    padding: 0;
}

.repayments-section .dataTables_wrapper .dataTables_length,
.repayments-section .dataTables_wrapper .dataTables_filter,
.repayments-section .dataTables_wrapper .dataTables_info,
.repayments-section .dataTables_wrapper .dataTables_paginate {
    margin-bottom: 0.5rem;
}

.repayments-section .dataTables_wrapper .dataTables_length select,
.repayments-section .dataTables_wrapper .dataTables_filter input {
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.repayments-section .dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.375rem 0.75rem;
    margin-left: 0.125rem;
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    background: #fff;
    color: #6c757d;
}

.repayments-section .dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #51087E !important;
    border-color: #51087E !important;
    color: white !important;
}

.repayments-section .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #eaecf4;
    border-color: #d1d3e2;
    color: #6c757d;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 1.25rem;
    background: #fff;
    border-radius: 0.35rem;
    box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
    border: 0;
}

.empty-icon {
    font-size: 3.5rem;
    color: #d1d3e2;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.empty-text {
    color: #858796;
    font-size: 1rem;
    margin: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .repayments-section .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .repayments-section .action-buttons {
        width: 100%;
        justify-content: flex-start;
    }
    
    .repayments-section .table {
        font-size: 0.8rem;
    }
    
    .repayments-section .table thead th,
    .repayments-section .table tbody td {
        padding: 0.5rem 0.375rem;
    }
    
    .btn-sm {
        padding: 0.125rem 0.25rem;
        font-size: 0.75rem;
    }
}

@media (max-width: 480px) {
    .repayments-section .section-title {
        font-size: 1.25rem;
    }
    
    .empty-state {
        padding: 2rem 1rem;
    }
    
    .empty-icon {
        font-size: 2.5rem;
    }
}
</style>

<!-- Loan Repayments Section -->
<div class="content-section repayments-section" id="repayments-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-credit-card"></i>
            Loan Repayments
        </h2>
        <div class="action-buttons">
            <button class="btn btn-success-modern" data-toggle="modal" data-target="#repayLoanModal">
                <i class="fas fa-plus"></i> Repay Loan
            </button>
        </div>
    </div>
    
    <div class="card mb-4 table-container">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="repaymentTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Loan Ref No</th>
                            <th>Receipt No</th>
                            <th>Amount Repaid</th>
                            <th>Date Paid</th>
                            <th>Mode of Payment</th>
                            <th>Served By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($repayments)): ?>
                            <?php foreach ($repayments as $repayment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($repayment['loan_ref_no']) ?></td>
                                    <td><?= htmlspecialchars($repayment['receipt_number']) ?></td>
                                    <td>KSh <?= number_format($repayment['amount_repaid'], 2) ?></td>
                                    <td><?= date("Y-m-d", strtotime($repayment['date_paid'])) ?></td>
                                    <td><?= htmlspecialchars($repayment['payment_mode']) ?></td>
                                    <td><?= htmlspecialchars($repayment['served_by_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <button class="btn btn-primary-modern btn-sm print-repayment-receipt" 
                                                data-repayment-id="<?= $repayment['id'] ?>">
                                            <i class="fas fa-print"></i> Print Receipt
                                        </button>
                                        <button class="btn btn-danger btn-sm delete-repayment" 
                                                data-repayment-id="<?= $repayment['id'] ?>"
                                                data-loan-id="<?= $repayment['loan_id'] ?>"
                                                data-amount="<?= $repayment['amount_repaid'] ?>"
                                                data-receipt="<?= htmlspecialchars($repayment['receipt_number']) ?>">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center empty-text">No repayments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
    </div>
</div>

<!-- Repay Loan Modal -->
<div class="modal fade" id="repayLoanModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white">
                    <i class="fas fa-credit-card mr-2"></i>Repay Loan
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="repayLoanForm">
                <div class="modal-body">
                    <input type="hidden" name="accountId" value="<?= $accountId ?>">
                    <input type="hidden" name="served_by" value="<?= $_SESSION['user_id'] ?>">
                    <div class="form-group">
                        <label for="loanSelect">Select Loan</label>
                        <select class="form-control" id="loanSelect" name="loanId" required>
                            <option value="">Select a loan</option>
                            <?php foreach ($loans as $loan): ?>
                                <?php if (isset($loan['loan_id']) && isset($loan['ref_no']) && isset($loan['outstanding_balance'])): ?>
                                    <option value="<?= $loan['loan_id'] ?>">
                                        <?= $loan['ref_no'] ?> - KSh <?= number_format($loan['outstanding_balance'] ?? 0, 2) ?> outstanding
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="refNo">Loan Ref No</label>
                        <input type="text" class="form-control" id="refNo" readonly>
                    </div>
                    <div class="form-group">
                        <label for="loanReceiptNumber">Receipt Number</label>
                        <input type="text" class="form-control" id="loanReceiptNumber" name="receiptNumber" required>
                    </div>
                    <div class="form-group">
                        <label for="outstandingBalance">Outstanding Balance</label>
                        <input type="text" class="form-control" id="outstandingBalance" readonly>
                    </div>
                    <div class="form-group">
                        <label for="nextDueAmount">Next Due Amount</label>
                        <input type="text" class="form-control" id="nextDueAmount" readonly>
                    </div>
                    <div class="form-group">
                        <label for="repayAmount">Repayment Amount</label>
                        <input type="number" class="form-control" id="repayAmount" name="repayAmount" required step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label for="repayPaymentMode">Payment Mode</label>
                        <select class="form-control" id="repayPaymentMode" name="paymentMode" required>
                            <option value="Cash">Cash</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Repay Loan</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Add Delete Confirmation Modal -->
<div class="modal fade" id="deleteRepaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Delete Loan Repayment
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to delete this repayment?</strong></p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> This action will:
                    <ul class="mb-0 mt-2">
                        <li>Permanently delete the repayment record</li>
                        <li>Update the loan schedule to reflect the removal</li>
                        <li>Remove the associated transaction record</li>
                        <li>This action cannot be undone</li>
                    </ul>
                </div>
                <div id="deleteRepaymentDetails">
                    <p><strong>Receipt Number:</strong> <span id="deleteReceiptNo"></span></p>
                    <p><strong>Amount:</strong> KSh <span id="deleteAmount"></span></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteRepayment">
                    <i class="fas fa-trash"></i> Delete Repayment
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable for repayments when section becomes active
    $(document).on('sectionChanged', function(event, section) {
    if (section === 'repayments') {
        setTimeout(() => {
            if (!$.fn.DataTable.isDataTable('#repaymentTable')) {
                $('#repaymentTable').DataTable({
                    responsive: true,
                    pageLength: 10,
                    order: [[3, 'desc']], // Order by date paid (newest first)
                    scrollX: true,
                    autoWidth: false,
                    language: {
                        search: "Search repayments:",
                        lengthMenu: "Show _MENU_ repayments",
                        info: "Showing _START_ to _END_ of _TOTAL_ repayments",
                        emptyTable: "No loan repayments found",
                        zeroRecords: "No matching repayments found"
                    },
                    columnDefs: [
                        { 
                            targets: '_all', 
                            className: 'text-nowrap' 
                        },
                        {
                            targets: [2], // Amount column
                            render: function(data, type, row) {
                                if (type === 'display') {
                                    // Format currency display
                                    const amount = parseFloat(data.replace(/[^\d.-]/g, ''));
                                    return 'KSh ' + formatCurrency(amount);
                                }
                                return data;
                            }
                        },
                        {
                            targets: [6], // Action column
                            orderable: false,
                            searchable: false
                        }
                    ],
                    drawCallback: function() {
                        // Reinitialize tooltips after table redraw
                        $('[data-toggle="tooltip"]').tooltip();
                    }
                });
            }
        }, 100);
    }
});

        // Repay Loan Form
       $('#repayLoanForm').submit(function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const submitButton = $form.find('button[type="submit"]');
    const originalText = submitButton.html();
    
    // Prevent multiple submissions
    if (submitButton.prop('disabled')) {
        console.log('Loan repayment submission blocked - already processing');
        return false;
    }
    
    // Disable submit button immediately
    submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
    
    // Comprehensive validation
    const validationErrors = [];
    const loanId = $('#loanSelect').val();
    const receiptNumber = $('#loanReceiptNumber').val()?.trim();
    const repayAmount = parseFloat($('#repayAmount').val());
    const paymentMode = $('#repayPaymentMode').val();
    const accountId = $('input[name="accountId"]').val();
    const servedBy = $('input[name="served_by"]').val();
    
    if (!loanId) validationErrors.push("Loan Selection");
    if (!receiptNumber) validationErrors.push("Receipt Number");
    if (!repayAmount || repayAmount <= 0) validationErrors.push("Valid Repayment Amount");
    if (!paymentMode) validationErrors.push("Payment Mode");
    
    // Check if loan is fully paid
    const nextDueText = $('#nextDueAmount').val();
    if (nextDueText && nextDueText.includes('Fully Paid')) {
        validationErrors.push("This loan is already fully paid");
    }
    
    // ADDED: Check against maximum reasonable repayment amount
    // This prevents accidentally large payments
    if (repayAmount > 1000000) { // 1 million KSh limit
        validationErrors.push("Repayment amount seems unusually large. Please verify.");
    }
    
    if (validationErrors.length > 0) {
        showToast("Please correct: " + validationErrors.join(", "), 'warning');
        submitButton.prop('disabled', false).html(originalText);
        return false;
    }
    
    // Prepare form data
    const formData = {
        accountId: accountId,
        loanId: loanId,
        repayAmount: repayAmount,
        paymentMode: paymentMode,
        receiptNumber: receiptNumber,
        served_by: servedBy
    };
    
    console.log('Submitting loan repayment:', formData);
    
    $.ajax({
        url: '../controllers/accountController.php?action=repayLoan',
        type: 'POST',
        data: formData,
        dataType: 'json',
        timeout: 20000,
        cache: false,
        success: function(response) {
            console.log('Loan repayment success response:', response);
            
            if (response && response.status === 'success') {
                let message = 'Loan repayment processed successfully!';
                let toastType = 'success';
                
                showToast(message, toastType);
                $('#repayLoanModal').modal('hide');
                
                // Trigger custom events for dashboard updates
                $(document).trigger('loanRepaymentProcessed', [response]);
                
                // Reload page to show updated loan schedule and repayments
                setTimeout(() => {
                    location.reload();
                }, 1500);
                
            } else {
                throw new Error(response?.message || 'Invalid response from server');
            }
        },
        error: function(xhr, status, error) {
            console.error('Loan repayment AJAX Error:', {
                status: status,
                error: error,
                statusCode: xhr.status,
                responseText: xhr.responseText
            });
            
            let errorMessage = 'Error processing loan repayment. Please try again.';
            
            if (status === 'timeout') {
                errorMessage = 'Request timed out. Please check your connection and try again.';
            } else if (xhr.status === 400) {
                errorMessage = 'Invalid repayment data. Please check your inputs.';
            } else if (xhr.status === 403) {
                errorMessage = 'Unauthorized. Please log in again.';
            } else if (xhr.status === 404) {
                errorMessage = 'Loan not found. Please refresh and try again.';
            } else if (xhr.status === 422) {
                errorMessage = 'Loan repayment validation failed. Please check loan status.';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error during loan processing. Please contact support.';
            } else if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (parseError) {
                    console.error('Error parsing loan repayment error response:', parseError);
                }
            }
            
            showToast(errorMessage, 'error');
        },
        complete: function(xhr, status) {
            // Always reset button state
            submitButton.prop('disabled', false).html(originalText);
            console.log('Loan repayment request completed with status:', status);
        }
    });
});




// Loan Select Change
$('#loanSelect').change(function() {
    const loanId = $(this).val();
    
    // Clear previous values
    $('#refNo, #outstandingBalance, #nextDueAmount, #repayAmount').val('');
    
    if (!loanId) {
        return;
    }
    
    // Show loading state
    $('#outstandingBalance').val('Loading...');
    $('#nextDueAmount').val('Loading...');
    
    $.ajax({
        url: '../controllers/accountController.php',
        type: 'GET',
        data: { 
            action: 'getLoanDetails',
            loanId: loanId 
        },
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if (response.status === 'success' && response.loan) {
                const loan = response.loan;
                
                // Check if loan is disbursed
                if (loan.status < 2) {
                    showToast('This loan is not yet disbursed and cannot accept repayments.', 'warning');
                    $('#loanSelect').val('');
                    $('#outstandingBalance, #nextDueAmount').val('');
                    return;
                }
                
                // Populate loan details
                $('#refNo').val(loan.ref_no || '');
                
                // FIXED: Show outstanding balance clearly labeled as "Principal Only"
                if (loan.outstanding_balance !== undefined) {
                    $('#outstandingBalance').val('KSh ' + formatCurrency(loan.outstanding_balance) + ' (Principal Only)');
                }
                
                // Handle current due amount from loan schedule
                if (loan.current_due_amount && loan.current_due_amount > 0) {
                    const currentDueAmount = parseFloat(loan.current_due_amount);
                    
                    let currentStatusText = '';
                    
                    // Current due amount (what needs to be paid now)
                    if (loan.is_overdue) {
                        currentStatusText = ' (Overdue - Pay Now!)';
                        $('#nextDueAmount').addClass('text-danger font-weight-bold');
                        $('#repayAmount').addClass('border-warning'); // Changed from border-danger
                        
                        if (loan.accumulated_defaults && loan.accumulated_defaults > 0) {
                            $('#nextDueAmount').attr('title', 
                                `This includes accumulated overdue amounts. Total overdue: KSh ${formatCurrency(loan.accumulated_defaults)}`
                            );
                        }
                    } else {
                        currentStatusText = ' (Current Due - Principal + Interest)';
                        $('#nextDueAmount').removeClass('text-danger font-weight-bold');
                        $('#repayAmount').removeClass('border-warning');
                        
                        if (loan.current_due_date) {
                            $('#nextDueAmount').attr('title', 
                                `Due on: ${new Date(loan.current_due_date).toLocaleDateString()} | Includes principal and interest`
                            );
                        }
                    }
                    
                    // Show current installment amount (principal + interest)
                    const currentDueText = `KSh ${formatCurrency(currentDueAmount)}${currentStatusText}`;
                    $('#nextDueAmount').val(currentDueText);
                    
                    // Auto-fill the current due amount as repayment amount
                    $('#repayAmount').val(currentDueAmount.toFixed(2));
                    
                    // Log detailed installment info for debugging
                    if (loan.current_installment_details) {
                        console.log('Current Installment Details:', {
                            fullAmount: loan.current_installment_details.full_amount,
                            alreadyPaid: loan.current_installment_details.already_paid,
                            remainingAmount: loan.current_installment_details.remaining_amount,
                            principal: loan.current_installment_details.principal,
                            interest: loan.current_installment_details.interest
                        });
                    }
                    
                } else {
                    // No payment due - loan is fully paid
                    $('#nextDueAmount').val('Loan Fully Paid - KSh 0.00');
                    $('#repayAmount').val('0.00');
                    $('#outstandingBalance').val('KSh 0.00 (Fully Paid)');
                    
                    // Disable repayment for fully paid loans
                    $('#repayAmount').prop('readonly', true);
                    showToast('This loan is fully paid. No repayment needed.', 'info');
                }
                
            } else {
                showToast('Error: Could not retrieve loan details - ' + (response.message || 'Unknown error'), 'error');
                $('#outstandingBalance, #nextDueAmount').val('Error loading');
            }
        },
        error: function(xhr, status, error) {
            console.error('Loan details AJAX Error:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            showToast('Error fetching loan details. Please try again.', 'error');
            $('#outstandingBalance, #nextDueAmount').val('Error');
        }
    });
});


// Reset form when modal is hidden
$('#repayLoanModal').on('hidden.bs.modal', function () {
    const $form = $('#repayLoanForm');
    $form[0].reset();
    $form.find('button[type="submit"]').prop('disabled', false).html('Repay Loan');
    $('#refNo, #outstandingBalance, #nextDueAmount, #repayAmount').val('');
    
    // Clear visual indicators
    $('#nextDueAmount').removeClass('text-danger font-weight-bold');
    $('#repayAmount').removeClass('border-danger').prop('readonly', false);
    
    // Clear any validation errors
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').remove();
    
    // Clear tooltips
    $('#nextDueAmount').removeAttr('title');
});

// Handle delete repayment button click
$(document).on('click', '.delete-repayment', function() {
    const repaymentId = $(this).data('repayment-id');
    const loanId = $(this).data('loan-id');
    const amount = $(this).data('amount');
    const receiptNumber = $(this).data('receipt');
    
    // Store data in modal for confirmation
    $('#deleteReceiptNo').text(receiptNumber);
    $('#deleteAmount').text(parseFloat(amount).toFixed(2));
    
    // Store the repayment details in modal data
    $('#deleteRepaymentModal').data('repayment-id', repaymentId);
    $('#deleteRepaymentModal').data('loan-id', loanId);
    $('#deleteRepaymentModal').data('amount', amount);
    
    // Show confirmation modal
    $('#deleteRepaymentModal').modal('show');
});

// Handle confirm delete button
$(document).on('click', '#confirmDeleteRepayment', function() {
    const modal = $('#deleteRepaymentModal');
    const repaymentId = modal.data('repayment-id');
    const loanId = modal.data('loan-id');
    const amount = modal.data('amount');
    
    const submitButton = $(this);
    const originalText = submitButton.html();
    
    submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
    
    $.ajax({
        url: '../controllers/accountController.php?action=deleteRepayment',
        type: 'POST',
        data: {
            repaymentId: repaymentId,
            loanId: loanId,
            amount: amount
        },
        dataType: 'json',
        timeout: 15000, // Longer timeout for delete operations
        success: function(response) {
            if (response.status === 'success') {
                showToast('Repayment deleted and loan schedule updated successfully!', 'success');
                modal.modal('hide');
                
                // FIXED: Reload page to show updated loan schedule
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast('Error: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Delete repayment AJAX Error:', {
                status: status,
                error: error,
                statusCode: xhr.status,
                responseText: xhr.responseText
            });
            
            let errorMessage = 'An error occurred while deleting the repayment.';
            
            if (status === 'timeout') {
                errorMessage = 'Delete operation timed out. Please try again.';
            } else if (xhr.status === 403) {
                errorMessage = 'Unauthorized. You may not have permission to delete repayments.';
            } else if (xhr.status === 404) {
                errorMessage = 'Repayment record not found.';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error during deletion. Please contact support.';
            } else if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (parseError) {
                    console.error('Error parsing delete response:', parseError);
                }
            }
            
            showToast(errorMessage, 'error');
        },
        complete: function() {
            submitButton.prop('disabled', false).html(originalText);
        }
    });
});



// Add validation for repayment amount input
$('#repayAmount').on('input', function() {
    const amount = parseFloat($(this).val());
    
    // Only check for negative amounts and reasonable limits
    if (amount < 0) {
        $(this).addClass('is-invalid');
        if (!$(this).next('.invalid-feedback').length) {
            $(this).after('<div class="invalid-feedback">Amount must be positive</div>');
        }
    } else if (amount > 1000000) {
        $(this).addClass('is-invalid');
        if (!$(this).next('.invalid-feedback').length) {
            $(this).after('<div class="invalid-feedback">Amount seems unusually large. Please verify.</div>');
        }
    } else {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
    }
});

// Add receipt number validation
$('#loanReceiptNumber').on('input', function() {
    const receiptNumber = $(this).val().trim();
    
    // Basic validation for receipt number format
    if (receiptNumber && !/^[A-Za-z0-9\-_]+$/.test(receiptNumber)) {
        $(this).addClass('is-invalid');
        if (!$(this).next('.invalid-feedback').length) {
            $(this).after('<div class="invalid-feedback">Receipt number can only contain letters, numbers, hyphens, and underscores</div>');
        }
    } else {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
    }
});





    // Print repayment receipt
 $(document).on('click', '.print-repayment-receipt', function() {
    const repaymentId = $(this).data('repayment-id');
    const $button = $(this);
    const originalText = $button.html();
    
    $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');
    
    $.ajax({
        url: '../controllers/accountController.php',
        type: 'GET',
        data: {
            action: 'getRepaymentDetails',
            repaymentId: repaymentId
        },
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if (response.status === 'success' && response.repayment) {
                if (typeof printLoanRepaymentReceipt === 'function') {
                    printLoanRepaymentReceipt(response.repayment);
                } else {
                    showToast('Print function not available', 'warning');
                }
            } else {
                showToast('Error fetching repayment details: ' + (response.message || 'Unknown error'), 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Print receipt error:', { status, error, responseText: xhr.responseText });
            
            let errorMessage = 'Error generating receipt';
            if (status === 'timeout') {
                errorMessage = 'Request timed out while fetching receipt details';
            } else if (xhr.status === 404) {
                errorMessage = 'Repayment record not found';
            }
            
            showToast(errorMessage, 'error');
        },
        complete: function() {
            $button.prop('disabled', false).html(originalText);
        }
    });
});


// Add confirmation before navigation if form has unsaved changes
let formChanged = false;

$('#repayLoanForm input, #repayLoanForm select').on('change input', function() {
    formChanged = true;
});

$('#repayLoanForm').on('submit', function() {
    formChanged = false; // Reset flag on form submission
});

$('#repayLoanModal').on('hidden.bs.modal', function() {
    if (formChanged) {
        formChanged = false;
        // Could add confirmation here if needed
    }
});

// Auto-refresh loan data every 30 seconds when modal is open
let refreshInterval;

$('#repayLoanModal').on('shown.bs.modal', function() {
    // Start auto-refresh if a loan is selected
    refreshInterval = setInterval(function() {
        const selectedLoanId = $('#loanSelect').val();
        if (selectedLoanId) {
            // Trigger change event to refresh loan details
            $('#loanSelect').trigger('change');
        }
    }, 30000); // 30 seconds
});

$('#repayLoanModal').on('hidden.bs.modal', function() {
    // Stop auto-refresh
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
});

// Add keyboard shortcuts for common actions
$(document).on('keydown', function(e) {
    // Ctrl/Cmd + R to open repay loan modal (when not in input field)
    if ((e.ctrlKey || e.metaKey) && e.key === 'r' && !$(e.target).is('input, textarea, select')) {
        e.preventDefault();
        if ($('#repayLoanModal').is(':visible')) {
            $('#repayLoanModal').modal('hide');
        } else {
            $('#repayLoanModal').modal('show');
        }
    }
    
    // ESC to close modals
    if (e.key === 'Escape') {
        $('.modal').modal('hide');
    }
});


    // FUNCTION TO FORMAT WHOLE NUMBERS
function formatCurrency(amount) {
    if (isNaN(amount)) return '0.00';
    return parseFloat(amount).toLocaleString('en-KE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
});
</script>