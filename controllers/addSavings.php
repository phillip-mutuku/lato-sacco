<?php
require_once('../controllers/accountController.php');
header('Content-Type: application/json');

$controller = new AccountController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accountId']) && isset($_POST['amount']) && isset($_POST['paymentMode'])) {
        $accountId = filter_var($_POST['accountId'], FILTER_VALIDATE_INT);
        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
        $paymentMode = filter_var($_POST['paymentMode'], FILTER_SANITIZE_STRING);

        if ($accountId === false || $amount === false || empty($paymentMode)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid input parameters']);
            exit;
        }

        try {
            // Get the logged-in user's name (assuming you have a session with user information)
            $servedBy = $_SESSION['user_name'] ?? 'Unknown';

            $result = $controller->addSavings($accountId, $amount, $paymentMode, $servedBy);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>