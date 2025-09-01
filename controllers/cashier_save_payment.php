<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../config/class.php';
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log errors
function logError($message) {
    error_log("Disbursement Error: " . $message);
}

if(isset($_POST['save'])) {
    try {
        $db = new db_class();
        
        // Debug: Output all POST data
        logError("POST data: " . print_r($_POST, true));
        
        // Get form data
        $loan_id = $_POST['loan_id'];
        $receipt_no = $_POST['receipt_no'];
        $payee = $_POST['payee'];
        $pay_amount = $_POST['pay_amount'];
        $penalty = $_POST['penalty'];
        $overdue = $_POST['overdue'];
        $withdrawal_fee = $_POST['withdrawal_fee'];
        $user_id = $_SESSION['user_id'] ?? null;

        // Validate receipt number
        if (empty($receipt_no)) {
            throw new Exception("Receipt number is required");
        }

        if (!$user_id) {
            throw new Exception("User ID not found in session");
        }

        // Fetch client details from client_accounts table
        $client_query = $db->conn->prepare("SELECT ca.account_id, ca.first_name, ca.last_name 
                                          FROM loan l
                                          JOIN client_accounts ca ON l.account_id = ca.account_id 
                                          WHERE l.loan_id = ?");
        $client_query->bind_param("i", $loan_id);
        $client_query->execute();
        $client_result = $client_query->get_result();
        $client_data = $client_result->fetch_assoc();
        
        if (!$client_data) {
            throw new Exception("Client details not found for loan ID: $loan_id");
        }
        
        $client_name = $client_data['first_name'] . ' ' . $client_data['last_name'];
        $account_id = $client_data['account_id'];

        // Start transaction
        $db->conn->begin_transaction();

        // Save payment (disbursement)
        $payment_result = $db->save_payment($loan_id, $receipt_no, $payee, $pay_amount, $penalty, $overdue, $withdrawal_fee, $user_id);
        
        if($payment_result) {
            // Update loan status to disbursed (use integer value instead of string)
            $update_loan_result = $db->conn->query("UPDATE `loan` SET `status`='2', `date_released`=NOW() WHERE `loan_id`='$loan_id'");
            if (!$update_loan_result) {
                throw new Exception("Failed to update loan status: " . $db->conn->error);
            }

            // Add notification
            $message = "Loan of KSh " . number_format($pay_amount, 2) . " disbursed to $client_name (Receipt #$receipt_no)";
            $db->addNotification($message, 'payment', $loan_id);

            // Record transaction for loan disbursement
            $description = "Loan Disbursement - Ref: " . $loan_id . " (Receipt #$receipt_no)";
            $transaction_query = $db->conn->prepare("INSERT INTO `transactions` (account_id, type, amount, description, date) VALUES (?, 'Loan Disbursement', ?, ?, NOW())");
            $transaction_query->bind_param("ids", $account_id, $pay_amount, $description);
            if (!$transaction_query->execute()) {
                throw new Exception("Failed to record transaction: " . $db->conn->error);
            }

            // Add SMS notification if configured
            if ($db->get_setting('sms_notifications_enabled')) {
                $phone = $client_data['phone_number'];
                $sms_message = "Dear $client_name, your loan of KSh " . number_format($pay_amount, 2) . 
                             " has been disbursed. Receipt #$receipt_no. Thank you for choosing Lato Sacco.";
                // Implement your SMS sending logic here
            }

            // Commit transaction
            $db->conn->commit();
            
            logError("Disbursement successful for loan_id: $loan_id");
            
            // Check if this is an AJAX request (multiple ways to detect)
            $is_ajax = (
                (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
                (isset($_POST['ajax']) && $_POST['ajax'] == '1') ||
                (isset($_REQUEST['format']) && $_REQUEST['format'] == 'json')
            );
            
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Loan disbursed successfully'
                ]);
                exit();
            } else {
                header("Location: ../models/cashier_disbursement.php?success=1");
                exit();
            }
        } else {
            throw new Exception("Failed to save payment");
        }
    } catch (Exception $e) {
        // An error occurred; rollback the transaction
        $db->conn->rollback();
        logError("Error in save_payment.php: " . $e->getMessage());
        
        // Check if this is an AJAX request (multiple ways to detect)
        $is_ajax = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
             strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
            (isset($_POST['ajax']) && $_POST['ajax'] == '1') ||
            (isset($_REQUEST['format']) && $_REQUEST['format'] == 'json')
        );
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit();
        } else {
            echo "<script>alert('An error occurred: " . addslashes($e->getMessage()) . "');</script>";
            echo "<script>window.location='../models/cashier_disbursement.php?error=1';</script>";
        }
    }
} else {
    logError("POST 'save' not set in save_payment.php");
    
    // Return JSON response for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method'
        ]);
        exit();
    } else {
        header("Location: ../models/cashier_disbursement.php?error=2");
        exit();
    }
}
?>