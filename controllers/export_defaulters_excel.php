<?php
require_once '../config/class.php';
require_once '../helpers/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

$db = new db_class();

// Get filter parameters
$selected_group = isset($_GET['group_filter']) ? intval($_GET['group_filter']) : 0;
$from_date = isset($_GET['from_date_filter']) ? $_GET['from_date_filter'] : '';
$to_date = isset($_GET['to_date_filter']) ? $_GET['to_date_filter'] : '';

// Build where clause
$where_conditions = [
    "ls.status IN ('unpaid', 'partial')",
    "ls.due_date < CURDATE()",
    "l.status IN (1, 2)",
    "gm.status = 'active'",
    "ls.default_amount > 0"
];

if ($selected_group) {
    $where_conditions[] = "g.group_id = $selected_group";
}
if ($from_date && $to_date) {
    $where_conditions[] = "ls.due_date BETWEEN '$from_date' AND '$to_date'";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Query to get defaulters
$query = "
    SELECT 
        ca.shareholder_no as 'Shareholder No',
        CONCAT(ca.first_name, ' ', ca.last_name) as 'Member Name',
        ca.phone_number as 'Phone Number',
        g.group_reference as 'Group Reference',
        g.group_name as 'Group Name',
        g.area as 'Area',
        l.ref_no as 'Loan Reference',
        l.amount as 'Loan Amount',
        l.loan_term as 'Loan Term (Months)',
        SUM(ls.default_amount) as 'Total Defaulted Amount',
        COUNT(DISTINCT ls.due_date) as 'Overdue Installments',
        MIN(ls.due_date) as 'First Overdue Date',
        MAX(ls.due_date) as 'Last Overdue Date',
        DATEDIFF(CURDATE(), MIN(ls.due_date)) as 'Days Overdue',
        CONCAT(u.firstname, ' ', u.lastname) as 'Field Officer'
    FROM loan_schedule ls
    JOIN loan l ON ls.loan_id = l.loan_id
    JOIN client_accounts ca ON l.account_id = ca.account_id
    JOIN group_members gm ON ca.account_id = gm.account_id
    JOIN lato_groups g ON gm.group_id = g.group_id
    JOIN user u ON g.field_officer_id = u.user_id
    $where_clause
    GROUP BY ca.account_id, g.group_id, l.loan_id
    ORDER BY SUM(ls.default_amount) DESC, ca.first_name ASC
";

$result = $db->conn->query($query);

if (!$result) {
    die("Query failed: " . $db->conn->error);
}

// Generate filename with timestamp
$filename = "Defaulters_Report_" . date('Y-m-d_His') . ".xls";

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Start output
echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
echo "<head>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">";
echo "<!--[if gte mso 9]>";
echo "<xml>";
echo "<x:ExcelWorkbook>";
echo "<x:ExcelWorksheets>";
echo "<x:ExcelWorksheet>";
echo "<x:Name>Defaulters Report</x:Name>";
echo "<x:WorksheetOptions>";
echo "<x:Print>";
echo "<x:ValidPrinterInfo/>";
echo "</x:Print>";
echo "</x:WorksheetOptions>";
echo "</x:ExcelWorksheet>";
echo "</x:ExcelWorksheets>";
echo "</x:ExcelWorkbook>";
echo "</xml>";
echo "<![endif]-->";
echo "</head>";
echo "<body>";

// Report Header
echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>";
echo "<tr><td colspan='15' style='font-size: 18px; font-weight: bold; text-align: center; padding: 10px;'>DEFAULTERS REPORT</td></tr>";
echo "<tr><td colspan='15' style='font-size: 14px; text-align: center; padding: 5px;'>Lato Management System</td></tr>";
echo "<tr><td colspan='15' style='font-size: 12px; text-align: center; padding: 5px;'>Generated on: " . date('d M Y H:i:s') . "</td></tr>";

// Filter information
if ($selected_group || $from_date || $to_date) {
    echo "<tr><td colspan='15' style='padding: 5px;'>&nbsp;</td></tr>";
    echo "<tr><td colspan='15' style='font-size: 12px; font-weight: bold; padding: 5px;'>Applied Filters:</td></tr>";
    
    if ($selected_group) {
        $group_query = "SELECT group_reference, group_name FROM lato_groups WHERE group_id = $selected_group";
        $group_result = $db->conn->query($group_query);
        $group_data = $group_result->fetch_assoc();
        echo "<tr><td colspan='15' style='font-size: 11px; padding: 2px 5px;'>Group: " . $group_data['group_reference'] . " - " . $group_data['group_name'] . "</td></tr>";
    }
    
    if ($from_date && $to_date) {
        echo "<tr><td colspan='15' style='font-size: 11px; padding: 2px 5px;'>Period: " . date('d M Y', strtotime($from_date)) . " to " . date('d M Y', strtotime($to_date)) . "</td></tr>";
    }
}

echo "<tr><td colspan='15' style='padding: 10px;'>&nbsp;</td></tr>";
echo "</table>";

// Data Table
echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";

// Table headers
echo "<thead>";
echo "<tr style='background-color: #51087E; color: white; font-weight: bold;'>";

if ($result->num_rows > 0) {
    $first_row = $result->fetch_assoc();
    
    // Output headers
    foreach ($first_row as $key => $value) {
        echo "<th style='border: 1px solid #000; padding: 8px; text-align: left;'>" . htmlspecialchars($key) . "</th>";
    }
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    // Reset pointer and output first row
    $result->data_seek(0);
    $total_defaulted = 0;
    $row_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $row_count++;
        echo "<tr>";
        foreach ($row as $key => $value) {
            if ($key === 'Total Defaulted Amount') {
                $total_defaulted += floatval($value);
                echo "<td style='border: 1px solid #000; padding: 5px; text-align: right;'>KSh " . number_format($value, 2) . "</td>";
            } elseif ($key === 'Loan Amount') {
                echo "<td style='border: 1px solid #000; padding: 5px; text-align: right;'>KSh " . number_format($value, 2) . "</td>";
            } elseif ($key === 'First Overdue Date' || $key === 'Last Overdue Date') {
                echo "<td style='border: 1px solid #000; padding: 5px;'>" . date('d M Y', strtotime($value)) . "</td>";
            } else {
                echo "<td style='border: 1px solid #000; padding: 5px;'>" . htmlspecialchars($value) . "</td>";
            }
        }
        echo "</tr>";
    }
    
    // Summary row
    echo "</tbody>";
    echo "<tfoot>";
    echo "<tr style='background-color: #f2f2f2; font-weight: bold;'>";
    echo "<td colspan='9' style='border: 1px solid #000; padding: 8px; text-align: right;'>TOTAL:</td>";
    echo "<td style='border: 1px solid #000; padding: 8px; text-align: right;'>KSh " . number_format($total_defaulted, 2) . "</td>";
    echo "<td colspan='5' style='border: 1px solid #000; padding: 8px;'>" . $row_count . " defaulters</td>";
    echo "</tr>";
    echo "</tfoot>";
} else {
    echo "<tr><td colspan='15' style='text-align: center; padding: 20px;'>No defaulters found</td></tr>";
}

echo "</table>";

// Footer
echo "<br><br>";
echo "<table border='0' width='100%'>";
echo "<tr>";
echo "<td style='font-size: 10px; color: #666;'>Report generated by: " . $_SESSION['firstname'] . " " . $_SESSION['lastname'] . "</td>";
echo "</tr>";
echo "<tr>";
echo "<td style='font-size: 10px; color: #666;'>System: Lato Management System</td>";
echo "</tr>";
echo "</table>";

echo "</body>";
echo "</html>";

$db->conn->close();
exit();
?>