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

/* Recalculate Button Styles */
.btn-warning {
    background-color: #f6c23e;
    border-color: #f6c23e;
    color: #fff;
}

.btn-warning:hover {
    background-color: #e0a800;
    border-color: #d39e00;
    color: #fff;
}

.btn-warning:focus {
    box-shadow: 0 0 0 0.2rem rgba(246, 194, 62, 0.5);
}

/* Custom Confirmation Dialog Styles */
.custom-confirm-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.custom-confirm-overlay.show {
    opacity: 1;
}

.custom-confirm-dialog {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 500px;
    overflow: hidden;
    transform: scale(0.9);
    transition: transform 0.3s ease;
}

.custom-confirm-overlay.show .custom-confirm-dialog {
    transform: scale(1);
}

.custom-confirm-header {
    background: linear-gradient(135deg, #51087E 0%, #6B1FA0 100%);
    color: #fff;
    padding: 24px 30px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.custom-confirm-header i {
    font-size: 32px;
    color: #ffd700;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.1); }
}

.custom-confirm-header h4 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 600;
    letter-spacing: 0.3px;
}

.custom-confirm-body {
    padding: 30px;
    color: #333;
}

.custom-confirm-body > p:first-child {
    font-size: 1rem;
    line-height: 1.6;
    color: #555;
    margin-bottom: 20px;
}

.custom-confirm-details {
    background: #f8f9fc;
    border-left: 4px solid #51087E;
    padding: 20px;
    margin: 20px 0;
    border-radius: 6px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    font-size: 0.95rem;
    color: #555;
}

.detail-item i {
    font-size: 18px;
    flex-shrink: 0;
}

.confirm-question {
    font-size: 1.1rem;
    font-weight: 600;
    color: #51087E;
    margin-top: 20px;
    margin-bottom: 0;
    text-align: center;
}

.custom-confirm-footer {
    padding: 20px 30px;
    background: #f8f9fc;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    border-top: 1px solid #e3e6f0;
}

.btn-confirm-cancel,
.btn-confirm-ok {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-confirm-cancel {
    background: #6c757d;
    color: #fff;
}

.btn-confirm-cancel:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
}

.btn-confirm-ok {
    background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
    color: #fff;
}

.btn-confirm-ok:hover {
    background: linear-gradient(135deg, #13855c 0%, #0e6b47 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(28, 200, 138, 0.4);
}

.btn-confirm-cancel:active,
.btn-confirm-ok:active {
    transform: translateY(0);
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

/* Alert Styles */
.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

/* Responsive Design */
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
    
    .modal-header .d-flex {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.5rem;
    }
    
    #recalculateScheduleBtn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}

/* Responsive Design for Confirmation Dialog */
@media (max-width: 576px) {
    .custom-confirm-dialog {
        width: 95%;
        margin: 10px;
    }
    
    .custom-confirm-header {
        padding: 20px;
    }
    
    .custom-confirm-header h4 {
        font-size: 1.2rem;
    }
    
    .custom-confirm-header i {
        font-size: 28px;
    }
    
    .custom-confirm-body {
        padding: 20px;
    }
    
    .custom-confirm-footer {
        flex-direction: column;
        padding: 15px 20px;
    }
    
    .btn-confirm-cancel,
    .btn-confirm-ok {
        width: 100%;
        justify-content: center;
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
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-warning btn-sm mr-3" id="recalculateScheduleBtn" title="Recalculate loan schedule based on actual repayments">
                        <i class="fas fa-sync-alt"></i> Recalculate Schedule
                    </button>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" id="scheduleInfoAlert" style="display: none;">
                    <i class="fas fa-info-circle"></i>
                    <span id="scheduleInfoMessage"></span>
                </div>
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
    let currentLoanId = null; // Store current loan ID for recalculation
    
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

    // View Loan Schedule - USE EVENT DELEGATION FOR DYNAMICALLY LOADED CONTENT
    $(document).on('click', '.view-schedule', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const loanId = $(this).data('loan-id');
        currentLoanId = loanId; // Store for recalculation
        loadLoanSchedule(loanId);
    });

    // Function to load loan schedule
    function loadLoanSchedule(loanId) {
        // Hide any previous info alerts
        $('#scheduleInfoAlert').hide();
        
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
    }

    // Recalculate Schedule Button - USE EVENT DELEGATION
    $(document).on('click', '#recalculateScheduleBtn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!currentLoanId) {
            showToast('No loan selected for recalculation', 'warning');
            return;
        }
        
        const $button = $(this);
        const originalHtml = $button.html();
        
        // Show custom styled confirmation dialog
        showRecalculateConfirmation($button, originalHtml);
    });

    // Function to show custom confirmation dialog
    function showRecalculateConfirmation($button, originalHtml) {
        const confirmHtml = `
            <div class="custom-confirm-overlay" id="recalculateConfirmOverlay">
                <div class="custom-confirm-dialog">
                    <div class="custom-confirm-header">
                        <i class="fas fa-exclamation-circle"></i>
                        <h4>Recalculate Loan Schedule</h4>
                    </div>
                    <div class="custom-confirm-body">
                        <p>This will recalculate the loan schedule based on actual repayments from the database.</p>
                        <div class="custom-confirm-details">
                            <div class="detail-item">
                                <i class="fas fa-check-circle text-success"></i>
                                <span>Updates status (paid/unpaid/partial)</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-check-circle text-success"></i>
                                <span>Recalculates repaid amounts</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-check-circle text-success"></i>
                                <span>Sets correct paid dates</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-check-circle text-success"></i>
                                <span>Updates default amounts</span>
                            </div>
                        </div>
                        <p class="confirm-question">Do you want to continue?</p>
                    </div>
                    <div class="custom-confirm-footer">
                        <button class="btn-confirm-cancel" id="confirmCancelBtn">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="btn-confirm-ok" id="confirmOkBtn">
                            <i class="fas fa-check"></i> Yes, Recalculate
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Add to body
        $('body').append(confirmHtml);
        
        // Animate in
        setTimeout(() => {
            $('#recalculateConfirmOverlay').addClass('show');
        }, 10);
        
        // Handle cancel - close on cancel button or overlay click
        $(document).on('click', '#confirmCancelBtn, #recalculateConfirmOverlay', function(e) {
            if (e.target === this) {
                $('#recalculateConfirmOverlay').removeClass('show');
                setTimeout(() => {
                    $('#recalculateConfirmOverlay').remove();
                }, 300);
            }
        });
        
        // Handle OK - proceed with recalculation
        $(document).on('click', '#confirmOkBtn', function() {
            $('#recalculateConfirmOverlay').removeClass('show');
            setTimeout(() => {
                $('#recalculateConfirmOverlay').remove();
            }, 300);
            
            // Proceed with recalculation
            proceedWithRecalculation($button, originalHtml);
        });
    }

    // Function to handle the actual recalculation
    function proceedWithRecalculation($button, originalHtml) {
        // Disable button and show loading state
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Recalculating...');
        
        // Hide previous alerts
        $('#scheduleInfoAlert').hide();
        
        $.ajax({
            url: '../controllers/recalculate_loan_schedule.php',
            type: 'POST',
            data: { loan_id: currentLoanId },
            dataType: 'json',
            timeout: 20000, // 20 second timeout
            success: function(response) {
                if (response.status === 'success') {
                    // Show success message with details
                    const details = response.details || {};
                    let message = `Schedule recalculated successfully! `;
                    message += `Updated ${details.entries_updated || 0} entries. `;
                    message += `${details.paid_installments || 0}/${details.total_installments || 0} installments paid. `;
                    
                    if (details.fully_paid) {
                        message += `Loan is now marked as FULLY PAID.`;
                    } else {
                        message += `Remaining: KSh ${formatCurrency(details.remaining_amount || 0)}.`;
                    }
                    
                    // Show info alert
                    $('#scheduleInfoMessage').text(message);
                    $('#scheduleInfoAlert').removeClass('alert-info alert-warning alert-success')
                                           .addClass('alert-success')
                                           .show();
                    
                    showToast('Loan schedule recalculated successfully!', 'success');
                    
                    // Reload the schedule to show updated values
                    setTimeout(() => {
                        loadLoanSchedule(currentLoanId);
                    }, 1000);
                    
                    // Optionally reload the page to update loan status in main table
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                    
                } else {
                    throw new Error(response.message || 'Recalculation failed');
                }
            },
            error: function(xhr, status, error) {
                console.error('Recalculate schedule error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                
                let errorMessage = 'Error recalculating schedule. ';
                
                if (status === 'timeout') {
                    errorMessage += 'Request timed out. Please try again.';
                } else if (xhr.status === 400) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage += response.message || 'Invalid request.';
                    } catch (e) {
                        errorMessage += 'Invalid request.';
                    }
                } else if (xhr.status === 403) {
                    errorMessage += 'Unauthorized. Please log in again.';
                } else if (xhr.status === 404) {
                    errorMessage += 'Loan not found.';
                } else if (xhr.status === 500) {
                    errorMessage += 'Server error. Please contact support.';
                } else {
                    errorMessage += 'Please try again.';
                }
                
                // Show error alert
                $('#scheduleInfoMessage').text(errorMessage);
                $('#scheduleInfoAlert').removeClass('alert-info alert-success')
                                       .addClass('alert-warning')
                                       .show();
                
                showToast(errorMessage, 'error');
            },
            complete: function() {
                // Re-enable button
                $button.prop('disabled', false).html(originalHtml);
            }
        });
    }

    // Reset current loan ID when modal is closed
    $('#loanScheduleModal').on('hidden.bs.modal', function() {
        currentLoanId = null;
        $('#scheduleInfoAlert').hide();
    });

    // Helper function to get status badge class
    function getStatusBadgeClass(status) {
        switch(status.toLowerCase()) {
            case 'paid': return 'badge-success';
            case 'partial': return 'badge-warning';
            case 'unpaid': return 'badge-danger';
            default: return 'badge-secondary';
        }
    }
    
    // Helper function to format currency
    function formatCurrency(amount) {
        if (isNaN(amount)) return '0.00';
        return parseFloat(amount).toLocaleString('en-KE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
});
</script>