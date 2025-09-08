<?php
// Get the user's name for the header
$user_name = $db->user_acc($_SESSION['user_id']);
$first_name = explode(' ', $user_name)[0];
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
        <a class="nav-link" href="../views/field-officer.php">
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
                <a class="collapse-item" href="../models/officer_loan.php">Manage Loans</a>
                <a class="collapse-item" href="../models/officer_arrears.php">Arrears</a>
                <a class="collapse-item" href="../models/officer_disbursement.php">Disbursements</a>
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
                <a class="collapse-item" href="../views/officer_account.php">Client Accounts</a>
                <a class="collapse-item" href="../models/officer_groups.php">Wekeza Groups</a>
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

        <!-- Nav Item - Announcements -->
        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link" href="../views/officer_announcements.php" title="Announcements">
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

<!-- MASTER SIDEBAR CONTROL SCRIPT - OVERRIDES ALL OTHER SCRIPTS -->
<script>
(function() {
    'use strict';
    
    // Wait for DOM and override any existing handlers
    function initializeMasterSidebar() {
        // Remove any existing event listeners by cloning elements
        function removeAllEventListeners(element) {
            if (element) {
                const clone = element.cloneNode(true);
                element.parentNode.replaceChild(clone, element);
                return clone;
            }
            return null;
        }

        // Master toggle function
        function toggleSidebar() {
            const body = document.body;
            const sidebar = document.querySelector('.sidebar');
            
            if (!sidebar) return;
            
            body.classList.toggle('sidebar-toggled');
            sidebar.classList.toggle('toggled');
            
            // Handle mobile special case
            if (window.innerWidth <= 768) {
                if (sidebar.classList.contains('toggled')) {
                    sidebar.classList.add('mobile-expanded');
                } else {
                    sidebar.classList.remove('mobile-expanded');
                }
            }
            
            // Collapse any open accordions when toggling
            if (sidebar.classList.contains('toggled')) {
                const openCollapses = document.querySelectorAll('.sidebar .collapse.show');
                openCollapses.forEach(collapse => {
                    if (window.bootstrap && window.bootstrap.Collapse) {
                        const bsCollapse = window.bootstrap.Collapse.getInstance(collapse);
                        if (bsCollapse) bsCollapse.hide();
                    } else if (window.jQuery) {
                        window.jQuery(collapse).collapse('hide');
                    } else {
                        collapse.classList.remove('show');
                    }
                });
            }
        }

        // Get toggle buttons and remove existing listeners
        let sidebarToggle = removeAllEventListeners(document.querySelector('#sidebarToggle'));
        let sidebarToggleTop = removeAllEventListeners(document.querySelector('#sidebarToggleTop'));

        // Add our master event listeners
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                toggleSidebar();
            });
        }

        if (sidebarToggleTop) {
            sidebarToggleTop.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                toggleSidebar();
            });
        }

        // Fullscreen toggle
        const fullscreenToggle = document.querySelector('#fullscreenToggle');
        if (fullscreenToggle) {
            fullscreenToggle.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch(err => {
                        console.log('Fullscreen error:', err.message);
                    });
                    this.innerHTML = '<i class="fas fa-compress-arrows-alt fa-fw"></i>';
                } else {
                    document.exitFullscreen();
                    this.innerHTML = '<i class="fas fa-expand-arrows-alt fa-fw"></i>';
                }
            });
        }

        // Responsive handler
        function handleResize() {
            const sidebar = document.querySelector('.sidebar');
            if (!sidebar) return;
            
            if (window.innerWidth <= 768) {
                document.body.classList.add('sidebar-toggled');
                sidebar.classList.add('toggled');
                sidebar.classList.remove('mobile-expanded');
            } else {
                sidebar.classList.remove('mobile-expanded');
            }
        }

        // Enhanced dropdown animations
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const dropdown = this.nextElementSibling;
                if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                    dropdown.classList.add('animated--grow-in');
                }
            });
        });

        // Window resize handler
        window.addEventListener('resize', handleResize);
        handleResize(); // Initial call
        
        // Override any conflicting jQuery handlers
        if (window.jQuery) {
            window.jQuery(document).ready(function($) {
                // Remove any existing handlers
                $('#sidebarToggle, #sidebarToggleTop').off('click');
                
                // Ensure our handlers take precedence
                $('#sidebarToggle, #sidebarToggleTop').on('click.masterSidebar', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    toggleSidebar();
                    return false;
                });
            });
        }

        console.log('Master Sidebar Control: Initialized and active');
    }

    // Initialize immediately if DOM is ready, otherwise wait
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeMasterSidebar);
    } else {
        initializeMasterSidebar();
    }
    
    // Also initialize after a short delay to override any late-loading scripts
    setTimeout(initializeMasterSidebar, 500);
    
    // Make the toggle function globally available
    window.masterToggleSidebar = function() {
        const body = document.body;
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebar) {
            body.classList.toggle('sidebar-toggled');
            sidebar.classList.toggle('toggled');
        }
    };
    
})();
</script>