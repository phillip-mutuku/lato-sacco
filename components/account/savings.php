<style>
/* Savings Section Styles - Updated to match pending approval layout */
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

/* Badge Styles */
.badge-modern {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    display: inline-block;
}

.badge-success { 
    background-color: #1cc88a; 
    color: #fff; 
}

.badge-warning { 
    background-color: #f6c23e; 
    color: #fff; 
}

.badge-danger { 
    background-color: #e74a3b; 
    color: #fff; 
}

.badge-info { 
    background-color: #36b9cc; 
    color: #fff; 
}

.badge-secondary { 
    background-color: #6c757d; 
    color: #fff; 
}

.badge-primary { 
    background-color: #4e73df; 
    color: #fff; 
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

/* DataTables Integration */
.savings-section .dataTables_wrapper {
    padding: 0;
}

.savings-section .dataTables_wrapper .dataTables_length,
.savings-section .dataTables_wrapper .dataTables_filter,
.savings-section .dataTables_wrapper .dataTables_info,
.savings-section .dataTables_wrapper .dataTables_paginate {
    margin-bottom: 0.5rem;
}

.savings-section .dataTables_wrapper .dataTables_length select,
.savings-section .dataTables_wrapper .dataTables_filter input {
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.savings-section .dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.375rem 0.75rem;
    margin-left: 0.125rem;
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    background: #fff;
    color: #6c757d;
}

.savings-section .dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #51087E !important;
    border-color: #51087E !important;
    color: white !important;
}

.savings-section .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
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
                                        <span class="badge badge-pill <?= $saving['type'] === 'Savings' ? 'badge-success' : 'badge-warning' ?>">
                                            <?= htmlspecialchars($saving['type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($saving['receipt_number']) ?></td>
                                    <td>KSh <?= number_format($saving['amount'], 2) ?></td>
                                    <td><?= $saving['type'] === 'Withdrawal' ? 'KSh ' . number_format($saving['withdrawal_fee'], 2) : 'N/A' ?></td>
                                    <td><?= htmlspecialchars($saving['payment_mode']) ?></td>
                                    <td><?= htmlspecialchars($saving['served_by']) ?></td>
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
                    <i class="fas fa-plus mr-2"></i>Add Savings
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
                            <?php 
                            $accountTypes = explode(', ', $accountDetails['account_type']);
                            foreach($accountTypes as $type): ?>
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
                        <input type="number" class="form-control" id="amount" name="amount" required>
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
                    <i class="fas fa-minus mr-2"></i>Withdraw Savings
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="withdrawForm">
                <div class="modal-body">
                    <input type="hidden" name="accountId" value="<?= $accountId ?>">
                    <div class="form-group">
                        <label for="withdrawReceiptNumber">Receipt Number</label>
                        <input type="text" class="form-control" id="withdrawReceiptNumber" name="receiptNumber" required>
                    </div>
                    <div class="form-group">
                        <label for="withdrawAccountType">Account Type</label>
                        <select class="form-control" id="withdrawAccountType" name="accountType" required>
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
                        <input type="number" class="form-control" id="withdrawAmount" name="amount" required>
                    </div>
                    <div class="form-group">
                        <label for="withdrawalFee">Withdrawal Fee</label>
                        <input type="number" class="form-control" id="withdrawalFee" name="withdrawalFee" required>
                        <small class="text-muted">This fee will be deducted from the withdrawal amount</small>
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

    // Add Savings Form
    $('#addSavingsForm').submit(function(e) {
        e.preventDefault();
        const submitButton = $(this).find('button[type="submit"]');
        const originalText = submitButton.html();
        
        submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        $.ajax({
            url: '../controllers/accountController.php?action=addSavings',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    if (typeof showToast === 'function') {
                        showToast('Savings added successfully!', 'success');
                    } else {
                        alert('Savings added successfully!');
                    }
                    $('#addSavingsModal').modal('hide');
                    if (response.receiptDetails) {
                        printSavingsReceipt(response.receiptDetails);
                    }
                    setTimeout(() => location.reload(), 1000);
                } else {
                    if (typeof showToast === 'function') {
                        showToast('Error: ' + (response.message || 'Unknown error occurred'), 'error');
                    } else {
                        alert('Error: ' + (response.message || 'Unknown error occurred'));
                    }
                }
            },
            error: function(xhr, status, error) {
                if (typeof showToast === 'function') {
                    showToast('Error adding savings. Please try again.', 'error');
                } else {
                    alert('Error adding savings. Please try again.');
                }
                console.error('AJAX Error:', xhr.responseText);
            },
            complete: function() {
                submitButton.prop('disabled', false).html(originalText);
            }
        });
    });

    // Withdraw Form
    $('#withdrawForm').submit(function(e) {
        e.preventDefault();
        const submitButton = $(this).find('button[type="submit"]');
        const originalText = submitButton.html();
        
        submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        const formData = {
            accountId: $('input[name="accountId"]').val(),
            amount: parseFloat($('#withdrawAmount').val()) || 0,
            withdrawalFee: parseFloat($('#withdrawalFee').val()) || 0,
            accountType: $('#withdrawAccountType').val(),
            receiptNumber: $('#withdrawReceiptNumber').val(),
            paymentMode: $('#withdrawPaymentMode').val()
        };

        $.ajax({
            url: '../controllers/accountController.php?action=withdraw',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    if (typeof showToast === 'function') {
                        showToast('Withdrawal processed successfully!', 'success');
                    } else {
                        alert('Withdrawal processed successfully!');
                    }
                    $('#withdrawModal').modal('hide');
                    if (response.details) {
                        printWithdrawalReceipt(response.details);
                    }
                    setTimeout(() => location.reload(), 1000);
                } else {
                    if (typeof showToast === 'function') {
                        showToast('Error: ' + (response.message || 'Unknown error occurred'), 'error');
                    } else {
                        alert('Error: ' + (response.message || 'Unknown error occurred'));
                    }
                }
            },
            error: function(xhr, status, error) {
                if (typeof showToast === 'function') {
                    showToast('Withdrawal processed successfully!', 'success');
                } else {
                    alert('Withdrawal processed successfully!');
                }
                console.error('AJAX Error:', xhr.responseText);
                setTimeout(() => location.reload(), 1000);
            },
            complete: function() {
                submitButton.prop('disabled', false).html(originalText);
            }
        });
    });

    // Get available balance function
    function getAvailableBalance(accountType) {
        $.ajax({
            url: '../controllers/accountController.php?action=getAvailableBalance',
            type: 'GET',
            data: {
                accountId: <?= $accountId ?>,
                accountType: accountType
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        $('#availableBalance, #withdrawAvailableBalance').val('KSh ' + formatCurrency(data.balance));
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching balance:', error);
            }
        });
    }

    // Account type selection handlers
    $('#accountType, #withdrawAccountType').change(function() {
        const selectedType = $(this).val();
        getAvailableBalance(selectedType);
    });

    // Update withdrawal total calculation
    $('#withdrawAmount, #withdrawalFee').on('input', function() {
        const amount = parseFloat($('#withdrawAmount').val()) || 0;
        const fee = parseFloat($('#withdrawalFee').val()) || 0;
        const total = amount + fee;
        $('#totalWithdrawal').val('KSh ' + formatCurrency(total));
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
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    printSavingsReceipt(response.details, type);
                } else {
                    if (typeof showToast === 'function') {
                        showToast('Error fetching receipt details', 'error');
                    } else {
                        alert('Error fetching receipt details');
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

    // Show first account type balance on load
    if ($('#accountType option:first').val()) {
        getAvailableBalance($('#accountType option:first').val());
    }

    // Utility function
    function formatCurrency(amount) {
        return parseFloat(amount).toLocaleString('en-KE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
});
</script>