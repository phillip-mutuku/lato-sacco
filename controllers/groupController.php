<?php
require_once '../config/class.php';
require_once '../helpers/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

class GroupController {
    private $db;

    public function __construct() {
        $this->db = new db_class();
    }

    // Private helper methods
    private function sanitizeInput($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    private function validateAmount($amount) {
        return is_numeric($amount) && $amount > 0;
    }

    private function validateGroupData($data) {
        $errors = [];
        
        if (empty($data['group_reference'])) {
            $errors[] = "Group reference is required";
        }
        
        if (empty($data['group_name'])) {
            $errors[] = "Group name is required";
        }
        
        if (empty($data['area'])) {
            $errors[] = "Area is required";
        }
        
        if (empty($data['field_officer_id'])) {
            $errors[] = "Field officer is required";
        }
        
        return $errors;
    }

    private function validateMemberStatus($groupId, $accountId) {
        try {
            $query = "SELECT status FROM group_members 
                     WHERE group_id = ? AND account_id = ?";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("ii", $groupId, $accountId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                return $row['status'] === 'active';
            }
            return false;
        } catch (Exception $e) {
            error_log("Error in validateMemberStatus: " . $e->getMessage());
            return false;
        }
    }



         // Get field officers
    public function getFieldOfficers() {
        try {
            $query = "SELECT user_id, firstname, lastname FROM user WHERE role = 'officer' ORDER BY firstname, lastname";
            $result = $this->db->conn->query($query);
            $officers = [];
            
            while ($row = $result->fetch_assoc()) {
                $officers[] = [
                    'id' => $row['user_id'],
                    'name' => $row['firstname'] . ' ' . $row['lastname']
                ];
            }
            
            return json_encode(['status' => 'success', 'data' => $officers]);
        } catch (Exception $e) {
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Create new group
    public function createGroup($data) {
        try {
            // Validate input data
            $errors = $this->validateGroupData($data);
            if (!empty($errors)) {
                return json_encode(['status' => 'error', 'message' => implode(", ", $errors)]);
            }

            // Sanitize inputs
            $group_reference = $this->sanitizeInput($data['group_reference']);
            $group_name = $this->sanitizeInput($data['group_name']);
            $area = $this->sanitizeInput($data['area']);
            $field_officer_id = (int)$data['field_officer_id'];

            // Verify officer exists and has correct role
            $officer_query = "SELECT user_id FROM user WHERE user_id = ? AND role = 'officer'";
            $stmt = $this->db->conn->prepare($officer_query);
            $stmt->bind_param("i", $field_officer_id);
            $stmt->execute();
            $officer_result = $stmt->get_result();

            if ($officer_result->num_rows === 0) {
                return json_encode(['status' => 'error', 'message' => 'Selected user is not a field officer']);
            }

            // Begin transaction
            $this->db->conn->begin_transaction();

            // Check if group reference already exists
            $check_query = "SELECT group_id FROM lato_groups WHERE group_reference = ?";
            $check_stmt = $this->db->conn->prepare($check_query);
            $check_stmt->bind_param("s", $group_reference);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                throw new Exception("Group reference already exists");
            }

            // Insert new group
            $insert_query = "INSERT INTO lato_groups (group_reference, group_name, area, field_officer_id) 
                           VALUES (?, ?, ?, ?)";
            $stmt = $this->db->conn->prepare($insert_query);
            $stmt->bind_param("sssi", $group_reference, $group_name, $area, $field_officer_id);
            
            if ($stmt->execute()) {
                $this->db->conn->commit();
                return json_encode(['status' => 'success', 'message' => 'Group created successfully']);
            } else {
                throw new Exception("Error creating group");
            }
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Get group details
    public function getGroup($group_id) {
        try {
            $query = "SELECT g.*, u.firstname, u.lastname 
                     FROM lato_groups g 
                     JOIN user u ON g.field_officer_id = u.user_id 
                     WHERE g.group_id = ? AND u.role = 'officer'";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("i", $group_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $group = $result->fetch_assoc();
                return json_encode(['status' => 'success', 'data' => $group]);
            } else {
                throw new Exception("Group not found");
            }
        } catch (Exception $e) {
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Update group
    public function updateGroup($data) {
        try {
            // Validate input data
            $errors = $this->validateGroupData($data);
            if (!empty($errors)) {
                return json_encode(['status' => 'error', 'message' => implode(", ", $errors)]);
            }

            // Sanitize inputs
            $group_id = (int)$data['group_id'];
            $group_reference = $this->sanitizeInput($data['group_reference']);
            $group_name = $this->sanitizeInput($data['group_name']);
            $area = $this->sanitizeInput($data['area']);
            $field_officer_id = (int)$data['field_officer_id'];

            // Begin transaction
            $this->db->conn->begin_transaction();

            // Verify officer
            $officer_query = "SELECT user_id FROM user WHERE user_id = ? AND role = 'officer'";
            $stmt = $this->db->conn->prepare($officer_query);
            $stmt->bind_param("i", $field_officer_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception('Selected user is not a field officer');
            }

            // Check if reference exists for other groups
            $check_query = "SELECT group_id FROM lato_groups WHERE group_reference = ? AND group_id != ?";
            $check_stmt = $this->db->conn->prepare($check_query);
            $check_stmt->bind_param("si", $group_reference, $group_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                throw new Exception("Group reference already exists");
            }

            // Update group
            $update_query = "UPDATE lato_groups 
                           SET group_reference = ?, group_name = ?, area = ?, field_officer_id = ? 
                           WHERE group_id = ?";
            $stmt = $this->db->conn->prepare($update_query);
            $stmt->bind_param("sssii", $group_reference, $group_name, $area, $field_officer_id, $group_id);
            
            if ($stmt->execute()) {
                $this->db->conn->commit();
                return json_encode(['status' => 'success', 'message' => 'Group updated successfully']);
            } else {
                throw new Exception("Error updating group");
            }
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Delete group
    public function deleteGroup($group_id) {
        try {
            // Begin transaction
            $this->db->conn->begin_transaction();

            // Check if group exists and has no dependent records
            $check_query = "SELECT group_id FROM lato_groups WHERE group_id = ?";
            $stmt = $this->db->conn->prepare($check_query);
            $stmt->bind_param("i", $group_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Group not found");
            }

            // Delete group
            $delete_query = "DELETE FROM lato_groups WHERE group_id = ?";
            $stmt = $this->db->conn->prepare($delete_query);
            $stmt->bind_param("i", $group_id);
            
            if ($stmt->execute()) {
                $this->db->conn->commit();
                return json_encode(['status' => 'success', 'message' => 'Group deleted successfully']);
            } else {
                throw new Exception("Error deleting group");
            }
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }



    // Get group by ID with field officer details
    function getGroupById($groupId) {
        try {
            $query = "SELECT g.*, u.firstname, u.lastname 
                     FROM lato_groups g 
                     JOIN user u ON g.field_officer_id = u.user_id 
                     WHERE g.group_id = ?";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("i", $groupId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            return null;
        } catch (Exception $e) {
            error_log("Error in getGroupById: " . $e->getMessage());
            return null;
        }
    }

    // Get group members with their savings details
     function getGroupMembers($groupId) {
        try {
            $query = "SELECT m.*, a.*, 
                     (SELECT COALESCE(SUM(amount), 0) FROM group_savings 
                      WHERE group_id = m.group_id AND account_id = m.account_id) -
                     (SELECT COALESCE(SUM(amount), 0) FROM group_withdrawals 
                      WHERE group_id = m.group_id AND account_id = m.account_id) as total_savings
                     FROM group_members m 
                     JOIN client_accounts a ON m.account_id = a.account_id 
                     WHERE m.group_id = ? AND m.status = 'active'
                     ORDER BY a.first_name, a.last_name";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("i", $groupId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getGroupMembers: " . $e->getMessage());
            return [];
        }
    }

    // Get transactions for the group
    public function getGroupTransactions($groupId) {
        try {
            $query = "SELECT 
                t.transaction_id,
                t.date,
                CONCAT(a.first_name, ' ', a.last_name) as member_name,
                t.type,
                t.amount,
                t.receipt_no,
                t.description,
                CONCAT(u.firstname, ' ', u.lastname) as served_by_name
            FROM transactions t
            JOIN client_accounts a ON t.account_id = a.account_id
            JOIN group_members m ON a.account_id = m.account_id
            LEFT JOIN user u ON t.served_by = u.user_id
            WHERE m.group_id = ? 
            AND m.status = 'active'
            AND t.group_id = ? 
            ORDER BY t.date DESC";
    
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("ii", $groupId, $groupId); 
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getGroupTransactions: " . $e->getMessage());
            return [];
        }
    }


    ///print statement
    public function getStatementData($groupId, $fromDate, $toDate) {
        try {
            // Get group details
            $groupDetails = $this->getGroupById($groupId);
            if (!$groupDetails) {
                throw new Exception("Group not found.");
            }
    
            // Get transactions for the specified period
            $sql = "SELECT 
                    t.*,
                    m.first_name, 
                    m.last_name,
                    CONCAT(m.first_name, ' ', m.last_name) as member_name,
                    CONCAT(u.firstname, ' ', u.lastname) as served_by_name,
                    CASE
                        WHEN t.type = 'Savings' THEN CONCAT('Group savings deposit - ', t.receipt_no)
                        ELSE CONCAT('Group withdrawal - ', t.receipt_no)
                    END as description
                FROM (
                    SELECT 
                        'Savings' as type,
                        date_saved as date,
                        amount,
                        receipt_no,
                        account_id,
                        served_by,
                        payment_mode
                    FROM group_savings
                    WHERE group_id = ? AND DATE(date_saved) BETWEEN ? AND ?
                    
                    UNION ALL
                    
                    SELECT 
                        'Withdrawal' as type,
                        date_withdrawn as date,
                        amount,
                        receipt_no,
                        account_id,
                        served_by,
                        payment_mode
                    FROM group_withdrawals
                    WHERE group_id = ? AND DATE(date_withdrawn) BETWEEN ? AND ?
                ) t
                JOIN client_accounts m ON t.account_id = m.account_id
                JOIN user u ON t.served_by = u.user_id
                ORDER BY t.date DESC";
    
            $stmt = $this->db->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->conn->error);
            }
    
            $stmt->bind_param("isssss", $groupId, $fromDate, $toDate, $groupId, $fromDate, $toDate);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
    
            $result = $stmt->get_result();
            
            $transactions = [];
            $totalSavings = 0;
            $totalWithdrawals = 0;
    
            while ($row = $result->fetch_assoc()) {
                // Ensure numeric amount
                $row['amount'] = floatval($row['amount']);
                
                // Calculate totals
                if ($row['type'] === 'Savings') {
                    $totalSavings += $row['amount'];
                } else {
                    $totalWithdrawals += $row['amount'];
                }
    
                $transactions[] = [
                    'date' => $row['date'],
                    'member_name' => $row['member_name'],
                    'type' => $row['type'],
                    'amount' => $row['amount'],
                    'receipt_no' => $row['receipt_no'],
                    'payment_mode' => $row['payment_mode'],
                    'description' => $row['description'],
                    'served_by_name' => $row['served_by_name']
                ];
            }
    
            // Calculate net balance
            $netBalance = $totalSavings - $totalWithdrawals;
    
            return [
                'status' => 'success',
                'data' => [
                    'group_details' => $groupDetails,
                    'transactions' => $transactions,
                    'summary' => [
                        'total_savings' => $totalSavings,
                        'total_withdrawals' => $totalWithdrawals,
                        'net_balance' => $netBalance
                    ]
                ]
            ];
        } catch (Exception $e) {
            error_log("Error in getStatementData: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    

    // Get savings records for the group
    public function getGroupSavings($groupId) {
        try {
            $query = "SELECT s.*,
                        CONCAT(a.first_name, ' ', a.last_name) as member_name,
                        CONCAT(u.firstname, ' ', u.lastname) as served_by_name,
                        s.date_saved as date
                     FROM group_savings s
                     JOIN client_accounts a ON s.account_id = a.account_id
                     JOIN user u ON s.served_by = u.user_id
                     WHERE s.group_id = ?
                     ORDER BY s.date_saved DESC";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("i", $groupId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getGroupSavings: " . $e->getMessage());
            return [];
        }
    }

    // Get withdrawals records for the group
    public function getGroupWithdrawals($groupId) {
        try {
            $query = "SELECT w.*,
                        CONCAT(a.first_name, ' ', a.last_name) as member_name,
                        CONCAT(u.firstname, ' ', u.lastname) as served_by_name,
                        w.date_withdrawn as date
                     FROM group_withdrawals w
                     JOIN client_accounts a ON w.account_id = a.account_id
                     JOIN user u ON w.served_by = u.user_id
                     WHERE w.group_id = ?
                     ORDER BY w.date_withdrawn DESC";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("i", $groupId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getGroupWithdrawals: " . $e->getMessage());
            return [];
        }
    }

    // Get total savings for the group
    public function getTotalGroupSavings($groupId) {
        try {
            $query = "SELECT COALESCE(SUM(amount), 0) as total 
                     FROM group_savings 
                     WHERE group_id = ?";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("i", $groupId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error in getTotalGroupSavings: " . $e->getMessage());
            return 0;
        }
    }

    // Get total withdrawals for the group
    public function getTotalGroupWithdrawals($groupId) {
        try {
            $query = "SELECT COALESCE(SUM(amount), 0) as total 
                     FROM group_withdrawals 
                     WHERE group_id = ?";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("i", $groupId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error in getTotalGroupWithdrawals: " . $e->getMessage());
            return 0;
        }
    }

    // Search for available members (not in any group)
    public function searchAvailableMembers($groupId, $search) {
        try {
            $search = "%{$search}%";
            $query = "SELECT a.* 
                     FROM client_accounts a
                     WHERE (a.account_id NOT IN (
                         SELECT account_id FROM group_members WHERE status = 'active'
                     ))
                     AND (
                         a.first_name LIKE ? OR 
                         a.last_name LIKE ? OR 
                         a.shareholder_no LIKE ?
                     )
                     LIMIT 10";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("sss", $search, $search, $search);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error in searchAvailableMembers: " . $e->getMessage());
            return [];
        }
    }

    // Add member to group
    public function addMember($groupId, $accountId) {
        try {
            // Begin transaction
            $this->db->conn->begin_transaction();

            // Check if member is already in a group
            $check_query = "SELECT group_id FROM group_members 
                          WHERE account_id = ? AND status = 'active'";
            $stmt = $this->db->conn->prepare($check_query);
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Member is already in a group");
            }

            // Add member to group
            $insert_query = "INSERT INTO group_members (group_id, account_id) 
                           VALUES (?, ?)";
            $stmt = $this->db->conn->prepare($insert_query);
            $stmt->bind_param("ii", $groupId, $accountId);
            
            if ($stmt->execute()) {
                $this->db->conn->commit();
                return json_encode(['status' => 'success', 'message' => 'Member added successfully']);
            } else {
                throw new Exception("Error adding member");
            }
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Remove member from group
    public function removeMember($groupId, $accountId) {
        try {
            // Begin transaction
            $this->db->conn->begin_transaction();

            // Check member's savings balance
            $balance_query = "SELECT 
                (SELECT COALESCE(SUM(amount), 0) FROM group_savings 
                 WHERE group_id = ? AND account_id = ?) -
                (SELECT COALESCE(SUM(amount), 0) FROM group_withdrawals 
                 WHERE group_id = ? AND account_id = ?) as balance";
            $stmt = $this->db->conn->prepare($balance_query);
            $stmt->bind_param("iiii", $groupId, $accountId, $groupId, $accountId);
            $stmt->execute();
            $balance = $stmt->get_result()->fetch_assoc()['balance'] ?? 0;

            if ($balance > 0) {
                throw new Exception("Cannot remove member with non-zero balance");
            }

            // Update member status to inactive
            $update_query = "UPDATE group_members 
                           SET status = 'inactive' 
                           WHERE group_id = ? AND account_id = ?";
            $stmt = $this->db->conn->prepare($update_query);
            $stmt->bind_param("ii", $groupId, $accountId);
            
            if ($stmt->execute()) {
                $this->db->conn->commit();
                return json_encode(['status' => 'success', 'message' => 'Member removed successfully']);
            } else {
                throw new Exception("Error removing member");
            }
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }




    // Add savings
    public function addSavings($data) {
        try {
            if (!$this->validateAmount($data['amount'])) {
                throw new Exception("Invalid amount");
            }
    
            // Begin transaction
            $this->db->conn->begin_transaction();
    
            // Insert savings record
            $insert_query = "INSERT INTO group_savings 
                           (group_id, account_id, amount, payment_mode, served_by, receipt_no) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->conn->prepare($insert_query);
            $stmt->bind_param("iidsss", 
                $data['group_id'], 
                $data['account_id'], 
                $data['amount'],
                $data['payment_mode'],
                $_SESSION['user_id'],
                $data['receipt_no']
            );
    
            if ($stmt->execute()) {
                // Add transaction record with group_id and receipt_no
                $transaction_query = "INSERT INTO transactions 
                                    (account_id, group_id, type, amount, description, receipt_no, payment_mode, served_by) 
                                    VALUES (?, ?, 'Group Savings', ?, ?, ?, ?, ?)";
                $stmt = $this->db->conn->prepare($transaction_query);
                $description = 'Group savings deposit';
                $stmt->bind_param("iidssss", 
                    $data['account_id'],
                    $data['group_id'],
                    $data['amount'],
                    $description,
                    $data['receipt_no'],
                    $data['payment_mode'],
                    $_SESSION['user_id']
                );
                
                if ($stmt->execute()) {
                    $this->db->conn->commit();
                    return json_encode(['status' => 'success', 'message' => 'Savings added successfully']);
                } else {
                    throw new Exception("Error recording transaction");
                }
            } else {
                throw new Exception("Error adding savings");
            }
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }


    
    public function withdraw($data) {
        try {
            if (!$this->validateAmount($data['amount'])) {
                throw new Exception("Invalid amount");
            }
    
            // Begin transaction
            $this->db->conn->begin_transaction();

    
            // Check available balance
            $balance_query = "SELECT 
                (SELECT COALESCE(SUM(amount), 0) FROM group_savings 
                 WHERE group_id = ? AND account_id = ?) -
                (SELECT COALESCE(SUM(amount), 0) FROM group_withdrawals 
                 WHERE group_id = ? AND account_id = ?) as balance";
            $stmt = $this->db->conn->prepare($balance_query);
            $stmt->bind_param("iiii", 
                $data['group_id'], $data['account_id'], 
                $data['group_id'], $data['account_id']
            );
            $stmt->execute();
            $available_balance = $stmt->get_result()->fetch_assoc()['balance'] ?? 0;
    
            if ($available_balance < $data['amount']) {
                throw new Exception("Insufficient balance");
            }
    
            // Insert withdrawal record
            $insert_query = "INSERT INTO group_withdrawals 
                           (group_id, account_id, amount, payment_mode, served_by, receipt_no) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->conn->prepare($insert_query);
            $stmt->bind_param("iidsss", 
                $data['group_id'], 
                $data['account_id'], 
                $data['amount'],
                $data['payment_mode'],
                $_SESSION['user_id'],
                $data['receipt_no']
            );
    
            if ($stmt->execute()) {
                // Add transaction record with group_id and receipt_no
                $transaction_query = "INSERT INTO transactions 
                                    (account_id, group_id, type, amount, description, receipt_no, payment_mode, served_by) 
                                    VALUES (?, ?, 'Group Withdrawal', ?, ?, ?, ?, ?)";
                $stmt = $this->db->conn->prepare($transaction_query);
                $description = 'Group savings withdrawal';
                $stmt->bind_param("iidssss", 
                    $data['account_id'],
                    $data['group_id'],
                    $data['amount'],
                    $description,
                    $data['receipt_no'],
                    $data['payment_mode'],
                    $_SESSION['user_id']
                );
                
                if ($stmt->execute()) {
                    $this->db->conn->commit();
                    return json_encode(['status' => 'success', 'message' => 'Withdrawal processed successfully']);
                } else {
                    throw new Exception("Error recording transaction");
                }
            } else {
                throw new Exception("Error processing withdrawal");
            }
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Get receipt details
    public function getReceiptDetails($id, $type) {
        try {
            if (!$id || !$type) {
                throw new Exception('Missing id or type parameter');
            }
    
            if ($type === 'Savings') {
                $query = "SELECT s.*, 
                                s.id as receipt_id,
                                g.group_name,
                                CONCAT(a.first_name, ' ', a.last_name) as member_name,
                                CONCAT(u.firstname, ' ', u.lastname) as served_by_name,
                                s.date_saved as date,
                                s.receipt_no,
                                s.payment_mode
                         FROM group_savings s
                         JOIN lato_groups g ON s.group_id = g.group_id
                         JOIN client_accounts a ON s.account_id = a.account_id
                         JOIN user u ON s.served_by = u.user_id
                         WHERE s.id = ?";
            } else {
                $query = "SELECT w.*, 
                                w.id as receipt_id,
                                g.group_name,
                                CONCAT(a.first_name, ' ', a.last_name) as member_name,
                                CONCAT(u.firstname, ' ', u.lastname) as served_by_name,
                                w.date_withdrawn as date,
                                w.receipt_no,
                                w.payment_mode
                         FROM group_withdrawals w
                         JOIN lato_groups g ON w.group_id = g.group_id
                         JOIN client_accounts a ON w.account_id = a.account_id
                         JOIN user u ON w.served_by = u.user_id
                         WHERE w.id = ?";
            }
    
            $stmt = $this->db->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->db->conn->error);
            }
    
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute statement: " . $stmt->error);
            }
    
            $result = $stmt->get_result();
            if (!$result) {
                throw new Exception("Failed to get result: " . $stmt->error);
            }
    
            $row = $result->fetch_assoc();
            if (!$row) {
                throw new Exception("Receipt details not found");
            }
    
            return json_encode(['status' => 'success', 'data' => $row]);
        } catch (Exception $e) {
            error_log("Error in getReceiptDetails: " . $e->getMessage());
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    
    

    // Get member savings balance
    public function getMemberBalance($groupId, $accountId) {
        try {
            $query = "SELECT 
                        (SELECT COALESCE(SUM(amount), 0) FROM group_savings 
                         WHERE group_id = ? AND account_id = ?) -
                        (SELECT COALESCE(SUM(amount), 0) FROM group_withdrawals 
                         WHERE group_id = ? AND account_id = ?) as balance";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("iiii", $groupId, $accountId, $groupId, $accountId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                return json_encode(['status' => 'success', 'balance' => $row['balance']]);
            } else {
                throw new Exception("Error getting balance");
            }
        } catch (Exception $e) {
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Get member transaction history
    public function getMemberTransactions($groupId, $accountId) {
        try {
            $query = "SELECT t.* 
                     FROM transactions t
                     JOIN group_members m ON t.account_id = m.account_id
                     WHERE m.group_id = ? AND m.account_id = ?
                     ORDER BY t.date DESC";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("ii", $groupId, $accountId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getMemberTransactions: " . $e->getMessage());
            return [];
        }
    }


    // Get group statistics
    public function getGroupStatistics($groupId) {
        try {
            $stats = [
                'total_members' => 0,
                'total_savings' => 0,
                'total_withdrawals' => 0,
                'net_balance' => 0,
                'monthly_savings' => [],
                'monthly_withdrawals' => []
            ];

            // Get member count
            $query = "SELECT COUNT(*) as count FROM group_members 
                     WHERE group_id = ? AND status = 'active'";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("i", $groupId);
            $stmt->execute();
            $stats['total_members'] = $stmt->get_result()->fetch_assoc()['count'];

            // Get monthly savings
            $query = "SELECT 
                        DATE_FORMAT(date_saved, '%Y-%m') as month,
                        SUM(amount) as total
                     FROM group_savings 
                     WHERE group_id = ?
                     GROUP BY DATE_FORMAT(date_saved, '%Y-%m')
                     ORDER BY month";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("i", $groupId);
            $stmt->execute();
            $stats['monthly_savings'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Get monthly withdrawals
            $query = "SELECT 
                        DATE_FORMAT(date_withdrawn, '%Y-%m') as month,
                        SUM(amount) as total
                     FROM group_withdrawals 
                     WHERE group_id = ?
                     GROUP BY DATE_FORMAT(date_withdrawn, '%Y-%m')
                     ORDER BY month";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param("i", $groupId);
            $stmt->execute();
            $stats['monthly_withdrawals'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Calculate totals
            $stats['total_savings'] = $this->getTotalGroupSavings($groupId);
            $stats['total_withdrawals'] = $this->getTotalGroupWithdrawals($groupId);
            $stats['net_balance'] = $stats['total_savings'] - $stats['total_withdrawals'];

            return json_encode(['status' => 'success', 'data' => $stats]);
        } catch (Exception $e) {
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }




    // Add this method to the GroupController class
public function getNextGroupReference() {
    try {
        // Get the latest reference number
        $query = "SELECT group_reference FROM lato_groups 
                 WHERE group_reference LIKE 'wekeza-%' 
                 ORDER BY CAST(SUBSTRING_INDEX(group_reference, '-', -1) AS UNSIGNED) DESC 
                 LIMIT 1";
        
        $result = $this->db->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $lastRef = $result->fetch_assoc()['group_reference'];
            // Extract the number and increment
            $lastNum = intval(substr($lastRef, strrpos($lastRef, '-') + 1));
            $nextNum = $lastNum + 1;
        } else {
            // Start with 1 if no existing references
            $nextNum = 1;
        }
        
        // Format with leading zeros
        return 'wekeza-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        error_log("Error generating next group reference: " . $e->getMessage());
        return null;
    }
}
}

// Handle incoming requests
$groupController = new GroupController();

if (isset($_POST['action']) || isset($_GET['action'])) {
    $action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'];
    
    switch ($action) {
        case 'getGroupById':
            $groupId = $_GET['id'] ?? null;
            if ($groupId) {
                $group = $groupController->getGroupById($groupId);
                echo $group ? json_encode(['status' => 'success', 'data' => $group]) 
                          : json_encode(['status' => 'error', 'message' => 'Group not found']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Group ID not provided']);
            }
            break;

        case 'searchAvailableMembers':
            $groupId = $_GET['group_id'] ?? null;
            $search = $_GET['search'] ?? '';
            if ($groupId) {
                echo json_encode($groupController->searchAvailableMembers($groupId, $search));
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Group ID not provided']);
            }
            break;

        case 'addMember':
            if (isset($_POST['group_id']) && isset($_POST['account_id'])) {
                echo $groupController->addMember($_POST['group_id'], $_POST['account_id']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
            }
            break;

        case 'removeMember':
            if (isset($_POST['group_id']) && isset($_POST['account_id'])) {
                echo $groupController->removeMember($_POST['group_id'], $_POST['account_id']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
            }
            break;

        case 'getNextReference':
                echo json_encode(['status' => 'success', 'reference' => $groupController->getNextGroupReference()]);
                break;

        case 'addSavings':
            echo $groupController->addSavings($_POST);
            break;

        case 'withdraw':
            echo $groupController->withdraw($_POST);
            break;

        case 'getReceiptDetails':
                    if (!isset($_POST['id']) || !isset($_POST['type'])) {
                        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
                        break;
                    }
                    echo $groupController->getReceiptDetails($_POST['id'], $_POST['type']);
                    break;


        case 'getMemberBalance':
            if (isset($_GET['group_id']) && isset($_GET['account_id'])) {
                echo $groupController->getMemberBalance($_GET['group_id'], $_GET['account_id']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
            }
            break;

      
   case 'getStatementData':
    if (isset($_POST['group_id']) && isset($_POST['from_date']) && isset($_POST['to_date'])) {
        $result = $groupController->getStatementData(
            $_POST['group_id'],
            $_POST['from_date'],
            $_POST['to_date']
        );
        echo json_encode($result);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required parameters'
        ]);
    }
    exit;
    break;

        case 'getGroupStatistics':
            if (isset($_GET['group_id'])) {
                echo $groupController->getGroupStatistics($_GET['group_id']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Group ID not provided']);
            }
            break;

        case 'create':
                echo $groupController->createGroup($_POST);
                break;
                
            case 'get':
                if (isset($_POST['group_id'])) {
                    echo $groupController->getGroup($_POST['group_id']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Group ID not provided']);
                }
                break;
                
            case 'update':
                echo $groupController->updateGroup($_POST);
                break;
                
            case 'delete':
                if (isset($_POST['group_id'])) {
                    echo $groupController->deleteGroup($_POST['group_id']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Group ID not provided']);
                }
                break;
    
            case 'getFieldOfficers':
                echo $groupController->getFieldOfficers();
                break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
}
?>