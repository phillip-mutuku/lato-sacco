<?php
// Stats Section Component
// This component displays the filtering section and summary cards with category filtering
?>

<!-- Filtering Section -->
<div class="filter-section">
    <form id="filterForm" method="GET" class="row">
        <div class="col-md-3 mb-3">
            <label class="font-weight-bold">Transaction Type</label>
            <select name="transaction_type" class="form-control">
                <option value="all" <?php echo $transaction_type === 'all' ? 'selected' : ''; ?>>All Transactions</option>
                <option value="expenses" <?php echo $transaction_type === 'expenses' ? 'selected' : ''; ?>>Expenses Only</option>
                <option value="received" <?php echo $transaction_type === 'received' ? 'selected' : ''; ?>>Money Received Only</option>
            </select>
        </div>
        
        <div class="col-md-3 mb-3">
            <label class="font-weight-bold">Income Source / Category</label>
            <select name="category_filter" class="form-control category-filter-select">
                <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                <?php foreach ($available_categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                            <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3 mb-3">
            <label class="font-weight-bold">Date Range</label>
            <select name="date_range" class="form-control" id="dateRangeSelect">
                <option value="all" <?php echo $date_range === 'all' ? 'selected' : ''; ?>>All Time</option>
                <option value="today" <?php echo $date_range === 'today' ? 'selected' : ''; ?>>Today</option>
                <option value="week" <?php echo $date_range === 'week' ? 'selected' : ''; ?>>This Week</option>
                <option value="month" <?php echo $date_range === 'month' ? 'selected' : ''; ?>>This Month</option>
                <option value="year" <?php echo $date_range === 'year' ? 'selected' : ''; ?>>This Year</option>
                <option value="custom" <?php echo $date_range === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
            </select>
        </div>
        
        <div class="col-md-3 date-range-picker <?php echo $date_range === 'custom' ? 'active' : ''; ?>">
            <div class="row">
                <div class="col-6">
                    <label class="font-weight-bold">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-6">
                    <label class="font-weight-bold">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
            </div>
        </div>
        
        <div class="col-md-12 mt-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <button type="submit" class="btn btn-warning mr-2">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                    <button type="button" class="btn btn-success" onclick="generateReport()">
                        <i class="fas fa-download"></i> Generate Report
                    </button>
                    <?php if ($transaction_type !== 'all' || $date_range !== 'all' || $category_filter !== 'all'): ?>
                        <a href="manage_expenses.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($category_filter !== 'all'): ?>
                        <span class="filter-info-badge">
                            <i class="fas fa-filter"></i> Filtered by: <?php echo htmlspecialchars($category_filter); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="summary-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="font-weight-bold text-primary mb-2">Total Expenses</h6>
                    <h4 class="text-danger mb-0">KSh <?php echo number_format($total_expenses, 2); ?></h4>
                </div>
                <div>
                    <i class="fas fa-minus-circle fa-2x text-danger" style="opacity: 0.3;"></i>
                </div>
            </div>
            <?php if ($category_filter !== 'all'): ?>
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> For category: <?php echo htmlspecialchars($category_filter); ?>
                </small>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="summary-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="font-weight-bold text-primary mb-2">Total Received</h6>
                    <h4 class="text-success mb-0">KSh <?php echo number_format($total_received, 2); ?></h4>
                </div>
                <div>
                    <i class="fas fa-plus-circle fa-2x text-success" style="opacity: 0.3;"></i>
                </div>
            </div>
            <?php if ($category_filter !== 'all'): ?>
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> For category: <?php echo htmlspecialchars($category_filter); ?>
                </small>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="summary-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="font-weight-bold text-primary mb-2">Net Balance</h6>
                    <h4 class="<?php echo ($total_received - $total_expenses) >= 0 ? 'text-success' : 'text-danger'; ?> mb-0">
                        KSh <?php echo number_format($total_received - $total_expenses, 2); ?>
                    </h4>
                </div>
                <div>
                    <i class="fas fa-balance-scale fa-2x <?php echo ($total_received - $total_expenses) >= 0 ? 'text-success' : 'text-danger'; ?>" style="opacity: 0.3;"></i>
                </div>
            </div>
            <?php if ($category_filter !== 'all'): ?>
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> For category: <?php echo htmlspecialchars($category_filter); ?>
                </small>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Category Breakdown Card (shows when a specific category is selected) -->
<?php if ($category_filter !== 'all' && !empty($category_totals)): ?>
<div class="card mb-4">
    <div class="card-header py-3">
        <h6 style="color: #51087E;" class="m-0 font-weight-bold">
            Category Details: <?php echo htmlspecialchars($category_filter); ?>
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="info-box p-3 mb-3" style="background-color: #f8f9fc; border-left: 4px solid #51087E;">
                    <h6 class="font-weight-bold">Transaction Count</h6>
                    <h5><?php echo $transactions ? $transactions->num_rows : 0; ?> transactions</h5>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-box p-3 mb-3" style="background-color: #f8f9fc; border-left: 4px solid #51087E;">
                    <h6 class="font-weight-bold">Date Range</h6>
                    <h5>
                        <?php 
                        if ($date_range === 'custom' && $start_date && $end_date) {
                            echo date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date));
                        } else {
                            echo ucfirst($date_range);
                        }
                        ?>
                    </h5>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>