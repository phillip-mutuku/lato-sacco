<?php
// components/manage_groups/sidebar.php
?>

<style>
:root {
    --primary-color: #51087E;
    --primary-hover: #3d0660;
    --primary-light: rgba(81, 8, 126, 0.1);
    --sidebar-width: 14rem;
    --sidebar-collapsed-width: 6.5rem;
    --white: #ffffff;
    --light-bg: #f8f9fc;
    --border-color: #e3e6f0;
    --text-muted: #858796;
    --shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    --border-radius: 0.35rem;
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
    box-shadow: 4px 0 15px rgba(0,0,0,0.1);
    overflow-y: auto;
    overflow-x: hidden;
    color: var(--white);
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.sidebar::-webkit-scrollbar {
    display: none;
}

.sidebar.toggled {
    width: var(--sidebar-collapsed-width);
}

/* Back Button Section */
.back-section {
    padding: 15px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.back-btn {
    background: rgba(255,255,255,0.1);
    color: var(--white);
    border: 1px solid rgba(255,255,255,0.2);
    padding: 8px 15px;
    border-radius: var(--border-radius);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    font-size: 0.85rem;
    width: 100%;
    justify-content: flex-start;
}

.back-btn:hover {
    background: rgba(255,255,255,0.2);
    color: var(--white);
    text-decoration: none;
    transform: translateX(-3px);
}

.sidebar.toggled .back-btn {
    justify-content: center;
    padding: 8px;
}

.sidebar.toggled .back-btn .back-text {
    display: none;
}

/* Group Information */
.group-info {
    text-align: center;
    transition: all 0.3s ease;
    padding: 15px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar.toggled .group-info {
    opacity: 0;
    height: 0;
    padding: 0;
    overflow: hidden;
    border-bottom: none;
}

.group-name {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: var(--white);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.group-reference {
    font-size: 0.85rem;
    opacity: 0.8;
    margin: 0;
}

/* Navigation Items */
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
    text-decoration: none;
    border: none;
    background: none;
    cursor: pointer;
    width: calc(100% - 1rem);
}

.sidebar:not(.toggled) .nav-item .nav-link:hover {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.15);
    transform: translateX(3px);
    text-decoration: none;
}

.sidebar.toggled .nav-item .nav-link:hover {
    color: rgba(255, 255, 255, 1);
    background-color: rgba(255, 255, 255, 0.1);
    text-decoration: none;
    transform: none;
}

.sidebar.toggled .nav-item .nav-link.active,
.sidebar.toggled .nav-item.active .nav-link {
    color: rgba(255, 255, 255, 1);
    background-color: rgba(255, 255, 255, 0.1);
}

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

.sidebar .nav-item .nav-link span {
    flex-grow: 1;
    transition: all 0.3s ease;
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

/* Sidebar toggle button at bottom */
.sidebar-toggle-bottom {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    width: 2.5rem;
    height: 2.5rem;
    background-color: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 50%;
    color: rgba(255, 255, 255, 0.8);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1001;
}

.sidebar-toggle-bottom:hover {
    background-color: rgba(255, 255, 255, 0.3);
    color: #fff;
}

.sidebar-toggle-bottom::after {
    font-weight: 900;
    content: '\f104';
    font-family: 'Font Awesome 5 Free';
    font-size: 1rem;
}

.sidebar.toggled .sidebar-toggle-bottom::after {
    content: '\f105';
}

/* Content wrapper adjustments */
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

/* Enhanced Topbar */
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
    padding: 0 0.2rem;
}

body.sidebar-toggled .topbar {
    left: var(--sidebar-collapsed-width);
}

/* Container adjustments for fixed header */
.container-fluid {
    margin-top: 4.375rem;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

/* Fullscreen toggle */
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

.topbar .nav-link {
    color: #5a5c69;
    transition: all 0.3s ease;
    position: relative;
    font-size: 1rem;
    text-decoration: none !important;
}

.topbar .nav-link:hover {
    color: var(--primary-color);
    text-decoration: none !important;
}

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

/* Responsive Design */
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

    .sidebar.toggled .nav-item .nav-link span {
        display: block;
    }

    .sidebar.toggled .nav-item .nav-link {
        justify-content: flex-start;
        padding: 0.875rem;
        margin: 0 0.5rem 0.25rem;
    }

    .sidebar.toggled .nav-item .nav-link i {
        margin-right: 1rem;
    }

    .sidebar.toggled .group-info {
        opacity: 1;
        height: auto;
        padding: 15px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        overflow: visible;
    }

    .sidebar.toggled .back-btn {
        justify-content: flex-start;
        padding: 8px 15px;
    }

    .sidebar.toggled .back-btn .back-text {
        display: inline;
    }
}
</style>

<!-- Sidebar -->
<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
    <!-- Back Button Section -->
    <div class="back-section">
        <a href="../models/cashier_groups.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            <span class="back-text">Back to Groups</span>
        </a>
    </div>

    <!-- Group Information -->
    <?php if ($groupDetails): ?>
    <div class="group-info">
        <h2 class="group-name"><?= htmlspecialchars($groupDetails['group_name']) ?></h2>
        <p class="group-reference">Ref: <?= htmlspecialchars($groupDetails['group_reference']) ?></p>
    </div>
    <?php endif; ?>

    <!-- Dashboard Nav Item -->
    <li class="nav-item active">
        <a class="nav-link" href="#" data-section="dashboard">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Members -->
    <li class="nav-item">
        <a class="nav-link" href="#" data-section="members">
            <i class="fas fa-fw fa-users"></i>
            <span>Members</span>
        </a>
    </li>

    <!-- Savings & Withdrawals -->
    <li class="nav-item">
        <a class="nav-link" href="#" data-section="savings">
            <i class="fas fa-fw fa-piggy-bank"></i>
            <span>Savings & Withdrawals</span>
        </a>
    </li>

    <!-- Transaction History -->
    <li class="nav-item">
        <a class="nav-link" href="#" data-section="transactions">
            <i class="fas fa-fw fa-exchange-alt"></i>
            <span>Transaction History</span>
        </a>
    </li>

    <!-- Sidebar Toggler at Bottom -->
    <button class="sidebar-toggle-bottom" id="sidebarToggle"></button>
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
                <!-- Fullscreen Toggle -->
                <a class="nav-link" href="#" id="fullscreenToggle" title="Toggle Fullscreen">
                    <i class="fas fa-expand-arrows-alt fa-fw"></i>
                </a>
            </div>

            <!-- Topbar Navbar -->
            <ul class="navbar-nav ml-auto">
                <div class="topbar-divider d-none d-sm-block"></div>

                <!-- Nav Item - User -->
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $db->user_acc($_SESSION['user_id']); ?></span>
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

            <!-- Scripts -->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>
    <script src="../public/js/jquery.easing.js"></script>
    <script src="../public/js/select2.js"></script>
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>
    <script src="../public/js/sb-admin-2.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced Sidebar Toggle Functionality
    function toggleSidebar() {
        document.body.classList.toggle('sidebar-toggled');
        document.querySelector('.sidebar').classList.toggle('toggled');
    }

    // Sidebar toggle button at bottom
    const sidebarToggle = document.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // Fullscreen Toggle Functionality
    const fullscreenToggle = document.querySelector('#fullscreenToggle');
    if (fullscreenToggle) {
        fullscreenToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
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

    // Handle sidebar navigation with improved visibility control
    $(document).on('click', '.nav-link[data-section]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Tab clicked:', $(this).data('section')); // Debug log
        
        // Remove active class from all nav links
        $('.nav-link').removeClass('active');
        $('.nav-item').removeClass('active');
        
        // Add active class to clicked nav link
        $(this).addClass('active');
        $(this).parent().addClass('active');
        
        // Hide all content sections
        $('.content-section').hide().removeClass('active');
        
        // Show selected content section
        const section = $(this).data('section');
        const targetSection = $(`#${section}-section`);
        
        console.log('Target section:', section, 'Found elements:', targetSection.length); // Debug log
        
        if (targetSection.length > 0) {
            targetSection.addClass('active').show();
            console.log('Section shown:', section); // Debug log
        } else {
            console.error('Section not found:', section);
        }
        
        // Close mobile sidebar on selection
        if (window.innerWidth <= 768) {
            if (document.querySelector('.sidebar').classList.contains('toggled')) {
                toggleSidebar();
            }
        }
        
        // Trigger custom event for section change
        $(document).trigger('sectionChanged', [section]);
        
        // Reinitialize DataTables if switching to a section with tables
        setTimeout(function() {
            if (section === 'members' && $.fn.DataTable.isDataTable('#membersTable')) {
                $('#membersTable').DataTable().columns.adjust().responsive.recalc();
            }
            if (section === 'savings' && $.fn.DataTable.isDataTable('#savingsTable')) {
                $('#savingsTable').DataTable().columns.adjust().responsive.recalc();
            }
            if (section === 'transactions' && $.fn.DataTable.isDataTable('#transactionTable')) {
                $('#transactionTable').DataTable().columns.adjust().responsive.recalc();
            }
        }, 100);
    });

    // Responsive behavior
    function handleResize() {
        if (window.innerWidth < 768) {
            // On mobile, start with sidebar hidden
            document.body.classList.add('sidebar-toggled');
            document.querySelector('.sidebar').classList.remove('toggled');
        } else {
            // On desktop, ensure proper state
            document.body.classList.remove('sidebar-toggled');
            document.querySelector('.sidebar').classList.remove('toggled');
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize(); // Call on load

    // Prevent sidebar from interfering with dropdown
    document.addEventListener('click', function(e) {
        // Don't close dropdowns when clicking inside sidebar
        if (e.target.closest('.sidebar')) {
            return;
        }
        
        // Close dropdowns when clicking outside
        if (!e.target.closest('.dropdown')) {
            $('.dropdown-menu').removeClass('show');
        }
    });
});
</script>