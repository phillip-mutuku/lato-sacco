<?php
require_once('../controllers/accountController.php');

header('Content-Type: application/json');

$controller = new AccountController();

if (isset($_GET['accountId']) && isset($_GET['filter'])) {
    $accountId = filter_var($_GET['accountId'], FILTER_VALIDATE_INT);
    $filter = filter_var($_GET['filter'], FILTER_SANITIZE_STRING);

    if ($accountId === false || empty($filter)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input parameters']);
        exit;
    }

    try {
        $transactionData = $controller->getTransactionData($accountId, $filter);

        $labels = array_column($transactionData, 'type');
        $amounts = array_column($transactionData, 'amount');
        $dates = array_column($transactionData, 'date');

        echo json_encode([
            'status' => 'success',
            'labels' => $labels,
            'amounts' => $amounts,
            'dates' => $dates
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
}
?>