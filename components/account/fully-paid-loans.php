<?php
// This should be saved as components/account/fully-paid-loans.php
?>

<style>
/* Fully Paid Loans Section Styles */
.fully-paid-loans-section {
    margin-bottom: 25px;
}

.fully-paid-loans-section .section-header {
    margin-bottom: 20px;
}

.fully-paid-loans-section .section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #51087E;
    margin: 0;
}

.fully-paid-loans-section .table-container {
    background: #fff;
    border-radius: 0.35rem;
    overflow: hidden;
    box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
    border: 0;
}

.fully-paid-loans-section .card-body {
    padding: 1.25rem;
}

.fully-paid-loans-section .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.fully-paid-loans-section .table {
    width: 100%;
    margin-bottom: 0;
    color: #858796;
    border-collapse: collapse;
}

.fully-paid-loans-section .table thead th {
    background-color: #1cc88a;
    color: #fff;
    border-bottom: 1px solid #e3e6f0;
    border-top: none;
    font-weight: 600;
    padding: 0.75rem;
    vertical-align: middle;
    border-right: none;
    font-size: 0.875rem;
}

.fully-paid-loans-section .table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #e3e6f0;
    border-bottom: none;
    border-right: none;
    font-size: 0.875rem;
}

.fully-paid-loans-section .table tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.025);
}

.fully-paid-loans-section .table-bordered {
    border: 1px solid #e3e6f0;
}

.fully-paid-loans-section .table-bordered th,
.fully-paid-loans-section .table-bordered td {
    border: 1px solid #e3e6f0;
}

/* Success Badge */
.badge-completed { 
    background-color: #1cc88a; 
    color: #fff; 
}

/* Button Styles */
.btn-success-modern {
    background-color: #1cc88a;
    border-color: #1cc88a;
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

.btn-success-modern:hover {
    background-color: #169b6b;
    border-color: #169b6b;
    color: #fff;
    text-decoration: none;
}

.btn-success-modern:focus {
    box-shadow: 0 0 0 0.2rem rgba(28, 200, 138, 0.25);
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
    color: #1cc88a;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.empty-text {
    color: #858796;
    font-size: 1rem;
    margin: 0;
}

/* Statistics Cards */
.stats-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-summary-card {
    background: linear-gradient(135deg, #1cc88a 0%, #169b6b 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 0.5rem;
    text-align: center;
    box-shadow: 0 4px 15px rgba(28, 200, 138, 0.3);
}

.stat-summary-card .stat-value {
    font-size: 2rem;
    font-weight: bold;
    display: block;
}

.stat-summary-card .stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-top: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .fully-paid-loans-section .table {
        font-size: 0.8rem;
    }
    
    .fully-paid-loans-section .table thead th,
    .fully-paid-loans-section .table tbody td {
        padding: 0.5rem 0.375rem;
    }
    
    .btn-sm {
        padding: 0.125rem 0.25rem;
        font-size: 0.75rem;
    }

    .stats-summary {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .fully-paid-loans-section .section-title {
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

<!-- Fully Paid Loans Section HTML Structure -->
<div class="content-section fully-paid-loans-section" id="fully-paid-loans-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-check-circle"></i>
            Fully Paid Loans
        </h2>
    </div>

    <!-- Summary Statistics -->
    <div class="stats-summary" id="fullyPaidStats">
        <div class="stat-summary-card">
            <span class="stat-value" id="totalCompletedLoans">0</span>
            <span class="stat-label">Total Completed Loans</span>
        </div>
        <div class="stat-summary-card">
            <span class="stat-value" id="totalAmountPaid">KSh 0</span>
            <span class="stat-label">Total Amount Paid</span>
        </div>
        <div class="stat-summary-card">
            <span class="stat-value" id="totalInterestPaid">KSh 0</span>
            <span class="stat-label">Total Interest Paid</span>
        </div>
    </div>
    
    <div id="fullyPaidLoansContainer">
        <!-- Fully paid loans will be loaded here -->
    </div>
</div>

<!-- Fully Paid Loan Schedule Modal -->
<div class="modal fade" id="fullyPaidLoanScheduleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #1cc88a; color: #fff;">
                <h5 class="modal-title text-white">
                    <i class="fas fa-calendar-check mr-2"></i>Completed Loan Payment Schedule
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>Loan Completed Successfully!</strong>
                            <span id="completionInfo"></span>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered" id="fullyPaidScheduleTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Due Date</th>
                                <th>Principal</th>
                                <th>Interest</th>
                                <th>Due Amount</th>
                                <th>Amount Paid</th>
                                <th>Payment Date</th>
                                <th>Days Late/Early</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="fullyPaidScheduleTableBody">
                            <!-- Schedule data will be inserted here -->
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #f8f9fc; font-weight: bold;">
                                <td>TOTALS</td>
                                <td id="totalPrincipal">-</td>
                                <td id="totalInterest">-</td>
                                <td id="totalDueAmount">-</td>
                                <td id="totalAmountPaid">-</td>
                                <td colspan="3">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="printFullyPaidSchedule">
                    <i class="fas fa-print"></i> Print Schedule
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let fullyPaidLoansTable;
    let currentFullyPaidLoanData = {};

    // Load fully paid loans when section becomes active
    $(document).on('sectionChanged', function(event, section) {
        if (section === 'fully-paid-loans') {
            loadFullyPaidLoans();
        }
    });

    function loadFullyPaidLoans() {
        showLoading('#fullyPaidLoansContainer');
        
        console.log('Loading fully paid loans for account:', ACCOUNT_ID);
        
        $.ajax({
            url: '../controllers/accountController.php',
            method: 'GET',
            data: {
                action: 'getFullyPaidLoans',
                accountId: ACCOUNT_ID,
                accountType: $('#accountTypeFilter').val() || 'all'
            },
            dataType: 'json',
            success: function(response) {
                console.log('Fully paid loans response:', response);
                if (response.status === 'success') {
                    displayFullyPaidLoans(response.loans);
                    updateSummaryStats(response.summary);
                } else {
                    showError('#fullyPaidLoansContainer', response.message || 'Error loading fully paid loans');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error Details:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                showError('#fullyPaidLoansContainer', 'Error loading fully paid loans: ' + error);
            }
        });
    }

    function displayFullyPaidLoans(loans) {
        const container = $('#fullyPaidLoansContainer');
        
        if (!loans || loans.length === 0) {
            container.html(`
                <div class="empty-state">
                    <i class="fas fa-check-circle empty-icon"></i>
                    <p class="empty-text">No fully paid loans found for this account.</p>
                </div>
            `);
            return;
        }

        const tableHtml = `
            <div class="card mb-4 table-container">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="fullyPaidLoansTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Ref No</th>
                                    <th>Loan Product</th>
                                    <th>Original Amount</th>
                                    <th>Interest Rate</th>
                                    <th>Total Paid</th>
                                    <th>Interest Paid</th>
                                    <th>Date Applied</th>
                                    <th>Date Completed</th>
                                    <th>Duration</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${loans.map(loan => `
                                    <tr>
                                        <td>${loan.ref_no}</td>
                                        <td>${loan.loan_product_id}</td>
                                        <td>KSh ${formatCurrency(loan.amount)}</td>
                                        <td>${parseFloat(loan.interest_rate).toFixed(2)}%</td>
                                        <td>KSh ${formatCurrency(loan.total_paid)}</td>
                                        <td>KSh ${formatCurrency(loan.interest_paid)}</td>
                                        <td>${formatDate(loan.date_applied)}</td>
                                        <td>${formatDate(loan.date_completed)}</td>
                                        <td>${loan.duration_months} months</td>
                                        <td>
                                            <button class="btn btn-success-modern btn-sm view-completed-schedule" data-loan-id="${loan.loan_id}">
                                                <i class="fas fa-calendar-check"></i> View Schedule
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;

        container.html(tableHtml);
        initializeFullyPaidLoansTable();
    }

    function initializeFullyPaidLoansTable() {
        if (fullyPaidLoansTable) {
            fullyPaidLoansTable.destroy();
        }

        fullyPaidLoansTable = $('#fullyPaidLoansTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[7, 'desc']], // Order by date completed
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

    function updateSummaryStats(summary) {
        if (summary) {
            $('#totalCompletedLoans').text(summary.total_loans || 0);
            $('#totalAmountPaid').text('KSh ' + formatCurrency(summary.total_amount_paid || 0));
            $('#totalInterestPaid').text('KSh ' + formatCurrency(summary.total_interest_paid || 0));
        }
    }

    // View Completed Loan Schedule
    $(document).on('click', '.view-completed-schedule', function() {
        const loanId = $(this).data('loan-id');
        
        $.ajax({
            url: '../controllers/accountController.php',
            type: 'GET',
            data: { 
                action: 'getFullyPaidLoanSchedule',
                loan_id: loanId 
            },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success' && response.schedule) {
                    currentFullyPaidLoanData = response.loan_details;
                    displayCompletedSchedule(response.schedule, response.loan_details);
                    $('#fullyPaidLoanScheduleModal').modal('show');
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

    function displayCompletedSchedule(schedule, loanDetails) {
        const tableBody = $('#fullyPaidScheduleTableBody');
        const completionInfo = $('#completionInfo');
        
        tableBody.empty();
        
        let totalPrincipal = 0;
        let totalInterest = 0;
        let totalDueAmount = 0;
        let totalAmountPaid = 0;

        $.each(schedule, function(index, item) {
            const dueDate = new Date(item.due_date);
            const paidDate = new Date(item.paid_date);
            const daysDifference = Math.floor((paidDate - dueDate) / (1000 * 60 * 60 * 24));
            
            let paymentStatus = '';
            if (daysDifference > 0) {
                paymentStatus = `<span class="text-warning">${daysDifference} days late</span>`;
            } else if (daysDifference < 0) {
                paymentStatus = `<span class="text-success">${Math.abs(daysDifference)} days early</span>`;
            } else {
                paymentStatus = '<span class="text-info">On time</span>';
            }

            const principal = parseFloat(item.principal.replace(/,/g, ''));
            const interest = parseFloat(item.interest.replace(/,/g, ''));
            const dueAmount = parseFloat(item.amount.replace(/,/g, ''));
            const amountPaid = parseFloat(item.repaid_amount.replace(/,/g, ''));

            totalPrincipal += principal;
            totalInterest += interest;
            totalDueAmount += dueAmount;
            totalAmountPaid += amountPaid;
            
            const row = `
                <tr>
                    <td>${formatDate(item.due_date)}</td>
                    <td>KSh ${item.principal}</td>
                    <td>KSh ${item.interest}</td>
                    <td>KSh ${item.amount}</td>
                    <td>KSh ${item.repaid_amount}</td>
                    <td>${formatDate(item.paid_date)}</td>
                    <td>${paymentStatus}</td>
                    <td><span class="badge-modern badge-completed">Paid</span></td>
                </tr>
            `;
            tableBody.append(row);
        });

        // Update totals
        $('#totalPrincipal').text('KSh ' + formatCurrency(totalPrincipal));
        $('#totalInterest').text('KSh ' + formatCurrency(totalInterest));
        $('#totalDueAmount').text('KSh ' + formatCurrency(totalDueAmount));
        $('#totalAmountPaid').text('KSh ' + formatCurrency(totalAmountPaid));

        // Update completion info
        if (loanDetails) {
            const completionText = `This loan was completed on ${formatDate(loanDetails.date_completed)}. 
                                   Total amount paid: KSh ${formatCurrency(totalAmountPaid)}.`;
            completionInfo.text(completionText);
        }
    }

    // Print Completed Schedule
    $(document).on('click', '#printFullyPaidSchedule', function() {
        printCompletedLoanSchedule(currentFullyPaidLoanData);
    });

    function printCompletedLoanSchedule(loanData) {
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        const scheduleRows = [];
        
        $('#fullyPaidScheduleTableBody tr').each(function() {
            const cells = $(this).find('td');
            scheduleRows.push({
                dueDate: cells.eq(0).text(),
                principal: cells.eq(1).text(),
                interest: cells.eq(2).text(),
                dueAmount: cells.eq(3).text(),
                amountPaid: cells.eq(4).text(),
                paidDate: cells.eq(5).text(),
                status: cells.eq(6).text()
            });
        });

        const content = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Completed Loan Schedule</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1cc88a; padding-bottom: 15px; }
                    .loan-info { margin-bottom: 20px; background: #f8f9fc; padding: 15px; border-radius: 5px; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
                    th { background-color: #1cc88a; color: white; font-weight: bold; }
                    .totals { background-color: #f8f9fc; font-weight: bold; }
                    .completion-badge { background: #1cc88a; color: white; padding: 10px; text-align: center; border-radius: 5px; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>Lato SACCO LTD</h2>
                    <h3>Completed Loan Payment Schedule</h3>
                </div>
                
                <div class="completion-badge">
                    ✓ LOAN FULLY PAID AND COMPLETED
                </div>

                <div class="loan-info">
                    <strong>Loan Reference:</strong> ${loanData.ref_no || 'N/A'}<br>
                    <strong>Client:</strong> ${loanData.client_name || 'N/A'}<br>
                    <strong>Original Amount:</strong> KSh ${formatCurrency(loanData.amount || 0)}<br>
                    <strong>Date Completed:</strong> ${formatDate(loanData.date_completed)}<br>
                    <strong>Total Payments Made:</strong> ${scheduleRows.length}
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Due Date</th>
                            <th>Principal</th>
                            <th>Interest</th>
                            <th>Due Amount</th>
                            <th>Amount Paid</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${scheduleRows.map(row => `
                            <tr>
                                <td>${row.dueDate}</td>
                                <td>${row.principal}</td>
                                <td>${row.interest}</td>
                                <td>${row.dueAmount}</td>
                                <td>${row.amountPaid}</td>
                                <td>${row.paidDate}</td>
                                <td>PAID</td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot>
                        <tr class="totals">
                            <td>TOTALS</td>
                            <td>${$('#totalPrincipal').text()}</td>
                            <td>${$('#totalInterest').text()}</td>
                            <td>${$('#totalDueAmount').text()}</td>
                            <td>${$('#totalAmountPaid').text()}</td>
                            <td colspan="2">-</td>
                        </tr>
                    </tfoot>
                </table>

                <div style="text-align: center; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 15px;">
                    <p><strong>Thank you for banking with us!</strong></p>
                    <p>Printed on: ${formatDateTime(new Date())}</p>
                </div>
            </body>
            </html>
        `;
        
        printWindow.document.write(content);
        printWindow.document.close();
        setTimeout(() => { 
            printWindow.print(); 
            printWindow.close(); 
        }, 500);
    }

    // Utility functions
    function showLoading(selector) {
        $(selector).html(`
            <div class="text-center py-4">
                <div class="spinner-border text-success" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading fully paid loans...</p>
            </div>
        `);
    }

    function showError(selector, message) {
        $(selector).html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                ${message}
            </div>
        `);
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('en-KE', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    // Listen for account type filter changes
    $(document).on('change', '#accountTypeFilter', function() {
        const activeSection = $('.content-section.active').attr('id');
        if (activeSection === 'fully-paid-loans-section') {
            loadFullyPaidLoans();
        }
    });

    // Make functions globally accessible if needed
    window.loadFullyPaidLoans = loadFullyPaidLoans;
});
</script>