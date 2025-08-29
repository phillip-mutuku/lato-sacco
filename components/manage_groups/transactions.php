<?php
// components/manage_groups/transactions.php
?>

<!-- Transactions History Section -->
<div id="transactions-section" class="content-section">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5 class="m-0">Transaction History</h5>
            </div>
            <div class="card-body">
                <!-- Statement Generator -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="statementForm" class="row align-items-end">
                            <div class="col-md-4">
                                <label>From Date</label>
                                <input type="date" name="from_date" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label>To Date</label>
                                <input type="date" name="to_date" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-warning" id="printStatement">
                                    <i class="fas fa-print"></i> Print Statement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table" id="transactionTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Member</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Receipt no</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?= date("Y-m-d", strtotime($transaction['date'])) ?></td>
                                    <td><?= htmlspecialchars($transaction['member_name']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= strpos($transaction['type'], 'Savings') !== false ? 'success' : 'warning' ?>">
                                            <?= htmlspecialchars($transaction['type']) ?>
                                        </span>
                                    </td>
                                    <td>KSh <?= number_format($transaction['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($transaction['receipt_no'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($transaction['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTables for Transaction History
    $('#transactionTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'desc']],
        language: {
            search: "Search transactions:",
            lengthMenu: "Show _MENU_ transactions per page",
            info: "Showing _START_ to _END_ of _TOTAL_ transactions"
        }
    });

    // Print statement
    $('#printStatement').click(function() {
        const fromDate = $('input[name="from_date"]').val();
        const toDate = $('input[name="to_date"]').val();

        if (!fromDate || !toDate) {
            showMessage('Please select both start and end dates', 'error');
            return;
        }

        $.ajax({
            url: '../controllers/groupController.php',
            method: 'POST',
            data: {
                action: 'getStatementData',
                group_id: <?php echo $groupId; ?>,
                from_date: fromDate,
                to_date: toDate
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    const statementWindow = window.open('', '_blank');
                    const statementContent = generateStatementHTML(response.data, fromDate, toDate);
                    statementWindow.document.write(statementContent);
                    statementWindow.document.close();
                    setTimeout(() => {
                        statementWindow.print();
                    }, 500);
                } else {
                    showMessage('Error: ' + (response.message || 'Failed to generate statement'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showMessage('Error generating statement', 'error');
            }
        });
    });

    // Generate Statement HTML
    function generateStatementHTML(data, fromDate, toDate) {
        if (!data || !data.transactions) {
            console.error('Invalid data structure:', data);
            return '<p>Error: Invalid data received</p>';
        }

        let transactionsHTML = '';
        let totalSavings = 0;
        let totalWithdrawals = 0;

        data.transactions.forEach(transaction => {
            const amount = parseFloat(transaction.amount);
            if (transaction.type === 'Savings') {
                totalSavings += amount;
            } else if (transaction.type === 'Withdrawal') {
                totalWithdrawals += amount;
            }

            transactionsHTML += `
                <tr>
                    <td>${new Date(transaction.date).toLocaleDateString()}</td>
                    <td>${transaction.member_name}</td>
                    <td>${transaction.type}</td>
                    <td>KSh ${amount.toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}</td>
                    <td>${transaction.receipt_no || 'N/A'}</td>
                    <td>${transaction.payment_mode || ''}</td>
                </tr>
            `;
        });

        return `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Group Statement</title>
                <style>
                    body { 
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        padding: 20px;
                    }
                    .statement {
                        max-width: 1000px;
                        margin: 0 auto;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                        border-bottom: 2px solid #333;
                        padding-bottom: 10px;
                    }
                    .group-info {
                        margin-bottom: 20px;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 20px 0;
                    }
                    th, td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                    }
                    th {
                        background-color: #51087E;
                        color: white;
                    }
                    tr:nth-child(even) {
                        background-color: #f8f9fa;
                    }
                    .summary {
                        margin-top: 20px;
                        border-top: 2px solid #333;
                        padding-top: 10px;
                    }
                    @media print {
                        body { print-color-adjust: exact; }
                        th { background-color: #51087E !important; color: white !important; }
                    }
                </style>
            </head>
            <body>
                <div class="statement">
                    <div class="header">
                        <h2>Lato Sacco LTD</h2>
                        <h3>Group Transaction Statement</h3>
                    </div>
                    <div class="group-info">
                        <p><strong>Group Name:</strong> ${data.group_details.group_name}</p>
                        <p><strong>Period:</strong> ${new Date(fromDate).toLocaleDateString()} to ${new Date(toDate).toLocaleDateString()}</p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Member</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Receipt No</th>
                                <th>Payment Mode</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${transactionsHTML}
                        </tbody>
                    </table>
                    <div class="summary">
                        <h4>Summary</h4>
                        <p><strong>Total Savings:</strong> KSh ${totalSavings.toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}</p>
                        <p><strong>Total Withdrawals:</strong> KSh ${totalWithdrawals.toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}</p>
                        <p><strong>Net Movement:</strong> KSh ${(totalSavings - totalWithdrawals).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}</p>
                    </div>
                    <div class="footer" style="text-align: center; margin-top: 30px;">
                        <p>Generated on: ${new Date().toLocaleString()}</p>
                    </div>
                </div>
            </body>
            </html>
        `;
    }
});
</script>