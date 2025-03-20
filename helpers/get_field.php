<?php 
date_default_timezone_set("Etc/GMT+8"); 
require_once '../helpers/session.php'; 
require_once '../config/class.php'; 
$db = new db_class();

if(isset($_REQUEST['loan_id'])) {
    $loan_id = $_REQUEST['loan_id'];
} else {
    header("location: ../views/payment.php");
    exit();
}

$query = "SELECT l.*, c.first_name, c.last_name, c.shareholder_no, lp.loan_type, lp.interest_rate AS product_interest_rate
          FROM loan l
          INNER JOIN client_accounts c ON l.account_id = c.account_id
          INNER JOIN loan_products lp ON l.loan_product_id = lp.id
          WHERE l.loan_id = '$loan_id'";

$tbl_loan = $db->conn->query($query);

if (!$tbl_loan) {
    die("Query failed: " . $db->conn->error);
}

$fetch = $tbl_loan->fetch_array();

if (!$fetch) {
    die("Loan not found or error in query: " . $db->conn->error);
}

$interest_rate = $fetch['interest_rate'] ?: $fetch['product_interest_rate'];
$loan_amount = $fetch['amount'];

// Remove automatic withdrawal fee calculation
$total_disbursement = $loan_amount;
?>

<hr />
<div class="form-row">
    <div class="form-group col-xl-6 col-md-6">
        <label>Payee</label>
        <input type="text" value="<?php echo $fetch['last_name'].", ".$fetch['first_name']." ".$fetch['shareholder_no']?>" name="payee" class="form-control" readonly="readonly"/>
    </div>
</div>

<hr />

<div class="form-row">
    <div class="form-group col-xl-6 col-md-6">
        <p>Loan Amount: <strong>KSh <?php echo number_format($loan_amount, 2)?></strong></p>
        <div class="form-group">
            <label>Withdrawal Fee</label>
            <input type="number" step="0.01" class="form-control" name="withdrawal_fee" required min="0" 
                   oninput="calculateDisbursement()" placeholder="Enter withdrawal fee"/>
        </div>
        <p>Total Disbursement: <strong id="totalDisbursement">KSh <?php echo number_format($total_disbursement, 2)?></strong></p>
    </div>
    <div class="form-group col-xl-6 col-md-6">
        <label>Amount to Disburse</label>
        <input type="number" step="0.01" class="form-control" name="pay_amount" required min="0" 
               max="<?php echo $loan_amount ?>" value="<?php echo $loan_amount ?>"/>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-12">
        <label>Disbursement Date: <strong><?php echo date('M d, Y') ?></strong></label>
    </div>
</div>

<input type="hidden" name="loan_amount" value="<?php echo $loan_amount ?>"/>
<input type="hidden" name="account_id" value="<?php echo $fetch['account_id'] ?>"/>
<input type="hidden" name="penalty" value="0"/>
<input type="hidden" name="overdue" value="0"/>

<script>
function calculateDisbursement() {
    const loanAmount = <?php echo $loan_amount ?>;
    const withdrawalFee = parseFloat(document.getElementsByName('withdrawal_fee')[0].value) || 0;
    const totalDisbursement = loanAmount - withdrawalFee;
    
    document.getElementById('totalDisbursement').textContent = 'KSh ' + totalDisbursement.toFixed(2)
        .replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementsByName('pay_amount')[0].value = totalDisbursement.toFixed(2);
}
</script>