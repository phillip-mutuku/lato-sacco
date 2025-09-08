<?php
// Set timezone and error reporting
date_default_timezone_set("Africa/Nairobi");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');


ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("Starting backup process");



ini_set('max_execution_time', 1800); // 30 minutes
ini_set('memory_limit', '1G');
set_time_limit(1800);
ignore_user_abort(true);



// Include required files and initialize the database connection
require_once '../helpers/session.php';
require_once '../config/class.php';
$db = new db_class();

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'cashier')) {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: index.php');
    exit();
}

// Function to log errors
function logError($message) {
    $logFile = '../logs/backup_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}


// Function to get directory size
function getDirSize($dir) {
    $size = 0;
    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : getDirSize($each);
    }
    return $size;
}

// Function to format bytes to human-readable format
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Get backup storage information
$backupDir = '../backups/';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}
$totalSpace = 1024 * 1024 * 1024 * 100;
$usedSpace = getDirSize($backupDir);
$databaseSize = $db->get_database_size();
$usedSpace += $databaseSize; 
$freeSpace = $totalSpace - $usedSpace;
$usedPercentage = round(($usedSpace / $totalSpace) * 100, 2);




// Function to create a full backup
function createFullBackup($db) {
    global $backupDir;
    $timestamp = date('Y-m-d_H-i-s');
    $filename = 'full_backup_' . $timestamp . '.sql';
    $filepath = $backupDir . $filename;

    try {
        // Database backup
        $backupContent = $db->create_full_backup();
        if ($backupContent === false) {
            throw new Exception("Failed to create database backup");
        }
        
        if (file_put_contents($filepath, $backupContent) === false) {
            throw new Exception("Failed to write database backup to file: $filepath");
        }
        
        logError("Database backup created successfully: $filepath");

        // Check if ZIP functionality is available
        if (!class_exists('ZipArchive')) {
            // Return success with just database backup if ZIP is not available
            return array(
                'database' => $filename,
                'filesystem' => null,
                'message' => 'Database backup created successfully. File system backup skipped - ZIP extension not available.'
            );
        }

        // File system backup
        try {
            $fileSystemBackup = 'full_filesystem_backup_' . $timestamp . '.zip';
            $fileSystemBackupPath = $backupDir . $fileSystemBackup;
            $rootDir = realpath('../');
            
            $zip = new ZipArchive();
            if ($zip->open($fileSystemBackupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new Exception("Failed to create ZIP archive");
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rootDir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($rootDir) + 1);
                    if (!$zip->addFile($filePath, $relativePath)) {
                        logError("Failed to add file to ZIP: $filePath");
                    }
                }
            }
            
            $zip->close();
            logError("File system backup created successfully: $fileSystemBackupPath");
            
            return array(
                'database' => $filename,
                'filesystem' => $fileSystemBackup,
                'message' => 'Full backup created successfully'
            );
        } catch (Exception $e) {
            logError("File system backup failed: " . $e->getMessage());
            // Return success with just database backup if filesystem backup fails
            return array(
                'database' => $filename,
                'filesystem' => null,
                'message' => 'Database backup created successfully. File system backup failed: ' . $e->getMessage()
            );
        }
    } catch (Exception $e) {
        logError("Full backup creation failed: " . $e->getMessage());
        throw $e;
    }
}


// Function to create an incremental backup
function createIncrementalBackup($db) {
    global $backupDir;
    $timestamp = date('Y-m-d_H-i-s');
    $filename = 'incremental_backup_' . $timestamp . '.sql';
    $filepath = $backupDir . $filename;

    try {
        $last_backup_date = getLastBackupDate();
        $backupContent = $db->create_incremental_backup($last_backup_date);
        
        if (empty($backupContent)) {
            return array('status' => 'info', 'message' => 'No changes detected since last backup.');
        }
        
        if (file_put_contents($filepath, $backupContent) === false) {
            throw new Exception("Failed to write backup to file: $filepath");
        }

        return array('status' => 'success', 'message' => 'Incremental backup created successfully', 'file' => $filename);
    } catch (Exception $e) {
        logError("Incremental backup creation failed: " . $e->getMessage());
        return array('status' => 'error', 'message' => 'An error occurred while creating the backup: ' . $e->getMessage());
    }
}

function getLastBackupDate() {
    global $backupDir;
    $files = glob($backupDir . '*.sql');
    if (empty($files)) {
        return date('Y-m-d H:i:s', 0);
    }
    $last_backup = max(array_map('filemtime', $files));
    return date('Y-m-d H:i:s', $last_backup);
}




// Handle backup creation
function checkBackupRequirements() {
    $requirements = array();
    
    // Check PHP version
    $requirements['php_version'] = PHP_VERSION_ID >= 70200;
    
    // Check ZipArchive
    $requirements['zip_extension'] = extension_loaded('zip');
    
    // Check directory permissions
    $backupDir = '../backups/';
    $logsDir = '../logs/';
    
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    if (!file_exists($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
    
    $requirements['backup_dir_writable'] = is_writable($backupDir);
    $requirements['logs_dir_writable'] = is_writable($logsDir);
    
    // Check memory limit
    $memoryLimit = ini_get('memory_limit');
    $requirements['memory_limit'] = (int)$memoryLimit >= 256;
    
    return $requirements;
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Ensure no output has been sent
    if (!headers_sent()) {
        // Clear any existing output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
    }
    
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'create_backup') {
            $backupType = $_POST['backupType'] ?? 'full';
            
            if (!in_array($backupType, ['full', 'incremental'])) {
                throw new Exception("Invalid backup type");
            }
            
            if ($backupType === 'full') {
                $result = createFullBackup($db);
                echo json_encode([
                    'status' => 'success',
                    'message' => $result['message'] ?? 'Full backup created successfully',
                    'files' => [
                        'database' => $result['database'],
                        'filesystem' => $result['filesystem']
                    ]
                ]);
            } else {
                $result = createIncrementalBackup($db);
                echo json_encode($result);
            }
            
        } elseif ($_POST['action'] === 'delete_backup') {
            if (!isset($_POST['filename'])) {
                throw new Exception('Filename not provided');
            }

            $filename = $_POST['filename'];
            
            // Validate filename to prevent directory traversal
            if (preg_match('/[^a-zA-Z0-9._-]/', $filename)) {
                throw new Exception('Invalid filename');
            }
            
            $filepath = $backupDir . $filename;
            
            // Check if file exists and is within backup directory
            if (!file_exists($filepath) || !is_file($filepath)) {
                throw new Exception('Backup file not found');
            }
            
            // Try to delete the file
            if (unlink($filepath)) {
                logError("Backup file deleted successfully: $filepath");
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Backup deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete backup file');
            }
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}






// Handle backup download
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['filename'])) {
    $filename = $_GET['filename'];
    $filepath = $backupDir . $filename;
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}

// Get list of existing backups
function getBackups() {
    global $backupDir;
    $backups = array();
    $files = glob($backupDir . '*.{sql,zip}', GLOB_BRACE);
    foreach ($files as $file) {
        $backups[] = array(
            'filename' => basename($file),
            'size' => formatBytes(filesize($file)),
            'date' => date('Y-m-d H:i:s', filemtime($file)),
            'type' => (strpos($file, 'full') !== false) ? 'Full' : 'Incremental'
        );
    }
    return $backups;
}

$backups = getBackups();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Backup - Lato Management System</title>

    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <!-- Font Awesome Icons -->
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Custom styles -->
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .backup-card {
            border: none;
            border-radius: 8px;
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
        }
        .backup-card .card-header {
            color: #51087E;;
            font-weight: bold;
            border-radius: 8px 8px 0 0;
        }
        .btn-create-backup {
            background-color: #1cc88a;
            border-color: #1cc88a;
        }
        .btn-create-backup:hover {
            background-color: #17a673;
            border-color: #17a673;
        }
        .progress {
            height: 25px;
        }
        .progress-bar {
            line-height: 25px;
        }
        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
    </style>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

   <!-- Import Sidebar -->
    <?php require_once '../components/includes/cashier_sidebar.php'; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid pt-4">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Backup Management</h1>

                    <!-- Backup Options Card -->
                    <div class="card backup-card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Backup Options</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <form id="createBackupForm">
                                        <div class="form-group">
                                            <label for="backupType">Backup Type</label>
                                            <select class="form-control" id="backupType" name="backupType">
                                                <option value="full">Full Backup</option>
                                                <option value="incremental">Incremental Backup</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-warning btn-create-backup">
                                            <i class="fas fa-database mr-2"></i>Create New Backup
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Backup Storage</h5>
                                    <div class="progress mb-3">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $usedPercentage; ?>%;" aria-valuenow="<?php echo $usedPercentage; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $usedPercentage; ?>% Used</div>
                                    </div>
                                    <p>Total Space: <?php echo formatBytes($totalSpace); ?></p>
                                    <p>Used Space: <?php echo formatBytes($usedSpace); ?></p>
                                    <p>Free Space: <?php echo formatBytes($freeSpace); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Backup List Card -->
                    <div class="card backup-card">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Backup List</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="backupTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Filename</th>
                                            <th>Size</th>
                                            <th>Date Created</th>
                                            <th>Type</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($backups as $backup): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($backup['filename']); ?></td>
                                                <td><?php echo htmlspecialchars($backup['size']); ?></td>
                                                <td><?php echo $backup['date']; ?></td>
                                                <td><?php echo htmlspecialchars($backup['type']); ?></td>
                                                <td>
                                                    <a href="?action=download&filename=<?php echo urlencode($backup['filename']); ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                    <button class="btn btn-sm btn-danger delete-backup" data-filename="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

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

        <!-- DataTables -->
        <script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js"></script>

        <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#backupTable').DataTable({
                responsive: true,
                order: [[2, 'desc']] 
            });

            // Handle backup creation
            $('#createBackupForm').submit(function(e) {
    e.preventDefault();
    
    var backupType = $('#backupType').val();
    
    // Disable the button and show progress
    $('.btn-create-backup')
        .prop('disabled', true)
        .html('<i class="fas fa-spinner fa-spin mr-2"></i>Creating Backup...');
    
    $.ajax({
        url: 'cashier_backup.php',
        type: 'POST',
        data: {
            action: 'create_backup',
            backupType: backupType
        },
        dataType: 'json',
        success: function(response) {
            console.log('Backup response:', response);
            if (response.status === 'success') {
                var message = response.message;
                if (response.files && !response.files.filesystem) {
                    message += '\nNote: File system backup was not created due to missing ZIP support.';
                }
                alert(message);
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Unknown error occurred'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            
            alert('An error occurred while creating the backup. The ZIP extension may not be installed. Please contact your system administrator.');
        },
        complete: function() {
            $('.btn-create-backup')
                .prop('disabled', false)
                .html('<i class="fas fa-database mr-2"></i>Create New Backup');
        }
    });
});



            // Handle backup deletion
            $('.delete-backup').click(function() {
                var filename = $(this).data('filename');
                if (confirm('Are you sure you want to delete this backup?')) {
                    $.ajax({
                        url: 'cashier_backup.php',
                        type: 'POST',
                        data: {
                            action: 'delete_backup',
                            filename: filename
                        },
                        dataType: 'json',
                        success: function(response) {
                            console.log('Delete response:', response);
                            if (response.status === 'success') {
                                alert(response.message);
                                location.reload();
                            } else {
                                alert('Error: ' + (response.message || 'Failed to delete backup'));
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Delete error:', {
                                status: status,
                                error: error,
                                response: xhr.responseText
                            });
                            alert('An error occurred while deleting the backup. Please check the console for details.');
                        }
                    });
                }
            });


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
        });
        </script>

    </body>

</html>