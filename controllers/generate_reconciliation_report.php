<?php
// controllers/generate_reconciliation_report.php
// Prevent any output before PDF generation
ob_start();

require_once '../config/class.php';
require_once '../helpers/fpdf/fpdf.php';
require_once '../helpers/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    ob_end_clean();
    die('Unauthorized access');
}

class DailyReconciliationPDF extends FPDF {
    protected $start_date;
    protected $end_date;
    protected $reconciled_by;
    protected $float_filter;

    function setFilters($start_date, $end_date, $reconciled_by, $float_filter = 'all') {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->reconciled_by = $reconciled_by;
        $this->float_filter = $float_filter;
    }

    function Header() {
        // Company Header with LATO SACCO styling
        $this->SetFillColor(81, 8, 126); // #51087E
        $this->Rect(0, 0, 297, 25, 'F'); // Full width colored header
        
        // Logo (if available)
        if (file_exists('../public/image/logo.jpg')) {
            $this->Image('../public/image/logo.jpg', 15, 5, 20);
        }
        
        // Company Info
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial','B', 18);
        $this->SetXY(45, 8);
        $this->Cell(200, 8, 'LATO SACCO LIMITED', 0, 1, 'L');
        
        $this->SetFont('Arial','', 12);
        $this->SetXY(45, 16);
        $this->Cell(200, 6, 'Daily Reconciliation Report', 0, 1, 'L');
        
        // Report Details Box
        $this->SetY(30);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial','B', 11);
        
        // Report info box
        $this->SetFillColor(248, 249, 252);
        $this->Rect(15, 30, 267, 35, 'F');
        $this->SetXY(20, 35);
        
        // Date Range
        $formatted_start = date('M d, Y', strtotime($this->start_date));
        $formatted_end = date('M d, Y', strtotime($this->end_date));
        $period_text = "Reconciliation Period: $formatted_start to $formatted_end";
        $this->Cell(130, 6, $period_text, 0, 0, 'L');
        
        // Float Filter
        $float_text = $this->float_filter === 'all' 
            ? 'Float Filter: All Types' 
            : 'Float Filter: ' . ucfirst($this->float_filter) . ' Only';
        $this->Cell(110, 6, $float_text, 0, 1, 'R');
        
        $this->SetXY(20, 42);
        $this->Cell(130, 6, 'Generated: ' . date('M d, Y H:i:s'), 0, 0, 'L');
        $this->Cell(110, 6, 'Reconciled by: ' . $this->reconciled_by, 0, 1, 'R');
        
        $this->SetXY(20, 49);
        $this->SetFont('Arial','', 9);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(130, 6, 'System: LATO Management System', 0, 0, 'L');
        $this->Cell(110, 6, 'Includes: All financial transactions for the period', 0, 1, 'R');
        
        $this->SetXY(20, 56);
        $this->Cell(130, 6, 'Status: ' . (date('Y-m-d') === date('Y-m-d', strtotime($this->end_date)) ? 'Current Day' : 'Historical'), 0, 0, 'L');
        $this->Cell(110, 6, 'Currency: Kenya Shillings (KSh)', 0, 1, 'R');
        
        $this->Ln(12);
    }
    
    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial','I', 8);
        $this->SetTextColor(128, 128, 128);
        
        // Footer line
        $this->Line(15, $this->GetY(), 282, $this->GetY());
        $this->Ln(3);
        
        $this->Cell(0, 6, 'LATO SACCO LIMITED - Daily Reconciliation Report - Confidential', 0, 0, 'L');
        $this->Cell(0, 6, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }

    function ExecutiveSummary($summary_data) {
        $this->SetFont('Arial','B', 14);
        $this->SetTextColor(81, 8, 126);
        $this->Cell(0, 10, 'EXECUTIVE SUMMARY', 0, 1, 'L');
        $this->Ln(5);
        
        // Summary boxes in a structured layout
        $box_width = 65;
        $this->SetFont('Arial','B', 10);
        $this->SetFillColor(245, 245, 245);
        
        // Row 1 - Main Totals
        $this->Cell($box_width, 8, 'Total Money In:', 1, 0, 'L', true);
        $this->SetFont('Arial','', 10);
        $this->SetTextColor(0, 128, 0); // Green for income
        $this->Cell($box_width, 8, 'KSh ' . number_format($summary_data['total_inflows'], 2), 1, 0, 'R');
        $this->SetTextColor(0, 0, 0);
        
        $this->SetFont('Arial','B', 10);
        $this->Cell($box_width, 8, 'Total Money Out:', 1, 0, 'L', true);
        $this->SetFont('Arial','', 10);
        $this->SetTextColor(220, 53, 69); // Red for outflows
        $this->Cell($box_width, 8, 'KSh ' . number_format($summary_data['total_outflows'], 2), 1, 1, 'R');
        $this->SetTextColor(0, 0, 0);
        
        // Row 2 - Float Management
        $this->SetFont('Arial','B', 10);
        $this->Cell($box_width, 8, 'Opening Float:', 1, 0, 'L', true);
        $this->SetFont('Arial','', 10);
        $this->Cell($box_width, 8, 'KSh ' . number_format($summary_data['opening_float'], 2), 1, 0, 'R');
        
        $this->SetFont('Arial','B', 10);
        $this->Cell($box_width, 8, 'Closing Float:', 1, 0, 'L', true);
        $this->SetFont('Arial','', 10);
        $this->Cell($box_width, 8, 'KSh ' . number_format($summary_data['closing_float'], 2), 1, 1, 'R');
        
        // Row 3 - Net Position & Analysis
        $this->SetFont('Arial','B', 10);
        $this->Cell($box_width, 8, 'Net Position:', 1, 0, 'L', true);
        $this->SetFont('Arial','', 10);
        $net_color = $summary_data['net_position'] >= 0 ? [0, 128, 0] : [220, 53, 69];
        $this->SetTextColor($net_color[0], $net_color[1], $net_color[2]);
        $this->Cell($box_width, 8, 'KSh ' . number_format($summary_data['net_position'], 2), 1, 0, 'R');
        $this->SetTextColor(0, 0, 0);
        
        $this->SetFont('Arial','B', 10);
        $this->Cell($box_width, 8, 'Total Transactions:', 1, 0, 'L', true);
        $this->SetFont('Arial','', 10);
        $this->Cell($box_width, 8, number_format($summary_data['total_transactions']), 1, 1, 'R');
        
        $this->Ln(10);
    }

    function FloatManagementSection($float_data) {
        $this->SetFont('Arial','B', 12);
        $this->SetTextColor(81, 8, 126);
        $this->Cell(0, 10, 'FLOAT MANAGEMENT SUMMARY', 0, 1, 'L');
        $this->Ln(3);
        
        // Float summary table
        $this->SetFont('Arial','B', 10);
        $this->SetFillColor(248, 249, 252);
        $this->SetTextColor(0, 0, 0);
        
        $this->Cell(60, 8, 'Float Type', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Transactions', 1, 0, 'C', true);
        $this->Cell(50, 8, 'Amount (KSh)', 1, 0, 'C', true);
        $this->Cell(112, 8, 'Notes', 1, 1, 'C', true);
        
        $this->SetFont('Arial','', 10);
        $this->SetFillColor(255, 255, 255);
        
        // Opening Float
        $this->Cell(60, 7, 'Opening Float (Added)', 1, 0, 'L');
        $this->Cell(40, 7, number_format($float_data['add_count']), 1, 0, 'C');
        $this->SetTextColor(0, 128, 0);
        $this->Cell(50, 7, number_format($float_data['total_added'], 2), 1, 0, 'R');
        $this->SetTextColor(0, 0, 0);
        $this->Cell(112, 7, 'Cash added to float for operations', 1, 1, 'L');
        
        // Offloaded Float
        $this->Cell(60, 7, 'Offloaded Float', 1, 0, 'L');
        $this->Cell(40, 7, number_format($float_data['offload_count']), 1, 0, 'C');
        $this->SetTextColor(220, 53, 69);
        $this->Cell(50, 7, number_format($float_data['total_offloaded'], 2), 1, 0, 'R');
        $this->SetTextColor(0, 0, 0);
        $this->Cell(112, 7, 'Cash removed from float', 1, 1, 'L');
        
        // Net Float
        $this->SetFont('Arial','B', 10);
        $this->SetFillColor(245, 245, 245);
        $this->Cell(60, 8, 'Closing Float Balance', 1, 0, 'L', true);
        $this->Cell(40, 8, number_format($float_data['total_transactions']), 1, 0, 'C', true);
        $net_float_color = $float_data['closing_float'] >= 0 ? [0, 128, 0] : [220, 53, 69];
        $this->SetTextColor($net_float_color[0], $net_float_color[1], $net_float_color[2]);
        $this->Cell(50, 8, number_format($float_data['closing_float'], 2), 1, 0, 'R', true);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(112, 8, 'Available cash for operations', 1, 1, 'L', true);
        
        $this->Ln(8);
    }

    function MoneyInTableHeader() {
        $this->SetFont('Arial','B', 8);
        $this->SetFillColor(40, 167, 69); // Green for money in
        $this->SetTextColor(255, 255, 255);
        
        $headers = [
            ['text' => 'Date', 'width' => 25],
            ['text' => 'Transaction Type', 'width' => 35],
            ['text' => 'Client/Group Name', 'width' => 55],
            ['text' => 'Amount (KSh)', 'width' => 30],
            ['text' => 'Payment Mode', 'width' => 25],
            ['text' => 'Receipt No', 'width' => 25],
            ['text' => 'Served By', 'width' => 37]
        ];
        
        foreach ($headers as $header) {
            $this->Cell($header['width'], 8, $header['text'], 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetTextColor(0, 0, 0);
    }

    function MoneyOutTableHeader() {
        $this->SetFont('Arial','B', 8);
        $this->SetFillColor(220, 53, 69); // Red for money out
        $this->SetTextColor(255, 255, 255);
        
        $headers = [
            ['text' => 'Date', 'width' => 25],
            ['text' => 'Transaction Type', 'width' => 35],
            ['text' => 'Client/Group Name', 'width' => 50],
            ['text' => 'Amount (KSh)', 'width' => 28],
            ['text' => 'Fee (KSh)', 'width' => 22],
            ['text' => 'Payment Mode', 'width' => 25],
            ['text' => 'Served By', 'width' => 37]
        ];
        
        foreach ($headers as $header) {
            $this->Cell($header['width'], 8, $header['text'], 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetTextColor(0, 0, 0);
    }

    function MoneyInTableRow($data, $is_even = false) {
        $this->SetFont('Arial','', 7);
        
        // Alternate row colors
        if ($is_even) {
            $this->SetFillColor(248, 249, 252);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        
        $this->Cell(25, 7, date('M d, Y', strtotime($data['date'])), 1, 0, 'C', true);
        $this->Cell(35, 7, substr($data['type'], 0, 18), 1, 0, 'L', true);
        $this->Cell(55, 7, substr($data['client_name'], 0, 30), 1, 0, 'L', true);
        
        // Amount in green
        $this->SetTextColor(0, 128, 0);
        $this->Cell(30, 7, number_format($data['amount'], 2), 1, 0, 'R', true);
        $this->SetTextColor(0, 0, 0);
        
        $this->Cell(25, 7, substr($data['payment_mode'] ?? 'Cash', 0, 12), 1, 0, 'C', true);
        $this->Cell(25, 7, substr($data['receipt_no'] ?? 'N/A', 0, 12), 1, 0, 'C', true);
        $this->Cell(37, 7, substr($data['served_by'], 0, 18), 1, 1, 'L', true);
    }

    function MoneyOutTableRow($data, $is_even = false) {
        $this->SetFont('Arial','', 7);
        
        // Alternate row colors
        if ($is_even) {
            $this->SetFillColor(248, 249, 252);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        
        $this->Cell(25, 7, date('M d, Y', strtotime($data['date'])), 1, 0, 'C', true);
        $this->Cell(35, 7, substr($data['type'], 0, 18), 1, 0, 'L', true);
        $this->Cell(50, 7, substr($data['client_name'], 0, 28), 1, 0, 'L', true);
        
        // Amount in red
        $this->SetTextColor(220, 53, 69);
        $this->Cell(28, 7, number_format($data['amount'], 2), 1, 0, 'R', true);
        $this->SetTextColor(0, 0, 0);
        
        // Fee (if applicable)
        $this->Cell(22, 7, number_format($data['fee'] ?? 0, 2), 1, 0, 'R', true);
        $this->Cell(25, 7, substr($data['payment_mode'] ?? 'Cash', 0, 12), 1, 0, 'C', true);
        $this->Cell(37, 7, substr($data['served_by'], 0, 18), 1, 1, 'L', true);
    }

    function FloatTransactionRow($data, $is_even = false) {
        $this->SetFont('Arial','', 7);
        
        // Alternate row colors
        if ($is_even) {
            $this->SetFillColor(248, 249, 252);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        
        $this->Cell(25, 7, date('M d, Y', strtotime($data['date'])), 1, 0, 'C', true);
        $this->Cell(25, 7, substr($data['receipt_no'], 0, 12), 1, 0, 'C', true);
        
        // Transaction type with color
        $type_color = $data['type'] === 'add' ? [0, 128, 0] : [220, 53, 69];
        $this->SetTextColor($type_color[0], $type_color[1], $type_color[2]);
        $this->Cell(30, 7, ucfirst($data['type']) . ' Float', 1, 0, 'C', true);
        $this->SetTextColor(0, 0, 0);
        
        // Amount with color
        $this->SetTextColor($type_color[0], $type_color[1], $type_color[2]);
        $this->Cell(30, 7, number_format($data['amount'], 2), 1, 0, 'R', true);
        $this->SetTextColor(0, 0, 0);
        
        $this->Cell(37, 7, substr($data['served_by'], 0, 18), 1, 0, 'L', true);
        $this->Cell(25, 7, date('H:i', strtotime($data['date'])), 1, 0, 'C', true);
        $this->Cell(60, 7, 'Float management transaction', 1, 1, 'L', true);
    }

    function TotalRow($label, $total_amount, $record_count, $color_rgb, $include_fee = false, $fee_amount = 0) {
        $this->SetFont('Arial','B', 8);
        $this->SetFillColor($color_rgb[0], $color_rgb[1], $color_rgb[2]);
        $this->SetTextColor(255, 255, 255);
        
        if ($include_fee) {
            $this->Cell(60, 9, $label . ' TOTALS', 1, 0, 'L', true);
            $this->Cell(50, 9, $record_count . ' Records', 1, 0, 'C', true);
            $this->Cell(28, 9, 'KSh ' . number_format($total_amount, 2), 1, 0, 'R', true);
            $this->Cell(22, 9, 'KSh ' . number_format($fee_amount, 2), 1, 0, 'R', true);
            $this->Cell(62, 9, '', 1, 1, 'C', true); // Filler
        } else {
            $this->Cell(60, 9, $label . ' TOTALS', 1, 0, 'L', true);
            $this->Cell(50, 9, $record_count . ' Records', 1, 0, 'C', true);
            $this->Cell(30, 9, 'KSh ' . number_format($total_amount, 2), 1, 0, 'R', true);
            $this->Cell(92, 9, '', 1, 1, 'C', true); // Filler
        }
        
        $this->SetTextColor(0, 0, 0);
    }
}

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$float_filter = isset($_GET['float_filter']) ? $_GET['float_filter'] : 'all';

// Initialize database
$db = new db_class();

// Get user name
$user_id = $_SESSION['user_id'];
$user_query = "SELECT username FROM user WHERE user_id = ?";
$stmt = $db->conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_name = $user_result->fetch_assoc()['username'] ?? 'System User';

// Convert dates for queries
$query_start = $start_date . " 00:00:00";
$query_end = $end_date . " 23:59:59";

// Get Float Management Data
function getFloatData($db, $start, $end, $filter = 'all') {
    $filter_condition = '';
    if ($filter !== 'all') {
        $filter_condition = " AND type = '$filter'";
    }
    
    $query = "SELECT 
        SUM(CASE WHEN type = 'add' THEN amount ELSE 0 END) as total_added,
        SUM(CASE WHEN type = 'offload' THEN amount ELSE 0 END) as total_offloaded,
        SUM(CASE WHEN type = 'add' THEN 1 ELSE 0 END) as add_count,
        SUM(CASE WHEN type = 'offload' THEN 1 ELSE 0 END) as offload_count,
        COUNT(*) as total_transactions
        FROM float_management 
        WHERE date_created BETWEEN ? AND ?";
    
    if ($filter !== 'all') {
        $query .= $filter_condition;
    }
    
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $result['closing_float'] = ($result['total_added'] ?? 0) - ($result['total_offloaded'] ?? 0);
    
    return $result;
}

// Get detailed float transactions
function getFloatTransactions($db, $start, $end, $filter = 'all') {
    $filter_condition = '';
    $params = [$start, $end];
    
    if ($filter !== 'all') {
        $filter_condition = " AND f.type = ?";
        $params[] = $filter;
    }
    
    $query = "SELECT f.*, u.username as served_by 
              FROM float_management f 
              LEFT JOIN user u ON f.user_id = u.user_id 
              WHERE f.date_created BETWEEN ? AND ?" . $filter_condition . "
              ORDER BY f.date_created DESC";
    
    $stmt = $db->conn->prepare($query);
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    $transactions = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = [
            'date' => $row['date_created'],
            'receipt_no' => $row['receipt_no'],
            'type' => $row['type'],
            'amount' => $row['amount'],
            'served_by' => $row['served_by'] ?? 'Unknown'
        ];
    }
    
    return $transactions;
}

// Get Money In transactions with full names
function getMoneyInTransactions($db, $start, $end) {
    $transactions = [];
    
    // Group Savings with full group names
    $query = "SELECT gs.*, lg.group_name, u.username as served_by_name
              FROM group_savings gs 
              LEFT JOIN lato_groups lg ON gs.group_id = lg.group_id 
              LEFT JOIN user u ON gs.served_by = u.user_id
              WHERE gs.date_saved BETWEEN ? AND ?
              ORDER BY gs.date_saved DESC";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = [
            'date' => $row['date_saved'],
            'type' => 'Group Savings',
            'client_name' => $row['group_name'] ?? 'Unknown Group',
            'amount' => $row['amount'],
            'payment_mode' => $row['payment_mode'] ?? 'Cash',
            'receipt_no' => $row['receipt_no'] ?? 'N/A',
            'served_by' => $row['served_by_name'] ?? 'Unknown'
        ];
    }
    
    // Business Group Savings with full business group names
    $query = "SELECT bgt.*, bg.group_name, u.username as served_by_name
              FROM business_group_transactions bgt
              LEFT JOIN business_groups bg ON bgt.group_id = bg.group_id
              LEFT JOIN user u ON bgt.served_by = u.user_id
              WHERE bgt.type = 'Savings' AND bgt.date BETWEEN ? AND ?
              ORDER BY bgt.date DESC";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = [
            'date' => $row['date'],
            'type' => 'Business Savings',
            'client_name' => $row['group_name'] ?? 'Unknown Business Group',
            'amount' => $row['amount'],
            'payment_mode' => $row['payment_mode'] ?? 'Cash',
            'receipt_no' => $row['receipt_no'] ?? 'N/A',
            'served_by' => $row['served_by_name'] ?? 'Unknown'
        ];
    }
    
    // Individual Savings with full client names
    $query = "SELECT s.*, 
                     CONCAT(a.first_name, ' ', a.last_name) as client_full_name,
                     u.username as served_by_name
              FROM savings s
              LEFT JOIN client_accounts a ON s.account_id = a.account_id
              LEFT JOIN user u ON s.served_by = u.user_id
              WHERE s.type = 'Savings' AND s.date BETWEEN ? AND ?
              ORDER BY s.date DESC";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = [
            'date' => $row['date'],
            'type' => 'Individual Savings',
            'client_name' => $row['client_full_name'] ?? 'Unknown Client',
            'amount' => $row['amount'],
            'payment_mode' => $row['payment_mode'] ?? 'Cash',
            'receipt_no' => $row['receipt_number'] ?? 'N/A',
            'served_by' => $row['served_by_name'] ?? 'Unknown'
        ];
    }
    
    // Loan Repayments with borrower names and loan reference
    $query = "SELECT lr.*, 
                     l.ref_no,
                     CONCAT(ca.first_name, ' ', ca.last_name) as borrower_name,
                     u.username as served_by_name
              FROM loan_repayments lr 
              LEFT JOIN loan l ON lr.loan_id = l.loan_id 
              LEFT JOIN client_accounts ca ON l.account_id = ca.account_id
              LEFT JOIN user u ON lr.served_by = u.user_id
              WHERE lr.date_paid BETWEEN ? AND ?
              ORDER BY lr.date_paid DESC";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $client_display = ($row['borrower_name'] ?? 'Unknown Borrower');
        if (!empty($row['ref_no'])) {
            $client_display .= ' [' . $row['ref_no'] . ']';
        }
        
        $transactions[] = [
            'date' => $row['date_paid'],
            'type' => 'Loan Repayment',
            'client_name' => $client_display,
            'amount' => $row['amount_repaid'],
            'payment_mode' => $row['payment_mode'] ?? 'Cash',
            'receipt_no' => $row['receipt_number'] ?? 'N/A',
            'served_by' => $row['served_by_name'] ?? 'Unknown'
        ];
    }
    
    // Money Received
    $query = "SELECT e.*, u.username as created_by_name
              FROM expenses e 
              LEFT JOIN user u ON e.created_by = u.user_id
              WHERE e.status = 'received' AND e.date BETWEEN ? AND ?
              ORDER BY e.date DESC";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $client_display = ($row['category'] ?? 'Income');
        if (!empty($row['description'])) {
            $client_display .= ' - ' . substr($row['description'], 0, 30);
        }
        
        $transactions[] = [
            'date' => $row['date'],
            'type' => 'Money Received',
            'client_name' => $client_display,
            'amount' => abs($row['amount']),
            'payment_mode' => $row['payment_method'] ?? 'Cash',
            'receipt_no' => $row['receipt_no'] ?? 'N/A',
            'served_by' => $row['created_by_name'] ?? 'Unknown'
        ];
    }
    
    // Sort by date descending
    usort($transactions, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return $transactions;
}

// Get Money Out transactions with full names
function getMoneyOutTransactions($db, $start, $end) {
    $transactions = [];
    
    // Group Withdrawals with full group names
    $query = "SELECT gw.*, lg.group_name, u.username as served_by_name
              FROM group_withdrawals gw
              LEFT JOIN lato_groups lg ON gw.group_id = lg.group_id
              LEFT JOIN user u ON gw.served_by = u.user_id
              WHERE gw.date_withdrawn BETWEEN ? AND ?
              ORDER BY gw.date_withdrawn DESC";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = [
            'date' => $row['date_withdrawn'],
            'type' => 'Group Withdrawal',
            'client_name' => $row['group_name'] ?? 'Unknown Group',
            'amount' => $row['amount'],
            'fee' => $row['withdrawal_fee'] ?? 0,
            'payment_mode' => $row['payment_mode'] ?? 'Cash',
            'receipt_no' => $row['receipt_no'] ?? 'N/A',
            'served_by' => $row['served_by_name'] ?? 'Unknown'
        ];
    }
    
    // Business Group Withdrawals with full business group names
    $query = "SELECT bgt.*, bg.group_name, u.username as served_by_name
              FROM business_group_transactions bgt
              LEFT JOIN business_groups bg ON bgt.group_id = bg.group_id
              LEFT JOIN user u ON bgt.served_by = u.user_id
              WHERE bgt.type IN ('Withdrawal', 'Withdrawal Fee') AND bgt.date BETWEEN ? AND ?
              ORDER BY bgt.date DESC";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $business_withdrawals = [];
    while ($row = $result->fetch_assoc()) {
        $ref = $row['group_name'] . '_' . date('Ymd', strtotime($row['date']));
        if (!isset($business_withdrawals[$ref])) {
            $business_withdrawals[$ref] = [
                'date' => $row['date'],
                'type' => 'Business Withdrawal',
                'client_name' => $row['group_name'] ?? 'Unknown Business Group',
                'amount' => 0,
                'fee' => 0,
                'payment_mode' => $row['payment_mode'] ?? 'Cash',
                'receipt_no' => $row['receipt_no'] ?? 'N/A',
                'served_by' => $row['served_by_name'] ?? 'Unknown'
            ];
        }
        
        if ($row['type'] === 'Withdrawal') {
            $business_withdrawals[$ref]['amount'] = $row['amount'];
        } elseif ($row['type'] === 'Withdrawal Fee') {
            $business_withdrawals[$ref]['fee'] = $row['amount'];
        }
    }
    $transactions = array_merge($transactions, array_values($business_withdrawals));
    
    // Individual Withdrawals with full client names
    $query = "SELECT s.*, 
                     CONCAT(a.first_name, ' ', a.last_name) as client_full_name,
                     u.username as served_by_name
              FROM savings s
              LEFT JOIN client_accounts a ON s.account_id = a.account_id
              LEFT JOIN user u ON s.served_by = u.user_id
              WHERE s.type = 'Withdrawal' AND s.date BETWEEN ? AND ?
              ORDER BY s.date DESC";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = [
            'date' => $row['date'],
            'type' => 'Individual Withdrawal',
            'client_name' => $row['client_full_name'] ?? 'Unknown Client',
            'amount' => $row['amount'],
            'fee' => $row['withdrawal_fee'] ?? 0,
            'payment_mode' => $row['payment_mode'] ?? 'Cash',
            'receipt_no' => $row['receipt_number'] ?? 'N/A',
            'served_by' => $row['served_by_name'] ?? 'Unknown'
        ];
    }
    
    // Loan Disbursements with borrower names
    $query = "SELECT p.*, 
                     l.ref_no, 
                     CONCAT(ca.first_name, ' ', ca.last_name) as borrower_name,
                     u.username as disbursed_by 
              FROM payment p 
              LEFT JOIN loan l ON p.loan_id = l.loan_id 
              LEFT JOIN client_accounts ca ON l.account_id = ca.account_id
              LEFT JOIN user u ON p.user_id = u.user_id
              WHERE p.date_created BETWEEN ? AND ?
              ORDER BY p.date_created DESC";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $client_display = ($row['borrower_name'] ?? $row['payee'] ?? 'Unknown Borrower');
        if (!empty($row['ref_no'])) {
            $client_display .= ' [' . $row['ref_no'] . ']';
        }
        
        $transactions[] = [
            'date' => $row['date_created'],
            'type' => 'Loan Disbursement',
            'client_name' => $client_display,
            'amount' => $row['pay_amount'],
            'fee' => $row['withdrawal_fee'] ?? 0,
            'payment_mode' => 'Bank Transfer',
            'receipt_no' => $row['receipt_no'] ?? 'N/A',
            'served_by' => $row['disbursed_by'] ?? 'Unknown'
        ];
    }
    
    // Expenses
    $query = "SELECT e.*, u.username as created_by_name
              FROM expenses e 
              LEFT JOIN user u ON e.created_by = u.user_id
              WHERE (e.status = 'completed' OR e.status IS NULL) AND e.date BETWEEN ? AND ?
              ORDER BY e.date DESC";
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $client_display = ($row['category'] ?? 'Expense');
        if (!empty($row['description'])) {
            $client_display .= ' - ' . substr($row['description'], 0, 20);
        }
        
        $transactions[] = [
            'date' => $row['date'],
            'type' => 'Expense',
            'client_name' => $client_display,
            'amount' => abs($row['amount']),
            'fee' => 0,
            'payment_mode' => $row['payment_method'] ?? 'Cash',
            'receipt_no' => $row['receipt_no'] ?? 'N/A',
            'served_by' => $row['created_by_name'] ?? 'Unknown'
        ];
    }
    
    // Sort by date descending
    usort($transactions, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return $transactions;
}

// Calculate totals
function calculateTotals($money_in, $money_out) {
    $total_inflows = array_sum(array_column($money_in, 'amount'));
    $total_outflows = array_sum(array_column($money_out, 'amount'));
    $total_fees = array_sum(array_column($money_out, 'fee'));
    
    return [
        'total_inflows' => $total_inflows,
        'total_outflows' => $total_outflows,
        'total_fees' => $total_fees,
        'net_position' => $total_inflows - $total_outflows,
        'total_transactions' => count($money_in) + count($money_out)
    ];
}

// Get all data
$float_data = getFloatData($db, $query_start, $query_end, $float_filter);
$float_transactions = getFloatTransactions($db, $query_start, $query_end, $float_filter);
$money_in_transactions = getMoneyInTransactions($db, $query_start, $query_end);
$money_out_transactions = getMoneyOutTransactions($db, $query_start, $query_end);

// Calculate summary data
$totals = calculateTotals($money_in_transactions, $money_out_transactions);
$summary_data = array_merge($totals, [
    'opening_float' => $float_data['total_added'] ?? 0,
    'closing_float' => $float_data['closing_float'] ?? 0
]);

// Initialize PDF
$pdf = new DailyReconciliationPDF('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->setFilters($start_date, $end_date, $user_name, $float_filter);

// Start generating PDF
$pdf->AddPage();

// Add executive summary
$pdf->ExecutiveSummary($summary_data);

// Add float management section
$pdf->FloatManagementSection($float_data);

// Float Transactions Detail (if any)
if (!empty($float_transactions)) {
    $pdf->SetFont('Arial','B', 12);
    $pdf->SetTextColor(81, 8, 126);
    $pdf->Cell(0, 10, 'FLOAT TRANSACTIONS DETAIL', 0, 1, 'L');
    $pdf->Ln(3);
    
    // Float transactions header
    $pdf->SetFont('Arial','B', 8);
    $pdf->SetFillColor(106, 27, 153); // Purple for float
    $pdf->SetTextColor(255, 255, 255);
    
    $headers = [
        ['text' => 'Date', 'width' => 25],
        ['text' => 'Receipt No', 'width' => 25],
        ['text' => 'Type', 'width' => 30],
        ['text' => 'Amount (KSh)', 'width' => 30],
        ['text' => 'Served By', 'width' => 37],
        ['text' => 'Time', 'width' => 25],
        ['text' => 'Notes', 'width' => 60]
    ];
    
    foreach ($headers as $header) {
        $pdf->Cell($header['width'], 8, $header['text'], 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetTextColor(0, 0, 0);
    
    // Float transaction rows
    $row_count = 0;
    foreach ($float_transactions as $transaction) {
        if ($pdf->GetY() > 180) {
            $pdf->AddPage();
            // Re-add header
            $pdf->SetFont('Arial','B', 8);
            $pdf->SetFillColor(106, 27, 153);
            $pdf->SetTextColor(255, 255, 255);
            foreach ($headers as $header) {
                $pdf->Cell($header['width'], 8, $header['text'], 1, 0, 'C', true);
            }
            $pdf->Ln();
            $pdf->SetTextColor(0, 0, 0);
        }
        
        $pdf->FloatTransactionRow($transaction, $row_count % 2 == 0);
        $row_count++;
    }
    
    // Add float totals
    if ($pdf->GetY() > 175) {
        $pdf->AddPage();
    }
    $pdf->Ln(3);
    
    $pdf->SetFont('Arial','B', 8);
    $pdf->SetFillColor(106, 27, 153);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(60, 9, 'FLOAT TOTALS', 1, 0, 'L', true);
    $pdf->Cell(50, 9, count($float_transactions) . ' Transactions', 1, 0, 'C', true);
    $pdf->Cell(30, 9, 'Net: KSh ' . number_format($float_data['closing_float'], 2), 1, 0, 'R', true);
    $pdf->Cell(92, 9, 'Added: KSh ' . number_format($float_data['total_added'], 2) . ' | Offloaded: KSh ' . number_format($float_data['total_offloaded'], 2), 1, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Ln(10);
}

// Money In Section
if (!empty($money_in_transactions)) {
    $pdf->SetFont('Arial','B', 12);
    $pdf->SetTextColor(40, 167, 69);
    $pdf->Cell(0, 10, 'MONEY IN TRANSACTIONS DETAIL', 0, 1, 'L');
    $pdf->Ln(3);
    
    $pdf->MoneyInTableHeader();
    
    $row_count = 0;
    foreach ($money_in_transactions as $transaction) {
        // Check if we need a new page
        if ($pdf->GetY() > 180) {
            $pdf->AddPage();
            $pdf->MoneyInTableHeader();
        }
        
        $pdf->MoneyInTableRow($transaction, $row_count % 2 == 0);
        $row_count++;
    }
    
    // Add money in totals
    if ($pdf->GetY() > 175) {
        $pdf->AddPage();
    }
    $pdf->Ln(3);
    $pdf->TotalRow('MONEY IN', $totals['total_inflows'], count($money_in_transactions), [40, 167, 69]);
    $pdf->Ln(10);
}

// Money Out Section
if (!empty($money_out_transactions)) {
    $pdf->SetFont('Arial','B', 12);
    $pdf->SetTextColor(220, 53, 69);
    $pdf->Cell(0, 10, 'MONEY OUT TRANSACTIONS DETAIL', 0, 1, 'L');
    $pdf->Ln(3);
    
    $pdf->MoneyOutTableHeader();
    
    $row_count = 0;
    foreach ($money_out_transactions as $transaction) {
        // Check if we need a new page
        if ($pdf->GetY() > 180) {
            $pdf->AddPage();
            $pdf->MoneyOutTableHeader();
        }
        
        $pdf->MoneyOutTableRow($transaction, $row_count % 2 == 0);
        $row_count++;
    }
    
    // Add money out totals
    if ($pdf->GetY() > 175) {
        $pdf->AddPage();
    }
    $pdf->Ln(3);
    $pdf->TotalRow('MONEY OUT', $totals['total_outflows'], count($money_out_transactions), [220, 53, 69], true, $totals['total_fees']);
}

// Final Summary Page
$pdf->AddPage();
$pdf->SetFont('Arial','B', 14);
$pdf->SetTextColor(81, 8, 126);
$pdf->Cell(0, 15, 'RECONCILIATION SUMMARY & ANALYSIS', 0, 1, 'C');
$pdf->Ln(10);

// Create comprehensive summary table
$pdf->SetFont('Arial','B', 10);
$pdf->SetFillColor(248, 249, 252);

// Financial Position Summary
$pdf->Cell(0, 8, 'FINANCIAL POSITION ANALYSIS', 0, 1, 'L');
$pdf->Ln(5);

$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(80, 8, 'Transaction Category', 1, 0, 'L', true);
$pdf->Cell(40, 8, 'Count', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Amount (KSh)', 1, 0, 'C', true);
$pdf->Cell(92, 8, 'Percentage of Total', 1, 1, 'C', true);

$pdf->SetFont('Arial','', 10);
$pdf->SetFillColor(255, 255, 255);

// Calculate percentages
$total_volume = $totals['total_inflows'] + $totals['total_outflows'];
$inflow_percentage = $total_volume > 0 ? ($totals['total_inflows'] / $total_volume) * 100 : 0;
$outflow_percentage = $total_volume > 0 ? ($totals['total_outflows'] / $total_volume) * 100 : 0;

// Money In Summary
$pdf->Cell(80, 7, 'Total Money In', 1, 0, 'L');
$pdf->Cell(40, 7, number_format(count($money_in_transactions)), 1, 0, 'C');
$pdf->SetTextColor(0, 128, 0);
$pdf->Cell(50, 7, number_format($totals['total_inflows'], 2), 1, 0, 'R');
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(92, 7, number_format($inflow_percentage, 2) . '%', 1, 1, 'C');

// Money Out Summary
$pdf->Cell(80, 7, 'Total Money Out', 1, 0, 'L');
$pdf->Cell(40, 7, number_format(count($money_out_transactions)), 1, 0, 'C');
$pdf->SetTextColor(220, 53, 69);
$pdf->Cell(50, 7, number_format($totals['total_outflows'], 2), 1, 0, 'R');
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(92, 7, number_format($outflow_percentage, 2) . '%', 1, 1, 'C');

// Float Summary
$pdf->Cell(80, 7, 'Float Transactions', 1, 0, 'L');
$pdf->Cell(40, 7, number_format(count($float_transactions)), 1, 0, 'C');
$pdf->SetTextColor(106, 27, 153);
$pdf->Cell(50, 7, number_format(abs($float_data['closing_float']), 2), 1, 0, 'R');
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(92, 7, 'Net Float Balance', 1, 1, 'C');

// Net Position
$pdf->SetFont('Arial','B', 10);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(80, 8, 'NET POSITION', 1, 0, 'L', true);
$pdf->Cell(40, 8, number_format($totals['total_transactions']), 1, 0, 'C', true);
$net_color = $totals['net_position'] >= 0 ? [0, 128, 0] : [220, 53, 69];
$pdf->SetTextColor($net_color[0], $net_color[1], $net_color[2]);
$pdf->Cell(50, 8, number_format($totals['net_position'], 2), 1, 0, 'R', true);
$pdf->SetTextColor(0, 0, 0);
$status = $totals['net_position'] >= 0 ? 'Positive' : 'Negative';
$pdf->Cell(92, 8, $status . ' Cash Flow', 1, 1, 'C', true);

$pdf->Ln(10);

// Key Performance Indicators
$pdf->SetFont('Arial','B', 12);
$pdf->Cell(0, 8, 'KEY PERFORMANCE INDICATORS', 0, 1, 'L');
$pdf->Ln(5);

$pdf->SetFont('Arial','', 10);
$avg_transaction_in = count($money_in_transactions) > 0 ? $totals['total_inflows'] / count($money_in_transactions) : 0;
$avg_transaction_out = count($money_out_transactions) > 0 ? $totals['total_outflows'] / count($money_out_transactions) : 0;
$fee_percentage = $totals['total_outflows'] > 0 ? ($totals['total_fees'] / $totals['total_outflows']) * 100 : 0;

$pdf->Cell(100, 7, 'Average Inflow Transaction:', 0, 0, 'L');
$pdf->Cell(50, 7, 'KSh ' . number_format($avg_transaction_in, 2), 0, 1, 'R');

$pdf->Cell(100, 7, 'Average Outflow Transaction:', 0, 0, 'L');
$pdf->Cell(50, 7, 'KSh ' . number_format($avg_transaction_out, 2), 0, 1, 'R');

$pdf->Cell(100, 7, 'Total Transaction Fees:', 0, 0, 'L');
$pdf->Cell(50, 7, 'KSh ' . number_format($totals['total_fees'], 2), 0, 1, 'R');

$pdf->Cell(100, 7, 'Fee as % of Outflows:', 0, 0, 'L');
$pdf->Cell(50, 7, number_format($fee_percentage, 2) . '%', 0, 1, 'R');

$pdf->Cell(100, 7, 'Float Utilization Rate:', 0, 0, 'L');
$float_utilization = $float_data['total_added'] > 0 ? ($float_data['total_offloaded'] / $float_data['total_added']) * 100 : 0;
$pdf->Cell(50, 7, number_format($float_utilization, 2) . '%', 0, 1, 'R');

// Report certification
$pdf->Ln(15);
$pdf->SetFont('Arial','B', 10);
$pdf->Cell(0, 8, 'REPORT CERTIFICATION', 0, 1, 'C');
$pdf->SetFont('Arial','', 9);
$pdf->Cell(0, 6, 'This reconciliation report has been generated automatically by the LATO Management System.', 0, 1, 'C');
$pdf->Cell(0, 6, 'All figures are based on transactions recorded between ' . date('M d, Y', strtotime($start_date)) . ' and ' . date('M d, Y', strtotime($end_date)) . '.', 0, 1, 'C');
$pdf->Cell(0, 6, 'Report generated by: ' . $user_name . ' on ' . date('M d, Y \a\t H:i:s'), 0, 1, 'C');

// Clear the output buffer
ob_end_clean();

// Generate filename
$filter_suffix = $float_filter !== 'all' ? '_' . ucfirst($float_filter) . 'Float' : '';
$date_suffix = '_' . date('Y-m-d', strtotime($start_date));
if ($start_date !== $end_date) {
    $date_suffix .= '_to_' . date('Y-m-d', strtotime($end_date));
}

$filename = 'Daily_Reconciliation_Report' . $filter_suffix . $date_suffix . '.pdf';

// Output PDF
$pdf->Output('D', $filename);
?>