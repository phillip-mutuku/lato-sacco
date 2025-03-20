<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../config/config.php';
require_once '../controllers/accountController.php';
require_once '../helpers/session.php';
require_once '../config/class.php';
$db = new db_class(); 

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: index.php');
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$accountController = new AccountController();

// Initialize variables
$accountId = $_GET['id'] ?? null;
$accountDetails = null;
$transactions = [];
$loans = [];
$savings = [];
$repayments = [];
$totalSavings = 0;
$totalLoans = 0;
$netBalance = 0;
$outstandingPrincipal = 0;
$error = null;

$accountType = $_GET['account_type'] ?? 'all';

if ($accountId) {
    try {
        $accountDetails = $accountController->getAccountById($accountId);
        if (!$accountDetails) {
            throw new Exception("Account not found.");
        }
        
        // Update these calls to include account type filtering
        $transactions = $accountController->getAccountTransactions($accountId, $accountType);
        $loans = $accountController->getAccountLoans($accountId, $accountType);
        $repayments = $accountController->getLoanRepayments($accountId);
        $savings = $accountController->getAccountSavings($accountId, $accountType);
        $totalSavings = $accountController->getTotalSavings($accountId, $accountType);
        $totalLoans = $accountController->getTotalLoans($accountId, $accountType);
        
        // Get outstanding principal based on account type
        if ($accountType === 'all') {
            $outstandingPrincipal = $accountController->getModel()->getTotalOutstandingPrincipal($accountId);
        } else {
            // Get filtered outstanding principal
            $outstandingPrincipal = $accountController->getModel()->getTotalOutstandingPrincipal($accountId, $accountType);
        }
        
        $accountDetails['outstanding_principal'] = $outstandingPrincipal;
        $netBalance = $totalSavings - $totalLoans;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error in view_account.php: " . $e->getMessage());
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
$monthlySavings = groupByMonth($savings, 'date', 'amount');
$monthlyTransactions = groupByMonth($transactions, 'date', 'amount');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Details - Lato Management System</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
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
        .card-header{
            color: #51087E;";
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
        .shareholder-card { background: linear-gradient(45deg,  #51087E, #224abe); }
        .savings-card { background: linear-gradient(45deg, #1cc88a, #13855c); }
        .loans-card { background: linear-gradient(45deg, #f6c23e, #dda20a); }
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

            /* Custom select container */
    .filter-section {
      margin: 1.5rem 0;
      padding: 1.8rem;
      background: white;
      border-radius: 0.5rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
    }

    .filter-header {
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .filter-icon {
      color: #51087E;
      font-size: 1.25rem;
    }

    .filter-title {
      color: #51087E;
      font-size: 1rem;
      font-weight: 600;
      margin: 0;
    }

    /* Custom select styling */
    .custom-select-wrapper {
      position: relative;
      max-width: 600px;
    }

    .custom-select {
      appearance: none;
      -webkit-appearance: none;
      width: 100%;
      padding: 0 1rem;
      font-size: 0.95rem;
      border: 2px solid #e2e8f0;
      border-radius: 0.375rem;
      background-color: white;
      color: #4a5568;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .custom-select:hover {
      border-color: #51087E;
    }

    .custom-select:focus {
      outline: none;
      border-color: #51087E;
      box-shadow: 0 0 0 3px rgba(81, 8, 126, 0.2);
    }

    /* Custom select arrow */
    .select-arrow {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      pointer-events: none;
      color: #51087E;
    }

    /* Animation for select focus */
    @keyframes select-focus {
      0% { box-shadow: 0 0 0 0 rgba(81, 8, 126, 0.4); }
      70% { box-shadow: 0 0 0 5px rgba(81, 8, 126, 0); }
      100% { box-shadow: 0 0 0 0 rgba(81, 8, 126, 0); }
    }

    .custom-select:focus {
      animation: select-focus 0.8s ease-out;
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
                    <!-- Back to Accounts Button -->
                    <a href="../views/account.php" style="background-color: #51087E; color: white;" class="btn btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Accounts
                    </a>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $db->user_acc($_SESSION['user_id'])?></span>
                                <img class="img-profile rounded-circle"
                                    src="../public/image/logo.jpg">
                            </a>
                            <!-- Dropdown - User Information -->
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

                    <?php if ($accountDetails): ?>
                        <h1 class="mb-4 text-black bold"><?= htmlspecialchars($accountDetails['first_name'] . ' ' . $accountDetails['last_name']) ?></h1>

                        <div class="filter-section">
                            <div class="filter-header">
                            <i class="fas fa-filter filter-icon"></i>
                            <h3 class="filter-title">Filter by Account Type</h3>
                            </div>
                            <div class="custom-select-wrapper">
                            <select id="accountTypeFilter" class="custom-select">
                                <option value="all">All Account Types</option>
                                <?php
                                $accountTypes = explode(', ', $accountDetails['account_type']);
                                foreach($accountTypes as $type): ?>
                                    <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="select-arrow">
                            </div>
                            </div>
                        </div>


                                                
                        <!-- Account Summary -->
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <div class="summary-card shareholder-card">
                                    <h4>Shareholder No</h4>
                                    <p><?= htmlspecialchars($accountDetails['shareholder_no'] ?? 'N/A') ?></p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="summary-card savings-card">
                                    <h4>Total Savings</h4>
                                    <p id="totalSavings">KSh <?= number_format($totalSavings, 2) ?></p>
                                    <small id="accountTypeSavings"></small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                            <div class="summary-card loans-card">
                                <h4>Outstanding Loans</h4>
                                <p id="outstandingLoans">
                                    KSh <?= number_format($accountDetails['totalLoans'] ?? 0, 2) ?>
                                </p>
                                <small id="accountTypeLoans"></small>
                            </div>
                        </div>
                            <div class="col-md-3 mb-3">
                                <div class="summary-card balance-card">
                                    <h4>Net Balance</h4>
                                    <p id="netBalance">KSh <?= number_format($netBalance, 2) ?></p>
                                    <small id="accountTypeBalance"></small>
                                </div>
                            </div>
                        </div>




                        <div class="row">
                            <div class="col-md-6">
                                <!-- Client Information -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="m-0 font-weight-bold">Client Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Name:</strong> <?= htmlspecialchars($accountDetails['first_name'] . ' ' . $accountDetails['last_name']) ?></p>
                                                <p><strong>National ID:</strong> <?= htmlspecialchars($accountDetails['national_id'] ?? 'N/A') ?></p>
                                                <p><strong>Phone:</strong> <?= htmlspecialchars($accountDetails['phone_number'] ?? 'N/A') ?></p>
                                                <p><strong>Email:</strong> <?= htmlspecialchars($accountDetails['email'] ?? 'N/A') ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Location:</strong> <?= htmlspecialchars($accountDetails['location'] ?? 'N/A') ?></p>
                                                <p><strong>Division:</strong> <?= htmlspecialchars($accountDetails['division'] ?? 'N/A') ?></p>
                                                <p><strong>Village:</strong> <?= htmlspecialchars($accountDetails['village'] ?? 'N/A') ?></p>
                                                <p><strong>Account Type:</strong> <?= htmlspecialchars($accountDetails['account_type'] ?? 'N/A') ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Account Performance Chart -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="m-0 font-weight-bold">Account Performance</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="accountPerformanceChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
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
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="m-0 font-weight-bold">Transaction Distribution</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="transactionDistributionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Loans Section -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="m-0 font-weight-bold">Loans</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($loans)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="loansTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Ref No</th>
                                                    <th>Loan Product</th>
                                                    <th>Amount</th>
                                                    <th>Interest Rate</th>
                                                    <th>Monthly Payment</th>
                                                    <th>Total Payable</th>
                                                    <th>Status</th>
                                                    <th>Date Applied</th>
                                                    <th>Next Payment Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($loans as $loan): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($loan['ref_no']) ?></td>
                                                        <td><?= htmlspecialchars($loan['loan_product_id']) ?></td>
                                                        <td>KSh <?= number_format($loan['amount'], 2) ?></td>
                                                        <td><?= number_format($loan['interest_rate'], 2) ?>%</td>
                                                        <td>KSh <?= number_format($loan['monthly_payment'], 2) ?></td>
                                                        <td>KSh <?= number_format($loan['total_payable'], 2) ?></td>
                                                        <td>
                                                        <?php
                                                            $status_class = '';
                                                            $status_text = '';
                                                            switch($loan['status']) {
                                                                case 0: $status_class = 'badge-warning'; $status_text = 'Pending Approval'; break;
                                                                case 1: $status_class = 'badge-info'; $status_text = 'Approved'; break;
                                                                case 2: $status_class = 'badge-primary'; $status_text = 'Released'; break;
                                                                case 3: $status_class = 'badge-success'; $status_text = 'Completed'; break;
                                                                case 4: $status_class = 'badge-danger'; $status_text = 'Denied'; break;
                                                                default: $status_class = 'badge-secondary'; $status_text = 'Unknown'; break;
                                                            }
                                                            echo "<span class='badge $status_class'>$status_text</span>";
                                                            ?>
                                                        </td>
                                                        <td><?= date("Y-m-d", strtotime($loan['date_applied'])) ?></td>
                                                        <td><?= $loan['next_payment_date'] ? date("Y-m-d", strtotime($loan['next_payment_date'])) : 'N/A' ?></td>
                                                        <td>
                                                            <button class="btn btn-info btn-sm view-schedule" data-loan-id="<?= $loan['loan_id'] ?>">Loan Schedule</button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No loans found for this account.</p>
                                <?php endif; ?>
                            </div>
                        </div>



                        <!-- Loan Repayments Section -->
                    <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="m-0 font-weight-bold">Loan Repayments</h5>
                        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#repayLoanModal">Repay Loan</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="repaymentTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Loan Ref No</th>
                                        <th>Receipt No</th>
                                        <th>Amount Repaid</th>
                                        <th>Date Paid</th>
                                        <th>Mode of Payment</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                        <?php if (!empty($repayments)): ?>
                                            <?php foreach ($repayments as $repayment): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($repayment['loan_ref_no']) ?></td>
                                                    <td><?= htmlspecialchars($repayment['receipt_number']) ?></td>
                                                    <td>KSh <?= number_format($repayment['amount_repaid'], 2) ?></td>
                                                    <td><?= date("Y-m-d", strtotime($repayment['date_paid'])) ?></td>
                                                    <td><?= htmlspecialchars($repayment['payment_mode']) ?></td>
                                                    <td>
                                                        <button style="background-color: #51087E; color: white;" 
                                                                class="btn btn-sm print-repayment-receipt" 
                                                                data-repayment-id="<?= $repayment['id'] ?>">
                                                            <i class="fas fa-print"></i> Print Receipt
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No repayments found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>



                        <!-- Savings Section -->
                        <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="m-0 font-weight-bold">Savings and Withdrawals</h5>
                        <div>
                            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addSavingsModal">Add Savings</button>
                            <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#withdrawModal">Withdraw</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($savings)): ?>
                            <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="savingsTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Receipt No</th>
                                                <th>Amount</th>
                                                <th>Withdrawal Fee</th>
                                                <th>Payment Mode</th>
                                                <th>Served By</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($savings as $saving): ?>
                                                <tr>
                                                    <td><?= date("Y-m-d H:i:s", strtotime($saving['date'])) ?></td>
                                                    <td><?= htmlspecialchars($saving['type']) ?></td>
                                                    <td><?= htmlspecialchars($saving['receipt_number']) ?></td>
                                                    <td>KSh <?= number_format($saving['amount'], 2) ?></td>
                                                    <td><?= $saving['type'] === 'Withdrawal' ? 'KSh ' . number_format($saving['withdrawal_fee'], 2) : 'N/A' ?></td>
                                                    <td><?= htmlspecialchars($saving['payment_mode']) ?></td>
                                                    <td><?= htmlspecialchars($saving['served_by']) ?></td>
                                                    <td>
                                                        <button style="background-color: #51087E; color: white;" 
                                                                class="btn btn-sm print-savings-receipt" 
                                                                data-id="<?= $saving['saving_id'] ?>" 
                                                                data-type="<?= htmlspecialchars($saving['type']) ?>">
                                                            <i class="fas fa-print"></i> Print Receipt
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No savings or withdrawal records found for this account.</p>
                        <?php endif; ?>
                    </div>
                </div>

                

<!-- Transactions Section -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="m-0 font-weight-bold">Transactions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <select id="transactionFilter" class="form-control">
                                            <option value="all">All Transactions</option>
                                            <option value="week">Last Week</option>
                                            <option value="month">Last Month</option>
                                            <option value="year">Last Year</option>
                                            <option value="custom">Custom Date Range</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3" id="customDateRange" style="display:none;">
                                        <input type="date" id="startDate" class="form-control" placeholder="Start Date">
                                    </div>
                                    <div class="col-md-3" id="customDateRange2" style="display:none;">
                                        <input type="date" id="endDate" class="form-control" placeholder="End Date">
                                    </div>
                                    <div class="col-md-3">
                                        <button id="printStatement" class="btn btn-success">Print Statement</button>
                                    </div>
                                </div>
                                <?php if (!empty($transactions)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="transactionTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                    <th>Description</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($transactions as $transaction): ?>
                                                    <tr>
                                                        <td><?= date("Y-m-d", strtotime($transaction['date'])) ?></td>
                                                        <td><?= htmlspecialchars($transaction['type']) ?></td>
                                                        <td>KSh <?= number_format($transaction['amount'], 2) ?></td>
                                                        <td><?= htmlspecialchars($transaction['description']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No transactions found for this account.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">System Information</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Are you sure you want to logout?</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-danger" href="../views/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addSavingsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div style="background-color: #51087E; color: white;" class="modal-header">
                <h5 class="modal-title">Add Savings</h5>
                <button style="background-color: #51087E; color: white;" type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addSavingsForm">
                <div class="modal-body">
                    <input type="hidden" name="accountId" value="<?= $accountId ?>">
                    <div class="form-group">
                        <label for="receiptNumber">Receipt Number</label>
                        <input type="text" class="form-control" id="receiptNumber" name="receiptNumber" required>
                    </div>
                    <div class="form-group">
                        <label for="accountType">Account Type</label>
                        <select class="form-control" id="accountType" name="accountType" required>
                            <?php foreach($accountTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="availableBalance">Available Balance</label>
                        <input type="text" class="form-control" id="availableBalance" readonly>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input type="number" class="form-control" id="amount" name="amount" required>
                    </div>
                    <div class="form-group">
                        <label for="paymentMode">Payment Mode</label>
                        <select class="form-control" id="paymentMode" name="paymentMode" required>
                            <option value="Cash">Cash</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Add Savings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Withdraw Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div style="background-color: #51087E; color: white;" class="modal-header">
                <h5 class="modal-title">Withdraw Savings</h5>
                <button style="background-color: #51087E; color: white;" type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="withdrawForm">
                <div class="modal-body">
                    <input type="hidden" name="accountId" value="<?= $accountId ?>">
                    <div class="form-group">
                        <label for="withdrawReceiptNumber">Receipt Number</label>
                        <input type="text" class="form-control" id="withdrawReceiptNumber" name="receiptNumber" required>
                    </div>
                    <div class="form-group">
                        <label for="withdrawAccountType">Account Type</label>
                        <select class="form-control" id="withdrawAccountType" name="accountType" required>
                            <?php foreach($accountTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="withdrawAvailableBalance">Available Balance</label>
                        <input type="text" class="form-control" id="withdrawAvailableBalance" readonly>
                    </div>
                    <div class="form-group">
                        <label for="withdrawAmount">Amount</label>
                        <input type="number" class="form-control" id="withdrawAmount" name="amount" required>
                    </div>
                    <div class="form-group">
                        <label for="withdrawalFee">Withdrawal Fee</label>
                        <input type="number" class="form-control" id="withdrawalFee" name="withdrawalFee" required>
                        <small class="text-muted">This fee will be deducted from the withdrawal amount</small>
                    </div>
                    <div class="form-group">
                        <label for="totalWithdrawal">Total Amount (including fee)</label>
                        <input type="text" class="form-control" id="totalWithdrawal" readonly>
                    </div>
                    <div class="form-group">
                        <label for="withdrawPaymentMode">Payment Mode</label>
                        <select class="form-control" id="withdrawPaymentMode" name="paymentMode" required>
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


    <!-- Repay Loan Modal -->
<div class="modal fade" id="repayLoanModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div style="background-color: #51087E; color: white;" class="modal-header">
                <h5 class="modal-title">Repay Loan</h5>
                <button style="background-color: #51087E; color: white;" type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="repayLoanForm">
                <div class="modal-body">
                <input type="hidden" name="accountId" value="<?= $accountId ?>">
                <input type="hidden" name="served_by" value="<?= $_SESSION['user_id'] ?>">
                    <div class="form-group">
                        <label for="loanSelect">Select Loan</label>
                        <select class="form-control" id="loanSelect" name="loanId" required>
                            <option value="">Select a loan</option>
                            <?php foreach ($loans as $loan): ?>
                                <?php if (isset($loan['loan_id']) && isset($loan['ref_no']) && isset($loan['outstanding_balance'])): ?>
                                    <option value="<?= $loan['loan_id'] ?>">
                                        <?= $loan['ref_no'] ?> - KSh <?= number_format($loan['outstanding_balance'] ?? 0, 2) ?> outstanding
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="refNo">Loan Ref No</label>
                        <input type="text" class="form-control" id="refNo" readonly>
                    </div>
                    <div class="form-group">
                        <label for="loanReceiptNumber">Receipt Number</label>
                        <input type="text" class="form-control" id="loanReceiptNumber" name="receiptNumber" required>
                    </div>
                    <div class="form-group">
                        <label for="outstandingBalance">Outstanding Balance</label>
                        <input type="text" class="form-control" id="outstandingBalance" readonly>
                    </div>
                    <div class="form-group">
                        <label for="nextDueAmount">Next Due Amount</label>
                        <input type="text" class="form-control" id="nextDueAmount" readonly>
                    </div>
                    <div class="form-group">
                        <label for="repayAmount">Repayment Amount</label>
                        <input type="number" class="form-control" id="repayAmount" name="repayAmount" required step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label for="paymentMode">Payment Mode</label>
                        <select class="form-control" id="paymentMode" name="paymentMode" required>
                            <option value="Cash">Cash</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Repay Loan</button>
                </div>
                <div id="loanLoadingIndicator" style="display:none; text-align:center; margin:10px 0;">
                    <i class="fas fa-spinner fa-spin"></i> Loading loan details...
                </div>
            </form>
        </div>
    </div>
</div>



<!-- Loan Schedule Modal -->
<div class="modal fade" id="loanScheduleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div style="background-color: #51087E; color: white;" class="modal-header">
                <h5 class="modal-title">Loan Schedule</h5>
                <button style="background-color: #51087E; color: white;" type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="scheduleTable">
                        <thead>
                            <tr>
                                <th>Due Date</th>
                                <th>Principal</th>
                                <th>Interest</th>
                                <th>Due Amount</th>
                                <th>Balance</th>
                                <th>Repaid Amount</th>
                                <th>Default Amount</th>
                                <th>Status</th>
                                <th>Paid Date</th>
                            </tr>
                        </thead>
                        <tbody id="scheduleTableBody">
                            <!-- Schedule data will be inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

    <!-- Bootstrap core JavaScript-->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../public/js/jquery.easing.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../public/js/sb-admin-2.js"></script>
    
    <!-- Page level plugins -->
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>

    <script>
$(document).ready(function() {
    // =====================================
    // CONSTANTS AND UTILITIES
    // =====================================
    const ACCOUNT_ID = <?= $accountId ?>;
    
    const CURRENCY_FORMAT = {
        style: 'currency',
        currency: 'KES',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    };

    // Utility functions
    // Function to format currency
    function formatCurrency(amount) {
        return 'KSh ' + parseFloat(amount).toLocaleString('en-KE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Function to format date
    function formatDateTime(dateString) {
        return new Date(dateString).toLocaleString('en-KE', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function formatStatementDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-KE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatStatementCurrency(amount) {
    return 'KSh ' + parseFloat(amount).toLocaleString('en-KE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

    // =====================================
    // INITIALIZATION
    // =====================================
    
    // Initialize DataTables
    const initializeTables = () => {
        const tables = ['#loansTable', '#savingsTable', '#transactionTable', '#repaymentTable'];
        tables.forEach(table => $(table).DataTable());
    };

    // Initialize account type filter from URL
    const initializeAccountTypeFilter = () => {
        const urlParams = new URLSearchParams(window.location.search);
        const accountType = urlParams.get('account_type');
        if (accountType) {
            $('#accountTypeFilter').val(accountType);
        }

            // =====================================
    // ACCOUNT TYPE FILTER
    // =====================================
    $('#accountTypeFilter').change(function() {
    const selectedType = $(this).val();
    
    $.ajax({
        url: '../controllers/accountController.php?action=getFilteredSummary',
        type: 'GET',
        data: {
            accountId: <?= $accountId ?>,
            accountType: selectedType
        },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                // Update summary cards without page reload
                $('#totalSavings').text('KSh ' + 
                    parseFloat(response.totalSavings)
                        .toLocaleString('en-KE', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));
                
                $('#outstandingLoans').text('KSh ' + 
                    parseFloat(response.totalLoanAmount)
                        .toLocaleString('en-KE', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));
                
                $('#netBalance').text('KSh ' + 
                    parseFloat(response.netBalance)
                        .toLocaleString('en-KE', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));

                localStorage.setItem('selectedAccountType', selectedType);
            } else {
                console.error('Error fetching summary:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
        }
    });
});



        // Restore selected account type
        const savedAccountType = localStorage.getItem('selectedAccountType');
        if (savedAccountType) {
            $('#accountTypeFilter').val(savedAccountType).trigger('change');
        }
    };


    
    // Add Savings Form Submission
    $('#addSavingsForm').submit(function(e) {
        e.preventDefault();
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true);
        
        $.ajax({
            url: '../controllers/accountController.php?action=addSavings',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                try {
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                    
                    if (response.status === 'success') {
                        alert('Savings added successfully');
                        if (response.receiptDetails) {
                            printSavingsReceipt(response.receiptDetails);
                        }
                        location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Unknown error occurred'));
                    }
                } catch (e) {
                    console.error('Error processing response:', e);
                    alert('An error occurred while processing the response');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('Error adding savings. Please try again.');
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    });

// Withdraw Form Submission
$('#withdrawForm').submit(function(e) {
    e.preventDefault();
    const submitButton = $(this).find('button[type="submit"]');
    submitButton.prop('disabled', true); 

    const formData = {
        accountId: $('input[name="accountId"]').val(),
        amount: parseFloat($('#withdrawAmount').val()) || 0,
        withdrawalFee: parseFloat($('#withdrawalFee').val()) || 0,
        accountType: $('#withdrawAccountType').val(),
        receiptNumber: $('#withdrawReceiptNumber').val(),
        paymentMode: $('#withdrawPaymentMode').val()
    };

    $.ajax({
        url: '../controllers/accountController.php?action=withdraw',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert('Withdrawal processed successfully');

                // Update displayed balances
                $('#totalSavings').text('KSh ' + parseFloat(response.newTotalSavings)
                    .toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#netBalance').text('KSh ' + parseFloat(response.newNetBalance)
                    .toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                if (response.details) {
                    printWithdrawalReceipt(response.details);
                }

                $('#withdrawModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Unknown error occurred'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', { status: status, error: error, response: xhr.responseText });
            alert('Withdrawal successful');
        },
        complete: function() {
            submitButton.prop('disabled', false);
        }
    });
});

// Update withdrawal total calculation
$('#withdrawAmount, #withdrawalFee').on('input', function() {
    const amount = parseFloat($('#withdrawAmount').val()) || 0;
    const fee = parseFloat($('#withdrawalFee').val()) || 0;
    const total = amount + fee;

    $('#totalWithdrawal').val('KSh ' + total.toLocaleString('en-KE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }));
});




    // =====================================
    // BALANCE MANAGEMENT
    // =====================================
    
    // Get Available Balance
    function getAvailableBalance(accountType) {
        $.ajax({
            url: '../controllers/accountController.php?action=getAvailableBalance',
            type: 'GET',
            data: {
                accountId: <?= $accountId ?>,
                accountType: accountType
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        $('#availableBalance, #withdrawAvailableBalance').val('KSh ' + 
                            parseFloat(data.balance).toLocaleString('en-KE', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            })
                        );
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching balance:', error);
            }
        });
    }


        // Account type selection event handlers
        $('#accountType, #withdrawAccountType').change(function() {
        const selectedType = $(this).val();
        getAvailableBalance(selectedType);
    });


    function updateOutstandingPrincipal() {
    $.ajax({
        url: '../controllers/accountController.php',
        type: 'GET',
        data: {
            action: 'getOutstandingPrincipal',
            accountId: <?= $accountId ?>
        },
        success: function(response) {
            if (typeof response === 'string') {
                response = JSON.parse(response);
            }
            
            if (response.status === 'success') {
                $('#outstandingLoans').text('KSh ' + 
                    parseFloat(response.outstandingPrincipal)
                        .toLocaleString('en-KE', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })
                );
            } else {
                console.error('Error updating outstanding principal:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
}


    // =====================================
    // LOAN MANAGEMENT
    // =====================================
        // Loan Repayment Form Submission
$('#repayLoanForm').submit(function(e) {
    e.preventDefault();
    console.log("Repay Loan Form Submitted");

    // Debug: Log all form field values
    console.log("Loan ID:", $('#loanSelect').val());
    console.log("Account ID:", $('input[name="accountId"]').val());
    console.log("Receipt Number:", $('#loanReceiptNumber').val());
    console.log("Repay Amount:", $('#repayAmount').val());
    console.log("Payment Mode:", $('#paymentMode').val());

    // Add a hidden input for served_by if it doesn't exist
    if (!$('input[name="served_by"]').length) {
        $(this).append('<input type="hidden" name="served_by" value="<?= $_SESSION['user_id'] ?>">');
    }

    var formData = $(this).serialize();
    console.log("Form Data:", formData);

    // Check for empty fields
    var missingFields = [];
    if (!$('#loanSelect').val()) missingFields.push("Loan");
    if (!$('input[name="accountId"]').val()) missingFields.push("Account ID");
    if (!$('#loanReceiptNumber').val()) missingFields.push("Receipt Number");
    if (!$('#repayAmount').val()) missingFields.push("Repayment Amount");
    if (!$('#paymentMode').val()) missingFields.push("Payment Mode");

    if (missingFields.length > 0) {
        alert("Please fill in the following fields: " + missingFields.join(", "));
        return;
    }

    $.ajax({
        url: '../controllers/accountController.php?action=repayLoan',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            console.log("Repay Loan Response:", response);

            if (response.status === 'success') {
                updateOutstandingPrincipal();
                alert('Loan repayment successful');
                
                $('#outstandingLoans').text('KSh ' + parseFloat(response.newTotalOutstandingLoans).toLocaleString('en-KE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                
                $('#netBalance').text('KSh ' + parseFloat(response.newNetBalance).toLocaleString('en-KE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                
                if (response.repaymentDetails) {
                    printLoanRepaymentReceipt(response.repaymentDetails.repayment);
                }
                
                $('#repayLoanModal').modal('hide');
                location.reload();
            } else {
                alert('Error repaying loan: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error details:', {
                xhr: xhr,
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            try {
                var response = JSON.parse(xhr.responseText);
                alert('Error: ' + response.message);
            } catch (e) {
                alert('An error occurred while repaying the loan. Please try again.');
            }
        }
    });
});


            // Alert Helper Function
            function showAlert(message, type) {
                const alert = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>`;
                
                $('#contentWrapper').prepend(alert);
                setTimeout(() => $('.alert').alert('close'), 5000);
            }


    
  // Loan Select Change
// Loan Select Change
$('#loanSelect').change(function() {
    var loanId = $(this).val();
    console.log("Selected loan ID:", loanId);
    
    if (loanId) {
        // Clear previous values first
        $('#refNo').val('');
        $('#outstandingBalance').val('');
        $('#nextDueAmount').val('');
        $('#repayAmount').val('');
        
        // Show loading indicator
        var loadingHtml = '<div id="loadingIndicator" style="text-align: center; margin: 10px 0;"><i class="fas fa-spinner fa-spin"></i> Loading loan details...</div>';
        $('#repayLoanForm').prepend(loadingHtml);
        
        $.ajax({
            url: '../controllers/accountController.php',
            type: 'GET',
            data: { 
                action: 'getLoanDetails',
                loanId: loanId 
            },
            dataType: 'json',
            success: function(response) {
                console.log("Loan details response:", response);
                
                // Remove loading indicator
                $('#loadingIndicator').remove();
                
                if (response.status === 'success' && response.loan) {
                    var loan = response.loan;
                    
                    // Set loan reference number
                    $('#refNo').val(loan.ref_no || '');
                    
                    // Format and set outstanding balance
                    if (loan.outstanding_balance !== undefined) {
                        $('#outstandingBalance').val('KSh ' + parseFloat(loan.outstanding_balance)
                            .toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    } else {
                        $('#outstandingBalance').val('KSh 0.00');
                    }
                    
                    // Process next due amount
                    if (loan.next_due_amount && loan.next_due_amount > 0) {
                        var dueAmount = parseFloat(loan.next_due_amount);
                        var statusText = loan.is_overdue ? ' (Overdue)' : '';
                        
                        $('#nextDueAmount').val('KSh ' + dueAmount.toLocaleString('en-KE', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }) + statusText);
                        
                        // For partially paid entries, show remaining amount
                        if (loan.partial_paid) {
                            var remainingDue = dueAmount - parseFloat(loan.partial_paid);
                            $('#repayAmount').val(remainingDue.toFixed(2));
                            
                            var partialText = ' (Partially paid: KSh ' + 
                                parseFloat(loan.partial_paid).toLocaleString('en-KE', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }) + ')';
                            $('#nextDueAmount').val($('#nextDueAmount').val() + partialText);
                        } else {
                            // Use the full due amount
                            $('#repayAmount').val(dueAmount.toFixed(2));
                        }
                        
                        if (loan.next_due_date) {
                            var dueDate = new Date(loan.next_due_date).toLocaleDateString();
                            $('#nextDueAmount').attr('title', 'Due on: ' + dueDate);
                        }
                    } else {
                        $('#nextDueAmount').val('No scheduled payment due');
                        $('#repayAmount').val('');
                    }
                } else {
                    // Show error
                    alert('Error: ' + (response.message || 'Could not retrieve loan details'));
                }
            },
            error: function(xhr, status, error) {
                // Remove loading indicator
                $('#loadingIndicator').remove();
                
                console.error('AJAX Error:', xhr.responseText);
                console.error('Status:', status);
                console.error('Error:', error);
                alert('Error fetching loan details. Please check the console for more information.');
            }
        });
    } else {
        // Clear fields if no loan is selected
        $('#refNo').val('');
        $('#outstandingBalance').val('');
        $('#nextDueAmount').val('');
        $('#repayAmount').val('');
    }
});



    const updateLoanDetails = (loan) => {
        $('#refNo').val(loan.ref_no);
        $('#outstandingBalance').val(formatCurrency(loan.outstanding_balance));
        
        if (loan.next_due_amount && loan.next_due_amount > 0) {
            const dueAmount = parseFloat(loan.next_due_amount);
            $('#nextDueAmount').val(formatCurrency(dueAmount));
            $('#repayAmount').val(dueAmount.toFixed(2));
            
            if (loan.next_due_date) {
                $('#nextDueAmount').attr('title', `Due on: ${new Date(loan.next_due_date).toLocaleDateString()}`);
            }
        } else {
            $('#nextDueAmount').val('No scheduled payment due');
            $('#repayAmount').val('');
        }
        
        const selectedOption = $('#loanSelect option:selected');
        selectedOption.text(`${loan.ref_no} - ${formatCurrency(loan.outstanding_balance)} outstanding`);
    };

    const clearLoanDetails = () => {
        $('#refNo').val('');
        $('#outstandingBalance').val('');
        $('#nextDueAmount').val('');
        $('#repayAmount').val('');
    };

    // =====================================
    // LOAN SCHEDULE
    // =====================================
    
    // View Loan Schedule
 // View Loan Schedule
$('.view-schedule').click(function() {
    var loanId = $(this).data('loan-id');
    console.log('Requesting loan schedule for loan ID:', loanId); // Debug log

    $.ajax({
        url: '../controllers/get_loan_schedule.php',
        type: 'GET',
        data: { loan_id: loanId },
        dataType: 'json',
        success: function(response) {
            console.log('Received response:', response); // Debug log
            
            if(response.status === 'success' && response.schedule) {
                var tableBody = $('#scheduleTableBody');
                tableBody.empty();
                
                $.each(response.schedule, function(index, item) {
                    console.log('Processing schedule item:', item); // Debug log
                    
                    var row = `
                        <tr>
                            <td>${item.due_date}</td>
                            <td>KSh ${item.principal}</td>
                            <td>KSh ${item.interest}</td>
                            <td>KSh ${item.amount}</td>
                            <td>KSh ${item.balance}</td>
                            <td>KSh ${item.repaid_amount}</td>
                            <td>KSh ${item.default_amount}</td>
                            <td><span class="badge ${getStatusBadgeClass(item.status)}">${item.status}</span></td>
                            <td>${item.paid_date || '-'}</td>
                        </tr>
                    `;
                    tableBody.append(row);
                });
                
                $('#loanScheduleModal').modal('show');
            } else {
                console.error('Invalid response format:', response);
                alert('Error: ' + (response.message || 'Invalid schedule data received'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                readyState: xhr.readyState,
                statusText: xhr.statusText
            });
            alert('An error occurred while fetching the loan schedule. Check console for details.');
        }
    });
});


// Helper function for status badge classes
function getStatusBadgeClass(status) {
    switch(status.toLowerCase()) {
        case 'paid':
            return 'badge-success';
        case 'partial':
            return 'badge-warning';
        case 'unpaid':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

    // =====================================
    // TRANSACTION FILTERING
    // =====================================
    

    // Transaction filter handling
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



    const applyTransactionFilter = (startDate, endDate) => {
        $.fn.dataTable.ext.search.push(
            (settings, data, dataIndex) => {
                const date = moment(data[0]);
                return (date.isSameOrAfter(startDate) && date.isSameOrBefore(endDate));
            }
        );

        $('#transactionTable').DataTable().draw();
        $.fn.dataTable.ext.search.pop();
    };

     // =====================================
    // PRINTING FUNCTIONALITY
    // =====================================

    // Print Loan Repayment Receipt
    $(document).on('click', '.print-repayment-receipt', function() {
        var repaymentId = $(this).data('repayment-id');
        $.ajax({
            url: '../controllers/accountController.php',
            type: 'GET',
            data: {
                action: 'getRepaymentDetails',
                repaymentId: repaymentId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    printLoanRepaymentReceipt(response.repayment);
                } else {
                    alert('Error fetching repayment details: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', xhr.responseText);
                alert('Error generating receipt');
            }
        });
    });

    // Print Savings Receipt
    $(document).on('click', '.print-savings-receipt', function() {
        var savingsId = $(this).data('id');
        var type = $(this).data('type');
        
        $.ajax({
            url: '../controllers/accountController.php',
            type: 'GET',
            data: {
                action: 'getSavingsDetails',
                savingsId: savingsId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    printSavingsReceipt(response.details, type);
                } else {
                    alert('Error fetching receipt details: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', xhr.responseText);
                alert('Error generating receipt');
            }
        });
    });

    // Print Loan Repayment Receipt
function printLoanRepaymentReceipt(data) {
    var receiptWindow = window.open('', '_blank', 'width=400,height=600');
    var content = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Loan Repayment Receipt</title>
            <style>
                body { 
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    line-height: 1.6;
                }
                .receipt {
                    border: 1px solid #ccc;
                    padding: 20px;
                    max-width: 800px;
                    margin: 0 auto;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .detail-row {
                    margin: 10px 0;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 5px;
                }
                .footer {
                    text-align: center;
                    margin-top: 20px;
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
                    <h3>Loan Repayment Receipt</h3>
                </div>
                <div class="detail-row">
                    <strong>Receipt No:</strong> ${data.receipt_number || 'N/A'}
                </div>
                <div class="detail-row">
                    <strong>Date:</strong> ${formatDateTime(data.date_paid)}
                </div>
                <div class="detail-row">
                    <strong>Client Name:</strong> ${data.first_name} ${data.last_name}
                </div>
                <div class="detail-row">
                    <strong>Loan Ref No:</strong> ${data.loan_ref_no}
                </div>
                <div class="detail-row">
                    <strong>Amount Paid:</strong> ${formatCurrency(data.amount_repaid)}
                </div>
                <div class="detail-row">
                    <strong>Payment Mode:</strong> ${data.payment_mode}
                </div>
                <div class="detail-row">
                    <strong>Served By:</strong> ${data.served_by || 'System'}
                </div>
                <div class="footer">
                    <p>Thank you for banking with us!</p>
                    <p>Printed on: ${formatDateTime(new Date())}</p>
                </div>
            </div>
        </body>
        </html>
    `;
    
    receiptWindow.document.write(content);
    receiptWindow.document.close();
    
    setTimeout(() => {
        receiptWindow.print();
        receiptWindow.close();
    }, 500);
}

// Print Savings/Withdrawal Receipt
function printSavingsReceipt(data, type) {
    var receiptWindow = window.open('', '_blank', 'width=400,height=600');
    var content = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>${type} Receipt</title>
            <style>
                body { 
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    line-height: 1.6;
                }
                .receipt {
                    border: 1px solid #ccc;
                    padding: 20px;
                    max-width: 800px;
                    margin: 0 auto;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .detail-row {
                    margin: 10px 0;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 5px;
                }
                .footer {
                    text-align: center;
                    margin-top: 20px;
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
                    <h2>Lato Lato LTD</h2>
                    <h3>${type} Receipt</h3>
                </div>
                <div class="detail-row">
                    <strong>Receipt No:</strong> ${data.receipt_number}
                </div>
                <div class="detail-row">
                    <strong>Date:</strong> ${formatDateTime(data.date)}
                </div>
                <div class="detail-row">
                    <strong>Client Name:</strong> ${data.client_name}
                </div>
                <div class="detail-row">
                    <strong>Account Type:</strong> ${data.account_type}
                </div>
                <div class="detail-row">
                    <strong>Amount:</strong> ${formatCurrency(data.amount)}
                </div>
                ${data.withdrawal_fee ? `
                <div class="detail-row">
                    <strong>Withdrawal Fee:</strong> ${formatCurrency(data.withdrawal_fee)}
                </div>
                <div class="detail-row">
                    <strong>Total Amount:</strong> ${formatCurrency(parseFloat(data.amount) + parseFloat(data.withdrawal_fee))}
                </div>
                ` : ''}
                <div class="detail-row">
                    <strong>Payment Mode:</strong> ${data.payment_mode}
                </div>
                <div class="detail-row">
                    <strong>Served By:</strong> ${data.served_by || 'System'}
                </div>
                <div class="footer">
                    <p>Thank you for banking with us!</p>
                    <p>Printed on: ${formatDateTime(new Date())}</p>
                </div>
            </div>
        </body>
        </html>
    `;
    
    receiptWindow.document.write(content);
    receiptWindow.document.close();
    
    setTimeout(() => {
        receiptWindow.print();
        receiptWindow.close();
    }, 500);
}



    // =====================================
    // STATEMENT COMPONENTS
    // =====================================
    $('#printStatement').click(function() {
    var filter = $('#transactionFilter').val();
    var startDate, endDate;
    
    switch(filter) {
        case 'week':
            startDate = moment().subtract(1, 'weeks').format('YYYY-MM-DD');
            endDate = moment().format('YYYY-MM-DD');
            break;
        case 'month':
            startDate = moment().subtract(1, 'months').format('YYYY-MM-DD');
            endDate = moment().format('YYYY-MM-DD');
            break;
        case 'year':
            startDate = moment().subtract(1, 'years').format('YYYY-MM-DD');
            endDate = moment().format('YYYY-MM-DD');
            break;
        case 'custom':
            startDate = $('#startDate').val();
            endDate = $('#endDate').val();
            if (!startDate || !endDate) {
                alert('Please select both start and end dates for custom range');
                return;
            }
            break;
        default:
            startDate = null;
            endDate = null;
    }

    printStatement(startDate, endDate);
});



// Function to print statement
function printStatement(startDate, endDate) {
    // Get filtered transactions
    var transactions = [];
    var table = $('#transactionTable').DataTable();
    table.rows({ search: 'applied' }).every(function(rowIdx) {
        transactions.push(this.data());
    });

    // Calculate totals
    var totalCredit = 0;
    var totalDebit = 0;
    transactions.forEach(function(trans) {
        var amount = parseFloat(trans[2].replace(/[^0-9.-]+/g, ""));
        if (trans[1].toLowerCase().includes('savings') || trans[1].toLowerCase().includes('deposit')) {
            totalCredit += amount;
        } else {
            totalDebit += amount;
        }
    });

    var statementWindow = window.open('', '_blank', 'width=800,height=600');
    var content = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Account Statement</title>
            <style>
                body { 
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    line-height: 1.6;
                }
                .statement {
                    padding: 20px;
                    max-width: 1000px;
                    margin: 0 auto;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .client-info {
                    margin-bottom: 20px;
                }
                .statement-period {
                    margin-bottom: 20px;
                    padding: 10px;
                    background-color: #f8f9fa;
                }
                .summary {
                    margin: 20px 0;
                    padding: 10px;
                    background-color: #f8f9fa;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                th, td {
                    padding: 10px;
                    border: 1px solid #ddd;
                    text-align: left;
                }
                th {
                    background-color: #51087E;
                    color: white;
                }
                .credits {
                    color: green;
                }
                .debits {
                    color: red;
                }
                .footer {
                    text-align: center;
                    margin-top: 20px;
                    border-top: 2px solid #333;
                    padding-top: 10px;
                }
                @media print {
                    body { print-color-adjust: exact; }
                    .statement-period, .summary {
                        -webkit-print-color-adjust: exact;
                        background-color: #f8f9fa !important;
                    }
                    th {
                        -webkit-print-color-adjust: exact;
                        background-color: #51087E !important;
                        color: white !important;
                    }
                }
            </style>
        </head>
        <body>
            <div class="statement">
                <div class="header">
                    <h2>Lato Sacco LTD</h2>
                    <h3>Account Statement</h3>
                </div>
                <div class="client-info">
                    <p><strong>Account Name:</strong> <?= htmlspecialchars($accountDetails['first_name'] . ' ' . $accountDetails['last_name']) ?></p>
                    <p><strong>Shareholder No:</strong> <?= htmlspecialchars($accountDetails['shareholder_no']) ?></p>
                    <p><strong>Account Type:</strong> <?= htmlspecialchars($accountDetails['account_type']) ?></p>
                </div>
                <div class="statement-period">
                    <h4>Statement Period</h4>
                    <p><strong>From:</strong> ${startDate ? formatStatementDate(startDate) : 'Beginning'}</p>
                    <p><strong>To:</strong> ${endDate ? formatStatementDate(endDate) : 'Present'}</p>
                </div>
                <div class="summary">
                    <h4>Transaction Summary</h4>
                    <p><strong>Total Credits:</strong> <span class="credits">${formatStatementCurrency(totalCredit)}</span></p>
                    <p><strong>Total Debits:</strong> <span class="debits">${formatStatementCurrency(totalDebit)}</span></p>
                    <p><strong>Net Movement:</strong> ${formatStatementCurrency(totalCredit - totalDebit)}</p>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${transactions.map(trans => `
                            <tr>
                                <td>${trans[0]}</td>
                                <td>${trans[1]}</td>
                                <td>${trans[2]}</td>
                                <td>${trans[3]}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                <div class="footer">
                    <p>Statement generated on: ${new Date().toLocaleString('en-KE')}</p>
                    <p>This is a computer generated statement and requires no signature</p>
                </div>
            </div>
        </body>
        </html>
    `;
    
    statementWindow.document.write(content);
    statementWindow.document.close();
    
    setTimeout(() => {
        statementWindow.print();
        // Don't close the window after printing so user can save as PDF if needed
    }, 500);
}





    // =====================================
    // HELPER FUNCTIONS
    // =====================================
    
    
    const updateSummaryCards = (response) => {
        $('#totalSavings').text(formatCurrency(response.totalSavings));
        $('#outstandingLoans').text(formatCurrency(response.totalLoanAmount));
        $('#netBalance').text(formatCurrency(response.netBalance));
    };

    const updateWithdrawalTotal = () => {
        const amount = parseFloat($('#withdrawAmount').val()) || 0;
        const fee = parseFloat($('#withdrawalFee').val()) || 0;
        const total = amount + fee;
        $('#totalWithdrawal').val(formatCurrency(total));
    };


    const getFilteredTransactions = () => {
        const transactions = [];
        const table = $('#transactionTable').DataTable();
        table.rows({ search: 'applied' }).every(function(rowIdx) {
            transactions.push(this.data());
        });
        return transactions;
    };

    const calculateTransactionTotals = (transactions) => {
        let totalCredit = 0;
        let totalDebit = 0;
        
        transactions.forEach(trans => {
            const amount = parseFloat(trans[2].replace(/[^0-9.-]+/g, ""));
            if (trans[1].toLowerCase().includes('savings') || trans[1].toLowerCase().includes('deposit')) {
                totalCredit += amount;
            } else {
                totalDebit += amount;
            }
        });

        return { totalCredit, totalDebit };
    };

    const handleResponsiveDesign = () => {
        if ($(window).width() < 768) {
            $('.summary-card').removeClass('h-100');
        } else {
            $('.summary-card').addClass('h-100');
        }
    };

    // =====================================
    // INITIALIZATION
    // =====================================

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

    // Account Performance Chart
    var performanceCtx = document.getElementById('accountPerformanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'bar',
        data: {
            labels: ['Total Savings', 'Outstanding Loans', 'Net Balance'],
            datasets: [{
                label: 'Account Performance',
                data: [
                    <?= $totalSavings ?>, 
                    <?= $totalLoans ?>, 
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

    // Transaction Distribution Chart
    var transactionCtx = document.getElementById('transactionDistributionChart').getContext('2d');
    var transactionData = <?= safeJsonEncode($monthlyTransactions) ?>;
    new Chart(transactionCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(transactionData),
            datasets: [{
                label: 'Monthly Transactions',
                data: Object.values(transactionData),
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
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
                            return 'Transactions: KSh ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });



    
    // Initialize all components
    initializeTables();
    initializeAccountTypeFilter();
    initializeCharts();
    handleAccountTypeFilter();
    handleSavingsForm();
    handleWithdrawalForm();
    handleLoanRepayment();
    handleLoanSelect();
    handleViewSchedule();
    setupEventListeners();

    // Trigger initial data load
    $('#accountTypeFilter').trigger('change');
    $('#loanSelect').trigger('change');
});
</script>
</body>
</html>