<?php
require_once 'config.php';


class db_class extends db_connect {
    
    public function __construct() {
        $this->connect();
    }
    
        // Add this method inside the db_class in class.php
        public function addNotification($message, $type, $relatedId = null) {
            try {
                $stmt = $this->conn->prepare("INSERT INTO notifications (message, type, related_id, date) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("ssi", $message, $type, $relatedId);
                $result = $stmt->execute();
                if (!$result) {
                    error_log("Failed to add notification: " . $stmt->error);
                }
                return $result;
            } catch (Exception $e) {
                error_log("Exception in addNotification: " . $e->getMessage());
                return false;
            }
        }

        ///add transaction
        public function add_transaction($account_id, $type, $amount, $description) {
            $query = $this->conn->prepare("INSERT INTO `transactions` (account_id, type, amount, description, date) VALUES (?, ?, ?, ?, NOW())");
            $query->bind_param("isds", $account_id, $type, $amount, $description);
            
            if($query->execute()) {
                $query->close();
                return true;
            }
            return false;
        }


        

    /* User Functions */
    public function add_user($username, $password, $firstname, $lastname, $role) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = $this->conn->prepare("INSERT INTO `user` (`username`, `password`, `firstname`, `lastname`, `role`) VALUES(?, ?, ?, ?, ?)");
        if (!$query) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        $query->bind_param("sssss", $username, $hashed_password, $firstname, $lastname, $role);
        
        if($query->execute()) {
            $query->close();
            return true;
        }
        return false;
    }
    
    public function update_user($user_id, $username, $password, $firstname, $lastname, $role) {
        // Get the current user data
        $current_user = $this->get_user($user_id);
        
        // Check if password has changed
        if ($password !== $current_user['password']) {
            // Only hash the password if it's different from the current one
            $password = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $query = $this->conn->prepare("UPDATE `user` SET `username`=?, `password`=?, `firstname`=?, `lastname`=?, `role`=? WHERE `user_id`=?");
        $query->bind_param("sssssi", $username, $password, $firstname, $lastname, $role, $user_id);
        
        if($query->execute()) {
            $query->close();
            return true;
        }
        return false;
    }
    

    public function getUserByUsername($username) {
        $query = $this->conn->prepare("SELECT * FROM `user` WHERE `username` = ?");
        if (!$query) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        $query->bind_param("s", $username);
        
        if ($query->execute()) {
            $result = $query->get_result();
            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }
        }
        return null;
    }


    public function resetPassword($username, $new_password) {
        // First, check if the user exists
        $user = $this->getUserByUsername($username);
        if (!$user) {
            return "User not found.";
        }

        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password in the database
        $stmt = $this->conn->prepare("UPDATE user SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $hashed_password, $username);
        
        if ($stmt->execute()) {
            return true;
        } else {
            return "Failed to reset password. Please try again.";
        }
    }



    public function get_pending_loans() {
    $query = $this->conn->query("SELECT l.loan_id, l.ref_no, l.amount, l.loan_term, l.monthly_payment,
                                 a.last_name, a.first_name, a.shareholder_no 
                                 FROM loan l 
                                 JOIN client_accounts a ON l.account_id = a.account_id 
                                 WHERE l.status = 0 
                                 ORDER BY l.loan_id DESC");
    return $query;
}





    // Add this function to get the date of the last backup
public function getLastBackupDate() {
    $backupDir = '../backups/';
    $files = glob($backupDir . '*.{sql,zip}', GLOB_BRACE);
    if (empty($files)) {
        return date('Y-m-d H:i:s', 0);
    }
    $lastBackup = max(array_map('filemtime', $files));
    return date('Y-m-d H:i:s', $lastBackup);
}

// Add these helper functions to the db_class
public function get_all_tables() {
    $query = $this->conn->query("SHOW TABLES");
    $tables = array();
    while ($row = $query->fetch_row()) {
        $tables[] = $row[0];
    }
    return $tables;
}



//full backup
public function create_full_backup() {
    try {
        error_log("Starting full backup creation");
        $tables = $this->get_all_tables();
        if (empty($tables)) {
            error_log("No tables found in database");
            throw new Exception("No tables found in database");
        }
        
        // Get database name from connection
        $dbname = mysqli_query($this->conn, "SELECT DATABASE()")->fetch_array()[0];
        
        $backup = "";
        $backup .= "-- Lato SACCO Full Backup\n";
        $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $backup .= "-- Database: " . $dbname . "\n\n";
        
        foreach ($tables as $table) {
            error_log("Backing up table: " . $table);
            $backup .= $this->backup_table($table);
        }
        
        error_log("Full backup created successfully");
        return $backup;
    } catch (Exception $e) {
        error_log("Full backup creation failed: " . $e->getMessage());
        throw $e;
    }
}

public function backup_table($table) {
    try {
        // Get table create statement
        $show_create = $this->conn->query("SHOW CREATE TABLE `$table`");
        if (!$show_create) {
            throw new Exception("Failed to get CREATE TABLE statement for $table: " . $this->conn->error);
        }
        $row = $show_create->fetch_row();
        $create_table_sql = $row[1] . ";\n\n";
        
        // Get table data
        $result = $this->conn->query("SELECT * FROM `$table`");
        if (!$result) {
            throw new Exception("Failed to get data from $table: " . $this->conn->error);
        }
        
        $backup = "DROP TABLE IF EXISTS `$table`;\n";
        $backup .= $create_table_sql;
        
        while ($row = $result->fetch_assoc()) {
            $values = array();
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = "NULL";
                } else {
                    $values[] = "'" . $this->conn->real_escape_string($value) . "'";
                }
            }
            $backup .= "INSERT INTO `$table` VALUES (" . implode(", ", $values) . ");\n";
        }
        
        return $backup . "\n";
    } catch (Exception $e) {
        error_log("Failed to backup table $table: " . $e->getMessage());
        throw $e;
    }
}

public function create_and_save_backup($type = 'full') {
    try {
        error_log("Starting backup process - Type: " . $type);
        
        // Create backup directory if it doesn't exist
        $backup_dir = dirname(__DIR__) . '/backups/';
        if (!file_exists($backup_dir)) {
            if (!mkdir($backup_dir, 0755, true)) {
                throw new Exception("Failed to create backup directory: " . $backup_dir);
            }
        }
        
        if (!is_writable($backup_dir)) {
            throw new Exception("Backup directory is not writable: " . $backup_dir);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backup_content = '';
        
        if ($type === 'full') {
            $backup_content = $this->create_full_backup();
            $filename = 'full_backup_' . $timestamp . '.sql';
        } else {
            $last_backup_date = $this->getLastBackupDate();
            $backup_content = $this->create_incremental_backup($last_backup_date);
            $filename = 'incremental_backup_' . $timestamp . '.sql';
        }
        
        if (empty($backup_content)) {
            throw new Exception("No backup content generated");
        }
        
        $filepath = $backup_dir . $filename;
        if (file_put_contents($filepath, $backup_content) === false) {
            throw new Exception("Failed to write backup to file: " . $filepath);
        }
        
        error_log("Backup created successfully: " . $filepath);
        return [
            'status' => 'success',
            'message' => ucfirst($type) . ' backup created successfully',
            'filename' => $filename
        ];
    } catch (Exception $e) {
        error_log("Backup creation failed: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}



public function create_incremental_backup($last_backup_date) {
    try {
        $tables = $this->get_all_tables();
        $backup = "";
        
        // Add header
        $backup .= "-- Lato SACCO Incremental Backup\n";
        $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $backup .= "-- Changes since: " . $last_backup_date . "\n\n";
        
        $backup .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $has_changes = false;
        
        foreach ($tables as $table) {
            // Check for a timestamp column
            $timestamp_columns = ['updated_at', 'modified_at', 'date_modified', 'last_updated', 'timestamp'];
            $columns = $this->get_table_columns($table);
            $timestamp_column = null;
            
            foreach ($timestamp_columns as $col) {
                if (in_array($col, $columns)) {
                    $timestamp_column = $col;
                    break;
                }
            }
            
            if ($timestamp_column) {
                $query = $this->conn->prepare("SELECT * FROM `$table` WHERE `$timestamp_column` > ?");
                $query->bind_param("s", $last_backup_date);
                $query->execute();
                $result = $query->get_result();
                
                if ($result->num_rows > 0) {
                    $has_changes = true;
                    $backup .= "\n-- Changes in table `$table`\n";
                    while ($row = $result->fetch_assoc()) {
                        $fields = array_map([$this->conn, 'real_escape_string'], $row);
                        $fields = array_map(function($field) {
                            return $field === null ? 'NULL' : "'$field'";
                        }, $fields);
                        
                        $backup .= "INSERT INTO `$table` VALUES (" . implode(", ", $fields) . ");\n";
                    }
                }
            }
        }
        
        $backup .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
        
        return $has_changes ? $backup : "";
        
    } catch (Exception $e) {
        error_log("Incremental backup creation failed: " . $e->getMessage());
        return false;
    }
}

private function get_table_columns($table) {
    $columns = [];
    $result = $this->conn->query("SHOW COLUMNS FROM `$table`");
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    return $columns;
}




// Add this helper method to check if a column exists in a table
private function column_exists($table, $column) {
    $query = $this->conn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $query->bind_param("s", $column);
    $query->execute();
    $result = $query->get_result();
    return $result->num_rows > 0;
}


// Add a method to get database size
public function get_database_size() {
    $query = $this->conn->query("SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = DATABASE()");
    $result = $query->fetch_assoc();
    return $result['size'];
}


public function get_user_role($user_id) {
    try {
        $query = $this->conn->prepare("SELECT role FROM `user` WHERE `user_id` = ?");
        if (!$query) {
            error_log("Prepare failed: " . $this->conn->error);
            return 'user'; // default fallback
        }
        
        $query->bind_param("i", $user_id);
        
        if ($query->execute()) {
            $result = $query->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $query->close();
                return $user['role'];
            }
        }
        $query->close();
        return 'user'; // default fallback if user not found
    } catch (Exception $e) {
        error_log("Error getting user role: " . $e->getMessage());
        return 'user'; // default fallback on error
    }
}

    
    public function login($username, $password) {
        try {
            $query = $this->conn->prepare("SELECT * FROM `user` WHERE `username` = ?");
            if (!$query) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
    
            $query->bind_param("s", $username);
            if (!$query->execute()) {
                throw new Exception("Execute failed: " . $query->error);
            }
    
            $result = $query->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_array();
                if (password_verify($password, $user['password'])) {
                    return array(
                        'user_id' => $user['user_id'],
                        'role' => $user['role'],
                        'count' => 1
                    );
                } else {
                    error_log("Password verification failed for user: $username");
                }
            } else {
                error_log("No user found with username: $username");
            }
        } catch (Exception $e) {
            error_log("Exception in login method: " . $e->getMessage());
        }
        return array('user_id' => 0, 'role' => '', 'count' => 0);
    }


    
    public function user_acc($user_id) {
        $query = $this->conn->prepare("SELECT * FROM `user` WHERE `user_id`=?") or die($this->conn->error);
        $query->bind_param("i", $user_id);
        if($query->execute()) {
            $result = $query->get_result();
            $fetch = $result->fetch_array();
            return $fetch['firstname'] . " " . $fetch['lastname'];    
        }
    }
    
    public function display_user() {
        $query = $this->conn->prepare("SELECT * FROM `user`") or die($this->conn->error);
        if($query->execute()) {
            $result = $query->get_result();
            return $result;
        }
    }


    ///deleting user
    
    public function check_user_dependencies($user_id) {
        // Check lato_groups
        $query = $this->conn->prepare("SELECT COUNT(*) as count FROM lato_groups WHERE field_officer_id = ?");
        $query->bind_param("i", $user_id);
        $query->execute();
        $result = $query->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            return array(
                'can_delete' => false,
                'reason' => 'User is assigned as field officer to ' . $result['count'] . ' group(s)',
                'groups_count' => $result['count']
            );
        }
        
        // Add more dependency checks here if needed
        return array('can_delete' => true);
    }
    
    public function reassign_groups($old_user_id, $new_user_id) {
        $query = $this->conn->prepare("UPDATE lato_groups SET field_officer_id = ? WHERE field_officer_id = ?");
        $query->bind_param("ii", $new_user_id, $old_user_id);
        return $query->execute();
    }
    
    public function get_field_officers($except_user_id = null) {
        $sql = "SELECT user_id, firstname, lastname FROM user WHERE role = 'officer'";
        if ($except_user_id) {
            $sql .= " AND user_id != ?";
        }
        
        $query = $this->conn->prepare($sql);
        if ($except_user_id) {
            $query->bind_param("i", $except_user_id);
        }
        
        $query->execute();
        return $query->get_result();
    }
    
    public function delete_user($user_id) {
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            // First check if user can be deleted
            $dependencies = $this->check_user_dependencies($user_id);
            if (!$dependencies['can_delete']) {
                throw new Exception("Cannot delete user: " . $dependencies['reason']);
            }
            
            // Proceed with deletion
            $query = $this->conn->prepare("DELETE FROM user WHERE user_id = ?");
            $query->bind_param("i", $user_id);
            
            if (!$query->execute()) {
                throw new Exception("Failed to delete user");
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    // Optional: Implement soft delete if preferred
    public function soft_delete_user($user_id) {
        $query = $this->conn->prepare("UPDATE user SET status = 'inactive', deleted_at = NOW() WHERE user_id = ?");
        $query->bind_param("i", $user_id);
        return $query->execute();
    }
    
    /* Loan Product Functions */
    
    public function save_loan_product($loan_type, $interest_rate) {
        $query = $this->conn->prepare("INSERT INTO `loan_products` (`loan_type`, `interest_rate`) VALUES(?, ?)") or die($this->conn->error);
        $query->bind_param("sd", $loan_type, $interest_rate);
        
        if($query->execute()) {
            $query->close();
            return true;
        }
        return false;
    }
    
    public function display_loan_products() {
        $query = $this->conn->prepare("SELECT * FROM `loan_products`") or die($this->conn->error);
        if($query->execute()) {
            $result = $query->get_result();
            return $result;
        }
    }
    
    public function get_loan_product($id) {
        $query = $this->conn->prepare("SELECT * FROM `loan_products` WHERE `id` = ?");
        $query->bind_param("i", $id);
        if($query->execute()) {
            $result = $query->get_result();
            return $result->fetch_assoc();
        }
        return false;
    }
    
    
    public function update_loan_product($id, $loan_type, $interest_rate) {
        $query = $this->conn->prepare("UPDATE `loan_products` SET `loan_type`=?, `interest_rate`=? WHERE `id`=?") or die($this->conn->error);
        $query->bind_param("sdi", $loan_type, $interest_rate, $id);
        
        if($query->execute()) {
            $query->close();
            return true;
        }
        return false;
    }
    
    public function delete_loan_product($id) {
        $query = $this->conn->prepare("DELETE FROM `loan_products` WHERE `id` = ?") or die($this->conn->error);
        $query->bind_param("i", $id);
        if($query->execute()) {
            $query->close();
            return true;
        }
        return false;
    }
    
    /* Client Account Functions */
    
    public function save_client_account($shareholder_no, $national_id, $first_name, $last_name, $phone_number, $email, $division, $location, $village, $account_type) {
        // Convert account_type array to comma-separated string if it's an array
        if (is_array($account_type)) {
            $account_type = implode(', ', $account_type);
        }
        
        $query = $this->conn->prepare("INSERT INTO `client_accounts` (`shareholder_no`, `national_id`, `first_name`, `last_name`, `phone_number`, `email`, `division`, `location`, `village`, `account_type`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $query->bind_param("ssssssssss", $shareholder_no, $national_id, $first_name, $last_name, $phone_number, $email, $division, $location, $village, $account_type);
        
        if ($query->execute()) {
            $query->close();
            return true;
        }
        return false;
    }
    
    public function display_client_accounts() {
        $query = $this->conn->prepare("SELECT account_id, shareholder_no, first_name, last_name FROM `client_accounts` ORDER BY last_name, first_name");
        if($query->execute()) {
            $result = $query->get_result();
            return $result;
        }
        return false;
    }
    
    public function delete_client_account($account_id) {
        $query = $this->conn->prepare("DELETE FROM `client_accounts` WHERE `account_id` = ?");
        $query->bind_param("i", $account_id);
        if($query->execute()) {
            $query->close();
            return true;
        }
        return false;
    }
    
    public function update_client_account($account_id, $shareholder_no, $national_id, $first_name, $last_name, $phone_number, $email, $division, $location, $village, $account_type) {
        // Convert account_type array to comma-separated string if it's an array
        if (is_array($account_type)) {
            $account_type = implode(', ', $account_type);
        }
        
        $query = $this->conn->prepare("UPDATE `client_accounts` SET `shareholder_no`=?, `national_id`=?, `first_name`=?, `last_name`=?, `phone_number`=?, `email`=?, `division`=?, `location`=?, `village`=?, `account_type`=? WHERE `account_id`=?");
        $query->bind_param("ssssssssssi", $shareholder_no, $national_id, $first_name, $last_name, $phone_number, $email, $division, $location, $village, $account_type, $account_id);
        
        if($query->execute()) {
            $query->close();
            return true;
        }
        return false;
    }
    
    /* Loan Functions */
    public function save_loan($client, $loan_product_id, $loan_amount, $purpose, $date_created, $loan_term, 
    $interest_rate, $monthly_payment, $total_payable, $total_interest, 
    $meeting_date, $client_pledges, $guarantor_name, $guarantor_id, 
    $guarantor_phone, $guarantor_location, $guarantor_sublocation, 
    $guarantor_village, $guarantor_pledges) {

$ref_no = mt_rand(1, 99999999);
$status = 0; // Initial status

// Check for unique reference number
$i = 1;
while($i == 1) {
$check_query = $this->conn->prepare("SELECT * FROM `loan` WHERE `ref_no` = ?");
$check_query->bind_param("s", $ref_no);
$check_query->execute();
$check_result = $check_query->get_result();

if($check_result->num_rows > 0) {
$ref_no = mt_rand(1, 99999999);
} else {
$i = 0;
}
$check_query->close();
}

$query = $this->conn->prepare("INSERT INTO `loan` (
ref_no, account_id, loan_product_id, amount, purpose, 
loan_term, interest_rate, monthly_payment, total_payable, 
total_interest, date_applied, status, meeting_date, 
client_pledges, guarantor_name, guarantor_id, guarantor_phone, 
guarantor_location, guarantor_sublocation, guarantor_village, 
guarantor_pledges
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$query) {
error_log("Prepare failed: " . $this->conn->error);
return false;
}

$query->bind_param(
"siisdiddddsssssssssss",
$ref_no, $client, $loan_product_id, $loan_amount, $purpose,
$loan_term, $interest_rate, $monthly_payment, $total_payable,
$total_interest, $date_created, $status, $meeting_date,
$client_pledges, $guarantor_name, $guarantor_id, $guarantor_phone,
$guarantor_location, $guarantor_sublocation, $guarantor_village,
$guarantor_pledges
);

if($query->execute()) {
$loan_id = $this->conn->insert_id;
$this->create_loan_schedule($loan_id);
$query->close();
return true;
}

error_log("Execute failed: " . $query->error);
return false;
}




    public function display_loan() {
        $query = $this->conn->prepare("SELECT l.*, ca.first_name, ca.last_name, ca.shareholder_no, ca.phone_number, ca.location, lp.loan_type, lp.interest_rate 
                                       FROM `loan` l 
                                       INNER JOIN `client_accounts` ca ON l.account_id = ca.account_id 
                                       INNER JOIN `loan_products` lp ON l.loan_product_id = lp.id");
        if($query->execute()) {
            $result = $query->get_result();
            return $result;
        }
    }
    
    public function delete_loan($loan_id) {
        $query = $this->conn->prepare("DELETE FROM `loan` WHERE `loan_id` = ?");
        $query->bind_param("i", $loan_id);
        if($query->execute()) {
            $query->close();
            return true;
        }
        return false;
    }
    
    public function update_loan_schedule($loan_id) {
        // First, delete existing schedule
        $delete_query = $this->conn->prepare("DELETE FROM loan_schedule WHERE loan_id = ?");
        $delete_query->bind_param("i", $loan_id);
        $delete_query->execute();
        $delete_query->close();

        // Then create a new schedule
        return $this->create_loan_schedule($loan_id);
    }


    //update loan
    public function update_loan($loan_id, $client, $loan_product_id, $loan_amount, $purpose, $status, 
    $loan_term, $interest_rate, $monthly_payment, $total_payable, $total_interest, 
    $meeting_date, $client_pledges, $guarantor_name, $guarantor_id, 
    $guarantor_phone, $guarantor_location, $guarantor_sublocation, 
    $guarantor_village, $guarantor_pledges, $date_released = null) {

    $query = $this->conn->prepare("UPDATE `loan` SET 
    account_id = ?, 
    loan_product_id = ?, 
    amount = ?, 
    purpose = ?, 
    status = ?, 
    loan_term = ?, 
    interest_rate = ?, 
    monthly_payment = ?, 
    total_payable = ?, 
    total_interest = ?, 
    meeting_date = ?,
    client_pledges = ?,
    guarantor_name = ?,
    guarantor_id = ?,
    guarantor_phone = ?,
    guarantor_location = ?,
    guarantor_sublocation = ?,
    guarantor_village = ?,
    guarantor_pledges = ?,
    date_released = ?
    WHERE loan_id = ?");

    $query->bind_param(
    "iisdsiidddssssssssssi",
    $client, $loan_product_id, $loan_amount, $purpose, $status,
    $loan_term, $interest_rate, $monthly_payment, $total_payable,
    $total_interest, $meeting_date, $client_pledges, $guarantor_name,
    $guarantor_id, $guarantor_phone, $guarantor_location,
    $guarantor_sublocation, $guarantor_village, $guarantor_pledges,
    $date_released, $loan_id
    );

    if($query->execute()) {
    $this->update_loan_schedule($loan_id);
    $query->close();
    return true;
    }
    return false;
    }
    
    public function get_loan($loan_id) {
        $query = $this->conn->prepare("SELECT * FROM `loan` WHERE `loan_id`=?") or die($this->conn->error);
        $query->bind_param("i", $loan_id);
        if($query->execute()) {
            $result = $query->get_result();
            return $result->fetch_assoc();
        }
        return false;
    }


        /* Newly added functions */
        public function get_paginated_loans($start = 0, $length = 10, $search = '') {
            $search_condition = '';
            if (!empty($search)) {
                $search = '%' . $this->conn->real_escape_string($search) . '%';
                $search_condition = " AND (ca.first_name LIKE ? OR ca.last_name LIKE ? OR ca.shareholder_no LIKE ? OR l.ref_no LIKE ?)";
            }
            
            $query = $this->conn->prepare("SELECT l.loan_id, l.ref_no, l.status, l.date_applied, 
                                          ca.first_name, ca.last_name, ca.shareholder_no, ca.phone_number, ca.location
                                          FROM `loan` l 
                                          INNER JOIN `client_accounts` ca ON l.account_id = ca.account_id 
                                          WHERE 1=1 " . $search_condition . "
                                          ORDER BY l.loan_id DESC LIMIT ?, ?");
            
            if (!empty($search)) {
                $query->bind_param("sssiii", $search, $search, $search, $search, $start, $length);
            } else {
                $query->bind_param("ii", $start, $length);
            }
            
            if($query->execute()) {
                $result = $query->get_result();
                return $result;
            }
            return false;
        }
        
        public function get_total_loans($search = '') {
            $search_condition = '';
            if (!empty($search)) {
                $search = '%' . $this->conn->real_escape_string($search) . '%';
                $search_condition = " AND (ca.first_name LIKE ? OR ca.last_name LIKE ? OR ca.shareholder_no LIKE ? OR l.ref_no LIKE ?)";
            }
            
            $query = $this->conn->prepare("SELECT COUNT(*) as total FROM `loan` l 
                                           INNER JOIN `client_accounts` ca ON l.account_id = ca.account_id 
                                           WHERE 1=1" . $search_condition);
            
            if (!empty($search)) {
                $query->bind_param("ssss", $search, $search, $search, $search);
            }
            
            if($query->execute()) {
                $result = $query->get_result();
                $row = $result->fetch_assoc();
                return $row['total'];
            }
            return 0;
        }
        
        public function get_loan_details_json($loan_id) {
            $query = $this->conn->prepare("SELECT l.*, ca.first_name, ca.last_name, ca.shareholder_no, 
                                           ca.phone_number, ca.location, lp.loan_type
                                           FROM `loan` l 
                                           INNER JOIN `client_accounts` ca ON l.account_id = ca.account_id 
                                           INNER JOIN `loan_products` lp ON l.loan_product_id = lp.id
                                           WHERE l.loan_id = ?");
            $query->bind_param("i", $loan_id);
            
            if($query->execute()) {
                $result = $query->get_result();
                return $result->fetch_assoc();
            }
            return null;
        }
    
    /* Payment Functions */
    public function save_payment($loan_id, $receipt_no, $payee, $pay_amount, $penalty, $overdue, $withdrawal_fee, $user_id) {
        $date_created = date("Y-m-d H:i:s");
        $query = $this->conn->prepare("INSERT INTO `payment` 
            (`loan_id`, `receipt_no`, `payee`, `pay_amount`, `penalty`, `overdue`, `date_created`, `withdrawal_fee`, `user_id`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)") or die($this->conn->error);
        
        $query->bind_param("issddisdi", $loan_id, $receipt_no, $payee, $pay_amount, $penalty, $overdue, $date_created, $withdrawal_fee, $user_id);
        
        if($query->execute()) {
            $query->close();
            return true;
        } else {
            error_log("Error in save_payment: " . $query->error);
            return false;
        }
    }


    


    public function get_payments($loan_id) {
        $query = $this->conn->prepare("SELECT * FROM `payments` WHERE `loan_id` = ? ORDER BY `payment_date`") or die($this->conn->error);
        $query->bind_param("i", $loan_id);
        if($query->execute()) {
            $result = $query->get_result();
            return $result;
        }
        return false;
    }
    
    /* Helper Functions */
    
    public function calculate_loan_summary($loan_id) {
        $loan = $this->get_loan($loan_id);
        if (!$loan) return false;

        $principal = $loan['amount'];
        $interest_rate = $loan['interest_rate'] / 100 / 12;
        $term_months = $loan['loan_term'];
        
        $monthly_payment = $loan['monthly_payment'];
        $total_payment = $loan['total_payable'];
        $total_interest = $loan['total_interest'];
        
        return array(
            'principal' => $principal,
            'total_payment' => $total_payment,
            'total_interest' => $total_interest,
            'monthly_payment' => $monthly_payment
        );
    }


    public function get_account($account_id) {
        $query = $this->conn->prepare("SELECT * FROM `client_accounts` WHERE `account_id` = ?");
        $query->bind_param("i", $account_id);
        if($query->execute()) {
            $result = $query->get_result();
            return $result->fetch_assoc();
        }
        return false;
    }

    
    public function get_account_transactions($account_id) {
        $query = $this->conn->prepare("SELECT * FROM `transactions` WHERE `account_id` = ? ORDER BY `date` DESC");
        $query->bind_param("i", $account_id);
        if($query->execute()) {
            $result = $query->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return false;
    }

    public function get_loan_schedule($loan_id) {
        $loan = $this->get_loan($loan_id);
        if (!$loan) return [];
    
        $amount = $loan['amount'];
        $term = $loan['loan_term'];
        $interest_rate = $loan['interest_rate'] / 100 / 12; 
        $monthly_payment = $loan['monthly_payment'];
    
        $balance = $amount;
        $start_date = new DateTime($loan['date_released'] ? $loan['date_released'] : $loan['date_applied']);
    
        $schedule = [];
    
        for ($i = 1; $i <= $term; $i++) {
            $interest = $balance * $interest_rate;
            $principal = $monthly_payment - $interest;
            $balance -= $principal;
    
            $schedule[] = [
                'month' => $i,
                'date' => $start_date->format('Y-m-d'),
                'payment' => $monthly_payment,
                'principal' => $principal,
                'interest' => $interest,
                'balance' => max(0, $balance)
            ];
    
            $start_date->modify('+1 month');
        }
    
        return $schedule;
    }

    public function get_next_payment_date($loan_id) {
        $query = $this->conn->prepare("SELECT MIN(due_date) as next_date 
                                       FROM `loan_schedule` 
                                       WHERE loan_id = ? AND due_date > CURDATE() AND status = 'unpaid'") or die($this->conn->error);
        $query->bind_param("i", $loan_id);
        if($query->execute()) {
            $result = $query->get_result();
            $fetch = $result->fetch_array();
            return $fetch['next_date'];
        }
        return false;
    }

    public function is_loan_overdue($loan_id) {
        $next_payment_date = $this->get_next_payment_date($loan_id);
        if (!$next_payment_date) return false;
        
        $today = new DateTime();
        $next_payment = new DateTime($next_payment_date);
        
        return $today > $next_payment;
    }

    public function get_loan_balance($loan_id) {
        $query = $this->conn->prepare("SELECT l.total_payable, COALESCE(SUM(p.amount), 0) as total_paid 
                                       FROM `loan` l 
                                       LEFT JOIN `payments` p ON l.loan_id = p.loan_id 
                                       WHERE l.loan_id = ?") or die($this->conn->error);
        $query->bind_param("i", $loan_id);
        if($query->execute()) {
            $result = $query->get_result();
            $fetch = $result->fetch_array();
            return $fetch['total_payable'] - $fetch['total_paid'];
        }
        return false;
    }

    public function get_client_name($account_id) {
        $query = $this->conn->prepare("SELECT first_name, last_name FROM `client_accounts` WHERE `account_id` = ?") or die($this->conn->error);
        $query->bind_param("i", $account_id);
        if($query->execute()) {
            $result = $query->get_result();
            $fetch = $result->fetch_array();
            return $fetch['first_name'] . " " . $fetch['last_name'];
        }
        return false;
    }

    public function get_loan_types() {
        $query = $this->conn->prepare("SELECT id, loan_type, interest_rate FROM `loan_products`") or die($this->conn->error);
        if($query->execute()) {
            $result = $query->get_result();
            $loan_types = array();
            while ($row = $result->fetch_assoc()) {
                $loan_types[] = $row;
            }
            return $loan_types;
        }
        return false;
    }

    //loan schedule

    public function create_loan_schedule($loan_id) {
        $loan = $this->get_loan($loan_id);
        if (!$loan) return false;
    
        // Use meeting date to determine first payment
        $meeting_date = new DateTime($loan['meeting_date']);
        $first_payment = clone $meeting_date;
        $first_payment->modify('+1 month');
    
        $amount = $loan['amount'];
        $term = $loan['loan_term'];
        $interest_rate = $loan['interest_rate'] / 100 / 12;
        $monthly_payment = $loan['monthly_payment'];
        $balance = $amount;
    
        $query = $this->conn->prepare("INSERT INTO loan_schedule (
            loan_id, due_date, amount, principal, interest, balance, 
            repaid_amount, default_amount, status
        ) VALUES (?, ?, ?, ?, ?, ?, 0, ?, 'unpaid')");
    
        for ($i = 0; $i < $term; $i++) {
            $due_date = clone $first_payment;
            $due_date->modify("+$i month");
            
            $interest = $balance * $interest_rate;
            $principal = $monthly_payment - $interest;
            $default_amount = $monthly_payment; // Initially, default is full payment
            
            $due_date_str = $due_date->format('Y-m-d');
            $query->bind_param(
                "isddddd",
                $loan_id, $due_date_str, $monthly_payment, $principal,
                $interest, $balance, $default_amount
            );
            $query->execute();
    
            $balance -= $principal;
        }
    
        return true;
    }

    public function update_loan_status($loan_id, $status) {
        $query = $this->conn->prepare("UPDATE `loan` SET `status` = ? WHERE `loan_id` = ?") or die($this->conn->error);
        $query->bind_param("ii", $status, $loan_id);
        
        if($query->execute()) {
            $query->close();
            return true;
        }
        return false;
    }

    public function process_payment($loan_id, $amount, $payment_date) {
        // Start transaction
        $this->conn->begin_transaction();

        try {
            // Save the payment
            if (!$this->save_payment($loan_id, $amount, $payment_date)) {
                throw new Exception("Failed to save payment");
            }

            // Update loan schedule
            $query = $this->conn->prepare("UPDATE `loan_schedule` 
                                           SET `status` = 'paid', `paid_date` = ? 
                                           WHERE `loan_id` = ? AND `status` = 'unpaid' 
                                           ORDER BY `due_date` ASC LIMIT 1");
            $query->bind_param("si", $payment_date, $loan_id);
            if (!$query->execute()) {
                throw new Exception("Failed to update loan schedule");
            }

            // Check if all payments are made
            $query = $this->conn->prepare("SELECT COUNT(*) as unpaid_count 
                                           FROM `loan_schedule` 
                                           WHERE `loan_id` = ? AND `status` = 'unpaid'");
            $query->bind_param("i", $loan_id);
            $query->execute();
            $result = $query->get_result()->fetch_assoc();

            if ($result['unpaid_count'] == 0) {
                // All payments made, update loan status to completed
                if (!$this->update_loan_status($loan_id, 3)) {
                    throw new Exception("Failed to update loan status");
                }
            }

            // Commit transaction
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            return false;
        }
    }

    public function hide_pass($str) {
        $len = strlen($str);
        return str_repeat('*', $len);
    }

    // New method for adding savings
    public function add_savings($account_id, $amount, $payment_mode) {
        $query = $this->conn->prepare("INSERT INTO `savings` (account_id, amount, payment_mode, date) VALUES (?, ?, ?, NOW())");
        $query->bind_param("ids", $account_id, $amount, $payment_mode);
        
        if($query->execute()) {
            $savings_id = $this->conn->insert_id;
            
            // Add a corresponding transaction
            $transaction_query = $this->conn->prepare("INSERT INTO `transactions` (account_id, type, amount, description, date) VALUES (?, 'Savings', ?, 'Savings deposit', NOW())");
            $transaction_query->bind_param("id", $account_id, $amount);
            $transaction_query->execute();
            
            $query->close();
            $transaction_query->close();
            return $savings_id;
        }
        return false;
    }

    public function update_settings($key, $value) {
        $query = $this->conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $query->bind_param("sss", $key, $value, $value);
        return $query->execute();
    }
    
    public function get_user($user_id) {
        $query = $this->conn->prepare("SELECT * FROM `user` WHERE `user_id` = ?");
        $query->bind_param("i", $user_id);
        if ($query->execute()) {
            $result = $query->get_result();
            return $result->fetch_assoc();
        }
        return false;
    }
    
    public function get_setting($key) {
        $query = $this->conn->prepare("SELECT setting_value, setting_type FROM settings WHERE setting_key = ?");
        $query->bind_param("s", $key);
        $query->execute();
        $result = $query->get_result();
        if ($row = $result->fetch_assoc()) {
            $value = $row['setting_value'];
            switch ($row['setting_type']) {
                case 'integer':
                    return intval($value);
                case 'float':
                    return floatval($value);
                case 'boolean':
                    return boolval($value);
                default:
                    return $value;
            }
        }
        return null;
    }


    
    public function get_all_settings() {
        $query = $this->conn->prepare("SELECT setting_key, setting_value, setting_type FROM settings");
        $query->execute();
        $result = $query->get_result();
        $settings = array();
        while ($row = $result->fetch_assoc()) {
            $value = $row['setting_value'];
            switch ($row['setting_type']) {
                case 'integer':
                    $value = intval($value);
                    break;
                case 'float':
                    $value = floatval($value);
                    break;
                case 'boolean':
                    $value = boolval($value);
                    break;
            }
            $settings[$row['setting_key']] = $value;
        }
        return $settings;
    }

}
?>