<?php
    date_default_timezone_set("Africa/Nairobi");
    require_once '../helpers/session.php';
    require_once '../config/class.php';
    $db = new db_class();

    // Get the user's name
    $user_name = $db->user_acc($_SESSION['user_id']);

   // Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: ../views/index.php');
    exit();
}

    // Handle delete action
    if (isset($_POST['delete_receipt'])) {
        $receipt_id = $_POST['receipt_id'];
        
        // Fetch the files associated with this receipt
        $files_query = "SELECT files FROM receipts WHERE id = ?";
        $stmt = $db->conn->prepare($files_query);
        $stmt->bind_param("i", $receipt_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $files_data = $result->fetch_assoc();
        
        if ($files_data) {
            $files = json_decode($files_data['files'], true);
            // Delete the physical files
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
        
        // Delete the receipt from the database
        $delete_query = "DELETE FROM receipts WHERE id = ?";
        $stmt = $db->conn->prepare($delete_query);
        $stmt->bind_param("i", $receipt_id);
        if ($stmt->execute()) {
            $success_message = "Receipt deleted successfully.";
        } else {
            $error_message = "Error deleting receipt: " . $db->conn->error;
        }
        $stmt->close();
    }

    // Fetch receipts from the database
    $receipts_query = "SELECT * FROM receipts ORDER BY posting_date DESC";
    $receipts_result = $db->conn->query($receipts_query);

    // Function to get file icon based on extension
    function getFileIcon($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        switch($extension) {
            case 'pdf':
                return 'fa-file-pdf';
            case 'doc':
            case 'docx':
                return 'fa-file-word';
            case 'xls':
            case 'xlsx':
                return 'fa-file-excel';
            case 'jpg':
            case 'jpeg':
            case 'png':
                return 'fa-file-image';
            default:
                return 'fa-file';
        }
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Receipts - Lato Management System</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>

#alertContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            width: 300px;
        }
        .alert {
            margin-bottom: 10px;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 100%;
        }
        .row .card{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }
        .container-fluid .card{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }
        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        .file-link {
            display: inline-block;
            margin-bottom: 5px;
        }
        .file-link i {
            margin-right: 5px;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
            <!-- Import Sidebar -->
            <?php require_once '../components/includes/sidebar.php'; ?>


                <!-- Begin Page Content -->
                <div class="container-fluid pt-4">

                <!-- Alert Container -->
                <div id="alertContainer"></div>
                    <!-- Page Heading -->
                    <h1 class="h3 mb-2 text-gray-800">Receipts</h1>
                    <p class="mb-4">View and manage all posted receipts.</p>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Receipts Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 style="color: #030f57;" class="m-0 font-weight-bold">Posted Receipts</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="receiptsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Receipt Type</th>
                                            <th>Posting Date</th>
                                            <th>Attached Files</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $receipts_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo ucfirst($row['type']); ?></td>
                                            <td><?php echo date('F j, Y', strtotime($row['posting_date'])); ?></td>
                                            <td>
                                            <?php
                                            $files = json_decode($row['files'], true);
                                            if (!empty($files) && is_array($files)) {
                                                foreach ($files as $file) {
                                                    $file_name = basename($file);
                                                    $file_icon = getFileIcon($file_name);
                                                    echo "<a href='" . htmlspecialchars($file) . "' download class='file-link'>";
                                                    echo "<i class='fas {$file_icon}'></i> " . htmlspecialchars($file_name);
                                                    echo "</a><br>";
                                                }
                                            } else {
                                                echo "<span class='text-muted'>No files attached</span>";
                                            }
                                            ?>
                                        </td>
                                            <td>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this receipt?');">
                                                    <input type="hidden" name="receipt_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="delete_receipt" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
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
                    <a class="btn btn-primary" href="logout.php">Logout</a>
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

   <!-- Page level plugins -->
   <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script>
        $(document).ready(function() {
            // Add this new function for showing alerts
            function showAlert(message, type) {
                var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
                var alertHtml = `
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        <i class="fas ${icon} mr-2"></i>
                        ${message}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                `;
                var $alert = $(alertHtml);
                $('#alertContainer').append($alert);
                
                // Auto-hide the alert after 5 seconds
                setTimeout(function() {
                    $alert.alert('close');
                }, 5000);
            }

            // Initialize DataTable
            $('#receiptsTable').DataTable({
                "order": [[ 1, "desc" ]],
                "columnDefs": [
                    { "orderable": false, "targets": [2, 3] }
                ]
            });

            // Handle delete form submission
            $('form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                
                $.ajax({
                    url: 'receipts.php',
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.includes("Receipt deleted successfully.")) {
                            showAlert("Receipt deleted successfully.", "success");
                            // Remove the row from the table
                            form.closest('tr').remove();
                        } else {
                            showAlert("Error deleting receipt. Please try again.", "error");
                        }
                    },
                    error: function() {
                        showAlert("An error occurred. Please try again.", "error");
                    }
                });
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
        });
    </script>

</body>

</html>