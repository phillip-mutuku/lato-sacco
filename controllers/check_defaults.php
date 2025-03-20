<?php
require_once '../config/class.php';
$db = new db_class();

// Check and update defaulters
$query = "UPDATE loan_schedule 
          SET status = 'paid' 
          WHERE default_amount <= 0";

$db->conn->query($query);

// Check if any records were updated
$affected_rows = $db->conn->affected_rows;

header('Content-Type: application/json');
echo json_encode(['refresh' => $affected_rows > 0]);
?>