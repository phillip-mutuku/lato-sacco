<?php
// Set timezone
date_default_timezone_set("Africa/Nairobi");

// Include required files
require_once '../helpers/session.php';
require_once '../config/class.php';

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['error' => 'Unauthorized']));
}

// Initialize database connection
$db = new db_class();

// Fetch all client accounts
$query = "SELECT shareholder_no, first_name, last_name, phone_number, village FROM client_accounts ORDER BY shareholder_no ASC";
$result = $db->conn->query($query);

// Check if query was successful
if (!$result) {
    die("Database query failed: " . $db->conn->error);
}

// Count total records
$totalClients = $result->num_rows;

// Generate filename with current date
$filename = "Clients_List_" . date('Y-m-d_His') . ".xls";

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Output Excel with HTML styling
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Calibri, Arial, sans-serif;
        }
        .company-header {
            background-color: #51087E;
            color: white;
            font-size: 18pt;
            font-weight: bold;
            text-align: center;
            padding: 10px;
        }
        .report-title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            padding: 8px;
        }
        .report-date {
            font-size: 10pt;
            font-style: italic;
            text-align: center;
            padding: 5px;
            color: #666666;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        th {
            background-color: #FFC107;
            color: white;
            font-weight: bold;
            padding: 12px;
            text-align: center;
            border: 1px solid #000000;
            font-size: 11pt;
        }
        td {
            padding: 10px;
            border: 1px solid #CCCCCC;
            font-size: 10pt;
        }
        tr:nth-child(even) {
            background-color: #F8F9FA;
        }
        tr:nth-child(odd) {
            background-color: #FFFFFF;
        }
        .shareholder-col, .phone-col {
            text-align: center;
        }
        .summary-row {
            background-color: #E8F5E9;
            font-weight: bold;
            border: 2px solid #000000;
        }
        .summary-row td {
            padding: 12px;
            font-size: 11pt;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9pt;
            font-style: italic;
            color: #666666;
        }
    </style>
</head>
<body>
    <div class="company-header">
        LATO MANAGEMENT SYSTEM
    </div>
    <div class="report-title">
        CLIENTS LIST REPORT
    </div>
    <div class="report-date">
        Generated on: <?php echo date('F d, Y h:i A'); ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Shareholder No</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Phone Number</th>
                <th>Village</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td class="shareholder-col"><?php echo htmlspecialchars($row['shareholder_no']); ?></td>
                <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                <td class="phone-col"><?php echo htmlspecialchars($row['phone_number']); ?></td>
                <td><?php echo htmlspecialchars($row['village']); ?></td>
            </tr>
            <?php endwhile; ?>
            <tr class="summary-row">
                <td colspan="4" style="text-align: right; font-weight: bold;">Total Clients:</td>
                <td style="text-align: center; font-weight: bold;"><?php echo $totalClients; ?></td>
            </tr>
        </tbody>
    </table>
    
    <div class="footer">
        © <?php echo date('Y'); ?> Lato Management System. All rights reserved.
    </div>
</body>
</html>

<?php
// Close database connection
$db->conn->close();
exit();
?>