<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../helpers/session.php';
require_once '../config/class.php';
$db = new db_class();

if(isset($_POST['save_loan'])) {
    $client = $_POST['client'];
    $loan_product_id = $_POST['loan_product_id'];
    $loan_amount = $_POST['loan_amount'];
    $purpose = $_POST['purpose'];
    $loan_term = $_POST['loan_term'];
    $meeting_date = $_POST['meeting_date'];
    
    // Process client pledges
    $client_pledges = array();
    foreach($_POST['client_pledges'] as $pledge) {
        if(!empty($pledge['item']) && !empty($pledge['value'])) {
            $client_pledges[] = $pledge;
        }
    }
    
    // Process guarantor details
    $guarantor_name = $_POST['guarantor_name'];
    $guarantor_id = $_POST['guarantor_id'];
    $guarantor_phone = $_POST['guarantor_phone'];
    $guarantor_location = $_POST['guarantor_location'];
    $guarantor_sublocation = $_POST['guarantor_sublocation'];
    $guarantor_village = $_POST['guarantor_village'];
    
    // Process guarantor pledges
    $guarantor_pledges = array();
    foreach($_POST['guarantor_pledges'] as $pledge) {
        if(!empty($pledge['item']) && !empty($pledge['value'])) {
            $guarantor_pledges[] = $pledge;
        }
    }
    
    $date_created = date("Y-m-d H:i:s");
    
    // Get client details
    $client_query = $db->conn->prepare("SELECT first_name, last_name FROM client_accounts WHERE account_id = ?");
    $client_query->bind_param("i", $client);
    $client_query->execute();
    $client_result = $client_query->get_result();
    $client_data = $client_result->fetch_assoc();
    $client_name = $client_data['first_name'] . ' ' . $client_data['last_name'];
    
    // Fetch loan product details
    $loan_product = $db->get_loan_product($loan_product_id);
    if (!$loan_product) {
        $_SESSION['error'] = "Invalid loan product selected.";
        header("Location: ../models/officer_loan.php");
        exit();
    }
    
    $interest_rate = $loan_product['interest_rate'];
    
    // Calculate loan details
    $monthly_rate = $interest_rate / 100 / 12;
    $monthly_payment = ($loan_amount * $monthly_rate * pow(1 + $monthly_rate, $loan_term)) / 
                      (pow(1 + $monthly_rate, $loan_term) - 1);
    $total_payable = $monthly_payment * $loan_term;
    $total_interest = $total_payable - $loan_amount;
    
    // Round the calculated values
    $monthly_payment = round($monthly_payment, 2);
    $total_payable = round($total_payable, 2);
    $total_interest = round($total_interest, 2);
    
    // Save loan with all new fields
    $result = $db->save_loan(
        $client, 
        $loan_product_id, 
        $loan_amount, 
        $purpose,
        $date_created,
        $loan_term,
        $interest_rate,
        $monthly_payment,
        $total_payable,
        $total_interest,
        $meeting_date,
        json_encode($client_pledges),
        $guarantor_name,
        $guarantor_id,
        $guarantor_phone,
        $guarantor_location,
        $guarantor_sublocation,
        $guarantor_village,
        json_encode($guarantor_pledges)
    );

    if ($result === true) {
        $loan_id = $db->conn->insert_id;
        
        // Add notification for new loan application
        $message = "New loan application received from $client_name for KSh " . number_format($loan_amount, 2);
        $db->addNotification($message, 'loan', $loan_id);
        
        // Add notification for initial loan status
        $status_message = "Loan application for $client_name has been submitted and is pending approval";
        $db->addNotification($status_message, 'loan', $loan_id);
        
        $_SESSION['success'] = "Loan application submitted successfully.";
    } else {
        $_SESSION['error'] = "Failed to submit loan application. Please try again.";
    }
    
    header("Location: ../models/officer_loan.php");
    exit();
}