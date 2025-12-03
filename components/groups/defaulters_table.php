<?php
// Get filter parameters
$selected_group = isset($_GET['group_filter']) ? intval($_GET['group_filter']) : 0;
$from_date = isset($_GET['from_date_filter']) ? $_GET['from_date_filter'] : '';
$to_date = isset($_GET['to_date_filter']) ? $_GET['to_date_filter'] : '';

// Build where clause for filters
$where_conditions = [
    "ls.status IN ('unpaid', 'partial')",
    "ls.due_date < CURDATE()",
    "l.status IN (1, 2)",
    "gm.status = 'active'",
    "ls.default_amount > 0"
];

if ($selected_group) {
    $where_conditions[] = "g.group_id = " . intval($selected_group);
}
if ($from_date && $to_date) {
    $from_date_safe = $db->conn->real_escape_string($from_date);
    $to_date_safe = $db->conn->real_escape_string($to_date);
    $where_conditions[] = "ls.due_date BETWEEN '$from_date_safe' AND '$to_date_safe'";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Initialize variables
$defaulters_result = null;
$summary = [
    'total_defaulters' => 0,
    'affected_groups' => 0,
    'total_amount_defaulted' => 0,
    'total_overdue_installments' => 0
];

// Execute queries
try {
    $defaulters_query = "
        SELECT 
            ca.account_id,
            CONCAT(ca.first_name, ' ', ca.last_name) as member_name,
            ca.shareholder_no,
            g.group_name,
            g.group_reference,
            l.loan_id,
            l.ref_no as loan_reference,
            SUM(ls.default_amount) as total_defaulted,
            COUNT(DISTINCT ls.due_date) as overdue_installments,
            MIN(ls.due_date) as first_overdue_date,
            CONCAT(u.firstname, ' ', u.lastname) as field_officer_name
        FROM loan_schedule ls
        JOIN loan l ON ls.loan_id = l.loan_id
        JOIN client_accounts ca ON l.account_id = ca.account_id
        JOIN group_members gm ON ca.account_id = gm.account_id
        JOIN lato_groups g ON gm.group_id = g.group_id
        JOIN user u ON g.field_officer_id = u.user_id
        $where_clause
        GROUP BY ca.account_id, g.group_id, l.loan_id
        ORDER BY total_defaulted DESC
    ";

    $defaulters_result = $db->conn->query($defaulters_query);
    if (!$defaulters_result) throw new Exception($db->conn->error);

    $summary_query = "
        SELECT 
            COUNT(DISTINCT ca.account_id) as total_defaulters,
            COUNT(DISTINCT g.group_id) as affected_groups,
            COALESCE(SUM(ls.default_amount), 0) as total_amount_defaulted,
            COUNT(DISTINCT CONCAT(ls.loan_id, '-', ls.due_date)) as total_overdue_installments
        FROM loan_schedule ls
        JOIN loan l ON ls.loan_id = l.loan_id
        JOIN client_accounts ca ON l.account_id = ca.account_id
        JOIN group_members gm ON ca.account_id = gm.account_id
        JOIN lato_groups g ON gm.group_id = g.group_id
        $where_clause
    ";

    $summary_result = $db->conn->query($summary_query);
    if ($summary_result) $summary = $summary_result->fetch_assoc();
} catch (Exception $e) {
    error_log("Error in defaulters_table.php: " . $e->getMessage());
}
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Defaulters</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($summary['total_defaulters']); ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Affected Groups</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($summary['affected_groups']); ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Defaulted</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">KSh <?php echo number_format($summary['total_amount_defaulted'], 2); ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Overdue Installments</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($summary['total_overdue_installments']); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <input type="hidden" name="tab" value="defaulters">
            <div class="col-md-3">
                <label><strong>Filter by Group:</strong></label>
                <select name="group_filter" class="form-control" id="groupFilter">
                    <option value="">All Groups</option>
                    <?php 
                    $groups_query = "SELECT group_id, group_name, group_reference FROM lato_groups ORDER BY group_name";
                    $groups_result = $db->conn->query($groups_query);
                    while ($group = $groups_result->fetch_assoc()): ?>
                        <option value="<?php echo $group['group_id']; ?>" <?php echo $selected_group == $group['group_id'] ? 'selected' : ''; ?>>
                            <?php echo $group['group_reference'] . ' - ' . $group['group_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label><strong>From Date:</strong></label>
                <input type="date" name="from_date_filter" class="form-control" value="<?php echo $from_date; ?>" id="fromDateFilter">
            </div>
            <div class="col-md-3">
                <label><strong>To Date:</strong></label>
                <input type="date" name="to_date_filter" class="form-control" value="<?php echo $to_date; ?>" id="toDateFilter">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
                <a href="?tab=defaulters" class="btn btn-secondary" style="margin:10px;"><i class="fas fa-sync-alt"></i> Reset</a>
                <button type="button" class="btn btn-success" id="exportDefaultersExcel"><i class="fas fa-file-excel"></i> Export Excel</button>
            </div>
        </form>
    </div>
</div>

<!-- Defaulters Table -->
<div class="card mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="defaultersTable">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Member Name</th>
                        <th>Shareholder No</th>
                        <th>Group</th>
                        <th>Loan Reference</th>
                        <th>Defaulted Amount</th>
                        <th>Overdue Installments</th>
                        <th>First Overdue</th>
                        <th>Field Officer</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    if ($defaulters_result && $defaulters_result->num_rows > 0):
                        while ($row = $defaulters_result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($row['member_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['shareholder_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['group_reference'] . ' - ' . $row['group_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['loan_reference']); ?></td>
                        <td><span class="badge badge-danger">KSh <?php echo number_format($row['total_defaulted'], 2); ?></span></td>
                        <td class="text-center"><span class="badge badge-warning"><?php echo $row['overdue_installments']; ?></span></td>
                        <td><?php echo date('d M Y', strtotime($row['first_overdue_date'])); ?></td>
                        <td><?php echo htmlspecialchars($row['field_officer_name']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary view-member-details" 
                                    data-member-id="<?php echo $row['account_id']; ?>"
                                    title="View member details">
                                <i class="fas fa-user"></i> Member
                            </button>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No defaulters found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>