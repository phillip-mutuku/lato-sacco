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

// Function to update loan schedules for group members (same logic as arrears page)
function updateGroupLoanSchedules($db, $selected_officer = 0) {
    // Get all active loans for group members under selected field officer
    $loans_query = "SELECT l.loan_id, l.amount, l.loan_term, l.meeting_date, l.date_created, lp.interest_rate
                    FROM loan l
                    JOIN loan_products lp ON l.loan_product_id = lp.id
                    JOIN client_accounts ca ON l.account_id = ca.account_id
                    JOIN group_members gm ON ca.account_id = gm.account_id
                    JOIN lato_groups g ON gm.group_id = g.group_id
                    WHERE l.status IN (1, 2)
                    AND gm.status = 'active'"
                    . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "");
    
    $loans_result = $db->conn->query($loans_query);
    
    if ($loans_result && $loans_result->num_rows > 0) {
        while ($loan = $loans_result->fetch_assoc()) {
            $loan_id = $loan['loan_id'];
            $total_amount = floatval($loan['amount']);
            $term = intval($loan['loan_term']);
            $interest_rate = floatval($loan['interest_rate']);
            $monthly_principal = round($total_amount / $term, 2);

            // Get existing repayments
            $repayment_query = "SELECT due_date, repaid_amount, paid_date FROM loan_schedule WHERE loan_id = ?";
            $repayment_stmt = $db->conn->prepare($repayment_query);
            $repayment_stmt->bind_param("i", $loan_id);
            $repayment_stmt->execute();
            $repayment_result = $repayment_stmt->get_result();
            $repayments = $repayment_result->fetch_all(MYSQLI_ASSOC);

            // Start from meeting date or loan date
            $payment_date = new DateTime($loan['meeting_date'] ?? $loan['date_created']);
            $payment_date->modify('+1 month');

            // Generate schedule
            $remaining_principal = $total_amount;

            for ($i = 0; $i < $term; $i++) {
                $interest = round($remaining_principal * ($interest_rate / 100), 2);
                $due_amount = $monthly_principal + $interest;
                $due_date = $payment_date->format('Y-m-d');

                // Check if this payment has been made
                $repaid_amount = 0;
                $paid_date = null;
                $status = 'unpaid';

                foreach ($repayments as $repayment) {
                    if ($repayment['due_date'] == $due_date) {
                        $repaid_amount = floatval($repayment['repaid_amount']);
                        $paid_date = $repayment['paid_date'];
                        $status = (abs($repaid_amount - $due_amount) <= 0.50) ? 'paid' : (($repaid_amount > 0) ? 'partial' : 'unpaid');
                        break;
                    }
                }

                // Calculate default amount - only if past due date
                $default_amount = 0;
                $today = new DateTime();
                if ($today > new DateTime($due_date) && $status !== 'paid') {
                    $default_amount = max(0, $due_amount - $repaid_amount);
                }

                // Update or insert schedule entry
                $upsert_stmt = $db->conn->prepare("
                    INSERT INTO loan_schedule 
                    (loan_id, due_date, principal, interest, amount, repaid_amount, default_amount, status, paid_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    principal = VALUES(principal),
                    interest = VALUES(interest),
                    amount = VALUES(amount),
                    default_amount = VALUES(default_amount),
                    status = CASE 
                        WHEN status = 'paid' THEN 'paid' 
                        ELSE VALUES(status) 
                    END
                ");
                
                $upsert_stmt->bind_param(
                    "isdddddss",
                    $loan_id,
                    $due_date,
                    $monthly_principal,
                    $interest,
                    $due_amount,
                    $repaid_amount,
                    $default_amount,
                    $status,
                    $paid_date
                );
                
                $upsert_stmt->execute();

                // Update balances for next iteration
                $remaining_principal -= $monthly_principal;
                $payment_date->modify('+1 month');
            }
        }
    }
}

// Update loan schedules before calculating statistics
try {
    updateGroupLoanSchedules($db, $selected_officer);
} catch (Exception $e) {
    error_log("Failed to update group loan schedules: " . $e->getMessage());
}

// Get total groups
$groups_query = "SELECT COUNT(*) as total_groups FROM lato_groups g $where_clause";
$total_groups = $db->conn->query($groups_query)->fetch_assoc()['total_groups'];

// Get total defaulters - clients with overdue unpaid/partial loans linked to field officers
// NOW USING default_amount from loan_schedule table (consistent with arrears page)
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
    AND ls.default_amount > 0"
    . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "") 
    . ($from_date && $to_date ? " AND DATE(l.date_applied) BETWEEN '$from_date' AND '$to_date'" : "");
$total_defaulters = $db->conn->query($defaulters_query)->fetch_assoc()['total_defaulters'];

// Get total defaulted amount - sum of default_amount from loan_schedule table
// NOW USING the calculated default_amount directly (consistent with arrears page)
$defaulted_amount_query = "
    SELECT 
        COALESCE(SUM(ls.default_amount), 0) as total_defaulted
    FROM loan_schedule ls
    JOIN loan l ON ls.loan_id = l.loan_id
    JOIN client_accounts ca ON l.account_id = ca.account_id
    JOIN group_members gm ON ca.account_id = gm.account_id
    JOIN lato_groups g ON gm.group_id = g.group_id
    WHERE ls.due_date < CURDATE() 
    AND ls.status IN ('unpaid', 'partial')
    AND l.status IN (1, 2)
    AND gm.status = 'active'
    AND ls.default_amount > 0"
    . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "")
    . ($from_date && $to_date ? " AND DATE(l.date_applied) BETWEEN '$from_date' AND '$to_date'" : "");
$total_defaulted = $db->conn->query($defaulted_amount_query)->fetch_assoc()['total_defaulted'];

// Calculate Outstanding Loans (Principal only) for active loans linked to field officers
function calculateOutstandingPrincipal($db, $selected_officer = 0, $from_date = '', $to_date = '') {
    $outstanding_principal = 0;
    
    // Get all active loans for group members
    $loans_query = "
        SELECT 
            l.loan_id,
            l.amount as original_amount,
            l.loan_term,
            l.date_applied,
            l.meeting_date,
            COALESCE(lp.interest_rate, 0) as interest_rate
        FROM loan l
        LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
        JOIN client_accounts ca ON l.account_id = ca.account_id
        JOIN group_members gm ON ca.account_id = gm.account_id
        JOIN lato_groups g ON gm.group_id = g.group_id
        WHERE l.status IN (1, 2)
        AND gm.status = 'active'
        " . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "")
        . ($from_date && $to_date ? " AND DATE(l.date_applied) BETWEEN '$from_date' AND '$to_date'" : "");
    
    $loans_result = $db->conn->query($loans_query);
    
    while ($loan = $loans_result->fetch_assoc()) {
        $loanId = $loan['loan_id'];
        $originalAmount = floatval($loan['original_amount']);
        $loanTerm = intval($loan['loan_term']);
        $interestRate = floatval($loan['interest_rate']) / 100;
        
        if ($loanTerm <= 0) {
            $outstanding_principal += $originalAmount;
            continue;
        }
        
        // Calculate monthly principal payment
        $monthlyPrincipal = $originalAmount / $loanTerm;
        
        // Get total amount repaid for this loan
        $repayment_query = "
            SELECT COALESCE(SUM(amount_repaid), 0) as total_repaid
            FROM loan_repayments 
            WHERE loan_id = $loanId";
        
        $repayment_result = $db->conn->query($repayment_query);
        $totalRepaid = floatval($repayment_result->fetch_assoc()['total_repaid']);
        
        // Calculate principal paid following amortization schedule
        $remainingPrincipal = $originalAmount;
        $principalPaid = 0;
        $remainingRepayment = $totalRepaid;
        
        for ($month = 1; $month <= $loanTerm && $remainingRepayment > 0; $month++) {
            $monthlyInterest = $remainingPrincipal * $interestRate;
            $monthlyPayment = $monthlyPrincipal + $monthlyInterest;
            
            if ($remainingRepayment >= $monthlyPayment) {
                // Full payment made for this month
                $principalPaid += $monthlyPrincipal;
                $remainingPrincipal -= $monthlyPrincipal;
                $remainingRepayment -= $monthlyPayment;
            } else {
                // Partial payment - allocate to interest first, then principal
                if ($remainingRepayment > $monthlyInterest) {
                    $principalPortionPaid = $remainingRepayment - $monthlyInterest;
                    $principalPaid += $principalPortionPaid;
                }
                break;
            }
        }
        
        $loanOutstanding = max(0, $originalAmount - $principalPaid);
        $outstanding_principal += $loanOutstanding;
    }
    
    return $outstanding_principal;
}

// Calculate Total Outstanding Loans (Principal + Interest) for active loans
function calculateTotalOutstandingWithInterest($db, $selected_officer = 0, $from_date = '', $to_date = '') {
    $total_outstanding = 0;
    
    // Get sum of unpaid amounts from loan schedule (principal + interest)
    $outstanding_query = "
        SELECT COALESCE(SUM(
            CASE 
                WHEN ls.status = 'unpaid' THEN ls.amount
                WHEN ls.status = 'partial' THEN (ls.amount - COALESCE(ls.repaid_amount, 0))
                ELSE 0
            END
        ), 0) as total_outstanding
        FROM loan_schedule ls
        JOIN loan l ON ls.loan_id = l.loan_id
        JOIN client_accounts ca ON l.account_id = ca.account_id
        JOIN group_members gm ON ca.account_id = gm.account_id
        JOIN lato_groups g ON gm.group_id = g.group_id
        WHERE l.status IN (1, 2)
        AND gm.status = 'active'
        AND ls.status IN ('unpaid', 'partial')
        " . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "")
        . ($from_date && $to_date ? " AND DATE(l.date_applied) BETWEEN '$from_date' AND '$to_date'" : "");
    
    $result = $db->conn->query($outstanding_query);
    $total_outstanding = floatval($result->fetch_assoc()['total_outstanding']);
    
    return $total_outstanding;
}

// Calculate the actual values
$outstanding_loans_principal = calculateOutstandingPrincipal($db, $selected_officer, $from_date, $to_date);
$total_outstanding_with_interest = calculateTotalOutstandingWithInterest($db, $selected_officer, $from_date, $to_date);

// Get total active members under selected field officer(s)
$active_members_query = "
    SELECT COUNT(DISTINCT ca.account_id) as total_members
    FROM client_accounts ca
    JOIN group_members gm ON ca.account_id = gm.account_id
    JOIN lato_groups g ON gm.group_id = g.group_id
    WHERE gm.status = 'active'
    " . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "");
$total_members = $db->conn->query($active_members_query)->fetch_assoc()['total_members'];

// Get total loans count for display purposes
$loans_count_query = "
    SELECT COUNT(l.loan_id) as loan_count
    FROM loan l
    JOIN client_accounts ca ON l.account_id = ca.account_id
    JOIN group_members gm ON ca.account_id = gm.account_id
    JOIN lato_groups g ON gm.group_id = g.group_id
    WHERE l.status IN (1, 2)
    AND gm.status = 'active'
    " . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "")
    . ($from_date && $to_date ? " AND DATE(l.date_applied) BETWEEN '$from_date' AND '$to_date'" : "");
$loan_count = $db->conn->query($loans_count_query)->fetch_assoc()['loan_count'];

// Calculate performance metrics
$default_rate = ($total_outstanding_with_interest > 0) ? ($total_defaulted / $total_outstanding_with_interest) * 100 : 0;


//reset button functionality
$current_page = basename($_SERVER['PHP_SELF']);
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
                    <a href="<?php echo $current_page; ?>" class="btn btn-secondary">
                            <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Statistics Cards - Row 1 -->
<div class="row mb-4">
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Groups</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($total_groups); ?>
                        </div>
                        <div class="text-xs text-muted">
                            Wekeza groups managed
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Active Members</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($total_members); ?>
                        </div>
                        <div class="text-xs text-muted">
                            Group members with active status
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-friends fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
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
                            <?php echo $total_members > 0 ? round(($total_defaulters / $total_members) * 100, 1) : 0; ?>% of active members
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users-slash fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards - Row 2 -->
<div class="row mb-4">
    <div class="col-xl-4 col-md-6 mb-4">
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

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Outstanding Loans</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            KSh <?php echo number_format($outstanding_loans_principal, 2); ?>
                        </div>
                        <div class="text-xs text-muted">
                            Principal amount outstanding
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                            Total Outstanding Loans</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            KSh <?php echo number_format($total_outstanding_with_interest, 2); ?>
                        </div>
                        <div class="text-xs text-muted">
                            Principal + Interest (<?php echo number_format($loan_count); ?> loans)
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
                        <div class="text-sm font-weight-bold text-uppercase text-muted mb-1">Principal Outstanding</div>
                        <div class="h6 mb-2">
                            KSh <?php echo number_format($outstanding_loans_principal, 0); ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-sm font-weight-bold text-uppercase text-muted mb-1">Total Outstanding</div>
                        <div class="h6 mb-2">
                            KSh <?php echo number_format($total_outstanding_with_interest, 0); ?>
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

<!-- Additional Performance Metrics (if filtering by officer) -->
<?php if ($selected_officer && $total_members > 0): ?>
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-left-info shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">
                    <i class="fas fa-chart-pie"></i> Portfolio Distribution
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-sm">Active Members</span>
                        <span class="text-sm font-weight-bold"><?php echo number_format($total_members); ?></span>
                    </div>
                    <div class="progress mb-2">
                        <div class="progress-bar bg-success" style="width: <?php echo ($total_members - $total_defaulters) / $total_members * 100; ?>%"></div>
                        <div class="progress-bar bg-danger" style="width: <?php echo $total_defaulters / $total_members * 100; ?>%"></div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-success">
                                <i class="fas fa-circle"></i> Good Standing: <?php echo number_format($total_members - $total_defaulters); ?>
                            </small>
                        </div>
                        <div class="col-6">
                            <small class="text-danger">
                                <i class="fas fa-circle"></i> Defaulters: <?php echo number_format($total_defaulters); ?>
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-sm">Financial Health</span>
                        <span class="text-sm font-weight-bold">
                            <?php echo $total_outstanding_with_interest > 0 ? round((($total_outstanding_with_interest - $total_defaulted) / $total_outstanding_with_interest) * 100, 1) : 100; ?>%
                        </span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" style="width: <?php echo $total_outstanding_with_interest > 0 ? (($total_outstanding_with_interest - $total_defaulted) / $total_outstanding_with_interest) * 100 : 100; ?>%"></div>
                    </div>
                    <small class="text-muted">Percentage of loans performing well</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-left-success shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-trophy"></i> Performance Indicators
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="text-sm font-weight-bold text-uppercase text-muted mb-1">Recovery Rate</div>
                        <div class="h5 mb-1">
                            <?php 
                            $recovery_rate = $total_outstanding_with_interest > 0 ? 
                                round((($total_outstanding_with_interest - $total_defaulted) / $total_outstanding_with_interest) * 100, 1) : 100;
                            ?>
                            <span class="text-<?php echo $recovery_rate >= 95 ? 'success' : ($recovery_rate >= 85 ? 'warning' : 'danger'); ?>">
                                <?php echo $recovery_rate; ?>%
                            </span>
                        </div>
                        <small class="text-muted">Portfolio recovery performance</small>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <div class="text-sm font-weight-bold text-uppercase text-muted mb-1">Average Default per Member</div>
                        <div class="h6 mb-1">
                            KSh <?php echo $total_defaulters > 0 ? number_format($total_defaulted / $total_defaulters, 0) : '0'; ?>
                        </div>
                        <small class="text-muted">Average arrears per defaulting member</small>
                    </div>
                    
                    <div class="col-12">
                        <div class="text-sm font-weight-bold text-uppercase text-muted mb-1">Portfolio Utilization</div>
                        <div class="h6 mb-1">
                            <?php echo $total_members > 0 ? round(($loan_count / $total_members) * 100, 1) : 0; ?>%
                        </div>
                        <small class="text-muted">Members with active loans</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Success Alert for Good Performance -->
<?php if ($default_rate < 5 && $total_members > 10): ?>
<div class="alert alert-success" role="alert">
    <h5 class="alert-heading">
        <i class="fas fa-check-circle"></i> Excellent Portfolio Performance
    </h5>
    <p class="mb-0">
        Outstanding portfolio management! Default rate of <?php echo round($default_rate, 1); ?>% is well below industry standards. 
        <?php if ($selected_officer): ?>
        This field officer demonstrates excellent client relationship management and collection practices.
        <?php else: ?>
        The overall field officer team is performing exceptionally well.
        <?php endif; ?>
    </p>
</div>
<?php endif; ?>