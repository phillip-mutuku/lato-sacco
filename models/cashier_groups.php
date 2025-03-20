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

// Get selected filters
$selected_officer = isset($_GET['field_officer']) ? intval($_GET['field_officer']) : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Get field officers for filter
$officers_query = "SELECT user_id, firstname, lastname FROM user WHERE role = 'officer' ORDER BY firstname";
$officers_result = $db->conn->query($officers_query);

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

// Get total defaulters with date filter
$defaulters_query = "
    SELECT COUNT(DISTINCT l.account_id) as total_defaulters 
    FROM loan_schedule ls
    JOIN loan l ON ls.loan_id = l.loan_id
    JOIN client_accounts ca ON l.account_id = ca.account_id
    JOIN group_members gm ON ca.account_id = gm.account_id
    JOIN lato_groups g ON gm.group_id = g.group_id
    WHERE ls.default_amount > 0 
    AND ls.due_date < CURDATE()
    " . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "") 
    . ($from_date && $to_date ? " AND DATE(l.date_applied) BETWEEN '$from_date' AND '$to_date'" : "");
$total_defaulters = $db->conn->query($defaulters_query)->fetch_assoc()['total_defaulters'];

// Get total defaulted amount with date filter
$defaulted_amount_query = "
    SELECT COALESCE(SUM(ls.default_amount), 0) as total_defaulted
    FROM loan_schedule ls
    JOIN loan l ON ls.loan_id = l.loan_id
    JOIN client_accounts ca ON l.account_id = ca.account_id
    JOIN group_members gm ON ca.account_id = gm.account_id
    JOIN lato_groups g ON gm.group_id = g.group_id
    WHERE ls.default_amount > 0 
    AND ls.due_date < CURDATE()
    " . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "")
    . ($from_date && $to_date ? " AND DATE(l.date_applied) BETWEEN '$from_date' AND '$to_date'" : "");
$total_defaulted = $db->conn->query($defaulted_amount_query)->fetch_assoc()['total_defaulted'];

// Get total loans applied with date filter
$total_loans_query = "
    SELECT COALESCE(SUM(l.amount), 0) as total_loans
    FROM loan l
    JOIN client_accounts ca ON l.account_id = ca.account_id
    JOIN group_members gm ON ca.account_id = gm.account_id
    JOIN lato_groups g ON gm.group_id = g.group_id
    WHERE gm.status = 'active'
    " . ($selected_officer ? " AND g.field_officer_id = $selected_officer" : "")
    . ($from_date && $to_date ? " AND DATE(l.date_applied) BETWEEN '$from_date' AND '$to_date'" : "");
$total_loans = $db->conn->query($total_loans_query)->fetch_assoc()['total_loans'];

// Get performance metrics for all field officers with date filter
$performance_query = "
WITH OfficerMetrics AS (
    SELECT 
        u.user_id,
        u.firstname,
        u.lastname,
        COUNT(DISTINCT g.group_id) as total_groups,
        COALESCE(SUM(ls.default_amount), 0) as total_defaulted,
        COALESCE(SUM(l.amount), 0) as total_loans_applied,
        COUNT(DISTINCT l.account_id) as total_borrowers,
        COUNT(DISTINCT CASE WHEN ls.default_amount > 0 THEN l.account_id END) as defaulting_borrowers
    FROM user u
    LEFT JOIN lato_groups g ON u.user_id = g.field_officer_id
    LEFT JOIN group_members gm ON g.group_id = gm.group_id
    LEFT JOIN loan l ON gm.account_id = l.account_id AND l.status IN (1, 2)
    LEFT JOIN loan_schedule ls ON l.loan_id = ls.loan_id AND ls.default_amount > 0
    WHERE u.role = 'officer'
    " . ($from_date && $to_date ? " AND DATE(l.created_at) BETWEEN '$from_date' AND '$to_date'" : "") . "
    GROUP BY u.user_id, u.firstname, u.lastname
)
SELECT 
    user_id,
    firstname,
    lastname,
    total_groups,
    total_defaulted,
    total_loans_applied,
    total_borrowers,
    defaulting_borrowers
FROM OfficerMetrics
WHERE total_groups > 0
ORDER BY 
    CASE 
        WHEN total_borrowers = 0 THEN 2
        ELSE 1
    END,
    CASE 
        WHEN total_borrowers > 0 
        THEN (defaulting_borrowers * 1.0 / total_borrowers) + 
             (total_defaulted * 1.0 / NULLIF(total_loans_applied, 0))
        ELSE 999999
    END ASC
LIMIT 3";

// Alternative query for older MySQL versions
$alternative_performance_query = "
SELECT 
    u.user_id,
    u.firstname,
    u.lastname,
    COUNT(DISTINCT g.group_id) as total_groups,
    COALESCE(SUM(ls.default_amount), 0) as total_defaulted,
    COALESCE(SUM(l.amount), 0) as total_loans_applied,
    COUNT(DISTINCT l.account_id) as total_borrowers,
    COUNT(DISTINCT CASE WHEN ls.default_amount > 0 THEN l.account_id END) as defaulting_borrowers
FROM user u
LEFT JOIN lato_groups g ON u.user_id = g.field_officer_id
LEFT JOIN group_members gm ON g.group_id = gm.group_id
LEFT JOIN loan l ON gm.account_id = l.account_id AND l.status IN (1, 2)
LEFT JOIN loan_schedule ls ON l.loan_id = ls.loan_id AND ls.default_amount > 0
WHERE u.role = 'officer'
" . ($from_date && $to_date ? " AND DATE(l.created_at) BETWEEN '$from_date' AND '$to_date'" : "") . "
GROUP BY u.user_id, u.firstname, u.lastname
HAVING COUNT(DISTINCT g.group_id) > 0
ORDER BY 
    CASE 
        WHEN COUNT(DISTINCT l.account_id) = 0 THEN 2
        ELSE 1
    END,
    CASE 
        WHEN COUNT(DISTINCT l.account_id) > 0 
        THEN (COUNT(DISTINCT CASE WHEN ls.default_amount > 0 THEN l.account_id END) * 1.0 / 
              COUNT(DISTINCT l.account_id)) + 
             (COALESCE(SUM(ls.default_amount), 0) * 1.0 / 
              NULLIF(COALESCE(SUM(l.amount), 0), 0))
        ELSE 999999
    END ASC
LIMIT 3";

try {
    // Try the CTE version first
    $top_performers = $db->conn->query($performance_query);
    if (!$top_performers) {
        // If CTE version fails, use the alternative query
        $top_performers = $db->conn->query($alternative_performance_query);
    }
    $top_performers = $top_performers->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error in performance query: " . $e->getMessage());
    $top_performers = [];
}

// Query for groups listing with filters
$groups_list_query = "
    SELECT g.*, u.firstname, u.lastname 
    FROM lato_groups g 
    JOIN user u ON g.field_officer_id = u.user_id 
    " . $where_clause . "
    ORDER BY g.group_id DESC";
$groups_result = $db->conn->query($groups_list_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lato Management System - Group Management</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    <link href="../public/css/select2.css" rel="stylesheet">
    <style>
        .modal-lg { max-width: 80% !important; }
        .form-group label { font-weight: bold; }
        .select2-container { width: 100% !important; }

        .stats-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #51087E;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #51087E;
            margin-bottom: 5px;
        }
        .stats-label {
            color: #666;
            font-size: 1rem;
        }
        .performance-card {
            background: linear-gradient(45deg, #51087E, #224abe);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .performance-card h4 {
            margin: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .performance-metrics {
            padding-top: 10px;
        }
        .metric {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
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

        .container-fluid .card {
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }


    .btn-group {
        position: relative;
        display: inline-flex;
    }
    
    .dropdown-menu {
        min-width: 10rem;
        padding: 0.5rem 0;
        margin: 0.125rem 0 0;
        font-size: 0.875rem;
    }
    
    .dropdown-item {
        display: block;
        width: 100%;
        padding: 0.5rem 1rem;
        clear: both;
        font-weight: 400;
        color: #3a3b45;
        text-align: inherit;
        white-space: nowrap;
        background-color: transparent;
        border: 0;
    }
    
    .dropdown-item:hover, .dropdown-item:focus {
        color: #2e2f37;
        text-decoration: none;
        background-color: #f8f9fc;
    }
    
    .dropdown-divider {
        height: 0;
        margin: 0.5rem 0;
        overflow: hidden;
        border-top: 1px solid #e3e6f0;
    }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <ul style="background: #51087E;"  class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
                <div class="sidebar-brand-text mx-3">LATO SACCO</div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item">
                <a class="nav-link" href="../views/cashier.php">
                    <i class="fas fa-fw fa-home"></i>
                    <span>Home</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Management
            </div>

            <li class="nav-item">
                <a class="nav-link" href="cashier_disbursement.php">
                    <i class="fas fa-fw fas fa-coins"></i>
                    <span>Disbursements</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="../views/cashier-daily-reconciliation.php">
                    <i class="fas fa-fw fa-balance-scale"></i>
                    <span>Daily Reconciliation</span>
                </a>
            </li>


            <li class="nav-item active">
                <a class="nav-link" href="../views/cashier_manage_expenses.php">
                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    <span>Manage Expenses</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="cashier_arrears.php">
                <i class="fas fa-users-slash fa-2x text-gray-300"></i>
                    <span>Arrears</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../views/cashier-account.php">
                <i class="fas fa-fw fa-user"></i>
                    <span>Client Accounts</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="cashier_groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Wekeza Groups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="cashier_business_groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Business Groups</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                System
            </div>

            <li class="nav-item active">
                <a class="nav-link" href="../views/cashier_announcements.php">
                    <i class="fas fa-fw fa-bullhorn"></i>
                    <span>Announcements</span>
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
                                    <?php echo $db->user_acc($_SESSION['user_id']); ?>
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
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid pt-4">
        <!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-md-3">
                <div class="form-group mb-0">
                    <label>Field Officer:</label>
                    <select name="field_officer" class="form-control">
                        <option value="">All Field Officers</option>
                        <?php while ($officer = $officers_result->fetch_assoc()): ?>
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
                    <label>From Date:</label>
                    <input type="date" name="from_date" class="form-control" 
                           value="<?php echo $_GET['from_date'] ?? ''; ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-0">
                    <label>To Date:</label>
                    <input type="date" name="to_date" class="form-control" 
                           value="<?php echo $_GET['to_date'] ?? ''; ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-0">
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

        <!-- Updated Statistics Cards Styling -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
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

    <div class="col-xl-3 col-md-6">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Total Defaulters</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($total_defaulters); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users-slash fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Defaulted Amount</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            KSh <?php echo number_format($total_defaulted, 2); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
    <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                        Total Outstanding Loans</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                        KSh <?php echo number_format($total_loans, 2); ?>
                    </div>
                </div>
                <div class="col-auto">
                    <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                </div>
            </div>
        </div>
    </div>
</div>
</div>


 <!-- Top Performers Section -->
<div style="display: none;" class="card mb-4">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold" style="color: #51087E;">
            <i class="fas fa-trophy mr-2"></i>Top Performing Field Officers
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($top_performers as $index => $performer): 
                $default_rate = $performer['total_borrowers'] > 0 ? 
                    ($performer['defaulting_borrowers'] / $performer['total_borrowers'] * 100) : 0;
                $performance_score = 100 - $default_rate;
            ?>
                <div class="col-md-4">
                    <div class="card mb-3" style="background: linear-gradient(45deg, #51087E, #224abe); color: white;">
                        <div class="card-body position-relative">
                            <div class="position-absolute" style="top: 10px; right: 10px;">
                                <?php if($index === 0): ?>
                                    <span style="font-size: 2em;">🏆</span>
                                <?php elseif($index === 1): ?>
                                    <span style="font-size: 2em;">🥈</span>
                                <?php else: ?>
                                    <span style="font-size: 2em;">🥉</span>
                                <?php endif; ?>
                            </div>
                            
                            <h5 class="card-title mb-4">
                                <?php echo htmlspecialchars($performer['firstname'] . ' ' . $performer['lastname']); ?>
                            </h5>
                            
                            <div class="metric-row mb-2 d-flex justify-content-between">
                                <span><i class="fas fa-users mr-2"></i>Groups:</span>
                                <strong><?php echo number_format($performer['total_groups']); ?></strong>
                            </div>
                            
                            <div class="metric-row mb-2 d-flex justify-content-between">
                                <span><i class="fas fa-user-friends mr-2"></i>Total Borrowers:</span>
                                <strong><?php echo number_format($performer['total_borrowers']); ?></strong>
                            </div>
                            
                            <div class="metric-row mb-2 d-flex justify-content-between">
                                <span><i class="fas fa-exclamation-triangle mr-2"></i>Default Rate:</span>
                                <strong><?php echo number_format($default_rate, 1); ?>%</strong>
                            </div>
                            
                            <div class="mt-3 pt-3 border-top" style="border-color: rgba(255,255,255,0.2);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Performance Score:</span>
                                    <strong style="font-size: 1.2em;"><?php echo number_format($performance_score, 0); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>



                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Manage Wekeza Groups</h1>
                        <button class="btn btn-warning" data-toggle="modal" data-target="#addGroupModal">
                            <i class="fas fa-plus"></i> Add New Group
                        </button>
                    </div>

                    <!-- Groups Table Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Group Reference</th>
                                            <th>Group Name</th>
                                            <th>Area</th>
                                            <th>Field Officer</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT g.*, u.firstname, u.lastname 
                                                 FROM lato_groups g 
                                                 JOIN user u ON g.field_officer_id = u.user_id 
                                                 ORDER BY g.group_id DESC";
                                        $result = $db->conn->query($query);
                                        $i = 1;
                                        while ($row = $result->fetch_assoc()) {
                                        ?>
                                        <tr>
                                            <td><?php echo $i++; ?></td>
                                            <td><?php echo $row['group_reference']; ?></td>
                                            <td><?php echo $row['group_name']; ?></td>
                                            <td><?php echo $row['area']; ?></td>
                                            <td><?php echo $row['firstname'] . ' ' . $row['lastname']; ?></td>
                                            <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                                    Action
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="manage_group.php?id=<?php echo $row['group_id']; ?>">
                                                        <i class="fas fa-users fa-fw"></i> Manage Group
                                                    </a>
                                                    <button type="button" class="dropdown-item edit-group" data-id="<?php echo $row['group_id']; ?>">
                                                        <i class="fas fa-edit fa-fw"></i> Edit
                                                    </button>
                                                    <button style="display: none;" type="button" class="dropdown-item delete-group" 
                                                            data-id="<?php echo $row['group_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($row['group_name']); ?>">
                                                        <i class="fas fa-trash fa-fw"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
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
        </div>
    </div>

    <!-- Add Group Modal -->
<div class="modal fade" id="addGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #51087E;">
                <h5 class="modal-title text-white">Add New Group</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addGroupForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Group Reference</label>
                                <div class="input-group">
                                    <input type="text" name="group_reference" class="form-control" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="suggestReference">
                                            <i class="fas fa-sync-alt"></i> Suggest
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Format: wekeza-001, wekeza-002, etc.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Group Name</label>
                                <input type="text" name="group_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Area</label>
                                <input type="text" name="area" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Field Officer</label>
                                <select name="field_officer_id" class="form-control" required>
                                    <option value="">Select Field Officer</option>
                                    <?php
                                    $officers_query = "SELECT user_id, firstname, lastname FROM user WHERE role = 'officer' ORDER BY firstname";
                                    $officers_result = $db->conn->query($officers_query);
                                    while ($officer = $officers_result->fetch_assoc()) {
                                        echo "<option value='" . $officer['user_id'] . "'>" . 
                                             $officer['firstname'] . " " . $officer['lastname'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Save Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Group Modal -->
<div class="modal fade" id="editGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #51087E;">
                <h5 class="modal-title text-white">Edit Group</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editGroupForm">
                <input type="hidden" name="group_id" id="edit_group_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Group Reference</label>
                                <div class="input-group">
                                    <input type="text" name="group_reference" class="form-control" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="editSuggestReference">
                                            <i class="fas fa-sync-alt"></i> Suggest
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Format: wekeza-001, wekeza-002, etc.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Group Name</label>
                                <input type="text" name="group_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Area</label>
                                <input type="text" name="area" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Field Officer</label>
                                <select name="field_officer_id" class="form-control" required>
                                    <option value="">Select Field Officer</option>
                                    <?php
                                    $officers_result->data_seek(0); // Reset the pointer
                                    while ($officer = $officers_result->fetch_assoc()) {
                                        echo "<option value='" . $officer['user_id'] . "'>" . 
                                             $officer['firstname'] . " " . $officer['lastname'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Group Modal -->
<div class="modal fade" id="deleteGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">Confirm Deletion</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Content will be dynamically inserted -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">Ready to Leave?</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Select "Logout" below if you are ready to end your current session.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger" href="../views/logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>

    <!-- Scripts -->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>
    <script src="../public/js/jquery.easing.js"></script>
    <script src="../public/js/select2.js"></script>
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>
    <script src="../public/js/sb-admin-2.js"></script>

    <script>

    $(document).ready(function() {
   // Initialize Select2 for field officer dropdowns
   $('select[name="field_officer_id"]').select2({
        placeholder: 'Select Field Officer',
        width: '100%',
        dropdownParent: $('#addGroupModal')
    });

    $('#editGroupForm select[name="field_officer_id"]').select2({
        placeholder: 'Select Field Officer',
        width: '100%',
        dropdownParent: $('#editGroupModal')
    });

    // Reference suggestion handlers
    function getNextReference(input) {
        $.ajax({
            url: '../controllers/groupController.php',
            method: 'GET',
            data: { action: 'getNextReference' },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.status === 'success' && result.reference) {
                        input.val(result.reference);
                    } else {
                        showAlert('error', 'Error getting reference number');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    showAlert('error', 'Error processing reference number');
                }
            },
            error: function() {
                showAlert('error', 'Error fetching reference number');
            }
        });
    }

    $('#suggestReference').click(function(e) {
        e.preventDefault();
        getNextReference($('#addGroupForm input[name="group_reference"]'));
    });

    $('#editSuggestReference').click(function(e) {
        e.preventDefault();
        getNextReference($('#editGroupForm input[name="group_reference"]'));
    });

    // Form validation
    function validateReferenceFormat(reference) {
        return /^wekeza-\d{3}$/.test(reference);
    }

    function validateForm(form) {
        let isValid = true;
        const reference = form.find('input[name="group_reference"]').val();

        if (!validateReferenceFormat(reference)) {
            showAlert('error', 'Invalid reference format. Please use format: wekeza-XXX (e.g., wekeza-001)');
            isValid = false;
        }

        form.find('input[required], select[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        return isValid;
    }

    // Add Group Form Handler
    $('#addGroupForm').on('submit', function(e) {
        e.preventDefault();
        if (!validateForm($(this))) return;

        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '../controllers/groupController.php',
            method: 'POST',
            data: $(this).serialize() + '&action=create',
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.status === 'success') {
                        showAlert('success', result.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('error', result.message);
                        submitBtn.prop('disabled', false).text('Save Group');
                    }
                } catch (e) {
                    showAlert('error', 'Error processing response');
                    submitBtn.prop('disabled', false).text('Save Group');
                }
            },
            error: function() {
                showAlert('error', 'Error saving group');
                submitBtn.prop('disabled', false).text('Save Group');
            }
        });
    });

    // Edit Group Form Handler
    $('#editGroupForm').on('submit', function(e) {
        e.preventDefault();
        if (!validateForm($(this))) return;

        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

        $.ajax({
            url: '../controllers/groupController.php',
            method: 'POST',
            data: $(this).serialize() + '&action=update',
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.status === 'success') {
                        showAlert('success', result.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('error', result.message);
                        submitBtn.prop('disabled', false).text('Update Group');
                    }
                } catch (e) {
                    showAlert('error', 'Error processing response');
                    submitBtn.prop('disabled', false).text('Update Group');
                }
            },
            error: function() {
                showAlert('error', 'Error updating group');
                submitBtn.prop('disabled', false).text('Update Group');
            }
        });
    });

    // Edit Group Button Click Handler
    $('.edit-group').click(function() {
        const groupId = $(this).data('id');
        $.ajax({
            url: '../controllers/groupController.php',
            method: 'POST',
            data: {
                action: 'get',
                group_id: groupId
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.status === 'success') {
                        const group = result.data;
                        $('#edit_group_id').val(group.group_id);
                        $('#editGroupForm input[name="group_reference"]').val(group.group_reference);
                        $('#editGroupForm input[name="group_name"]').val(group.group_name);
                        $('#editGroupForm input[name="area"]').val(group.area);
                        $('#editGroupForm select[name="field_officer_id"]')
                            .val(group.field_officer_id)
                            .trigger('change');
                        $('#editGroupModal').modal('show');
                    } else {
                        showAlert('error', result.message || 'Error fetching group details');
                    }
                } catch (e) {
                    showAlert('error', 'Error processing group details');
                }
            },
            error: function() {
                showAlert('error', 'Error fetching group details');
            }
        });
    });

    // Delete Group Button Click Handler
    $('.delete-group').click(function() {
        const groupId = $(this).data('id');
        const groupName = $(this).data('name');
        $('#deleteGroupModal .modal-body').html(
            `Are you sure you want to delete this group?<br><br>
            <strong>Group:</strong> ${groupName}<br><br>
            This action cannot be undone.`
        );
        $('#confirmDelete').data('id', groupId);
        $('#deleteGroupModal').modal('show');
    });

    // Confirm Delete Handler
    $('#confirmDelete').click(function() {
        const groupId = $(this).data('id');
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');

        $.ajax({
            url: '../controllers/groupController.php',
            method: 'POST',
            data: {
                action: 'delete',
                group_id: groupId
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.status === 'success') {
                        showAlert('success', result.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('error', result.message);
                        btn.prop('disabled', false).text('Delete');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    showAlert('error', 'Error processing response');
                    btn.prop('disabled', false).text('Delete');
                }
            },
            error: function() {
                showAlert('error', 'Error deleting group');
                btn.prop('disabled', false).text('Delete');
            }
        });
    });

    // Modal Reset Handlers
    $('#addGroupModal').on('hidden.bs.modal', function() {
        $('#addGroupForm').trigger('reset');
        $('#addGroupForm').find('select').val('').trigger('change');
        $('#addGroupForm').find('.is-invalid').removeClass('is-invalid');
    });

    $('#editGroupModal').on('hidden.bs.modal', function() {
        $('#editGroupForm').find('.is-invalid').removeClass('is-invalid');
    });

    // Logout Modal Handler
    $('[data-target="#logoutModal"]').click(function(e) {
        e.preventDefault();
        $('#logoutModal').modal('show');
    });

    // Helper Functions
    function showAlert(type, message) {
        const alertDiv = $('<div>')
            .addClass('alert alert-' + (type === 'success' ? 'success' : 'danger') + ' alert-dismissible fade show')
            .css({
                'position': 'fixed',
                'top': '20px',
                'right': '20px',
                'z-index': '9999',
                'min-width': '300px',
                'box-shadow': '0 0 10px rgba(0,0,0,0.2)'
            })
            .html(`
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            `);
        
        $('body').append(alertDiv);
        setTimeout(() => alertDiv.alert('close'), 5000);
    }

    // Reference number validation on input
    $('input[name="group_reference"]').on('input', function() {
        const input = $(this);
        const reference = input.val().trim();
        
        if (reference && !reference.match(/^wekeza-\d{3}$/)) {
            input.addClass('is-invalid');
            if (!input.next('.invalid-feedback').length) {
                input.after('<div class="invalid-feedback">Reference must be in format wekeza-XXX (e.g., wekeza-001)</div>');
            }
        } else {
            input.removeClass('is-invalid');
            input.next('.invalid-feedback').remove();
        }
    });

    // Initialize tooltips and popovers if using them
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();

    // Handle modal backdrop issues
    $('.modal').on('show.bs.modal', function () {
        if ($('.modal-backdrop').length === 0) {
            $('body').append('<div class="modal-backdrop fade show"></div>');
        }
    }).on('hidden.bs.modal', function () {
        if ($('.modal:visible').length === 0) {
            $('.modal-backdrop').remove();
        }
    });

    // Add keyboard shortcuts (optional)
    $(document).keydown(function(e) {
        // Escape key closes modals
        if (e.keyCode === 27) {
            $('.modal').modal('hide');
        }
        
        // Enter key in reference field moves to next field
        if (e.keyCode === 13 && $(document.activeElement).attr('name') === 'group_reference') {
            e.preventDefault();
            $(document.activeElement).closest('.row').find('input[name="group_name"]').focus();
        }
    });

    // Enhance Select2 dropdown with search
    $('select[name="field_officer_id"]').select2({
        placeholder: 'Select Field Officer',
        width: '100%',
        dropdownParent: $('#addGroupModal'),
        matcher: function(params, data) {
            // If there are no search terms, return all of the data
            if ($.trim(params.term) === '') {
                return data;
            }

            // Do not display the item if there is no 'text' property
            if (typeof data.text === 'undefined') {
                return null;
            }

            // `params.term` should be the term that is used for searching
            // `data.text` is the text that is displayed for the data object
            if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                return data;
            }

            // Return `null` if the term should not be displayed
            return null;
        }
    });

    // Add loading overlay for long operations
    function showLoading() {
        const overlay = $('<div>').addClass('loading-overlay').css({
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(0,0,0,0.5)',
            zIndex: 9999,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center'
        }).append(
            $('<div>').addClass('spinner-border text-light').css({
                width: '3rem',
                height: '3rem'
            })
        );
        $('body').append(overlay);
    }

    function hideLoading() {
        $('.loading-overlay').remove();
    }



  // Initialize all dropdowns
    $('.dropdown-toggle').dropdown();

    // Initialize all Bootstrap modals
    $('.modal').modal({
        show: false,
        backdrop: 'static',
        keyboard: false
    });

    // Logout handler
    $('[data-target="#logoutModal"]').click(function(e) {
        e.preventDefault();
        handleModal('logoutModal');
    });



                // Ensure Bootstrap modal backdrop is properly handled
                $('.modal').on('show.bs.modal', function () {
                    if ($('.modal-backdrop').length === 0) {
                        $('body').append('<div class="modal-backdrop fade show"></div>');
                    }
                }).on('hidden.bs.modal', function () {
                    $('.modal-backdrop').remove();
                });

        // Sidebar Toggle Handler
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

        // Responsive handlers
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

    

});
    </script>
</body>
</html>