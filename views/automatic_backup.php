<?php
require_once '../config/class.php';
require_once '../models/settings_handler.php';

$db = new db_class();
$settings = getSettings();


// Function to add a notification
function addNotification($db, $message, $type = 'system') {
    $query = $db->conn->prepare("INSERT INTO notifications (message, type, date) VALUES (?, ?, NOW())");
    $query->bind_param("ss", $message, $type);
    $query->execute();
}

// Function to perform backup
function performBackup($db, $backupDir) {
    $timestamp = date('Y-m-d_H-i-s');
    $filename = 'auto_backup_' . $timestamp . '.sql';
    $filepath = $backupDir . $filename;

    try {
        // Get all table names
        $tables = $db->get_all_tables();

        // Open file for writing
        $handle = fopen($filepath, 'w');
        if (!$handle) {
            throw new Exception("Failed to open file for writing: $filepath");
        }

        // Iterate through tables and write CREATE and INSERT statements
        foreach ($tables as $table) {
            $tableData = $db->backup_table($table);
            fwrite($handle, $tableData);
        }

        fclose($handle);

        // Check if the backup file was created and has content
        if (!file_exists($filepath) || filesize($filepath) == 0) {
            throw new Exception("Backup file is empty or was not created");
        }

        return $filename;
    } catch (Exception $e) {
        throw $e;
    }
}

// Check if it's time to run the backup
$currentTime = date('H:i');
$currentDay = date('N'); // 1 (for Monday) through 7 (for Sunday)
$currentDate = date('j'); // Day of the month without leading zeros

$shouldRunBackup = false;

switch ($settings['backup_frequency']) {
    case 'daily':
        $shouldRunBackup = true;
        break;
    case 'weekly':
        if ($currentDay == 1) { // Monday
            $shouldRunBackup = true;
        }
        break;
    case 'monthly':
        if ($currentDate == 1) { // First day of the month
            $shouldRunBackup = true;
        }
        break;
}

if ($shouldRunBackup && $currentTime == $settings['backup_time']) {
    $backupDir = '../backups/';

    // Ensure backup directory exists
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    try {
        $filename = performBackup($db, $backupDir);

        // Log successful backup
        error_log("Automatic backup created successfully: $filename");

        // Add notification for successful backup
        addNotification($db, "Automatic backup created successfully: $filename", 'system');

        // Update last_backup_date in settings
        $db->update_settings('last_backup_date', date('Y-m-d H:i:s'));

    } catch (Exception $e) {
        error_log("Automatic backup failed: " . $e->getMessage());

        // Add notification for failed backup
        addNotification($db, "Automatic backup failed: " . $e->getMessage(), 'system');
    }
}
?>