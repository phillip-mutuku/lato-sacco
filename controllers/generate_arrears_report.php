<?php
require_once '../config/class.php';
require_once('../helpers/fpdf/fpdf.php');

class PDF extends FPDF {
    function Header() {
        $this->Image('../public/image/mylogo.png', 10, 6, 30);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(80);
        $this->Cell(30, 10, 'LATO SACCO LTD', 0, 0, 'C');
        $this->Ln(20);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$db = new db_class();

// Get date parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get defaulters data
$query = "SELECT l.ref_no, 
                 CONCAT(ca.first_name, ' ', ca.last_name) as client_name,
                 ls.amount as expected_amount,
                 ls.repaid_amount,
                 ls.default_amount,
                 ls.due_date,
                 DATEDIFF(CURDATE(), ls.due_date) as days_overdue
          FROM loan_schedule ls
          JOIN loan l ON ls.loan_id = l.loan_id
          JOIN client_accounts ca ON l.account_id = ca.account_id
          WHERE ls.default_amount > 0 
          AND ls.due_date < CURDATE()
          AND ls.due_date BETWEEN ? AND ?
          ORDER BY ls.due_date DESC";

$stmt = $db->conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Create PDF
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);

// Title
$pdf->Cell(0, 10, 'Loan Defaults Report', 0, 1, 'C');
$pdf->Cell(0, 10, "Period: " . date('M d, Y', strtotime($start_date)) . " to " . date('M d, Y', strtotime($end_date)), 0, 1, 'C');
$pdf->Ln(10);

// Table Header
$pdf->SetFillColor(3, 15, 87);
$pdf->SetTextColor(255);
$pdf->Cell(30, 8, 'Ref No', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Client Name', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Expected', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Defaulted', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Due Date', 1, 1, 'C', true);

// Table Data
$pdf->SetFillColor(255);
$pdf->SetTextColor(0);
while($row = $result->fetch_assoc()) {
    $pdf->Cell(30, 8, $row['ref_no'], 1);
    $pdf->Cell(50, 8, $row['client_name'], 1);
    $pdf->Cell(35, 8, number_format($row['expected_amount'], 2), 1);
    $pdf->Cell(35, 8, number_format($row['default_amount'], 2), 1);
    $pdf->Cell(35, 8, date('M d, Y', strtotime($row['due_date'])), 1);
    $pdf->Ln();
}

// Output PDF
$pdf->Output('Arrears_Report.pdf', 'D');
?>