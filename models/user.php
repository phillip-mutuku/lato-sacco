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
            <?php require_once '../components/includes/sidebar.php'; ?>

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