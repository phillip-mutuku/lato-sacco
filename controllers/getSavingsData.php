<?php
require_once('../controllers/accountController.php');

header('Content-Type: application/json');

$controller = new AccountController();

if (isset($_GET['accountId']) && isset($_GET['filter'])) {
    $accountId = filter_var($_GET['accountId'], FILTER_VALIDATE_INT);
    $filter = filter_var($_GET['filter'], FILTER_SANITIZE_STRING);
    $startDate = isset($_GET['startDate']) ? filter_var($_GET['startDate'], FILTER_SANITIZE_STRING) : null;
    $endDate = isset($_GET['endDate']) ? filter_var($_GET['endDate'], FILTER_SANITIZE_STRING) : null;

    if ($accountId === false || empty($filter)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input parameters']);
        exit;
    }

    try {
        $savingsData = $controller->getSavingsData($accountId, $filter, $startDate, $endDate);

        $labels = array_column($savingsData, 'date');
        $amounts = array_column($savingsData, 'amount');

        echo json_encode([
            'status' => 'success',
            'labels' => $labels,
            'amounts' => $amounts
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
}
?>