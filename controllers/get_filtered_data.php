<?php
require_once '../helpers/session.php';
require_once '../config/class.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'cashier')) {
    exit(json_encode(['error' => 'Unauthorized']));
}

$db = new db_class();
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$category = $_GET['category'] ?? 'all';
$expense_id = $_GET['expense_id'] ?? null;

// Get expenditure data
$expenditure_data = getExpenditureData($db, $start_date, $end_date, $category, $expense_id);

// Calculate totals
$total_income = 0;
$total_expenditure = 0;

foreach ($expenditure_data as $item) {
    if ($item['main_category'] === 'Income') {
        $total_income += $item['amount'];
    } else {
        $total_expenditure += $item['amount'];
    }
}

$net_position = $total_income - $total_expenditure;

// Prepare chart data
$chart_data = prepareChartData($expenditure_data);

// Send response
echo json_encode([
    'expenses' => $expenditure_data,
    'total_income' => $total_income,
    'total_expenditure' => $total_expenditure,
    'net_position' => $net_position,
    'chart_data' => $chart_data
]);

function prepareChartData($data) {
    $chart_data = [];
    foreach ($data as $item) {
        $month = date('Y-m', strtotime($item['date']));
        if (!isset($chart_data[$month])) {
            $chart_data[$month] = [
                'income' => 0,
                'expenditure' => 0
            ];
        }
        
        if ($item['main_category'] === 'Income') {
            $chart_data[$month]['income'] += $item['amount'];
        } else {
            $chart_data[$month]['expenditure'] += $item['amount'];
        }
    }
    
    ksort($chart_data);
    return $chart_data;
}