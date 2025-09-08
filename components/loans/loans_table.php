<!-- Begin Page Content -->
<div class="container-fluid pt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Loans</h1>
        <div>
            <button id="refreshLoansBtn" class="btn btn-sm btn-info mr-2">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button class="btn btn-lg btn-warning" href="#" data-toggle="modal" data-target="#addModal">
                <i class="fas fa-plus"></i> Create New Loan Application
            </button>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="loansTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Reference No</th>
                            <th>Date Applied</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- End of Main Content -->