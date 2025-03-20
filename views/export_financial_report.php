<?php
require_once '../helpers/session.php';
require_once '../config/class.php';
require_once '../vendor/autoload.php'; // Make sure you have PhpSpreadsheet installed

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'cashier')) {
    exit('Unauthorized access');
}

$db = new db_class();

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$category = $_GET['category'] ?? 'all';

// Create new spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$sheet->setCellValue('A1', 'Financial Report');
$sheet->setCellValue('A2', 'Period: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)));
$sheet->setCellValue('A4', 'Income Breakdown');
$sheet->setCellValue('A5', 'Source');
$sheet->setCellValue('B5', 'Amount');
$sheet->setCellValue('C5', 'Percentage');

// Get income data
$query = "
    SELECT 
        'Loan Disbursements' as source,
        SUM(amount) as amount 
    FROM loan 
    WHERE DATE(date_released) BETWEEN ? AND ?
    UNION ALL
    SELECT 
        'Loan Repayments', SUM(amount_repaid)
    FROM loan_repayments
    WHERE DATE(date_paid) BETWEEN ? AND ?
    UNION ALL
    SELECT 
        'Savings Deposits',
        SUM(CASE WHEN type = 'Deposit' THEN amount ELSE 0 END)
    FROM savings
    WHERE DATE(date) BETWEEN ? AND ?";

$stmt = $db->conn->prepare($query);
$stmt->bind_param("ssssss", $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
$stmt->execute();
$income_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total income
$total_income = array_sum(array_column($income_data, 'amount'));

// Fill income data
$row = 6;
foreach ($income_data as $income) {
    $percentage = ($total_income > 0) ? ($income['amount'] / $total_income) * 100 : 0;
    
    $sheet->setCellValue('A' . $row, $income['source']);
    $sheet->setCellValue('B' . $row, $income['amount']);
    $sheet->setCellValue('C' . $row, number_format($percentage, 1) . '%');
    $row++;
}

// Add total row for income
$sheet->setCellValue('A' . $row, 'Total Income');
$sheet->setCellValue('B' . $row, $total_income);
$sheet->setCellValue('C' . $row, '100%');
$row += 2;

// Expenditure section
$sheet->setCellValue('A' . $row, 'Expenditure Breakdown');
$row++;
$sheet->setCellValue('A' . $row, 'Category');
$sheet->setCellValue('B' . $row, 'Amount');
$sheet->setCellValue('C' . $row, 'Percentage');
$row++;

// Get expenditure data
$category_condition = "";
if ($category !== 'all') {
    $category_condition = "AND category = ?";
}

$query = "
    SELECT 
        category,
        SUM(amount) as amount,
        COUNT(*) as transaction_count
    FROM transactions 
    WHERE DATE(date) BETWEEN ? AND ?
    $category_condition
    GROUP BY category
    ORDER BY amount DESC";

$stmt = $db->conn->prepare($query);
if ($category !== 'all') {
    $stmt->bind_param("sss", $start_date, $end_date, $category);
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
}
$stmt->execute();
$expenditure_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total expenditure
$total_expenditure = array_sum(array_column($expenditure_data, 'amount'));

// Fill expenditure data
foreach ($expenditure_data as $expense) {
    $percentage = ($total_expenditure > 0) ? ($expense['amount'] / $total_expenditure) * 100 : 0;
    
    $sheet->setCellValue('A' . $row, $expense['category']);
    $sheet->setCellValue('B' . $row, $expense['amount']);
    $sheet->setCellValue('C' . $row, number_format($percentage, 1) . '%');
    $row++;
}

// Add total row for expenditure
$sheet->setCellValue('A' . $row, 'Total Expenditure');
$sheet->setCellValue('B' . $row, $total_expenditure);
$sheet->setCellValue('C' . $row, '100%');
$row += 2;

// Add net position
$net_position = $total_income - $total_expenditure;
$sheet->setCellValue('A' . $row, 'Net Position');
$sheet->setCellValue('B' . $row, $net_position);

// Style the spreadsheet
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A4:C4')->getFont()->setBold(true);
$sheet->getStyle('A5:C5')->getFont()->setBold(true);
$sheet->getStyle('B6:B' . ($row))->getNumberFormat()->setFormatCode('_("KSh"* #,##0.00_);_("KSh"* \(#,##0.00\);_("KSh"* "-"??_);_(@_)');

// Auto-size columns
foreach (range('A', 'C') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Financial_Report_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

// Save the spreadsheet
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>