<?php
// Modals Section Component
// This component contains all modals for the manage expenses page
?>

<style>
/* Toast Notifications */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.toast {
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    margin-bottom: 10px;
    min-width: 300px;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
}

.toast.show {
    opacity: 1;
    transform: translateX(0);
}

.toast.success {
    border-left: 4px solid #28a745;
}

.toast.error {
    border-left: 4px solid #dc3545;
}

.toast-header {
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: between;
    align-items: center;
    background: #f8f9fa;
    border-radius: 5px 5px 0 0;
}

.toast-body {
    padding: 10px 15px;
    color: #495057;
}

.toast-close {
    background: none;
    border: none;
    font-size: 18px;
    color: #6c757d;
    cursor: pointer;
    padding: 0;
    margin-left: 10px;
}

.toast-close:hover {
    color: #343a40;
}

/* Enhanced Select Styles */
.enhanced-select {
    position: relative;
}

.enhanced-select select {
    width: 100%;
    padding: 8px 30px 8px 12px;
    border: 1px solid #d1d3e2;
    border-radius: 4px;
    background-color: white;
    font-size: 14px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 8px center;
    background-repeat: no-repeat;
    background-size: 16px;
}

.enhanced-select select:focus {
    outline: none;
    border-color: #4e73df;
    box-shadow: 0 0 0 2px rgba(78, 115, 223, 0.2);
}

.enhanced-select .dropdown-container {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #d1d3e2;
    border-top: none;
    border-radius: 0 0 4px 4px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    display: none;
    max-height: 250px;
    overflow: hidden;
}

.enhanced-select .dropdown-container.show {
    display: block;
}

.enhanced-select .search-input {
    width: 100%;
    padding: 8px 12px;
    border: none;
    border-bottom: 1px solid #e3e6f0;
    outline: none;
    font-size: 14px;
}

.enhanced-select .search-input:focus {
    border-bottom-color: #4e73df;
}

.enhanced-select .options-container {
    max-height: 200px;
    overflow-y: auto;
}

.enhanced-select .option-item {
    padding: 8px 12px;
    cursor: pointer;
    font-size: 14px;
    border-bottom: 1px solid #f8f9fa;
}

.enhanced-select .option-item:hover {
    background-color: #f8f9fc;
}

.enhanced-select .option-item.selected {
    background-color: #4e73df;
    color: white;
}
</style>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="../controllers/cashier_save_expense.php" id="expenseForm">
            <div class="modal-content">
                <div style="background-color: #51087E;" class="modal-header">
                    <h5 class="modal-title text-white">Add New Expense</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category <span class="text-danger">*</span></label>
                                <div class="enhanced-select">
                                    <select name="main_category" class="form-control" required>
                                        <option value="">Select Category</option>
                                        <?php 
                                        $categories_query = "SELECT DISTINCT category FROM expenses_categories ORDER BY category";
                                        $categories_result = $db->conn->query($categories_query);
                                        while($cat = $categories_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                                <?php echo htmlspecialchars($cat['category']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="dropdown-container">
                                        <input type="text" class="search-input" placeholder="Type to search categories...">
                                        <div class="options-container">
                                            <!-- Options will be populated by JavaScript -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Expense Name <span class="text-danger">*</span></label>
                                <div class="enhanced-select">
                                    <select name="category" class="form-control" required>
                                        <option value="">Select Expense Name</option>
                                        <?php 
                                        $names_query = "SELECT DISTINCT name FROM expenses_categories ORDER BY name";
                                        $names_result = $db->conn->query($names_query);
                                        while($name = $names_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo htmlspecialchars($name['name']); ?>">
                                                <?php echo htmlspecialchars($name['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="dropdown-container">
                                        <input type="text" class="search-input" placeholder="Type to search expense names...">
                                        <div class="options-container">
                                            <!-- Options will be populated by JavaScript -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Amount (KSh) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control" required step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-control" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Mobile Money">Mobile Money</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <input type="text" name="status" value="completed" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Receipt No. <span class="text-danger">*</span></label>
                        <input type="text" name="receipt_no" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Enter expense description"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Enter additional remarks if any"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="save_expense" class="btn btn-warning">Save Expense</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Money Received Modal -->
<div class="modal fade" id="addReceivedModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="../controllers/cashier_save_expense.php" id="receivedForm">
            <div class="modal-content">
                <div style="background-color: #28a745;" class="modal-header">
                    <h5 class="modal-title text-white">Add Money Received</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category <span class="text-danger">*</span></label>
                                <div class="enhanced-select">
                                    <select name="main_category" class="form-control" required>
                                        <option value="">Select Category</option>
                                        <?php 
                                        $categories_result->data_seek(0); // Reset pointer
                                        while($cat = $categories_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                                <?php echo htmlspecialchars($cat['category']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="dropdown-container">
                                        <input type="text" class="search-input" placeholder="Type to search categories...">
                                        <div class="options-container">
                                            <!-- Options will be populated by JavaScript -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Income Source <span class="text-danger">*</span></label>
                                <div class="enhanced-select">
                                    <select name="category" class="form-control" required>
                                        <option value="">Select Income Source</option>
                                        <?php 
                                        $names_result->data_seek(0); // Reset pointer
                                        while($name = $names_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo htmlspecialchars($name['name']); ?>">
                                                <?php echo htmlspecialchars($name['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="dropdown-container">
                                        <input type="text" class="search-input" placeholder="Type to search income sources...">
                                        <div class="options-container">
                                            <!-- Options will be populated by JavaScript -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Amount (KSh) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control" required step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-control" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Mobile Money">Mobile Money</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <input type="text" name="status" value="received" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Receipt No. <span class="text-danger">*</span></label>
                        <input type="text" name="receipt_no" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Enter description"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Enter additional remarks if any"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="save_expense" class="btn btn-success">Save Transaction</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Expense Receipt</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="receiptContent" class="receipt"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" onclick="printReceipt()">Print</button>
            </div>
        </div>
    </div>
</div>

<!-- Statement Print Template (hidden) -->
<div id="statementTemplate" style="display: none;">
    <div class="statement-header">
        <h2>LATO SACCO LTD</h2>
        <h3>Transaction Statement</h3>
        <p>Period: <span id="statementPeriod"></span></p>
    </div>
    <div class="statement-summary">
        <h4>Summary</h4>
        <table class="summary-table">
            <tr>
                <td>Total Expenses:</td>
                <td>KSh <?php echo number_format($total_expenses, 2); ?></td>
            </tr>
            <tr>
                <td>Total Received:</td>
                <td>KSh <?php echo number_format($total_received, 2); ?></td>
            </tr>
            <tr>
                <td>Net Balance:</td>
                <td>KSh <?php echo number_format($total_received - $total_expenses, 2); ?></td>
            </tr>
        </table>
        
        <h4>Category Breakdown</h4>
        <table class="category-table">
            <?php foreach ($category_totals as $category => $total): ?>
            <tr>
                <td><?php echo htmlspecialchars($category); ?>:</td>
                <td>KSh <?php echo number_format($total, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <div class="statement-details">
        <h4>Transaction Details</h4>
        <table class="transaction-table">
            <!-- Transaction details will be populated via JavaScript -->
        </table>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">Confirm Deletion</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="text-white">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete transaction with receipt no: <strong id="deleteReceiptNo"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <button class="btn btn-danger" type="button" id="confirmDeleteBtn">Delete Transaction</button>
            </div>
        </div>
    </div>
</div>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger" href="../views/logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Success Message Modal -->
<?php if (isset($_SESSION['success_msg'])): ?>
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white">Success</h5>
                <button class="close" type="button" data-dismiss="modal">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <?php 
                    echo $_SESSION['success_msg'];
                    unset($_SESSION['success_msg']);
                ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Error Message Modal -->
<?php if (isset($_SESSION['error_msg'])): ?>
<div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">Error</h5>
                <button class="close" type="button" data-dismiss="modal">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <?php 
                    echo $_SESSION['error_msg'];
                    unset($_SESSION['error_msg']);
                ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>