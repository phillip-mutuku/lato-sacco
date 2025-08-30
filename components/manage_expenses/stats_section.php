<?php
// Stats Section Component
// This component displays the filtering section and summary cards
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
        <div class="col-md-4 date-range-picker <?php echo $date_range === 'custom' ? 'active' : ''; ?>">
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
        <div class="col-md-4 mt-4">
            <div class="d-flex">
                <button type="submit" class="btn btn-warning mr-2">Apply Filter</button>
                <button type="button" class="btn btn-success" onclick="generateReport()">
                    <i class="fas fa-download"></i> Generate Report
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="summary-card">
            <h6 class="font-weight-bold text-primary">Total Expenses</h6>
            <h4 class="text-danger">KSh <?php echo number_format($total_expenses, 2); ?></h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="summary-card">
            <h6 class="font-weight-bold text-primary">Total Received</h6>
            <h4 class="text-success">KSh <?php echo number_format($total_received, 2); ?></h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="summary-card">
            <h6 class="font-weight-bold text-primary">Net Balance</h6>
            <h4 class="<?php echo ($total_received - $total_expenses) >= 0 ? 'text-success' : 'text-danger'; ?>">
                KSh <?php echo number_format($total_received - $total_expenses, 2); ?>
            </h4>
        </div>
    </div>
</div>