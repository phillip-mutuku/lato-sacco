<?php
// Transactions Table Component
// This component displays the recent transactions table with enhanced columns
?>

<!-- Manage expenses and income begins -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Expenses & Income</h1>
</div>

<div class="mb-2 d-flex justify-content-between">
    <button class="btn btn-lg btn-warning" href="#" data-toggle="modal" data-target="#addExpenseModal">
        <span class="fa fa-minus-circle"></span> Add New Expense
    </button>
    <button class="btn btn-lg btn-success" href="#" data-toggle="modal" data-target="#addReceivedModal">
        <span class="fa fa-plus-circle"></span> Add Money Received
    </button>
</div>

<div class="card mb-4">
    <div class="card-header py-3">
        <h6 style="color: #51087E;" class="m-0 font-weight-bold">Recent Transactions</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="transactionTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Receipt No</th>
                        <th>Category</th>
                        <th>Expense Name</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Created By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($transactions && $transactions->num_rows > 0):
                        // Update the query to include both category and expense name
                        $enhanced_query = "
                            SELECT 
                                e.*,
                                ec.category as main_category,
                                e.category as expense_name,
                                u.username as created_by_name,
                                CASE 
                                    WHEN e.status = 'received' THEN ABS(e.amount)
                                    ELSE -ABS(e.amount)
                                END as signed_amount
                            FROM expenses e 
                            LEFT JOIN expenses_categories ec ON e.category = ec.name 
                            LEFT JOIN user u ON e.created_by = u.user_id
                            WHERE 1=1";
                        
                        // Apply the same filters as in the main query
                        if ($transaction_type !== 'all') {
                            if ($transaction_type === 'expenses') {
                                $enhanced_query .= " AND e.status = 'completed'";
                            } elseif ($transaction_type === 'received') {
                                $enhanced_query .= " AND e.status = 'received'";
                            }
                        }

                        if ($date_range !== 'all') {
                            $today = date('Y-m-d');
                            switch ($date_range) {
                                case 'today':
                                    $enhanced_query .= " AND DATE(e.date) = '$today'";
                                    break;
                                case 'week':
                                    $week_start = date('Y-m-d', strtotime('-1 week'));
                                    $enhanced_query .= " AND DATE(e.date) >= '$week_start'";
                                    break;
                                case 'month':
                                    $month_start = date('Y-m-d', strtotime('first day of this month'));
                                    $enhanced_query .= " AND DATE(e.date) >= '$month_start'";
                                    break;
                                case 'year':
                                    $year_start = date('Y-m-d', strtotime('first day of january this year'));
                                    $enhanced_query .= " AND DATE(e.date) >= '$year_start'";
                                    break;
                                case 'custom':
                                    if ($start_date && $end_date) {
                                        $enhanced_query .= " AND DATE(e.date) BETWEEN '$start_date' AND '$end_date'";
                                    }
                                    break;
                            }
                        }

                        $enhanced_query .= " ORDER BY e.date DESC, e.created_at DESC";
                        
                        $enhanced_transactions = $db->conn->query($enhanced_query);
                        
                        if ($enhanced_transactions && $enhanced_transactions->num_rows > 0):
                            while($transaction = $enhanced_transactions->fetch_assoc()): 
                    ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i', strtotime($transaction['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['receipt_no']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['main_category']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['expense_name']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td class="<?php echo $transaction['status'] === 'received' ? 'text-success' : 'text-danger'; ?>">
                                        KSh <?php echo number_format(abs($transaction['amount']), 2); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['payment_method']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            if ($transaction['status'] == 'received') {
                                                echo 'success';
                                            } elseif ($transaction['status'] == 'completed') {
                                                echo 'danger';
                                            } else {
                                                echo 'warning';
                                            }
                                        ?>">
                                            <?php 
                                            if ($transaction['status'] == 'received') {
                                                echo 'Received';
                                            } elseif ($transaction['status'] == 'completed') {
                                                echo 'Expense';
                                            } else {
                                                echo ucfirst($transaction['status']);
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['remarks']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['created_by_name']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning print-receipt mr-2" 
                                                data-transaction='<?php echo json_encode($transaction); ?>'>
                                            <i class="fas fa-print"></i> Print
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-transaction" 
                                                data-receipt-no="<?php echo htmlspecialchars($transaction['receipt_no']); ?>"
                                                data-id="<?php echo htmlspecialchars($transaction['id']); ?>">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                    <?php 
                            endwhile;
                        else:
                    ?>
                            <tr>
                                <td colspan="11" class="text-center">No transactions found</td>
                            </tr>
                    <?php 
                        endif;
                    else:
                    ?>
                        <tr>
                            <td colspan="11" class="text-center">No transactions found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>