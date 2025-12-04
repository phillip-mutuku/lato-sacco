<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../helpers/session.php';
require_once '../config/class.php';

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['error' => 'Unauthorized']));
}

$db = new db_class();

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Validate dates
if (empty($start_date) || empty($end_date)) {
    $_SESSION['error_msg'] = "Please select both start and end dates";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Get the arrears data with filters
$arrears_query = "SELECT 
                    l.ref_no, 
                    CONCAT(ca.first_name, ' ', ca.last_name) as client_name,
                    ca.phone_number,
                    ls.principal,
                    ls.interest,
                    ls.amount as expected_amount,
                    COALESCE(ls.repaid_amount, 0) as repaid_amount,
                    ls.default_amount,
                    ls.due_date,
                    DATEDIFF(CURDATE(), ls.due_date) as days_overdue,
                    ls.status,
                    l.amount as loan_amount,
                    ca.shareholder_no,
                    ca.location,
                    ca.village
                FROM loan_schedule ls
                JOIN loan l ON ls.loan_id = l.loan_id
                JOIN client_accounts ca ON l.account_id = ca.account_id
                WHERE ls.due_date < CURDATE() 
                AND ls.status IN ('unpaid', 'partial')
                AND l.status >= 2
                AND ls.default_amount > 0
                AND ls.due_date BETWEEN ? AND ?
                ORDER BY ls.due_date DESC, ls.default_amount DESC";

$stmt = $db->conn->prepare($arrears_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Prepare data for export
$data = [];
$total_defaulted = 0;

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
    $total_defaulted += $row['default_amount'];
}

$total_clients = count(array_unique(array_column($data, 'client_name')));

// Set headers for Excel download
$filename = 'LATO_Arrears_Report_' . date('Y-m-d_His') . '.xls';
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        /* Excel will interpret these styles */
        body { font-family: Calibri, Arial, sans-serif; }
        
        .title {
            font-size: 18pt;
            font-weight: bold;
            color: #51087E;
            text-align: center;
            background-color: #FFFFFF;
            height: 35px;
        }
        
        .subtitle {
            font-size: 12pt;
            font-weight: bold;
            color: #333333;
            text-align: center;
            background-color: #FFFFFF;
            height: 20px;
        }
        
        .metadata {
            font-size: 10pt;
            font-style: italic;
            color: #666666;
            text-align: center;
        }
        
        .summary-box {
            background-color: #F8F9FA;
            border: 1px solid #D1D3E2;
            padding: 10px;
        }
        
        .summary-header {
            font-size: 12pt;
            font-weight: bold;
            color: #51087E;
            background-color: #F8F9FA;
        }
        
        .summary-label {
            font-size: 11pt;
            font-weight: bold;
            background-color: #F8F9FA;
        }
        
        .summary-value {
            font-size: 11pt;
            font-weight: bold;
            color: #51087E;
            background-color: #F8F9FA;
        }
        
        .summary-amount {
            font-size: 12pt;
            font-weight: bold;
            color: #DC3545;
            background-color: #F8F9FA;
        }
        
        .alert-box {
            background-color: #FFF3CD;
            color: #856404;
            font-weight: bold;
            text-align: center;
            border: 1px solid #FFEAA7;
            padding: 5px;
        }
        
        .table-header {
            background-color: #51087E;
            color: #FFFFFF;
            font-weight: bold;
            font-size: 11pt;
            text-align: center;
            border: 2px solid #51087E;
            height: 30px;
        }
        
        .data-cell {
            font-size: 10pt;
            border: 1px solid #D1D3E2;
            padding: 5px;
        }
        
        .data-cell-center {
            font-size: 10pt;
            border: 1px solid #D1D3E2;
            text-align: center;
            padding: 5px;
        }
        
        .data-cell-right {
            font-size: 10pt;
            border: 1px solid #D1D3E2;
            text-align: right;
            padding: 5px;
        }
        
        .loan-ref {
            font-weight: bold;
            color: #51087E;
            font-size: 10pt;
            border: 1px solid #D1D3E2;
            text-align: center;
        }
        
        .overdue-amount {
            font-weight: bold;
            color: #DC3545;
            font-size: 10pt;
            border: 1px solid #D1D3E2;
            text-align: right;
        }
        
        .status-unpaid {
            background-color: #DC3545;
            color: #FFFFFF;
            font-weight: bold;
            text-align: center;
            border: 1px solid #D1D3E2;
        }
        
        .status-partial {
            background-color: #FFC107;
            color: #FFFFFF;
            font-weight: bold;
            text-align: center;
            border: 1px solid #D1D3E2;
        }
        
        .days-green {
            font-weight: bold;
            color: #28A745;
            text-align: center;
            border: 1px solid #D1D3E2;
        }
        
        .days-yellow {
            font-weight: bold;
            color: #FFC107;
            text-align: center;
            border: 1px solid #D1D3E2;
        }
        
        .days-orange {
            font-weight: bold;
            color: #FD7E14;
            text-align: center;
            border: 1px solid #D1D3E2;
        }
        
        .days-red {
            font-weight: bold;
            color: #DC3545;
            text-align: center;
            border: 1px solid #D1D3E2;
        }
        
        .alt-row {
            background-color: #F8F9FA;
        }
        
        .unpaid-row {
            background-color: #FFEBEE;
        }
        
        .partial-row {
            background-color: #FFF9E6;
        }
        
        .total-row {
            background-color: #51087E;
            color: #FFFFFF;
            font-weight: bold;
            font-size: 11pt;
            text-align: center;
            border: 2px solid #51087E;
            height: 25px;
        }
        
        .footer-info {
            font-size: 9pt;
            font-style: italic;
            color: #666666;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <!-- TITLE -->
        <tr>
            <td colspan="15" class="title">LATO SACCO - ARREARS MANAGEMENT REPORT</td>
        </tr>
        
        <!-- PERIOD -->
        <tr>
            <td colspan="15" class="subtitle">Report Period: <?php echo date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date)); ?></td>
        </tr>
        
        <!-- GENERATED DATE -->
        <tr>
            <td colspan="15" class="metadata">Generated: <?php echo date('F d, Y \a\t h:i A'); ?></td>
        </tr>
        
        <!-- EMPTY ROW -->
        <tr><td colspan="15" style="height: 10px;"></td></tr>
        
        <!-- SUMMARY HEADER -->
        <tr>
            <td colspan="7" class="summary-header">📊 SUMMARY STATISTICS</td>
            <td colspan="8" class="summary-box"></td>
        </tr>
        
        <!-- SUMMARY DATA -->
        <tr>
            <td colspan="3" class="summary-label">Total Defaulting Clients:</td>
            <td colspan="2" class="summary-value"><?php echo number_format($total_clients); ?></td>
            <td colspan="2" class="summary-box"></td>
            <td colspan="3" class="summary-label">Total Amount in Arrears:</td>
            <td colspan="5" class="summary-amount">KSh <?php echo number_format($total_defaulted, 2); ?></td>
        </tr>
        
        <!-- ALERT IF HIGH ARREARS -->
        <?php if ($total_defaulted > 100000): ?>
        <tr>
            <td colspan="15" class="alert-box">⚠️ HIGH ARREARS ALERT - Immediate action required</td>
        </tr>
        <?php endif; ?>
        
        <!-- EMPTY ROW -->
        <tr><td colspan="15" style="height: 10px;"></td></tr>
        
        <!-- TABLE HEADER -->
        <tr>
            <td class="table-header">Loan Reference</td>
            <td class="table-header">Client Name</td>
            <td class="table-header">Shareholder No</td>
            <td class="table-header">Phone Number</td>
            <td class="table-header">Location</td>
            <td class="table-header">Village</td>
            <td class="table-header">Principal</td>
            <td class="table-header">Interest</td>
            <td class="table-header">Expected Amount</td>
            <td class="table-header">Repaid Amount</td>
            <td class="table-header">Overdue Amount</td>
            <td class="table-header">Due Date</td>
            <td class="table-header">Days Overdue</td>
            <td class="table-header">Status</td>
            <td class="table-header">Loan Amount</td>
        </tr>
        
        <!-- DATA ROWS -->
        <?php 
        $row_num = 0;
        foreach ($data as $row): 
            $row_num++;
            
            // Determine row background class
            $row_class = '';
            if ($row['status'] == 'unpaid') {
                $row_class = 'unpaid-row';
            } elseif ($row['status'] == 'partial') {
                $row_class = 'partial-row';
            } elseif ($row_num % 2 == 0) {
                $row_class = 'alt-row';
            }
            
            // Determine days overdue color
            $days_overdue = intval($row['days_overdue']);
            if ($days_overdue > 90) {
                $days_class = 'days-red';
            } elseif ($days_overdue > 60) {
                $days_class = 'days-orange';
            } elseif ($days_overdue > 30) {
                $days_class = 'days-yellow';
            } else {
                $days_class = 'days-green';
            }
            
            // Status class
            $status_class = $row['status'] == 'unpaid' ? 'status-unpaid' : 'status-partial';
        ?>
        <tr class="<?php echo $row_class; ?>">
            <td class="loan-ref"><?php echo htmlspecialchars($row['ref_no']); ?></td>
            <td class="data-cell"><?php echo htmlspecialchars($row['client_name']); ?></td>
            <td class="data-cell-center"><?php echo htmlspecialchars($row['shareholder_no']); ?></td>
            <td class="data-cell-center"><?php echo htmlspecialchars($row['phone_number'] ?? 'N/A'); ?></td>
            <td class="data-cell"><?php echo htmlspecialchars($row['location'] ?? 'N/A'); ?></td>
            <td class="data-cell"><?php echo htmlspecialchars($row['village'] ?? 'N/A'); ?></td>
            <td class="data-cell-right"><?php echo number_format($row['principal'], 2); ?></td>
            <td class="data-cell-right"><?php echo number_format($row['interest'], 2); ?></td>
            <td class="data-cell-right"><?php echo number_format($row['expected_amount'], 2); ?></td>
            <td class="data-cell-right"><?php echo number_format($row['repaid_amount'], 2); ?></td>
            <td class="overdue-amount"><?php echo number_format($row['default_amount'], 2); ?></td>
            <td class="data-cell-center"><?php echo date('d/m/Y', strtotime($row['due_date'])); ?></td>
            <td class="<?php echo $days_class; ?>"><?php echo $days_overdue; ?></td>
            <td class="<?php echo $status_class; ?>"><?php echo strtoupper($row['status']); ?></td>
            <td class="data-cell-right"><?php echo number_format($row['loan_amount'], 2); ?></td>
        </tr>
        <?php endforeach; ?>
        
        <!-- TOTAL ROW -->
        <?php if (!empty($data)): ?>
        <tr>
            <td colspan="6" class="total-row">TOTAL</td>
            <td class="total-row"></td>
            <td class="total-row"></td>
            <td class="total-row"></td>
            <td class="total-row"></td>
            <td class="total-row" style="text-align: right;">KSh <?php echo number_format($total_defaulted, 2); ?></td>
            <td class="total-row"></td>
            <td class="total-row"></td>
            <td class="total-row"></td>
            <td class="total-row"></td>
        </tr>
        <?php endif; ?>
        
        <!-- EMPTY ROW -->
        <tr><td colspan="15" style="height: 10px;"></td></tr>
        
        <!-- FOOTER INFO -->
        <tr>
            <td colspan="15" class="footer-info">
                📄 Report contains <?php echo count($data); ?> overdue installment(s) from <?php echo $total_clients; ?> client(s)
            </td>
        </tr>
    </table>
</body>
</html>