<?php
// Set timezone
date_default_timezone_set("Africa/Nairobi");

// Include required files
require_once '../helpers/session.php';
require_once '../config/class.php';

// Initialize database connection
try {
    $db = new db_class();
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    $_SESSION['error_msg'] = "System error: Could not connect to database";
    header('Location: ../views/manage_expenses.php');
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_msg'] = "Unauthorized access. Please login first.";
    header('Location: ../views/index.php');
    exit();
}

// Function to validate and sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to generate unique reference number
function generate_reference($type, $prefix) {
    $date = date('Ymd');
    $random = sprintf('%04d', rand(1, 9999));
    return $prefix . '-' . $date . '-' . $random;
}

// Check if the form was actually submitted
if (!isset($_POST['save_expense'])) {
    $_SESSION['error_msg'] = "Form submission error: save_expense parameter missing";
    header('Location: ../views/manage_expenses.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_expense'])) {
    try {
        // Begin transaction
        $db->conn->autocommit(FALSE);

        // Get and sanitize form data
        $category = isset($_POST['category']) ? sanitize_input($_POST['category']) : null;
        $main_category = isset($_POST['main_category']) ? sanitize_input($_POST['main_category']) : null;
        $amount = isset($_POST['amount']) ? str_replace(',', '', $_POST['amount']) : null;
        $payment_method = isset($_POST['payment_method']) ? sanitize_input($_POST['payment_method']) : null;
        $date = isset($_POST['date']) ? sanitize_input($_POST['date']) : null;
        $description = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';
        $remarks = isset($_POST['remarks']) ? sanitize_input($_POST['remarks']) : '';
        $status = isset($_POST['status']) ? sanitize_input($_POST['status']) : 'completed';
        $receipt_no = isset($_POST['receipt_no']) ? sanitize_input($_POST['receipt_no']) : null;

        // Generate reference number
        $prefix = ($status === 'received') ? 'RCV' : 'EXP';
        $reference_no = generate_reference($status, $prefix);

        // Validate required fields
        $required_fields = [
            'category' => ($status === 'received' ? 'Income Source' : 'Expense Name'),
            'amount' => 'Amount',
            'payment_method' => 'Payment Method',
            'date' => 'Date',
            'receipt_no' => 'Receipt Number'
        ];

        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                throw new Exception("$label is required");
            }
        }

        // Validate amount
        $amount = filter_var($amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        if (!is_numeric($amount) || $amount <= 0) {
            throw new Exception("Please enter a valid amount greater than zero");
        }

        // Set amount sign based on transaction type (for reporting purposes)
        $signed_amount = ($status === 'received') ? abs($amount) : -abs($amount);

        // Validate date
        $formatted_date = date('Y-m-d H:i:s', strtotime($date));
        if ($formatted_date === false || strtotime($date) > time()) {
            throw new Exception("Please enter a valid date not in the future");
        }

        // Check for duplicate receipt number
        $check_receipt = "SELECT COUNT(*) as count FROM expenses WHERE receipt_no = ?";
        $stmt = $db->conn->prepare($check_receipt);
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $db->conn->error);
        }
        
        $stmt->bind_param('s', $receipt_no);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();

        if ($count > 0) {
            throw new Exception("Receipt number already exists. Please use a unique receipt number.");
        }

        // Insert into expenses table - this is all you need!
        $expense_query = "INSERT INTO expenses (
            category,
            amount,
            payment_method,
            date,
            description,
            remarks,
            reference_no,
            receipt_no,
            created_by,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $db->conn->prepare($expense_query);
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $db->conn->error);
        }
        
        $created_by = $_SESSION['user_id'];
        
        $stmt->bind_param(
            'sdssssssis',
            $category,
            $signed_amount,
            $payment_method,
            $formatted_date,
            $description,
            $remarks,
            $reference_no,
            $receipt_no,
            $created_by,
            $status
        );

        if (!$stmt->execute()) {
            throw new Exception("Error saving transaction: " . $stmt->error);
        }

        $transaction_id = $stmt->insert_id;
        $stmt->close();

        // Optional: Add notification (if the method exists)
        try {
            $notification_message = ($status === 'received') ?
                "New money received of KSh " . number_format(abs($amount), 2) . " has been recorded." :
                "New expense of KSh " . number_format(abs($amount), 2) . " has been recorded.";

            if (method_exists($db, 'addNotification')) {
                $transaction_type = ($status === 'received') ? 'income' : 'expense';
                $db->addNotification($notification_message, $transaction_type, $transaction_id);
            }
        } catch (Exception $notificationError) {
            error_log("Warning: Notification creation failed but transaction completed successfully: " . 
                     $notificationError->getMessage());
        }

        // Commit transaction
        if (!$db->conn->commit()) {
            throw new Exception("Failed to commit transaction");
        }

        $db->conn->autocommit(TRUE);

        // Set success message
        $_SESSION['success_msg'] = ($status === 'received' ? "Money received" : "Expense") . 
                                 " added successfully! Reference No: " . $reference_no;
        
        header('Location: ../views/manage_expenses.php');
        exit();

    } catch (Exception $e) {
        // Rollback transaction
        $db->conn->rollback();
        $db->conn->autocommit(TRUE);
        
        error_log("Error in save_expense.php: " . $e->getMessage());
        $_SESSION['error_msg'] = $e->getMessage();
        header('Location: ../views/manage_expenses.php');
        exit();
    }
} else {
    $_SESSION['error_msg'] = "Invalid request method";
    header('Location: ../views/manage_expenses.php');
    exit();
}
?>