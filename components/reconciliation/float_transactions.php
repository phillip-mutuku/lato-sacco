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
</style>

<!-- Float Transactions Section -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold" style="color: #51087E;">Float Transactions</h6>
    </div>
    <div class="card-body">
        <!-- Filter Section -->
        <div class="float-filter-section">
            <form id="floatFilterForm" class="filter-form">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="floatType" class="font-weight-bold">Float Type</label>
                        <select name="float_type" id="floatType" class="form-control">
                            <option value="all">All Types</option>
                            <option value="add">Add Float</option>
                            <option value="offload">Offload Float</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="floatStartDate" class="font-weight-bold">Start Date</label>
                        <input type="date" name="start_date" id="floatStartDate" class="form-control" 
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="floatEndDate" class="font-weight-bold">End Date</label>
                        <input type="date" name="end_date" id="floatEndDate" class="form-control"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-block" style="background-color: #51087E; border-color: #51087E;">
                            <i class="fas fa-filter"></i> Apply Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

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
                    endif; 
                    ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3"><strong>TOTALS</strong></td>
                        <td><strong id="tableTotalAmount"><?= number_format($total_added_display + $total_offloaded_display, 2) ?></strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
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