<!-- Loan Details Modal -->
<div class="modal fade" id="loanDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #51087E;">
                <h5 class="modal-title text-white">Loan Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="loanDetailsLoading" class="text-center p-4">
                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                    <p class="mt-2">Loading loan details...</p>
                </div>
                
                <div id="loanDetailsContent" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="m-0 font-weight-bold">Client Information</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            <tr>
                                                <td width="40%"><strong>Name:</strong></td>
                                                <td id="loanClientName"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Phone Number:</strong></td>
                                                <td id="loanClientPhone"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Location:</strong></td>
                                                <td id="loanClientLocation"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="m-0 font-weight-bold">Client Pledges</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Item</th>
                                                <th>Value</th>
                                            </tr>
                                        </thead>
                                        <tbody id="clientPledgesTableBody">
                                            <!-- Client pledges will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="m-0 font-weight-bold">Loan Information</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            <tr>
                                                <td width="40%"><strong>Reference No:</strong></td>
                                                <td id="loanRefNo"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Loan Product:</strong></td>
                                                <td id="loanType"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Loan Term:</strong></td>
                                                <td id="loanTerm"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Interest Rate:</strong></td>
                                                <td id="loanInterestRate"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Amount:</strong></td>
                                                <td id="loanAmount"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Payable:</strong></td>
                                                <td id="loanTotalPayable"></td>
                                            </tr>
                                            <tr id="nextPaymentRow" style="display: none;">
                                                <td><strong>Next Payment Date:</strong></td>
                                                <td id="nextPaymentDate"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Meeting Date:</strong></td>
                                                <td id="loanMeetingDate"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Status:</strong></td>
                                                <td id="loanStatus"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="m-0 font-weight-bold">Guarantor Information</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            <tr>
                                                <td width="40%"><strong>Name:</strong></td>
                                                <td id="guarantorName"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>ID Number:</strong></td>
                                                <td id="guarantorId"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Phone Number:</strong></td>
                                                <td id="guarantorPhone"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Location:</strong></td>
                                                <td id="guarantorLocation"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Sub-location:</strong></td>
                                                <td id="guarantorSublocation"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Village:</strong></td>
                                                <td id="guarantorVillage"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="m-0 font-weight-bold">Guarantor Pledges</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Item</th>
                                                <th>Value</th>
                                            </tr>
                                        </thead>
                                        <tbody id="guarantorPledgesTableBody">
                                            <!-- Guarantor pledges will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" id="editLoanBtn">Edit Loan</button>
            </div>
        </div>
    </div>
</div>