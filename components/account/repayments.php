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
                        order: [[3, 'desc']], // Order by date paid
                        scrollX: true,
                        autoWidth: false,
                        language: {
                            search: "Search:",
                            lengthMenu: "Show _MENU_ entries",
                            info: "Showing _START_ to _END_ of _TOTAL_ entries"
                        },
                        columnDefs: [
                            { targets: '_all', className: 'text-nowrap' }
                        ]
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
        
        // Check for duplicate receipt number in repayments table
        const existingRepaymentReceipts = [];
        $('#repaymentTable tbody tr').each(function() {
            const receiptCell = $(this).find('td:eq(1)').text().trim();
            if (receiptCell) existingRepaymentReceipts.push(receiptCell);
        });
        
        if (existingRepaymentReceipts.includes(receiptNumber)) {
            validationErrors.push("Receipt Number already exists in repayments");
        }
        
        // Validate repayment amount against outstanding balance
        const outstandingText = $('#outstandingBalance').val();
        if (outstandingText) {
            const outstandingMatch = outstandingText.match(/[\d,]+\.?\d*/);
            if (outstandingMatch) {
                const outstandingAmount = parseFloat(outstandingMatch[0].replace(/,/g, ''));
                if (repayAmount > outstandingAmount) {
                    validationErrors.push("Repayment amount cannot exceed outstanding balance");
                }
            }
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
            timeout: 20000, // Longer timeout for loan processing
            cache: false,
            success: function(response) {
                console.log('Loan repayment success response:', response);
                
                if (response && response.status === 'success') {
                    showToast('Loan repayment processed successfully!', 'success');
                    $('#repayLoanModal').modal('hide');
                    
                    // Trigger custom events for dashboard updates
                    $(document).trigger('loanRepaymentProcessed', [response]);
                    
                    // Optional: Print receipt if repayment details available
                    if (response.repaymentDetails && typeof printLoanRepaymentReceipt === 'function') {
                        setTimeout(() => {
                            printLoanRepaymentReceipt(response.repaymentDetails);
                        }, 500);
                    }
                    
                    // Reload page after delay to show updated data
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                    
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
                    errorMessage = 'Request timed out. Loan processing takes time - please check your connection and try again.';
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
                
                if (loan.outstanding_balance !== undefined) {
                    $('#outstandingBalance').val('KSh ' + formatCurrency(loan.outstanding_balance));
                }
                
                // Handle the next due amount properly
                if (loan.next_due_amount && loan.next_due_amount > 0) {
                    const dueAmount = parseFloat(loan.next_due_amount);
                    let statusText = '';
                    let displayText = '';
                    
                    if (loan.is_overdue) {
                        statusText = ' (Overdue + Accumulated)';
                        displayText = `KSh ${formatCurrency(dueAmount)}${statusText}`;
                        
                        // Add helpful tooltip about accumulated defaults
                        if (loan.accumulated_defaults && loan.accumulated_defaults > 0) {
                            $('#nextDueAmount').attr('title', 
                                `Total includes accumulated defaults: KSh ${formatCurrency(loan.accumulated_defaults)}`
                            );
                        }
                    } else {
                        statusText = ' (Current Due)';
                        displayText = `KSh ${formatCurrency(dueAmount)}${statusText}`;
                        
                        if (loan.next_due_date) {
                            $('#nextDueAmount').attr('title', 
                                `Due on: ${new Date(loan.next_due_date).toLocaleDateString()}`
                            );
                        }
                    }
                    
                    $('#nextDueAmount').val(displayText);
                    $('#repayAmount').val(dueAmount.toFixed(2));
                    
                } else {
                    $('#nextDueAmount').val('No payment due / Loan fully paid');
                    $('#repayAmount').val('');
                }
                
                // Add visual indicators for overdue status
                if (loan.is_overdue) {
                    $('#nextDueAmount').addClass('text-danger font-weight-bold');
                    $('#repayAmount').addClass('border-danger');
                } else {
                    $('#nextDueAmount').removeClass('text-danger font-weight-bold');
                    $('#repayAmount').removeClass('border-danger');
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
    $form.find('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Repay Loan');
    $('#refNo, #outstandingBalance, #nextDueAmount, #repayAmount').val('');
    
    // Clear any validation errors
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').remove();
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
        success: function(response) {
            if (response.status === 'success') {
                if (typeof showToast === 'function') {
                    showToast('Repayment deleted successfully!', 'success');
                } else {
                    alert('Repayment deleted successfully!');
                }
                modal.modal('hide');
                
                // Reload the page to refresh the repayments table
                setTimeout(() => location.reload(), 1000);
            } else {
                if (typeof showToast === 'function') {
                    showToast('Error: ' + response.message, 'error');
                } else {
                    alert('Error: ' + response.message);
                }
            }
        },
        error: function(xhr, status, error) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (typeof showToast === 'function') {
                    showToast('Error: ' + response.message, 'error');
                } else {
                    alert('Error: ' + response.message);
                }
            } catch (e) {
                if (typeof showToast === 'function') {
                    showToast('An error occurred while deleting the repayment.', 'error');
                } else {
                    alert('An error occurred while deleting the repayment.');
                }
            }
        },
        complete: function() {
            submitButton.prop('disabled', false).html(originalText);
        }
    });
});




    // Print repayment receipt
    $(document).on('click', '.print-repayment-receipt', function() {
        const repaymentId = $(this).data('repayment-id');
        $.ajax({
            url: '../controllers/accountController.php',
            type: 'GET',
            data: {
                action: 'getRepaymentDetails',
                repaymentId: repaymentId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    printLoanRepaymentReceipt(response.repayment);
                } else {
                    if (typeof showToast === 'function') {
                        showToast('Error fetching repayment details', 'error');
                    } else {
                        alert('Error fetching repayment details');
                    }
                }
            },
            error: function() {
                if (typeof showToast === 'function') {
                    showToast('Error generating receipt', 'error');
                } else {
                    alert('Error generating receipt');
                }
            }
        });
    });

    // FUNCTION TO FORMAT WHOLE NUMBERS
function formatCurrency(amount) {
    return parseFloat(amount).toLocaleString('en-KE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
});
</script>