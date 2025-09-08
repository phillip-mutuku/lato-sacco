<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../helpers/session.php';
require_once '../config/class.php';
$db = new db_class();

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'cashier')) {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: ../views/index.php');
    exit();
}
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

        /* Search functionality styles */
        .search-container {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e3e6f0;
        }
        
        .search-input {
            border: 2px solid #e3e6f0;
            padding: 12px 20px 12px 45px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: #51087E;
            box-shadow: 0 0 0 0.2rem rgba(81, 8, 126, 0.25);
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 16px;
        }
        
        .search-stats {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 10px;
        }
        
        .clear-search {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 16px;
            display: none;
        }
        
        .clear-search:hover {
            color: #51087E;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .no-results i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #e3e6f0;
        }

        .highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: bold;
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

        .export-btn {
            margin-left: 10px;
        }

        .search-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .container-fluid {
            padding: 1.5rem;
            margin-top: 0;
            width: 100%;
        }

        /* Report Generation Styles */
        .report-generation-section {
            background: linear-gradient(135deg, #51087E 0%, #6B1FA0 100%);
            border-radius: 12px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(81, 8, 126, 0.15);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .report-generation-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            pointer-events: none;
        }

        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
        }

        .report-title-section {
            display: flex;
            align-items: center;
        }

        .report-icon {
            color: #ffffff;
            font-size: 1.8rem;
            margin-right: 15px;
            background: rgba(255,255,255,0.2);
            padding: 12px;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .report-title {
            color: #ffffff;
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .report-subtitle {
            color: rgba(255,255,255,0.9);
            font-size: 0.95rem;
            margin: 0;
            margin-top: 5px;
        }

        .generate-report-btn {
            background: rgba(255,255,255,0.95);
            border: 2px solid rgba(255,255,255,0.3);
            color: #51087E;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 2;
            min-width: 180px;
        }

        .generate-report-btn:hover {
            background: #ffffff;
            border-color: #ffffff;
            color: #51087E;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .generate-report-btn:active {
            transform: translateY(0);
        }

        .generate-report-btn i {
            margin-right: 8px;
        }

        .report-description {
            color: rgba(255,255,255,0.85);
            font-size: 0.9rem;
            margin: 0;
            position: relative;
            z-index: 2;
        }

        /* Loading state for report button */
        .generate-report-btn.loading {
            opacity: 0.8;
            pointer-events: none;
        }

        .generate-report-btn.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .report-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .generate-report-btn {
                width: 100%;
                text-align: center;
            }
            
            .report-generation-section {
                padding: 20px;
                margin-bottom: 25px;
            }
        }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Include Sidebar -->
        <?php include '../components/includes/cashier_sidebar.php'; ?>
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Report Generation Section -->
                    <div class="report-generation-section">
                        <div class="report-header">
                            <div class="report-title-section">
                                <i class="fas fa-chart-line report-icon"></i>
                                <div>
                                    <h3 class="report-title">Groups Performance Report</h3>
                                    <p class="report-subtitle">Generate comprehensive portfolio analysis</p>
                                </div>
                            </div>
                            <button class="btn generate-report-btn" id="generateReportBtn">
                                <i class="fas fa-file-pdf"></i> Generate Report
                            </button>
                        </div>
                        <p class="report-description">
                            Generate detailed performance reports including group analytics, loan portfolio status, 
                            field officer performance metrics, and risk assessment with current filter settings.
                        </p>
                    </div>

                    <!-- Include Groups Statistics -->
                    <?php include '../components/groups/groups_stats.php'; ?>

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Manage Wekeza Groups</h1>
                        <button class="btn btn-warning" data-toggle="modal" data-target="#addGroupModal">
                            <i class="fas fa-plus"></i> Add New Group
                        </button>
                    </div>

                    <!-- Search Section -->
                    <div class="search-container">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="position-relative">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" 
                                           id="groupSearch" 
                                           class="form-control search-input" 
                                           placeholder="Search by group reference, group name, or field officer..."
                                           autocomplete="off">
                                    <button type="button" class="clear-search" id="clearSearch">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="search-actions">
                                    <div class="search-stats" id="searchStats">
                                        Showing all groups
                                    </div>
                                    <button class="btn btn-outline-success btn-sm export-btn" id="exportResults" title="Export search results">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                </div>
                            </div>
                        </div>
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
                                    <tbody id="groupsTableBody">
                                        <?php
                                        $query = "SELECT g.*, u.firstname, u.lastname 
                                                 FROM lato_groups g 
                                                 JOIN user u ON g.field_officer_id = u.user_id 
                                                 ORDER BY g.group_id DESC";
                                        $result = $db->conn->query($query);
                                        $i = 1;
                                        while ($row = $result->fetch_assoc()) {
                                        ?>
                                        <tr class="group-row" 
                                            data-reference="<?php echo strtolower($row['group_reference']); ?>"
                                            data-name="<?php echo strtolower($row['group_name']); ?>"
                                            data-officer="<?php echo strtolower($row['firstname'] . ' ' . $row['lastname']); ?>"
                                            data-area="<?php echo strtolower($row['area']); ?>">
                                            <td class="row-number"><?php echo $i++; ?></td>
                                            <td class="group-reference"><?php echo $row['group_reference']; ?></td>
                                            <td class="group-name"><?php echo $row['group_name']; ?></td>
                                            <td class="group-area"><?php echo $row['area']; ?></td>
                                            <td class="field-officer"><?php echo $row['firstname'] . ' ' . $row['lastname']; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                                        Action
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href="cashier_manage_group.php?id=<?php echo $row['group_id']; ?>">
                                                            <i class="fas fa-users fa-fw"></i> Manage Group
                                                        </a>
                                                        <button type="button" class="dropdown-item edit-group" data-id="<?php echo $row['group_id']; ?>">
                                                            <i class="fas fa-edit fa-fw"></i> Edit
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                
                                <!-- No results message -->
                                <div id="noResults" class="no-results" style="display: none;">
                                    <i class="fas fa-search"></i>
                                    <h5>No groups found</h5>
                                    <p>Try adjusting your search terms or clear the search to see all groups.</p>
                                    <button class="btn btn-outline-primary" id="clearSearchBtn">
                                        <i class="fas fa-times"></i> Clear Search
                                    </button>
                                </div>
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
        // Initialize DataTable with pagination
        $('#dataTable').DataTable({
            "pageLength": 10,
            "lengthChange": true,
            "searching": false, // We'll use custom search
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "language": {
                "lengthMenu": "Show _MENU_ entries",
                "zeroRecords": "No matching records found",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Previous"
                }
            }
        });

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

        // Report Generation Functionality
        $('#generateReportBtn').click(function() {
            const btn = $(this);
            const originalHtml = btn.html();
            
            // Get current filter values
            const selectedOfficer = $('select[name="field_officer"]').val() || '';
            const fromDate = $('input[name="from_date"]').val() || '';
            const toDate = $('input[name="to_date"]').val() || '';
            
            // Add loading state
            btn.addClass('loading').html('<i class="fas fa-spinner fa-spin"></i> Generating Report...');
            btn.prop('disabled', true);
            
            // Build URL with current filters
            let reportUrl = '../controllers/groups_performance_report.php?';
            const params = [];
            
            if (selectedOfficer) {
                params.push('field_officer=' + encodeURIComponent(selectedOfficer));
            }
            if (fromDate) {
                params.push('from_date=' + encodeURIComponent(fromDate));
            }
            if (toDate) {
                params.push('to_date=' + encodeURIComponent(toDate));
            }
            
            reportUrl += params.join('&');
            
            // Show success message
            showAlert('info', 'Generating report with current filter settings. Download will start automatically.');
            
            // Create hidden iframe for PDF download
            const iframe = $('<iframe>', {
                src: reportUrl,
                style: 'display: none;'
            }).appendTo('body');
            
            // Reset button after 3 seconds
            setTimeout(function() {
                btn.removeClass('loading').html(originalHtml);
                btn.prop('disabled', false);
                iframe.remove();
                showAlert('success', 'Report generated successfully!');
            }, 3000);
        });

        // Search functionality
        let totalRows = $('.group-row').length;
        let visibleRows = totalRows;
        
        function updateSearchStats(visible, total, searchTerm = '') {
            const statsElement = $('#searchStats');
            if (searchTerm) {
                statsElement.html(`Showing ${visible} of ${total} groups for "<strong>${searchTerm}</strong>"`);
            } else {
                statsElement.html(`Showing all ${total} groups`);
            }
        }

        function highlightText(text, searchTerm) {
            if (!searchTerm) return text;
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            return text.replace(regex, '<span class="highlight">$1</span>');
        }

        function performSearch(searchTerm) {
            searchTerm = searchTerm.toLowerCase().trim();
            visibleRows = 0;
            let rowCounter = 1;

            $('.group-row').each(function() {
                const $row = $(this);
                const reference = $row.data('reference');
                const name = $row.data('name');
                const officer = $row.data('officer');
                const area = $row.data('area');
                
                const isVisible = !searchTerm || 
                    reference.includes(searchTerm) || 
                    name.includes(searchTerm) || 
                    officer.includes(searchTerm) || 
                    area.includes(searchTerm);

                if (isVisible) {
                    $row.show();
                    $row.find('.row-number').text(rowCounter++);
                    visibleRows++;
                    
                    // Highlight matching text
                    if (searchTerm) {
                        const originalReference = $row.find('.group-reference').data('original') || $row.find('.group-reference').text();
                        const originalName = $row.find('.group-name').data('original') || $row.find('.group-name').text();
                        const originalOfficer = $row.find('.field-officer').data('original') || $row.find('.field-officer').text();
                        const originalArea = $row.find('.group-area').data('original') || $row.find('.group-area').text();
                        
                        if (!$row.find('.group-reference').data('original')) {
                            $row.find('.group-reference').data('original', originalReference);
                            $row.find('.group-name').data('original', originalName);
                            $row.find('.field-officer').data('original', originalOfficer);
                            $row.find('.group-area').data('original', originalArea);
                        }
                        
                        $row.find('.group-reference').html(highlightText(originalReference, searchTerm));
                        $row.find('.group-name').html(highlightText(originalName, searchTerm));
                        $row.find('.field-officer').html(highlightText(originalOfficer, searchTerm));
                        $row.find('.group-area').html(highlightText(originalArea, searchTerm));
                    } else {
                        // Remove highlighting
                        if ($row.find('.group-reference').data('original')) {
                            $row.find('.group-reference').html($row.find('.group-reference').data('original'));
                            $row.find('.group-name').html($row.find('.group-name').data('original'));
                            $row.find('.field-officer').html($row.find('.field-officer').data('original'));
                            $row.find('.group-area').html($row.find('.group-area').data('original'));
                        }
                    }
                } else {
                    $row.hide();
                }
            });

            // Show/hide no results message
            if (visibleRows === 0 && searchTerm) {
                $('#noResults').show();
                $('#dataTable').hide();
            } else {
                $('#noResults').hide();
                $('#dataTable').show();
            }

            // Update search stats
            updateSearchStats(visibleRows, totalRows, searchTerm);
            
            // Show/hide clear button
            if (searchTerm) {
                $('#clearSearch').show();
            } else {
                $('#clearSearch').hide();
            }
        }

        // Search input handler with debounce
        let searchTimeout;
        $('#groupSearch').on('input', function() {
            const searchTerm = $(this).val();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(searchTerm);
            }, 150);
        });

        // Clear search handlers
        $('#clearSearch, #clearSearchBtn').on('click', function() {
            $('#groupSearch').val('');
            performSearch('');
            $('#groupSearch').focus();
        });

        // Export functionality
        function exportSearchResults() {
            const visibleRows = $('.group-row:visible');
            const exportData = [];
            
            visibleRows.each(function() {
                const row = $(this);
                exportData.push({
                    'Group Reference': row.find('.group-reference').text().trim(),
                    'Group Name': row.find('.group-name').text().trim(),
                    'Area': row.find('.group-area').text().trim(),
                    'Field Officer': row.find('.field-officer').text().trim()
                });
            });

            if (exportData.length === 0) {
                showAlert('warning', 'No data to export');
                return;
            }

            // Convert to CSV
            const headers = Object.keys(exportData[0]);
            const csv = [
                headers.join(','),
                ...exportData.map(row => headers.map(header => `"${row[header]}"`).join(','))
            ].join('\n');

            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `wekeza_groups_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showAlert('success', `Exported ${exportData.length} groups to CSV`);
        }

        $('#exportResults').on('click', exportSearchResults);

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

        // Helper Functions
        function showAlert(type, message) {
            const alertDiv = $('<div>')
                .addClass('alert alert-' + (type === 'success' ? 'success' : (type === 'info' ? 'info' : (type === 'warning' ? 'warning' : 'danger'))) + ' alert-dismissible fade show')
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

        // Initialize search stats
        updateSearchStats(totalRows, totalRows);

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

        // Initialize all dropdowns
        $('.dropdown-toggle').dropdown();

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl+F or Cmd+F to focus search
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 70) {
                e.preventDefault();
                $('#groupSearch').focus();
            }
            // Escape to clear search
            if (e.keyCode === 27 && $('#groupSearch').is(':focus')) {
                $('#groupSearch').val('');
                performSearch('');
            }
            // Ctrl+R or Cmd+R to generate report
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 82) {
                e.preventDefault();
                $('#generateReportBtn').click();
            }
        });

    });
    </script>
</body>
</html>