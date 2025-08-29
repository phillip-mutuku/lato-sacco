<?php
// components/reconciliation/transactions_filter.php
?>

<style>
.transaction-summary {
    background-color: #f8f9fc;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.transaction-summary h6 {
    color: #51087E;
    margin-bottom: 10px;
}

.money-flow-tabs .nav-link {
    color: #51087E;
    font-weight: 500;
    padding: 12px 20px;
    border-radius: 10px 10px 0 0;
    transition: all 0.3s ease;
}

.money-flow-tabs .nav-link.active {
    color: #fff;
    background-color: #51087E;
    border-color: #51087E;
}

.money-flow-tabs .nav-link:hover:not(.active) {
    background-color: rgba(81, 8, 126, 0.1);
}

.tab-pane {
    padding: 20px;
    background-color: #fff;
    border: 1px solid #dee2e6;
    border-top: 0;
    border-radius: 0 0 10px 10px;
}

.table thead th {
    background-color: #f8f9fc;
    color: #51087E;
    font-weight: 600;
    border-bottom: 2px solid #51087E;
}

.total-row {
    background-color: #e9ecef;
    font-weight: bold;
    color: #51087E;
}

.total-row td {
    border-top: 2px solid #51087E;
}

.money-in-summary {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.money-out-summary {
    background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
    color: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.net-position-summary {
    background: linear-gradient(135deg, #51087E 0%, #6a1b99 100%);
    color: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.summary-amount {
    font-size: 1.3rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.summary-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.search-section {
    background-color: #f8f9fc;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.withdrawal-fees-section {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.fees-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 10px;
}

.fee-item {
    background: rgba(255, 255, 255, 0.1);
    padding: 10px;
    border-radius: 6px;
}

.fee-label {
    font-size: 0.85rem;
    opacity: 0.9;
}

.fee-amount {
    font-size: 1.1rem;
    font-weight: bold;
}
</style>

<!-- Transaction Filter Section -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold" style="color: #51087E;">Transaction Analysis</h6>
    </div>
    <div class="card-body">
        <!-- Date Filter Form -->
        <form id="transactionFilterForm" method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <label class="font-weight-bold">Start Date</label>
                    <input type="date" name="start_date" id="transactionStartDate" class="form-control" 
                           value="<?= isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label class="font-weight-bold">End Date</label>
                    <input type="date" name="end_date" id="transactionEndDate" class="form-control"
                           value="<?= isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block" style="background-color: #51087E; border-color: #51087E;">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </div>
            </div>
        </form>

        <!-- Overall Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="money-in-summary">
                    <div class="summary-amount" id="totalMoneyIn">KSh <?= number_format($total_inflows, 2) ?></div>
                    <div class="summary-label">Total Money In</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="money-out-summary">
                    <div class="summary-amount" id="totalMoneyOut">KSh <?= number_format($total_outflows, 2) ?></div>
                    <div class="summary-label">Total Money Out</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="net-position-summary">
                    <div class="summary-amount" id="netPositionAmount">KSh <?= number_format($net_position, 2) ?></div>
                    <div class="summary-label">Net Position</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="net-position-summary">
                    <div class="summary-amount"><?= count(array_merge($group_savings_data, $group_withdrawals_data, $business_savings_data, $business_withdrawals_data, $payments_data, $repayments_data, $expenses_data, $savings_data)) ?></div>
                    <div class="summary-label">Total Transactions</div>
                </div>
            </div>
        </div>

        <!-- Money Flow Tabs -->
        <ul class="nav nav-tabs money-flow-tabs" id="moneyFlowTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="money-in-tab" data-toggle="tab" href="#moneyIn" role="tab">
                    <i class="fas fa-arrow-down text-success"></i> Money In
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="money-out-tab" data-toggle="tab" href="#moneyOut" role="tab">
                    <i class="fas fa-arrow-up text-danger"></i> Money Out
                </a>
            </li>
        </ul>

        <div class="tab-content" id="moneyFlowTabContent">
            <!-- Money In Tab -->
            <div class="tab-pane fade show active" id="moneyIn" role="tabpanel">
                <!-- Search Section -->
                <div class="search-section">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" id="moneyInSearch" class="form-control" placeholder="Search by name, receipt no, group name...">
                        </div>
                        <div class="col-md-3">
                            <select id="moneyInType" class="form-control">
                                <option value="">All Types</option>
                                <option value="group_savings">Group Savings</option>
                                <option value="business_savings">Business Savings</option>
                                <option value="individual_savings">Individual Savings</option>
                                <option value="loan_repayments">Loan Repayments</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-primary btn-block" onclick="filterMoneyIn()">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Withdrawal Fees Section -->
                <?php
                $total_group_withdrawal_fees = 0;
                $total_business_withdrawal_fees = 0; 
                $total_individual_withdrawal_fees = 0;
                $total_disbursement_fees = 0;

                // Calculate withdrawal fees
                foreach ($business_withdrawals_data as $row) {
                    if ($row['type'] == 'Withdrawal Fee') {
                        $total_business_withdrawal_fees += $row['amount'];
                    }
                }

                foreach ($savings_data as $row) {
                    if ($row['type'] == 'Withdrawal') {
                        $total_individual_withdrawal_fees += $row['withdrawal_fee'];
                    }
                }

                foreach ($payments_data as $row) {
                    $total_disbursement_fees += $row['withdrawal_fee'];
                }

                $total_withdrawal_fees = $total_group_withdrawal_fees + $total_business_withdrawal_fees + $total_individual_withdrawal_fees + $total_disbursement_fees;
                ?>

                <div class="withdrawal-fees-section">
                    <h6><i class="fas fa-calculator"></i> Withdrawal Fees Breakdown</h6>
                    <div class="fees-grid">
                        <div class="fee-item">
                            <div class="fee-label">Group Withdrawal Fees</div>
                            <div class="fee-amount">KSh <?= number_format($total_group_withdrawal_fees, 2) ?></div>
                        </div>
                        <div class="fee-item">
                            <div class="fee-label">Business Withdrawal Fees</div>
                            <div class="fee-amount">KSh <?= number_format($total_business_withdrawal_fees, 2) ?></div>
                        </div>
                        <div class="fee-item">
                            <div class="fee-label">Individual Withdrawal Fees</div>
                            <div class="fee-amount">KSh <?= number_format($total_individual_withdrawal_fees, 2) ?></div>
                        </div>
                        <div class="fee-item">
                            <div class="fee-label">Loan Disbursement Fees</div>
                            <div class="fee-amount">KSh <?= number_format($total_disbursement_fees, 2) ?></div>
                        </div>
                        <div class="fee-item" style="background: rgba(255, 255, 255, 0.2);">
                            <div class="fee-label">Total Withdrawal Fees</div>
                            <div class="fee-amount">KSh <?= number_format($total_withdrawal_fees, 2) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Money In Summary -->
                <div class="transaction-summary">
                    <div class="row">
                        <div class="col-md-3">
                            <h6>Group Savings: <span class="text-success">KSh <?= number_format($total_group_savings, 2) ?></span></h6>
                        </div>
                        <div class="col-md-3">
                            <h6>Business Savings: <span class="text-success">KSh <?= number_format($total_business_savings, 2) ?></span></h6>
                        </div>
                        <div class="col-md-3">
                            <h6>Individual Savings: <span class="text-success">KSh <?= number_format($total_individual_savings, 2) ?></span></h6>
                        </div>
                        <div class="col-md-3">
                            <h6>Loan Repayments: <span class="text-success">KSh <?= number_format($total_repayments, 2) ?></span></h6>
                        </div>
                    </div>
                </div>

                <!-- Money In Table -->
                <div class="table-responsive">
                    <table class="table table-bordered" id="moneyInTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Client/Group</th>
                                <th>Amount (KSh)</th>
                                <th>Payment Mode</th>
                                <th>Receipt No</th>
                                <th>Served By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Group Savings -->
                            <?php foreach ($group_savings_data as $row): ?>
                            <tr class="group_savings">
                                <td><?= date('M d, Y', strtotime($row['date_saved'])) ?></td>
                                <td><span class="badge badge-success">Group Savings</span></td>
                                <td><?= htmlspecialchars($row['group_name'] ?? 'Unknown Group') ?></td>
                                <td><?= number_format($row['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($row['payment_mode']) ?></td>
                                <td><?= htmlspecialchars($row['receipt_no']) ?></td>
                                <td><?= htmlspecialchars($row['served_by_name']) ?></td>
                            </tr>
                            <?php endforeach; ?>

                            <!-- Business Savings -->
                            <?php foreach ($business_savings_data as $row): ?>
                            <tr class="business_savings">
                                <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                <td><span class="badge badge-success">Business Savings</span></td>
                                <td><?= htmlspecialchars($row['group_name'] ?? 'Unknown Business Group') ?></td>
                                <td><?= number_format($row['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($row['payment_mode']) ?></td>
                                <td><?= htmlspecialchars($row['receipt_no']) ?></td>
                                <td><?= htmlspecialchars($row['served_by_name']) ?></td>
                            </tr>
                            <?php endforeach; ?>

                            <!-- Individual Savings -->
                            <?php foreach ($savings_data as $row): ?>
                                <?php if ($row['type'] == 'Savings'): ?>
                                <tr class="individual_savings">
                                    <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                    <td><span class="badge badge-success">Individual Savings</span></td>
                                    <td><?= htmlspecialchars($row['account_name'] ?? 'Unknown Client') ?></td>
                                    <td><?= number_format($row['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['payment_mode']) ?></td>
                                    <td><?= htmlspecialchars($row['receipt_number']) ?></td>
                                    <td><?= htmlspecialchars($row['served_by_name']) ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <!-- Loan Repayments -->
                            <?php foreach ($repayments_data as $row): ?>
                            <tr class="loan_repayments">
                                <td><?= date('M d, Y', strtotime($row['date_paid'])) ?></td>
                                <td><span class="badge badge-success">Loan Repayment</span></td>
                                <td><?= htmlspecialchars($row['ref_no'] ?? 'Unknown Loan') ?></td>
                                <td><?= number_format($row['amount_repaid'], 2) ?></td>
                                <td><?= htmlspecialchars($row['payment_mode']) ?></td>
                                <td><?= htmlspecialchars($row['receipt_number']) ?></td>
                                <td><?= htmlspecialchars($row['served_by_name']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="3"><strong>TOTAL MONEY IN</strong></td>
                                <td><strong><?= number_format($total_inflows, 2) ?></strong></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Money Out Tab -->
            <div class="tab-pane fade" id="moneyOut" role="tabpanel">
                <!-- Search Section -->
                <div class="search-section">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" id="moneyOutSearch" class="form-control" placeholder="Search by name, receipt no, group name...">
                        </div>
                        <div class="col-md-3">
                            <select id="moneyOutType" class="form-control">
                                <option value="">All Types</option>
                                <option value="group_withdrawals">Group Withdrawals</option>
                                <option value="business_withdrawals">Business Withdrawals</option>
                                <option value="individual_withdrawals">Individual Withdrawals</option>
                                <option value="loan_disbursements">Loan Disbursements</option>
                                <option value="expenses">Expenses</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-primary btn-block" onclick="filterMoneyOut()">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Withdrawal Fees Section -->
                <?php
                $total_group_withdrawal_fees = 0;
                $total_business_withdrawal_fees = 0; 
                $total_individual_withdrawal_fees = 0;
                $total_disbursement_fees = 0;

                // Calculate withdrawal fees
                foreach ($business_withdrawals_data as $row) {
                    if ($row['type'] == 'Withdrawal Fee') {
                        $total_business_withdrawal_fees += $row['amount'];
                    }
                }

                foreach ($savings_data as $row) {
                    if ($row['type'] == 'Withdrawal') {
                        $total_individual_withdrawal_fees += $row['withdrawal_fee'];
                    }
                }

                foreach ($payments_data as $row) {
                    $total_disbursement_fees += $row['withdrawal_fee'];
                }

                $total_withdrawal_fees = $total_group_withdrawal_fees + $total_business_withdrawal_fees + $total_individual_withdrawal_fees + $total_disbursement_fees;
                ?>

                <div class="withdrawal-fees-section">
                    <h6><i class="fas fa-calculator"></i> Withdrawal Fees Breakdown</h6>
                    <div class="fees-grid">
                        <div class="fee-item">
                            <div class="fee-label">Group Withdrawal Fees</div>
                            <div class="fee-amount">KSh <?= number_format($total_group_withdrawal_fees, 2) ?></div>
                        </div>
                        <div class="fee-item">
                            <div class="fee-label">Business Withdrawal Fees</div>
                            <div class="fee-amount">KSh <?= number_format($total_business_withdrawal_fees, 2) ?></div>
                        </div>
                        <div class="fee-item">
                            <div class="fee-label">Individual Withdrawal Fees</div>
                            <div class="fee-amount">KSh <?= number_format($total_individual_withdrawal_fees, 2) ?></div>
                        </div>
                        <div class="fee-item">
                            <div class="fee-label">Loan Disbursement Fees</div>
                            <div class="fee-amount">KSh <?= number_format($total_disbursement_fees, 2) ?></div>
                        </div>
                        <div class="fee-item" style="background: rgba(255, 255, 255, 0.2);">
                            <div class="fee-label">Total Withdrawal Fees</div>
                            <div class="fee-amount">KSh <?= number_format($total_withdrawal_fees, 2) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Money Out Summary -->
                <div class="transaction-summary">
                    <div class="row">
                        <div class="col-md-2">
                            <h6>Group Withdrawals: <span class="text-danger">KSh <?= number_format($total_group_withdrawals, 2) ?></span></h6>
                        </div>
                        <div class="col-md-2">
                            <h6>Business Withdrawals: <span class="text-danger">KSh <?= number_format($total_business_withdrawals, 2) ?></span></h6>
                        </div>
                        <div class="col-md-2">
                            <h6>Individual Withdrawals: <span class="text-danger">KSh <?= number_format($total_individual_withdrawals, 2) ?></span></h6>
                        </div>
                        <div class="col-md-2">
                            <h6>Loan Disbursements: <span class="text-danger">KSh <?= number_format($total_payments, 2) ?></span></h6>
                        </div>
                        <div class="col-md-2">
                            <h6>Expenses: <span class="text-danger">KSh <?= number_format($total_expenses, 2) ?></span></h6>
                        </div>
                        <div class="col-md-2">
                            <h6>Total Fees: <span class="text-info">KSh <?= number_format($total_withdrawal_fees, 2) ?></span></h6>
                        </div>
                    </div>
                </div>

                <!-- Money Out Table -->
                <div class="table-responsive">
                    <table class="table table-bordered" id="moneyOutTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Client/Group/Category</th>
                                <th>Amount (KSh)</th>
                                <th>Withdrawal Fee (KSh)</th>
                                <th>Payment Mode</th>
                                <th>Receipt/Ref No</th>
                                <th>Served By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Group Withdrawals -->
                            <?php foreach ($group_withdrawals_data as $row): ?>
                            <tr class="group_withdrawals">
                                <td><?= date('M d, Y', strtotime($row['date_withdrawn'])) ?></td>
                                <td><span class="badge badge-danger">Group Withdrawal</span></td>
                                <td><?= htmlspecialchars($row['group_name'] ?? 'Unknown Group') ?></td>
                                <td><?= number_format($row['amount'], 2) ?></td>
                                <td><?= number_format($row['withdrawal_fee'] ?? 0, 2) ?></td>
                                <td><?= htmlspecialchars($row['payment_mode']) ?></td>
                                <td><?= htmlspecialchars($row['receipt_no']) ?></td>
                                <td><?= htmlspecialchars($row['served_by_name']) ?></td>
                            </tr>
                            <?php endforeach; ?>

                            <!-- Business Withdrawals -->
                            <?php foreach ($business_withdrawals_data as $row): ?>
                                <?php if ($row['type'] == 'Withdrawal'): ?>
                                <tr class="business_withdrawals">
                                    <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                    <td><span class="badge badge-danger">Business Withdrawal</span></td>
                                    <td><?= htmlspecialchars($row['group_name'] ?? 'Unknown Business Group') ?></td>
                                    <td><?= number_format($row['amount'], 2) ?></td>
                                    <td><?= number_format($row['withdrawal_fee'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['payment_mode']) ?></td>
                                    <td><?= htmlspecialchars($row['receipt_no']) ?></td>
                                    <td><?= htmlspecialchars($row['served_by_name']) ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <!-- Individual Withdrawals -->
                            <?php foreach ($savings_data as $row): ?>
                                <?php if ($row['type'] == 'Withdrawal'): ?>
                                <tr class="individual_withdrawals">
                                    <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                    <td><span class="badge badge-danger">Individual Withdrawal</span></td>
                                    <td><?= htmlspecialchars($row['account_name'] ?? 'Unknown Client') ?></td>
                                    <td><?= number_format($row['amount'], 2) ?></td>
                                    <td><?= number_format($row['withdrawal_fee'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['payment_mode']) ?></td>
                                    <td><?= htmlspecialchars($row['receipt_number']) ?></td>
                                    <td><?= htmlspecialchars($row['served_by_name']) ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <!-- Loan Disbursements -->
                            <?php foreach ($payments_data as $row): ?>
                            <tr class="loan_disbursements">
                                <td><?= date('M d, Y', strtotime($row['date_created'])) ?></td>
                                <td><span class="badge badge-danger">Loan Disbursement</span></td>
                                <td><?= htmlspecialchars($row['payee'] ?? 'Unknown Payee') ?></td>
                                <td><?= number_format($row['pay_amount'], 2) ?></td>
                                <td><?= number_format($row['withdrawal_fee'], 2) ?></td>
                                <td>Bank Transfer</td>
                                <td><?= htmlspecialchars($row['receipt_no']) ?></td>
                                <td><?= htmlspecialchars($row['disbursed_by']) ?></td>
                            </tr>
                            <?php endforeach; ?>

                            <!-- Expenses -->
                            <?php foreach ($expenses_data as $row): ?>
                            <tr class="expenses">
                                <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                <td><span class="badge badge-danger">Expense</span></td>
                                <td><?= htmlspecialchars($row['category']) ?> - <?= htmlspecialchars($row['description']) ?></td>
                                <td><?= number_format($row['amount'], 2) ?></td>
                                <td>0.00</td>
                                <td><?= htmlspecialchars($row['payment_method']) ?></td>
                                <td><?= htmlspecialchars($row['reference_no']) ?></td>
                                <td><?= htmlspecialchars($row['created_by_name']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="3"><strong>TOTAL MONEY OUT</strong></td>
                                <td><strong><?= number_format($total_outflows, 2) ?></strong></td>
                                <td><strong><?= number_format($total_withdrawal_fees, 2) ?></strong></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTables for Money In
    var moneyInTable = $('#moneyInTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10,
        "responsive": true,
        "footerCallback": function(row, data, start, end, display) {
            var api = this.api();
            var totalAmount = 0;
            api.column(3, { page: 'current', search: 'applied' }).data().each(function(value) {
                var numValue = parseFloat(value.toString().replace(/,/g, ''));
                if (!isNaN(numValue)) {
                    totalAmount += numValue;
                }
            });
            $(api.column(3).footer()).html('<strong>' + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2}) + '</strong>');
        },
        "language": {
            "search": "Search money in transactions: ",
            "lengthMenu": "Show _MENU_ entries per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ money in transactions",
            "infoEmpty": "No money in transactions found",
            "infoFiltered": "(filtered from _MAX_ total transactions)"
        }
    });

    // Initialize DataTables for Money Out
    var moneyOutTable = $('#moneyOutTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10,
        "responsive": true,
        "footerCallback": function(row, data, start, end, display) {
            var api = this.api();
            var totalAmount = 0;
            var totalFees = 0;
            
            api.column(3, { page: 'current', search: 'applied' }).data().each(function(value) {
                var numValue = parseFloat(value.toString().replace(/,/g, ''));
                if (!isNaN(numValue)) {
                    totalAmount += numValue;
                }
            });
            
            api.column(4, { page: 'current', search: 'applied' }).data().each(function(value) {
                var numValue = parseFloat(value.toString().replace(/,/g, ''));
                if (!isNaN(numValue)) {
                    totalFees += numValue;
                }
            });
            
            $(api.column(3).footer()).html('<strong>' + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2}) + '</strong>');
            $(api.column(4).footer()).html('<strong>' + totalFees.toLocaleString('en-US', {minimumFractionDigits: 2}) + '</strong>');
        },
        "language": {
            "search": "Search money out transactions: ",
            "lengthMenu": "Show _MENU_ entries per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ money out transactions",
            "infoEmpty": "No money out transactions found",
            "infoFiltered": "(filtered from _MAX_ total transactions)"
        }
    });

    // Store table references globally
    window.moneyInTable = moneyInTable;
    window.moneyOutTable = moneyOutTable;

    // Handle transaction filter form submission
    $('#transactionFilterForm').on('submit', function(e) {
        e.preventDefault();
        const startDate = $('#transactionStartDate').val();
        const endDate = $('#transactionEndDate').val();

        if (!startDate || !endDate) {
            alert('Please select both start and end dates');
            return false;
        }

        if (startDate > endDate) {
            alert('Start date cannot be later than end date');
            return false;
        }

        showLoadingSpinner();
        this.submit();
    });
});

function filterMoneyIn() {
    const searchTerm = $('#moneyInSearch').val();
    const transactionType = $('#moneyInType').val();
    
    var table = window.moneyInTable;
    
    // Clear existing filters
    $.fn.dataTable.ext.search = [];
    
    // Apply custom filtering
    if (transactionType) {
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'moneyInTable') {
                    return true;
                }
                
                var row = table.row(dataIndex).node();
                return $(row).hasClass(transactionType);
            }
        );
    }
    
    // Apply search term
    if (searchTerm) {
        table.search(searchTerm);
    }
    
    table.draw();
    
    // Clear custom filters
    if (transactionType) {
        $.fn.dataTable.ext.search.pop();
    }
}

function filterMoneyOut() {
    const searchTerm = $('#moneyOutSearch').val();
    const transactionType = $('#moneyOutType').val();
    
    var table = window.moneyOutTable;
    
    // Clear existing filters
    $.fn.dataTable.ext.search = [];
    
    // Apply custom filtering
    if (transactionType) {
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'moneyOutTable') {
                    return true;
                }
                
                var row = table.row(dataIndex).node();
                return $(row).hasClass(transactionType);
            }
        );
    }
    
    // Apply search term
    if (searchTerm) {
        table.search(searchTerm);
    }
    
    table.draw();
    
    // Clear custom filters
    if (transactionType) {
        $.fn.dataTable.ext.search.pop();
    }
}

function showLoadingSpinner() {
    $('body').append(`
        <div class="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.9); display: flex; justify-content: center; align-items: center; z-index: 9999;">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
    `);
}

function generateReport() {
    const startDate = $('#transactionStartDate').val();
    const endDate = $('#transactionEndDate').val();
    window.location.href = `../controllers/generate_reconciliation_report.php?start_date=${startDate}&end_date=${endDate}`;
}
</script>