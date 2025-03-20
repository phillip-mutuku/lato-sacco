<?php
require_once '../models/Loan.php';

class LoansController {
    public function apply() {
        if ($_POST) {
            $loan = new Loan();
            $loan->apply($_POST['client_id'], $_POST['loan_amount'], $_POST['loan_term']);
            header('Location: ../views/loans/apply.php');
        }
    }

    public function approve($loan_app_id) {
        $loan = new Loan();
        $loan->update_status($loan_app_id, 'approved');
        header('Location: ../views/loans/history.php');
    }

    public function deny($loan_app_id) {
        $loan = new Loan();
        $loan->update_status($loan_app_id, 'denied');
        header('Location: ../views/loans/history.php');
    }
}

// Handle form submission
if (isset($_POST['action']) && $_POST['action'] == 'apply_loan') {
    (new LoansController())->apply();
}