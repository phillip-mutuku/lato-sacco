<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../helpers/session.php';
require_once '../config/class.php';
$db = new db_class(); 

// Check if user is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$products = $db->get_loan_types();

echo json_encode($products);
?>