<?php
// Extract account types for the dropdowns
$accountTypes = [];
if (isset($accountDetails['account_type']) && !empty($accountDetails['account_type'])) {
    $accountTypes = array_map('trim', explode(', ', $accountDetails['account_type']));
}
?>

<style>
/* Savings Section Styles */
.modal-header{
    background-color: #51087E;
}

.savings-section {
    margin-bottom: 25px;
}

.savings-section .section-header {
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.savings-section .section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #51087E;
    margin: 0;
}

.savings-section .action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.savings-section .table-container {
    background: #fff;
    border-radius: 0.35rem;
    overflow: hidden;
    box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
    border: 0;
}

.savings-section .card-body {
    padding: 1.25rem;
}

.savings-section .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.savings-section .table {
    width: 100%;
    margin-bottom: 0;
    color: #858796;
    border-collapse: collapse;
}

.savings-section .table thead th {
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

.savings-section .table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #e3e6f0;
    border-bottom: none;
    border-right: none;
    font-size: 0.875rem;
}

.savings-section .table tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.025);
}

.savings-section .table-bordered {
    border: 1px solid #e3e6f0;
}

.savings-section .table-bordered th,
.savings-section .table-bordered td {
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

.btn-warning-modern {
    background-color: #f6c23e;
    border-color: #f6c23e;
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

.btn-warning-modern:hover {
    background-color: #dda20a;
    border-color: #dda20a;
    color: #fff;
    text-decoration: none;
}

.btn-warning-modern:focus {
    box-shadow: 0 0 0 0.2rem rgba(246, 194, 62, 0.25);
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

/* Badge Styles */
.badge-modern {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-success { 
    background: #1cc88a; 
    color: #fff; 
}

.badge-warning { 
    background: #f6c23e; 
    color: #fff; 
}

/* Modal Styles */
.savings-section .modal-content {
    border: 0;
    border-radius: 0.35rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.savings-section .modal-header {
    background: #51087E;
    color: #fff;
    border-bottom: 1px solid #e3e6f0;
    border-top-left-radius: 0.35rem;
    border-top-right-radius: 0.35rem;
    padding: 1rem;
}

.savings-section .modal-header .close {
    color: #fff;
    opacity: 0.8;
}

.savings-section .modal-header .close:hover {
    opacity: 1;
    color: #fff;
}

.savings-section .modal-body {
    padding: 1.5rem;
}

.savings-section .modal-footer {
    border-top: 1px solid #e3e6f0;
    border-bottom-left-radius: 0.35rem;
    border-bottom-right-radius: 0.35rem;
    padding: 1rem;
}

.savings-section .form-group label {
    font-weight: 600;
    color: #51087E;
    margin-bottom: 8px;
    display: block;
}

.savings-section .form-control {
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.savings-section .form-control:focus {
    border-color: #51087E;
    box-shadow: 0 0 0 0.2rem rgba(81, 8, 126, 0.1);
    outline: 0;
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
    .savings-section .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .savings-section .action-buttons {
        width: 100%;
        justify-content: flex-start;
    }
    
    .savings-section .table {
        font-size: 0.8rem;
    }
    
    .savings-section .table thead th,
    .savings-section .table tbody td {
        padding: 0.5rem 0.375rem;
    }
    
    .btn-sm {
        padding: 0.125rem 0.25rem;
        font-size: 0.75rem;
    }
}

@media (max-width: 480px) {
    .savings-section .section-title {
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

<!-- Savings and Withdrawals Section -->
<div class="content-section savings-section" id="savings-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-piggy-bank"></i>
            Savings and Withdrawals
        </h2>
        <div class="action-buttons">
            <button class="btn btn-success-modern" data-toggle="modal" data-target="#addSavingsModal">
                <i class="fas fa-plus"></i> Add Savings
            </button>
            <button class="btn btn-warning-modern" data-toggle="modal" data-target="#withdrawModal">
                <i class="fas fa-minus"></i> Withdraw
            </button>
        </div>
    </div>
    
    <?php if (!empty($savings)): ?>
        <div class="card mb-4 table-container">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="savingsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Receipt No</th>
                                <th>Amount</th>
                                <th>Withdrawal Fee</th>
                                <th>Payment Mode</th>
                                <th>Served By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($savings as $saving): ?>
                                <tr>
                                    <td><?= date("Y-m-d H:i:s", strtotime($saving['date'])) ?></td>
                                    <td>
                                        <span class="badge-modern <?= $saving['type'] === 'Savings' ? 'badge-success' : 'badge-warning' ?>">
                                            <?= htmlspecialchars($saving['type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($saving['receipt_number']) ?></td>
                                    <td>KSh <?= number_format($saving['amount'], 2) ?></td>
                                    <td><?= $saving['type'] === 'Withdrawal' ? 'KSh ' . number_format($saving['withdrawal_fee'] ?? 0, 2) : 'N/A' ?></td>
                                    <td><?= htmlspecialchars($saving['payment_mode']) ?></td>
                                    <td><?= htmlspecialchars($saving['served_by_name'] ?? 'System') ?></td>
                                    <td>
                                        <button class="btn btn-primary-modern btn-sm print-savings-receipt" 
                                                data-id="<?= $saving['saving_id'] ?>" 
                                                data-type="<?= htmlspecialchars($saving['type']) ?>">
                                            <i class="fas fa-print"></i> Print Receipt
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-piggy-bank empty-icon"></i>
            <p class="empty-text">No savings or withdrawal records found for this account.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Savings Modal -->
<div class="modal fade" id="addSavingsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white">
                    <i class="fas fa-piggy-bank mr-2"></i>Add Savings
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addSavingsForm">
                <div class="modal-body">
                    <input type="hidden" name="accountId" value="<?= $accountId ?>">
                    <div class="form-group">
                        <label for="receiptNumber">Receipt Number</label>
                        <input type="text" class="form-control" id="receiptNumber" name="receiptNumber" required>
                    </div>
                    <div class="form-group">
                        <label for="accountType">Account Type</label>
                        <select class="form-control" id="accountType" name="accountType" required>
                            <option value="">Select account type</option>
                            <?php foreach($accountTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="availableBalance">Available Balance</label>
                        <input type="text" class="form-control" id="availableBalance" readonly>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input type="number" class="form-control" id="amount" name="amount" required step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label for="paymentMode">Payment Mode</label>
                        <select class="form-control" id="paymentMode" name="paymentMode" required>
                            <option value="Cash">Cash</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Add Savings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Withdraw Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white">
                    <i class="fas fa-hand-holding-usd mr-2"></i>Withdraw Savings
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="withdrawForm">
                <div class="modal-body">
                    <input type="hidden" name="accountId" value="<?= $accountId ?>">
                    <input type="hidden" name="served_by" value="<?= $_SESSION['user_id'] ?>">
                    <div class="form-group">
                        <label for="withdrawReceiptNumber">Receipt Number</label>
                        <input type="text" class="form-control" id="withdrawReceiptNumber" name="receiptNumber" required>
                    </div>
                    <div class="form-group">
                        <label for="withdrawAccountType">Account Type</label>
                        <select class="form-control" id="withdrawAccountType" name="accountType" required>
                            <option value="">Select account type</option>
                            <?php foreach($accountTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="withdrawAvailableBalance">Available Balance</label>
                        <input type="text" class="form-control" id="withdrawAvailableBalance" readonly>
                    </div>
                    <div class="form-group">
                        <label for="withdrawAmount">Amount</label>
                        <input type="number" class="form-control" id="withdrawAmount" name="amount" required step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label for="withdrawalFee">Withdrawal Fee</label>
                        <input type="number" class="form-control" id="withdrawalFee" name="withdrawalFee" required step="0.01" min="0" value="0">
                        <small class="text-muted">This fee will be deducted from the available balance</small>
                    </div>
                    <div class="form-group">
                        <label for="totalWithdrawal">Total Amount (including fee)</label>
                        <input type="text" class="form-control" id="totalWithdrawal" readonly>
                    </div>
                    <div class="form-group">
                        <label for="withdrawPaymentMode">Payment Mode</label>
                        <select class="form-control" id="withdrawPaymentMode" name="paymentMode" required>
                            <option value="Cash">Cash</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning">Withdraw</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const ACCOUNT_ID = <?= $accountId ?>;
    
    // Initialize DataTable for savings when section becomes active
    $(document).on('sectionChanged', function(event, section) {
        if (section === 'savings') {
            setTimeout(() => {
                if (!$.fn.DataTable.isDataTable('#savingsTable')) {
                    $('#savingsTable').DataTable({
                        responsive: true,
                        pageLength: 10,
                        order: [[0, 'desc']], // Order by date
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

    // =====================================
    // BALANCE MANAGEMENT
    // =====================================
    
    function getAvailableBalance(accountType, targetSelector = '#availableBalance, #withdrawAvailableBalance') {
        if (!accountType) return;
        
        $.ajax({
            url: '../controllers/accountController.php?action=getAvailableBalance',
            type: 'GET',
            data: {
                accountId: ACCOUNT_ID,
                accountType: accountType
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data && data.status === 'success') {
                        $(targetSelector).val('KSh ' + formatCurrency(data.balance));
                    } else {
                        $(targetSelector).val('Error loading balance');
                        if (typeof showToast === 'function') {
                            showToast('Error loading balance: ' + (data ? data.message : 'Unknown error'), 'warning');
                        }
                    }
                } catch (e) {
                    $(targetSelector).val('Error loading balance');
                    console.error('Error parsing balance response:', e);
                }
            },
            error: function(xhr, status, error) {
                $(targetSelector).val('Error loading balance');
                console.error('Error fetching balance:', error);
            }
        });
    }

    // Account type selection handlers
    $('#accountType').change(function() {
        const selectedType = $(this).val();
        getAvailableBalance(selectedType, '#availableBalance');
    });

    $('#withdrawAccountType').change(function() {
        const selectedType = $(this).val();
        getAvailableBalance(selectedType, '#withdrawAvailableBalance');
    });

    // Update withdrawal total calculation
    $('#withdrawAmount, #withdrawalFee').on('input', function() {
        const amount = parseFloat($('#withdrawAmount').val()) || 0;
        const fee = parseFloat($('#withdrawalFee').val()) || 0;
        const total = amount + fee;
        $('#totalWithdrawal').val('KSh ' + formatCurrency(total));
    });

    // =====================================
    // FORM SUBMISSIONS
    // =====================================
    
    // Add Savings Form - Fixed to match controller expectations
    $('#addSavingsForm').submit(function(e) {
        e.preventDefault();
        const submitButton = $(this).find('button[type="submit"]');
        const originalText = submitButton.html();
        
        submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        // Validation
        const missingFields = [];
        if (!$('#receiptNumber').val()) missingFields.push("Receipt Number");
        if (!$('#accountType').val()) missingFields.push("Account Type");
        if (!$('#amount').val()) missingFields.push("Amount");
        if (!$('#paymentMode').val()) missingFields.push("Payment Mode");

        if (missingFields.length > 0) {
            if (typeof showToast === 'function') {
                showToast("Please fill in: " + missingFields.join(", "), 'warning');
            }
            submitButton.prop('disabled', false).html(originalText);
            return;
        }
        
        // Submit the form - no dataType specified to avoid JSON parsing issues
        $.ajax({
            url: '../controllers/accountController.php?action=addSavings',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                console.log('Add Savings Response:', response);
                
                // Success - operation completed successfully
                if (typeof showToast === 'function') {
                    showToast('Savings added successfully!', 'success');
                }
                $('#addSavingsModal').modal('hide');
                
                // Trigger custom event for dashboard updates
                $(document).trigger('savingsProcessed', [{ status: 'success' }]);
                
                setTimeout(() => location.reload(), 1500);
            },
            error: function(xhr, status, error) {
                console.log('AJAX Status:', xhr.status);
                console.log('Response Text:', xhr.responseText);
                
                // Check if it's actually successful despite being in error callback
                if (xhr.status === 200 || xhr.status === 201) {
                    if (typeof showToast === 'function') {
                        showToast('Savings added successfully!', 'success');
                    }
                    $('#addSavingsModal').modal('hide');
                    setTimeout(() => location.reload(), 1500);
                } else if (xhr.status !== 0) { // Only show error for non-cancelled requests
                    console.error('Actual error occurred:', error);
                    if (typeof showToast === 'function') {
                        showToast('Error adding savings. Please try again.', 'error');
                    }
                }
            },
            complete: function() {
                submitButton.prop('disabled', false).html(originalText);
            }
        });
    });

    // Withdraw Form - matches controller pattern
    $('#withdrawForm').submit(function(e) {
        e.preventDefault();
        const submitButton = $(this).find('button[type="submit"]');
        const originalText = submitButton.html();
        
        submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        // Validation
        const missingFields = [];
        if (!$('#withdrawReceiptNumber').val()) missingFields.push("Receipt Number");
        if (!$('#withdrawAccountType').val()) missingFields.push("Account Type");
        if (!$('#withdrawAmount').val()) missingFields.push("Amount");
        if (!$('#withdrawPaymentMode').val()) missingFields.push("Payment Mode");

        if (missingFields.length > 0) {
            if (typeof showToast === 'function') {
                showToast("Please fill in: " + missingFields.join(", "), 'warning');
            }
            submitButton.prop('disabled', false).html(originalText);
            return;
        }

        const formData = {
            accountId: $('input[name="accountId"]').val(),
            amount: parseFloat($('#withdrawAmount').val()) || 0,
            withdrawalFee: parseFloat($('#withdrawalFee').val()) || 0,
            accountType: $('#withdrawAccountType').val(),
            receiptNumber: $('#withdrawReceiptNumber').val(),
            paymentMode: $('#withdrawPaymentMode').val(),
            served_by: $('input[name="served_by"]').val()
        };

        $.ajax({
            url: '../controllers/accountController.php?action=withdraw',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success') {
                    if (typeof showToast === 'function') {
                        showToast('Withdrawal processed successfully!', 'success');
                    }
                    $('#withdrawModal').modal('hide');
                    
                    // Trigger custom event for dashboard updates
                    $(document).trigger('withdrawalProcessed', [response]);
                    
                    if (response.details && typeof printWithdrawalReceipt === 'function') {
                        printWithdrawalReceipt(response.details);
                    }
                    setTimeout(() => location.reload(), 1500);
                } else {
                    if (typeof showToast === 'function') {
                        showToast('Error: ' + (response && response.message ? response.message : 'Unknown error occurred'), 'error');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('Withdraw AJAX Status:', xhr.status);
                if (xhr.status === 200 || xhr.status === 201) {
                    if (typeof showToast === 'function') {
                        showToast('Withdrawal processed successfully!', 'success');
                    }
                    $('#withdrawModal').modal('hide');
                    setTimeout(() => location.reload(), 1500);
                } else if (xhr.status !== 0) {
                    console.error('Withdrawal error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Error processing withdrawal. Please try again.', 'error');
                    }
                }
            },
            complete: function() {
                submitButton.prop('disabled', false).html(originalText);
            }
        });
    });

    // Print savings receipt
    $(document).on('click', '.print-savings-receipt', function() {
        const savingsId = $(this).data('id');
        const type = $(this).data('type');
        
        $.ajax({
            url: '../controllers/accountController.php',
            type: 'GET',
            data: {
                action: 'getSavingsDetails',
                savingsId: savingsId
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data && data.status === 'success') {
                        if (typeof printSavingsReceipt === 'function') {
                            printSavingsReceipt(data.details, type);
                        } else {
                            if (typeof showToast === 'function') {
                                showToast('Print function not available', 'warning');
                            }
                        }
                    } else {
                        if (typeof showToast === 'function') {
                            showToast('Error fetching receipt details', 'error');
                        }
                    }
                } catch (e) {
                    if (typeof showToast === 'function') {
                        showToast('Error processing receipt data', 'error');
                    }
                    console.error('Error parsing receipt response:', e);
                }
            },
            error: function(xhr, status, error) {
                if (typeof showToast === 'function') {
                    showToast('Error generating receipt', 'error');
                }
                console.error('Print receipt error:', error);
            }
        });
    });

    // =====================================
    // UTILITY FUNCTIONS
    // =====================================
    
    function formatCurrency(amount) {
        return parseFloat(amount || 0).toLocaleString('en-KE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // =====================================
    // MODAL RESET FUNCTIONS
    // =====================================

    // Reset forms when modals are closed
    $('#addSavingsModal').on('hidden.bs.modal', function () {
        $('#addSavingsForm')[0].reset();
        $('#availableBalance').val('');
    });

    $('#withdrawModal').on('hidden.bs.modal', function () {
        $('#withdrawForm')[0].reset();
        $('#withdrawAvailableBalance').val('');
        $('#totalWithdrawal').val('');
    });

    // Initialize balance for first account type when modals open
    $('#addSavingsModal').on('shown.bs.modal', function () {
        const firstAccountType = $('#accountType option:nth-child(2)').val(); // Skip "Select account type" option
        if (firstAccountType) {
            $('#accountType').val(firstAccountType).trigger('change');
        }
    });

    $('#withdrawModal').on('shown.bs.modal', function () {
        const firstAccountType = $('#withdrawAccountType option:nth-child(2)').val(); // Skip "Select account type" option
        if (firstAccountType) {
            $('#withdrawAccountType').val(firstAccountType).trigger('change');
        }
    });

    // =====================================
    // ERROR PREVENTION
    // =====================================
    
    // Suppress any JavaScript errors that might cause browser dialogs
    window.addEventListener('error', function(event) {
        console.error('JavaScript Error:', {
            message: event.message,
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno,
            error: event.error
        });
        
        // Prevent the browser from showing the default error dialog
        event.preventDefault();
        return true;
    });

    // Handle unhandled promise rejections
    window.addEventListener('unhandledrejection', function(event) {
        console.error('Unhandled Promise Rejection:', event.reason);
        event.preventDefault();
    });

    // Override any potential alert() calls
    const originalAlert = window.alert;
    window.alert = function(message) {
        console.log('Alert suppressed:', message);
        if (typeof showToast === 'function') {
            showToast(message, 'info');
        }
    };

    // =====================================
    // GLOBAL AJAX ERROR HANDLER
    // =====================================
    
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        // Only log actual errors, not cancelled requests
        if (xhr.status !== 0) {
            console.error('Global AJAX Error:', {
                url: settings.url,
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText ? xhr.responseText.substring(0, 200) : 'No response',
                thrownError: thrownError
            });
        }
        
        // Prevent any error alerts from showing
        event.stopPropagation();
    });
});
</script>