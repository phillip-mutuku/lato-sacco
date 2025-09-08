<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../config/class.php';
session_start();


$db = new db_class();
$error_message = '';
$success_message = '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $result = $db->login($username, $password);
        
        if ($result['count'] > 0) {
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['role'] = $result['role'];
            
            switch($_SESSION['role']) {
                case 'admin':
                    header("Location: home.php");
                    break;
                case 'officer':
                    header("Location: field-officer.php");
                    break;
                case 'cashier':
                    header("Location: cashier.php");
                    break;
                case 'manager':
                        header("Location: home.php");
                        break;
                default:
                    header("Location: home.php");
            }
            exit();
        } else {
            $error_message = "Invalid Username or Password";
        }
    } elseif (isset($_POST['create_admin'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $role = 'admin';

        if (empty($username) || empty($password) || empty($confirm_password) || empty($firstname) || empty($lastname)) {
            $error_message = "All fields are required.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif ($db->getUserByUsername($username)) {
            $error_message = "Username already exists.";
        } else {
            if ($db->add_user($username, $password, $firstname, $lastname, $role)) {
                $success_message = "Admin account created successfully. You can now log in.";
                $mode = 'login';
            } else {
                $error_message = "Failed to create admin account. Please try again.";
            }
        }
    } elseif (isset($_POST['reset_password'])) {
        $username = $_POST['username'];
        $new_password = $_POST['new_password'];
        $confirm_new_password = $_POST['confirm_new_password'];
        
        if (empty($username) || empty($new_password) || empty($confirm_new_password)) {
            $error_message = "All fields are required.";
        } elseif ($new_password !== $confirm_new_password) {
            $error_message = "Passwords do not match.";
        } else {
            $result = $db->resetPassword($username, $new_password);
            if ($result === true) {
                $success_message = "Your password has been reset successfully. You can now log in with your new password.";
                $mode = 'login';
            } else {
                $error_message = $result;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lato Management System - Growing with you</title>
    <link rel="icon" type="image/jpeg" href="../public/image/logo.jpg">
    <link href="../public/css/all.css" rel="stylesheet" type="text/css">
    <link href="../public/css/sb-admin-2.css" rel="stylesheet">
    <style>
        body {
            background: url('../public/image/home-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            animation: animateBackground 30s infinite alternate;
            position: relative;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        @keyframes animateBackground {
            0% { background-position: center; }
            50% { background-position: center top; }
            100% { background-position: center bottom; }
        }
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom right, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7));
            z-index: 1;
        }
        .container {
            position: relative;
            z-index: 2;
            max-width: 500px;
            width: 100%;
        }
        .card {
            background: #c4c4c4;
            border-radius: 8px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            padding: 2em;
        }
        .form-control-user {
            border-radius: 25px;
            padding: 1.25em;
            font-size: 1em;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        .form-control-user:focus {
            border-color: #4e73df;
            box-shadow: 0 0 5px rgba(78, 115, 223, 0.5);
        }
        .btn-user {
            border-radius: 25px;
            background-color: #4e73df;
            color: #fff;
            padding: 0.75em 1.5em;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-user:hover {
            background-color: #2e59d9;
            color: #fff;
        }
        .text-gray-900 {
            color: #343a40 !important;
        }
        .alert {
            border-radius: 25px;
        }
        .text-center h1 {
            text-transform: uppercase;
            font-family: verdana;
            font-size: 2em;
            font-weight: 700;
            color: #f5f5f5;
            text-shadow: 1px 1px 1px #919191,
                1px 2px 1px #919191,
                1px 3px 1px #919191,
                1px 4px 1px #919191,
                1px 5px 1px #919191,
                1px 6px 1px #919191,
                1px 7px 1px #919191,
                1px 8px 1px #919191,
                1px 9px 1px #919191,
                1px 10px 1px #919191,
            1px 8px 6px rgba(16,16,16,0.4),
            1px 12px 10px rgba(16,16,16,0.2),
            1px 10px 20px rgba(16,16,16,0.2),
            1px 20px 30px rgba(16,16,16,0.4);
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="text-center">
                <h1>
                    <?php 
                    switch($mode) {
                        case 'login':
                            echo 'USER LOGIN';
                            break;
                        case 'create_admin':
                            echo 'CREATE ADMIN ACCOUNT';
                            break;
                        case 'reset_password':
                            echo 'RESET PASSWORD';
                            break;
                    }
                    ?>
                </h1>
            </div>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($mode == 'login'): ?>
                <?php
                // Display error message if set
                if(isset($_SESSION['error_msg'])) {
                    echo '<div id="error-alert" class="alert alert-danger alert-dismissible fade show" role="alert">
                            ' . $_SESSION['error_msg'] . '
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>';
                    unset($_SESSION['error_msg']);
                }
                ?>
                <form method="POST" class="user" action="">
                    <div class="form-group">
                        <input type="text" class="form-control form-control-user" name="username" placeholder="Enter Username here..." required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control form-control-user" name="password" placeholder="Enter Password here..." required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-user btn-block" name="login">Login</button>
                </form>
                <hr>
                <div class="text-center">
                    <a style="display: none;" class="small" href="?mode=create_admin">Create Admin Account</a>
                    <br>
                    <a style="display: none;" class="small" href="?mode=reset_password">Forgot Password?</a>
                </div>
            <?php elseif ($mode == 'create_admin'): ?>
                <form method="POST" class="user" action="">
                    <div class="form-group">
                        <input type="text" class="form-control form-control-user" name="username" placeholder="Username" required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control form-control-user" name="password" placeholder="Password" required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control form-control-user" name="confirm_password" placeholder="Confirm Password" required>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control form-control-user" name="firstname" placeholder="First Name" required>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control form-control-user" name="lastname" placeholder="Last Name" required>
                    </div>
                    <button style="display: none;" type="submit" class="btn btn-primary btn-user btn-block" name="create_admin">Create Admin Account</button>
                </form>
                <hr>
                <div class="text-center">
                    <a class="small" href="?mode=login">Back to Login</a>
                </div>
            <?php elseif ($mode == 'reset_password'): ?>
                <form method="POST" class="user" action="">
                    <div class="form-group">
                        <input type="text" class="form-control form-control-user" name="username" placeholder="Enter your username" required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control form-control-user" name="new_password" placeholder="Enter new password" required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control form-control-user" name="confirm_new_password" placeholder="Confirm new password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-user btn-block" name="reset_password">Reset Password</button>
                </form>
                <hr>
                <div class="text-center">
                    <a class="small" href="?mode=login">Back to Login</a>
                </div>
            <?php endif; ?>
            <div style="position: absolute; bottom: 10px; right: 10px; font-size: 0.8em; color: #666;">Lato Sacco &copy; <?php echo date('Y'); ?></div>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
        var alertElement = document.getElementById('error-alert');
        if (alertElement) {
            var closeButton = alertElement.querySelector('.close');
            if (closeButton) {
                closeButton.addEventListener('click', function() {
                    alertElement.style.display = 'none';
                });
            }
        }
    });
    </script>
</body>
</html>