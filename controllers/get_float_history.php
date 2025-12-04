<?php
/**
 * Updated Float History Controller
 * Fetches all float reset history with unlimited storage and date range filtering
 * Includes withdrawal fees tracking and enhanced data presentation
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

date_default_timezone_set("Africa/Nairobi");

require_once '../helpers/session.php';
require_once '../config/class.php';

$response = array(
    'success' => false,
    'message' => '',
    'data' => array(),
    'summary' => array(),
    'meta' => array()
);

try {
    // Check if user is logged in 
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['error' => 'Unauthorized']));
}

    $db = new db_class();
    
    if (!$db->conn) {
        throw new Exception('Database connection failed.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method. Only GET requests are allowed.');
    }

    // Get filter parameters
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    $days_back = isset($_GET['days']) ? (int)$_GET['days'] : 30;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $specific_date = isset($_GET['date']) ? $_GET['date'] : null;

    // Validate parameters
    if ($days_back < 1 || $days_back > 365) {
        $days_back = 30;
    }
    
    if ($limit < 1 || $limit > 200) {
        $limit = 100;
    }

    if ($page < 1) {
        $page = 1;
    }

    $offset = ($page - 1) * $limit;

    // Calculate date range
    if ($start_date && $end_date) {
        // Use provided date range
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
            throw new Exception('Invalid date format. Use YYYY-MM-DD.');
        }
    } elseif ($specific_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $specific_date)) {
        $start_date = $specific_date;
        $end_date = $specific_date;
    } else {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$days_back} days"));
    }

    // Count total records for pagination
    $count_query = "SELECT COUNT(*) as total FROM float_history fh WHERE fh.date BETWEEN ? AND ?";
    $count_stmt = $db->conn->prepare($count_query);
    
    if (!$count_stmt) {
        throw new Exception('Failed to prepare count query: ' . $db->conn->error);
    }
    
    $count_stmt->bind_param("ss", $start_date, $end_date);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();

    // Prepare the main query
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
              LIMIT ? OFFSET ?";

    $stmt = $db->conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare database query: ' . $db->conn->error);
    }

    $stmt->bind_param("ssii", $start_date, $end_date, $limit, $offset);

    if (!$stmt->execute()) {
        throw new Exception('Failed to execute database query: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $history_data = array();
    $daily_summary = array();

    while ($row = $result->fetch_assoc()) {
        $net_money_flow = ($row['total_money_in'] + $row['total_withdrawal_fees']) - $row['total_money_out'];
        
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

    // Calculate comprehensive summary statistics for the filtered data
    $summary = array();
    if (!empty($history_data)) {
        // Get summary for all records in date range (not just current page)
        $summary_query = "SELECT 
                            COUNT(*) as total_resets,
                            COUNT(DISTINCT date) as unique_days,
                            MAX(closing_float) as highest_closing,
                            MIN(closing_float) as lowest_closing,
                            AVG(closing_float) as average_closing,
                            SUM(total_withdrawal_fees) as total_fees_collected,
                            SUM(total_money_in) as total_money_in,
                            SUM(total_money_out) as total_money_out
                          FROM float_history fh
                          WHERE fh.date BETWEEN ? AND ?";
        
        $summary_stmt = $db->conn->prepare($summary_query);
        $summary_stmt->bind_param("ss", $start_date, $end_date);
        $summary_stmt->execute();
        $summary_result = $summary_stmt->get_result()->fetch_assoc();
        $summary_stmt->close();
        
        $total_fees = (float)$summary_result['total_fees_collected'];
        $total_money_in = (float)$summary_result['total_money_in'];
        $total_money_out = (float)$summary_result['total_money_out'];
        
        $summary = array(
            'total_resets' => (int)$summary_result['total_resets'],
            'unique_days' => (int)$summary_result['unique_days'],
            'highest_closing' => (float)$summary_result['highest_closing'],
            'lowest_closing' => (float)$summary_result['lowest_closing'],
            'average_closing' => round((float)$summary_result['average_closing'], 2),
            'total_fees_collected' => $total_fees,
            'total_money_in_period' => $total_money_in,
            'total_money_out_period' => $total_money_out,
            'net_money_flow_period' => $total_money_in + $total_fees - $total_money_out,
            'average_resets_per_day' => $summary_result['unique_days'] > 0 ? 
                round((float)$summary_result['total_resets'] / (float)$summary_result['unique_days'], 2) : 0,
            'date_range' => array(
                'start' => $start_date,
                'end' => $end_date,
                'days' => (strtotime($end_date) - strtotime($start_date)) / 86400 + 1
            ),
            'daily_breakdown' => array_values($daily_summary)
        );
    }

    // Get current float status
    $current_float_query = "SELECT 
                            COALESCE(SUM(CASE WHEN type = 'add' THEN amount ELSE 0 END), 0) as current_opening,
                            COALESCE(SUM(CASE WHEN type = 'offload' THEN amount ELSE 0 END), 0) as current_offloaded
                          FROM float_management";
    $stmt = $db->conn->prepare($current_float_query);
    $stmt->execute();
    $current_float = $stmt->get_result()->fetch_assoc();
    $stmt->close();

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
    $response['pagination'] = array(
        'current_page' => $page,
        'total_records' => $total_records,
        'total_pages' => ceil($total_records / $limit),
        'limit' => $limit,
        'offset' => $offset
    );
    $response['filters'] = array(
        'start_date' => $start_date,
        'end_date' => $end_date,
        'date_range_days' => (strtotime($end_date) - strtotime($start_date)) / 86400 + 1
    );
    $response['meta'] = array(
        'total_records' => count($history_data),
        'query_limit' => $limit,
        'query_time' => date('Y-m-d H:i:s'),
        'timezone' => 'Africa/Nairobi'
    );

} catch (Exception $e) {
    error_log('Float History Error: ' . $e->getMessage() . ' - User ID: ' . ($_SESSION['user_id'] ?? 'Unknown') . ' - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
    
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['data'] = array();
    
    http_response_code(400);
    
} catch (Error $e) {
    error_log('Float History Fatal Error: ' . $e->getMessage() . ' - File: ' . $e->getFile() . ' - Line: ' . $e->getLine());
    
    $response['success'] = false;
    $response['message'] = 'A system error occurred. Please contact support.';
    $response['data'] = array();
    
    http_response_code(500);
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit();
?>