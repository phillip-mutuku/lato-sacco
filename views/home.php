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
        }

        /* Dashboard Cards */
        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15);
            border: none;
            border-radius: 0.35rem;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.25rem 2rem 0 rgba(33, 40, 50, 0.2);
        }

        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 100%;
            padding: 20px;
        }

        /* Custom Bar Chart Styles */
        .bar-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            height: 250px;
            padding: 20px 10px;
            background: #f8f9fc;
            border-radius: 5px;
        }

        .bar {
            flex: 1;
            margin: 0 2px;
            background: linear-gradient(to top, var(--primary-color), #7209b7);
            border-radius: 4px 4px 0 0;
            position: relative;
            transition: all 0.3s ease;
            min-height: 10px;
        }

        .bar:hover {
            opacity: 0.8;
            transform: scaleY(1.05);
        }

        .bar-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 11px;
            font-weight: bold;
            color: #5a5c69;
        }

        .bar-value {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .bar:hover .bar-value {
            opacity: 1;
        }

        /* Custom Line Chart Styles */
        .line-chart {
            height: 250px;
            background: #f8f9fc;
            border-radius: 5px;
            position: relative;
            padding: 20px;
        }

        .line-chart svg {
            width: 100%;
            height: 100%;
        }

        .line-path {
            fill: none;
            stroke: #1cc88a;
            stroke-width: 3;
            stroke-linejoin: round;
            stroke-linecap: round;
        }

        .line-area {
            fill: url(#lineGradient);
            opacity: 0.3;
        }

        .line-point {
            fill: #1cc88a;
            stroke: white;
            stroke-width: 2;
            r: 4;
            cursor: pointer;
        }

        .line-point:hover {
            r: 6;
            fill: #17a673;
        }

        .chart-labels {
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            margin-top: 10px;
        }

        .chart-label {
            font-size: 11px;
            font-weight: bold;
            color: #5a5c69;
        }

        /* Custom Donut Chart Styles */
        .donut-chart {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
            position: relative;
        }

        .donut-svg {
            width: 150px;
            height: 150px;
            transform: rotate(-90deg);
        }

        .donut-segment {
            fill: none;
            stroke-width: 25;
            cursor: pointer;
            transition: stroke-width 0.3s ease;
        }

        .donut-segment:hover {
            stroke-width: 30;
        }

        .donut-legend {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        /* Ensure content is always visible */
        .container-fluid {
            overflow: visible !important;
            min-width: 0 !important;
        }

        .row {
            margin-right: 0 !important;
            margin-left: 0 !important;
        }

        .col-xl-3, .col-xl-6, .col-xl-8, .col-xl-4, .col-lg-6, .col-lg-7, .col-lg-5, .col-md-6 {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
            min-width: 0 !important;
        }

        /* Fullscreen styles */
        .fullscreen-active {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            background: white;
        }

        .fullscreen-active .sidebar {
            display: none;
        }

        .fullscreen-active #content-wrapper {
            margin-left: 0;
            width: 100%;
        }

        /* Tooltip styles */
        .chart-tooltip {
            position: absolute;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Include Sidebar and Header -->
        <?php include '../components/includes/sidebar.php'; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800"><?php echo $greeting . ", " . $first_name; ?></h1>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Completed Loans Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary h-100 py-2" style="border-left-color: var(--primary-color);">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: var(--primary-color);">Completed Loans</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completed_loans; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Savings Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Savings</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">KSh <?php echo number_format($net_savings, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-piggy-bank fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Clients Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Clients</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_clients; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Payments Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Total Payments for <?php echo date('F'); ?>
                                                </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">KSh <?php echo number_format($total_payments, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Loan Distribution Chart -->
        <div class="col-xl-6 col-lg-6">
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Loan Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <div id="loanDistributionChart" class="bar-chart"></div>
                        <div class="chart-labels">
                            <span class="chart-label">Jan</span>
                            <span class="chart-label">Feb</span>
                            <span class="chart-label">Mar</span>
                            <span class="chart-label">Apr</span>
                            <span class="chart-label">May</span>
                            <span class="chart-label">Jun</span>
                            <span class="chart-label">Jul</span>
                            <span class="chart-label">Aug</span>
                            <span class="chart-label">Sep</span>
                            <span class="chart-label">Oct</span>
                            <span class="chart-label">Nov</span>
                            <span class="chart-label">Dec</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Savings Trend Chart -->
        <div class="col-xl-6 col-lg-6">
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Savings Trend</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <div id="savingsTrendChart" class="line-chart">
                            <svg viewBox="0 0 400 200">
                                <defs>
                                    <linearGradient id="lineGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" style="stop-color:#1cc88a;stop-opacity:0.3" />
                                        <stop offset="100%" style="stop-color:#1cc88a;stop-opacity:0.1" />
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                        <div class="chart-labels">
                            <span class="chart-label">Jan</span>
                            <span class="chart-label">Feb</span>
                            <span class="chart-label">Mar</span>
                            <span class="chart-label">Apr</span>
                            <span class="chart-label">May</span>
                            <span class="chart-label">Jun</span>
                            <span class="chart-label">Jul</span>
                            <span class="chart-label">Aug</span>
                            <span class="chart-label">Sep</span>
                            <span class="chart-label">Oct</span>
                            <span class="chart-label">Nov</span>
                            <span class="chart-label">Dec</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Recent Loans Table -->
        <div class="col-xl-8 col-lg-7">
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Recent Loans</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="recentLoansTable" width="100%" cellspacing="0">
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
                                    <td><?php echo $row['ref_no']; ?></td>
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
                                    <td><?php echo date('Y-m-d', strtotime($row['date_applied'])); ?></td>
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
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Client Activity</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <div id="clientActivityChart" class="donut-chart">
                            <svg class="donut-svg" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="37.5" fill="none" stroke="#e74a3b" stroke-width="25" 
                                        stroke-dasharray="0 235.6" class="donut-segment" id="activeLoansSegment"></circle>
                                <circle cx="50" cy="50" r="37.5" fill="none" stroke="#36a2eb" stroke-width="25" 
                                        stroke-dasharray="0 235.6" class="donut-segment" id="savingsSegment"></circle>
                                <circle cx="50" cy="50" r="37.5" fill="none" stroke="#ffc107" stroke-width="25" 
                                        stroke-dasharray="0 235.6" class="donut-segment" id="inactiveSegment"></circle>
                            </svg>
                            <div class="donut-legend">
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #e74a3b;"></div>
                                    <span>Active Loans (<?php echo $active_loans; ?>)</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #36a2eb;"></div>
                                    <span>Savings (<?php echo $savings_accounts; ?>)</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #ffc107;"></div>
                                    <span>Inactive (<?php echo $inactive_clients; ?>)</span>
                                </div>
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

<!-- Tooltip element -->
<div id="chartTooltip" class="chart-tooltip"></div>

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
        document.querySelector('.sidebar').classList.toggle('toggled');
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
            document.querySelector('.sidebar').classList.add('toggled');
            // Collapse any open accordions
            document.querySelectorAll('.sidebar .collapse.show').forEach(collapse => {
                collapse.classList.remove('show');
            });
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize(); // Call on load

    // Custom Chart Data
    const loanData = <?php echo json_encode($loan_distribution); ?>;
    const savingsData = <?php echo json_encode($savings_trend); ?>;
    const clientActivityData = [<?php echo $active_loans . ',' . $savings_accounts . ',' . $inactive_clients; ?>];
    
    // Create Bar Chart for Loan Distribution
    function createBarChart() {
        const chartContainer = document.getElementById('loanDistributionChart');
        const maxValue = Math.max(...loanData);
        
        loanData.forEach((value, index) => {
            const bar = document.createElement('div');
            bar.className = 'bar';
            const height = maxValue > 0 ? (value / maxValue) * 100 : 10;
            bar.style.height = height + '%';
            
            const label = document.createElement('div');
            label.className = 'bar-label';
            label.textContent = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                               'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][index];
            
            const valueLabel = document.createElement('div');
            valueLabel.className = 'bar-value';
            valueLabel.textContent = 'KSh ' + (value ? value.toLocaleString() : '0');
            
            bar.appendChild(label);
            bar.appendChild(valueLabel);
            chartContainer.appendChild(bar);
        });
    }

    // Create Line Chart for Savings Trend
    function createLineChart() {
        const svg = document.querySelector('#savingsTrendChart svg');
        const maxValue = Math.max(...savingsData);
        const width = 400;
        const height = 200;
        const padding = 20;
        
        // Create path data
        let pathData = '';
        let areaData = '';
        const points = [];
        
        savingsData.forEach((value, index) => {
            const x = padding + (index * (width - 2 * padding) / (savingsData.length - 1));
            const y = height - padding - (maxValue > 0 ? (value / maxValue) * (height - 2 * padding) : 0);
            
            if (index === 0) {
                pathData += `M ${x} ${y}`;
                areaData += `M ${x} ${height - padding} L ${x} ${y}`;
            } else {
                pathData += ` L ${x} ${y}`;
                areaData += ` L ${x} ${y}`;
            }
            
            points.push({ x, y, value });
        });
        
        // Close area path
        areaData += ` L ${padding + (savingsData.length - 1) * (width - 2 * padding) / (savingsData.length - 1)} ${height - padding} Z`;
        
        // Create area
        const area = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        area.setAttribute('d', areaData);
        area.setAttribute('class', 'line-area');
        svg.appendChild(area);
        
        // Create line
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', pathData);
        path.setAttribute('class', 'line-path');
        svg.appendChild(path);
        
        // Create points
        points.forEach((point, index) => {
            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', point.x);
            circle.setAttribute('cy', point.y);
            circle.setAttribute('class', 'line-point');
            circle.setAttribute('r', '4');
            
            // Add hover events
            circle.addEventListener('mouseenter', function(e) {
                showTooltip(e, `Month: ${['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                                        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][index]}<br>
                                Amount: KSh ${point.value.toLocaleString()}`);
                this.setAttribute('r', '6');
            });
            
            circle.addEventListener('mouseleave', function() {
                hideTooltip();
                this.setAttribute('r', '4');
            });
            
            svg.appendChild(circle);
        });
    }

    // Create Donut Chart for Client Activity
    function createDonutChart() {
        const total = clientActivityData.reduce((sum, val) => sum + val, 0);
        if (total === 0) return;
        
        const radius = 37.5;
        const circumference = 2 * Math.PI * radius;
        let currentAngle = 0;
        
        const segments = ['activeLoansSegment', 'savingsSegment', 'inactiveSegment'];
        const colors = ['#e74a3b', '#36a2eb', '#ffc107'];
        
        clientActivityData.forEach((value, index) => {
            const percentage = value / total;
            const dashArray = percentage * circumference;
            const dashOffset = -currentAngle * circumference / 100;
            
            const segment = document.getElementById(segments[index]);
            segment.style.strokeDasharray = `${dashArray} ${circumference}`;
            segment.style.strokeDashoffset = dashOffset;
            segment.style.stroke = colors[index];
            
            // Add hover events
            segment.addEventListener('mouseenter', function(e) {
                showTooltip(e, `${['Active Loans', 'Savings Accounts', 'Inactive Clients'][index]}: ${value} (${(percentage * 100).toFixed(1)}%)`);
                this.style.strokeWidth = '30';
            });
            
            segment.addEventListener('mouseleave', function() {
                hideTooltip();
                this.style.strokeWidth = '25';
            });
            
            currentAngle += percentage * 100;
        });
    }

    // Tooltip functions
    function showTooltip(event, content) {
        const tooltip = document.getElementById('chartTooltip');
        tooltip.innerHTML = content;
        tooltip.style.opacity = '1';
        tooltip.style.left = event.pageX + 10 + 'px';
        tooltip.style.top = event.pageY - 10 + 'px';
    }

    function hideTooltip() {
        document.getElementById('chartTooltip').style.opacity = '0';
    }

    // Initialize all charts
    createBarChart();
    createLineChart();
    createDonutChart();


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
        if (window.pageYOffset > 100) {
            scrollButton.style.display = 'block';
        } else {
            scrollButton.style.display = 'none';
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

    // Card hover effects
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 0.25rem 2rem 0 rgba(33, 40, 50, 0.2)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15)';
        });
    });

    // Chart animation on load
    setTimeout(() => {
        document.querySelectorAll('.bar').forEach((bar, index) => {
            bar.style.animation = `slideUp 0.8s ease ${index * 0.1}s forwards`;
        });
    }, 300);

    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideUp {
            from {
                height: 0;
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .bar {
            opacity: 0;
        }
        
        .line-path {
            stroke-dasharray: 1000;
            stroke-dashoffset: 1000;
            animation: drawLine 2s ease forwards;
        }
        
        @keyframes drawLine {
            to {
                stroke-dashoffset: 0;
            }
        }
        
        .line-point {
            opacity: 0;
            animation: fadeInPoint 0.5s ease forwards;
            animation-delay: 2s;
        }
        
        @keyframes fadeInPoint {
            to {
                opacity: 1;
            }
        }
        
        .donut-segment {
            stroke-dasharray: 0 235.6;
            animation: drawDonut 1.5s ease forwards;
            animation-delay: 0.5s;
        }
        
        @keyframes drawDonut {
            to {
                stroke-dasharray: var(--dash-array) 235.6;
            }
        }
    `;
    document.head.appendChild(style);

    // Update donut segments with CSS custom properties for animation
    setTimeout(() => {
        const total = clientActivityData.reduce((sum, val) => sum + val, 0);
        if (total > 0) {
            const segments = ['activeLoansSegment', 'savingsSegment', 'inactiveSegment'];
            const circumference = 2 * Math.PI * 37.5;
            
            clientActivityData.forEach((value, index) => {
                const percentage = value / total;
                const dashArray = percentage * circumference;
                const segment = document.getElementById(segments[index]);
                segment.style.setProperty('--dash-array', dashArray);
            });
        }
    }, 100);
});
</script>

</body>
</html>