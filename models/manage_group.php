<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../config/config.php';
require_once '../controllers/groupController.php';
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
    <style>
        :root {
            --primary-color: #51087E;
            --secondary-color: #f8f9fc;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
            --sidebar-width: 280px;
            --topbar-height: 70px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-color);
            overflow-x: hidden;
        }

        /* Tab Content */
        .content-section {
            display: none;
            transition: all 0.3s ease;
        }

        .content-section.active {
            display: block;
        }

        /* Ensure container has proper margin for fixed header */
        .container-fluid {
            margin-top: 4.375rem;
            padding: 1.5rem;
        }

        /* Dashboard Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.members { border-left-color: var(--primary-color); }
        .stat-card.savings { border-left-color: var(--success-color); }
        .stat-card.withdrawals { border-left-color: var(--warning-color); }
        .stat-card.balance { border-left-color: var(--info-color); }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-card.members .stat-card-icon { background: var(--primary-color); }
        .stat-card.savings .stat-card-icon { background: var(--success-color); }
        .stat-card.withdrawals .stat-card-icon { background: var(--warning-color); }
        .stat-card.balance .stat-card-icon { background: var(--info-color); }

        .stat-card-content {
            flex: 1;
            text-align: right;
        }

        .stat-card-title {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card-value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin: 5px 0 0 0;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            border: none;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 20px;
            border-bottom: none;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        /* Tables */
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: background-color 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(81, 8, 126, 0.05);
        }

        .table tbody td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
        }

        /* Buttons */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: #3d065d;
            border-color: #3d065d;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .content-section {
                padding: 20px 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                justify-content: center;
            }
        }

        /* Modal Enhancements */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .modal-header {
            border-radius: 12px 12px 0 0;
            border-bottom: none;
        }

        .modal-footer {
            border-top: 1px solid #f1f1f1;
            border-radius: 0 0 12px 12px;
        }

        /* Select2 Customization */
        .select2-container--default .select2-selection--single {
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            height: 42px;
            padding: 8px 12px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 26px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }

        .select2-dropdown {
            border-radius: 8px;
            border: 2px solid #e1e1e1;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px;
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Badge Styles */
        .badge {
            padding: 8px 12px;
            font-size: 0.75rem;
            border-radius: 20px;
        }

        /* Animation classes */
        .animated--grow-in {
            animation-name: growIn;
            animation-duration: 200ms;
            animation-timing-function: transform cubic-bezier(0.18, 1.25, 0.4, 1), opacity cubic-bezier(0.0, 0.0, 0.4, 1);
        }

        @keyframes growIn {
            0% {
                transform: scale(0.9);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            Error: <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($groupDetails): ?>
        <!-- Include Sidebar Component -->
        <?php include '../components/manage_groups/sidebar.php'; ?>

        <!-- Include Members Component -->
        <?php include '../components/manage_groups/members.php'; ?>

        <!-- Include Savings Component -->  
        <?php include '../components/manage_groups/savings.php'; ?>

        <!-- Include Transactions Component -->
        <?php include '../components/manage_groups/transactions.php'; ?>

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

        <!-- End of Content Wrapper -->
        </div>
    </div>
    <?php endif; ?>

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
        // Loading states for forms
        $('#addSavingsForm, #withdrawForm, #addMemberForm').on('submit', function() {
            $(this).addClass('loading');
            $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        });

        // Reset loading states when modals are hidden
        $('.modal').on('hidden.bs.modal', function() {
            $(this).find('form').removeClass('loading');
            $(this).find('button[type="submit"]').prop('disabled', false);
            
            // Reset button text based on modal
            if ($(this).is('#addSavingsModal')) {
                $(this).find('button[type="submit"]').html('Save');
            } else if ($(this).is('#withdrawModal')) {
                $(this).find('button[type="submit"]').html('Withdraw');
            } else if ($(this).is('#addMemberModal')) {
                $(this).find('button[type="submit"]').html('Add Member');
            }
            
            // Reset Select2 elements
            $(this).find('.select2').val(null).trigger('change');
        });

        // Helper function to show messages
        window.showMessage = function(message, type) {
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
                    'border-radius': '8px',
                    'box-shadow': '0 0 20px rgba(0,0,0,0.2)',
                    'max-width': '300px'
                });

            $('body').append(messageDiv);

            setTimeout(function() {
                messageDiv.fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 4000);
        };

        // Keyboard shortcuts
        $(document).keydown(function(e) {
            // Ctrl + 1-4 for tab navigation
            if (e.ctrlKey) {
                switch(e.which) {
                    case 49: // Ctrl + 1 = Dashboard
                        $('.nav-link[data-section="dashboard"]').click();
                        e.preventDefault();
                        break;
                    case 50: // Ctrl + 2 = Members
                        $('.nav-link[data-section="members"]').click();
                        e.preventDefault();
                        break;
                    case 51: // Ctrl + 3 = Savings
                        $('.nav-link[data-section="savings"]').click();
                        e.preventDefault();
                        break;
                    case 52: // Ctrl + 4 = Transactions
                        $('.nav-link[data-section="transactions"]').click();
                        e.preventDefault();
                        break;
                }
            }
        });

        // Clear search on modal close
        $('.modal').on('hidden.bs.modal', function() {
            $(this).find('input[type="text"]').val('');
            $(this).find('select').prop('selectedIndex', 0);
        });

        // Initialize page with fade-in effect
        $('.content-section').hide();
        $('#dashboard-section').addClass('active').fadeIn(500);
        
        console.log('Group Details page initialized successfully');
    });
    </script>

</body>
</html>