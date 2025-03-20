<?php
    date_default_timezone_set("Africa/Nairobi");
    require_once '../helpers/session.php';
    require_once '../config/class.php';
    $db = new db_class();

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: index.php');
    exit();
}


  // Initialize filter variables with proper defaults
$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) 
? $_GET['start_date'] 
: date('Y-m-01');

$end_date = isset($_GET['end_date']) && !empty($_GET['end_date'])
? $_GET['end_date']
: date('Y-m-d');

$category = isset($_GET['category']) && !empty($_GET['category'])
? $_GET['category']
: 'all';

$expense_id = isset($_GET['expense_id']) && !empty($_GET['expense_id'])
? $_GET['expense_id']
: null;

// Validate dates
if (!validateDate($start_date) || !validateDate($end_date)) {
$start_date = date('Y-m-01');
$end_date = date('Y-m-d');
}

// Helper function to validate date
function validateDate($date, $format = 'Y-m-d') {
$d = DateTime::createFromFormat($format, $date);
return $d && $d->format($format) === $date;
}

    // Define expense categories
    function getExpenseCategories($db) {
        $query = "SELECT DISTINCT id, category, name FROM expenses_categories ORDER BY category, name";
        $result = $db->conn->query($query);
        $categories = [];
        while($row = $result->fetch_assoc()) {
            if (!isset($categories[$row['category']])) {
                $categories[$row['category']] = [];
            }
            $categories[$row['category']][] = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }
        return $categories;
    }

//income sources
function getExpenditureData($db, $start_date, $end_date, $category = 'all') {
    $params = [];
    $types = '';
    $conditions = [];
    
    // Base parameters
    $params = array_merge($params, [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
    $types = 'ssssss';

    $query = "
        WITH expense_data AS (
            SELECT 
                'Regular Expense' as source,
                e.category as name,
                ec.category as main_category,
                ABS(e.amount) as amount,  -- Convert negative amount to positive
                e.date,
                e.description
            FROM expenses e
            JOIN expenses_categories ec ON e.category = ec.name
            WHERE DATE(e.date) BETWEEN ? AND ?
            AND e.status = 'completed'
            
            UNION ALL
            
            SELECT 
                'Loan Disbursement' as source,
                'Loan Disbursement' as name,
                'Financial Operations' as main_category,
                amount,
                date,
                description
            FROM transactions 
            WHERE DATE(date) BETWEEN ? AND ?
            AND type = 'Loan Disbursement'
            
            UNION ALL
            
            SELECT 
                'Business Group Withdrawal' as source,
                'Business Group Withdrawal' as name,
                'Financial Operations' as main_category,
                amount,
                date,
                description
            FROM business_group_transactions
            WHERE DATE(date) BETWEEN ? AND ?
            AND type = 'Withdrawal'
        )
    ";

    // Build the main query
    $mainQuery = "
        SELECT 
            main_category,
            name,
            SUM(amount) as total_amount,
            COUNT(*) as transaction_count,
            GROUP_CONCAT(description) as descriptions,
            MIN(date) as start_date,
            MAX(date) as end_date
        FROM expense_data
    ";

    // Add category filter if specified
    if ($category !== 'all') {
        $conditions[] = "main_category = ?";
        $params[] = $category;
        $types .= 's';
    }

    // Add conditions to the query
    if (!empty($conditions)) {
        $mainQuery .= " WHERE " . implode(" AND ", $conditions);
    }

    // Add grouping and ordering
    $mainQuery .= " GROUP BY main_category, name ORDER BY total_amount DESC";

    // Combine the CTE with the main query
    $query .= $mainQuery;

    // Prepare and execute the statement
    $stmt = $db->conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Update getIncomeData to ensure received expenses are handled properly
function getIncomeData($db, $start_date, $end_date, $category = 'all') {
    try {
        $withdrawalFees = getWithdrawalFees($db, $start_date, $end_date);
        $total_fees = $withdrawalFees['total'];
        
        $query = "
            (SELECT 
                type as source,
                SUM(amount) as amount,
                'Financial Operations' as category
            FROM transactions 
            WHERE DATE(date) BETWEEN ? AND ?
            AND type IN ('Savings', 'Loan Repayment')
            GROUP BY type)
            
            UNION ALL
            
            (SELECT 
                'Group Savings' as source,
                SUM(amount) as amount,
                'Financial Operations' as category
            FROM group_savings 
            WHERE DATE(date_saved) BETWEEN ? AND ?
            GROUP BY source)
            
            UNION ALL
            
            (SELECT 
                'Business Group Savings' as source,
                SUM(amount) as amount,
                'Financial Operations' as category
            FROM business_group_transactions 
            WHERE DATE(date) BETWEEN ? AND ?
            AND type = 'Savings'
            GROUP BY source)
            
            UNION ALL
            
            (SELECT 
                'Withdrawal Fees' as source,
                COALESCE(SUM(CASE 
                    WHEN type = 'Withdrawal Fee' THEN amount 
                    ELSE 0 
                END), 0) as amount,
                'Financial Operations' as category
            FROM business_group_transactions
            WHERE DATE(date) BETWEEN ? AND ?
            AND type = 'Withdrawal Fee'
            )

            UNION ALL

            (SELECT 
                CONCAT('Income: ', e.category) as source,
                SUM(e.amount) as amount,
                ec.category as category
            FROM expenses e
            JOIN expenses_categories ec ON e.category = ec.name
            WHERE DATE(e.date) BETWEEN ? AND ?
            AND e.status = 'received'
            GROUP BY e.category, ec.category)
        ";
        
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("ssssssssss", 
            $start_date, $end_date,
            $start_date, $end_date,
            $start_date, $end_date,
            $start_date, $end_date,
            $start_date, $end_date
        );
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if ($category !== 'all') {
            $result = array_filter($result, function($item) use ($category) {
                return $item['category'] === $category;
            });
        }
        
        return array_values($result);
    } catch (Exception $e) {
        error_log("Error in getIncomeData: " . $e->getMessage());
        return [];
    }
}

// Update the getWithdrawalFees function to use business_group_transactions
function getWithdrawalFees($db, $start_date, $end_date) {
    try {
        $query = "
            SELECT 
                source,
                COALESCE(SUM(fee), 0) as fees
            FROM (
                -- Individual withdrawals from savings
                SELECT 
                    'Individual Withdrawals' as source,
                    withdrawal_fee as fee
                FROM savings
                WHERE DATE(date) BETWEEN ? AND ?
                AND type = 'Withdrawal'
                AND withdrawal_fee > 0
                
                UNION ALL
                
                -- System payments
                SELECT 
                    'Payment System' as source,
                    withdrawal_fee as fee
                FROM payment
                WHERE DATE(date_created) BETWEEN ? AND ?
                AND withdrawal_fee > 0
                
                UNION ALL
                
                -- Business group withdrawals from transactions table
                SELECT 
                    'Business Group Withdrawals' as source,
                    amount as fee
                FROM business_group_transactions
                WHERE DATE(date) BETWEEN ? AND ?
                AND type = 'Withdrawal Fee'
            ) all_fees
            GROUP BY source
        ";
        
        $stmt = $db->conn->prepare($query);
        $stmt->bind_param("ssssss", 
            $start_date, $end_date,
            $start_date, $end_date,
            $start_date, $end_date
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $total = 0;
        foreach ($result as $row) {
            $total += $row['fees'];
        }
        
        return [
            'total' => $total,
            'breakdown' => $result
        ];
        
    } catch (Exception $e) {
        error_log("Error in getWithdrawalFees: " . $e->getMessage());
        return ['total' => 0, 'breakdown' => []];
    }
}




// Get filtered data
$income_data = getIncomeData($db, $start_date, $end_date, $category);
$expenditure_data = getExpenditureData($db, $start_date, $end_date, $category);
$withdrawal_fees_data = getWithdrawalFees($db, $start_date, $end_date);

// Get total withdrawal fees
$withdrawal_fees = $withdrawal_fees_data['total'];

// Calculate totals
$total_income = array_sum(array_column($income_data, 'amount'));
$total_expenditure = array_sum(array_column($expenditure_data, 'total_amount'));
$net_position = $total_income - $total_expenditure;
$total_profit = $net_position + $withdrawal_fees;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lato Management System - Income & Expenditure</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        .financial-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .financial-card:hover {
            transform: translateY(-5px);
        }
        .income-card {
            border-left: 5px solid #28a745;
        }
        .expenditure-card {
            border-left: 5px solid #dc3545;
        }
        .net-position-card {
            border-left: 5px solid #17a2b8;
        }
        .profit-card {
            border-left: 5px solid #007bff;
        }
        .category-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .filter-section {
            background-color: #f8f9fc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .table-responsive {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .summary-icon {
            font-size: 2rem;
            opacity: 0.7;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 100%;
        }
        @media print {
            .no-print {
                display: none;
            }
            .print-only {
                display: block;
            }
        }
        html, body {
            overflow-x: hidden;
        }
        #accordionSidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            width: 225px;
            transition: width 0.3s ease;
        }
        #content-wrapper {
            margin-left: 225px;
            width: calc(100% - 225px);
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        .topbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 225px;
            z-index: 1000;
            transition: left 0.3s ease;
        }
        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        @media (max-width: 768px) {
            #accordionSidebar {
                width: 100px;
            }
            #content-wrapper {
                margin-left: 100px;
                width: calc(100% - 100px);
            }
            .topbar {
                left: 100px;
            }
            .sidebar .nav-item .nav-link span {
                display: none;
            }
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
            <!-- Sidebar -->
        <ul style="background: #51087E;"  class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
                <div class="sidebar-brand-text mx-3">LATO SACCO</div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item">
                <a class="nav-link" href="home.php">
                    <i class="fas fa-fw fa-home"></i>
                    <span>Home</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Management
            </div>

            <li class="nav-item">
                <a class="nav-link" href="../models/loan.php">
                <i class="fas fa-fw fas fa-comment-dollar"></i>
                    <span>New Loan</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../models/pending_approval.php">
                <i class="fas fa-fw fas fa-comment-dollar"></i>
                    <span>Pending Approval</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/disbursement.php">
                    <i class="fas fa-fw fas fa-coins"></i>
                    <span>Disbursements</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="daily-reconciliation.php">
                    <i class="fas fa-fw fa-balance-scale"></i>
                    <span>Daily Reconciliation</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="expenses_tracking.php">
                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                    <span>Expenses Tracking</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="manage_expenses.php">
                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    <span>Manage Expenses</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="../models/arrears.php">
                <i class="fas fa-users-slash fa-2x text-gray-300"></i>
                    <span>Arrears</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="receipts.php">
                <i class="fas fa-receipt fa-2x"></i>
                    <span>Receipts</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="account.php">
                <i class="fas fa-fw fa-user"></i>
                    <span>Client Accounts</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Wekeza Groups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/business_groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Business Groups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../models/loan_plan.php">
                    <i class="fas fa-fw fa-piggy-bank"></i>
                    <span>Loan Products</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                System
            </div>

            <li class="nav-item">
                <a class="nav-link" href="../models/user.php">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Users</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="announcements.php">
                    <i class="fas fa-fw fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="notifications.php">
                    <i class="fas fa-fw fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="backup.php">
                    <i class="fas fa-fw fa-database"></i>
                    <span>Backup</span>
                </a>
            </li>
        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo $db->user_acc($_SESSION['user_id'])?>
                                </span>
                                <img class="img-profile rounded-circle" src="../public/image/logo.jpg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <!-- Begin Page Content -->
                <div class="container-fluid pt-4">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Income & Expenditure</h1>
                        <div>
                            <button class="btn btn-sm btn-warning shadow-sm" onclick="window.print()">
                                <i class="fas fa-print fa-sm text-white-50"></i> Print Report
                            </button>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="card mb-4 filter-section no-print">
                        <div class="card-body">
                            <form id="filterForm" method="GET" class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="form-group mb-0">
                                        <label for="daterange">Date Range</label>
                                        <input type="text" class="form-control" id="daterange" name="daterange">
                                        <input type="hidden" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                                        <input type="hidden" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-0">
                                        <label for="category">Category</label>
                                        <select class="form-control" id="category" name="category">
                                            <option value="all">All Categories</option>
                                            <?php 
                                            $categories_query = "SELECT DISTINCT category FROM expenses_categories ORDER BY category";
                                            $categories_result = $db->conn->query($categories_query);
                                            while($cat = $categories_result->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                                        <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['category']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-0">
                                        <label for="expense_id">Specific Expense</label>
                                        <select class="form-control" id="expense_id" name="expense_id" <?php echo $category === 'all' ? 'disabled' : ''; ?>>
                                            <option value="">All Expenses</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-0">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-warning btn-block">
                                            <i class="fas fa-filter"></i> Apply Filters
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row">
                        <!-- Income Card -->
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card financial-card income-card h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Income
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                KSh <?php echo number_format($total_income, 2); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                       <!-- Expenditure Card -->
                       <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card financial-card expenditure-card h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Total Expenditure
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                KSh <?php echo number_format($total_expenditure, 2); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-coins fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Net Position Card -->
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card financial-card net-position-card h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Net Position
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold <?php echo $net_position >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                KSh <?php echo number_format($net_position, 2); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-balance-scale fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Profit Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card financial-card profit-card h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Profit
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold <?php echo $total_profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            KSh <?php echo number_format($total_profit, 2); ?>
                                        </div>
                                        <div class="text-xs text-muted mt-2">
                                            <div>Total Withdrawal Fees: KSh <?php echo number_format($withdrawal_fees, 2); ?></div>
                                            <div class="small mt-1">
                                                <?php foreach ($withdrawal_fees_data['breakdown'] as $source): ?>
                                                    <div><?php echo htmlspecialchars($source['source']); ?>: 
                                                        KSh <?php echo number_format($source['fees'], 2); ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>

                    <!-- Income and Expenditure Details -->
                    <div class="row">
                        <!-- Income Details -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-success">Income Breakdown</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="incomeTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Source</th>
                                                    <th>Amount</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($income_data as $income): ?>
                                                    <tr>
                                                        <td><?php echo $income['source']; ?></td>
                                                        <td>KSh <?php echo number_format($income['amount'], 2); ?></td>
                                                        <td>
                                                            <?php 
                                                                $percentage = ($total_income > 0) ? 
                                                                    ($income['amount'] / $total_income) * 100 : 0;
                                                                echo number_format($percentage, 1) . '%';
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="font-weight-bold">
                                                    <td>Total</td>
                                                    <td>KSh <?php echo number_format($total_income, 2); ?></td>
                                                    <td>100%</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Expenditure Details -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-danger">
                                        Expenditure Breakdown
                                        <?php if ($category !== 'all'): ?>
                                            - <?php echo htmlspecialchars($category); ?>
                                        <?php endif; ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="expenseTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Category</th>
                                                    <th>Amount</th>
                                                    <th>Count</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($expenditure_data as $expense): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($expense['main_category']); ?></td>
                                                    <td>KSh <?php echo number_format($expense['total_amount'], 2); ?></td>
                                                    <td><?php echo $expense['transaction_count']; ?></td>
                                                    <td>
                                                        <?php 
                                                        $percentage = ($total_expenditure > 0) ? 
                                                            ($expense['total_amount'] / $total_expenditure) * 100 : 0;
                                                        echo number_format($percentage, 1) . '%';
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="font-weight-bold">
                                                    <td>Total</td>
                                                    <td>KSh <?php echo number_format($total_expenditure, 2); ?></td>
                                                    <td><?php echo array_sum(array_column($expenditure_data, 'transaction_count')); ?></td>
                                                    <td>100%</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row">
                        <div class="col-xl-12 col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3">
                                    <h6 style="color: #51087E;" class="m-0 font-weight-bold">Income vs Expenditure Trend</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="financialTrendChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Lato Management System <?php echo date("Y")?></span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
            </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
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

    <!-- Bootstrap core JavaScript-->
<script src="../public/js/jquery.js"></script>
<script src="../public/js/bootstrap.bundle.js"></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.print.min.js"></script>

    <!-- Custom scripts -->
    <script>
$(document).ready(function() {
    initializeDataTables();

    // Initialize date range picker
    $('#daterange').daterangepicker({
        startDate: moment('<?php echo $start_date; ?>'),
        endDate: moment('<?php echo $end_date; ?>'),
        minYear: 2020,
        maxYear: 2025,
        showDropdowns: true,
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'This Year': [moment().startOf('year'), moment().endOf('year')],
            'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
        },
        opens: 'left',
        autoUpdateInput: true,
        alwaysShowCalendars: true,
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear'
        }
    }, function(start, end) {
        $('#start_date').val(start.format('YYYY-MM-DD'));
        $('#end_date').val(end.format('YYYY-MM-DD'));
        $('#filterForm').submit();
    });

    function initializeDataTables() {
    $('#expenseTable').DataTable({
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        order: [[1, 'desc']],
        responsive: true,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
    });

    $('#incomeTable').DataTable({
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        order: [[1, 'desc']],
        responsive: true,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
    });
}

    // Update the expense dropdown function
    function updateExpenseDropdown(category) {
        const expenseSelect = $('#expense_id');
        expenseSelect.empty().append('<option value="">All Expenses</option>');
        
        if (category === 'all') {
            expenseSelect.prop('disabled', true);
            return;
        }
        
        expenseSelect.prop('disabled', false);
        
        $.ajax({
            url: '../controllers/get_expenses.php',
            data: {
                category: category,
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val()
            },
            method: 'GET',
            success: function(response) {
                const expenses = JSON.parse(response);
                expenses.forEach(expense => {
                    expenseSelect.append(
                        $('<option></option>')
                            .val(expense.name)
                            .text(`${expense.name} - KSh ${numberFormat(expense.total_amount)}`)
                    );
                });
            }
        });
    }

    // Initialize trend chart
    function initializeTrendChart() {
        const ctx = document.getElementById('financialTrendChart').getContext('2d');
        
        $.ajax({
            url: '../controllers/get_trend_data.php',
            data: {
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                category: $('#category').val()
            },
            method: 'GET',
            success: function(response) {
                const data = JSON.parse(response);
                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Income',
                                data: data.income,
                                borderColor: 'rgb(40, 167, 69)',
                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                tension: 0.4,
                                fill: true
                            },
                            {
                                label: 'Expenditure',
                                data: data.expenditure,
                                borderColor: 'rgb(220, 53, 69)',
                                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                tension: 0.4,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: value => 'KSh ' + value.toLocaleString()
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: context => {
                                        const label = context.dataset.label;
                                        const value = context.raw;
                                        return `${label}: KSh ${value.toLocaleString()}`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    }

    // Event Handlers
    $('#category').change(function() {
        const selectedCategory = $(this).val();
        updateExpenseDropdown(selectedCategory);
        $('#filterForm').submit();
    });

    $('#expense_id').change(function() {
        $('#filterForm').submit();
    });

    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        window.location.href = 'expenses_tracking.php?' + formData;
    });

    // Helper Functions
    function numberFormat(number) {
        return new Intl.NumberFormat('en-KE', { 
            minimumFractionDigits: 2,
            maximumFractionDigits: 2 
        }).format(number);
    }

    // Initialize components
    if ($('#category').val() !== 'all') {
        updateExpenseDropdown($('#category').val());
    }
    initializeTrendChart();

    // Responsive sidebar handling
    $("#sidebarToggleTop").on('click', function(e) {
        $("body").toggleClass("sidebar-toggled");
        $(".sidebar").toggleClass("toggled");
        if ($(".sidebar").hasClass("toggled")) {
            $('.sidebar .collapse').collapse('hide');
            $("#content-wrapper").css({"margin-left": "100px", "width": "calc(100% - 100px)"});
            $(".topbar").css("left", "100px");
        } else {
            $("#content-wrapper").css({"margin-left": "225px", "width": "calc(100% - 225px)"});
            $(".topbar").css("left", "225px");
        }
    });

    // Handle window resize
    $(window).resize(function() {
        if ($(window).width() < 768) {
            $('.sidebar .collapse').collapse('hide');
        }
        
        if ($(window).width() < 480 && !$(".sidebar").hasClass("toggled")) {
            $("body").addClass("sidebar-toggled");
            $(".sidebar").addClass("toggled");
            $('.sidebar .collapse').collapse('hide');
        }
    });

    // Export functionality
    window.exportToExcel = function() {
        window.location.href = 'export_financial_report.php?' + 
            'start_date=' + $('#start_date').val() + '&' +
            'end_date=' + $('#end_date').val() + '&' +
            'category=' + $('#category').val();
    };
});
    </script>
</body>
</html>