<?php
// controllers/get_trend_data.php
require_once '../helpers/session.php';
require_once '../config/class.php';

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['error' => 'Unauthorized']));
}

$db = new db_class();
$current_year = date('Y');
$category = $_GET['category'] ?? 'all';

$months = [];
$income_data = array_fill(0, 12, 0);
$expenditure_data = array_fill(0, 12, 0);

// Get income data
$income_query = "
    SELECT 
        MONTH(date) as month,
        SUM(amount) as total
    FROM transactions
    WHERE YEAR(date) = ? AND type IN ('Savings', 'Loan Repayment')
    GROUP BY MONTH(date)
";

$stmt = $db->conn->prepare($income_query);
$stmt->bind_param('i', $current_year);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $income_data[$row['month'] - 1] = (float)$row['total'];
}

// Get expenditure data
$expenditure_query = "
    SELECT 
        MONTH(date) as month,
        SUM(amount) as total
    FROM (
        SELECT date, amount 
        FROM expenses
        WHERE YEAR(date) = ?
        " . ($category !== 'all' ? "AND category IN (SELECT name FROM expenses_categories WHERE category = ?)" : "") . "
        UNION ALL
        SELECT date, amount
        FROM transactions
        WHERE YEAR(date) = ? AND type = 'Loan Disbursement'
        " . ($category !== 'all' ? "AND 'Financial Operations' = ?" : "") . "
    ) combined_data
    GROUP BY MONTH(date)
";

$params = [$current_year];
$types = 'i';

if ($category !== 'all') {
    $params[] = $category;
    $params[] = $current_year;
    $params[] = $category;
    $types .= 'sis';
} else {
    $params[] = $current_year;
    $types .= 'i';
}

$stmt = $db->conn->prepare($expenditure_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $expenditure_data[$row['month'] - 1] = (float)$row['total'];
}

echo json_encode([
    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    'income' => $income_data,
    'expenditure' => $expenditure_data
]);