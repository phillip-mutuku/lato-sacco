<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../config/class.php';

$db = new db_class();

$today = date('Y-m-d');

$query = "SELECT SUM(pay_amount) as total 
          FROM payment 
          WHERE DATE(date_created) = '$today'";

$result = $db->conn->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    $total = $row['total'] ?? 0;
    echo number_format($total, 2);
} else {
    error_log("Error in get_total_disbursements.php: " . $db->conn->error);
    echo "0.00";
}
?>