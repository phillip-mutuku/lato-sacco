
<?php
// controllers/get_category_expenses.php
require_once '../helpers/session.php';
require_once '../config/class.php';

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['error' => 'Unauthorized']));
}

$db = new db_class();
$category = $_GET['category'] ?? '';

if (empty($category)) {
    exit(json_encode([]));
}

$query = "SELECT name 
          FROM expenses_categories 
          WHERE category = ? 
          ORDER BY name";

$stmt = $db->conn->prepare($query);
$stmt->bind_param('s', $category);
$stmt->execute();
$result = $stmt->get_result();

$expenses = [];
while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
}

echo json_encode($expenses);
?>