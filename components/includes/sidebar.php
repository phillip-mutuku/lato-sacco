<?php
// Get the user's name for the header
$user_name = $db->user_acc($_SESSION['user_id']);
$first_name = explode(' ', $user_name)[0];

// Get user role from session (more efficient than database query)
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
$is_admin = ($user_role === 'admin');
?>

<style>
:root {
    --primary-color: #51087E;
    --primary-light: rgba(81, 8, 126, 0.1);
    --sidebar-width: 14rem;
    --sidebar-collapsed-width: 6.5rem;
}

/* Enhanced Sidebar Styles */
.sidebar {
    background: linear-gradient(180deg, var(--primary-color) 0%, #6a1b99 100%);
    min-height: 100vh;
    width: var(--sidebar-width);
    transition: all 0.3s ease;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15);
}

/* Hide scrollbar in sidebar but allow scrolling */
.sidebar {
    overflow-y: auto;
    overflow-x: hidden;
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.sidebar::-webkit-scrollbar {
    display: none;
}

.sidebar.toggled {
    width: var(--sidebar-collapsed-width);
}

.sidebar .sidebar-brand {
    height: 4.375rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    font-weight: 700;
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.9);
    white-space: nowrap;
    transition: all 0.3s ease;
    padding: 0 1rem;
}

.sidebar .sidebar-brand-icon {
    font-size: 1.5rem;
    margin-right: 0.75rem;
    width: 2rem;
    text-align: center;
}

.sidebar.toggled .sidebar-brand {
    justify-content: center;
}

.sidebar.toggled .sidebar-brand-text {
    display: none;
}

.sidebar .nav-item .nav-link {
    color: rgba(255, 255, 255, 0.85);
    padding: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    border-radius: 0.35rem;
    margin: 0 0.5rem 0.25rem;
    transition: all 0.3s ease;
    position: relative;
    font-size: 0.95rem;
    white-space: nowrap;
}

/* Normal sidebar hover effects */
.sidebar:not(.toggled) .nav-item .nav-link:hover {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.15);
    transform: translateX(3px);
    text-decoration: none;
}

/* Collapsed sidebar hover effects - NO BACKGROUND */
.sidebar.toggled .nav-item .nav-link:hover {
    color: rgba(255, 255, 255, 1);
    background-color: transparent !important;
    text-decoration: none;
    transform: none;
}

/* Remove active background when collapsed */
.sidebar.toggled .nav-item .nav-link.active,
.sidebar.toggled .nav-item.active .nav-link {
    color: rgba(255, 255, 255, 1);
    background-color: transparent !important;
    box-shadow: none;
}

/* Normal active state when expanded */
.sidebar:not(.toggled) .nav-item .nav-link.active,
.sidebar:not(.toggled) .nav-item.active .nav-link {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.25);
    box-shadow: inset 4px 0 0 rgba(255, 255, 255, 0.9);
}

.sidebar .nav-item .nav-link i {
    font-size: 1.1rem;
    margin-right: 1rem;
    width: 1.5rem;
    text-align: center;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

/* Spacing between text and arrow icon */
.sidebar .nav-item .nav-link span {
    flex-grow: 1;
    margin-right: 0.75rem;
}

.sidebar.toggled .nav-item .nav-link {
    justify-content: center;
    padding: 0.875rem 0.25rem;
    margin: 0 0.125rem 0.25rem;
}

.sidebar.toggled .nav-item .nav-link span {
    display: none;
}

.sidebar.toggled .nav-item .nav-link i {
    margin-right: 0;
}

.sidebar .sidebar-heading {
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1rem;
    padding: 1rem 1.5rem 0.5rem;
    transition: all 0.3s ease;
}

.sidebar.toggled .sidebar-heading {
    display: none;
}

.sidebar .collapse-inner {
    border-radius: 0.35rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15);
    margin: 0 0.5rem;
}

.sidebar .collapse-item {
    padding: 0.75rem 1.25rem;
    margin: 0.125rem 0;
    display: block;
    color: #6c757d;
    text-decoration: none;
    border-radius: 0.25rem;
    white-space: nowrap;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.sidebar .collapse-item:hover {
    color: var(--primary-color);
    background-color: var(--primary-light);
    text-decoration: none;
}

/* Content wrapper adjustments - smooth transitions */
#content-wrapper {
    margin-left: var(--sidebar-width);
    width: calc(100% - var(--sidebar-width));
    transition: all 0.3s ease;
    min-height: 100vh;
}

body.sidebar-toggled #content-wrapper {
    margin-left: var(--sidebar-collapsed-width);
    width: calc(100% - var(--sidebar-collapsed-width));
}

/* Main content area */
#content {
    flex: 1;
    padding: 0;
}

/* Enhanced Topbar - Fixed Position with proper positioning */
.topbar {
    height: 4.375rem;
    background-color: #fff;
    border-bottom: 1px solid #e3e6f0;
    position: fixed;
    top: 0;
    left: var(--sidebar-width);
    right: 0;
    z-index: 999;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15);
    transition: all 0.3s ease;
    padding: 0 1rem;
}

body.sidebar-toggled .topbar {
    left: var(--sidebar-collapsed-width);
}

/* Container adjustments for fixed header - proper spacing */
.container-fluid {
    margin-top: 4.375rem;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

/* Sidebar toggle button in topbar - always in fixed header */
#sidebarToggleTop {
    background: none;
    border: none;
    color: #5a5c69;
    font-size: 1.2rem;
    padding: 0.1rem;
    margin-right: 1rem;
    border-radius: 0.35rem;
    transition: all 0.3s ease;
    display: block !important;
    text-decoration: none !important;
}

#sidebarToggleTop:hover {
    background-color: #f8f9fc;
    color: var(--primary-color);
    text-decoration: none !important;
}

#sidebarToggleTop:focus {
    outline: none;
    box-shadow: 0 0 0 0.1rem rgba(81, 8, 126, 0.25);
    text-decoration: none !important;
}

/* Fullscreen toggle - positioned in topbar properly */
#fullscreenToggle {
    color: #5a5c69;
    font-size: 1rem;
    padding: 0.5rem;
    border-radius: 0.25rem;
    transition: all 0.3s ease;
    margin-left: 0;
    text-decoration: none !important;
}

#fullscreenToggle:hover {
    color: var(--primary-color);
    background-color: #f8f9fc;
    text-decoration: none !important;
}

#fullscreenToggle:focus,
#fullscreenToggle:active,
#fullscreenToggle:visited {
    text-decoration: none !important;
}

.topbar .nav-link {
    color: #5a5c69;
    transition: all 0.3s ease;
    position: relative;
    font-size: 1rem;
}

.topbar .nav-link:hover {
    color: var(--primary-color);
}

/* Notification dot instead of badge */
.notification-dot {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 6px;
    height: 6px;
    background-color: #e74a3b;
    border-radius: 50%;
    border: 1px solid #fff;
}

/* Sidebar toggle button - ALWAYS VISIBLE */
#sidebarToggle {
    width: 2.25rem;
    height: 2.25rem;
    background-color: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 50%;
    color: rgba(255, 255, 255, 0.8);
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 1rem auto;
    display: block !important;
}

#sidebarToggle:hover {
    background-color: rgba(255, 255, 255, 0.3);
    color: #fff;
}

#sidebarToggle::after {
    font-weight: 900;
    content: '\f104';
    font-family: 'Font Awesome 5 Free';
    font-size: 0.9rem;
}

.sidebar.toggled #sidebarToggle::after {
    content: '\f105';
}

/* Dropdown improvements */
.dropdown-list {
    max-width: 20rem;
}

.dropdown-list-image {
    position: relative;
}

.status-indicator {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 1rem;
    height: 1rem;
    border: 2px solid #fff;
    border-radius: 50%;
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

/* Left-side buttons container */
.topbar-left {
    display: flex;
    align-items: center;
    position: relative;
}

/* Remove underlines from all topbar links */
.topbar a {
    text-decoration: none !important;
}

.topbar a:hover,
.topbar a:focus,
.topbar a:active,
.topbar a:visited {
    text-decoration: none !important;
}

/* Responsive Design - proper mobile behavior */
@media (max-width: 768px) {
    .sidebar {
        width: var(--sidebar-collapsed-width);
        transform: translateX(-100%);
    }
    
    .sidebar.toggled {
        transform: translateX(0);
        width: var(--sidebar-width);
    }
    
    #content-wrapper {
        margin-left: 0;
        width: 100%;
    }
    
    body.sidebar-toggled #content-wrapper {
        margin-left: 0;
        width: 100%;
    }

    .topbar {
        left: 0;
    }
    
    body.sidebar-toggled .topbar {
        left: 0;
    }

    .sidebar:not(.mobile-expanded) .nav-item .nav-link span {
        display: none;
    }

    .sidebar:not(.mobile-expanded) .sidebar-heading {
        display: none;
    }

    .sidebar:not(.mobile-expanded) .nav-item .nav-link {
        justify-content: center;
        padding: 0.875rem 0.25rem;
        margin: 0 0.125rem 0.25rem;
    }

    .sidebar:not(.mobile-expanded) .nav-item .nav-link i {
        margin-right: 0;
    }

    .sidebar:not(.mobile-expanded) .nav-item .nav-link:hover {
        color: rgba(255, 255, 255, 1);
        background-color: transparent !important;
        transform: none;
    }

    .sidebar:not(.mobile-expanded) .nav-item .nav-link.active,
    .sidebar:not(.mobile-expanded) .nav-item.active .nav-link {
        color: rgba(255, 255, 255, 1);
        background-color: transparent !important;
        box-shadow: none;
    }
}
</style>

<!-- Sidebar -->
<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
    <!-- Sidebar Brand -->
    <a class="sidebar-brand d-flex align-items-center" href="home.php">
        <div class="sidebar-brand-icon">
            <i class="fas fa-university"></i>
        </div>
        <div class="sidebar-brand-text">LATO SACCO</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Dashboard Nav Item -->
    <li class="nav-item active">
        <a class="nav-link" href="../views/home.php">
            <i class="fas fa-fw fa-home"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Management Heading -->
    <div class="sidebar-heading">
        Management
    </div>

    <!-- Loans Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLoans"
            aria-expanded="false" aria-controls="collapseLoans">
            <i class="fas fa-fw fa-comment-dollar"></i>
            <span>Loans</span>
        </a>
        <div id="collapseLoans" class="collapse" aria-labelledby="headingLoans" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../models/loan.php">Manage Loans</a>
                <a class="collapse-item" href="../models/pending_approval.php">Pending Approval</a>
                <a class="collapse-item" 
                   href="../models/disbursement.php">
                    Disbursements
                </a>
                <a class="collapse-item" href="../models/arrears.php">Arrears</a>
            </div>
        </div>
    </li>

    <!-- Financial Management -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseFinance"
            aria-expanded="false" aria-controls="collapseFinance">
            <i class="fas fa-fw fa-chart-line"></i>
            <span>Finance</span>
        </a>
        <div id="collapseFinance" class="collapse" aria-labelledby="headingFinance" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../views/daily-reconciliation.php">
                    Daily Reconciliation
                </a>
                <!-- Expenses Tracking - Admin Only -->
                <?php if ($is_admin): ?>
                <a class="collapse-item" href="../views/expenses_tracking.php">
                    Expenses Tracking
                </a>
                <!-- General Reporting - Admin Only -->
                <a class="collapse-item" href="../views/general_reporting.php">
                    General Reports
                </a>
                <?php endif; ?>
                <a class="collapse-item" href="../views/manage_expenses.php">Manage Expenses</a>
                <a class="collapse-item" href="../views/receipts.php">Receipts</a>
            </div>
        </div>
    </li>

    <!-- Clients Management -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseClients"
            aria-expanded="false" aria-controls="collapseClients">
            <i class="fas fa-fw fa-users"></i>
            <span>Clients</span>
        </a>
        <div id="collapseClients" class="collapse" aria-labelledby="headingClients" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../views/account.php">Client Accounts</a>
                <a class="collapse-item" href="../models/groups.php">Wekeza Groups</a>
                <a class="collapse-item" href="../models/business_groups.php">Business Groups</a>
            </div>
        </div>
    </li>

    <!-- Products -->
    <li class="nav-item">
        <a class="nav-link" href="../models/loan_plan.php">
            <i class="fas fa-fw fa-piggy-bank"></i>
            <span>Loan Products</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- System Heading -->
    <div class="sidebar-heading">
        System
    </div>

    <!-- System Management -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSystem"
            aria-expanded="false" aria-controls="collapseSystem">
            <i class="fas fa-fw fa-cog"></i>
            <span>System</span>
        </a>
        <div id="collapseSystem" class="collapse" aria-labelledby="headingSystem" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <!-- Users - Admin Only -->
                <?php if ($is_admin): ?>
                <a class="collapse-item" href="../models/user.php">Users</a>
                <?php endif; ?>
                <a class="collapse-item" href="../views/settings.php">Settings</a>
                <a class="collapse-item" href="../views/backup.php">Backup</a>
            </div>
        </div>
    </li>

    <!-- Sidebar Toggler (Sidebar) - Always visible -->
    <div class="text-center">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>
<!-- End of Sidebar -->

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
    <!-- Main Content -->
    <div id="content">
        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar static-top shadow">
            <!-- Left side buttons -->
            <div class="topbar-left">
                <!-- Sidebar Toggle (Topbar) -->
                <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>
                
                <!-- Fullscreen Toggle -->
                <a class="nav-link" href="#" id="fullscreenToggle" title="Toggle Fullscreen">
                    <i class="fas fa-expand-arrows-alt fa-fw"></i>
                </a>
            </div>

            <!-- Topbar Navbar -->
            <ul class="navbar-nav ml-auto">
                <!-- Nav Item - Notifications (Admin Only) -->
                <?php if ($is_admin): ?>
                <li class="nav-item dropdown no-arrow mx-1">
                    <a class="nav-link" href="../views/notifications.php" title="Notifications">
                        <i class="fas fa-bell fa-fw"></i>
                        <span class="notification-dot"></span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Nav Item - Announcements -->
                <li class="nav-item dropdown no-arrow mx-1">
                    <a class="nav-link" href="../views/announcements.php" title="Announcements">
                        <i class="fas fa-bullhorn fa-fw"></i>
                        <span class="notification-dot"></span>
                    </a>
                </li>

                <div class="topbar-divider d-none d-sm-block"></div>

                <!-- Nav Item - Logout Only -->
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $user_name; ?></span>
                        <img class="img-profile rounded-circle" src="../public/image/logo.jpg">
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