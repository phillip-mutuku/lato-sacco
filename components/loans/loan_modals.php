<!-- Add Loan Modal-->
<div class="modal fade" id="addModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="../controllers/save_loan.php" id="loanForm">
            <div class="modal-content">
                <div style="background-color: #51087E;" class="modal-header">
                    <h5 class="modal-title text-white">New Loan Application</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Step 1: Client Details -->
                    <div id="step1" class="form-step">
                        <h6 class="mb-3">Client Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Client</label>
                                    <select name="client" class="form-control client-select" required>
                                        <option value="">Select a client</option>
                                        <?php
                                            $clients = $db->display_client_accounts();
                                            while($client = $clients->fetch_array()){
                                                echo "<option value='".$client['account_id']."'>".$client['last_name'].", ".$client['first_name']." (".$client['shareholder_no'].")</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Loan Product</label>
                                    <select name="loan_product_id" class="form-control loan-product-select" required>
                                        <option value="">Select a loan product</option>
                                        <?php
                                            $loan_products = $db->get_loan_types();
                                            foreach($loan_products as $product){
                                                echo "<option value='".$product['id']."' data-interest='".$product['interest_rate']."'>".$product['loan_type']." (".$product['interest_rate']."% interest)</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Loan Amount</label>
                                    <input type="number" name="loan_amount" id="loan_amount" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Loan Term (months)</label>
                                    <input type="number" name="loan_term" id="loan_term" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Meeting Date</label>
                            <input type="date" name="meeting_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Purpose</label>
                            <textarea name="purpose" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Client Pledges</label>
                            <div id="clientPledges">
                                <div class="pledge-entry row mb-2">
                                    <div class="col-md-6">
                                        <input type="text" name="client_pledges[0][item]" class="form-control" placeholder="Item" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" name="client_pledges[0][value]" class="form-control" placeholder="Value" required>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" onclick="addPledge('client')">Add More Pledge</button>
                        </div>
                        <button type="button" class="btn btn-warning" onclick="showStep(2)">Next</button>
                    </div>

                    <!-- Step 2: Guarantor Details -->
                    <div id="step2" class="form-step" style="display: none;">
                        <h6 class="mb-3">Guarantor Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="guarantor_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>National ID</label>
                                    <input type="text" name="guarantor_id" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="text" name="guarantor_phone" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Location</label>
                                    <input type="text" name="guarantor_location" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Sub-location</label>
                                    <input type="text" name="guarantor_sublocation" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Village</label>
                                    <input type="text" name="guarantor_village" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Guarantor Pledges</label>
                            <div id="guarantorPledges">
                                <div class="pledge-entry row mb-2">
                                    <div class="col-md-6">
                                        <input type="text" name="guarantor_pledges[0][item]" class="form-control" placeholder="Item" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" name="guarantor_pledges[0][value]" class="form-control" placeholder="Value" required>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" onclick="addPledge('guarantor')">Add More Pledge</button>
                        </div>
                        <div id="loan_calculation_results" class="mt-3 p-3 bg-light">
                           <h6>Loan Calculation Results</h6>
                           <div class="row">
                               <div class="col-md-4">
                                   <p>Monthly Payment: <strong><span id="monthly_payment"></span></strong></p>
                               </div>
                               <div class="col-md-4">
                                   <p>Total Interest: <strong><span id="total_interest"></span></strong></p>
                               </div>
                               <div class="col-md-4">
                                   <p>Total Payment: <strong><span id="total_payment"></span></strong></p>
                               </div>
                           </div>
                       </div>
                       <button type="button" class="btn btn-secondary" onclick="showStep(1)">Back</button>
                       <button type="submit" name="save_loan" class="btn btn-warning">Save Loan</button>
                   </div>
               </div>
           </div>
       </form>
   </div>
</div>