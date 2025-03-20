<?php
// Prevent any output before PDF generation
ob_start();

require('../config/class.php');
require('../helpers/fpdf/fpdf.php');

class PDF extends FPDF {
    protected $start_date;
    protected $end_date;

    function setDates($start_date, $end_date) {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    function Header() {
        // Logo
        $this->Image('../public/image/mylogo.png',10,6,30);
        // Title
        $this->SetFont('Arial','B',15);
        $this->Cell(80);
        $this->Cell(100,10,'Comprehensive Disbursement Report',0,0,'C');
        $this->Ln(10);
        
        // Date Range
        $this->SetFont('Arial','',11);
        $this->Cell(80);
        if ($this->start_date && $this->end_date) {
            $formatted_start = date('M d, Y', strtotime($this->start_date));
            $formatted_end = date('M d, Y', strtotime($this->end_date));
            $this->Cell(100,10,"Period: $formatted_start to $formatted_end",0,0,'C');
        }
        $this->Ln(20);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
        $this->Cell(0,10,'Generated on: '.date('M d, Y H:i:s'),0,0,'R');
    }

    function SectionTitle($title) {
        $this->SetFont('Arial','B',12);
        $this->SetFillColor(81, 8, 126); // #51087E
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0,10,$title,0,1,'L',true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(5);
    }

    function SummaryBox($title, $value, $width = 65) {
        $this->SetFont('Arial','B',10);
        $this->Cell($width,10,$title,1,0,'L');
        $this->SetFont('Arial','',10);
        $this->Cell($width,10,$value,1,0,'R');
        $this->Ln();
    }
}

// Initialize PDF
$pdf = new PDF('L', 'mm', 'A4');
$pdf->AliasNbPages();

// Get date range from URL parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Set dates for the header
$pdf->setDates($start_date, $end_date);
$pdf->AddPage();

$db = new db_class();

// 1. Summary Statistics Section
$pdf->SectionTitle('Summary Statistics');

// Calculate summary statistics
$summary_query = "SELECT 
    COUNT(*) as total_loans,
    COALESCE(SUM(CASE WHEN l.status = 1 THEN l.amount ELSE 0 END), 0) as pending_amount,
    COALESCE(SUM(CASE WHEN l.status = 3 THEN l.amount ELSE 0 END), 0) as disbursed_amount,
    COALESCE(SUM(l.amount), 0) as total_amount
    FROM loan l 
    WHERE DATE(l.date_applied) BETWEEN ? AND ?";

$stmt = $db->conn->prepare($summary_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

// Display summary statistics
$pdf->SummaryBox('Total Loans:', number_format($summary['total_loans']));
$pdf->SummaryBox('Pending Amount:', 'KSh ' . number_format($summary['pending_amount'], 2));
$pdf->SummaryBox('Disbursed Amount:', 'KSh ' . number_format($summary['disbursed_amount'], 2));
$pdf->SummaryBox('Total Amount:', 'KSh ' . number_format($summary['total_amount'], 2));

$pdf->Ln(10);

// 2. Pending Disbursements Section
$pdf->AddPage();
$pdf->SectionTitle('Pending Disbursements');

// Get pending loans
$pending_query = "SELECT l.*, c.first_name, c.last_name, c.phone_number 
                 FROM loan l 
                 INNER JOIN client_accounts c ON l.account_id = c.account_id 
                 WHERE l.status = 1 AND DATE(l.date_applied) BETWEEN ? AND ?";

$stmt = $db->conn->prepare($pending_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$pending_loans = $stmt->get_result();

// Set up pending loans table headers
$pdf->SetFont('Arial','B',9);
$pending_headers = array(
    array('width' => 30, 'text' => 'Ref No'),
    array('width' => 50, 'text' => 'Borrower'),
    array('width' => 35, 'text' => 'Phone'),
    array('width' => 35, 'text' => 'Amount'),
    array('width' => 40, 'text' => 'Date Applied'),
    array('width' => 40, 'text' => 'Date Approved')
);

foreach ($pending_headers as $header) {
    $pdf->Cell($header['width'], 10, $header['text'], 1);
}
$pdf->Ln();

// Print pending loans data
$pdf->SetFont('Arial','',8);
while($loan = $pending_loans->fetch_assoc()) {
    $pdf->Cell(30, 10, $loan['ref_no'], 1);
    $pdf->Cell(50, 10, $loan['first_name'] . ' ' . $loan['last_name'], 1);
    $pdf->Cell(35, 10, $loan['phone_number'], 1);
    $pdf->Cell(35, 10, 'KSh ' . number_format($loan['amount'], 2), 1);
    $pdf->Cell(40, 10, date('M d, Y', strtotime($loan['date_applied'])), 1);
    $pdf->Cell(40, 10, $loan['date_approved'] ? date('M d, Y', strtotime($loan['date_approved'])) : 'Pending', 1);
    $pdf->Ln();
}

// 3. Completed Disbursements Section
$pdf->AddPage();
$pdf->SectionTitle('Completed Disbursements');

// Get completed disbursements
$completed_query = "SELECT p.*, l.ref_no, c.first_name, c.last_name, u.username as disbursed_by 
                   FROM payment p 
                   INNER JOIN loan l ON p.loan_id = l.loan_id 
                   INNER JOIN client_accounts c ON l.account_id = c.account_id 
                   LEFT JOIN user u ON p.user_id = u.user_id 
                   WHERE DATE(p.date_created) BETWEEN ? AND ?
                   ORDER BY p.date_created DESC";

$stmt = $db->conn->prepare($completed_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$completed_disbursements = $stmt->get_result();

// Set up completed disbursements table headers
$pdf->SetFont('Arial','B',9);
$completed_headers = array(
    array('width' => 25, 'text' => 'Ref No'),
    array('width' => 25, 'text' => 'Receipt No'),
    array('width' => 45, 'text' => 'Borrower'),
    array('width' => 35, 'text' => 'Amount'),
    array('width' => 25, 'text' => 'W/D Fee'),
    array('width' => 35, 'text' => 'Date'),
    array('width' => 35, 'text' => 'Time'),
    array('width' => 35, 'text' => 'Disbursed By')
);

foreach ($completed_headers as $header) {
    $pdf->Cell($header['width'], 10, $header['text'], 1);
}
$pdf->Ln();

// Print completed disbursements data
$pdf->SetFont('Arial','',8);
$total_disbursed = 0;
$total_withdrawal_fee = 0;

while($row = $completed_disbursements->fetch_assoc()) {
    $pdf->Cell(25, 10, $row['ref_no'], 1);
    $pdf->Cell(25, 10, $row['receipt_no'] ?? 'N/A', 1);
    $pdf->Cell(45, 10, $row['first_name'] . ' ' . $row['last_name'], 1);
    $pdf->Cell(35, 10, 'KSh '.number_format($row['pay_amount'],2), 1);
    $pdf->Cell(25, 10, 'KSh '.number_format($row['withdrawal_fee'],2), 1);
    $pdf->Cell(35, 10, date('M d, Y', strtotime($row['date_created'])), 1);
    $pdf->Cell(35, 10, date('H:i:s', strtotime($row['date_created'])), 1);
    $pdf->Cell(35, 10, $row['disbursed_by'] ?? 'N/A', 1);
    $pdf->Ln();
    
    $total_disbursed += $row['pay_amount'];
    $total_withdrawal_fee += $row['withdrawal_fee'];
}

// Add disbursement summary
$pdf->Ln(10);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(100,10,'Disbursement Summary:',0);
$pdf->Ln();
$pdf->SetFont('Arial','',10);
$pdf->Cell(100,8,'Total Disbursed: KSh '.number_format($total_disbursed,2),0);
$pdf->Ln();
$pdf->Cell(100,8,'Total Withdrawal Fees: KSh '.number_format($total_withdrawal_fee,2),0);
$pdf->Ln();
$pdf->Cell(100,8,'Number of Disbursements: '.$completed_disbursements->num_rows,0);

// Clear the output buffer
ob_end_clean();

// Generate filename with date range
$filename = 'Comprehensive_Disbursement_Report_'.date('Y-m-d', strtotime($start_date)).'_to_'.date('Y-m-d', strtotime($end_date)).'.pdf';

// Output PDF
$pdf->Output('D', $filename);
?>