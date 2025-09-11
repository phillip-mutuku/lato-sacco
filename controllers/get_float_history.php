<?php
/**
 * Updated Float History Controller
 * Fetches all float reset history with support for multiple resets per day
 * Includes withdrawal fees tracking and enhanced data presentation
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Set timezone
date_default_timezone_set("Africa/Nairobi");

// Include required files
require_once '../helpers/session.php';
require_once '../config/class.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => '',
    'data' => array(),
    'summary' => array(),
    'meta' => array()
);

try {
    // Check if user is logged in and has appropriate permissions
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
        throw new Exception('Unauthorized access. Please log in with appropriate permissions.');
    }

    // Initialize database connection
    $db = new db_class();
    
    if (!$db->conn) {
        throw new Exception('Database connection failed.');
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method. Only GET requests are allowed.');
    }

    // Get optional parameters for customization
    $days_back = isset($_GET['days']) ? (int)$_GET['days'] : 30;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $specific_date = isset($_GET['date']) ? $_GET['date'] : null;

    // Validate parameters
    if ($days_back < 1 || $days_back > 365) {
        $days_back = 30; // Default to 30 days
    }
    
    if ($limit < 1 || $limit > 200) {
        $limit = 100; // Default to 100 records
    }

    // Calculate date range
    if ($specific_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $specific_date)) {
        $start_date = $specific_date;
        $end_date = $specific_date;
    } else {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$days_back} days"));
    }

    // Prepare the main query to fetch float history
    $query = "SELECT 
                fh.history_id,
                fh.date,
                fh.closing_float,
                fh.opening_float,
                fh.total_money_in,
                fh.total_money_out,
                fh.total_withdrawal_fees,
                fh.total_offloaded,
                fh.reset_reason,
                fh.notes,
                fh.created_at,
                u.username as reset_by,
                u.firstname,
                u.lastname,
                CASE 
                    WHEN u.firstname IS NOT NULL AND u.lastname IS NOT NULL 
                    THEN CONCAT(u.firstname, ' ', u.lastname)
                    WHEN u.username IS NOT NULL 
                    THEN u.username
                    ELSE 'System'
                END as reset_by_full_name
              FROM float_history fh
              LEFT JOIN user u ON fh.reset_by = u.user_id
              WHERE fh.date BETWEEN ? AND ?
              ORDER BY fh.date DESC, fh.created_at DESC
              LIMIT ?";

    $stmt = $db->conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare database query: ' . $db->conn->error);
    }

    // Bind parameters
    $stmt->bind_param("ssi", $start_date, $end_date, $limit);

    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute database query: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $history_data = array();
    $daily_summary = array();

    // Fetch and process results
    while ($row = $result->fetch_assoc()) {
        // Calculate net position for this reset
        $net_money_flow = ($row['total_money_in'] + $row['total_withdrawal_fees']) - $row['total_money_out'];
        
        // Format the data for frontend consumption
        $history_record = array(
            'history_id' => (int)$row['history_id'],
            'date' => $row['date'],
            'closing_float' => number_format((float)$row['closing_float'], 2, '.', ''),
            'closing_float_formatted' => number_format((float)$row['closing_float'], 2),
            'opening_float' => number_format((float)$row['opening_float'], 2, '.', ''),
            'opening_float_formatted' => number_format((float)$row['opening_float'], 2),
            'total_money_in' => number_format((float)$row['total_money_in'], 2, '.', ''),
            'total_money_in_formatted' => number_format((float)$row['total_money_in'], 2),
            'total_money_out' => number_format((float)$row['total_money_out'], 2, '.', ''),
            'total_money_out_formatted' => number_format((float)$row['total_money_out'], 2),
            'total_withdrawal_fees' => number_format((float)$row['total_withdrawal_fees'], 2, '.', ''),
            'total_withdrawal_fees_formatted' => number_format((float)$row['total_withdrawal_fees'], 2),
            'total_offloaded' => number_format((float)$row['total_offloaded'], 2, '.', ''),
            'total_offloaded_formatted' => number_format((float)$row['total_offloaded'], 2),
            'net_money_flow' => number_format($net_money_flow, 2, '.', ''),
            'net_money_flow_formatted' => number_format($net_money_flow, 2),
            'reset_reason' => $row['reset_reason'] ?: 'Manual Reset',
            'notes' => $row['notes'],
            'created_at' => $row['created_at'],
            'reset_by' => $row['reset_by'] ?: 'System',
            'reset_by_full_name' => $row['reset_by_full_name'],
            'formatted_date' => date('M d, Y', strtotime($row['date'])),
            'formatted_time' => date('H:i A', strtotime($row['created_at'])),
            'formatted_datetime' => date('M d, Y H:i A', strtotime($row['created_at']))
        );

        $history_data[] = $history_record;

        // Build daily summary
        $date_key = $row['date'];
        if (!isset($daily_summary[$date_key])) {
            $daily_summary[$date_key] = array(
                'date' => $date_key,
                'reset_count' => 0,
                'first_reset' => $row['created_at'],
                'last_reset' => $row['created_at'],
                'final_closing_float' => (float)$row['closing_float'],
                'total_fees_collected' => 0,
                'total_money_handled' => 0
            );
        }
        
        $daily_summary[$date_key]['reset_count']++;
        if ($row['created_at'] < $daily_summary[$date_key]['first_reset']) {
            $daily_summary[$date_key]['first_reset'] = $row['created_at'];
        }
        if ($row['created_at'] > $daily_summary[$date_key]['last_reset']) {
            $daily_summary[$date_key]['last_reset'] = $row['created_at'];
            $daily_summary[$date_key]['final_closing_float'] = (float)$row['closing_float'];
        }
        $daily_summary[$date_key]['total_fees_collected'] += (float)$row['total_withdrawal_fees'];
        $daily_summary[$date_key]['total_money_handled'] += (float)$row['total_money_in'] + (float)$row['total_money_out'];
    }

    $stmt->close();

    // Calculate comprehensive summary statistics
    $summary = array();
    if (!empty($history_data)) {
        $closing_amounts = array_column($history_data, 'closing_float');
        $closing_amounts = array_map('floatval', $closing_amounts);
        
        $total_fees = array_sum(array_map('floatval', array_column($history_data, 'total_withdrawal_fees')));
        $total_money_in = array_sum(array_map('floatval', array_column($history_data, 'total_money_in')));
        $total_money_out = array_sum(array_map('floatval', array_column($history_data, 'total_money_out')));
        
        $summary = array(
            'total_resets' => count($history_data),
            'unique_days' => count($daily_summary),
            'highest_closing' => max($closing_amounts),
            'lowest_closing' => min($closing_amounts),
            'average_closing' => round(array_sum($closing_amounts) / count($closing_amounts), 2),
            'total_fees_collected' => $total_fees,
            'total_money_in_period' => $total_money_in,
            'total_money_out_period' => $total_money_out,
            'net_money_flow_period' => $total_money_in + $total_fees - $total_money_out,
            'average_resets_per_day' => round(count($history_data) / max(1, count($daily_summary)), 2),
            'date_range' => array(
                'start' => $start_date,
                'end' => $end_date,
                'days' => $days_back
            ),
            'daily_breakdown' => array_values($daily_summary)
        );
    }

    // Get current float status for context
    $current_float_query = "SELECT 
                            COALESCE(SUM(CASE WHEN type = 'add' THEN amount ELSE 0 END), 0) as current_opening,
                            COALESCE(SUM(CASE WHEN type = 'offload' THEN amount ELSE 0 END), 0) as current_offloaded
                          FROM float_management";
    $stmt = $db->conn->prepare($current_float_query);
    $stmt->execute();
    $current_float = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Prepare successful response
    $response['success'] = true;
    $response['message'] = 'Float history retrieved successfully.';
    $response['data'] = $history_data;
    $response['summary'] = $summary;
    $response['current_status'] = array(
        'current_opening_float' => (float)$current_float['current_opening'],
        'current_offloaded' => (float)$current_float['current_offloaded'],
        'current_net_float' => (float)$current_float['current_opening'] - (float)$current_float['current_offloaded'],
        'last_reset' => !empty($history_data) ? $history_data[0]['created_at'] : null
    );
    $response['meta'] = array(
        'total_records' => count($history_data),
        'date_range_days' => $days_back,
        'query_limit' => $limit,
        'query_time' => date('Y-m-d H:i:s'),
        'timezone' => 'Africa/Nairobi'
    );

} catch (Exception $e) {
    // Log the error for debugging
    error_log('Float History Error: ' . $e->getMessage() . ' - User ID: ' . ($_SESSION['user_id'] ?? 'Unknown') . ' - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
    
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['data'] = array();
    
    // Set appropriate HTTP status code
    http_response_code(400);
    
} catch (Error $e) {
    // Handle fatal errors
    error_log('Float History Fatal Error: ' . $e->getMessage() . ' - File: ' . $e->getFile() . ' - Line: ' . $e->getLine());
    
    $response['success'] = false;
    $response['message'] = 'A system error occurred. Please contact support.';
    $response['data'] = array();
    
    http_response_code(500);
}

// Output JSON response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

// Ensure no additional output
exit();
?>