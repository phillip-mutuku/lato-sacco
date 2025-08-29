<?php
// components/reconciliation/float_management.php
?>

<style>
.float-card {
    background: linear-gradient(135deg, #fff 0%, #f8f9fc 100%);
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: rgb(204, 219, 232) 3px 3px 6px 0px inset, rgba(255, 255, 255, 0.5) -3px -3px 6px 1px inset;
    transition: transform 0.3s ease;
}

.float-card:hover {
    transform: translateY(-5px);
}

.float-title {
    color: #51087E;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 15px;
}

.float-amount {
    font-size: 28px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 10px;
}

.action-buttons {
    display: flex;
    gap: 15px;
    margin: 25px 0;
}

.action-buttons button {
    padding: 10px 20px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.receipt {
    max-width: 400px;
    margin: 0 auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #fff;
}

.receipt-header {
    text-align: center;
    margin-bottom: 20px;
}

.receipt-details {
    margin-bottom: 20px;
}

.receipt-footer {
    text-align: center;
    font-size: 14px;
    color: #666;
}
</style>

<!-- Float Management Section -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold" style="color: #51087E;">Float Management</h6>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="float-card">
                    <div class="float-title">Opening Float</div>
                    <div class="float-amount">KSh <?= number_format($opening_float, 2) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="float-card">
                    <div class="float-title">Total Offloaded</div>
                    <div class="float-amount">KSh <?= number_format($total_offloaded, 2) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="float-card">
                    <div class="float-title">Closing Float</div>
                    <div class="float-amount" id="closingFloatAmount">KSh <?= number_format($closing_float, 2) ?></div>
                    <button class="btn btn-warning mt-3 w-100" onclick="calculateClosingFloat()">
                        <i class="fas fa-calculator"></i> Calculate
                    </button>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-success" data-toggle="modal" data-target="#addFloatModal">
                <i class="fas fa-plus"></i> Add Float
            </button>
            <button class="btn btn-danger" data-toggle="modal" data-target="#offloadFloatModal">
                <i class="fas fa-minus"></i> Offload Float
            </button>
        </div>
    </div>
</div>

<!-- Add Float Modal -->
<div class="modal fade" id="addFloatModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #51087E;">
                <h5 class="modal-title text-white">Add Float</h5>
                <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form method="POST" id="addFloatForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Receipt Number</label>
                        <input type="text" name="receipt_no" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">KSh</span>
                            </div>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_float" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Add Float
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Offload Float Modal -->
<div class="modal fade" id="offloadFloatModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #51087E;">
                <h5 class="modal-title text-white">Offload Float</h5>
                <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form method="POST" id="offloadFloatForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Receipt Number</label>
                        <input type="text" name="receipt_no" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">KSh</span>
                            </div>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="offload_float" class="btn btn-danger">
                        <i class="fas fa-minus-circle"></i> Offload Float
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Receipt Print Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #51087E;">
                <h5 class="modal-title text-white">Transaction Receipt</h5>
                <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="receipt" id="receiptContent">
                    <div class="receipt-header">
                        <img src="../public/image/mylogo.png" alt="Lato Management System Logo" style="width: 150px;">
                        <h4 class="mt-3">Transaction Receipt</h4>
                        <p class="text-muted">Lato Sacco LTD</p>
                    </div>
                    <div class="receipt-details">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Receipt No:</strong></td>
                                <td id="receiptNo"></td>
                            </tr>
                            <tr>
                                <td><strong>Amount:</strong></td>
                                <td id="receiptAmount"></td>
                            </tr>
                            <tr>
                                <td><strong>Transaction Type:</strong></td>
                                <td id="receiptType"></td>
                            </tr>
                            <tr>
                                <td><strong>Date:</strong></td>
                                <td id="receiptDate"></td>
                            </tr>
                            <tr>
                                <td><strong>Served By:</strong></td>
                                <td id="receiptServedBy"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="receipt-footer">
                        <hr>
                        <p class="mb-1">Thank you for choosing Lato Sacco LTD</p>
                        <p class="small text-muted">This is a computer generated receipt</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="printReceipt()">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function calculateClosingFloat() {
    const openingFloat = parseFloat('<?= $opening_float ?>');
    const totalOffloaded = parseFloat('<?= $total_offloaded ?>');
    const closingFloat = openingFloat - totalOffloaded;
    
    $('#closingFloatAmount').text('KSh ' + closingFloat.toLocaleString('en-US', {minimumFractionDigits: 2}));
    
    // Animate the calculation
    $('#closingFloatAmount').fadeOut(200).fadeIn(200);
}

function printReceipt() {
    const printContents = document.getElementById('receiptContent').innerHTML;
    const originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    
    // Reinitialize event handlers
    $(document).ready();
}

function showLoadingSpinner() {
    $('body').append(`
        <div class="loading-overlay">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
    `);
}

// Handle float form submissions
$(document).ready(function() {
    $('#addFloatForm, #offloadFloatForm').on('submit', function() {
        showLoadingSpinner();
    });
});
</script>