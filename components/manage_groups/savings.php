<?php
// components/manage_groups/savings.php
?>

<!-- Savings & Withdrawals Section -->
<div id="savings-section" class="content-section">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="m-0">Savings and Withdrawals</h5>
                    <div class="action-buttons">
                        <button class="btn btn-success" data-toggle="modal" data-target="#addSavingsModal">
                            <i class="fas fa-plus"></i> Add Savings
                        </button>
                        <button class="btn btn-warning" data-toggle="modal" data-target="#withdrawModal">
                            <i class="fas fa-minus"></i> Withdraw
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="savingsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Member</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Payment Mode</th>
                                <th>Served By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $allTransactions = array_merge(
                                array_map(function($s) { 
                                    return array_merge($s, ['type' => 'Savings']); 
                                }, $savings),
                                array_map(function($w) { 
                                    return array_merge($w, ['type' => 'Withdrawal']); 
                                }, $withdrawals)
                            );
                            usort($allTransactions, function($a, $b) {
                                return strtotime($b['date']) - strtotime($a['date']);
                            });
                            foreach ($allTransactions as $transaction):
                            ?>
                                <tr>
                                    <td><?= date("Y-m-d H:i", strtotime($transaction['date'])) ?></td>
                                    <td><?= htmlspecialchars($transaction['member_name']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $transaction['type'] === 'Savings' ? 'success' : 'warning' ?>">
                                            <?= $transaction['type'] ?>
                                        </span>
                                    </td>
                                    <td>KSh <?= number_format($transaction['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($transaction['payment_mode']) ?></td>
                                    <td><?= htmlspecialchars($transaction['served_by_name']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary print-receipt" 
                                                data-id="<?= $transaction['id'] ?>"
                                                data-type="<?= $transaction['type'] ?>">
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
</div>

<!-- Add Savings Modal -->
<div class="modal fade" id="addSavingsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #51087E;">
                <h5 class="modal-title text-white">Add Savings</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addSavingsForm">
                <div class="modal-body">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <div class="form-group">
                        <label>Receipt Number</label>
                        <input type="text" name="receipt_no" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Select Member</label>
                        <select name="account_id" class="form-control member-select" required>
                            <option value="">Select member...</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?= $member['account_id'] ?>">
                                    <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" name="amount" class="form-control" required step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Payment Mode</label>
                        <select name="payment_mode" class="form-control" required>
                            <option value="Cash">Cash</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Withdraw Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #51087E;">
                <h5 class="modal-title text-white">Withdraw Savings</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="withdrawForm">
                <div class="modal-body">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <div class="form-group">
                        <label>Receipt Number</label>
                        <input type="text" name="receipt_no" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Select Member</label>
                        <select name="account_id" class="form-control member-select" required>
                            <option value="">Select member...</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?= $member['account_id'] ?>" 
                                        data-balance="<?= $member['total_savings'] ?>">
                                    <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Available Balance</label>
                        <input type="text" id="availableBalance" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Withdrawal Amount</label>
                        <input type="number" name="amount" class="form-control" required step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Payment Mode</label>
                        <select name="payment_mode" class="form-control" required>
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
    // Initialize DataTables for Savings
    $('#savingsTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'desc']],
        language: {
            search: "Search transactions:",
            lengthMenu: "Show _MENU_ transactions per page",
            info: "Showing _START_ to _END_ of _TOTAL_ transactions"
        }
    });

    // Initialize Select2 when modals are shown
    $('#addSavingsModal, #withdrawModal').on('shown.bs.modal', function() {
        // Initialize Select2 for member selection with search in this modal
        $(this).find('.member-select').select2({
            placeholder: 'Search and select member',
            width: '100%',
            allowClear: true,
            dropdownParent: $(this), // This ensures dropdown appears within modal
            matcher: function(params, data) {
                // If there are no search terms, return all data
                if ($.trim(params.term) === '') {
                    return data;
                }

                // Skip if there is no 'children' property
                if (typeof data.children === 'undefined') {
                    // Check if the text contains the term
                    if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                        return data;
                    }
                }

                // Return `null` if the term should not be displayed
                return null;
            }
        });
    });

    // Destroy Select2 when modals are hidden to prevent conflicts
    $('#addSavingsModal, #withdrawModal').on('hidden.bs.modal', function() {
        $(this).find('.member-select').select2('destroy');
    });

    // Auto-fill balance when member is selected in withdrawal form
    $('#withdrawModal').on('change', 'select[name="account_id"]', function() {
        var selectedOption = $(this).find('option:selected');
        var balance = selectedOption.data('balance') || 0;
        $('#availableBalance').val('KSh ' + balance.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
    });

    // Print Receipt functionality
    $(document).on('click', '.print-receipt', function() {
        var id = $(this).data('id');
        var type = $(this).data('type');
        $.ajax({
            url: '../controllers/groupController.php',
            type: 'POST',
            data: {
                action: 'getReceiptDetails',
                id: id,
                type: type
            },
            success: function(response) {
                try {
                    var data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.status === 'success') {
                        var receiptWindow = window.open('', '_blank');
                        var receiptContent = generateReceiptHTML(data.data, type);
                        receiptWindow.document.write(receiptContent);
                        receiptWindow.document.close();
                        setTimeout(function() {
                            receiptWindow.print();
                        }, 500);
                    } else {
                        showMessage('Error: ' + data.message, 'error');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    showMessage('Error generating receipt', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showMessage('Error generating receipt', 'error');
            }
        });
    });

    // Receipt number validation
    function validateReceiptNumber(input) {
        const value = input.val().trim();
        const pattern = /^[A-Za-z0-9\-_.]+$/;
        
        if (value && !pattern.test(value)) {
            input.addClass('is-invalid');
            if (!input.next('.invalid-feedback').length) {
                input.after('<div class="invalid-feedback">Use only letters, numbers, hyphens, underscores, and dots</div>');
            }
            return false;
        } else {
            input.removeClass('is-invalid');
            input.next('.invalid-feedback').remove();
            return true;
        }
    }

    // Apply validation on input
    $(document).on('input', '#addSavingsForm input[name="receipt_no"], #withdrawForm input[name="receipt_no"]', function() {
        validateReceiptNumber($(this));
    });

    // Validate before form submission
    $(document).on('submit', '#addSavingsForm, #withdrawForm', function(e) {
        const receiptInput = $(this).find('input[name="receipt_no"]');
        if (!validateReceiptNumber(receiptInput)) {
            e.preventDefault();
            showMessage('Please enter a valid receipt number (letters, numbers, hyphens, underscores, and dots only)', 'error');
            return false;
        }
    });

    // Add Savings Form Submit
    $(document).on('submit', '#addSavingsForm', function(e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&action=addSavings';
        $.ajax({
            url: '../controllers/groupController.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showMessage('Savings added successfully', 'success');
                    $('#addSavingsModal').modal('hide');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                showMessage('Error adding savings', 'error');
            }
        });
    });

    // Withdraw Form Submit
    $(document).on('submit', '#withdrawForm', function(e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&action=withdraw';
        $.ajax({
            url: '../controllers/groupController.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showMessage('Withdrawal processed successfully', 'success');
                    $('#withdrawModal').modal('hide');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                showMessage('Error processing withdrawal', 'error');
            }
        });
    });

    // Form validation for savings and withdrawals
    $(document).on('submit', 'form', function(e) {
        var amount = parseFloat($(this).find('input[name="amount"]').val());
        
        if ($(this).attr('id') === 'withdrawForm') {
            var availableBalanceText = $('#availableBalance').val();
            var availableBalance = parseFloat(availableBalanceText.replace('KSh ', '').replace(/,/g, ''));
            
            if (amount > availableBalance) {
                e.preventDefault();
                showMessage('Withdrawal amount cannot exceed available balance', 'error');
                return false;
            }
        }

        if (amount <= 0) {
            e.preventDefault();
            showMessage('Amount must be greater than zero', 'error');
            return false;
        }
    });

    // Generate Receipt HTML
    function generateReceiptHTML(data, type) {
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <title>${type} Receipt</title>
                <style>
                    body { 
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        padding: 20px;
                    }
                    .receipt {
                        max-width: 800px;
                        margin: 0 auto;
                        padding: 20px;
                        border: 1px solid #ddd;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                        border-bottom: 2px solid #333;
                        padding-bottom: 10px;
                    }
                    
                    .details {
                        margin-bottom: 30px;
                    }
                    .details p {
                        margin: 10px 0;
                        border-bottom: 1px solid #eee;
                        padding-bottom: 5px;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 30px;
                        border-top: 2px solid #333;
                        padding-top: 10px;
                    }
                    @media print {
                        body { print-color-adjust: exact; }
                    }
                </style>
            </head>
            <body>
                <div class="receipt">
                    <div class="header">
                        <h2>Lato Sacco LTD</h2>
                        <h3>${type} Receipt</h3>
                    </div>
                    <div class="details">
                        <p><strong>Receipt No:</strong> ${data.receipt_no || 'N/A'}</p>
                        <p><strong>Date:</strong> ${new Date(data.date).toLocaleString()}</p>
                        <p><strong>Group Name:</strong> ${data.group_name}</p>
                        <p><strong>Member Name:</strong> ${data.member_name}</p>
                        <p><strong>Amount:</strong> KSh ${parseFloat(data.amount).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}</p>
                        <p><strong>Payment Mode:</strong> ${data.payment_mode}</p>
                        <p><strong>Served By:</strong> ${data.served_by_name}</p>
                    </div>
                    <div class="footer">
                        <p>Thank you for your transaction!</p>
                        <p>Printed on: ${new Date().toLocaleString()}</p>
                    </div>
                </div>
            </body>
            </html>
        `;
    }
});
</script>