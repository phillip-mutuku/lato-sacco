<?php
// components/reconciliation/float_transactions.php
?>

<style>
.float-filter-section {
    background-color: #f8f9fc;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.table thead th {
    background-color: #f8f9fc;
    color: #51087E;
    font-weight: 600;
    border-bottom: 2px solid #51087E;
}

.total-row {
    background-color: #e9ecef;
    font-weight: bold;
    color: #51087E;
}

.total-row td {
    border-top: 2px solid #51087E;
}

.btn-print {
    padding: 5px 10px;
    font-size: 0.8rem;
}

.filter-form .form-control {
    border-radius: 6px;
    border: 1px solid #d1d3e2;
    transition: all 0.3s ease;
}

.filter-form .form-control:focus {
    border-color: #51087E;
    box-shadow: 0 0 0 0.2rem rgba(81, 8, 126, 0.25);
}

.filter-info {
    background-color: #e7f3ff;
    border-left: 4px solid #51087E;
    padding: 10px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.filter-info small {
    color: #51087E;
    font-weight: 500;
}
</style>

<!-- Float Transactions Section -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold" style="color: #51087E;">Float Transactions</h6>
    </div>
    <div class="card-body">
        <!-- Filter Section -->
        <div class="float-filter-section">
            <form method="GET" id="floatFilterForm" class="filter-form">
                <!-- Preserve other GET parameters -->
                <?php if (isset($_GET['start_date'])): ?>
                    <input type="hidden" name="start_date" value="<?= htmlspecialchars($_GET['start_date']) ?>">
                <?php endif; ?>
                <?php if (isset($_GET['end_date'])): ?>
                    <input type="hidden" name="end_date" value="<?= htmlspecialchars($_GET['end_date']) ?>">
                <?php endif; ?>
                
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="floatType" class="font-weight-bold">Float Type</label>
                        <select name="float_type" id="floatType" class="form-control">
                            <option value="all" <?= isset($_GET['float_type']) && $_GET['float_type'] == 'all' ? 'selected' : '' ?>>All Types</option>
                            <option value="add" <?= isset($_GET['float_type']) && $_GET['float_type'] == 'add' ? 'selected' : '' ?>>Add Float</option>
                            <option value="offload" <?= isset($_GET['float_type']) && $_GET['float_type'] == 'offload' ? 'selected' : '' ?>>Offload Float</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="floatStartDate" class="font-weight-bold">Start Date</label>
                        <input type="date" name="float_start_date" id="floatStartDate" class="form-control" 
                               value="<?= isset($_GET['float_start_date']) ? $_GET['float_start_date'] : date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="floatEndDate" class="font-weight-bold">End Date</label>
                        <input type="date" name="float_end_date" id="floatEndDate" class="form-control"
                               value="<?= isset($_GET['float_end_date']) ? $_GET['float_end_date'] : date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-block" style="background-color: #51087E; border-color: #51087E;">
                            <i class="fas fa-filter"></i> Apply Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Current Filter Information -->
        <?php if (isset($_GET['float_start_date']) || isset($_GET['float_end_date']) || (isset($_GET['float_type']) && $_GET['float_type'] != 'all')): ?>
        <div class="filter-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small>
                        <i class="fas fa-filter"></i> 
                        Current Filter: 
                        <?php if (isset($_GET['float_start_date']) && isset($_GET['float_end_date'])): ?>
                            <strong><?= date('M d, Y', strtotime($_GET['float_start_date'])) ?> - <?= date('M d, Y', strtotime($_GET['float_end_date'])) ?></strong>
                        <?php endif; ?>
                        <?php if (isset($_GET['float_type']) && $_GET['float_type'] != 'all'): ?>
                            | Type: <strong><?= ucfirst($_GET['float_type']) ?> Float</strong>
                        <?php endif; ?>
                        | Total Transactions: <strong><?= $float_transactions ? $float_transactions->num_rows : 0 ?></strong>
                    </small>
                </div>
                <a href="<?= $_SERVER['PHP_SELF'] ?><?= isset($_GET['start_date']) || isset($_GET['end_date']) ? '?start_date=' . ($_GET['start_date'] ?? '') . '&end_date=' . ($_GET['end_date'] ?? '') : '' ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i> Clear Filter
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Transactions Table -->
        <div class="table-responsive">
            <table class="table table-bordered" id="floatTransactionsTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Receipt No</th>
                        <th>Transaction Type</th>
                        <th>Amount (KSh)</th>
                        <th>Served By</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="floatTransactionsBody">
                    <?php 
                    $total_added_display = 0;
                    $total_offloaded_display = 0;
                    if ($float_transactions && $float_transactions->num_rows > 0): 
                        $float_transactions->data_seek(0); // Reset pointer
                        while ($transaction = $float_transactions->fetch_assoc()): 
                            if ($transaction['type'] == 'add') {
                                $total_added_display += $transaction['amount'];
                            } else {
                                $total_offloaded_display += $transaction['amount'];
                            }
                    ?>
                    <tr data-type="<?= $transaction['type'] ?>" data-date="<?= date('Y-m-d', strtotime($transaction['date_created'])) ?>">
                        <td><?= date('M d, Y', strtotime($transaction['date_created'])) ?></td>
                        <td><?= htmlspecialchars($transaction['receipt_no']) ?></td>
                        <td>
                            <span class="badge badge-<?= $transaction['type'] == 'add' ? 'success' : 'danger' ?>">
                                <?= ucfirst($transaction['type']) ?> Float
                            </span>
                        </td>
                        <td><?= number_format($transaction['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($transaction['served_by']) ?></td>
                        <td><?= date('H:i', strtotime($transaction['date_created'])) ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm print-receipt" 
                                    data-receipt="<?= htmlspecialchars($transaction['receipt_no']) ?>"
                                    data-amount="<?= $transaction['amount'] ?>"
                                    data-type="<?= ucfirst($transaction['type']) ?> Float"
                                    data-date="<?= $transaction['date_created'] ?>"
                                    data-served="<?= htmlspecialchars($transaction['served_by']) ?>"
                                    title="Print Receipt">
                                <i class="fas fa-print"></i>
                            </button>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No float transactions found for the selected criteria</p>
                        </td>
                    </tr>
                    <?php 
                    endif; 
                    ?>
                </tbody>
                <?php if ($float_transactions && $float_transactions->num_rows > 0): ?>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3"><strong>TOTALS</strong></td>
                        <td><strong id="tableTotalAmount"><?= number_format($total_added_display + $total_offloaded_display, 2) ?></strong></td>
                        <td colspan="3">
                            <small class="text-muted">
                                Added: <?= number_format($total_added_display, 2) ?> | 
                                Offloaded: <?= number_format($total_offloaded_display, 2) ?>
                            </small>
                        </td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- Receipt Print Modal -->
<div class="modal fade" id="floatReceiptModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #51087E;">
                <h5 class="modal-title text-white">Transaction Receipt</h5>
                <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="receipt" id="floatReceiptContent">
                    <div class="receipt-header text-center">
                        <h4 class="mt-3">LATO SACCO LTD</h4>
                        <h5>Float Transaction Receipt</h5>
                        <hr>
                    </div>
                    <div class="receipt-details">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Receipt No:</strong></td>
                                <td id="floatReceiptNo"></td>
                            </tr>
                            <tr>
                                <td><strong>Amount:</strong></td>
                                <td id="floatReceiptAmount"></td>
                            </tr>
                            <tr>
                                <td><strong>Transaction Type:</strong></td>
                                <td id="floatReceiptType"></td>
                            </tr>
                            <tr>
                                <td><strong>Date & Time:</strong></td>
                                <td id="floatReceiptDate"></td>
                            </tr>
                            <tr>
                                <td><strong>Served By:</strong></td>
                                <td id="floatReceiptServedBy"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="receipt-footer text-center">
                        <hr>
                        <p class="mb-1">Thank you for choosing Lato Sacco LTD</p>
                        <p class="small text-muted">This is a computer generated receipt</p>
                        <p class="small text-muted">Printed on: <span id="printDate"></span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="printFloatReceipt()" style="background-color: #51087E; border-color: #51087E;">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>