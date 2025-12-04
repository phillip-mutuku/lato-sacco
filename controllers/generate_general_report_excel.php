<?php
date_default_timezone_set("Africa/Nairobi");
require_once '../helpers/session.php';
require_once '../config/class.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    $_SESSION['error_msg'] = "Unauthorized access";
    header('Location: ../views/index.php');
    exit();
}

require_once '../components/general_reporting/financial_utilities.php';
require_once '../components/general_reporting/debit.php';
require_once '../components/general_reporting/credit.php';

$filters = FinancialUtilities::initializeFilters();
$start_date = $filters['start_date'];
$end_date = $filters['end_date'];
$filter_type = $filters['filter_type'];
$opening_date = $filters['opening_date'];

$db = new db_class();
$debitCalc = new DebitCalculator($db, $start_date, $end_date, $opening_date);
$creditCalc = new CreditCalculator($db, $start_date, $end_date, $opening_date);

$debit_data = $debitCalc->getDebitData();
$credit_data = $creditCalc->getCreditData();
$total_debit = $debitCalc->getTotalDebit();
$total_credit = $creditCalc->getTotalCredit();
$net_position = $total_debit - $total_credit;
$is_profit = $net_position > 0;

$filename = 'LATO_General_Financial_Report_' . date('Ymd', strtotime($start_date)) . '_to_' . date('Ymd', strtotime($end_date)) . '.xls';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Calibri, Arial, sans-serif; }
        .title { font-size: 18pt; font-weight: bold; color: #51087E; text-align: center; height: 35px; }
        .subtitle { font-size: 12pt; font-weight: bold; color: #333333; text-align: center; height: 20px; }
        .metadata { font-size: 10pt; font-style: italic; color: #666666; text-align: center; }
        .summary-header { font-size: 12pt; font-weight: bold; color: #51087E; background-color: #F8F9FA; }
        .summary-label { font-size: 11pt; font-weight: bold; background-color: #F8F9FA; }
        .summary-value { font-size: 11pt; font-weight: bold; color: #51087E; background-color: #F8F9FA; }
        .summary-amount-green { font-size: 12pt; font-weight: bold; color: #28A745; background-color: #F8F9FA; }
        .summary-amount-red { font-size: 12pt; font-weight: bold; color: #DC3545; background-color: #F8F9FA; }
        .alert-profit { background-color: #D4EDDA; color: #155724; font-weight: bold; text-align: center; border: 1px solid #C3E6CB; padding: 5px; }
        .alert-loss { background-color: #FFF3CD; color: #856404; font-weight: bold; text-align: center; border: 1px solid #FFEAA7; padding: 5px; }
        .table-header-debit { background-color: #28A745; color: #FFFFFF; font-weight: bold; font-size: 10pt; text-align: center; border: 2px solid #28A745; height: 25px; }
        .table-header-credit { background-color: #DC3545; color: #FFFFFF; font-weight: bold; font-size: 10pt; text-align: center; border: 2px solid #DC3545; height: 25px; }
        .data-cell { font-size: 9pt; border: 1px solid #D1D3E2; padding: 3px; }
        .data-cell-right { font-size: 9pt; border: 1px solid #D1D3E2; text-align: right; padding: 3px; }
        .amount-green { font-weight: bold; color: #28A745; font-size: 9pt; border: 1px solid #D1D3E2; text-align: right; }
        .amount-red { font-weight: bold; color: #DC3545; font-size: 9pt; border: 1px solid #D1D3E2; text-align: right; }
        .alt-row { background-color: #F8F9FA; }
        .total-row-debit { background-color: #28A745; color: #FFFFFF; font-weight: bold; font-size: 10pt; text-align: center; border: 2px solid #28A745; height: 25px; }
        .total-row-credit { background-color: #DC3545; color: #FFFFFF; font-weight: bold; font-size: 10pt; text-align: center; border: 2px solid #DC3545; height: 25px; }
        .final-summary { background-color: #51087E; color: #FFFFFF; font-weight: bold; font-size: 11pt; text-align: center; border: 2px solid #51087E; height: 30px; }
        .footer-info { font-size: 9pt; font-style: italic; color: #666666; padding-top: 10px; }
    </style>
</head>
<body>
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr><td colspan="10" class="title">LATO SACCO - GENERAL FINANCIAL REPORT</td></tr>
        <tr><td colspan="10" class="subtitle">Income & Expenditure Analysis</td></tr>
        <tr><td colspan="10" class="subtitle">Period: <?php echo date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date)); ?></td></tr>
        <tr><td colspan="10" class="metadata">Generated: <?php echo date('F d, Y \a\t h:i A'); ?> | By: <?php echo $_SESSION['username'] ?? 'System'; ?></td></tr>
        <tr><td colspan="10" style="height: 10px;"></td></tr>
        
        <tr><td colspan="10" class="summary-header">📊 EXECUTIVE SUMMARY</td></tr>
        <?php if ($filter_type !== 'credit'): ?>
        <tr>
            <td colspan="4" class="summary-label">Total Debit (Income):</td>
            <td colspan="6" class="summary-amount-green">KSh <?php echo number_format($total_debit, 2); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($filter_type !== 'debit'): ?>
        <tr>
            <td colspan="4" class="summary-label">Total Credit (Expenses):</td>
            <td colspan="6" class="summary-amount-red">KSh <?php echo number_format($total_credit, 2); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($filter_type === 'all'): ?>
        <tr>
            <td colspan="4" class="summary-label">Net Position (<?php echo $is_profit ? 'Profit' : 'Loss'; ?>):</td>
            <td colspan="6" class="<?php echo $is_profit ? 'summary-amount-green' : 'summary-amount-red'; ?>">KSh <?php echo number_format($net_position, 2); ?></td>
        </tr>
        <tr><td colspan="10" class="<?php echo $is_profit ? 'alert-profit' : 'alert-loss'; ?>"><?php echo $is_profit ? '✅ PROFITABLE PERIOD' : '⚠️ LOSS PERIOD - Review expenses'; ?></td></tr>
        <?php endif; ?>
        <tr><td colspan="10" style="height: 15px;"></td></tr>
        
        <?php if ($filter_type !== 'credit' && !empty($debit_data)): ?>
        <tr><td colspan="10" class="summary-header">💰 DEBIT - INCOME SOURCES</td></tr>
        <tr><td colspan="10" style="height: 5px;"></td></tr>
        <tr>
            <td class="table-header-debit">Category</td>
            <td class="table-header-debit">Item</td>
            <td class="table-header-debit">Opening (<?php echo date('d/m/Y', strtotime($opening_date)); ?>)</td>
            <td class="table-header-debit">Transactions</td>
            <td class="table-header-debit">Closing (<?php echo date('d/m/Y', strtotime($end_date)); ?>)</td>
        </tr>
        <?php 
        $row_num = 0;
        $total_opening_debit = 0;
        $total_trans_debit = 0;
        $total_closing_debit = 0;
        foreach ($debit_data as $item): 
            $row_num++;
            $row_class = $row_num % 2 == 0 ? 'alt-row' : '';
            $total_opening_debit += $item['opening_balance'];
            $total_trans_debit += $item['transactions'];
            $total_closing_debit += $item['closing_balance'];
        ?>
        <tr class="<?php echo $row_class; ?>">
            <td class="data-cell"><?php echo htmlspecialchars($item['category']); ?></td>
            <td class="data-cell"><?php echo htmlspecialchars($item['name']); ?></td>
            <td class="data-cell-right"><?php echo number_format($item['opening_balance'], 2); ?></td>
            <td class="amount-green"><?php echo number_format($item['transactions'], 2); ?></td>
            <td class="data-cell-right"><?php echo number_format($item['closing_balance'], 2); ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="2" class="total-row-debit">TOTAL DEBIT</td>
            <td class="total-row-debit" style="text-align: right;">KSh <?php echo number_format($total_opening_debit, 2); ?></td>
            <td class="total-row-debit" style="text-align: right;">KSh <?php echo number_format($total_trans_debit, 2); ?></td>
            <td class="total-row-debit" style="text-align: right;">KSh <?php echo number_format($total_closing_debit, 2); ?></td>
        </tr>
        <tr><td colspan="10" style="height: 15px;"></td></tr>
        <?php endif; ?>
        
        <?php if ($filter_type !== 'debit' && !empty($credit_data)): ?>
        <tr><td colspan="10" class="summary-header">💳 CREDIT - EXPENSE CATEGORIES</td></tr>
        <tr><td colspan="10" style="height: 5px;"></td></tr>
        <tr>
            <td class="table-header-credit">Main Category</td>
            <td class="table-header-credit">Expense Name</td>
            <td class="table-header-credit">Opening (<?php echo date('d/m/Y', strtotime($opening_date)); ?>)</td>
            <td class="table-header-credit">Transactions</td>
            <td class="table-header-credit">Closing (<?php echo date('d/m/Y', strtotime($end_date)); ?>)</td>
        </tr>
        <?php 
        $row_num = 0;
        $total_opening_credit = 0;
        $total_trans_credit = 0;
        $total_closing_credit = 0;
        foreach ($credit_data as $item): 
            $row_num++;
            $row_class = $row_num % 2 == 0 ? 'alt-row' : '';
            $total_opening_credit += $item['opening_balance'];
            $total_trans_credit += $item['transactions'];
            $total_closing_credit += $item['closing_balance'];
        ?>
        <tr class="<?php echo $row_class; ?>">
            <td class="data-cell"><?php echo htmlspecialchars($item['main_category']); ?></td>
            <td class="data-cell"><?php echo htmlspecialchars($item['category']); ?></td>
            <td class="data-cell-right"><?php echo number_format($item['opening_balance'], 2); ?></td>
            <td class="amount-red"><?php echo number_format($item['transactions'], 2); ?></td>
            <td class="data-cell-right"><?php echo number_format($item['closing_balance'], 2); ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="2" class="total-row-credit">TOTAL CREDIT</td>
            <td class="total-row-credit" style="text-align: right;">KSh <?php echo number_format($total_opening_credit, 2); ?></td>
            <td class="total-row-credit" style="text-align: right;">KSh <?php echo number_format($total_trans_credit, 2); ?></td>
            <td class="total-row-credit" style="text-align: right;">KSh <?php echo number_format($total_closing_credit, 2); ?></td>
        </tr>
        <tr><td colspan="10" style="height: 15px;"></td></tr>
        <?php endif; ?>
        
        <?php if ($filter_type === 'all'): ?>
        <tr><td colspan="10" class="summary-header">📈 FINAL FINANCIAL SUMMARY</td></tr>
        <tr><td colspan="10" style="height: 5px;"></td></tr>
        <tr>
            <td colspan="4" class="final-summary">Total Debit (Income)</td>
            <td colspan="6" class="final-summary" style="text-align: right;">KSh <?php echo number_format($total_debit, 2); ?></td>
        </tr>
        <tr>
            <td colspan="4" class="final-summary">Total Credit (Expenses)</td>
            <td colspan="6" class="final-summary" style="text-align: right;">KSh <?php echo number_format($total_credit, 2); ?></td>
        </tr>
        <tr>
            <td colspan="4" class="final-summary">NET POSITION (<?php echo $is_profit ? 'PROFIT' : 'LOSS'; ?>)</td>
            <td colspan="6" class="final-summary" style="text-align: right;">KSh <?php echo number_format($net_position, 2); ?></td>
        </tr>
        <tr><td colspan="10" style="height: 10px;"></td></tr>
        <?php endif; ?>
        
        <tr><td colspan="10" class="footer-info">📄 Report Type: <?php echo $filter_type === 'all' ? 'Complete Analysis' : ($filter_type === 'debit' ? 'Income Only' : 'Expenses Only'); ?> | Generated by LATO Management System © <?php echo date('Y'); ?></td></tr>
    </table>
</body>
</html>