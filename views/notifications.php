<?php
// Set timezone
date_default_timezone_set("Africa/Nairobi");

// Include required files and initialize the database connection
require_once '../helpers/session.php';
require_once '../config/class.php';
$db = new db_class();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: index.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get notifications
$query = "SELECT * FROM notifications ORDER BY date DESC LIMIT $limit OFFSET $offset";
$result = $db->conn->query($query);
$notifications = $result->fetch_all(MYSQLI_ASSOC);

// Count total notifications
$countQuery = "SELECT COUNT(*) as total FROM notifications";
$countResult = $db->conn->query($countQuery);
$total = $countResult->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $action = $_POST['action'];
    
    if ($action === 'delete') {
        $stmt = $db->conn->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
    } elseif ($action === 'mark_read') {
        $stmt = $db->conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
    }
    
    echo json_encode(['success' => $success]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifications - Lato Management System</title>
    
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    
    <style>
        .container-fluid { margin-top: 70px; padding: 2rem; }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        
        .table th {
            background: #f8f9fc;
            border: none;
            font-weight: 600;
            color: #5a5c69;
        }
        
        .table td {
            border: none;
            border-bottom: 1px solid #e3e6f0;
            vertical-align: middle;
        }
        
        .table tr:hover { background: #f8f9fc; }
        
        .badge-unread { 
            background: #e74a3b; 
            color: white;
        }
        .badge-read { 
            background: #1cc88a; 
            color: white;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            border-radius: 20px;
        }
        
        .pagination .page-link {
            border: none;
            color: #4e73df;
            margin: 0 2px;
            border-radius: 5px;
        }
        
        .pagination .page-item.active .page-link {
            background: #4e73df;
            border-color: #4e73df;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #858796;
        }
        
        @media (max-width: 768px) {
            .container-fluid { padding: 1rem; }
            .table-responsive { font-size: 0.85rem; }
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require_once '../components/includes/sidebar.php'; ?>
        
        <div class="container-fluid">
            <h1 class="h3 mb-4 text-gray-800">
                <i class="fas fa-bell mr-2"></i>Notifications
            </h1>
            
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">
                        All Notifications (<?php echo $total; ?>)
                    </h6>
                </div>
                
                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash fa-3x mb-3"></i>
                            <h5>No notifications found</h5>
                            <p>You're all caught up!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="50%">Message</th>
                                        <th width="12%">Type</th>
                                        <th width="10%">Status</th>
                                        <th width="13%">Date</th>
                                        <th width="10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notifications as $index => $notification): ?>
                                        <tr data-id="<?php echo $notification['id']; ?>">
                                            <td><?php echo $offset + $index + 1; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($notification['message']); ?></strong>
                                                <?php if ($notification['related_id']): ?>
                                                    <br><small class="text-muted">ID: <?php echo $notification['related_id']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    <?php echo ucfirst($notification['type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $notification['is_read'] ? 'badge-read' : 'badge-unread'; ?>">
                                                    <?php echo $notification['is_read'] ? 'Read' : 'Unread'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo date('M j, Y', strtotime($notification['date'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if (!$notification['is_read']): ?>
                                                    <button class="btn btn-success btn-sm mark-read" data-id="<?php echo $notification['id']; ?>">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $notification['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($totalPages > 1): ?>
                            <div class="card-footer bg-white">
                                <nav>
                                    <ul class="pagination justify-content-center mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                <div class="text-center mt-2 text-muted">
                                    <small>Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total); ?> of <?php echo $total; ?></small>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog Modal -->
    <div class="modal fade" id="confirmDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmTitle">Confirm Action</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="confirmMessage">
                    Are you sure you want to proceed?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmYes">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../public/js/jquery.js"></script>
    <script src="../public/js/bootstrap.bundle.js"></script>
    <script src="../public/js/sb-admin-2.js"></script>
    
    <script>
    $(document).ready(function() {
        // Mark as read
        $('.mark-read').click(function() {
            var id = $(this).data('id');
            var row = $(this).closest('tr');
            var button = $(this);
            
            // Add loading state
            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
            $.post('notifications.php', {action: 'mark_read', id: id}, function(response) {
                if (response.success) {
                    row.find('.badge-unread').removeClass('badge-unread').addClass('badge-read').text('Read');
                    button.remove();
                } else {
                    button.prop('disabled', false).html('<i class="fas fa-check"></i>');
                    alert('Failed to mark as read. Please try again.');
                }
            }, 'json').fail(function() {
                button.prop('disabled', false).html('<i class="fas fa-check"></i>');
                alert('An error occurred. Please try again.');
            });
        });
        
        // Delete notification with confirmation dialog
        var deleteId = null;
        var deleteRow = null;
        
        $('.delete-btn').click(function() {
            deleteId = $(this).data('id');
            deleteRow = $(this).closest('tr');
            
            $('#confirmTitle').text('Delete Notification');
            $('#confirmMessage').text('Are you sure you want to delete this notification? This action cannot be undone.');
            $('#confirmYes').text('Yes, Delete').removeClass('btn-primary').addClass('btn-danger');
            $('#confirmDialog').modal('show');
        });
        
        // Handle confirmation
        $('#confirmYes').click(function() {
            $('#confirmDialog').modal('hide');
            
            if (deleteId && deleteRow) {
                $.post('notifications.php', {action: 'delete', id: deleteId}, function(response) {
                    if (response.success) {
                        deleteRow.fadeOut(300, function() {
                            $(this).remove();
                            // Reload if no more notifications on current page
                            if ($('tbody tr:visible').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        alert('Failed to delete notification. Please try again.');
                    }
                }, 'json').fail(function() {
                    alert('An error occurred. Please try again.');
                });
                
                // Reset variables
                deleteId = null;
                deleteRow = null;
            }
        });
        
        // Reset variables when modal is closed
        $('#confirmDialog').on('hidden.bs.modal', function() {
            deleteId = null;
            deleteRow = null;
        });
    });
    </script>
</body>
</html>