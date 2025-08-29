<?php
// Get selected filters
$selected_officer = isset($_GET['field_officer']) ? intval($_GET['field_officer']) : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Build where clauses
$where_clause = "WHERE 1=1";
if ($selected_officer) {
    $where_clause .= " AND g.field_officer_id = $selected_officer";
}
if ($from_date && $to_date) {
    $where_clause .= " AND DATE(g.created_at) BETWEEN '$from_date' AND '$to_date'";
}

// Get total groups
$groups_query = "SELECT COUNT(*) as total_groups FROM lato_groups g $where_clause";
$total_groups = $db->conn->query($groups_query)->fetch_assoc()['total_groups'];

// CORRECTED: Get total defaulters - clients with overdue unpaid/partial loans linked to field officers
$defaulters_query = "
    SELECT COUNT(DISTINCT l.account_id) as total_defaulters 
    FROM loan_schedule ls
    JOIN loan l ON ls.loan_id = l.loan_id
    JOIN client_accounts ca ON l.account_id = ca.account_id
    JOIN group_members gm ON ca.account_id = gm.account_id
    JOIN lato_groups g ON gm.group_id = g.group_id
    WHERE ls.due_date < CURDATE() 
    AND ls.status IN ('unpaid', 'partial')
    AND l.status IN (1, 2)
    AND gm.status = 'active'
    " . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "") 
    . ($from_date && $to_date ? " AND DATE(l.date_applied) BETWEEN '$from_date' AND '$to_date'" : "");
$total_defaulters = $db->conn->query($defaulters_query)->fetch_assoc()['total_defaulters'];

// CORRECTED: Get total defaulted amount - sum of overdue amounts from unpaid/partial installments linked to field officers
$defaulted_amount_query = "
    SELECT 
        COALESCE(SUM(
            CASE 
                WHEN ls.status = 'unpaid' THEN ls.amount
                WHEN ls.status = 'partial' THEN (ls.amount - COALESCE(ls.repaid_amount, 0))
                ELSE 0
            END
        ), 0) as total_defaulted
    FROM loan_schedule ls
    JOIN loan l ON ls.loan_id = l.loan_id
    JOIN client_accounts ca ON l.account_id = ca.account_id
    JOIN group_members gm ON ca.account_id = gm.account_id
    JOIN lato_groups g ON gm.group_id = g.group_id
    WHERE ls.due_date < CURDATE() 
    AND ls.status IN ('unpaid', 'partial')
    AND l.status IN (1, 2)
    AND gm.status = 'active'
    " . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "")
    . ($from_date && $to_date ? " AND DATE(l.date_applied) BETWEEN '$from_date' AND '$to_date'" : "");
$total_defaulted = $db->conn->query($defaulted_amount_query)->fetch_assoc()['total_defaulted'];

// CORRECTED: Get total outstanding loans (principal remaining) for active loans linked to field officers
$total_loans_query = "
    SELECT COALESCE(SUM(
        CASE 
            WHEN l.status = 3 THEN 0  -- Completed loans
            ELSE (l.amount - COALESCE((
                SELECT SUM(lr.amount_repaid) 
                FROM loan_repayments lr 
                WHERE lr.loan_id = l.loan_id
            ), 0))
        END
    ), 0) as total_loans
    FROM loan l
    JOIN client_accounts ca ON l.account_id = ca.account_id
    JOIN group_members gm ON ca.account_id = gm.account_id
    JOIN lato_groups g ON gm.group_id = g.group_id
    WHERE gm.status = 'active'
    AND l.status IN (1, 2, 3)  -- Include all loan statuses but calculate outstanding properly
    " . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "")
    . ($from_date && $to_date ? " AND DATE(l.date_applied) BETWEEN '$from_date' AND '$to_date'" : "");
$total_loans = $db->conn->query($total_loans_query)->fetch_assoc()['total_loans'];

// Additional useful metrics for field officer performance
// Get total active members under this field officer
$active_members_query = "
    SELECT COUNT(DISTINCT ca.account_id) as total_members
    FROM client_accounts ca
    JOIN group_members gm ON ca.account_id = gm.account_id
    JOIN lato_groups g ON gm.group_id = g.group_id
    WHERE gm.status = 'active'
    " . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "");
$total_members = $db->conn->query($active_members_query)->fetch_assoc()['total_members'];

// Get total loans disbursed (for performance tracking)
$disbursed_loans_query = "
    SELECT 
        COUNT(l.loan_id) as loan_count,
        COALESCE(SUM(l.amount), 0) as total_disbursed
    FROM loan l
    JOIN client_accounts ca ON l.account_id = ca.account_id
    JOIN group_members gm ON ca.account_id = gm.account_id
    JOIN lato_groups g ON gm.group_id = g.group_id
    WHERE l.status >= 2  -- Disbursed loans
    AND gm.status = 'active'
    " . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "")
    . ($from_date && $to_date ? " AND DATE(l.date_applied) BETWEEN '$from_date' AND '$to_date'" : "");
$disbursed_data = $db->conn->query($disbursed_loans_query)->fetch_assoc();
$total_disbursed = $disbursed_data['total_disbursed'];
$disbursed_count = $disbursed_data['loan_count'];

// Calculate performance metrics
$default_rate = ($total_disbursed > 0) ? ($total_defaulted / $total_disbursed) * 100 : 0;
$recovery_rate = ($total_disbursed > 0) ? (($total_disbursed - $total_loans) / $total_disbursed) * 100 : 0;
?>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-md-3">
                <div class="form-group mb-0">
                    <label><strong>Field Officer:</strong></label>
                    <select name="field_officer" class="form-control">
                        <option value="">All Field Officers</option>
                        <?php 
                        $officers_query = "SELECT user_id, firstname, lastname FROM user WHERE role = 'officer' ORDER BY firstname";
                        $officers_result = $db->conn->query($officers_query);
                        while ($officer = $officers_result->fetch_assoc()): ?>
                            <option value="<?php echo $officer['user_id']; ?>" 
                                    <?php echo $selected_officer == $officer['user_id'] ? 'selected' : ''; ?>>
                                <?php echo $officer['firstname'] . ' ' . $officer['lastname']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-0">
                    <label><strong>From Date:</strong></label>
                    <input type="date" name="from_date" class="form-control" 
                           value="<?php echo $_GET['from_date'] ?? ''; ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-0">
                    <label><strong>To Date:</strong></label>
                    <input type="date" name="to_date" class="form-control" 
                           value="<?php echo $_GET['to_date'] ?? ''; ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-0 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="groups.php" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Groups</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($total_groups); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Active Members</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($total_members); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-friends fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Total Defaulters</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($total_defaulters); ?>
                        </div>
                        <div class="text-xs text-muted">
                            <?php echo $total_members > 0 ? round(($total_defaulters / $total_members) * 100, 1) : 0; ?>% of members
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users-slash fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Defaulted Amount</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            KSh <?php echo number_format($total_defaulted, 2); ?>
                        </div>
                        <div class="text-xs text-muted">
                            <?php echo round($default_rate, 1); ?>% default rate
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Outstanding Loans</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            KSh <?php echo number_format($total_loans, 2); ?>
                        </div>
                        <div class="text-xs text-muted">
                            <?php echo number_format($disbursed_count); ?> loans
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                            Total Disbursed</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            KSh <?php echo number_format($total_disbursed, 2); ?>
                        </div>
                        <div class="text-xs text-muted">
                            <?php echo round($recovery_rate, 1); ?>% recovered
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($selected_officer): ?>
<!-- Field Officer Performance Summary -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-left-primary shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Performance Summary
                    <?php 
                    if ($selected_officer) {
                        $officer_query = "SELECT firstname, lastname FROM user WHERE user_id = $selected_officer";
                        $officer_result = $db->conn->query($officer_query)->fetch_assoc();
                        echo "- " . $officer_result['firstname'] . " " . $officer_result['lastname'];
                    }
                    ?>
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-sm font-weight-bold text-uppercase text-muted mb-1">Portfolio Health</div>
                        <div class="h6 mb-2">
                            <span class="badge badge-<?php echo $default_rate < 5 ? 'success' : ($default_rate < 10 ? 'warning' : 'danger'); ?>">
                                <?php echo round($default_rate, 1); ?>% Default Rate
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-sm font-weight-bold text-uppercase text-muted mb-1">Recovery Performance</div>
                        <div class="h6 mb-2">
                            <span class="badge badge-<?php echo $recovery_rate > 90 ? 'success' : ($recovery_rate > 75 ? 'warning' : 'danger'); ?>">
                                <?php echo round($recovery_rate, 1); ?>% Recovered
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-sm font-weight-bold text-uppercase text-muted mb-1">Portfolio Size</div>
                        <div class="h6 mb-2">
                            KSh <?php echo number_format($total_loans, 0); ?> Outstanding
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-sm font-weight-bold text-uppercase text-muted mb-1">Risk Level</div>
                        <div class="h6 mb-2">
                            <?php 
                            $risk_level = 'Low';
                            $risk_class = 'success';
                            if ($default_rate > 10 || ($total_defaulted > 50000)) {
                                $risk_level = 'High';
                                $risk_class = 'danger';
                            } elseif ($default_rate > 5 || ($total_defaulted > 25000)) {
                                $risk_level = 'Medium';
                                $risk_class = 'warning';
                            }
                            ?>
                            <span class="badge badge-<?php echo $risk_class; ?>">
                                <?php echo $risk_level; ?> Risk
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>