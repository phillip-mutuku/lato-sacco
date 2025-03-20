<?php
require_once('../controllers/accountController.php');

header('Content-Type: application/json');

$controller = new AccountController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accountId']) && isset($_POST['loanId']) && isset($_POST['repayAmount'])) {
        $accountId = filter_var($_POST['accountId'], FILTER_VALIDATE_INT);
        $loanId = filter_var($_POST['loanId'], FILTER_VALIDATE_INT);
        $repayAmount = filter_var($_POST['repayAmount'], FILTER_VALIDATE_FLOAT);

        if ($accountId === false || $loanId === false || $repayAmount === false) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid input parameters']);
            exit;
        }

        try {
            $result = $controller->repayLoan($accountId, $loanId, $repayAmount);
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Loan repayment successful']);
            } else {
                $error = $controller->getLastError();
                echo json_encode(['status' => 'error', 'message' => 'Failed to process loan repayment: ' . $error]);
            }
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