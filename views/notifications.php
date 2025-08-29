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


// Function to get notifications with filtering
function getNotifications($db, $category, $filter = 'all') {
    $activityTypes = "'loan', 'payment', 'account', 'user'";
    $systemTypes = "'system', 'backup', 'update', 'settings'";
    
    $query = "SELECT * FROM notifications WHERE type IN (" . 
             ($category === 'activity' ? $activityTypes : $systemTypes) . ")";
    
    switch ($filter) {
        case 'unread':
            $query .= " AND is_read = FALSE";
            break;
        case 'today':
            $query .= " AND DATE(date) = CURDATE()";
            break;
        case 'this_month':
            $query .= " AND YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())";
            break;
        // 'all' doesn't need additional filtering
    }
    
    $query .= " ORDER BY date DESC LIMIT 50";
    
    $result = $db->conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to mark notification as read
function deleteNotification($db, $id) {
    $stmt = $db->conn->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// Function to add a new notification
function addNotification($db, $message, $type, $relatedId = null) {
    $stmt = $db->conn->prepare("INSERT INTO notifications (message, type, related_id, date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("ssi", $message, $type, $relatedId);
    return $stmt->execute();
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
        $success = deleteNotification($db, $_POST['id']);
        echo json_encode(['success' => $success]);
        exit;
    }
}

// Get filter from GET parameter, default to 'all'
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get notifications
$activityNotifications = getNotifications($db, 'activity', $filter);
$systemNotifications = getNotifications($db, 'system', $filter);

// Count unread notifications
function countUnread($notifications) {
    return array_reduce($notifications, function($carry, $item) {
        return $carry + ($item['is_read'] ? 0 : 1);
    }, 0);
}

$unreadActivityCount = countUnread($activityNotifications);
$unreadSystemCount = countUnread($systemNotifications);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Notifications - Lato Management System</title>

    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <!-- Font Awesome Icons -->
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Custom styles -->
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .row .card{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }
        .notification-item {
            transition: background-color 0.3s;
        }
        .notification-item:hover {
            background-color: #f8f9fc;
        }
        .notification-item.unread {
            background-color: #e8f0fe;
        }
        .notification-item.unread:hover {
            background-color: #d8e5fd;
        }
        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        }
    </style>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

   <!-- Import Sidebar -->
    <?php require_once '../components/includes/sidebar.php'; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid pt-4">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Notifications</h1>

                    <!-- Filter Buttons -->
                    <div class="mb-3">
                        <a href="?filter=all" class="btn btn-warning <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                        <a href="?filter=unread" class="btn btn-warning <?php echo $filter === 'unread' ? 'active' : ''; ?>">Unread</a>
                        <a href="?filter=today" class="btn btn-warning <?php echo $filter === 'today' ? 'active' : ''; ?>">Today</a>
                        <a href="?filter=this_month" class="btn btn-warning <?php echo $filter === 'this_month' ? 'active' : ''; ?>">This Month</a>
                    </div>

                    <div class="row">
                        <!-- Activity Notifications -->
                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header py-3">
                                    <h6 style="color: #030f57;" class="m-0 font-weight-bold">
                                        Activities
                                        <?php if ($unreadActivityCount > 0): ?>
                                            <span class="badge badge-danger ml-2"><?php echo $unreadActivityCount; ?> unread</span>
                                        <?php endif; ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group" id="activityNotifications">
                                        <?php foreach ($activityNotifications as $notification): ?>
                                            <li class="list-group-item notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $notification['id']; ?>">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></h5>
                                                    <small><?php echo $notification['date']; ?></small>
                                                </div>
                                                <p class="mb-1">Type: <?php echo ucfirst($notification['type']); ?></p>
                                                <?php if ($notification['related_id']): ?>
                                                    <small>Related ID: <?php echo $notification['related_id']; ?></small>
                                                <?php endif; ?>
                                                <?php if (!$notification['is_read']): ?>
                                                    <button class="btn btn-sm btn-outline-danger mt-2 delete-notification">Mark as Read</button>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- System Notifications -->
                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header py-3">
                                    <h6 style="color: #030f57;" class="m-0 font-weight-bold">
                                        System
                                        <?php if ($unreadSystemCount > 0): ?>
                                            <span class="badge badge-danger ml-2"><?php echo $unreadSystemCount; ?> unread</span>
                                        <?php endif; ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group" id="systemNotifications">
                                        <?php foreach ($systemNotifications as $notification): ?>
                                            <li class="list-group-item notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $notification['id']; ?>">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></h5>
                                                    <small><?php echo $notification['date']; ?></small>
                                                </div>
                                                <p class="mb-1">Type: <?php echo ucfirst($notification['type']); ?></p>
                                                <?php if ($notification['related_id']): ?>
                                                    <small>Related ID: <?php echo $notification['related_id']; ?></small>
                                                <?php endif; ?>
                                                <?php if (!$notification['is_read']): ?>
                                                    <button class="btn btn-sm btn-outline-primary mt-2 mark-read">Mark as Read</button>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
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
                        <span>© 2024 Lato Management System. All rights reserved.</span>
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
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
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
                    <a class="btn btn-primary" href="../views/logout.php">Logout</a>
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
$(document).ready(function() {
    // Rename 'mark-read' to 'delete-notification'
    $('.delete-notification').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $item = $button.closest('.notification-item');
        var notificationId = $item.data('id');

        $.ajax({
            url: 'notifications.php',
            type: 'POST',
            data: {
                action: 'delete',
                id: notificationId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $item.fadeOut(300, function() {
                        $(this).remove();
                        updateUnreadCount($item.closest('.card'));
                    });
                }
            }
        });
    });


        function updateUnreadCount($card) {
            var unreadCount = $card.find('.notification-item.unread').length;
            var $badge = $card.find('.badge-danger');
            if (unreadCount > 0) {
                if ($badge.length) {
                    $badge.text(unreadCount + ' unread');
                } else {
                    $card.find('.card-header h6').append('<span class="badge badge-danger ml-2">' + unreadCount + ' unread</span>');
                }
            } else {
                $badge.remove();
            }
        }

        // Toggle the side navigation
        $("#sidebarToggleTop").on('click', function(e) {
            $("body").toggleClass("sidebar-toggled");
            $(".sidebar").toggleClass("toggled");
            if ($(".sidebar").hasClass("toggled")) {
                $('.sidebar .collapse').collapse('hide');
                $("#content-wrapper").css({"margin-left": "100px", "width": "calc(100% - 100px)"});
                $(".topbar").css("left", "100px");
            } else {
                $("#content-wrapper").css({"margin-left": "225px", "width": "calc(100% - 225px)"});
                $(".topbar").css("left", "225px");
            }
        });

        // Close any open menu accordions when window is resized below 768px
        $(window).resize(function() {
            if ($(window).width() < 768) {
                $('.sidebar .collapse').collapse('hide');
            };
            
            // Toggle the side navigation when window is resized below 480px
            if ($(window).width() < 480 && !$(".sidebar").hasClass("toggled")) {
                $("body").addClass("sidebar-toggled");
                $(".sidebar").addClass("toggled");
                $('.sidebar .collapse').collapse('hide');
            };
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

        // Scroll to top button appear
        $(document).on('scroll', function() {
            var scrollDistance = $(this).scrollTop();
            if (scrollDistance > 100) {
                $('.scroll-to-top').fadeIn();
            } else {
                $('.scroll-to-top').fadeOut();
            }
        });

        // Smooth scrolling using jQuery easing
        $(document).on('click', 'a.scroll-to-top', function(e) {
            var $anchor = $(this);
            $('html, body').stop().animate({
                scrollTop: ($($anchor.attr('href')).offset().top)
            }, 1000, 'easeInOutExpo');
            e.preventDefault();
        });

        // Function to refresh notifications
        function refreshNotifications() {
            $.ajax({
                url: 'notifications.php',
                method: 'GET',
                data: { filter: '<?php echo $filter; ?>' },
                success: function(data) {
                    $('#content').html($(data).find('#content').html());
                },
                error: function(xhr, status, error) {
                    console.error("Error refreshing notifications:", error);
                }
            });
        }

        // Refresh notifications every 5 minutes (300000 milliseconds)
        setInterval(refreshNotifications, 300000);
    });
    </script>

</body>

</html>