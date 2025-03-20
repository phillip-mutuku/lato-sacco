<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

// Log function
function logError($message) {
    file_put_contents('pdf_error_log.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

try {
    require_once('../config/class.php');
    require_once('../helpers/fpdf/fpdf.php');

    class PDF extends FPDF
    {
        function Header()
        {
            // Logo
            $this->Image('../public/image/mylogo.png', 10, 6, 30);
            // Arial bold 15
            $this->SetFont('Arial', 'B', 15);
            // Move to the right
            $this->Cell(80);
            // Title
            $this->Cell(30, 10, 'Lato SACCO Detailed Report', 0, 0, 'C');
            // Date and Time
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');
            // Line break
            $this->Ln(20);
        }

        function Footer()
        {
            // Position at 1.5 cm from bottom
            $this->SetY(-15);
            // Arial italic 8
            $this->SetFont('Arial', 'I', 8);
            // Page number
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }

        function SectionTitle($label)
        {
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 6, $label, 0, 1, 'L');
            $this->Ln(4);
        }

        function TableHeader($header)
        {
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(200, 200, 200);
            foreach($header as $col)
                $this->Cell(40, 7, $col, 1, 0, 'C', true);
            $this->Ln();
        }

        function TableRow($data)
        {
            $this->SetFont('Arial', '', 10);
            foreach($data as $col)
                $this->Cell(40, 6, $col, 1);
            $this->Ln();
        }
    }

    $db = new db_class();

    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // 1. Executive Summary
    $pdf->SectionTitle('1. Executive Summary');
    $total_loans = $db->conn->query("SELECT COUNT(*) as count FROM `loan`")->fetch_assoc()['count'];
    $active_loans = $db->conn->query("SELECT COUNT(*) as count FROM `loan` WHERE `status`='2'")->fetch_assoc()['count'];
    $total_loan_amount = $db->conn->query("SELECT SUM(amount) as total FROM `loan`")->fetch_assoc()['total'];
    $total_clients = $db->conn->query("SELECT COUNT(*) as count FROM `client_accounts`")->fetch_assoc()['count'];
    $total_savings = $db->conn->query("SELECT SUM(amount) as total FROM `savings`")->fetch_assoc()['total'];

    $pdf->TableHeader(['Metric', 'Value']);
    $pdf->TableRow(['Total Loans', $total_loans]);
    $pdf->TableRow(['Active Loans', $active_loans]);
    $pdf->TableRow(['Total Loan Amount', 'KSh ' . number_format($total_loan_amount, 2)]);
    $pdf->TableRow(['Total Clients', $total_clients]);
    $pdf->TableRow(['Total Savings', 'KSh ' . number_format($total_savings, 2)]);

    // 2. Loan Statistics
    $pdf->AddPage();
    $pdf->SectionTitle('2. Loan Statistics');
    
    $loan_stats = $db->conn->query("SELECT 
        AVG(amount) as avg_amount, 
        MAX(amount) as max_amount, 
        MIN(amount) as min_amount,
        AVG(DATEDIFF(CURDATE(), date_applied)) as avg_loan_age
    FROM `loan`")->fetch_assoc();

    $pdf->TableHeader(['Statistic', 'Value']);
    $pdf->TableRow(['Average Loan Amount', 'KSh ' . number_format($loan_stats['avg_amount'], 2)]);
    $pdf->TableRow(['Maximum Loan Amount', 'KSh ' . number_format($loan_stats['max_amount'], 2)]);
    $pdf->TableRow(['Minimum Loan Amount', 'KSh ' . number_format($loan_stats['min_amount'], 2)]);
    $pdf->TableRow(['Average Loan Age', round($loan_stats['avg_loan_age'], 2) . ' days']);

    // 3. Client Demographics
    $pdf->SectionTitle('3. Client Demographics');
    
    $client_demo = $db->conn->query("SELECT 
        COUNT(CASE WHEN account_type = 'Individual' THEN 1 END) as individual_count,
        COUNT(CASE WHEN account_type = 'Business' THEN 1 END) as business_count
    FROM `client_accounts`")->fetch_assoc();

    $pdf->TableHeader(['Account Type', 'Count']);
    $pdf->TableRow(['Individual Accounts', $client_demo['individual_count']]);
    $pdf->TableRow(['Business Accounts', $client_demo['business_count']]);

    // 4. Recent Loans
    $pdf->AddPage();
    $pdf->SectionTitle('4. Recent Loans');

    $pdf->TableHeader(['Ref No', 'Amount', 'Date Applied', 'Status', 'Client Name']);
    $recent_loans = $db->conn->query("SELECT l.*, c.first_name, c.last_name 
        FROM `loan` l 
        JOIN `client_accounts` c ON l.account_id = c.account_id 
        ORDER BY l.date_applied DESC LIMIT 10");

    while ($row = $recent_loans->fetch_assoc()) {
        $pdf->TableRow([
            $row['ref_no'],
            'KSh ' . number_format($row['amount'], 2),
            date('Y-m-d', strtotime($row['date_applied'])),
            $row['status'],
            $row['first_name'] . ' ' . $row['last_name']
        ]);
    }

    // 5. Financial Summary
    $pdf->AddPage();
    $pdf->SectionTitle('5. Financial Summary');

    $monthly_summary = $db->conn->query("SELECT 
        DATE_FORMAT(date_created, '%Y-%m') as month,
        SUM(CASE WHEN type = 'loan' THEN amount ELSE 0 END) as loans_issued,
        SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END) as payments_received,
        SUM(CASE WHEN type = 'savings' THEN amount ELSE 0 END) as savings_deposited
    FROM (
        SELECT date_applied as date_created, amount, 'loan' as type FROM `loan`
        UNION ALL
        SELECT date_created, pay_amount, 'payment' FROM `payment`
        UNION ALL
        SELECT date, amount, 'savings' FROM `savings`
    ) as combined_data
    GROUP BY DATE_FORMAT(date_created, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6");

    $pdf->TableHeader(['Month', 'Loans Issued', 'Payments Received', 'Savings Deposited']);
    while ($row = $monthly_summary->fetch_assoc()) {
        $pdf->TableRow([
            $row['month'],
            'KSh ' . number_format($row['loans_issued'], 2),
            'KSh ' . number_format($row['payments_received'], 2),
            'KSh ' . number_format($row['savings_deposited'], 2)
        ]);
    }

    // Output the PDF
    $pdf_content = $pdf->Output('S');
    
    // Clear any previous output
    ob_clean();

    // Set the appropriate headers
    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($pdf_content));
    header('Content-Disposition: attachment; filename="Lato_SACCO_Detailed_Report_' . date('Y-m-d') . '.pdf"');
    
    // Output the PDF content
    echo $pdf_content;
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: text/plain');
    echo "An error occurred while generating the PDF:\n\n";
    echo $e->getMessage() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
    logError($e->getMessage() . "\n" . $e->getTraceAsString());
}

// End output buffering and flush output
ob_end_flush();
?>