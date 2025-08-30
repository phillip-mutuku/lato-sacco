<?php
    date_default_timezone_set("Africa/Nairobi");
    require_once '../helpers/session.php';
    require_once '../config/class.php';
    $db = new db_class(); 

    // Get the current user's name
    $user_name = $db->user_acc($_SESSION['user_id']);

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'officer')) {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: index.php');
    exit();
}

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $topic = $_POST['topic'];
        $announcement = $_POST['announcement'];
        $announced_by = $user_name;
        $date_announced = date('Y-m-d H:i:s');

        // File upload handling
        $uploaded_files = [];
        if (!empty($_FILES['files']['name'][0])) {
            $file_count = count($_FILES['files']['name']);
            $allowed_extensions = array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'jpg', 'jpeg', 'png');
            for ($i = 0; $i < $file_count; $i++) {
                $filename = $_FILES['files']['name'][$i];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed_extensions)) {
                    $target_file = "../uploads/announcements/" . basename($filename);
                    if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $target_file)) {
                        $uploaded_files[] = $target_file;
                    }
                }
            }
        }

        // Insert into database
        $files_json = json_encode($uploaded_files);
        $stmt = $db->conn->prepare("INSERT INTO announcements (topic, announcement, files, announced_by, date_announced) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $topic, $announcement, $files_json, $announced_by, $date_announced);
        
        if ($stmt->execute()) {
            $success_message = "Announcement posted successfully!";
        } else {
            $error_message = "Error posting announcement: " . $db->conn->error;
        }
        $stmt->close();
    }

    // Handle deletion
    if (isset($_GET['delete'])) {
        $id = $_GET['delete'];
        $delete_stmt = $db->conn->prepare("DELETE FROM announcements WHERE id = ?");
        $delete_stmt->bind_param("i", $id);
        if ($delete_stmt->execute()) {
            $success_message = "Announcement deleted successfully!";
        } else {
            $error_message = "Error deleting announcement: " . $db->conn->error;
        }
        $delete_stmt->close();
    }

    // Fetch announcements
    $announcements = $db->conn->query("SELECT * FROM announcements ORDER BY date_announced DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Announcements - Lato Management System</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 100%;
        }
        .container-fluid .card {
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }
        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        .dropzone {
            border: 2px dashed #51087E;";
            border-radius: 5px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
        }
        .dropzone.dragover {
            background-color: #f8f9fa;
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
                    <h1 class="h3 mb-4 text-gray-800">Announcements</h1>

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

                    <!-- Announcement Form -->
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <h6 style="color: #51087E;" class="m-0 font-weight-bold">Post New Announcement</h6>
                        </div>
                        <div class="card-body">
                            <form id="announcementForm" method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="topic">Topic</label>
                                    <input type="text" class="form-control" id="topic" name="topic" required>
                                </div>
                                <div class="form-group">
                                    <label for="announcement">Announcement</label>
                                    <textarea class="form-control" id="announcement" name="announcement" rows="4" required></textarea>
                                </div>
                                <div class="form-group">
                                <label for="files">Attach Files (Optional)</label>
                                <div id="dropzone" class="dropzone">
                                    <p>Drag and drop files here or click to select files</p>
                                    <input type="file" id="files" name="files[]" multiple>
                                </div>
                                <div id="file-list"></div>
                                <small class="form-text text-muted">Accepted file types: docx, xlsx, pptx, pdf, jpg, jpeg, png</small>
                            </div>
                                <button style="background-color: #51087E; color: white;" type="submit" class="btn">Post Announcement</button>
                            </form>
                        </div>
                    </div>

                    <!-- Announcements Table -->
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <h6 style="color: #51087E;" class="m-0 font-weight-bold">All Announcements</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="announcementsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Topic</th>
                                            <th>Announcement</th>
                                            <th>Attached Files</th>
                                            <th>Announced By</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $announcements->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['topic']; ?></td>
                                            <td><?php echo $row['announcement']; ?></td>
                                            <td>
                                                <?php 
                                                $files = json_decode($row['files'], true);
                                                if (!empty($files)) {
                                                    foreach ($files as $file) {
                                                        echo '<a href="' . $file . '" download>' . basename($file) . '</a><br>';
                                                    }
                                                } else {
                                                    echo 'No files attached';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo $row['announced_by']; ?></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($row['date_announced'])); ?></td>
                                            <td>
                                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this announcement?');">Delete</a>
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
    <script src="../public/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../public/vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#announcementsTable').DataTable();

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


   // File upload functionality
   document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('files');
    const fileList = document.getElementById('file-list');

    dropzone.onclick = function() {
        fileInput.click();
    };

    fileInput.onchange = function() {
        handleFiles(this.files);
    };

    dropzone.ondragover = function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    };

    dropzone.ondragleave = function() {
        this.classList.remove('dragover');
    };

    dropzone.ondrop = function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    };

    function handleFiles(files) {
        Array.from(files).forEach(file => {
            const div = document.createElement('div');
            div.innerHTML = `
                <span>${file.name}</span>
                <button type="button" class="btn btn-sm btn-danger remove-file">Remove</button>
            `;
            fileList.appendChild(div);
        });
        updateDropzoneText();
    }

    function updateDropzoneText() {
        if (fileList.children.length > 0) {
            dropzone.querySelector('p').textContent = 'Files added. Click or drag to add more.';
        } else {
            dropzone.querySelector('p').textContent = 'Drag and drop files here or click to select files';
        }
    }

    // Remove file from list
    fileList.onclick = function(e) {
        if (e.target.classList.contains('remove-file')) {
            e.target.parentElement.remove();
            updateDropzoneText();
        }
    };
});

    // Form submission
    $('#announcementForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);

        $.ajax({
            url: 'announcements.php',
            type: 'POST',
            data: formData,
            success: function(data) {
                // Refresh the page to show the new announcement
                location.reload();
            },
            cache: false,
            contentType: false,
            processData: false
        });
    });
        });
    </script>
</body>
</html>