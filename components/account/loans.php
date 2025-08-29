<style>
/* Loans Section Styles - Updated to match pending approval layout */
.loans-section {
    margin-bottom: 25px;
}

.loans-section .section-header {
    margin-bottom: 20px;
}

.loans-section .section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #51087E;
    margin: 0;
}

.loans-section .table-container {
    background: #fff;
    border-radius: 0.35rem;
    overflow: hidden;
    box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
    border: 0;
}

.loans-section .card-body {
    padding: 1.25rem;
}

.loans-section .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.loans-section .table {
    width: 100%;
    margin-bottom: 0;
    color: #858796;
    border-collapse: collapse;
}

.loans-section .table thead th {
    background-color: #51087E;
    color: #fff;
    border-bottom: 1px solid #e3e6f0;
    border-top: none;
    font-weight: 600;
    padding: 0.75rem;
    vertical-align: middle;
    border-right: none;
    font-size: 0.875rem;
}

.loans-section .table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #e3e6f0;
    border-bottom: none;
    border-right: none;
    font-size: 0.875rem;
}

.loans-section .table tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.025);
}

.loans-section .table-bordered {
    border: 1px solid #e3e6f0;
}

.loans-section .table-bordered th,
.loans-section .table-bordered td {
    border: 1px solid #e3e6f0;
}

/* Badge Styles - Keep existing colors */
.badge-modern {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    display: inline-block;
}

.badge-success { 
    background-color: #1cc88a; 
    color: #fff; 
}

.badge-warning { 
    background-color: #f6c23e; 
    color: #fff; 
}

.badge-danger { 
    background-color: #e74a3b; 
    color: #fff; 
}

.badge-info { 
    background-color: #36b9cc; 
    color: #fff; 
}

.badge-secondary { 
    background-color: #6c757d; 
    color: #fff; 
}

.badge-primary { 
    background-color: #4e73df; 
    color: #fff; 
}

/* Button Styles */
.btn-primary-modern {
    background-color: #51087E;
    border-color: #51087E;
    color: #fff;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 400;
    line-height: 1.5;
    border-radius: 0.35rem;
    transition: all 0.15s ease-in-out;
    border: 1px solid transparent;
    display: inline-block;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    text-decoration: none;
}

.btn-primary-modern:hover {
    background-color: #3e0664;
    border-color: #3e0664;
    color: #fff;
    text-decoration: none;
}

.btn-primary-modern:focus {
    box-shadow: 0 0 0 0.2rem rgba(81, 8, 126, 0.25);
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.2rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 1.25rem;
    background: #fff;
    border-radius: 0.35rem;
    box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
    border: 0;
}

.empty-icon {
    font-size: 3.5rem;
    color: #d1d3e2;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.empty-text {
    color: #858796;
    font-size: 1rem;
    margin: 0;
}

/* DataTables Integration */
.loans-section .dataTables_wrapper {
    padding: 0;
}

.loans-section .dataTables_wrapper .dataTables_length,
.loans-section .dataTables_wrapper .dataTables_filter,
.loans-section .dataTables_wrapper .dataTables_info,
.loans-section .dataTables_wrapper .dataTables_paginate {
    margin-bottom: 0.5rem;
}

.loans-section .dataTables_wrapper .dataTables_length select,
.loans-section .dataTables_wrapper .dataTables_filter input {
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.loans-section .dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.375rem 0.75rem;
    margin-left: 0.125rem;
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    background: #fff;
    color: #6c757d;
}

.loans-section .dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #51087E !important;
    border-color: #51087E !important;
    color: white !important;
}

.loans-section .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #eaecf4;
    border-color: #d1d3e2;
    color: #6c757d;
}

/* Modal Styles */
.modal-content {
    border: 0;
    border-radius: 0.35rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.modal-header {
    border-bottom: 1px solid #e3e6f0;
    border-top-left-radius: 0.35rem;
    border-top-right-radius: 0.35rem;
    padding: 1rem;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid #e3e6f0;
    border-bottom-left-radius: 0.35rem;
    border-bottom-right-radius: 0.35rem;
    padding: 1rem;
}

/* Remove any custom variables that might cause issues */
.loans-section .table-modern {
    width: 100%;
    margin: 0;
    border-collapse: collapse;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .loans-section .table {
        font-size: 0.8rem;
    }
    
    .loans-section .table thead th,
    .loans-section .table tbody td {
        padding: 0.5rem 0.375rem;
    }
    
    .btn-sm {
        padding: 0.125rem 0.25rem;
        font-size: 0.75rem;
    }
}

@media (max-width: 480px) {
    .loans-section .section-title {
        font-size: 1.25rem;
    }
    
    .empty-state {
        padding: 2rem 1rem;
    }
    
    .empty-icon {
        font-size: 2.5rem;
    }
}
</style>

<!-- Updated Loans Section HTML Structure -->
<div class="content-section loans-section" id="loans-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-money-bill-wave"></i>
            Loans
        </h2>
    </div>
    
    <?php if (!empty($loans)): ?>
        <div class="card mb-4 table-container">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="loansTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Ref No</th>
                                <th>Loan Product</th>
                                <th>Amount</th>
                                <th>Interest Rate</th>
                                <th>Monthly Payment</th>
                                <th>Total Payable</th>
                                <th>Status</th>
                                <th>Date Applied</th>
                                <th>Next Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($loans as $loan): ?>
                                <tr>
                                    <td><?= htmlspecialchars($loan['ref_no']) ?></td>
                                    <td><?= htmlspecialchars($loan['loan_product_id']) ?></td>
                                    <td>KSh <?= number_format($loan['amount'], 2) ?></td>
                                    <td><?= number_format($loan['interest_rate'], 2) ?>%</td>
                                    <td>KSh <?= number_format($loan['monthly_payment'], 2) ?></td>
                                    <td>KSh <?= number_format($loan['total_payable'], 2) ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch($loan['status']) {
                                            case 0: $status_class = 'badge-warning'; $status_text = 'Pending Approval'; break;
                                            case 1: $status_class = 'badge-info'; $status_text = 'Approved'; break;
                                            case 2: $status_class = 'badge-primary'; $status_text = 'Disbursed'; break;
                                            case 3: $status_class = 'badge-success'; $status_text = 'Completed'; break;
                                            case 4: $status_class = 'badge-danger'; $status_text = 'Denied'; break;
                                            default: $status_class = 'badge-secondary'; $status_text = 'Unknown'; break;
                                        }
                                        echo "<span class='badge badge-pill $status_class'>$status_text</span>";
                                        ?>
                                    </td>
                                    <td><?= date("Y-m-d", strtotime($loan['date_applied'])) ?></td>
                                    <td><?= $loan['next_payment_date'] ? date("Y-m-d", strtotime($loan['next_payment_date'])) : 'N/A' ?></td>
                                    <td>
                                        <button class="btn btn-primary-modern btn-sm view-schedule" data-loan-id="<?= $loan['loan_id'] ?>">
                                            <i class="fas fa-calendar-alt"></i> Schedule
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-money-bill-wave empty-icon"></i>
            <p class="empty-text">No loans found for this account.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Loan Schedule Modal -->
<div class="modal fade" id="loanScheduleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #51087E; color: #fff;">
                <h5 class="modal-title text-white">
                    <i class="fas fa-calendar-alt mr-2"></i>Loan Schedule
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="scheduleTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Due Date</th>
                                <th>Principal</th>
                                <th>Interest</th>
                                <th>Due Amount</th>
                                <th>Balance</th>
                                <th>Repaid Amount</th>
                                <th>Default Amount</th>
                                <th>Status</th>
                                <th>Paid Date</th>
                            </tr>
                        </thead>
                        <tbody id="scheduleTableBody">
                            <!-- Schedule data will be inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable for loans when section becomes active
    $(document).on('sectionChanged', function(event, section) {
        if (section === 'loans') {
            setTimeout(() => {
                if (!$.fn.DataTable.isDataTable('#loansTable')) {
                    $('#loansTable').DataTable({
                        responsive: true,
                        pageLength: 10,
                        order: [[0, 'desc']],
                        scrollX: true,
                        autoWidth: false,
                        language: {
                            search: "Search:",
                            lengthMenu: "Show _MENU_ entries",
                            info: "Showing _START_ to _END_ of _TOTAL_ entries",
                            paginate: {
                                first: "First",
                                last: "Last",
                                next: "Next",
                                previous: "Previous"
                            }
                        },
                        columnDefs: [
                            { targets: '_all', className: 'text-nowrap' }
                        ]
                    });
                }
            }, 100);
        }
    });

    // View Loan Schedule
    $(document).on('click', '.view-schedule', function() {
        const loanId = $(this).data('loan-id');
        
        $.ajax({
            url: '../controllers/get_loan_schedule.php',
            type: 'GET',
            data: { loan_id: loanId },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success' && response.schedule) {
                    const tableBody = $('#scheduleTableBody');
                    tableBody.empty();
                    
                    $.each(response.schedule, function(index, item) {
                        const statusBadge = getStatusBadgeClass(item.status);
                        const row = `
                            <tr>
                                <td>${item.due_date}</td>
                                <td>KSh ${item.principal}</td>
                                <td>KSh ${item.interest}</td>
                                <td>KSh ${item.amount}</td>
                                <td>KSh ${item.balance}</td>
                                <td>KSh ${item.repaid_amount}</td>
                                <td>KSh ${item.default_amount}</td>
                                <td><span class="badge-modern ${statusBadge}">${item.status}</span></td>
                                <td>${item.paid_date || '-'}</td>
                            </tr>
                        `;
                        tableBody.append(row);
                    });
                    
                    $('#loanScheduleModal').modal('show');
                } else {
                    showToast('Error: ' + (response.message || 'Invalid schedule data'), 'error');
                }
            },
            error: function(xhr, status, error) {
                showToast('Error fetching loan schedule', 'error');
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    });

    function getStatusBadgeClass(status) {
        switch(status.toLowerCase()) {
            case 'paid': return 'badge-success';
            case 'partial': return 'badge-warning';
            case 'unpaid': return 'badge-danger';
            default: return 'badge-secondary';
        }
    }
});
</script>