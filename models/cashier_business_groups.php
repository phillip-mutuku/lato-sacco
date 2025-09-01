<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../helpers/session.php';
require_once '../config/class.php';
require_once '../controllers/businessGroupController.php';
$db = new db_class();
$businessGroupController = new BusinessGroupController();

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
    <title>Lato Management System - Business Groups</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    <style>
        .modal-lg { max-width: 80% !important; }
        .form-group label { font-weight: bold; }

        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        .container-fluid .card{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
             <!-- Import Sidebar -->
            <?php require_once '../components/includes/sidebar.php'; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid pt-4">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Manage Business Groups</h1>
                        <button class="btn btn-warning" data-toggle="modal" data-target="#addBusinessGroupModal">
                            <i class="fas fa-plus"></i> Add New Business Group
                        </button>
                    </div>

                    <!-- Business Groups Table Card -->
                    <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 style="color: #51087E;" class="m-0 font-weight-bold">Business Groups</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="businessGroupsTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Reference</th>
                                                <th>Group Name</th>
                                                <th>Chairperson</th>
                                                <th>Secretary</th>
                                                <th>Treasurer</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = "SELECT * FROM business_groups ORDER BY group_id DESC";
                                            $result = $db->conn->query($query);
                                            $i = 1;
                                            while ($row = $result->fetch_assoc()) {
                                            ?>
                                            <tr>
                                                <td><?php echo $i++; ?></td>
                                                <td><?php echo $row['reference_name'] ? htmlspecialchars($row['reference_name']) : '<span class="text-muted">Not assigned</span>'; ?></td>
                                                <td><?php echo htmlspecialchars($row['group_name']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($row['chairperson_name']); ?><br>
                                                    <small>ID: <?php echo htmlspecialchars($row['chairperson_id_number']); ?></small><br>
                                                    <small>Phone: <?php echo htmlspecialchars($row['chairperson_phone']); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($row['secretary_name']); ?><br>
                                                    <small>ID: <?php echo htmlspecialchars($row['secretary_id_number']); ?></small><br>
                                                    <small>Phone: <?php echo htmlspecialchars($row['secretary_phone']); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($row['treasurer_name']); ?><br>
                                                    <small>ID: <?php echo htmlspecialchars($row['treasurer_id_number']); ?></small><br>
                                                    <small>Phone: <?php echo htmlspecialchars($row['treasurer_phone']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="manage_business_group.php?id=<?php echo $row['group_id']; ?>" 
                                                        class="btn btn-sm" style="background-color: #51087E; color: white;">
                                                            <i class="fas fa-users"></i> Manage
                                                        </a>
                                                        <button type="button" class="btn btn-warning btn-sm edit-group" 
                                                                data-id="<?php echo $row['group_id']; ?>">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm delete-group" 
                                                                data-id="<?php echo $row['group_id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($row['group_name']); ?>">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
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

<!-- Add Business Group Modal -->
<div class="modal fade" id="addBusinessGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #51087E;">
                <h5 class="modal-title text-white">Add New Business Group</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addBusinessGroupForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Business Group Name</label>
                                <input type="text" name="group_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reference Name</label>
                                <div class="input-group">
                                    <input type="text" name="reference_name" class="form-control" placeholder="e.g., BG-001">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="suggestReference">
                                            <i class="fas fa-sync-alt"></i> Suggest
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Optional. Format: BG-001, BG-002, etc.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Chairperson Details -->
                    <h5 class="mt-4">Chairperson Details</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="chairperson_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>ID Number</label>
                                <input type="text" name="chairperson_id_number" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="chairperson_phone" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <!-- Secretary Details -->
                    <h5 class="mt-4">Secretary Details</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="secretary_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>ID Number</label>
                                <input type="text" name="secretary_id_number" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="secretary_phone" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <!-- Treasurer Details -->
                    <h5 class="mt-4">Treasurer Details</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="treasurer_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>ID Number</label>
                                <input type="text" name="treasurer_id_number" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="treasurer_phone" class="form-control" required>
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

<!-- Edit Business Group Modal -->
<div class="modal fade" id="editBusinessGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #51087E;">
                <h5 class="modal-title text-white">Edit Business Group</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editBusinessGroupForm">
                <input type="hidden" name="group_id" id="edit_group_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Business Group Name</label>
                                <input type="text" name="group_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reference Name</label>
                                <div class="input-group">
                                    <input type="text" name="reference_name" class="form-control" placeholder="e.g., BG-001">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="editSuggestReference">
                                            <i class="fas fa-sync-alt"></i> Suggest
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Optional. Format: BG-001, BG-002, etc.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Chairperson Details -->
                    <h5 class="mt-4">Chairperson Details</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="chairperson_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>ID Number</label>
                                <input type="text" name="chairperson_id_number" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="chairperson_phone" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <!-- Secretary Details -->
                    <h5 class="mt-4">Secretary Details</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="secretary_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>ID Number</label>
                                <input type="text" name="secretary_id_number" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="secretary_phone" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <!-- Treasurer Details -->
                    <h5 class="mt-4">Treasurer Details</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="treasurer_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>ID Number</label>
                                <input type="text" name="treasurer_id_number" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="treasurer_phone" class="form-control" required>
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



    <!-- Delete Business Group Modal -->
    <div class="modal fade" id="deleteBusinessGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">Confirm Deletion</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this business group? This action cannot be undone.
                    <p class="mt-2 mb-0"><strong>Group Name: </strong><span id="groupToDelete"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
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
    <script src="../public/js/jquery.dataTables.js"></script>
    <script src="../public/js/dataTables.bootstrap4.js"></script>
    <script src="../public/js/sb-admin-2.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#businessGroupsTable').DataTable();

        // Form submission handlers
        $('#addBusinessGroupForm').on('submit', function(e) {
    e.preventDefault();
    
    // Show loading state
    const submitBtn = $(this).find('button[type="submit"]');
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    
    $.ajax({
        url: '../controllers/businessGroupController.php',
        method: 'POST',
        data: $(this).serialize() + '&action=create',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                showAlert('success', response.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('error', response.message || 'Error creating business group');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Response:', xhr.responseText);
            showAlert('error', 'Error creating business group. Please check the console for details.');
        },
        complete: function() {
            submitBtn.prop('disabled', false).html('Save Group');
        }
    });
});


    // Function to get next reference number
    function getNextReferenceNumber(input) {
        $.ajax({
            url: '../controllers/businessGroupController.php',
            method: 'POST',
            data: {
                action: 'getNextReference'
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.reference) {
                    input.val(response.reference);
                } else {
                    alert('Error getting reference number: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                alert('Error getting reference number. Please try again.');
            }
        });
    }

    // Suggest reference button click handlers
    $('#suggestReference').click(function(e) {
        e.preventDefault(); 
        getNextReferenceNumber($('#addBusinessGroupForm input[name="reference_name"]'));
    });

    $('#editSuggestReference').click(function(e) {
        e.preventDefault();
        getNextReferenceNumber($('#editBusinessGroupForm input[name="reference_name"]'));
    });

    // When Add Modal opens
    $('#addBusinessGroupModal').on('shown.bs.modal', function() {
        getNextReferenceNumber($('#addBusinessGroupForm input[name="reference_name"]'));
    });





// Add this helper function for showing alerts
function showAlert(type, message) {
    const alertDiv = $('<div>')
        .addClass('alert alert-' + (type === 'success' ? 'success' : 'danger') + ' alert-dismissible fade show')
        .css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'z-index': '9999',
            'min-width': '300px'
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



        // Edit group handler
        $('.edit-group').click(function() {
        const groupId = $(this).data('id');
        $.ajax({
            url: '../controllers/businessGroupController.php',
            method: 'POST',
            data: {
                action: 'get',
                group_id: groupId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const group = response.data;
                    $('#edit_group_id').val(group.group_id);
                    $('#editBusinessGroupForm input[name="group_name"]').val(group.group_name);
                    $('#editBusinessGroupForm input[name="reference_name"]').val(group.reference_name);
                    
                    // Fill chairperson details
                    $('#editBusinessGroupForm input[name="chairperson_name"]').val(group.chairperson_name);
                    $('#editBusinessGroupForm input[name="chairperson_id_number"]').val(group.chairperson_id_number);
                    $('#editBusinessGroupForm input[name="chairperson_phone"]').val(group.chairperson_phone);
                    
                    // Fill secretary details
                    $('#editBusinessGroupForm input[name="secretary_name"]').val(group.secretary_name);
                    $('#editBusinessGroupForm input[name="secretary_id_number"]').val(group.secretary_id_number);
                    $('#editBusinessGroupForm input[name="secretary_phone"]').val(group.secretary_phone);
                    
                    // Fill treasurer details
                    $('#editBusinessGroupForm input[name="treasurer_name"]').val(group.treasurer_name);
                    $('#editBusinessGroupForm input[name="treasurer_id_number"]').val(group.treasurer_id_number);
                    $('#editBusinessGroupForm input[name="treasurer_phone"]').val(group.treasurer_phone);
                    
                    $('#editBusinessGroupModal').modal('show');
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error fetching group details');
            }
        });
    });

    // Form validation for reference name format
    function validateReferenceFormat(reference) {
        if (!reference) return true; 
        return /^BG-\d{3}$/.test(reference);
    }


        // Update group handler
        $('#editBusinessGroupForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '../controllers/businessGroupController.php',
                method: 'POST',
                data: $(this).serialize() + '&action=update',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error occurred while updating business group');
                }
            });
        });


    // Add validation to both forms
    $('#addBusinessGroupForm, #editBusinessGroupForm').on('submit', function(e) {
        const referenceInput = $(this).find('input[name="reference_name"]');
        const reference = referenceInput.val().trim();
        
        if (reference && !validateReferenceFormat(reference)) {
            e.preventDefault();
            alert('Reference name must be in format BG-XXX (e.g., BG-001)');
            referenceInput.focus();
            return false;
        }
    });

    // Phone number validation
    $('input[name$="phone"]').on('input', function() {
        this.value = this.value.replace(/[^0-9+]/, '');
    });

    // ID number validation
    $('input[name$="id_number"]').on('input', function() {
        this.value = this.value.replace(/[^0-9]/, '');
    });


        
        // Delete group handler
        $('.delete-group').click(function() {
            const groupId = $(this).data('id');
            const groupName = $(this).data('name');
            $('#groupToDelete').text(groupName);
            $('#confirmDelete').data('id', groupId);
            $('#deleteBusinessGroupModal').modal('show');
        });

        // Confirm delete handler
        $('#confirmDelete').click(function() {
            const groupId = $(this).data('id');
            
            // Show loading state
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
            
            $.ajax({
                url: '../controllers/businessGroupController.php',
                method: 'POST',
                data: {
                    action: 'delete',
                    group_id: groupId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Show success message
                        showAlert('success', response.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        // Show error message
                        showAlert('error', response.message);
                        $('#confirmDelete').prop('disabled', false).html('Delete');
                        $('#deleteBusinessGroupModal').modal('hide');
                    }
                },
                error: function() {
                    showAlert('error', 'An error occurred while deleting the group.');
                    $('#confirmDelete').prop('disabled', false).html('Delete');
                    $('#deleteBusinessGroupModal').modal('hide');
                }
            });
        });

        // Form validation helper
        function validateForm(form) {
            let isValid = true;
            form.find('input[required]').each(function() {
                if (!$(this).val().trim()) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            return isValid;
        }

        // Phone number validation
        $('input[name$="phone"]').on('input', function() {
            this.value = this.value.replace(/[^0-9+]/, '');
        });

        // ID number validation
        $('input[name$="id_number"]').on('input', function() {
            this.value = this.value.replace(/[^0-9]/, '');
        });

        // Reset form when modal is closed
        $('.modal').on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
            $(this).find('.is-invalid').removeClass('is-invalid');
        });

        // Sidebar toggle functionality
        $("#sidebarToggleTop").on('click', function(e) {
            $("body").toggleClass("sidebar-toggled");
            $(".sidebar").toggleClass("toggled");
            if ($(".sidebar").hasClass("toggled")) {
                $('.sidebar .collapse').collapse('hide');
            }
        });

        // Close any open menu accordions when window is resized
        $(window).resize(function() {
            if ($(window).width() < 768) {
                $('.sidebar .collapse').collapse('hide');
            }
        });

        // Prevent the content wrapper from scrolling when the fixed side navigation hovered over
        $('body.fixed-nav .sidebar').on('mousewheel DOMMouseScroll wheel', function(e) {
            if ($(window).width() > 768) {
                var e0 = e.originalEvent,
                    delta = e0.wheelDelta || -e0.detail;
                this.scrollTop += (delta < 0 ? 1 : -1) * 30;
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>