<?php
require_once '../config/class.php';
require_once '../helpers/session.php';
$db = new db_class();

if(isset($_POST['update'])) {
    $loan_id = $_POST['loan_id'];
    $client = $_POST['client'];
    $loan_product_id = $_POST['loan_product_id'];
    $loan_amount = $_POST['loan_amount'];
    $purpose = $_POST['purpose'];
    $status = $_POST['status'];
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
    
    // Get client details
    $client_query = $db->conn->prepare("SELECT first_name, last_name FROM client_accounts WHERE account_id = ?");
    $client_query->bind_param("i", $client);
    $client_query->execute();
    $client_result = $client_query->get_result();
    $client_data = $client_result->fetch_assoc();
    $client_name = $client_data['first_name'] . ' ' . $client_data['last_name'];
    
    // Get current loan status
    $current_status_query = $db->conn->prepare("SELECT status FROM loan WHERE loan_id = ?");
    $current_status_query->bind_param("i", $loan_id);
    $current_status_query->execute();
    $current_status_result = $current_status_query->get_result();
    $current_status_data = $current_status_result->fetch_assoc();
    $current_status = $current_status_data['status'];
    
    // Fetch loan product details
    $loan_product = $db->get_loan_product($loan_product_id);
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
    
    // Set date_released if status changes to Released (2)
    $date_released = null;
    if ($status == 2 && $current_status != 2) {
        $date_released = date('Y-m-d H:i:s');
    }
    
    $result = $db->update_loan(
        $loan_id,
        $client,
        $loan_product_id,
        $loan_amount,
        $purpose,
        $status,
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
        json_encode($guarantor_pledges),
        $date_released
    );
    
    if ($result === true) {
        // Add notification for status change
        if ($current_status != $status) {
            $status_texts = ['Pending Approval', 'Approved', 'Released', 'Completed', 'Denied'];
            $new_status_text = $status_texts[$status] ?? 'Unknown';
            $status_message = "Loan status for $client_name changed to $new_status_text";
            $db->addNotification($status_message, 'loan', $loan_id);
        }
        
        $_SESSION['success'] = "Loan updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update loan. Please try again.";
    }
    
    header("Location: ../models/loan.php");
    exit();
}

header("Location: ../models/loan.php");
exit();
?>