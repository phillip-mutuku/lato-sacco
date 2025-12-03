<?php
require_once '../config/class.php';
require_once('../helpers/fpdf/fpdf.php');

class PDF extends FPDF {
    private $reportTitle = 'LOAN ARREARS REPORT';
    private $companyName = 'LATO SACCO LTD';
    
    function Header() {
        // Logo
        if (file_exists('../public/image/mylogo.png')) {
            $this->Image('../public/image/mylogo.png', 10, 6, 25);
        }
        
        // Company name
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(80);
        $this->Cell(30, 10, $this->companyName, 0, 0, 'C');
        $this->Ln(15);
        
        // Report title
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, $this->reportTitle, 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        
        // Generation info
        $this->Cell(0, 5, 'Generated on: ' . date('M d, Y \a\t H:i A'), 0, 1, 'L');
        $this->Ln(5);
        
        // Page number
        $this->Cell(0, 5, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
        
        // Footer line
        $this->SetY(-15);
        $this->SetDrawColor(81, 8, 126);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
    }
    
    function AddSummarySection($total_defaulters, $total_amount, $period_start, $period_end) {
        // Summary box
        $this->SetFillColor(245, 245, 245);
        $this->SetDrawColor(200, 200, 200);
        $this->Rect(10, $this->GetY(), 190, 30, 'FD');
        
        $y_start = $this->GetY() + 5;
        
        // Period information
        $this->SetXY(15, $y_start);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 6, 'REPORT PERIOD', 0, 1);
        
        $this->SetX(15);
        $this->SetFont('Arial', '', 10);
        $period_text = date('M d, Y', strtotime($period_start)) . ' to ' . date('M d, Y', strtotime($period_end));
        $this->Cell(0, 5, $period_text, 0, 1);
        
        // Summary statistics
        $this->SetXY(15, $y_start + 15);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(60, 5, 'Total Defaulting Clients:', 0, 0);
        $this->SetFont('Arial', '', 10);
        $this->Cell(40, 5, number_format($total_defaulters), 0, 0);
        
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(60, 5, 'Total Amount in Arrears:', 0, 0);
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'KSh ' . number_format($total_amount, 2), 0, 1);
        
        $this->Ln(10);
    }
    
    function AddTableHeader() {
        $this->SetFillColor(81, 8, 126);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 9);
        
        // Table headers
        $this->Cell(25, 8, 'Loan Ref', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Client Name', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Phone', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Expected', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Paid', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Overdue', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Due Date', 1, 0, 'C', true);
        $this->Cell(15, 8, 'Days', 1, 1, 'C', true);
        
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 8);
    }
    
    function AddTableRow($row, $fill = false) {
        $this->SetFillColor(245, 245, 245);
        
        // Truncate long names
        $client_name = strlen($row['client_name']) > 25 ? 
                      substr($row['client_name'], 0, 22) . '...' : $row['client_name'];
        
        // Format phone number
        $phone = !empty($row['phone_number']) ? substr($row['phone_number'], 0, 12) : 'N/A';
        
        $this->Cell(25, 6, $row['ref_no'], 1, 0, 'C', $fill);
        $this->Cell(40, 6, $client_name, 1, 0, 'L', $fill);
        $this->Cell(25, 6, $phone, 1, 0, 'C', $fill);
        $this->Cell(25, 6, number_format($row['expected_amount'], 0), 1, 0, 'R', $fill);
        $this->Cell(25, 6, number_format($row['repaid_amount'], 0), 1, 0, 'R', $fill);
        $this->Cell(25, 6, number_format($row['default_amount'], 0), 1, 0, 'R', $fill);
        $this->Cell(20, 6, date('d/m/y', strtotime($row['due_date'])), 1, 0, 'C', $fill);
        $this->Cell(15, 6, $row['days_overdue'], 1, 1, 'C', $fill);
    }
    
    function AddTotalRow($total_expected, $total_paid, $total_overdue) {
        $this->SetFillColor(81, 8, 126);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 9);
        
        $this->Cell(90, 8, 'TOTALS', 1, 0, 'C', true);
        $this->Cell(25, 8, number_format($total_expected, 0), 1, 0, 'R', true);
        $this->Cell(25, 8, number_format($total_paid, 0), 1, 0, 'R', true);
        $this->Cell(25, 8, number_format($total_overdue, 0), 1, 0, 'R', true);
        $this->Cell(35, 8, '', 1, 1, 'C', true);
        
        $this->SetTextColor(0);
    }
    
    function AddAnalysisSection($data) {
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'ARREARS ANALYSIS', 0, 1, 'L');
        
        // Age analysis
        $age_brackets = [
            '1-30 days' => 0,
            '31-60 days' => 0,
            '61-90 days' => 0,
            '90+ days' => 0
        ];
        
        $age_amounts = [
            '1-30 days' => 0,
            '31-60 days' => 0,
            '61-90 days' => 0,
            '90+ days' => 0
        ];
        
        foreach($data as $row) {
            $days = $row['days_overdue'];
            $amount = $row['default_amount'];
            
            if($days <= 30) {
                $age_brackets['1-30 days']++;
                $age_amounts['1-30 days'] += $amount;
            } elseif($days <= 60) {
                $age_brackets['31-60 days']++;
                $age_amounts['31-60 days'] += $amount;
            } elseif($days <= 90) {
                $age_brackets['61-90 days']++;
                $age_amounts['61-90 days'] += $amount;
            } else {
                $age_brackets['90+ days']++;
                $age_amounts['90+ days'] += $amount;
            }
        }
        
        // Analysis table
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, 'Arrears Age Analysis:', 0, 1);
        
        $this->SetFillColor(240, 240, 240);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(40, 6, 'Age Bracket', 1, 0, 'C', true);
        $this->Cell(30, 6, 'No. of Loans', 1, 0, 'C', true);
        $this->Cell(40, 6, 'Amount (KSh)', 1, 0, 'C', true);
        $this->Cell(30, 6, 'Percentage', 1, 1, 'C', true);
        
        $total_amount = array_sum($age_amounts);
        $this->SetFont('Arial', '', 8);
        
        foreach($age_brackets as $bracket => $count) {
            $amount = $age_amounts[$bracket];
            $percentage = $total_amount > 0 ? ($amount / $total_amount) * 100 : 0;
            
            $this->Cell(40, 5, $bracket, 1, 0, 'C');
            $this->Cell(30, 5, number_format($count), 1, 0, 'C');
            $this->Cell(40, 5, number_format($amount, 2), 1, 0, 'R');
            $this->Cell(30, 5, number_format($percentage, 1) . '%', 1, 1, 'C');
        }
    }
}

// Initialize database connection
$db = new db_class();

// Get filter parameters - simplified
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Validate dates
if (empty($start_date) || empty($end_date)) {
    die('Error: Start date and end date are required');
}

// Get arrears data with proper filtering
$query = "SELECT 
    l.ref_no,
    CONCAT(ca.first_name, ' ', ca.last_name) as client_name,
    ca.phone_number,
    ls.amount as expected_amount,
    COALESCE(ls.repaid_amount, 0) as repaid_amount,
    ls.default_amount,
    ls.due_date,
    ls.status,
    DATEDIFF(CURDATE(), ls.due_date) as days_overdue,
    l.amount as loan_amount
FROM loan_schedule ls
JOIN loan l ON ls.loan_id = l.loan_id
JOIN client_accounts ca ON l.account_id = ca.account_id
WHERE ls.due_date < CURDATE() 
AND ls.status IN ('unpaid', 'partial')
AND l.status >= 2
AND ls.default_amount > 0
AND ls.due_date BETWEEN ? AND ?
ORDER BY ls.due_date ASC, ca.last_name ASC";

$stmt = $db->conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Collect data
$data = [];
$total_expected = 0;
$total_paid = 0;
$total_overdue = 0;
$client_count = [];

while($row = $result->fetch_assoc()) {
    $data[] = $row;
    $total_expected += $row['expected_amount'];
    $total_paid += $row['repaid_amount'];
    $total_overdue += $row['default_amount'];
    $client_count[$row['client_name']] = true;
}

$total_defaulters = count($client_count);

// Create PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Add summary section
$pdf->AddSummarySection($total_defaulters, $total_overdue, $start_date, $end_date);

// Add main table
if(!empty($data)) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, 'DETAILED ARREARS LISTING', 0, 1, 'L');
    $pdf->Ln(2);
    
    $pdf->AddTableHeader();
    
    $fill = false;
    foreach($data as $row) {
        // Check if we need a new page
        if($pdf->GetY() > 250) {
            $pdf->AddPage();
            $pdf->AddTableHeader();
        }
        
        $pdf->AddTableRow($row, $fill);
        $fill = !$fill;
    }
    
    // Add totals row
    $pdf->AddTotalRow($total_expected, $total_paid, $total_overdue);
    
    // Add analysis section if there's space, otherwise new page
    if($pdf->GetY() > 200) {
        $pdf->AddPage();
    }
    
    $pdf->AddAnalysisSection($data);
    
} else {
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 20, 'No overdue payments found for the selected period.', 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 10, 'All loan payments are up to date!', 0, 1, 'C');
}

// Add footer information
$pdf->SetY(-35);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 5, 'Report Parameters:', 0, 1, 'L');
$pdf->Cell(0, 4, '- Period: ' . date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date)), 0, 1, 'L');
$pdf->Cell(0, 4, '- Total Records: ' . count($data), 0, 1, 'L');

// Generate filename
$filename = 'Arrears_Report_' . date('Y-m-d') . '.pdf';

// Output PDF
$pdf->Output($filename, 'D');
?>