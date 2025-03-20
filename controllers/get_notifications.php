<?php
// Set timezone
date_default_timezone_set("Africa/Nairobi");

// Include required files and initialize the database connection
require_once '../helpers/session.php';
require_once '../config/class.php';

$db = new db_class();

// Function to get notifications
function getNotifications($db, $category) {
    $activityTypes = "'loan', 'payment', 'account', 'user'";
    $systemTypes = "'system', 'backup', 'update', 'settings'";
    
    $query = "SELECT * FROM notifications WHERE type IN (" . 
             ($category === 'activity' ? $activityTypes : $systemTypes) . 
             ") ORDER BY date DESC LIMIT 50";
    
    $result = $db->conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get notifications
$activityNotifications = getNotifications($db, 'activity');
$systemNotifications = getNotifications($db, 'system');

// Format notifications for JSON output
function formatNotifications($notifications) {
    return array_map(function($notification) {
        return [
            'id' => $notification['id'],
            'message' => $notification['message'],
            'type' => $notification['type'],
            'related_id' => $notification['related_id'],
            'date' => $notification['date'],
            'is_read' => (bool)$notification['is_read']
        ];
    }, $notifications);
}

// Prepare response
$response = [
    'activityNotifications' => formatNotifications($activityNotifications),
    'systemNotifications' => formatNotifications($systemNotifications)
];

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>