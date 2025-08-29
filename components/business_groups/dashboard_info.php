<?php
// components/business_groups/dashboard_info.php
?>

<!-- Dashboard Section -->
<div id="dashboard-section" class="content-section active">
    <div class="container-fluid">
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card deposits">
                <div class="stat-card-header">
                    <div class="stat-card-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="stat-card-content">
                        <p class="stat-card-title">Total Deposits</p>
                        <h3 class="stat-card-value">KSh <?= number_format($totalDeposits, 2) ?></h3>
                    </div>
                </div>
            </div>
            <div class="stat-card withdrawals">
                <div class="stat-card-header">
                    <div class="stat-card-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stat-card-content">
                        <p class="stat-card-title">Total Withdrawals</p>
                        <h3 class="stat-card-value">KSh <?= number_format($totalWithdrawals, 2) ?></h3>
                    </div>
                </div>
            </div>
            <div class="stat-card fees">
                <div class="stat-card-header">
                    <div class="stat-card-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-card-content">
                        <p class="stat-card-title">Total Fees</p>
                        <h3 class="stat-card-value">KSh <?= number_format($totalFees, 2) ?></h3>
                    </div>
                </div>
            </div>
            <div class="stat-card balance">
                <div class="stat-card-header">
                    <div class="stat-card-icon">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <div class="stat-card-content">
                        <p class="stat-card-title">Net Balance</p>
                        <h3 class="stat-card-value">KSh <?= number_format($netBalance, 2) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Group Information Section -->
<div id="information-section" class="content-section">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5 class="m-0">Group Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="font-weight-bold">Group Name</h6>
                        <p><?= htmlspecialchars($groupDetails['group_name']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold">Reference Number</h6>
                        <p><?= $groupDetails['reference_name'] ? htmlspecialchars($groupDetails['reference_name']) : '<span class="text-muted">Not assigned</span>' ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <h6 class="font-weight-bold">Chairperson</h6>
                        <p>Name: <?= htmlspecialchars($groupDetails['chairperson_name']) ?><br>
                        ID: <?= htmlspecialchars($groupDetails['chairperson_id_number']) ?><br>
                        Phone: <?= htmlspecialchars($groupDetails['chairperson_phone']) ?></p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="font-weight-bold">Secretary</h6>
                        <p>Name: <?= htmlspecialchars($groupDetails['secretary_name']) ?><br>
                        ID: <?= htmlspecialchars($groupDetails['secretary_id_number']) ?><br>
                        Phone: <?= htmlspecialchars($groupDetails['secretary_phone']) ?></p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="font-weight-bold">Treasurer</h6>
                        <p>Name: <?= htmlspecialchars($groupDetails['treasurer_name']) ?><br>
                        ID: <?= htmlspecialchars($groupDetails['treasurer_id_number']) ?><br>
                        Phone: <?= htmlspecialchars($groupDetails['treasurer_phone']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>