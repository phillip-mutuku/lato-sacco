<?php
// Error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session and set timezone
session_start();
date_default_timezone_set("Africa/Nairobi");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

// Required files
require_once __DIR__ . '/../config/class.php';
require_once __DIR__ . '/../helpers/fpdf/fpdf.php';

class PDF extends FPDF {
    private $data;
    
    function __construct($orientation = 'L', $unit = 'mm', $size = 'A4') {
        parent::__construct($orientation, $unit, $size);
        $this->data = [];
    }

    function setData($data) {
        $this->data = $data;
    }

    function Header() {
        // Add logo
        if (file_exists(__DIR__ . '/../public/image/mylogo.png')) {
            $this->Image(__DIR__ . '/../public/image/mylogo.png', 10, 6, 30);
        }
        
        // Report title
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'LATO SACCO - Financial Report', 0, 1, 'C');
        
        // Filter information
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, 'Report Period: ' . $this->data['period_text'], 0, 1, 'C');
        $this->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

try {
    // Initialize database connection
    $db = new db_class();

    // Get and validate parameters
    $transaction_type = isset($_GET['transaction_type']) ? $_GET['transaction_type'] : 'all';
    $date_range = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

    // Determine period text for report
    $period_text = 'All Time';
    switch ($date_range) {
        case 'today':
            $period_text = 'Today (' . date('Y-m-d') . ')';
            break;
        case 'week':
            $period_text = 'This Week';
            break;
        case 'month':
            $period_text = 'This Month (' . date('F Y') . ')';
            break;
        case 'year':
            $period_text = 'This Year (' . date('Y') . ')';
            break;
        case 'custom':
            if ($start_date && $end_date) {
                $period_text = date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date));
            }
            break;
    }

    // Build base query
    $query = "SELECT 
        e.*,
        ec.category as main_category,
        u.username as created_by_name,
        CASE 
            WHEN e.status = 'received' THEN ABS(e.amount)
            ELSE -ABS(e.amount)
        END as signed_amount
    FROM expenses e 
    LEFT JOIN expenses_categories ec ON e.category = ec.name 
    LEFT JOIN user u ON e.created_by = u.user_id
    WHERE 1=1";

    $params = [];
    $types = '';

    // Add filters
    if ($transaction_type !== 'all') {
        $query .= " AND e.status = ?";
        $params[] = ($transaction_type === 'expenses') ? 'completed' : 'received';
        $types .= 's';
    }

    if ($date_range !== 'all') {
        switch ($date_range) {
            case 'today':
                $query .= " AND DATE(e.date) = CURDATE()";
                break;
            case 'week':
                $query .= " AND YEARWEEK(e.date, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $query .= " AND YEAR(e.date) = YEAR(CURDATE()) AND MONTH(e.date) = MONTH(CURDATE())";
                break;
            case 'year':
                $query .= " AND YEAR(e.date) = YEAR(CURDATE())";
                break;
            case 'custom':
                if ($start_date && $end_date) {
                    $query .= " AND DATE(e.date) BETWEEN ? AND ?";
                    $params[] = $start_date;
                    $params[] = $end_date;
                    $types .= 'ss';
                }
                break;
        }
    }

    $query .= " ORDER BY e.date DESC";

    // Execute query
    $stmt = $db->conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        throw new Exception("Query failed: " . $db->conn->error);
    }

    // Calculate totals
    $total_expenses = 0;
    $total_received = 0;
    $category_totals = [];
    $transactions = [];

    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
        
        if ($row['status'] === 'received') {
            $total_received += $row['amount'];
        } else {
            $total_expenses += abs($row['amount']);
        }
        
        $category = $row['main_category'];
        if (!isset($category_totals[$category])) {
            $category_totals[$category] = 0;
        }
        $category_totals[$category] += $row['signed_amount'];
    }

    // Create PDF
    $pdf = new PDF();
    $pdf->setData([
        'period_text' => $period_text,
        'transaction_type' => $transaction_type
    ]);
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Financial Summary
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Financial Summary', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Cell(60, 8, 'Total Expenses:', 1);
    $pdf->SetTextColor(255, 0, 0);
    $pdf->Cell(60, 8, 'KSh ' . number_format($total_expenses, 2), 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln();
    
    $pdf->Cell(60, 8, 'Total Received:', 1);
    $pdf->SetTextColor(0, 128, 0);
    $pdf->Cell(60, 8, 'KSh ' . number_format($total_received, 2), 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln();
    
    $net_balance = $total_received - $total_expenses;
    $pdf->Cell(60, 8, 'Net Balance:', 1);
    $pdf->SetTextColor($net_balance >= 0 ? 0 : 255, $net_balance >= 0 ? 128 : 0, 0);
    $pdf->Cell(60, 8, 'KSh ' . number_format($net_balance, 2), 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(15);

    // Category Breakdown
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Category Breakdown', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);

    foreach ($category_totals as $category => $total) {
        $pdf->Cell(100, 8, $category, 1);
        $pdf->SetTextColor($total >= 0 ? 0 : 255, $total >= 0 ? 128 : 0, 0);
        $pdf->Cell(60, 8, 'KSh ' . number_format($total, 2), 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln();
    }
    $pdf->Ln(15);

    // Detailed Transactions
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Transaction Details', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 9);

    // Table headers
    $headers = ['Date', 'Receipt No', 'Category', 'Description', 'Amount', 'Payment Method', 'Status', 'Created By'];
    $widths = [25, 30, 40, 50, 30, 30, 25, 30];
    
    foreach (array_combine($headers, $widths) as $header => $width) {
        $pdf->Cell($width, 8, $header, 1);
    }
    $pdf->Ln();

    // Table data
    foreach ($transactions as $transaction) {
        $pdf->Cell($widths[0], 8, date('Y-m-d', strtotime($transaction['date'])), 1);
        $pdf->Cell($widths[1], 8, $transaction['receipt_no'], 1);
        $pdf->Cell($widths[2], 8, $transaction['main_category'], 1);
        $pdf->Cell($widths[3], 8, substr($transaction['description'], 0, 30), 1);
        
        $amount = $transaction['signed_amount'];
        $pdf->SetTextColor($amount >= 0 ? 0 : 255, $amount >= 0 ? 128 : 0, 0);
        $pdf->Cell($widths[4], 8, 'KSh ' . number_format(abs($amount), 2), 1);
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->Cell($widths[5], 8, $transaction['payment_method'], 1);
        $pdf->Cell($widths[6], 8, ucfirst($transaction['status']), 1);
        $pdf->Cell($widths[7], 8, $transaction['created_by_name'], 1);
        $pdf->Ln();
    }

    // Output the PDF
    $filename = 'Financial_Report_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output('D', $filename);

} catch (Exception $e) {
    error_log("Error generating expense report: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to generate report. Please try again later.']);
    exit;
}
?>