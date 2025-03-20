<?php
session_start();
require_once '../config/class.php';
$db = new db_class();

function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function updateGeneralSettings($companyName, $systemEmail, $defaultCurrency) {
    global $db;
    $companyName = validateInput($companyName);
    $systemEmail = validateInput($systemEmail);
    $defaultCurrency = validateInput($defaultCurrency);
    
    if (empty($companyName) || empty($systemEmail) || empty($defaultCurrency)) {
        return false;
    }
    
    $db->update_settings('company_name', $companyName);
    $db->update_settings('system_email', $systemEmail);
    $db->update_settings('default_currency', $defaultCurrency);
    return true;
}

function updateAppearanceSettings($darkMode) {
    global $db;
    $darkMode = validateInput($darkMode);
    $db->update_settings('dark_mode', $darkMode);
    return true;
}

function updateSecuritySettings($sessionTimeout, $maxLoginAttempts, $twoFactorAuth) {
    global $db;
    $sessionTimeout = validateInput($sessionTimeout);
    $maxLoginAttempts = validateInput($maxLoginAttempts);
    $twoFactorAuth = validateInput($twoFactorAuth);
    
    if (!is_numeric($sessionTimeout) || !is_numeric($maxLoginAttempts)) {
        return false;
    }
    
    $db->update_settings('session_timeout', $sessionTimeout);
    $db->update_settings('max_login_attempts', $maxLoginAttempts);
    $db->update_settings('two_factor_auth', $twoFactorAuth);
    
    // Update session timeout immediately
    ini_set('session.gc_maxlifetime', $sessionTimeout * 60);
    $_SESSION['LAST_ACTIVITY'] = time();
    
    return true;
}

function updateSystemSettings($maintenanceMode, $backupFrequency, $backupTime) {
    global $db;
    $maintenanceMode = validateInput($maintenanceMode);
    $backupFrequency = validateInput($backupFrequency);
    $backupTime = validateInput($backupTime);
    
    $db->update_settings('maintenance_mode', $maintenanceMode);
    $db->update_settings('backup_frequency', $backupFrequency);
    $db->update_settings('backup_time', $backupTime);
    
    // If maintenance mode is enabled, redirect all non-admin users
    if ($maintenanceMode == '1') {
        $_SESSION['maintenance_mode'] = true;
    } else {
        unset($_SESSION['maintenance_mode']);
    }
    
    // Update last settings change time
    $db->update_settings('last_settings_change', date('Y-m-d H:i:s'));
    
    return true;
}

function getSettings() {
    global $db;
    return $db->get_all_settings();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? validateInput($_POST['action']) : '';
    $response = ['success' => false, 'message' => ''];
    
    switch ($action) {
        case 'general':
            $result = updateGeneralSettings($_POST['companyName'], $_POST['systemEmail'], $_POST['defaultCurrency']);
            $response['success'] = $result;
            $response['message'] = $result ? 'General settings updated successfully.' : 'Failed to update general settings.';
            break;
        case 'appearance':
            $result = updateAppearanceSettings($_POST['darkMode']);
            $response['success'] = $result;
            $response['message'] = $result ? 'Appearance settings updated successfully.' : 'Failed to update appearance settings.';
            break;
        case 'security':
            $result = updateSecuritySettings($_POST['sessionTimeout'], $_POST['maxLoginAttempts'], $_POST['twoFactorAuth']);
            $response['success'] = $result;
            $response['message'] = $result ? 'Security settings updated successfully.' : 'Failed to update security settings.';
            break;
        case 'system':
            $result = updateSystemSettings($_POST['maintenanceMode'], $_POST['backupFrequency'], $_POST['backupTime']);
            $response['success'] = $result;
            $response['message'] = $result ? 'System settings updated successfully.' : 'Failed to update system settings.';
            break;
        case 'get_settings':
            $settings = getSettings();
            $response['success'] = true;
            $response['data'] = $settings;
            break;
        default:
            $response['message'] = 'Invalid action.';
    }
    
    echo json_encode($response);
    exit;
}
?>