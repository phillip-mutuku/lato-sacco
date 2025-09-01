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