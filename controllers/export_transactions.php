<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../helpers/session.php';
require_once '../config/class.php';

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    die('Unauthorized access');
}

$db = new db_class();

// Get filter parameters
$type = isset($_GET['type']) ? $_GET['type'] : 'money_in';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] . " 00:00:00" : date('Y-m-d 00:00:00');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] . " 23:59:59" : date('Y-m-d 23:59:59');
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$transaction_type = isset($_GET['transaction_type']) ? $_GET['transaction_type'] : '';

// Initialize arrays
$export_data = [];

try {
    if ($type === 'money_in') {
        // Get Group Savings with full group names
        if (empty($transaction_type) || $transaction_type === 'group_savings') {
            $query = "SELECT gs.date_saved as date, 'Group Savings' as type, 
                      COALESCE(lg.group_name, 'Unknown Group') as client, 
                      gs.amount, 
                      COALESCE(gs.payment_mode, 'N/A') as payment_mode, 
                      COALESCE(gs.receipt_no, 'N/A') as receipt_no, 
                      COALESCE(u.username, 'N/A') as served_by
                      FROM group_savings gs 
                      LEFT JOIN lato_groups lg ON gs.group_id = lg.group_id 
                      LEFT JOIN user u ON gs.served_by = u.user_id
                      WHERE gs.date_saved BETWEEN ? AND ?
                      ORDER BY gs.date_saved DESC";
            $stmt = $db->conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $export_data[] = $row;
                }
                $stmt->close();
            }
        }
        
        // Get Business Savings with full business group names
        if (empty($transaction_type) || $transaction_type === 'business_savings') {
            $query = "SELECT bgt.date, 'Business Savings' as type, 
                      COALESCE(bg.group_name, 'Unknown Business Group') as client, 
                      bgt.amount, 
                      COALESCE(bgt.payment_mode, 'N/A') as payment_mode, 
                      COALESCE(bgt.receipt_no, 'N/A') as receipt_no, 
                      COALESCE(u.username, 'N/A') as served_by
                      FROM business_group_transactions bgt
                      LEFT JOIN business_groups bg ON bgt.group_id = bg.group_id
                      LEFT JOIN user u ON bgt.served_by = u.user_id
                      WHERE bgt.type = 'Savings' AND bgt.date BETWEEN ? AND ?
                      ORDER BY bgt.date DESC";
            $stmt = $db->conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $export_data[] = $row;
                }
                $stmt->close();
            }
        }
        
        // Get Individual Savings with full client names
        if (empty($transaction_type) || $transaction_type === 'individual_savings') {
            $query = "SELECT s.date, 'Individual Savings' as type, 
                      COALESCE(CONCAT(a.first_name, ' ', a.last_name), 'Unknown Client') as client, 
                      s.amount, 
                      COALESCE(s.payment_mode, 'N/A') as payment_mode, 
                      COALESCE(s.receipt_number, 'N/A') as receipt_no, 
                      COALESCE(u.username, 'N/A') as served_by
                      FROM savings s
                      LEFT JOIN client_accounts a ON s.account_id = a.account_id
                      LEFT JOIN user u ON s.served_by = u.user_id
                      WHERE s.type = 'Savings' AND s.date BETWEEN ? AND ?
                      ORDER BY s.date DESC";
            $stmt = $db->conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $export_data[] = $row;
                }
                $stmt->close();
            }
        }
        
        // Get Loan Repayments with borrower names and loan reference
        if (empty($transaction_type) || $transaction_type === 'loan_repayments') {
            $query = "SELECT lr.date_paid as date, 'Loan Repayment' as type, 
                      CONCAT(
                          COALESCE(CONCAT(ca.first_name, ' ', ca.last_name), 'Unknown Borrower'),
                          ' [Loan: ', COALESCE(l.ref_no, 'N/A'), ']'
                      ) as client, 
                      lr.amount_repaid as amount, 
                      COALESCE(lr.payment_mode, 'N/A') as payment_mode, 
                      COALESCE(lr.receipt_number, 'N/A') as receipt_no, 
                      COALESCE(u.username, 'N/A') as served_by
                      FROM loan_repayments lr 
                      LEFT JOIN loan l ON lr.loan_id = l.loan_id 
                      LEFT JOIN client_accounts ca ON l.account_id = ca.account_id
                      LEFT JOIN user u ON lr.served_by = u.user_id
                      WHERE lr.date_paid BETWEEN ? AND ?
                      ORDER BY lr.date_paid DESC";
            $stmt = $db->conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $export_data[] = $row;
                }
                $stmt->close();
            }
        }
        
        // Get Money Received
        if (empty($transaction_type) || $transaction_type === 'money_received') {
            $query = "SELECT e.date, 'Money Received' as type, 
                      CONCAT(COALESCE(e.category, 'Income'), 
                             CASE WHEN e.description IS NOT NULL AND e.description != '' 
                                  THEN CONCAT(' - ', e.description) 
                                  ELSE '' END
                      ) as client, 
                      ABS(e.amount) as amount, 
                      COALESCE(e.payment_method, 'N/A') as payment_mode, 
                      COALESCE(e.receipt_no, 'N/A') as receipt_no, 
                      COALESCE(u.username, 'N/A') as served_by
                      FROM expenses e 
                      LEFT JOIN user u ON e.created_by = u.user_id
                      WHERE e.status = 'received' AND e.date BETWEEN ? AND ?
                      ORDER BY e.date DESC";
            $stmt = $db->conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $export_data[] = $row;
                }
                $stmt->close();
            }
        }
        
    } else if ($type === 'money_out') {
        // Get Group Withdrawals with full group names
        if (empty($transaction_type) || $transaction_type === 'group_withdrawals') {
            $query = "SELECT gw.date_withdrawn as date, 'Group Withdrawal' as type, 
                      COALESCE(lg.group_name, 'Unknown Group') as client, 
                      gw.amount, 
                      COALESCE(gw.withdrawal_fee, 0) as withdrawal_fee, 
                      COALESCE(gw.payment_mode, 'N/A') as payment_mode, 
                      COALESCE(gw.receipt_no, 'N/A') as receipt_no, 
                      COALESCE(u.username, 'N/A') as served_by
                      FROM group_withdrawals gw
                      LEFT JOIN lato_groups lg ON gw.group_id = lg.group_id
                      LEFT JOIN user u ON gw.served_by = u.user_id
                      WHERE gw.date_withdrawn BETWEEN ? AND ?
                      ORDER BY gw.date_withdrawn DESC";
            $stmt = $db->conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $export_data[] = $row;
                }
                $stmt->close();
            }
        }
        
        // Get Business Withdrawals with full business group names
        if (empty($transaction_type) || $transaction_type === 'business_withdrawals') {
            $query = "SELECT bgt.date, 'Business Withdrawal' as type, 
                      COALESCE(bg.group_name, 'Unknown Business Group') as client, 
                      bgt.amount, 
                      0 as withdrawal_fee,
                      COALESCE(bgt.payment_mode, 'N/A') as payment_mode, 
                      COALESCE(bgt.receipt_no, 'N/A') as receipt_no, 
                      COALESCE(u.username, 'N/A') as served_by,
                      bgt.group_id
                      FROM business_group_transactions bgt
                      LEFT JOIN business_groups bg ON bgt.group_id = bg.group_id
                      LEFT JOIN user u ON bgt.served_by = u.user_id
                      WHERE bgt.type = 'Withdrawal' AND bgt.date BETWEEN ? AND ?
                      ORDER BY bgt.date DESC";
            $stmt = $db->conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    // Try to get withdrawal fee from separate record
                    $fee_query = "SELECT amount FROM business_group_transactions 
                                 WHERE type = 'Withdrawal Fee' 
                                 AND group_id = ? 
                                 AND DATE(date) = DATE(?)
                                 LIMIT 1";
                    $fee_stmt = $db->conn->prepare($fee_query);
                    if ($fee_stmt && isset($row['group_id'])) {
                        $group_id = $row['group_id'];
                        $date = $row['date'];
                        $fee_stmt->bind_param("is", $group_id, $date);
                        $fee_stmt->execute();
                        $fee_result = $fee_stmt->get_result();
                        if ($fee_row = $fee_result->fetch_assoc()) {
                            $row['withdrawal_fee'] = $fee_row['amount'];
                        }
                        $fee_stmt->close();
                    }
                    unset($row['group_id']); // Remove group_id from export
                    $export_data[] = $row;
                }
                $stmt->close();
            }
        }
        
        // Get Individual Withdrawals with full client names
        if (empty($transaction_type) || $transaction_type === 'individual_withdrawals') {
            $query = "SELECT s.date, 'Individual Withdrawal' as type, 
                      COALESCE(CONCAT(a.first_name, ' ', a.last_name), 'Unknown Client') as client, 
                      s.amount, 
                      COALESCE(s.withdrawal_fee, 0) as withdrawal_fee, 
                      COALESCE(s.payment_mode, 'N/A') as payment_mode, 
                      COALESCE(s.receipt_number, 'N/A') as receipt_no, 
                      COALESCE(u.username, 'N/A') as served_by
                      FROM savings s
                      LEFT JOIN client_accounts a ON s.account_id = a.account_id
                      LEFT JOIN user u ON s.served_by = u.user_id
                      WHERE s.type = 'Withdrawal' AND s.date BETWEEN ? AND ?
                      ORDER BY s.date DESC";
            $stmt = $db->conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $export_data[] = $row;
                }
                $stmt->close();
            }
        }
        
        // Get Loan Disbursements with borrower names and loan reference
        if (empty($transaction_type) || $transaction_type === 'loan_disbursements') {
            $query = "SELECT p.date_created as date, 'Loan Disbursement' as type, 
                      CONCAT(
                          COALESCE(CONCAT(ca.first_name, ' ', ca.last_name), p.payee, 'Unknown Borrower'),
                          ' [Loan: ', COALESCE(l.ref_no, 'N/A'), ']'
                      ) as client, 
                      p.pay_amount as amount, 
                      COALESCE(p.withdrawal_fee, 0) as withdrawal_fee, 
                      'Bank Transfer' as payment_mode, 
                      COALESCE(p.receipt_no, 'N/A') as receipt_no, 
                      COALESCE(u.username, 'N/A') as served_by
                      FROM payment p 
                      LEFT JOIN loan l ON p.loan_id = l.loan_id
                      LEFT JOIN client_accounts ca ON l.account_id = ca.account_id
                      LEFT JOIN user u ON p.user_id = u.user_id
                      WHERE p.date_created BETWEEN ? AND ?
                      ORDER BY p.date_created DESC";
            $stmt = $db->conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $export_data[] = $row;
                }
                $stmt->close();
            }
        }
        
        // Get Expenses
        if (empty($transaction_type) || $transaction_type === 'expenses') {
            $query = "SELECT e.date, 'Expense' as type, 
                      CONCAT(
                          COALESCE(e.category, 'N/A'),
                          CASE WHEN e.description IS NOT NULL AND e.description != '' 
                               THEN CONCAT(' - ', e.description) 
                               ELSE '' END
                      ) as client, 
                      ABS(e.amount) as amount, 
                      0 as withdrawal_fee,
                      COALESCE(e.payment_method, 'N/A') as payment_mode, 
                      COALESCE(e.receipt_no, 'N/A') as receipt_no, 
                      COALESCE(u.username, 'N/A') as served_by
                      FROM expenses e 
                      LEFT JOIN user u ON e.created_by = u.user_id
                      WHERE (e.status = 'completed' OR e.status IS NULL) AND e.date BETWEEN ? AND ?
                      ORDER BY e.date DESC";
            $stmt = $db->conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $export_data[] = $row;
                }
                $stmt->close();
            }
        }
    }

    // Apply search filter if provided
    if (!empty($search_term)) {
        $export_data = array_filter($export_data, function($row) use ($search_term) {
            return (
                stripos($row['client'], $search_term) !== false ||
                stripos($row['receipt_no'], $search_term) !== false ||
                stripos($row['served_by'], $search_term) !== false ||
                stripos($row['type'], $search_term) !== false
            );
        });
    }

    // Sort by date descending
    usort($export_data, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    // Calculate totals
    $total_amount = 0;
    $total_fees = 0;

    foreach ($export_data as $row) {
        $total_amount += floatval($row['amount']);
        if ($type === 'money_out' && isset($row['withdrawal_fee'])) {
            $total_fees += floatval($row['withdrawal_fee']);
        }
    }

    // Generate filename
    $filename = ($type === 'money_in' ? 'Money_In' : 'Money_Out') . '_Transactions_' . date('Y-m-d_His') . '.xls';

    // Set headers for Excel download
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Output Excel content
    echo "LATO SACCO LTD\n";
    echo ($type === 'money_in' ? 'Money In' : 'Money Out') . " Transactions Report\n";
    echo "Period: " . date('M d, Y', strtotime($start_date)) . " to " . date('M d, Y', strtotime($end_date)) . "\n";
    echo "Generated on: " . date('M d, Y H:i:s') . "\n";
    echo "Generated by: " . ($_SESSION['username'] ?? 'System') . "\n";
    echo "\n";

    // Summary
    echo "SUMMARY\n";
    echo "Total Transactions\t" . count($export_data) . "\n";
    echo "Total Amount (KSh)\t" . number_format($total_amount, 2) . "\n";
    if ($type === 'money_out') {
        echo "Total Fees (KSh)\t" . number_format($total_fees, 2) . "\n";
    }
    echo "\n";

    // Table headers
    echo "Date\t";
    echo "Type\t";
    echo "Client/Group Name\t";
    echo "Amount (KSh)\t";
    if ($type === 'money_out') {
        echo "Withdrawal Fee (KSh)\t";
    }
    echo "Payment Mode\t";
    echo "Receipt No\t";
    echo "Served By\n";

    // Data rows
    foreach ($export_data as $row) {
        echo date('Y-m-d', strtotime($row['date'])) . "\t";
        echo ($row['type'] ?? 'N/A') . "\t";
        echo ($row['client'] ?? 'N/A') . "\t";
        echo number_format($row['amount'], 2) . "\t";
        if ($type === 'money_out') {
            echo number_format($row['withdrawal_fee'] ?? 0, 2) . "\t";
        }
        echo ($row['payment_mode'] ?? 'N/A') . "\t";
        echo ($row['receipt_no'] ?? 'N/A') . "\t";
        echo ($row['served_by'] ?? 'N/A') . "\n";
    }

    // Total row
    echo "\n";
    echo "TOTAL\t\t\t";
    echo number_format($total_amount, 2) . "\t";
    if ($type === 'money_out') {
        echo number_format($total_fees, 2) . "\t";
    }
    echo "\n";

} catch (Exception $e) {
    // Log the error
    error_log("Export error: " . $e->getMessage());
    
    // Send error message
    header("Content-Type: text/plain");
    echo "Error generating export: " . $e->getMessage() . "\n";
    echo "Please contact system administrator.";
}

exit();
?>