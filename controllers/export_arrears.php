<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../helpers/session.php';
require_once '../config/class.php';

// Check if user is logged in and is either an admin or manager
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: ../views/index.php');
    exit();
}

$db = new db_class();

// Get filter parameters (same as the main page)
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'month';
$custom_start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$custom_end = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Initialize dates based on filter type
switch($filter_type) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
    case 'custom':
        $start_date = !empty($custom_start) ? $custom_start : date('Y-m-01');
        $end_date = !empty($custom_end) ? $custom_end : date('Y-m-t');
        break;
    default:
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
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

// Create Excel file using openpyxl via Python
$python_script = <<<'PYTHON'
import sys
import json
from openpyxl import Workbook
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter
from datetime import datetime

# Read data from stdin
input_data = json.loads(sys.stdin.read())
data = input_data['data']
start_date = input_data['start_date']
end_date = input_data['end_date']
filter_type = input_data['filter_type']
total_defaulted = input_data['total_defaulted']
total_clients = input_data['total_clients']

# Create workbook
wb = Workbook()
ws = wb.active
ws.title = 'Arrears Report'

# Define styles
header_fill = PatternFill(start_color='51087E', end_color='51087E', fill_type='solid')
header_font = Font(bold=True, color='FFFFFF', size=12)
title_font = Font(bold=True, size=14, color='51087E')
border = Border(
    left=Side(style='thin'),
    right=Side(style='thin'),
    top=Side(style='thin'),
    bottom=Side(style='thin')
)

# Add title
ws['A1'] = 'LATO SACCO - ARREARS MANAGEMENT REPORT'
ws['A1'].font = title_font
ws.merge_cells('A1:O1')
ws['A1'].alignment = Alignment(horizontal='center')

# Add report metadata
ws['A2'] = f'Period: {start_date} to {end_date} ({filter_type.title()})'
ws['A2'].font = Font(bold=True, size=11)
ws.merge_cells('A2:O2')

ws['A3'] = f'Generated: {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}'
ws['A3'].font = Font(size=10, italic=True)
ws.merge_cells('A3:O3')

# Add summary
ws['A4'] = f'Total Defaulters: {total_clients}'
ws['A4'].font = Font(bold=True, size=11)
ws['G4'] = f'Total Amount in Arrears: KSh {total_defaulted:,.2f}'
ws['G4'].font = Font(bold=True, size=11, color='FF0000')

# Headers starting at row 6
headers = [
    'Loan Reference',
    'Client Name',
    'Shareholder No',
    'Phone Number',
    'Location',
    'Village',
    'Principal',
    'Interest',
    'Expected Amount',
    'Repaid Amount',
    'Overdue Amount',
    'Due Date',
    'Days Overdue',
    'Status',
    'Loan Amount'
]

# Write headers
for col_num, header in enumerate(headers, 1):
    cell = ws.cell(row=6, column=col_num)
    cell.value = header
    cell.font = header_font
    cell.fill = header_fill
    cell.alignment = Alignment(horizontal='center', vertical='center')
    cell.border = border

# Write data
row_num = 7
for record in data:
    ws.cell(row=row_num, column=1, value=record['ref_no'])
    ws.cell(row=row_num, column=2, value=record['client_name'])
    ws.cell(row=row_num, column=3, value=record['shareholder_no'])
    ws.cell(row=row_num, column=4, value=record['phone_number'] or 'N/A')
    ws.cell(row=row_num, column=5, value=record['location'] or 'N/A')
    ws.cell(row=row_num, column=6, value=record['village'] or 'N/A')
    
    # Currency cells with formulas
    ws.cell(row=row_num, column=7, value=float(record['principal']))
    ws.cell(row=row_num, column=8, value=float(record['interest']))
    ws.cell(row=row_num, column=9, value=float(record['expected_amount']))
    ws.cell(row=row_num, column=10, value=float(record['repaid_amount']))
    ws.cell(row=row_num, column=11, value=float(record['default_amount']))
    
    ws.cell(row=row_num, column=12, value=record['due_date'])
    ws.cell(row=row_num, column=13, value=int(record['days_overdue']))
    ws.cell(row=row_num, column=14, value=record['status'].title())
    ws.cell(row=row_num, column=15, value=float(record['loan_amount']))
    
    # Apply borders
    for col in range(1, 16):
        ws.cell(row=row_num, column=col).border = border
        
    # Format currency columns
    for col in [7, 8, 9, 10, 11, 15]:
        ws.cell(row=row_num, column=col).number_format = '#,##0.00'
        
    # Highlight overdue amount in red
    ws.cell(row=row_num, column=11).font = Font(color='FF0000', bold=True)
    
    row_num += 1

# Add totals row
total_row = row_num
ws.cell(row=total_row, column=1, value='TOTAL')
ws.cell(row=total_row, column=1).font = Font(bold=True)
ws.cell(row=total_row, column=11).value = f'=SUM(K7:K{row_num-1})')
ws.cell(row=total_row, column=11).font = Font(bold=True, color='FF0000')
ws.cell(row=total_row, column=11).number_format = '#,##0.00'

for col in range(1, 16):
    ws.cell(row=total_row, column=col).border = border
    ws.cell(row=total_row, column=col).fill = PatternFill(start_color='E0E0E0', end_color='E0E0E0', fill_type='solid')

# Auto-adjust column widths
for col_num in range(1, 16):
    col_letter = get_column_letter(col_num)
    max_length = 0
    
    for row in ws[col_letter]:
        try:
            if row.value:
                max_length = max(max_length, len(str(row.value)))
        except:
            pass
    
    adjusted_width = min(max_length + 2, 50)
    ws.column_dimensions[col_letter].width = adjusted_width

# Save file
output_file = '/home/claude/arrears_export.xlsx'
wb.save(output_file)
print(output_file)
PYTHON;

// Prepare JSON data for Python
$export_data = [
    'data' => $data,
    'start_date' => date('M d, Y', strtotime($start_date)),
    'end_date' => date('M d, Y', strtotime($end_date)),
    'filter_type' => $filter_type,
    'total_defaulted' => $total_defaulted,
    'total_clients' => count(array_unique(array_column($data, 'client_name')))
];

$json_data = json_encode($export_data);

// Write Python script to temp file
$temp_script = tempnam(sys_get_temp_dir(), 'export_') . '.py';
file_put_contents($temp_script, $python_script);

// Execute Python script
$descriptors = [
    0 => ['pipe', 'r'], // stdin
    1 => ['pipe', 'w'], // stdout
    2 => ['pipe', 'w']  // stderr
];

$process = proc_open("python3 $temp_script", $descriptors, $pipes);

if (is_resource($process)) {
    // Send data to Python script
    fwrite($pipes[0], $json_data);
    fclose($pipes[0]);
    
    // Get output
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    
    $return_value = proc_close($process);
    
    if ($return_value === 0 && !empty($output)) {
        $file_path = trim($output);
        
        if (file_exists($file_path)) {
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="Arrears_Report_' . date('Y-m-d_His') . '.xlsx"');
            header('Cache-Control: max-age=0');
            
            // Output file
            readfile($file_path);
            
            // Clean up
            unlink($file_path);
            unlink($temp_script);
            exit();
        } else {
            error_log("Export file not found: $file_path");
        }
    } else {
        error_log("Python script error: $errors");
    }
    
    // Clean up temp script
    unlink($temp_script);
}

// If we get here, something went wrong
$_SESSION['error_msg'] = "Failed to generate export file";
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
?>