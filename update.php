<?php
/**
 * System Update Script
 * This file pulls the latest changes from the GitHub repository
 * Part of the Lato SACCO Management System
 */

session_start();

// Include your configuration and class
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/class.php';

// Initialize database connection
$db = new db_class();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']));
}

// Set headers for JSON response
header('Content-Type: application/json');

// Get the project root directory
$projectRoot = __DIR__;

// Change to project directory
chdir($projectRoot);

// Array to store output
$output = [];
$returnCode = 0;

try {
    // Get current branch name
    exec('git rev-parse --abbrev-ref HEAD 2>&1', $branchOutput, $branchCode);
    $currentBranch = trim($branchOutput[0] ?? 'main');
    
    // Fetch the latest changes from remote
    exec('git fetch origin 2>&1', $fetchOutput, $fetchCode);
    $output[] = 'Checking for updates...';
    
    // Check if there are updates available
    exec("git rev-list HEAD...origin/{$currentBranch} --count 2>&1", $countOutput);
    $updatesAvailable = (int)trim($countOutput[0] ?? 0);
    
    if ($updatesAvailable === 0) {
        // Already up to date
        echo json_encode([
            'success' => true,
            'message' => 'System is already up to date',
            'output' => 'Already up to date.'
        ]);
        exit;
    }
    
    // Reset local changes to match remote exactly (prevents conflicts)
    exec("git reset --hard origin/{$currentBranch} 2>&1", $resetOutput, $resetCode);
    $output[] = 'Applying updates...';
    
    // Pull to verify
    exec("git pull origin {$currentBranch} 2>&1", $pullOutput, $returnCode);
    $output = array_merge($output, $pullOutput);
    
    // Log the update attempt
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/system_updates.log';
    $user_name = $db->user_acc($_SESSION['user_id']);
    $logEntry = date('Y-m-d H:i:s') . " - User: " . $user_name . " (ID: " . $_SESSION['user_id'] . ")\n" . 
                "Branch: " . $currentBranch . "\n" .
                "Updates Applied: " . $updatesAvailable . "\n" .
                "Return Code: " . $returnCode . "\n" . 
                implode("\n", $output) . "\n" . str_repeat('-', 50) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // Check if successful
    if ($returnCode === 0 || $resetCode === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'System updated successfully',
            'output' => implode("\n", $output)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Update failed. Please check logs for details.',
            'output' => implode("\n", $output)
        ]);
    }
} catch (Exception $e) {
    // Log error
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/system_updates.log';
    $errorEntry = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n" . str_repeat('-', 50) . "\n";
    file_put_contents($logFile, $errorEntry, FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during update: ' . $e->getMessage()
    ]);
}
?>