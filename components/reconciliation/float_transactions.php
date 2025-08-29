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
                    foreach ($float_transactions as $transaction): 
                        if ($transaction['type'] == 'add') {
                            $total_added_display += $transaction['amount'];
                        } else {
                            $total_offloaded_display += $transaction['amount'];
                        }
                    ?>
                    <tr data-type="<?= $transaction['type'] ?>" data-date="<?= date('Y-m-d', strtotime($transaction['date_created'])) ?>">
                        <td><?= date('M d, Y', strtotime($transaction['date_created'])) ?></td>
                        <td><?= $transaction['receipt_no'] ?></td>
                        <td>
                            <span class="badge badge-<?= $transaction['type'] == 'add' ? 'success' : 'danger' ?>">
                                <?= ucfirst($transaction['type']) ?> Float
                            </span>
                        </td>
                        <td><?= number_format($transaction['amount'], 2) ?></td>
                        <td><?= $transaction['served_by'] ?></td>
                        <td><?= date('H:i', strtotime($transaction['date_created'])) ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm print-receipt" 
                                    data-receipt="<?= $transaction['receipt_no'] ?>"
                                    data-amount="<?= $transaction['amount'] ?>"
                                    data-type="<?= ucfirst($transaction['type']) ?> Float"
                                    data-date="<?= $transaction['date_created'] ?>"
                                    data-served="<?= $transaction['served_by'] ?>"
                                    title="Print Receipt">
                                <i class="fas fa-print"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
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

 <!-- Bootstrap core JavaScript-->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../public/js/jquery.easing.js"></script>

    <!-- Page level plugins -->
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../public/js/sb-admin-2.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    window.floatTable = $('#floatTransactionsTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25,
        "responsive": true,
        "footerCallback": function(row, data, start, end, display) {
            var api = this.api();
            
            // Calculate totals for visible rows
            var totalAmount = 0;
            api.column(3, { page: 'current', search: 'applied' }).data().each(function(value) {
                var numValue = parseFloat(value.toString().replace(/,/g, ''));
                if (!isNaN(numValue)) {
                    totalAmount += numValue;
                }
            });
                
            // Update footer
            $('#tableTotalAmount').html(totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2}));
        },
        "language": {
            "search": "Search transactions: ",
            "lengthMenu": "Show _MENU_ entries per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "No transactions found",
            "infoFiltered": "(filtered from _MAX_ total entries)"
        }
    });

    // Automatic filtering when dropdown changes
    $('#floatType').on('change', function() {
        applyFloatFilters();
    });

    // Automatic filtering when date changes
    $('#floatStartDate, #floatEndDate').on('change', function() {
        applyFloatFilters();
    });

    // Handle manual filter button click
    $('#floatFilterForm').on('submit', function(e) {
        e.preventDefault();
        applyFloatFilters();
    });

    // Handle receipt printing
    $(document).on('click', '.print-receipt', function(e) {
        e.preventDefault();
        const data = $(this).data();
        
        // Populate modal with data
        $('#floatReceiptNo').text(data.receipt || 'N/A');
        $('#floatReceiptAmount').text('KSh ' + parseFloat(data.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('#floatReceiptType').text(data.type || 'N/A');
        $('#floatReceiptDate').text(new Date(data.date).toLocaleString() || 'N/A');
        $('#floatReceiptServedBy').text(data.served || 'N/A');
        $('#printDate').text(new Date().toLocaleString());
        
        // Show modal
        $('#floatReceiptModal').modal('show');
    });
});

function applyFloatFilters() {
    const floatType = $('#floatType').val();
    const startDate = $('#floatStartDate').val();
    const endDate = $('#floatEndDate').val();
    
    var table = window.floatTable;
    
    // Clear existing search
    table.search('').columns().search('').draw();
    
    // Remove any existing custom filters
    $.fn.dataTable.ext.search = [];
    
    // Apply custom filtering
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'floatTransactionsTable') {
                return true;
            }
            
            // Get the actual row data
            var transactionTypeCell = data[2]; // Transaction Type column
            var dateCell = data[0]; // Date column
            
            // Extract transaction type from badge HTML
            var typeMatch = transactionTypeCell.match(/(Add|Offload)\s+Float/i);
            var rowType = typeMatch ? typeMatch[1].toLowerCase() : '';
            
            // Parse date from the date column
            var rowDate = new Date(dateCell);
            var filterStartDate = startDate ? new Date(startDate) : null;
            var filterEndDate = endDate ? new Date(endDate + 'T23:59:59') : null;
            
            // Type filter
            if (floatType !== 'all' && rowType !== floatType) {
                return false;
            }
            
            // Date filter
            if (filterStartDate && rowDate < filterStartDate) {
                return false;
            }
            
            if (filterEndDate && rowDate > filterEndDate) {
                return false;
            }
            
            return true;
        }
    );
    
    // Redraw table
    table.draw();
}

function printFloatReceipt() {
    var printContent = document.getElementById('floatReceiptContent').innerHTML;
    var originalContent = document.body.innerHTML;
    
    // Create a new window for printing
    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Float Transaction Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .receipt { max-width: 400px; margin: 0 auto; }
                .receipt-header { text-align: center; margin-bottom: 20px; }
                .receipt-details table { width: 100%; }
                .receipt-details td { padding: 5px; }
                .receipt-footer { text-align: center; margin-top: 20px; }
                hr { border: 1px solid #ccc; }
                @media print {
                    body { margin: 0; }
                    .receipt { max-width: none; }
                }
            </style>
        </head>
        <body>
            <div class="receipt">${printContent}</div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
    printWindow.close();
    
    // Close the modal
    $('#floatReceiptModal').modal('hide');
}
</script>