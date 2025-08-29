<?php
// components/business_groups/transactions.php
?>

<!-- Transactions Section -->
<div id="transactions-section" class="content-section">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="m-0">Transactions History</h5>
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
                <!-- Statement Generator -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="statementForm" class="row align-items-end">
                            <div class="col-md-4">
                                <label>From Date</label>
                                <input type="date" name="from_date" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label>To Date</label>
                                <input type="date" name="to_date" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-warning" id="printStatement">
                                    <i class="fas fa-print"></i> Print Statement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Receipt No</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?= date("Y-m-d H:i", strtotime($transaction['date'])) ?></td>
                                    <td>
                                        <span class="badge badge-<?= 
                                            $transaction['type'] === 'Savings' ? 'success' : 
                                            ($transaction['type'] === 'Withdrawal' ? 'warning' : 'info') 
                                        ?>">
                                            <?= htmlspecialchars($transaction['type']) ?>
                                        </span>
                                    </td>
                                    <td>KSh <?= number_format($transaction['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($transaction['description']) ?></td>
                                    <td><?= htmlspecialchars($transaction['receipt_no']) ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm print-receipt" 
                                                data-id="<?= $transaction['transaction_id'] ?>"
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
            <form id="addSavingsForm" method="post" action="#">
                <div class="modal-body">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <div class="form-group">
                        <label>Receipt Number</label>
                        <input type="text" name="receipt_no" class="form-control" required>
                        <small class="text-muted">This must be unique</small>
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
                <h5 class="modal-title text-white">Withdraw Funds</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="withdrawForm" method="post" action="#">
                <div class="modal-body">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <div class="form-group">
                        <label>Receipt Number</label>
                        <input type="text" name="receipt_no" class="form-control" required>
                        <small class="text-muted">This must be unique</small>
                    </div>
                    <div class="form-group">
                        <label>Available Balance</label>
                        <input type="text" id="availableBalance" class="form-control" readonly 
                            value="KSh <?= number_format($netBalance, 2) ?>">
                    </div>
                    <div class="form-group">
                        <label>Withdrawal Amount</label>
                        <input type="number" name="amount" class="form-control" required step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Withdrawal Fee</label>
                        <input type="number" name="withdrawal_fee" class="form-control" required step="0.01" min="0">
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