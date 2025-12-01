<?php
// Transactions Table Component
// This component displays the recent transactions table with enhanced columns
// Now properly respects all filters including category filter
?>

<!-- Manage expenses and income begins -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Expenses & Income</h1>
</div>

<div class="mb-2 d-flex justify-content-between">
    <button class="btn btn-lg btn-warning" href="#" data-toggle="modal" data-target="#addExpenseModal">
        <span class="fa fa-minus-circle"></span> Add New Expense
    </button>
    <button class="btn btn-lg btn-success" href="#" data-toggle="modal" data-target="#addReceivedModal">
        <span class="fa fa-plus-circle"></span> Add Money Received
    </button>
</div>

<div class="card mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 style="color: #51087E;" class="m-0 font-weight-bold">Recent Transactions</h6>
        <?php if ($transactions && $transactions->num_rows > 0): ?>
            <span class="badge badge-info badge-pill" style="font-size: 14px;">
                <?php echo $transactions->num_rows; ?> transaction(s) found
            </span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php 
        // Display active filters information
        if ($transaction_type !== 'all' || $date_range !== 'all' || $category_filter !== 'all'): 
        ?>
        <div class="alert alert-info mb-3" role="alert">
            <i class="fas fa-info-circle"></i> <strong>Active Filters:</strong>
            <?php 
            $active_filters = [];
            
            if ($transaction_type !== 'all') {
                $active_filters[] = '<strong>Type:</strong> ' . ucfirst($transaction_type);
            }
            
            if ($category_filter !== 'all') {
                $active_filters[] = '<strong>Category:</strong> ' . htmlspecialchars($category_filter);
            }
            
            if ($date_range !== 'all') {
                if ($date_range === 'custom' && $start_date && $end_date) {
                    $active_filters[] = '<strong>Date:</strong> ' . date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date));
                } else {
                    $active_filters[] = '<strong>Date:</strong> ' . ucfirst($date_range);
                }
            }
            
            echo implode(' | ', $active_filters);
            ?>
        </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="transactionTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Receipt No</th>
                        <th>Category</th>
                        <th>Expense Name</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Created By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($transactions && $transactions->num_rows > 0):
                        // Reset the pointer to the beginning since we already processed the data
                        $transactions->data_seek(0);
                        
                        while($transaction = $transactions->fetch_assoc()): 
                    ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i', strtotime($transaction['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['receipt_no']); ?></td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            <?php echo htmlspecialchars($transaction['main_category'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['category']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['description'] ?? '-'); ?></td>
                                    <td class="<?php echo $transaction['status'] === 'received' ? 'text-success' : 'text-danger'; ?>">
                                        <strong>
                                            <?php echo $transaction['status'] === 'received' ? '+' : '-'; ?>
                                            KSh <?php echo number_format(abs($transaction['amount']), 2); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-light">
                                            <?php echo htmlspecialchars($transaction['payment_method'] ?? 'Cash'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            if ($transaction['status'] == 'received') {
                                                echo 'success';
                                            } elseif ($transaction['status'] == 'completed') {
                                                echo 'danger';
                                            } else {
                                                echo 'warning';
                                            }
                                        ?>">
                                            <?php 
                                            if ($transaction['status'] == 'received') {
                                                echo 'Received';
                                            } elseif ($transaction['status'] == 'completed') {
                                                echo 'Expense';
                                            } else {
                                                echo ucfirst($transaction['status']);
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['remarks'] ?? '-'); ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($transaction['created_by_name'] ?? 'Unknown'); ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-warning print-receipt" 
                                                    data-transaction='<?php echo htmlspecialchars(json_encode($transaction), ENT_QUOTES, 'UTF-8'); ?>'
                                                    title="Print Receipt">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-transaction" 
                                                    data-receipt-no="<?php echo htmlspecialchars($transaction['receipt_no']); ?>"
                                                    data-id="<?php echo htmlspecialchars($transaction['id']); ?>"
                                                    title="Delete Transaction">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="11" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No transactions found</h5>
                                <?php if ($transaction_type !== 'all' || $date_range !== 'all' || $category_filter !== 'all'): ?>
                                    <p class="text-muted">Try adjusting your filters or <a href="manage_expenses.php">clear all filters</a></p>
                                <?php else: ?>
                                    <p class="text-muted">Get started by adding your first transaction using the buttons above</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Transaction Statistics Summary (shows at bottom) -->
<?php if ($transactions && $transactions->num_rows > 0): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h6 class="font-weight-bold text-primary mb-3">
                    <i class="fas fa-chart-line"></i> Quick Statistics for Current View
                </h6>
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-box text-center p-3" style="background-color: #f8f9fc; border-radius: 5px;">
                            <small class="text-muted d-block mb-1">Transaction Count</small>
                            <h4 class="mb-0"><?php echo $transactions->num_rows; ?></h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box text-center p-3" style="background-color: #fff5f5; border-radius: 5px;">
                            <small class="text-muted d-block mb-1">Total Expenses</small>
                            <h4 class="text-danger mb-0">KSh <?php echo number_format($total_expenses, 2); ?></h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box text-center p-3" style="background-color: #f0fdf4; border-radius: 5px;">
                            <small class="text-muted d-block mb-1">Total Received</small>
                            <h4 class="text-success mb-0">KSh <?php echo number_format($total_received, 2); ?></h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box text-center p-3" style="background-color: <?php echo ($total_received - $total_expenses) >= 0 ? '#f0fdf4' : '#fff5f5'; ?>; border-radius: 5px;">
                            <small class="text-muted d-block mb-1">Net Balance</small>
                            <h4 class="<?php echo ($total_received - $total_expenses) >= 0 ? 'text-success' : 'text-danger'; ?> mb-0">
                                KSh <?php echo number_format($total_received - $total_expenses, 2); ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>