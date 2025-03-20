<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../helpers/session.php';
require_once '../config/class.php';
$db = new db_class(); 

// Check if user is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'officer')) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$loan_id = isset($_GET['loan_id']) ? intval($_GET['loan_id']) : 0;

if ($loan_id <= 0) {
    echo json_encode(['error' => 'Invalid loan ID']);
    exit();
}

$loan = $db->get_loan_details_json($loan_id);

if (!$loan) {
    echo json_encode(['error' => 'Loan not found']);
    exit();
}

// Format data for display
$client_pledges = json_decode($loan['client_pledges'], true) ?: [];
$guarantor_pledges = json_decode($loan['guarantor_pledges'], true) ?: [];

// Prepare payment info if loan is released
$payment_info = [];
if ($loan['status'] == 2) {
    $next_payment = $db->get_next_payment_date($loan_id);
    $is_overdue = $db->is_loan_overdue($loan_id);
    $penalty = $is_overdue ? $loan['monthly_payment'] * 0.05 : 0; // 5% penalty for overdue
    
    $payment_info = [
        'next_payment_date' => $next_payment ? date('F d, Y', strtotime($next_payment)) : 'N/A',
        'monthly_amount' => $loan['monthly_payment'],
        'penalty' => $penalty,
        'payable_amount' => $loan['monthly_payment'] + $penalty
    ];
}

// Prepare response
$response = [
    'loan' => $loan,
    'client_pledges' => $client_pledges,
    'guarantor_pledges' => $guarantor_pledges,
    'payment_info' => $payment_info
];

echo json_encode($response);
?>