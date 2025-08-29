<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../config/config.php';
require_once '../controllers/groupController.php';
require_once '../helpers/session.php';
require_once '../config/class.php';
$db = new db_class(); 

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'officer') {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: ../views/index.php');
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$groupController = new GroupController();

// Initialize variables
$groupId = $_GET['id'] ?? null;
$groupDetails = null;
$members = [];
$transactions = [];
$savings = [];
$withdrawals = [];
$totalSavings = 0;
$totalWithdrawals = 0;
$netBalance = 0;
$error = null;

if ($groupId) {
    try {
        $groupDetails = $groupController->getGroupById($groupId);
        if (!$groupDetails) {
            throw new Exception("Group not found.");
        }
        $members = $groupController->getGroupMembers($groupId);
        $transactions = $groupController->getGroupTransactions($groupId);
        $savings = $groupController->getGroupSavings($groupId);
        $withdrawals = $groupController->getGroupWithdrawals($groupId);
        $totalSavings = $groupController->getTotalGroupSavings($groupId);
        $totalWithdrawals = $groupController->getTotalGroupWithdrawals($groupId);
        $netBalance = $totalSavings - $totalWithdrawals;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Function to safely encode JSON for JavaScript
function safeJsonEncode($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

// Function to group data by month
function groupByMonth($data, $dateKey, $valueKey) {
    $grouped = [];
    foreach ($data as $item) {
        $month = date('Y-m', strtotime($item[$dateKey]));
        if (!isset($grouped[$month])) {
            $grouped[$month] = 0;
        }
        $grouped[$month] += $item[$valueKey];
    }
    ksort($grouped);
    return $grouped;
}

// Prepare data for charts
$monthlySavings = groupByMonth($savings, 'date_saved', 'amount');
$monthlyWithdrawals = groupByMonth($withdrawals, 'date_withdrawn', 'amount');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Details - Lato Management System</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    <link href="../public/css/select2.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { 
            background-color: #f8f9fc; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container-fluid {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .card {
            border: 0;
            border-radius: 5px;
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .card-header {
            color: #51087E;
            font-weight: bold;
        }
        .summary-card {
            color: white;
            border-radius: 12px;
            padding: 20px;
            height: 100%;
            transition: all 0.3s ease;
        }
        .summary-card:hover { 
            transform: translateY(-5px);
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
        }
        .summary-card h4 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            font-style: italic;
        }
        .summary-card p {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0;
            opacity: 0.9;
        }
        .members-card { background: linear-gradient(45deg, #51087E, #224abe); }
        .savings-card { background: linear-gradient(45deg, #1cc88a, #13855c); }
        .withdrawals-card { background: linear-gradient(45deg, #f6c23e, #dda20a); }
        .balance-card { background: linear-gradient(45deg, #36b9cc, #258391); }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
        }
        .topbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 1000;
        }
        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        .member-action-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }
        .modal-lg {
            max-width: 80% !important;
        }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <!-- Back to Groups Button -->
                    <a href="officer_groups.php" style="background-color: #51087E; color: white;" class="btn btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Groups
                    </a>

                    <!-- Topbar Navbar -->
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
                <div class="container-fluid">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            Error: <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($groupDetails): ?>
                        <h1 class="mb-4 text-black bold"><?= htmlspecialchars($groupDetails['group_name']) ?></h1>
                        
                        <!-- Group Summary -->
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <div class="summary-card members-card">
                                    <h4>Members</h4>
                                    <p><?= count($members) ?></p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="summary-card savings-card">
                                    <h4>Total Savings</h4>
                                    <p>KSh <?= number_format($totalSavings, 2) ?></p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="summary-card withdrawals-card">
                                    <h4>Total Withdrawals</h4>
                                    <p>KSh <?= number_format($totalWithdrawals, 2) ?></p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="summary-card balance-card">
                                    <h4>Net Balance</h4>
                                    <p>KSh <?= number_format($netBalance, 2) ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <!-- Group Information -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="m-0 font-weight-bold">Group Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Group Name:</strong> <?= htmlspecialchars($groupDetails['group_name']) ?></p>
                                                <p><strong>Reference:</strong> <?= htmlspecialchars($groupDetails['group_reference']) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Area:</strong> <?= htmlspecialchars($groupDetails['area']) ?></p>
                                                <p><strong>Field Officer:</strong> <?= htmlspecialchars($groupDetails['firstname'] . ' ' . $groupDetails['lastname']) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Group Performance Chart -->
                                <div style="display: none;" class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="m-0 font-weight-bold">Group Performance</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="groupPerformanceChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <div style="display: none;" class="col-md-6">
                                <!-- Savings Trend Chart -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="m-0 font-weight-bold">Savings Trend</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="savingsTrendChart"></canvas>
                                    </div>
                                </div>

                                <!-- Transaction Distribution Chart -->
                                <div style="display: none;" class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="m-0 font-weight-bold">Transaction Distribution</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="transactionDistributionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Group Members Section -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="m-0 font-weight-bold">Group Members</h5>
                                <button class="btn btn-success" data-toggle="modal" data-target="#addMemberModal">
                                    <i class="fas fa-user-plus"></i> Add Member
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="membersTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Member Name</th>
                                                <th>Shareholder No</th>
                                                <th>Phone Number</th>
                                                <th>Location</th>
                                                <th>Date Joined</th>
                                                <th>Total Savings</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($members as $member): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></td>
                                                    <td><?= htmlspecialchars($member['shareholder_no']) ?></td>
                                                    <td><?= htmlspecialchars($member['phone_number']) ?></td>
                                                    <td><?= htmlspecialchars($member['location']) ?></td>
                                                    <td><?= date("Y-m-d", strtotime($member['date_joined'])) ?></td>
                                                    <td>KSh <?= number_format($member['total_savings'], 2) ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <button class="btn btn-sm btn-primary view-member" 
                                                                    data-member-id="<?= $member['account_id'] ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger remove-member" 
                                                                    data-member-id="<?= $member['account_id'] ?>"
                                                                    data-member-name="<?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>">
                                                                <i class="fas fa-user-minus"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Group Savings Section -->
                        <div style="display: none;" class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="m-0 font-weight-bold">Savings and Withdrawals</h5>
                                <div>
                                    <button class="btn btn-success" data-toggle="modal" data-target="#addSavingsModal">
                                        <i class="fas fa-plus"></i> Add Savings
                                    </button>
                                    <button class="btn btn-warning" data-toggle="modal" data-target="#withdrawModal">
                                        <i class="fas fa-minus"></i> Withdraw
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="savingsTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Member</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Payment Mode</th>
                                                <th>Served By</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $allTransactions = array_merge(
                                                array_map(function($s) { 
                                                    return array_merge($s, ['type' => 'Savings']); 
                                                }, $savings),
                                                array_map(function($w) { 
                                                    return array_merge($w, ['type' => 'Withdrawal']); 
                                                }, $withdrawals)
                                            );
                                            usort($allTransactions, function($a, $b) {
                                                return strtotime($b['date']) - strtotime($a['date']);
                                            });
                                            foreach ($allTransactions as $transaction):
                                            ?>
                                                <tr>
                                                    <td><?= date("Y-m-d H:i", strtotime($transaction['date'])) ?></td>
                                                    <td><?= htmlspecialchars($transaction['member_name']) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $transaction['type'] === 'Savings' ? 'success' : 'warning' ?>">
                                                            <?= $transaction['type'] ?>
                                                        </span>
                                                    </td>
                                                    <td>KSh <?= number_format($transaction['amount'], 2) ?></td>
                                                    <td><?= htmlspecialchars($transaction['payment_mode']) ?></td>
                                                    <td><?= htmlspecialchars($transaction['served_by_name']) ?></td>
                                                    <td>
                                                        <button style="background-color: #51087E; color: white;" 
                                                                class="btn btn-sm print-receipt" 
                                                                data-id="<?= $transaction['id'] ?>"
                                                                data-type="<?= $transaction['type'] ?>">
                                                            <i class="fas fa-print"></i> Print Receipt
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Transactions History -->
                        <div style="display: none;" class="card mb-4">
                            <div class="card-header">
                                <h5 class="m-0 font-weight-bold">Transaction History</h5>
                            </div>
                            <div class="card-body">
                                <div class="card mb-4">
                                    <div class="card-header">
                                    </div>
                                    <div class="card-body">
                                        <form id="statementForm" class="row align-items-end">
                                            <div class="col-md-4">
                                                <label>From Date</label>
                                                <input type="date" name="from_date" class="form-control" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label>To Date</label>
                                                <input type="date" name="to_date" class="form-control" required>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-warning" id="printStatement">
                                                    <i class="fas fa-print"></i> Print Statement
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="transactionTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Member</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Receipt no</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?= date("Y-m-d", strtotime($transaction['date'])) ?></td>
                                            <td><?= htmlspecialchars($transaction['member_name']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= strpos($transaction['type'], 'Savings') !== false ? 'success' : 'warning' ?>">
                                                    <?= htmlspecialchars($transaction['type']) ?>
                                                </span>
                                            </td>
                                            <td>KSh <?= number_format($transaction['amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($transaction['receipt_no'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($transaction['description']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
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



<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #51087E;">
                <h5 class="modal-title text-white">Add New Member</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addMemberForm">
                <div class="modal-body">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <div class="form-group">
                        <label>Select Client</label>
                        <select class="form-control select2-clients" name="account_id" style="width: 100%">
                            <option value="">Search by name or shareholder number...</option>
                            <?php
                            // Fetch clients who aren't in any group
                            $query = "SELECT a.* FROM client_accounts a 
                                    LEFT JOIN group_members m ON a.account_id = m.account_id
                                    WHERE m.group_id IS NULL OR m.status = 'inactive'
                                    ORDER BY a.first_name, a.last_name";
                            $result = $db->conn->query($query);
                            while ($client = $result->fetch_assoc()) {
                                echo "<option value='" . $client['account_id'] . "' 
                                      data-shareholder='" . $client['shareholder_no'] . "'
                                      data-phone='" . $client['phone_number'] . "'
                                      data-location='" . $client['location'] . "'
                                      data-division='" . $client['division'] . "'
                                      data-village='" . $client['village'] . "'>";
                                echo $client['first_name'] . ' ' . $client['last_name'] . 
                                     ' (' . $client['shareholder_no'] . ')';
                                echo "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div id="clientDetails" style="display: none;">
                        <h6 class="font-weight-bold mt-3">Selected Client Details:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Shareholder No:</strong> <span id="clientShareholderNo"></span></p>
                                <p><strong>Phone:</strong> <span id="clientPhone"></span></p>
                                <p><strong>Location:</strong> <span id="clientLocation"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Division:</strong> <span id="clientDivision"></span></p>
                                <p><strong>Village:</strong> <span id="clientVillage"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Savings Modal -->
<div class="modal fade" id="addSavingsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #51087E;">
                <h5 class="modal-title text-white">Add Savings</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addSavingsForm">
                <div class="modal-body">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <div class="form-group">
                        <label>Receipt Number</label>
                        <input type="text" name="receipt_no" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Select Member</label>
                        <select name="account_id" class="form-control member-select" required>
                            <option value="">Select member...</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?= $member['account_id'] ?>">
                                    <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" name="amount" class="form-control" required step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Payment Mode</label>
                        <select name="payment_mode" class="form-control" required>
                            <option value="Cash">Cash</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Withdraw Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #51087E;">
                <h5 class="modal-title text-white">Withdraw Savings</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="withdrawForm">
                <div class="modal-body">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <div class="form-group">
                        <label>Receipt Number</label>
                        <input type="text" name="receipt_no" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Select Member</label>
                        <select name="account_id" class="form-control member-select" required>
                            <option value="">Select member...</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?= $member['account_id'] ?>" 
                                        data-balance="<?= $member['total_savings'] ?>">
                                    <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Available Balance</label>
                        <input type="text" id="availableBalance" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Withdrawal Amount</label>
                        <input type="number" name="amount" class="form-control" required step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Payment Mode</label>
                        <select name="payment_mode" class="form-control" required>
                            <option value="Cash">Cash</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning">Withdraw</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Remove Member Modal -->
    <div class="modal fade" id="removeMemberModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">Remove Member</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to remove <span id="memberToRemove"></span> from the group?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmRemove">Remove</button>
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
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
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
        // Initialize DataTables
        $('#membersTable').DataTable();
        $('#savingsTable').DataTable();
        $('#transactionTable').DataTable();



    // Initialize Select2 for client selection
    $('.select2-clients').select2({
        placeholder: 'Search by name or shareholder number',
        width: '100%',
        dropdownParent: $('#addMemberModal')
    }).on('select2:select', function(e) {
        var option = $(this).find(':selected');
        
        // Display client details
        $('#clientShareholderNo').text(option.data('shareholder'));
        $('#clientPhone').text(option.data('phone'));
        $('#clientLocation').text(option.data('location'));
        $('#clientDivision').text(option.data('division'));
        $('#clientVillage').text(option.data('village'));
        $('#clientDetails').show();
    });


        // Auto-fill balance when member is selected in withdrawal form
        $('#withdrawModal select[name="account_id"]').change(function() {
        var selectedOption = $(this).find('option:selected');
        var balance = selectedOption.data('balance') || 0;
        $('#availableBalance').val('KSh ' + balance.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
    });


// Print Receipt functionality
$('.print-receipt').click(function() {
    var id = $(this).data('id');
    var type = $(this).data('type');
    $.ajax({
        url: '../controllers/groupController.php',
        type: 'POST',
        data: {
            action: 'getReceiptDetails',
            id: id,
            type: type
        },
        success: function(response) {
            try {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.status === 'success') {
                    var receiptWindow = window.open('', '_blank');
                    var receiptContent = generateReceiptHTML(data.data, type);
                    receiptWindow.document.write(receiptContent);
                    receiptWindow.document.close();
                    setTimeout(function() {
                        receiptWindow.print();
                    }, 500);
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                alert('Error generating receipt');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('Error generating receipt');
        }
    });
});



//print statement
$('#printStatement').click(function() {
    const fromDate = $('input[name="from_date"]').val();
    const toDate = $('input[name="to_date"]').val();

    if (!fromDate || !toDate) {
        alert('Please select both start and end dates');
        return;
    }

    console.log("Sending request with:", {
        group_id: <?php echo $groupId; ?>,
        from_date: fromDate,
        to_date: toDate
    });

    $.ajax({
        url: '../controllers/groupController.php',
        method: 'POST',
        data: {
            action: 'getStatementData',
            group_id: <?php echo $groupId; ?>,
            from_date: fromDate,
            to_date: toDate
        },
        dataType: 'json',
        success: function(response) {
            console.log('Server Response:', response);
            
            if (response.status === 'success' && response.data) {
                const statementWindow = window.open('', '_blank');
                const statementContent = generateStatementHTML(response.data, fromDate, toDate);
                statementWindow.document.write(statementContent);
                statementWindow.document.close();
                setTimeout(() => {
                    statementWindow.print();
                }, 500);
            } else {
                alert('Error: ' + (response.message || 'Failed to generate statement'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {
                xhr: xhr.responseText,
                status: status,
                error: error
            });
            alert('Error generating statement. Check console for details.');
        }
    });
});








        // Handle member addition
$('#addMemberForm').on('submit', function(e) {
    e.preventDefault();
    
    var accountId = $('.select2-clients').val();
    if (!accountId) {
        alert('Please select a client first');
        return;
    }

    $.ajax({
        url: '../controllers/groupController.php',
        method: 'POST',
        data: {
            action: 'addMember',
            group_id: <?= $groupId ?>,
            account_id: accountId
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                $('#addMemberModal').modal('hide');
                showMessage('Member added successfully', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showMessage('Error: ' + response.message, 'error');
            }
        },
        error: function() {
            showMessage('Error adding member. Please try again.', 'error');
        }
    });
});


// Reset form and details when modal is closed
$('#addMemberModal').on('hidden.bs.modal', function() {
    $(this).find('form')[0].reset();
    $('.select2-clients').val(null).trigger('change');
    $('#clientDetails').hide();
});


// Helper function to show messages
function showMessage(message, type) {
    var messageDiv = $('<div>')
        .addClass('alert')
        .addClass(type === 'success' ? 'alert-success' : 'alert-danger')
        .text(message)
        .css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'z-index': 9999,
            'padding': '15px',
            'border-radius': '5px',
            'box-shadow': '0 0 10px rgba(0,0,0,0.2)'
        });

    $('body').append(messageDiv);

    setTimeout(function() {
        messageDiv.fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
}





        // Remove Member Click
        $('.remove-member').click(function() {
            var memberId = $(this).data('member-id');
            var memberName = $(this).data('member-name');
            $('#memberToRemove').text(memberName);
            $('#confirmRemove').data('id', memberId);
            $('#removeMemberModal').modal('show');
        });

        // Confirm Remove Member
        $('#confirmRemove').click(function() {
            var memberId = $(this).data('id');
            $.ajax({
                url: '../controllers/groupController.php',
                method: 'POST',
                data: {
                    action: 'removeMember',
                    group_id: <?= $groupId ?>,
                    account_id: memberId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error removing member');
                }
            });
        });

        // Receipt number validation
        function validateReceiptNumber(input) {
            const value = input.val().trim();
            const pattern = /^[A-Za-z0-9\-_.]+$/;
            
            if (value && !pattern.test(value)) {
                input.addClass('is-invalid');
                if (!input.next('.invalid-feedback').length) {
                    input.after('<div class="invalid-feedback">Use only letters, numbers, hyphens, underscores, and dots</div>');
                }
                return false;
            } else {
                input.removeClass('is-invalid');
                input.next('.invalid-feedback').remove();
                return true;
            }
        }

        // Apply validation on input
        $('#addSavingsForm input[name="receipt_no"], #withdrawForm input[name="receipt_no"]').on('input', function() {
            validateReceiptNumber($(this));
        });

        // Validate before form submission
        $('#addSavingsForm, #withdrawForm').on('submit', function(e) {
            const receiptInput = $(this).find('input[name="receipt_no"]');
            if (!validateReceiptNumber(receiptInput)) {
                e.preventDefault();
                alert('Please enter a valid receipt number (letters, numbers, hyphens, underscores, and dots only)');
                return false;
            }
        });



        // Add Savings Form Submit
    $('#addSavingsForm').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&action=addSavings';
        $.ajax({
            url: '../controllers/groupController.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('Savings added successfully');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error adding savings');
            }
        });
    });

    $('#withdrawForm').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&action=withdraw';
        $.ajax({
            url: '../controllers/groupController.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('Withdrawal processed successfully');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error processing withdrawal');
            }
        });
    });



        // Update available balance on member select for withdrawal
        $('select[name="account_id"]').change(function() {
            var balance = $(this).find(':selected').data('balance') || 0;
            $('#availableBalance').val('KSh ' + balance.toFixed(2));
        });

        // Initialize Charts
        var performanceCtx = document.getElementById('groupPerformanceChart').getContext('2d');
        new Chart(performanceCtx, {
            type: 'bar',
            data: {
                labels: ['Total Savings', 'Total Withdrawals', 'Net Balance'],
                datasets: [{
                    label: 'Group Performance',
                    data: [
                        <?= $totalSavings ?>,
                        <?= $totalWithdrawals ?>,
                        <?= $netBalance ?>
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KSh ' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': KSh ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Savings Trend Chart
        var savingsTrendCtx = document.getElementById('savingsTrendChart').getContext('2d');
        var savingsTrendData = <?= safeJsonEncode($monthlySavings) ?>;
        new Chart(savingsTrendCtx, {
            type: 'line',
            data: {
                labels: Object.keys(savingsTrendData),
                datasets: [{
                    label: 'Monthly Savings',
                    data: Object.values(savingsTrendData),
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KSh ' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Savings: KSh ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Transaction Distribution Chart
        var transactionCtx = document.getElementById('transactionDistributionChart').getContext('2d');
        new Chart(transactionCtx, {
            type: 'pie',
            data: {
                labels: ['Savings', 'Withdrawals'],
                datasets: [{
                    data: [<?= $totalSavings ?>, <?= $totalWithdrawals ?>],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(255, 99, 132, 0.6)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.parsed || 0;
                                return label + ': KSh ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });


        //print receipt
        function generateReceiptHTML(data, type) {
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>${type} Receipt</title>
            <style>
                body { 
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    padding: 20px;
                }
                .receipt {
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                    border: 1px solid #ddd;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .details {
                    margin-bottom: 30px;
                }
                .details p {
                    margin: 10px 0;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 5px;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    border-top: 2px solid #333;
                    padding-top: 10px;
                }
                @media print {
                    body { print-color-adjust: exact; }
                }
            </style>
        </head>
        <body>
            <div class="receipt">
                <div class="header">
                    <h2>Lato Sacco LTD</h2>
                    <h3>${type} Receipt</h3>
                </div>
                <div class="details">
                    <p><strong>Receipt No:</strong> ${data.receipt_no || 'N/A'}</p>
                    <p><strong>Date:</strong> ${new Date(data.date).toLocaleString()}</p>
                    <p><strong>Group Name:</strong> ${data.group_name}</p>
                    <p><strong>Member Name:</strong> ${data.member_name}</p>
                    <p><strong>Amount:</strong> KSh ${parseFloat(data.amount).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}</p>
                    <p><strong>Payment Mode:</strong> ${data.payment_mode}</p>
                    <p><strong>Served By:</strong> ${data.served_by_name}</p>
                </div>
                <div class="footer">
                    <p>Thank you for your transaction!</p>
                    <p>Printed on: ${new Date().toLocaleString()}</p>
                </div>
            </div>
        </body>
        </html>
    `;
}





//PRINT STATEMENT
function generateStatementHTML(data, fromDate, toDate) {
    if (!data || !data.transactions) {
        console.error('Invalid data structure:', data);
        return '<p>Error: Invalid data received</p>';
    }

    let transactionsHTML = '';
    let totalSavings = 0;
    let totalWithdrawals = 0;

    data.transactions.forEach(transaction => {
        const amount = parseFloat(transaction.amount);
        if (transaction.type === 'Savings') {
            totalSavings += amount;
        } else if (transaction.type === 'Withdrawal') {
            totalWithdrawals += amount;
        }

        transactionsHTML += `
            <tr>
                <td>${new Date(transaction.date).toLocaleDateString()}</td>
                <td>${transaction.member_name}</td>
                <td>${transaction.type}</td>
                <td>KSh ${amount.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })}</td>
                <td>${transaction.receipt_no || 'N/A'}</td>
                <td>${transaction.payment_mode || ''}</td>
            </tr>
        `;
    });

    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Group Statement</title>
            <style>
                body { 
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    padding: 20px;
                }
                .statement {
                    max-width: 1000px;
                    margin: 0 auto;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .group-info {
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #51087E;
                    color: white;
                }
                tr:nth-child(even) {
                    background-color: #f8f9fa;
                }
                .summary {
                    margin-top: 20px;
                    border-top: 2px solid #333;
                    padding-top: 10px;
                }
                @media print {
                    body { print-color-adjust: exact; }
                    th { background-color: #51087E !important; color: white !important; }
                }
            </style>
        </head>
        <body>
            <div class="statement">
                <div class="header">
                    <h2>Lato Sacco LTD</h2>
                    <h3>Group Transaction Statement</h3>
                </div>
                <div class="group-info">
                    <p><strong>Group Name:</strong> ${data.group_details.group_name}</p>
                    <p><strong>Period:</strong> ${new Date(fromDate).toLocaleDateString()} to ${new Date(toDate).toLocaleDateString()}</p>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Member</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Receipt No</th>
                            <th>Payment Mode</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${transactionsHTML}
                    </tbody>
                </table>
                <div class="summary">
                    <h4>Summary</h4>
                    <p><strong>Total Savings:</strong> KSh ${totalSavings.toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}</p>
                    <p><strong>Total Withdrawals:</strong> KSh ${totalWithdrawals.toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}</p>
                    <p><strong>Net Movement:</strong> KSh ${(totalSavings - totalWithdrawals).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}</p>
                </div>
                <div class="footer" style="text-align: center; margin-top: 30px;">
                    <p>Generated on: ${new Date().toLocaleString()}</p>
                </div>
            </div>
        </body>
        </html>
    `;
}





        // Transaction Filter Handling
        $('#transactionFilter').change(function() {
            var filter = $(this).val();
            if (filter === 'custom') {
                $('#customDateRange, #customDateRange2').show();
            } else {
                $('#customDateRange, #customDateRange2').hide();
                filterTransactions(filter);
            }
        });

        $('#startDate, #endDate').change(function() {
            if ($('#startDate').val() && $('#endDate').val()) {
                filterTransactions('custom');
            }
        });

        function filterTransactions(filter) {
            var startDate, endDate;

            switch(filter) {
                case 'week':
                    startDate = moment().subtract(1, 'weeks');
                    endDate = moment();
                    break;
                case 'month':
                    startDate = moment().subtract(1, 'months');
                    endDate = moment();
                    break;
                case 'year':
                    startDate = moment().subtract(1, 'years');
                    endDate = moment();
                    break;
                case 'custom':
                    startDate = moment($('#startDate').val());
                    endDate = moment($('#endDate').val());
                    break;
                default:
                    $('#transactionTable').DataTable().search('').columns().search('').draw();
                    return;
            }

            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var date = moment(data[0]);
                    return (date.isSameOrAfter(startDate) && date.isSameOrBefore(endDate));
                }
            );

            $('#transactionTable').DataTable().draw();
            $.fn.dataTable.ext.search.pop();
        }

        // Form validation for savings and withdrawals
        $('form').on('submit', function(e) {
            var amount = parseFloat($(this).find('input[name="amount"]').val());
            var availableBalance = parseFloat($('#availableBalance').val().replace('KSh ', ''));

            if ($(this).attr('id') === 'withdrawForm' && amount > availableBalance) {
                e.preventDefault();
                alert('Withdrawal amount cannot exceed available balance');
                return false;
            }

            if (amount <= 0) {
                e.preventDefault();
                alert('Amount must be greater than zero');
                return false;
            }
        });

        // View member details
        $('.view-member').click(function() {
            var memberId = $(this).data('member-id');
            window.open(`../views/view_account.php?id=${memberId}`, '_blank');
        });

        // Responsive handlers
        $(window).resize(function() {
            if ($(window).width() < 768) {
                $('.summary-card').removeClass('h-100');
            } else {
                $('.summary-card').addClass('h-100');
            }
        }).resize();
    });
    </script>
</body>
</html>