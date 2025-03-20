<?php
require_once '../config/class.php';
require_once '../helpers/fpdf/fpdf.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die('User not logged in');
}

$db = new db_class();
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get user name
$user_id = $_SESSION['user_id'];
$user_query = "SELECT username FROM user WHERE user_id = $user_id";
$user_result = $db->conn->query($user_query);
$user_name = $user_result->fetch_assoc()['username'];

class PDF extends FPDF {
    private $reconciled_by;
    
    function setReconciledBy($name) {
        $this->reconciled_by = $name;
    }
    
    function Header() {
        $this->Image('../public/image/mylogo.png', 10, 6, 30);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(30);
        $this->Cell(0, 10, 'Lato Sacco LTD', 0, 1, 'L');
        $this->Cell(0, 10, 'Daily Reconciliation Report', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'From: ' . $_GET['start_date'], 0, 1, 'R');
        $this->Cell(0, 5, 'To: ' . $_GET['end_date'], 0, 1, 'R');
        $this->Cell(0, 5, 'Reconciled by: ' . $this->reconciled_by, 0, 1, 'R');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->setReconciledBy($user_name);
$pdf->AliasNbPages();
$pdf->AddPage('L');

// Float Management Section
$float_query = "SELECT 
    COALESCE(SUM(CASE WHEN type = 'add' THEN amount ELSE 0 END), 0) as total_added,
    COALESCE(SUM(CASE WHEN type = 'offload' THEN amount ELSE 0 END), 0) as total_offloaded
    FROM float_management 
    WHERE DATE(date_created) BETWEEN ? AND ?";
$stmt = $db->conn->prepare($float_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$float_result = $stmt->get_result()->fetch_assoc();

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Float Management', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(70, 7, 'Opening Float:', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($float_result['total_added'], 2), 1, 1);
$pdf->Cell(70, 7, 'Total Offloaded:', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($float_result['total_offloaded'], 2), 1, 1);
$pdf->Cell(70, 7, 'Closing Float:', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($float_result['total_added'] - $float_result['total_offloaded'], 2), 1, 1);
$pdf->Ln(10);

// Group Savings
$group_savings_query = "SELECT SUM(amount) as total, COUNT(*) as count 
                       FROM group_savings 
                       WHERE DATE(date_saved) BETWEEN ? AND ?";
$stmt = $db->conn->prepare($group_savings_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$group_savings = $stmt->get_result()->fetch_assoc();
$total_group_savings = $group_savings['total'] ?? 0;

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Group Savings', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(70, 7, 'Total Transactions:', 1);
$pdf->Cell(60, 7, ($group_savings['count'] ?? 0) . ' transactions', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($total_group_savings, 2), 1, 1);
$pdf->Ln(5);

// Business Group Transactions (Savings)
$business_savings_query = "SELECT SUM(amount) as total, COUNT(*) as count 
                          FROM business_group_transactions 
                          WHERE type = 'Savings' AND DATE(date) BETWEEN ? AND ?";
$stmt = $db->conn->prepare($business_savings_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$business_savings = $stmt->get_result()->fetch_assoc();
$total_business_savings = $business_savings['total'] ?? 0;

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Business Group Savings', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(70, 7, 'Total Transactions:', 1);
$pdf->Cell(60, 7, ($business_savings['count'] ?? 0) . ' transactions', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($total_business_savings, 2), 1, 1);
$pdf->Ln(5);

// Group Withdrawals
$group_withdrawals_query = "SELECT SUM(amount) as total, COUNT(*) as count 
                           FROM group_withdrawals 
                           WHERE DATE(date_withdrawn) BETWEEN ? AND ?";
$stmt = $db->conn->prepare($group_withdrawals_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$group_withdrawals = $stmt->get_result()->fetch_assoc();
$total_group_withdrawals = $group_withdrawals['total'] ?? 0;

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Group Withdrawals', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(70, 7, 'Total Transactions:', 1);
$pdf->Cell(60, 7, ($group_withdrawals['count'] ?? 0) . ' transactions', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($total_group_withdrawals, 2), 1, 1);
$pdf->Ln(5);

// Business Group Transactions (Withdrawals)
$business_withdrawals_query = "SELECT 
    SUM(CASE WHEN type = 'Withdrawal' THEN amount ELSE 0 END) as total_withdrawals,
    SUM(CASE WHEN type = 'Withdrawal Fee' THEN amount ELSE 0 END) as total_fees,
    COUNT(*) as count
    FROM business_group_transactions 
    WHERE type IN ('Withdrawal', 'Withdrawal Fee') 
    AND DATE(date) BETWEEN ? AND ?";
$stmt = $db->conn->prepare($business_withdrawals_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$business_withdrawals = $stmt->get_result()->fetch_assoc();
$total_business_withdrawals = $business_withdrawals['total_withdrawals'] ?? 0;
$total_withdrawal_fees = $business_withdrawals['total_fees'] ?? 0;

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Business Group Withdrawals', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(70, 7, 'Total Transactions:', 1);
$pdf->Cell(60, 7, ($business_withdrawals['count'] ?? 0) . ' transactions', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($total_business_withdrawals, 2), 1, 1);
$pdf->Cell(70, 7, 'Total Withdrawal Fees:', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($total_withdrawal_fees, 2), 1, 1);
$pdf->Ln(5);

// Individual Savings and Withdrawals
$individual_transactions_query = "SELECT 
    SUM(CASE WHEN type = 'Savings' THEN amount ELSE 0 END) as total_savings,
    SUM(CASE WHEN type = 'Withdrawal' THEN amount ELSE 0 END) as total_withdrawals,
    SUM(CASE WHEN type = 'Withdrawal' THEN withdrawal_fee ELSE 0 END) as total_fees,
    COUNT(*) as count
    FROM savings 
    WHERE DATE(date) BETWEEN ? AND ?";
$stmt = $db->conn->prepare($individual_transactions_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$individual_transactions = $stmt->get_result()->fetch_assoc();
$total_individual_savings = $individual_transactions['total_savings'] ?? 0;
$total_individual_withdrawals = $individual_transactions['total_withdrawals'] ?? 0;
$total_individual_fees = $individual_transactions['total_fees'] ?? 0;

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Individual Transactions', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(70, 7, 'Total Savings:', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($total_individual_savings, 2), 1, 1);
$pdf->Cell(70, 7, 'Total Withdrawals:', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($total_individual_withdrawals, 2), 1, 1);
$pdf->Cell(70, 7, 'Total Withdrawal Fees:', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($total_individual_fees, 2), 1, 1);
$pdf->Ln(5);

// Get other transaction totals
$total_payments = getTransactionTotal($db, "SELECT SUM(pay_amount) as total FROM payment WHERE DATE(date_created) BETWEEN ? AND ?", $start_date, $end_date);
$total_repayments = getTransactionTotal($db, "SELECT SUM(amount_repaid) as total FROM loan_repayments WHERE DATE(date_paid) BETWEEN ? AND ?", $start_date, $end_date);
$total_expenses = getTransactionTotal($db, "SELECT SUM(amount) as total FROM expenses WHERE DATE(date) BETWEEN ? AND ?", $start_date, $end_date);

// Summary Section
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 15, 'Summary', 0, 1);
$pdf->SetFont('Arial', '', 10);

// Calculate totals
$total_inflows = $total_group_savings + $total_business_savings + $total_repayments + $total_individual_savings;
$total_outflows = $total_group_withdrawals + $total_business_withdrawals + $total_payments + $total_expenses + $total_individual_withdrawals;
$total_fees = $total_withdrawal_fees + $total_individual_fees;

$pdf->Cell(70, 7, 'Total Inflows:', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($total_inflows, 2), 1, 1);
$pdf->Cell(70, 7, 'Total Outflows:', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($total_outflows, 2), 1, 1);
$pdf->Cell(70, 7, 'Total Fees:', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($total_fees, 2), 1, 1);
$pdf->Cell(70, 7, 'Net Position:', 1);
$pdf->Cell(90, 7, 'KSh ' . number_format($total_inflows - $total_outflows + $total_fees, 2), 1, 1);

// Helper function for getting transaction totals
function getTransactionTotal($db, $query, $start_date, $end_date) {
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['total'] ?? 0;
}

// Output the PDF
$pdf->Output('D', 'Lato_Sacco_Daily_Reconciliation_Report_' . date('Y-m-d') . '.pdf');
?>