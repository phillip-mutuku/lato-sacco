<style>
/* Client Information Styles */
.client-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    background: var(--white);
    padding: 25px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.info-item {
    display: flex;
    flex-direction: column;
    padding: 15px;
    background: var(--light-bg);
    border-radius: var(--border-radius);
    border-left: 3px solid var(--primary-color);
}

.info-label {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 8px;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    color: #333;
    font-size: 1rem;
    font-weight: 500;
    word-break: break-word;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.section-title {
    color: var(--primary-color);
    font-size: 1.4rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

@media (max-width: 768px) {
    .client-info-grid {
        grid-template-columns: 1fr;
        padding: 20px;
    }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<!-- Client Information Section -->
<div class="content-section" id="client-info-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-user"></i>
            Client Information
        </h2>
    </div>
    
    <?php if ($accountDetails): ?>
    <div class="client-info-grid">
        <div class="info-item">
            <span class="info-label">Full Name</span>
            <span class="info-value"><?= htmlspecialchars($accountDetails['first_name'] . ' ' . $accountDetails['last_name']) ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">National ID</span>
            <span class="info-value"><?= htmlspecialchars($accountDetails['national_id'] ?? 'N/A') ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Phone Number</span>
            <span class="info-value"><?= htmlspecialchars($accountDetails['phone_number'] ?? 'N/A') ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Email Address</span>
            <span class="info-value"><?= htmlspecialchars($accountDetails['email'] ?? 'N/A') ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Location</span>
            <span class="info-value"><?= htmlspecialchars($accountDetails['location'] ?? 'N/A') ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Division</span>
            <span class="info-value"><?= htmlspecialchars($accountDetails['division'] ?? 'N/A') ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Village</span>
            <span class="info-value"><?= htmlspecialchars($accountDetails['village'] ?? 'N/A') ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Account Type</span>
            <span class="info-value"><?= htmlspecialchars($accountDetails['account_type'] ?? 'N/A') ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Date Created</span>
            <span class="info-value"><?= isset($accountDetails['date_created']) ? date("Y-m-d", strtotime($accountDetails['date_created'])) : 'N/A' ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Account Status</span>
            <span class="info-value">
                <span class="badge-modern badge-success">Active</span>
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">Shareholder Number</span>
            <span class="info-value"><?= htmlspecialchars($accountDetails['shareholder_no'] ?? 'N/A') ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Outstanding Loans</span>
            <span class="info-value">KSh <?= number_format($outstandingPrincipal ?? 0, 2) ?></span>
        </div>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-user empty-icon"></i>
        <p class="empty-text">Client information not available.</p>
    </div>
    <?php endif; ?>
</div>