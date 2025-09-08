<?php
require_once '../config/class.php';
require_once '../helpers/session.php';


class BusinessGroupController {
    private $db;


public function __construct() {
    $this->db = new db_class();
    if (!$this->db->conn) {
        error_log("Database connection failed: " . mysqli_connect_error());
    }
}

    // Create new business group
    private function validateReferenceFormat($reference) {
        if (empty($reference)) return true;
        return preg_match('/^BG-\d{3}$/', $reference);
    }

    // Add this method to check if a reference name is already in use
    private function isReferenceNameUnique($reference, $excludeGroupId = null) {
        $query = "SELECT COUNT(*) as count FROM business_groups WHERE reference_name = ?";
        $params = [$reference];
        $types = "s";

        if ($excludeGroupId) {
            $query .= " AND group_id != ?";
            $params[] = $excludeGroupId;
            $types .= "i";
        }

        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] === 0;
    }

    // Update the createBusinessGroup method to handle reference_name
    public function createBusinessGroup($data) {
        try {
            // Validate required fields
            $required_fields = [
                'group_name',
                'chairperson_name', 'chairperson_id_number', 'chairperson_phone',
                'secretary_name', 'secretary_id_number', 'secretary_phone',
                'treasurer_name', 'treasurer_id_number', 'treasurer_phone'
            ];

            foreach ($required_fields as $field) {
                if (empty(trim($data[$field]))) {
                    throw new Exception("Field '$field' is required");
                }
            }

            // Validate reference name if provided
            if (!empty($data['reference_name'])) {
                if (!$this->validateReferenceFormat($data['reference_name'])) {
                    throw new Exception("Invalid reference name format. Use format BG-XXX (e.g., BG-001)");
                }
                if (!$this->isReferenceNameUnique($data['reference_name'])) {
                    throw new Exception("Reference name already in use");
                }
            }

            // Create the business group
            $groupQuery = "INSERT INTO business_groups (
                group_name,
                reference_name,
                chairperson_name,
                chairperson_id_number,
                chairperson_phone,
                secretary_name,
                secretary_id_number,
                secretary_phone,
                treasurer_name,
                treasurer_id_number,
                treasurer_phone
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->conn->prepare($groupQuery);
            $stmt->bind_param("sssssssssss",
                $data['group_name'],
                $data['reference_name'],
                $data['chairperson_name'],
                $data['chairperson_id_number'],
                $data['chairperson_phone'],
                $data['secretary_name'],
                $data['secretary_id_number'],
                $data['secretary_phone'],
                $data['treasurer_name'],
                $data['treasurer_id_number'],
                $data['treasurer_phone']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error creating business group: " . $stmt->error);
            }

            return array(
                'status' => 'success',
                'message' => 'Business group created successfully',
                'group_id' => $this->db->conn->insert_id
            );

        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => $e->getMessage()
            );
        }
    }

    // Update the updateBusinessGroup method to handle reference_name
    public function updateBusinessGroup($data) {
        try {
            // Validate reference name if provided
            if (!empty($data['reference_name'])) {
                if (!$this->validateReferenceFormat($data['reference_name'])) {
                    throw new Exception("Invalid reference name format. Use format BG-XXX (e.g., BG-001)");
                }
                if (!$this->isReferenceNameUnique($data['reference_name'], $data['group_id'])) {
                    throw new Exception("Reference name already in use");
                }
            }

            $query = "UPDATE business_groups SET 
                group_name = ?,
                reference_name = ?,
                chairperson_name = ?,
                chairperson_id_number = ?,
                chairperson_phone = ?,
                secretary_name = ?,
                secretary_id_number = ?,
                secretary_phone = ?,
                treasurer_name = ?,
                treasurer_id_number = ?,
                treasurer_phone = ?
                WHERE group_id = ?";
            
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("sssssssssssi",
                $data['group_name'],
                $data['reference_name'],
                $data['chairperson_name'],
                $data['chairperson_id_number'],
                $data['chairperson_phone'],
                $data['secretary_name'],
                $data['secretary_id_number'],
                $data['secretary_phone'],
                $data['treasurer_name'],
                $data['treasurer_id_number'],
                $data['treasurer_phone'],
                $data['group_id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating business group: " . $stmt->error);
            }

            return array(
                'status' => 'success',
                'message' => 'Business group updated successfully'
            );

        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => $e->getMessage()
            );
        }
    }

    // Update the getNextReferenceNumber method
    public function getNextReferenceNumber() {
        try {
            $query = "SELECT reference_name 
                     FROM business_groups 
                     WHERE reference_name REGEXP '^BG-[0-9]{3}$'
                     ORDER BY CAST(SUBSTRING(reference_name, 4) AS UNSIGNED) DESC 
                     LIMIT 1";
            
            $result = $this->db->conn->query($query);
            
            if ($result && $row = $result->fetch_assoc()) {
                $currentNumber = intval(substr($row['reference_name'], 3));
                $nextNumber = $currentNumber + 1;
            } else {
                $nextNumber = 1;
            }
            
            $nextReference = sprintf("BG-%03d", $nextNumber);
            
            return array(
                'status' => 'success',
                'reference' => $nextReference
            );
        } catch (Exception $e) {
            error_log("Error generating next reference number: " . $e->getMessage());
            return array(
                'status' => 'error',
                'message' => 'Error generating reference number'
            );
        }
    }





    // Get business group by ID
    public function getBusinessGroupById($groupId) {
        $query = "SELECT * FROM business_groups WHERE group_id = ?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("i", $groupId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }




    // Delete business group
    public function deleteBusinessGroup($groupId) {
        try {
            // Start transaction
            $this->db->conn->begin_transaction();
    
            // First check if there are any transactions
            $checkQuery = "SELECT COUNT(*) as count FROM business_group_transactions WHERE group_id = ?";
            $stmt = $this->db->conn->prepare($checkQuery);
            $stmt->bind_param("i", $groupId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                // Get the group details for the error message
                $groupQuery = "SELECT group_name, reference_name FROM business_groups WHERE group_id = ?";
                $stmt = $this->db->conn->prepare($groupQuery);
                $stmt->bind_param("i", $groupId);
                $stmt->execute();
                $groupDetails = $stmt->get_result()->fetch_assoc();
                
                $groupIdentifier = $groupDetails['reference_name'] 
                    ? $groupDetails['reference_name'] . ' (' . $groupDetails['group_name'] . ')'
                    : $groupDetails['group_name'];
    
                throw new Exception("Cannot delete group '$groupIdentifier' because it has existing transactions. Please archive the group instead or contact the administrator for assistance.");
            }
    
            // If no transactions exist, proceed with deletion
            $deleteQuery = "DELETE FROM business_groups WHERE group_id = ?";
            $stmt = $this->db->conn->prepare($deleteQuery);
            $stmt->bind_param("i", $groupId);
            
            if (!$stmt->execute()) {
                throw new Exception("Error deleting business group: " . $stmt->error);
            }
    
            $this->db->conn->commit();
            
            return array(
                'status' => 'success',
                'message' => 'Business group deleted successfully'
            );
    
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return array(
                'status' => 'error',
                'message' => $e->getMessage()
            );
        }
    }





    // Add savings
    public function addSavings($data) {
        try {
            // Start transaction
            $this->db->conn->begin_transaction();

            // Insert into business_group_transactions
            $query = "INSERT INTO business_group_transactions (
                group_id, type, amount, description, receipt_no, payment_mode, served_by
            ) VALUES (?, 'Savings', ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->conn->prepare($query);
            $description = "Business group savings deposit";
            $stmt->bind_param("idsssi",
                $data['group_id'],
                $data['amount'],
                $description,
                $data['receipt_no'],
                $data['payment_mode'],
                $data['served_by']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error recording transaction: " . $stmt->error);
            }

            $this->db->conn->commit();
            return array('status' => 'success', 'message' => 'Savings added successfully');
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return array('status' => 'error', 'message' => $e->getMessage());
        }
    }

    public function processWithdrawal($data) {
        try {
            // Check available balance
            $balanceQuery = "SELECT 
                (COALESCE(
                    (SELECT SUM(amount) FROM business_group_transactions 
                    WHERE group_id = ? AND type = 'Savings'), 0
                ) -
                COALESCE(
                    (SELECT SUM(amount) FROM business_group_transactions 
                    WHERE group_id = ? AND type = 'Withdrawal'), 0
                )) as balance";
            
            $stmt = $this->db->conn->prepare($balanceQuery);
            $stmt->bind_param("ii", $data['group_id'], $data['group_id']);
            $stmt->execute();
            $balance = $stmt->get_result()->fetch_assoc()['balance'];

            if ($balance < $data['amount']) {
                return array('status' => 'error', 'message' => 'Insufficient balance');
            }

            // Start transaction
            $this->db->conn->begin_transaction();

            // Insert withdrawal transaction
            $query = "INSERT INTO business_group_transactions (
                group_id, type, amount, description, receipt_no, payment_mode, served_by
            ) VALUES (?, 'Withdrawal', ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->conn->prepare($query);
            $description = "Business group withdrawal";
            $stmt->bind_param("idsssi",
                $data['group_id'],
                $data['amount'],
                $description,
                $data['receipt_no'],
                $data['payment_mode'],
                $data['served_by']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error recording withdrawal: " . $stmt->error);
            }

            // Insert withdrawal fee if applicable
            if ($data['withdrawal_fee'] > 0) {
                $feeQuery = "INSERT INTO business_group_transactions (
                    group_id, type, amount, description, receipt_no, payment_mode, served_by
                ) VALUES (?, 'Withdrawal Fee', ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->conn->prepare($feeQuery);
                $feeDescription = "Withdrawal fee";
                $feeReceiptNo = $data['receipt_no'] . '-FEE';
                $stmt->bind_param("idsssi",
                    $data['group_id'],
                    $data['withdrawal_fee'],
                    $feeDescription,
                    $feeReceiptNo,
                    $data['payment_mode'],
                    $data['served_by']
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Error recording withdrawal fee: " . $stmt->error);
                }
            }

            $this->db->conn->commit();
            return array('status' => 'success', 'message' => 'Withdrawal processed successfully');
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return array('status' => 'error', 'message' => $e->getMessage());
        }
    }

    public function getTransactions($groupId) {
        $query = "SELECT t.*, u.firstname, u.lastname, g.group_name
                 FROM business_group_transactions t
                 JOIN user u ON t.served_by = u.user_id
                 JOIN business_groups g ON t.group_id = g.group_id
                 WHERE t.group_id = ?
                 ORDER BY t.date DESC";
        
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("i", $groupId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

// Update these methods in your BusinessGroupController class

public function getTotals($groupId) {
    $query = "SELECT 
        COALESCE(SUM(CASE WHEN type = 'Savings' THEN amount ELSE 0 END), 0) as total_deposits,
        COALESCE(SUM(CASE WHEN type IN ('Withdrawal', 'Withdrawal Fee') THEN amount ELSE 0 END), 0) as total_withdrawals,
        COALESCE(SUM(CASE WHEN type = 'Withdrawal Fee' THEN amount ELSE 0 END), 0) as total_fees
    FROM business_group_transactions 
    WHERE group_id = ?";
    
    $stmt = $this->db->conn->prepare($query);
    $stmt->bind_param("i", $groupId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    // Calculate net balance by subtracting both withdrawals and fees from deposits
    $result['net_balance'] = $result['total_deposits'] - $result['total_withdrawals'];
    return $result;
}


public function getReceiptDetails($transactionId) {
    $query = "SELECT t.*, 
              u.firstname, u.lastname,
              g.group_name,
              (SELECT amount FROM business_group_transactions 
               WHERE receipt_no = CONCAT(t.receipt_no, '-FEE') 
               AND type = 'Withdrawal Fee' 
               LIMIT 1) as withdrawal_fee
              FROM business_group_transactions t
              JOIN user u ON t.served_by = u.user_id
              JOIN business_groups g ON t.group_id = g.group_id
              WHERE t.transaction_id = ?";
    
    $stmt = $this->db->conn->prepare($query);
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $result['served_by_name'] = $result['firstname'] . ' ' . $result['lastname'];
    }
    
    return $result;
}

    public function checkReceiptNumber($receiptNo) {
        $query = "SELECT COUNT(*) as count FROM business_group_transactions WHERE receipt_no = ?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("s", $receiptNo);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
    }



    // Get total deposits
    public function getTotalDeposits($groupId) {
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM business_group_savings WHERE group_id = ?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("i", $groupId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }

    // Get total withdrawals
    public function getTotalWithdrawals($groupId) {
        $query = "SELECT COALESCE(SUM(amount), 0) as withdrawals, 
                         COALESCE(SUM(withdrawal_fee), 0) as fees 
                 FROM business_group_withdrawals WHERE group_id = ?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("i", $groupId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }



    //statements
    public function getStatementData($groupId, $fromDate, $toDate) {
    try {
        // Get group details
        $groupDetails = $this->getBusinessGroupById($groupId);
        if (!$groupDetails) {
            throw new Exception("Business group not found.");
        }

        // Get transactions for the specified period
        $sql = "SELECT t.*, 
                CONCAT(u.firstname, ' ', u.lastname) as served_by_name,
                CASE 
                    WHEN t.type = 'Withdrawal Fee' THEN 
                        (SELECT amount FROM business_group_transactions 
                         WHERE receipt_no = SUBSTRING_INDEX(t.receipt_no, '-FEE', 1)
                         AND type = 'Withdrawal'
                         LIMIT 1)
                    ELSE NULL 
                END as related_withdrawal_amount
                FROM business_group_transactions t 
                LEFT JOIN user u ON t.served_by = u.user_id 
                WHERE t.group_id = ? 
                AND DATE(t.date) BETWEEN ? AND ? 
                ORDER BY t.date DESC";
        
        $stmt = $this->db->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->conn->error);
        }

        $stmt->bind_param("iss", $groupId, $fromDate, $toDate);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            // Keep amount as number for JavaScript calculations
            $row['amount'] = floatval($row['amount']); // Convert to float, not formatted string
            
            // Format date properly
            $row['date'] = date('Y-m-d H:i:s', strtotime($row['date']));
            
            // Add description enhancement for withdrawal fees
            if ($row['type'] === 'Withdrawal Fee' && $row['related_withdrawal_amount']) {
                $row['description'] .= ' (for withdrawal of KSh ' . number_format($row['related_withdrawal_amount'], 2) . ')';
            }
            
            // Remove helper field
            unset($row['related_withdrawal_amount']);
            $transactions[] = $row;
        }

        // Get totals for the filtered period only
        $totalsSql = "SELECT 
            COALESCE(SUM(CASE WHEN type = 'Savings' THEN amount ELSE 0 END), 0) as total_deposits,
            COALESCE(SUM(CASE WHEN type = 'Withdrawal' THEN amount ELSE 0 END), 0) as total_withdrawals,
            COALESCE(SUM(CASE WHEN type = 'Withdrawal Fee' THEN amount ELSE 0 END), 0) as total_fees
        FROM business_group_transactions 
        WHERE group_id = ? 
        AND DATE(date) BETWEEN ? AND ?";
        
        $stmt = $this->db->conn->prepare($totalsSql);
        $stmt->bind_param("iss", $groupId, $fromDate, $toDate);
        $stmt->execute();
        $totals = $stmt->get_result()->fetch_assoc();

        // Keep totals as numbers for JavaScript
        $totals['net_balance'] = $totals['total_deposits'] - $totals['total_withdrawals'] - $totals['total_fees'];

        return [
            'status' => 'success',
            'data' => [
                'group_details' => $groupDetails,
                'transactions' => $transactions,
                'summary' => [
                    // Keep as numbers, not formatted strings
                    'total_deposits' => floatval($totals['total_deposits']),
                    'total_withdrawals' => floatval($totals['total_withdrawals']),
                    'total_fees' => floatval($totals['total_fees']),
                    'net_balance' => floatval($totals['net_balance'])
                ]
            ]
        ];
    } catch (Exception $e) {
        error_log("Error generating statement: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}



}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $controller = new BusinessGroupController();
    $response = array('status' => 'error', 'message' => 'Invalid action');

    switch ($_POST['action']) {
        case 'create':
            // Validate required fields
            $required_fields = [
                'group_name',
                'chairperson_name', 'chairperson_id_number', 'chairperson_phone',
                'secretary_name', 'secretary_id_number', 'secretary_phone',
                'treasurer_name', 'treasurer_id_number', 'treasurer_phone'
            ];

            $missing_fields = array();
            foreach ($required_fields as $field) {
                if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                    $missing_fields[] = $field;
                }
            }

            if (!empty($missing_fields)) {
                $response = array(
                    'status' => 'error',
                    'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
                );
            } else {
                $response = $controller->createBusinessGroup($_POST);
            }
            break;

        case 'get':
            if (isset($_POST['group_id'])) {
                $group = $controller->getBusinessGroupById($_POST['group_id']);
                if ($group) {
                    $response = array('status' => 'success', 'data' => $group);
                } else {
                    $response = array('status' => 'error', 'message' => 'Group not found');
                }
            }
            break;

        case 'update':
            if (isset($_POST['group_id'])) {
                $response = $controller->updateBusinessGroup($_POST);
            }
            break;

        case 'delete':
            if (isset($_POST['group_id'])) {
                $response = $controller->deleteBusinessGroup($_POST['group_id']);
            }
            break;

            case 'getNextReference':
                $result = $controller->getNextReferenceNumber();
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
                break;

        case 'addSavings':
            if (isset($_POST['group_id'], $_POST['amount'], $_POST['receipt_no'], $_POST['payment_mode'], $_POST['served_by'])) {
                $response = $controller->addSavings($_POST);
            } else {
                $response = array('status' => 'error', 'message' => 'Missing required fields for savings');
            }
            break;

        case 'withdraw':
            if (isset($_POST['group_id'], $_POST['amount'], $_POST['withdrawal_fee'], $_POST['receipt_no'], $_POST['payment_mode'], $_POST['served_by'])) {
                $response = $controller->processWithdrawal($_POST);
            } else {
                $response = array('status' => 'error', 'message' => 'Missing required fields for withdrawal');
            }
            break;

        case 'checkReceiptNo':
            if (isset($_POST['receipt_no'], $_POST['type'])) {
                $exists = $controller->checkReceiptNumber($_POST['receipt_no'], $_POST['type']);
                $response = array(
                    'status' => $exists ? 'error' : 'success',
                    'message' => $exists ? 'Receipt number already exists' : 'Receipt number is available'
                );
            }
            break;

            case 'getReceiptDetails':
                if (isset($_POST['transaction_id'])) {
                    $receiptDetails = $controller->getReceiptDetails($_POST['transaction_id']);
                    if ($receiptDetails) {
                        $response = array('status' => 'success', 'data' => $receiptDetails);
                    } else {
                        $response = array('status' => 'error', 'message' => 'Receipt details not found');
                    }
                }
                break;

                    
case 'getStatementData':
    if (isset($_POST['group_id']) && isset($_POST['from_date']) && isset($_POST['to_date'])) {
        try {
            // Validate dates
            $fromDate = DateTime::createFromFormat('Y-m-d', $_POST['from_date']);
            $toDate = DateTime::createFromFormat('Y-m-d', $_POST['to_date']);
            
            if (!$fromDate || !$toDate) {
                throw new Exception('Invalid date format');
            }
            
            if ($fromDate > $toDate) {
                throw new Exception('Start date cannot be after end date');
            }

            $response = $controller->getStatementData(
                $_POST['group_id'],
                $_POST['from_date'],
                $_POST['to_date']
            );
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'message' => $e->getMessage()
            );
        }
    } else {
        $response = array(
            'status' => 'error',
            'message' => 'Missing required parameters: group_id, from_date, and/or to_date'
        );
    }
    break;
                
    }

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}


?>