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

    // Get the current hour to determine the greeting
    $current_hour = date('H');
    if ($current_hour < 12) {
        $greeting = "Good morning";
    } elseif ($current_hour < 18) {
        $greeting = "Good afternoon";
    } else {
        $greeting = "Good evening";
    }

// Get the user's name
$user_name = $db->user_acc($_SESSION['user_id']);
$first_name = explode(' ', $user_name)[0];
        
    // Fetch data for charts and metrics
    $completed_loans = $db->conn->query("SELECT COUNT(*) as count FROM `loan` WHERE `status`='3'")->fetch_assoc()['count'];
    
    // Calculate total savings without deductions
    $total_savings = $db->conn->query("SELECT SUM(amount) as total FROM `savings`")->fetch_assoc()['total'] ?? 0;
 
    // For now, we'll assume there's no separate deduction calculation
    $net_savings = $total_savings;
    
    $total_clients = $db->conn->query("SELECT COUNT(*) as count FROM `client_accounts`")->fetch_assoc()['count'];
    
    $current_month = date('Y-m');
    $total_payments = $db->conn->query("SELECT SUM(pay_amount) as total FROM `payment` WHERE DATE_FORMAT(date_created, '%Y-%m') = '$current_month'")->fetch_assoc()['total'] ?? 0;

    // Fetch data for charts
    $loan_distribution = [];
    $savings_trend = [];
    for($i = 1; $i <= 12; $i++){
        $month = date('Y-') . str_pad($i, 2, '0', STR_PAD_LEFT);
        $loan_total = $db->conn->query("SELECT SUM(amount) as total FROM `loan` WHERE DATE_FORMAT(date_applied, '%Y-%m') = '$month'")->fetch_assoc()['total'] ?? 0;
        $loan_distribution[] = $loan_total;
        
        $savings_total = $db->conn->query("SELECT SUM(amount) as total FROM `savings` WHERE DATE_FORMAT(date, '%Y-%m') = '$month'")->fetch_assoc()['total'] ?? 0;
        $savings_trend[] = $savings_total;
    }

    $active_loans = $db->conn->query("SELECT COUNT(DISTINCT account_id) as count FROM `loan` WHERE status = 2")->fetch_assoc()['count'];
    $savings_accounts = $db->conn->query("SELECT COUNT(DISTINCT account_id) as count FROM `savings` WHERE amount > 0")->fetch_assoc()['count'];
    $inactive_clients = $total_clients - $active_loans - $savings_accounts;

    // Fetch recent loans
    $recent_loans = $db->conn->query("SELECT ref_no, amount, status, date_applied FROM `loan` ORDER BY date_applied DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lato Management System - Growing with you</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #51087E;
            --primary-light: rgba(81, 8, 126, 0.1);
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --dark-color: #5a5c69;
            --light-gray: #f8f9fc;
            --border-light: #e3e6f0;
            --shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            --shadow-hover: 0 0.25rem 2rem 0 rgba(58, 59, 69, 0.25);
        }

        body {
            background: linear-gradient(135deg, #f8f9fc 0%, #e3e6f0 100%);
            min-height: 100vh;
        }

        /* Enhanced Dashboard Cards */
        .card {
            box-shadow: var(--shadow);
            border: none;
            border-radius: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            position: relative;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color) 0%, #8e44ad 100%);
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Metric Cards Styling */
        .metric-card {
            position: relative;
            background: linear-gradient(135deg, #fff 0%, #f8f9fc 100%);
        }

        .metric-card .card-body {
            position: relative;
            z-index: 2;
        }

        .metric-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(81, 8, 126, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .metric-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0;
        }

        .metric-icon {
            font-size: 2.5rem;
            opacity: 0.3;
            transition: all 0.3s ease;
        }

        .card:hover .metric-icon {
            opacity: 0.6;
            transform: scale(1.1);
        }

        /* Chart Containers */
        .chart-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fc 100%);
            min-height: 400px;
        }

        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 100%;
            padding: 1rem;
        }

        .chart-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, var(--light-gray) 25%, transparent 25%), 
                        linear-gradient(-45deg, var(--light-gray) 25%, transparent 25%), 
                        linear-gradient(45deg, transparent 75%, var(--light-gray) 75%), 
                        linear-gradient(-45deg, transparent 75%, var(--light-gray) 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark-color);
            font-weight: 600;
            border: 2px dashed var(--border-light);
        }

        /* Bar Chart Styles */
        .bar-chart {
            display: flex;
            align-items: end;
            justify-content: space-between;
            height: 250px;
            padding: 1rem 0;
            gap: 0.5rem;
        }

        .bar {
            flex: 1;
            background: linear-gradient(180deg, var(--primary-color) 0%, #8e44ad 100%);
            border-radius: 0.25rem 0.25rem 0 0;
            position: relative;
            transition: all 0.3s ease;
            min-height: 10px;
        }

        .bar:hover {
            filter: brightness(1.1);
            transform: scaleY(1.05);
        }

        .bar-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.75rem;
            color: var(--dark-color);
            font-weight: 600;
        }

        /* Line Chart Styles */
        .line-chart {
            position: relative;
            height: 250px;
            padding: 1rem;
        }

        .line-chart svg {
            width: 100%;
            height: 100%;
        }

        /* Doughnut Chart Styles */
        .doughnut-chart {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto;
        }

        .doughnut-chart svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        /* Table Enhancements */
        .table-responsive {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary-color) 0%, #8e44ad 100%);
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: var(--primary-light);
            transform: scale(1.01);
        }

        .table td {
            border-color: var(--border-light);
            padding: 1rem;
            vertical-align: middle;
        }

        /* Status Badges */
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        /* Header Enhancements */
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #8e44ad 100%);
            color: white;
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }

        .page-header h1 {
            margin: 0;
            font-weight: 700;
        }

        /* Button Enhancements */
        .btn {
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #f4b942 100%);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .metric-value {
                font-size: 1.5rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .page-header {
                padding: 1.5rem;
            }
            
            .chart-container {
                height: 250px;
            }
        }

        /* Animation classes */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.6s ease forwards;
        }

        /* Stagger animation delays */
        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }
        .card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Include Sidebar and Header -->
        <?php include '../components/includes/sidebar.php'; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><?php echo $greeting . ", " . $first_name; ?></h1>
                        <p class="mb-0 opacity-75">Welcome to your dashboard overview</p>
                    </div>
                    <a href="#" class="btn btn-warning shadow-sm" id="generateReportBtn">
                        <i class="fas fa-download fa-sm me-2"></i> Generate Report
                    </a>
                </div>
            </div>

            <!-- Content Row - Metrics -->
            <div class="row">
                <!-- Completed Loans Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card metric-card animate-fade-in">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="metric-label" style="color: var(--primary-color);">Completed Loans</div>
                                    <div class="metric-value"><?php echo number_format($completed_loans); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle metric-icon" style="color: var(--primary-color);"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Savings Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card metric-card animate-fade-in">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="metric-label" style="color: var(--success-color);">Total Savings</div>
                                    <div class="metric-value">KSh <?php echo number_format($net_savings, 0); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-piggy-bank metric-icon" style="color: var(--success-color);"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Clients Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card metric-card animate-fade-in">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="metric-label" style="color: var(--info-color);">Total Clients</div>
                                    <div class="metric-value"><?php echo number_format($total_clients); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users metric-icon" style="color: var(--info-color);"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Payments Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card metric-card animate-fade-in">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="metric-label" style="color: var(--warning-color);">
                                        Payments - <?php echo date('F'); ?>
                                    </div>
                                    <div class="metric-value">KSh <?php echo number_format($total_payments, 0); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-dollar-sign metric-icon" style="color: var(--warning-color);"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Row - Charts -->
            <div class="row">
                <!-- Loan Distribution Chart -->
                <div class="col-xl-6 col-lg-6">
                    <div class="card chart-card mb-4 animate-fade-in">
                        <div class="card-header py-3 bg-transparent">
                            <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">
                                <i class="fas fa-chart-bar me-2"></i>Loan Distribution
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div class="bar-chart" id="loanChart">
                                    <?php 
                                    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                    $maxLoan = max($loan_distribution) ?: 1;
                                    for($i = 0; $i < 12; $i++): 
                                        $height = ($loan_distribution[$i] / $maxLoan) * 100;
                                    ?>
                                    <div class="bar" style="height: <?php echo $height; ?>%;" 
                                         data-value="<?php echo number_format($loan_distribution[$i]); ?>"
                                         title="<?php echo $months[$i] . ': KSh ' . number_format($loan_distribution[$i]); ?>">
                                        <div class="bar-label"><?php echo $months[$i]; ?></div>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Savings Trend Chart -->
                <div class="col-xl-6 col-lg-6">
                    <div class="card chart-card mb-4 animate-fade-in">
                        <div class="card-header py-3 bg-transparent">
                            <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">
                                <i class="fas fa-chart-line me-2"></i>Savings Trend
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div class="line-chart">
                                    <svg viewBox="0 0 400 200" id="savingsChart">
                                        <?php
                                        $maxSavings = max($savings_trend) ?: 1;
                                        $points = [];
                                        for($i = 0; $i < 12; $i++) {
                                            $x = ($i * 400 / 11);
                                            $y = 200 - (($savings_trend[$i] / $maxSavings) * 180);
                                            $points[] = "$x,$y";
                                        }
                                        $pointsStr = implode(' ', $points);
                                        ?>
                                        <!-- Grid lines -->
                                        <defs>
                                            <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#e3e6f0" stroke-width="1"/>
                                            </pattern>
                                        </defs>
                                        <rect width="100%" height="100%" fill="url(#grid)" />
                                        
                                        <!-- Area fill -->
                                        <path d="M 0,200 <?php echo $pointsStr; ?> L 400,200 Z" 
                                              fill="rgba(28, 200, 138, 0.1)" />
                                        
                                        <!-- Line -->
                                        <polyline points="<?php echo $pointsStr; ?>" 
                                                  fill="none" 
                                                  stroke="rgb(28, 200, 138)" 
                                                  stroke-width="3" 
                                                  stroke-linecap="round" 
                                                  stroke-linejoin="round"/>
                                        
                                        <!-- Data points -->
                                        <?php for($i = 0; $i < 12; $i++): 
                                            $x = ($i * 400 / 11);
                                            $y = 200 - (($savings_trend[$i] / $maxSavings) * 180);
                                        ?>
                                        <circle cx="<?php echo $x; ?>" cy="<?php echo $y; ?>" r="4" 
                                                fill="rgb(28, 200, 138)" stroke="white" stroke-width="2"
                                                class="data-point" 
                                                title="<?php echo $months[$i] . ': KSh ' . number_format($savings_trend[$i]); ?>">
                                        </circle>
                                        <?php endfor; ?>
                                        
                                        <!-- Month labels -->
                                        <?php for($i = 0; $i < 12; $i++): 
                                            $x = ($i * 400 / 11);
                                        ?>
                                        <text x="<?php echo $x; ?>" y="220" 
                                              text-anchor="middle" 
                                              font-size="12" 
                                              fill="#5a5c69">
                                            <?php echo $months[$i]; ?>
                                        </text>
                                        <?php endfor; ?>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Row - Table and Activity -->
            <div class="row">
                <!-- Recent Loans Table -->
                <div class="col-xl-8 col-lg-7">
                    <div class="card mb-4 animate-fade-in">
                        <div class="card-header py-3 bg-transparent">
                            <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">
                                <i class="fas fa-list me-2"></i>Recent Loans
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover" id="recentLoansTable">
                                    <thead>
                                        <tr>
                                            <th>Ref No</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date Applied</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $recent_loans->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo $row['ref_no']; ?></strong></td>
                                            <td>KSh <?php echo number_format($row['amount'], 2); ?></td>
                                            <td>
                                                <?php
                                                    switch($row['status']){
                                                        case 0: echo '<span class="badge badge-warning">Pending</span>'; break;
                                                        case 1: echo '<span class="badge badge-info">Released</span>'; break;
                                                        case 2: echo '<span class="badge badge-primary">Active</span>'; break;
                                                        case 3: echo '<span class="badge badge-success">Completed</span>'; break;
                                                        case 4: echo '<span class="badge badge-danger">Denied</span>'; break;
                                                    }
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($row['date_applied'])); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Client Activity -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card mb-4 animate-fade-in">
                        <div class="card-header py-3 bg-transparent">
                            <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">
                                <i class="fas fa-chart-pie me-2"></i>Client Activity
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div class="doughnut-chart">
                                    <?php
                                    $total = $active_loans + $savings_accounts + $inactive_clients;
                                    $activeLoansPercent = $total > 0 ? ($active_loans / $total) * 100 : 0;
                                    $savingsPercent = $total > 0 ? ($savings_accounts / $total) * 100 : 0;
                                    $inactivePercent = $total > 0 ? ($inactive_clients / $total) * 100 : 0;
                                    
                                    $radius = 80;
                                    $circumference = 2 * pi() * $radius;
                                    ?>
                                    <svg viewBox="0 0 200 200">
                                        <!-- Active Loans -->
                                        <circle cx="100" cy="100" r="<?php echo $radius; ?>"
                                                fill="transparent"
                                                stroke="#e74a3b"
                                                stroke-width="20"
                                                stroke-dasharray="<?php echo ($activeLoansPercent/100) * $circumference; ?> <?php echo $circumference; ?>"
                                                stroke-dashoffset="0"/>
                                        
                                        <!-- Savings Accounts -->
                                        <circle cx="100" cy="100" r="<?php echo $radius; ?>"
                                                fill="transparent"
                                                stroke="#36b9cc"
                                                stroke-width="20"
                                                stroke-dasharray="<?php echo ($savingsPercent/100) * $circumference; ?> <?php echo $circumference; ?>"
                                                stroke-dashoffset="<?php echo -($activeLoansPercent/100) * $circumference; ?>"/>
                                        
                                        <!-- Inactive Clients -->
                                        <circle cx="100" cy="100" r="<?php echo $radius; ?>"
                                                fill="transparent"
                                                stroke="#f6c23e"
                                                stroke-width="20"
                                                stroke-dasharray="<?php echo ($inactivePercent/100) * $circumference; ?> <?php echo $circumference; ?>"
                                                stroke-dashoffset="<?php echo -(($activeLoansPercent + $savingsPercent)/100) * $circumference; ?>"/>
                                        
                                        <!-- Center text -->
                                        <text x="100" y="95" text-anchor="middle" font-size="24" font-weight="bold" fill="#5a5c69">
                                            <?php echo $total; ?>
                                        </text>
                                        <text x="100" y="115" text-anchor="middle" font-size="12" fill="#858796">
                                            Total Clients
                                        </text>
                                    </svg>
                                </div>
                                
                                <div class="chart-legend">
                                    <div class="legend-item">
                                        <div class="legend-color" style="background-color: #e74a3b;"></div>
                                        <span>Active Loans (<?php echo $active_loans; ?>)</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color" style="background-color: #36b9cc;"></div>
                                        <span>Savings (<?php echo $savings_accounts; ?>)</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color" style="background-color: #f6c23e;"></div>
                                        <span>Inactive (<?php echo $inactive_clients; ?>)</span>
                                    </div>
                                </div>
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
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                <a class="btn btn-danger" href="logout.php">Logout</a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced Sidebar Toggle Functionality
    function toggleSidebar() {
        document.body.classList.toggle('sidebar-toggled');
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.toggle('toggled');
        }
    }

    // Sidebar toggle buttons
    const sidebarToggle = document.querySelector('#sidebarToggle');
    const sidebarToggleTop = document.querySelector('#sidebarToggleTop');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

    if (sidebarToggleTop) {
        sidebarToggleTop.addEventListener('click', toggleSidebar);
    }

    // Fullscreen Toggle Functionality
    const fullscreenToggle = document.querySelector('#fullscreenToggle');
    if (fullscreenToggle) {
        fullscreenToggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.log(`Error attempting to enable fullscreen: ${err.message}`);
                });
                this.innerHTML = '<i class="fas fa-compress-arrows-alt fa-fw"></i>';
            } else {
                document.exitFullscreen();
                this.innerHTML = '<i class="fas fa-expand-arrows-alt fa-fw"></i>';
            }
        });
    }

    // Responsive behavior
    function handleResize() {
        if (window.innerWidth < 768) {
            document.body.classList.add('sidebar-toggled');
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.add('toggled');
            }
            // Collapse any open accordions
            document.querySelectorAll('.sidebar .collapse.show').forEach(collapse => {
                collapse.classList.remove('show');
            });
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize(); // Call on load

    // Generate Report Button
    const generateReportBtn = document.getElementById('generateReportBtn');
    if (generateReportBtn) {
        generateReportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Show loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin fa-sm me-2"></i> Generating...';
            this.disabled = true;
            
            // Use jQuery if available, otherwise use fetch
            if (typeof $ !== 'undefined') {
                $.ajax({
                    url: 'generate_report.php',
                    method: 'GET',
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function(response) {
                        var blob = new Blob([response], { type: 'application/pdf' });
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = "Loan_Report_" + new Date().toISOString().slice(0,10) + ".pdf";
                        link.click();
                    },
                    error: function(xhr, status, error) {
                        if(xhr.responseType !== 'blob') {
                            alert("Error generating report: " + xhr.responseText);
                        } else {
                            var reader = new FileReader();
                            reader.onload = function() {
                                alert("Error generating report: " + reader.result);
                            }
                            reader.readAsText(xhr.response);
                        }
                    },
                    complete: function() {
                        // Restore button state
                        generateReportBtn.innerHTML = originalText;
                        generateReportBtn.disabled = false;
                    }
                });
            } else {
                // Fallback using fetch
                fetch('generate_report.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.blob();
                    })
                    .then(blob => {
                        const link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = "Loan_Report_" + new Date().toISOString().slice(0,10) + ".pdf";
                        link.click();
                    })
                    .catch(error => {
                        alert("Error generating report: " + error.message);
                    })
                    .finally(() => {
                        // Restore button state
                        generateReportBtn.innerHTML = originalText;
                        generateReportBtn.disabled = false;
                    });
            }
        });
    }

    // Smooth scrolling
    document.querySelectorAll('a.scroll-to-top').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    });

    // Show/hide scroll to top button
    window.addEventListener('scroll', function() {
        const scrollButton = document.querySelector('.scroll-to-top');
        if (scrollButton) {
            if (window.pageYOffset > 100) {
                scrollButton.style.display = 'block';
            } else {
                scrollButton.style.display = 'none';
            }
        }
    });

    // Enhanced dropdown animations
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const dropdown = this.nextElementSibling;
            if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                dropdown.classList.add('animated--grow-in');
            }
        });
    });

    // Card hover effects and animations
    document.querySelectorAll('.card').forEach((card, index) => {
        // Add staggered animation
        card.style.animationDelay = (index * 0.1) + 's';
        
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
            this.style.boxShadow = 'var(--shadow-hover)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'var(--shadow)';
        });
    });

    // Interactive chart elements
    
    // Bar chart tooltips
    document.querySelectorAll('.bar').forEach(bar => {
        bar.addEventListener('mouseenter', function() {
            this.style.filter = 'brightness(1.1)';
            this.style.transform = 'scaleY(1.05)';
            
            // Show tooltip
            const tooltip = document.createElement('div');
            tooltip.className = 'chart-tooltip';
            tooltip.innerHTML = this.getAttribute('title');
            tooltip.style.cssText = `
                position: absolute;
                background: rgba(0,0,0,0.8);
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                pointer-events: none;
                z-index: 1000;
                top: -40px;
                left: 50%;
                transform: translateX(-50%);
                white-space: nowrap;
            `;
            this.appendChild(tooltip);
        });

        bar.addEventListener('mouseleave', function() {
            this.style.filter = '';
            this.style.transform = '';
            
            // Remove tooltip
            const tooltip = this.querySelector('.chart-tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        });
    });

    // SVG data points tooltips
    document.querySelectorAll('.data-point').forEach(point => {
        point.addEventListener('mouseenter', function() {
            this.setAttribute('r', '6');
            this.style.filter = 'drop-shadow(0 2px 4px rgba(0,0,0,0.2))';
        });

        point.addEventListener('mouseleave', function() {
            this.setAttribute('r', '4');
            this.style.filter = '';
        });
    });

    // Table row animations
    document.querySelectorAll('.table tbody tr').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'var(--primary-light)';
            this.style.transform = 'scale(1.01)';
        });

        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
            this.style.transform = '';
        });
    });

    // Metric cards counter animation
    document.querySelectorAll('.metric-value').forEach(metric => {
        const finalValue = parseInt(metric.textContent.replace(/[^0-9]/g, '')) || 0;
        const prefix = metric.textContent.replace(/[0-9,]/g, '');
        let currentValue = 0;
        const increment = Math.ceil(finalValue / 50);
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                currentValue = finalValue;
                clearInterval(timer);
            }
            metric.textContent = prefix + currentValue.toLocaleString();
        }, 30);
    });

    // Initialize tooltips for better UX
    function initTooltips() {
        document.querySelectorAll('[title]').forEach(element => {
            element.addEventListener('mouseenter', function(e) {
                if (this.getAttribute('title')) {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'custom-tooltip';
                    tooltip.textContent = this.getAttribute('title');
                    tooltip.style.cssText = `
                        position: fixed;
                        background: rgba(0,0,0,0.9);
                        color: white;
                        padding: 6px 10px;
                        border-radius: 4px;
                        font-size: 12px;
                        pointer-events: none;
                        z-index: 10000;
                        max-width: 200px;
                        word-wrap: break-word;
                    `;
                    document.body.appendChild(tooltip);
                    
                    const updatePosition = (e) => {
                        tooltip.style.left = (e.clientX + 10) + 'px';
                        tooltip.style.top = (e.clientY - 30) + 'px';
                    };
                    
                    updatePosition(e);
                    this.addEventListener('mousemove', updatePosition);
                    
                    this.addEventListener('mouseleave', () => {
                        tooltip.remove();
                        this.removeEventListener('mousemove', updatePosition);
                    }, { once: true });
                }
            });
        });
    }
    
    initTooltips();

    // Performance optimization - Intersection Observer for animations
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.card').forEach(card => {
            observer.observe(card);
        });
    }

    console.log('Dashboard initialized successfully');
});

// Utility function for number formatting
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

// Error handling
window.addEventListener('error', function(e) {
    console.error('Dashboard error:', e.error);
});

</script>

</body>
</html>