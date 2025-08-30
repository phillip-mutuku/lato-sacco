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

$accountController = new AccountController();

// Initialize variables
$accountId = $_GET['id'] ?? null;
$accountDetails = null;
$transactions = [];
$loans = [];
$savings = [];
$repayments = [];
$totalSavings = 0;
$totalWithdrawals = 0;
$outstandingLoans = 0;
$activeLoansCount = 0;
$totalGroupSavings = 0;
$error = null;

$accountType = $_GET['account_type'] ?? 'all';

if ($accountId) {
    try {
        $accountDetails = $accountController->getAccountById($accountId);
        if (!$accountDetails) {
            throw new Exception("Account not found.");
        }
        
        // Load all data
        $transactions = $accountController->getAccountTransactions($accountId, $accountType);
        $loans = $accountController->getAccountLoans($accountId, $accountType);
        $repayments = $accountController->getLoanRepayments($accountId);
        $savings = $accountController->getAccountSavings($accountId, $accountType);
        $totalSavings = $accountController->getTotalSavings($accountId, $accountType);
        $totalWithdrawals = $accountController->getTotalWithdrawals($accountId, $accountType);
        
        // Get outstanding loans (principal balance from loan schedule)
        $outstandingLoans = $accountController->getTotalOutstandingLoans($accountId, $accountType);
        
        // Get active loans count (status = 2)
        $activeLoansCount = $accountController->getActiveLoansCount($accountId, $accountType);
        
        // Get total group savings
        $totalGroupSavings = $accountController->getTotalGroupSavings($accountId, $accountType);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error in view_account.php: " . $e->getMessage());
    }
}

// Function to safely encode JSON for JavaScript
function safeJsonEncode($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}
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
    
    <style>
        :root {
            --primary-color: #51087E;
            --primary-hover: #3d0660;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --info-color: #36b9cc;
            --purple-color: #6f42c1;
            --teal-color: #20c997;
            --light-bg: #f8f9fc;
            --white: #ffffff;
            --border-color: #e3e6f0;
            --text-muted: #858796;
            --shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            --border-radius: 0.35rem;
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
        }

        * {
            box-sizing: border-box;
        }

        body { 
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            overflow-x: hidden;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Page Layout */
        .page-container {
            display: flex;
            min-height: 100vh;
        }

        /* Content Area */
        .content-area {
            padding: 25px;
            min-height: calc(100vh - 70px);
        }

        /* Enhanced Filter Section */
        .filter-section {
            background: linear-gradient(135deg, #51087E 0%, #6B1FA0 100%);
            border-radius: 12px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(81, 8, 126, 0.15);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .filter-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            pointer-events: none;
        }

        .filter-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        .filter-icon {
            color: #ffffff;
            font-size: 1.4rem;
            margin-right: 15px;
            background: rgba(255,255,255,0.2);
            padding: 10px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .filter-title {
            color: #ffffff;
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .filter-control-wrapper {
            position: relative;
            z-index: 2;
        }

        .custom-select {
            width: 100%;
            max-width: 400px;
            padding: 1px 15px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            background: rgba(255,255,255,0.95);
            font-size: 1.1rem;
            font-weight: 500;
            color: #333;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 45px;
        }

        .custom-select:focus {
            outline: none;
            border-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(255,255,255,0.3), 0 6px 20px rgba(0,0,0,0.15);
            background: #ffffff;
            transform: translateY(-1px);
        }

        .custom-select:hover {
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .custom-select option {
            background: #ffffff;
            color: #333;
            padding: 12px;
            font-weight: 500;
        }

        .custom-select option:first-child {
            font-weight: 600;
            color: #51087E;
        }

        /* Stats Cards with Drag & Drop */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-left: 4px solid var(--primary-color);
            cursor: move;
            user-select: none;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(58, 59, 69, 0.25);
        }

        .stat-card.dragging {
            opacity: 0.6;
            transform: rotate(5deg) scale(0.95);
            box-shadow: 0 15px 35px rgba(58, 59, 69, 0.4);
            z-index: 1000;
        }

        .stat-card.drag-over {
            border: 2px dashed var(--primary-color);
            background: rgba(81, 8, 126, 0.05);
        }

        .stat-card.savings { border-left-color: var(--success-color); }
        .stat-card.withdrawals { border-left-color: var(--danger-color); }
        .stat-card.loans { border-left-color: var(--warning-color); }
        .stat-card.active-loans { border-left-color: var(--purple-color); }
        .stat-card.group-savings { border-left-color: var(--teal-color); }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .stat-card.savings .stat-icon { color: var(--success-color); }
        .stat-card.withdrawals .stat-icon { color: var(--danger-color); }
        .stat-card.loans .stat-icon { color: var(--warning-color); }
        .stat-card.active-loans .stat-icon { color: var(--purple-color); }
        .stat-card.group-savings .stat-icon { color: var(--teal-color); }

        .stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #333;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .drag-handle {
            position: absolute;
            top: 10px;
            right: 10px;
            color: var(--text-muted);
            font-size: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover .drag-handle {
            opacity: 1;
        }

        .drag-instructions {
            background: rgba(81, 8, 126, 0.1);
            border: 1px solid rgba(81, 8, 126, 0.2);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            color: var(--primary-color);
            font-size: 0.9rem;
            text-align: center;
        }

        /* Content Sections */
        .content-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .content-section.active {
            display: block !important;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Toast Container */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast-modern {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 15px 20px;
            margin-bottom: 10px;
            min-width: 300px;
            border-left: 4px solid var(--success-color);
            transform: translateX(350px);
            transition: transform 0.3s ease;
        }

        .toast-modern.show {
            transform: translateX(0);
        }

        .toast-modern.error {
            border-left-color: var(--danger-color);
        }

        .toast-modern.warning {
            border-left-color: var(--warning-color);
        }

        .toast-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .toast-body {
            color: var(--text-muted);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .content-area {
                padding: 15px;
            }
            
            .filter-section {
                padding: 20px;
                margin-bottom: 25px;
            }
            
            .filter-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .filter-title {
                font-size: 1.1rem;
            }
            
            .custom-select {
                max-width: 100%;
                padding: 12px 16px;
                font-size: 1rem;
            }

            .drag-instructions {
                display: none;
            }
        }

        /* Loading state for select */
        .custom-select.loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .custom-select.loading::after {
            content: '';
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            border: 2px solid #51087E;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <div class="page-container">
        <!-- Include Sidebar -->
        <?php include '../components/account/sidebar.php'; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error: <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($accountDetails): ?>
                <!-- Dashboard Section -->
                <div class="content-section active" id="dashboard-section">
                    <!-- Filter Section -->
                    <div class="filter-section">
                        <div class="filter-header">
                            <i class="fas fa-filter filter-icon"></i>
                            <h3 class="filter-title">Filter by Account Type</h3>
                        </div>
                        <div class="filter-control-wrapper">
                            <select id="accountTypeFilter" class="custom-select">
                                <option value="all" selected>All Account Types</option>
                                <?php
                                if (isset($accountDetails['account_type']) && !empty($accountDetails['account_type'])) {
                                    $accountTypes = explode(', ', $accountDetails['account_type']);
                                    foreach($accountTypes as $type): 
                                        $type = trim($type);
                                        if (!empty($type)): ?>
                                            <option value="<?= htmlspecialchars($type) ?>">
                                                <?= htmlspecialchars(ucfirst($type)) ?>
                                            </option>
                                        <?php endif;
                                    endforeach; 
                                }?>
                            </select>
                        </div>
                    </div>

                    <!-- Drag Instructions -->
                    <div class="drag-instructions">
                        <i class="fas fa-hand-rock"></i>
                        Drag and drop the cards below to rearrange them according to your preference
                    </div>

                    <!-- Stats Grid with Drag & Drop -->
                    <div class="stats-grid" id="statsGrid">
                        <div class="stat-card" data-card-id="shareholder" draggable="true">
                            <div class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="stat-value"><?= htmlspecialchars($accountDetails['shareholder_no'] ?? 'N/A') ?></div>
                            <div class="stat-label">Shareholder Number</div>
                        </div>
                        
                        <div class="stat-card savings" data-card-id="savings" draggable="true">
                            <div class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-piggy-bank"></i>
                            </div>
                            <div class="stat-value" id="totalSavings">KSh <?= number_format($totalSavings, 2) ?></div>
                            <div class="stat-label">Total Savings</div>
                        </div>
                        
                        <div class="stat-card withdrawals" data-card-id="withdrawals" draggable="true">
                            <div class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <div class="stat-value" id="totalWithdrawals">KSh <?= number_format($totalWithdrawals, 2) ?></div>
                            <div class="stat-label">Total Withdrawals</div>
                        </div>
                        
                        <div class="stat-card loans" data-card-id="loans" draggable="true">
                            <div class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="stat-value" id="outstandingLoans">KSh <?= number_format($outstandingLoans, 2) ?></div>
                            <div class="stat-label">Outstanding Loans</div>
                        </div>
                        
                        <div class="stat-card active-loans" data-card-id="active-loans" draggable="true">
                            <div class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-value" id="activeLoansCount"><?= $activeLoansCount ?></div>
                            <div class="stat-label">Active Loans</div>
                        </div>
                        
                        <div class="stat-card group-savings" data-card-id="group-savings" draggable="true">
                            <div class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-value" id="totalGroupSavings">KSh <?= number_format($totalGroupSavings, 2) ?></div>
                            <div class="stat-label">Total Group Savings</div>
                        </div>
                    </div>
                </div>

                <!-- Include Component Sections -->
                <?php include '../components/account/savings.php'; ?>
                <?php include '../components/account/transactions.php'; ?>          
                <?php include '../components/account/client-info.php'; ?>
                 <?php include '../components/account/fully-paid-loans.php'; ?>
                <?php include '../components/account/loans.php'; ?>            
                <?php include '../components/account/repayments.php'; ?>
             
            <?php endif; ?>
        </div>
    </div>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">System Information</h5>
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

    <!-- Bootstrap core JavaScript-->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>
    <script src="../public/js/jquery.easing.js"></script>
    <script src="../public/js/sb-admin-2.js"></script>
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>

    <script>
    $(document).ready(function() {
        // =====================================
        // CONSTANTS AND UTILITIES
        // =====================================
        const ACCOUNT_ID = <?= $accountId ?>;
        let draggedElement = null;
        
        // Hide loading overlay after page loads
        setTimeout(function() {
            $('#loadingOverlay').fadeOut(300);
        }, 800);

        // =====================================
        // DRAG AND DROP FUNCTIONALITY
        // =====================================
        
        function initializeDragAndDrop() {
            const statsGrid = document.getElementById('statsGrid');
            const statCards = statsGrid.querySelectorAll('.stat-card');

            // Load saved card order from localStorage
            loadCardOrder();

            statCards.forEach(card => {
                card.addEventListener('dragstart', handleDragStart);
                card.addEventListener('dragover', handleDragOver);
                card.addEventListener('dragenter', handleDragEnter);
                card.addEventListener('dragleave', handleDragLeave);
                card.addEventListener('drop', handleDrop);
                card.addEventListener('dragend', handleDragEnd);
            });

            function handleDragStart(e) {
                draggedElement = this;
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.outerHTML);
            }

            function handleDragOver(e) {
                if (e.preventDefault) {
                    e.preventDefault();
                }
                e.dataTransfer.dropEffect = 'move';
                return false;
            }

            function handleDragEnter(e) {
                this.classList.add('drag-over');
            }

            function handleDragLeave(e) {
                this.classList.remove('drag-over');
            }

            function handleDrop(e) {
                if (e.stopPropagation) {
                    e.stopPropagation();
                }

                if (draggedElement !== this) {
                    const draggedIndex = Array.from(statsGrid.children).indexOf(draggedElement);
                    const targetIndex = Array.from(statsGrid.children).indexOf(this);

                    if (draggedIndex < targetIndex) {
                        statsGrid.insertBefore(draggedElement, this.nextSibling);
                    } else {
                        statsGrid.insertBefore(draggedElement, this);
                    }

                    saveCardOrder();
                    showToast('Card layout updated successfully!', 'success');
                }

                return false;
            }

            function handleDragEnd(e) {
                const statCards = statsGrid.querySelectorAll('.stat-card');
                statCards.forEach(card => {
                    card.classList.remove('dragging', 'drag-over');
                });
            }

            function saveCardOrder() {
                const cardOrder = Array.from(statsGrid.children).map(card => 
                    card.getAttribute('data-card-id')
                );
                localStorage.setItem('statsCardOrder_' + ACCOUNT_ID, JSON.stringify(cardOrder));
            }

            function loadCardOrder() {
                const savedOrder = localStorage.getItem('statsCardOrder_' + ACCOUNT_ID);
                if (savedOrder) {
                    try {
                        const cardOrder = JSON.parse(savedOrder);
                        const cards = {};
                        
                        // Store current cards by their ID
                        statsGrid.querySelectorAll('.stat-card').forEach(card => {
                            const cardId = card.getAttribute('data-card-id');
                            cards[cardId] = card;
                        });

                        // Clear the grid
                        statsGrid.innerHTML = '';

                        // Append cards in saved order
                        cardOrder.forEach(cardId => {
                            if (cards[cardId]) {
                                statsGrid.appendChild(cards[cardId]);
                            }
                        });

                        // Add any cards that weren't in the saved order (new cards)
                        Object.values(cards).forEach(card => {
                            if (!statsGrid.contains(card)) {
                                statsGrid.appendChild(card);
                            }
                        });
                    } catch (e) {
                        console.error('Error loading card order:', e);
                    }
                }
            }
        }

        // Initialize drag and drop
        initializeDragAndDrop();

        // =====================================
        // TOAST NOTIFICATIONS
        // =====================================
        function showToast(message, type = 'success') {
            const toastId = 'toast-' + Date.now();
            const toastClass = type === 'error' ? 'error' : type === 'warning' ? 'warning' : '';
            const iconClass = type === 'error' ? 'fa-exclamation-triangle' : type === 'warning' ? 'fa-exclamation-circle' : 'fa-check-circle';
            
            const toast = `
                <div class="toast-modern ${toastClass}" id="${toastId}">
                    <div class="toast-header">
                        <i class="fas ${iconClass}" style="margin-right: 8px;"></i>
                        <span>${type.charAt(0).toUpperCase() + type.slice(1)}</span>
                        <button type="button" style="margin-left: auto; background: none; border: none; font-size: 1.2rem; cursor: pointer;" onclick="closeToast('${toastId}')">&times;</button>
                    </div>
                    <div class="toast-body">${message}</div>
                </div>
            `;
            
            $('#toastContainer').append(toast);
            
            setTimeout(() => {
                $(`#${toastId}`).addClass('show');
            }, 100);
            
            setTimeout(() => {
                closeToast(toastId);
            }, 5000);
        }
        
        function closeToast(toastId) {
            $(`#${toastId}`).removeClass('show');
            setTimeout(() => {
                $(`#${toastId}`).remove();
            }, 300);
        }
        
        window.closeToast = closeToast;
        window.showToast = showToast;

        // =====================================
        // UTILITY FUNCTIONS
        // =====================================
        
        function formatCurrency(amount) {
            return parseFloat(amount).toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatDateTime(dateString) {
            return new Date(dateString).toLocaleString('en-KE', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        window.formatCurrency = formatCurrency;
        window.formatDateTime = formatDateTime;

       // ACCOUNT TYPE FILTER (SERVER-SIDE)
        // =====================================
        
        $('#accountTypeFilter').change(function() {
            const selectedType = $(this).val();
            
            // Add loading state to select
            $(this).addClass('loading');
            
            // Show loading state for all cards
            updateStatCard('#totalSavings', 'Loading...');
            updateStatCard('#totalWithdrawals', 'Loading...');
            updateStatCard('#outstandingLoans', 'Loading...');
            updateStatCard('#activeLoansCount', 'Loading...');
            updateStatCard('#totalGroupSavings', 'Loading...');
            
            // Make AJAX request to get filtered data
            $.ajax({
                url: '../controllers/accountController.php',
                method: 'GET',
                data: {
                    action: 'getFilteredSummary',
                    accountId: ACCOUNT_ID,
                    accountType: selectedType
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Update summary cards with actual filtered data
                        updateStatCard('#totalSavings', 'KSh ' + formatCurrency(response.totalSavings));
                        updateStatCard('#totalWithdrawals', 'KSh ' + formatCurrency(response.totalWithdrawals));
                        updateStatCard('#outstandingLoans', 'KSh ' + formatCurrency(response.outstandingLoans));
                        updateStatCard('#activeLoansCount', response.activeLoansCount);
                        updateStatCard('#totalGroupSavings', 'KSh ' + formatCurrency(response.totalGroupSavings));
                    } else {
                        showToast('Error filtering account data: ' + response.message, 'error');
                        resetStatCards();
                    }
                },
                error: function(xhr, status, error) {
                    showToast('Error loading filtered data', 'error');
                    resetStatCards();
                },
                complete: function() {
                    // Remove loading state from select
                    $('#accountTypeFilter').removeClass('loading');
                }
            });
        });
        
        function updateStatCard(selector, value) {
            const $element = $(selector);
            $element.fadeOut(200, function() {
                $element.text(value);
                $element.fadeIn(200);
            });
        }
        
        function resetStatCards() {
            updateStatCard('#totalSavings', 'KSh <?= number_format($totalSavings, 2) ?>');
            updateStatCard('#totalWithdrawals', 'KSh <?= number_format($totalWithdrawals, 2) ?>');
            updateStatCard('#outstandingLoans', 'KSh <?= number_format($outstandingLoans, 2) ?>');
            updateStatCard('#activeLoansCount', '<?= $activeLoansCount ?>');
            updateStatCard('#totalGroupSavings', 'KSh <?= number_format($totalGroupSavings, 2) ?>');
        }

        // Initialize with default value
        $('#accountTypeFilter').val('all');

        // =====================================
        // REAL-TIME UPDATES FOR TRANSACTIONS
        // =====================================
        
        // Listen for loan repayment success
        $(document).on('loanRepaymentSuccess', function(e, data) {
            if (data.newOutstandingLoans !== undefined) {
                updateStatCard('#outstandingLoans', 'KSh ' + formatCurrency(data.newOutstandingLoans));
            }
            
            // Refresh all stats to ensure consistency
            const currentAccountType = $('#accountTypeFilter').val();
            $('#accountTypeFilter').trigger('change');
        });

        // Listen for savings success
        $(document).on('savingsSuccess', function() {
            const currentAccountType = $('#accountTypeFilter').val();
            $('#accountTypeFilter').trigger('change');
        });

        // Listen for withdrawal success  
        $(document).on('withdrawalSuccess', function() {
            const currentAccountType = $('#accountTypeFilter').val();
            $('#accountTypeFilter').trigger('change');
        });

        // Enhanced event handlers for better integration
        $(document).on('loanRepaymentProcessed', function(e, response) {
            if (response.status === 'success') {
                if (response.newOutstandingLoans !== undefined) {
                    updateStatCard('#outstandingLoans', 'KSh ' + formatCurrency(response.newOutstandingLoans));
                }
                
                showToast('Loan repayment processed successfully!', 'success');
                
                setTimeout(() => {
                    $('#accountTypeFilter').trigger('change');
                }, 1000);
            }
        });

        $(document).on('savingsProcessed', function(e, response) {
            if (response.status === 'success') {
                showToast('Savings processed successfully!', 'success');
                
                setTimeout(() => {
                    $('#accountTypeFilter').trigger('change');
                }, 1000);
            }
        });

        $(document).on('withdrawalProcessed', function(e, response) {
            if (response.status === 'success') {
                showToast('Withdrawal processed successfully!', 'success');
                
                setTimeout(() => {
                    $('#accountTypeFilter').trigger('change');
                }, 1000);
            }
        });

        // =====================================
        // PRINTING FUNCTIONALITY
        // =====================================
        
        function printLoanRepaymentReceipt(data) {
            const receiptWindow = window.open('', '_blank', 'width=400,height=600');
            const content = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Loan Repayment Receipt</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
                        .receipt { border: 1px solid #ccc; padding: 20px; max-width: 800px; margin: 0 auto; }
                        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                        .detail-row { margin: 10px 0; border-bottom: 1px solid #eee; padding-bottom: 5px; }
                        .footer { text-align: center; margin-top: 20px; border-top: 2px solid #333; padding-top: 10px; }
                    </style>
                </head>
                <body>
                    <div class="receipt">
                        <div class="header">
                            <h2>Lato Sacco LTD</h2>
                            <h3>Loan Repayment Receipt</h3>
                        </div>
                        <div class="detail-row"><strong>Receipt No:</strong> ${data.receipt_number || 'N/A'}</div>
                        <div class="detail-row"><strong>Date:</strong> ${formatDateTime(data.date_paid)}</div>
                        <div class="detail-row"><strong>Client Name:</strong> ${data.first_name} ${data.last_name}</div>
                        <div class="detail-row"><strong>Loan Ref No:</strong> ${data.loan_ref_no}</div>
                        <div class="detail-row"><strong>Amount Paid:</strong> KSh ${formatCurrency(data.amount_repaid)}</div>
                        <div class="detail-row"><strong>Payment Mode:</strong> ${data.payment_mode}</div>
                        <div class="detail-row"><strong>Served By:</strong> ${data.served_by || 'System'}</div>
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
            setTimeout(() => { receiptWindow.print(); receiptWindow.close(); }, 500);
        }

        function printSavingsReceipt(data, type) {
            const receiptWindow = window.open('', '_blank', 'width=400,height=600');
            const content = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${type} Receipt</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
                        .receipt { border: 1px solid #ccc; padding: 20px; max-width: 800px; margin: 0 auto; }
                        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                        .detail-row { margin: 10px 0; border-bottom:1px solid #eee; padding-bottom: 5px; }
                        .footer { text-align: center; margin-top: 20px; border-top: 2px solid #333; padding-top: 10px; }
                    </style>
                </head>
                <body>
                    <div class="receipt">
                        <div class="header">
                            <h2>Lato Sacco LTD</h2>
                            <h3>${type} Receipt</h3>
                        </div>
                        <div class="detail-row"><strong>Receipt No:</strong> ${data.receipt_number}</div>
                        <div class="detail-row"><strong>Date:</strong> ${formatDateTime(data.date)}</div>
                        <div class="detail-row"><strong>Client Name:</strong> ${data.client_name}</div>
                        <div class="detail-row"><strong>Account Type:</strong> ${data.account_type}</div>
                        <div class="detail-row"><strong>Amount:</strong> KSh ${formatCurrency(data.amount)}</div>
                        ${data.withdrawal_fee ? `<div class="detail-row"><strong>Withdrawal Fee:</strong> KSh ${formatCurrency(data.withdrawal_fee)}</div>` : ''}
                        <div class="detail-row"><strong>Payment Mode:</strong> ${data.payment_mode}</div>
                        <div class="detail-row"><strong>Served By:</strong> ${data.served_by || 'System'}</div>
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
            setTimeout(() => { receiptWindow.print(); receiptWindow.close(); }, 500);
        }

        function printWithdrawalReceipt(data) {
            printSavingsReceipt(data, 'Withdrawal');
        }

        // Make print functions globally accessible
        window.printLoanRepaymentReceipt = printLoanRepaymentReceipt;
        window.printSavingsReceipt = printSavingsReceipt;
        window.printWithdrawalReceipt = printWithdrawalReceipt;

        // =====================================
        // RESPONSIVE HANDLING
        // =====================================
        
        $(window).resize(function() {
            if (window.innerWidth > 768) {
                $('#sidebar').removeClass('mobile-open');
            }
            
            // Redraw DataTables on resize
            setTimeout(() => {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            }, 100);

            // Reinitialize drag and drop after resize
            setTimeout(() => {
                initializeDragAndDrop();
            }, 200);
        });

        // =====================================
        // MODAL AND BOOTSTRAP FIXES
        // =====================================
        
        $(document).on('click', '.dropdown-menu', function(e) {
            e.stopPropagation();
        });

        $(document).on('hidden.bs.modal', '.modal', function () {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        });

        $(document).on('show.bs.modal', '.modal', function () {
            const zIndex = 1040 + (10 * $('.modal:visible').length);
            $(this).css('z-index', zIndex);
            setTimeout(() => {
                $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
            }, 0);
        });

        // =====================================
        // KEYBOARD SHORTCUTS
        // =====================================
        
        $(document).keydown(function(e) {
            // Alt + R to reset card order
            if (e.altKey && e.keyCode === 82) {
                e.preventDefault();
                localStorage.removeItem('statsCardOrder_' + ACCOUNT_ID);
                location.reload();
                showToast('Card layout reset to default!', 'success');
            }
            
            // Alt + F to focus filter
            if (e.altKey && e.keyCode === 70) {
                e.preventDefault();
                $('#accountTypeFilter').focus();
            }
        });

        // =====================================
        // INITIALIZATION AND CLEANUP
        // =====================================
        
        // Initialize dashboard section as active on page load
        setTimeout(() => {
            $(document).trigger('sectionChanged', ['dashboard']);
        }, 1000);

        // Cleanup function for when page is unloaded
        $(window).on('beforeunload', function() {
            clearTimeout();
            
            $(document).off('loanRepaymentProcessed');
            $(document).off('savingsProcessed'); 
            $(document).off('withdrawalProcessed');
            $(document).off('loanRepaymentSuccess');
            $(document).off('savingsSuccess');
            $(document).off('withdrawalSuccess');
        });

        // =====================================
        // ERROR HANDLING AND DEBUGGING
        // =====================================
        
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            if (xhr.status !== 0) {
                console.error('AJAX Error:', {
                    url: settings.url,
                    status: xhr.status,
                    error: thrownError,
                    response: xhr.responseText
                });
                
                showToast('An error occurred while processing your request. Please try again.', 'error');
            }
        });

        // Log account information for debugging
        console.log('Account Details Loaded:', {
            accountId: ACCOUNT_ID,
            outstandingLoans: <?= $outstandingLoans ?>,
            activeLoansCount: <?= $activeLoansCount ?>,
            totalGroupSavings: <?= $totalGroupSavings ?>
        });

        // =====================================
        // ADDITIONAL FEATURES
        // =====================================
        
        // Double-click to reset single card position
        $('.stat-card').on('dblclick', function() {
            const cardId = $(this).attr('data-card-id');
            showToast('Double-click feature: Card "' + cardId + '" selected. Use Alt+R to reset all positions.', 'info');
        });

        // Add tooltip for drag handles
        $('.drag-handle').attr('title', 'Drag to reorder cards');

        // Show keyboard shortcuts help
        if (!localStorage.getItem('keyboardShortcutsShown_' + ACCOUNT_ID)) {
            setTimeout(() => {
                showToast('Tip: Use Alt+R to reset card layout, Alt+F to focus filter', 'info');
                localStorage.setItem('keyboardShortcutsShown_' + ACCOUNT_ID, 'true');
            }, 3000);
        }

        // Trigger initial filter to load data
        setTimeout(() => {
            $('#accountTypeFilter').trigger('change');
        }, 500);
    });
    </script>
</body>
</html>