<?php
    date_default_timezone_set("Africa/Nairobi");
    require_once '../helpers/session.php';
    require_once '../config/class.php';
    $db = new db_class(); 

    // Check if the user is logged in and has the admin role
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        $_SESSION['error_msg'] = "Unauthorized access";
        header("Location: ../views/index.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Users - Lato Management System</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <link href="../public/css/dataTables.bootstrap4.css" rel="stylesheet">
    <style>
        .container-fluid .card{
            box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
            border: 0;
        }
        html, body {
            overflow-x: hidden;
        }
        #accordionSidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            width: 225px;
            transition: width 0.3s ease;
        }
        #content-wrapper {
            margin-left: 225px;
            width: calc(100% - 225px);
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        .topbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 225px;
            z-index: 1000;
            transition: left 0.3s ease;
        }
        .container-fluid {
            margin-top: 70px;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        @media (max-width: 768px) {
            #accordionSidebar {
                width: 100px;
            }
            #content-wrapper {
                margin-left: 100px;
                width: calc(100% - 100px);
            }
            .topbar {
                left: 100px;
            }
            .sidebar .nav-item .nav-link span {
                display: none;
            }
        }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <ul style="background: #51087E;"  class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
                <div class="sidebar-brand-text mx-3">LATO SACCO</div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item">
                <a class="nav-link" href="../views/home.php">
                    <i class="fas fa-fw fa-home"></i>
                    <span>Home</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Management
            </div>

            <li class="nav-item">
                <a class="nav-link" href="loan.php">
                <i class="fas fa-fw fas fa-comment-dollar"></i>
                    <span>New Loan</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="pending_approval.php">
                <i class="fas fa-fw fas fa-comment-dollar"></i>
                    <span>Pending Approval</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="disbursement.php">
                    <i class="fas fa-fw fas fa-coins"></i>
                    <span>Disbursements</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="../views/daily-reconciliation.php">
                    <i class="fas fa-fw fa-balance-scale"></i>
                    <span>Daily Reconciliation</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="../views/expenses_tracking.php">
                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                    <span>Expenses Tracking</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="../views/manage_expenses.php">
                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    <span>Manage Expenses</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="arrears.php">
                <i class="fas fa-users-slash fa-2x text-gray-300"></i>
                    <span>Arrears</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../views/receipts.php">
                <i class="fas fa-receipt fa-2x"></i>
                    <span>Receipts</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../views/account.php">
                <i class="fas fa-fw fa-user"></i>
                    <span>Client Accounts</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Wekeza Groups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="business_groups.php">
                <i class="fas fa-users fa-2x text-gray-300"></i>
                    <span>Business Groups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="loan_plan.php">
                    <i class="fas fa-fw fa-piggy-bank"></i>
                    <span>Loan Products</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                System
            </div>

            <li class="nav-item">
                <a class="nav-link" href="user.php">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Users</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../views/settings.php">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link" href="../views/announcements.php">
                    <i class="fas fa-fw fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../views/notifications.php">
                    <i class="fas fa-fw fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../views/backup.php">
                    <i class="fas fa-fw fa-database"></i>
                    <span>Backup</span>
                </a>
            </li>
        </ul>
        <!-- End of Sidebar -->


        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $db->user_acc($_SESSION['user_id'])?></span>
                                <img class="img-profile rounded-circle" src="../public/image/logo.jpg">
                            </a>
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

                <!-- Begin Page Content -->
                <div class="container-fluid pt-4">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Manage Users</h1>
                    </div>
                    <button class="mb-2 btn btn-lg btn-warning" href="#" data-toggle="modal" data-target="#addModal"><span class="fa fa-plus"></span> Add User</button>
                    <!-- DataTales Example -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Password</th>
                                            <th>Name</th>
                                            <th>Role</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $tbl_user=$db->display_user();
                                            
                                            while($fetch=$tbl_user->fetch_array()){
                                        ?>
                                        <tr>
                                            <td><?php echo $fetch['username']?></td>
                                            <td><?php echo $db->hide_pass($fetch['password'])?></td>
                                            <td><?php echo $fetch['firstname']." ".$fetch['lastname']?></td>
                                            <td><?php echo ucfirst($fetch['role'])?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        Action
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item bg-warning text-white" href="#" data-toggle="modal" data-target="#updateModal<?php echo $fetch['user_id']?>"><i class="fa fa-edit fa-1x"></i> Edit</a>
                                                        <?php
                                                            if($fetch['user_id'] == $_SESSION['user_id']){
                                                        ?>
                                                            <a class="dropdown-item bg-danger text-white" href="#" disabled="disabled"><i class="fa fa-exclamation fa-1x"></i> Cannot Delete</a>
                                                        <?php
                                                            }else{
                                                        ?>
                                                            <a class="dropdown-item bg-danger text-white" href="#" data-toggle="modal" data-target="#deleteModal<?php echo $fetch['user_id']?>"><i class="fa fa-trash fa-1x"></i> Delete</a>
                                                        <?php
                                                            }
                                                        ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Update User Modal -->
                                        <div class="modal fade" id="updateModal<?php echo $fetch['user_id']?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <form method="POST" action="../controllers/updateUser.php">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-warning">
                                                            <h5 class="modal-title text-white">Edit User</h5>
                                                            <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">×</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label>Username</label>
                                                                <input type="text" name="username" value="<?php echo $fetch['username']?>" class="form-control" required="required" />
                                                                <input type="hidden" name="user_id" value="<?php echo $fetch['user_id']?>"/>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Password</label>
                                                                <input type="password" name="password" value="<?php echo $fetch['password']?>" class="form-control" required="required" />
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Firstname</label>
                                                                <input type="text" name="firstname" value="<?php echo $fetch['firstname']?>" class="form-control" required="required" />
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Lastname</label>
                                                                <input type="text" name="lastname" value="<?php echo $fetch['lastname']?>" class="form-control" required="required" />
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Role</label>
                                                                <select name="role" class="form-control" required="required">
                                                                <option value="admin" <?php echo ($fetch['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                                                <option value="officer" <?php echo ($fetch['role'] == 'officer') ? 'selected' : ''; ?>>Field Officer</option>
                                                                <option value="cashier" <?php echo ($fetch['role'] == 'cashier') ? 'selected' : ''; ?>>Cashier</option>
                                                                <option value="manager" <?php echo ($fetch['role'] == 'manager') ? 'selected' : ''; ?>>Manager</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="update" class="btn btn-warning">Update</a>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Delete User Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $fetch['user_id']?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger">
                                                        <h5 class="modal-title text-white">System Information</h5>
                                                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">×</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">Are you sure you want to delete this record?</div>
                                                    <div class="modal-footer">
                                                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                                                        <a class="btn btn-danger" href="../controllers/deleteUser.php?user_id=<?php echo $fetch['user_id']?>&user=<?php echo $_SESSION['user']?>">Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                            }
                                        ?>
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

        <!-- Add User Modal-->
        <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="../controllers/addUser.php">
                    <div class="modal-content">
                        <div style="background-color: #51087E;" class="modal-header">
                            <h5 class="modal-title text-white">Add User</h5>
                            <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" required="required" />
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required="required" />
                            </div>
                            <div class="form-group">
                                <label>Firstname</label>
                                <input type="text" name="firstname" class="form-control" required="required" />
                            </div>
                            <div class="form-group">
                                <label>Lastname</label>
                                <input type="text" name="lastname" class="form-control" required="required" />
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" class="form-control" required="required">
                                    <option value="admin">Admin</option>
                                    <option value="officer">Field Officer</option>
                                    <option value="cashier">Cashier</option>
                                    <option value="manager">Manager</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                            <button type="submit" name="confirm" class="btn btn-warning">Confirm</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Logout Modal-->
        <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger">
                        <h5 class="modal-title text-white">System Information</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">Are you sure you want to logout?</div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <a class="btn btn-danger" href="../views/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>



<!--Reassign modal--->
        <div class="modal fade" id="reassignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="../controllers/deleteUser.php">
            <div class="modal-content">
                <div style="background-color: #51087E;" class="modal-header">
                    <h5 class="modal-title text-white">Reassign Groups Before Deletion</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>This user is assigned as a field officer to some groups. Please select a new field officer to take over these groups before deletion.</p>
                    
                    <div class="form-group">
                        <label>New Field Officer</label>
                        <select name="new_officer_id" class="form-control" required>
                            <?php
                            if (isset($_SESSION['delete_user_id'])) {
                                $officers = $db->get_field_officers($_SESSION['delete_user_id']);
                                while ($officer = $officers->fetch_assoc()) {
                                    echo "<option value='" . $officer['user_id'] . "'>" . 
                                         htmlspecialchars($officer['firstname'] . ' ' . $officer['lastname']) . 
                                         "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <input type="hidden" name="user_id" value="<?php echo isset($_SESSION['delete_user_id']) ? $_SESSION['delete_user_id'] : ''; ?>">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="reassign_and_delete" class="btn btn-warning">Reassign and Delete</button>
                </div>
            </div>
        </form>
    </div>
</div>

        <!-- Bootstrap core JavaScript-->
        <script src="../public/js/jquery.js"></script>
        <script src="../public/js/bootstrap.bundle.js"></script>

        <!-- Core plugin JavaScript-->
        <script src="../public/js/jquery.easing.js"></script>

        <!-- Page level plugins -->
        <script src="../public/js/jquery.dataTables.js"></script>
        <script src="../public/js/dataTables.bootstrap4.js"></script>

        <!-- Custom scripts for all pages-->
        <script src="../public/js/sb-admin-2.js"></script>

        <script>
            <?php if (isset($_GET['show_reassign']) && $_GET['show_reassign'] == 1): ?>
                $(document).ready(function() {
                    $('#reassignModal').modal('show');
                });
            <?php endif; ?>




            $(document).ready(function() {
                $('#dataTable').DataTable({
                    "order": [[2, "asc"]]
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
            });
        </script>

    </body>
</html>