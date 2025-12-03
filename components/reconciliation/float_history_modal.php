<div id="floatHistoryModal" class="modal-overlay" style="display: none;">
    <div class="modal-content-large">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-clock-history"></i> Closing Float History
            </div>
            <button class="modal-close" onclick="closeFloatHistoryModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <!-- Date Range Filter Section -->
            <div class="filter-section" style="background: #f8f9fc; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div class="row">
                    <div class="col-md-4">
                        <label for="history_start_date" style="font-weight: 600; color: #51087e; font-size: 13px; margin-bottom: 5px; display: block;">
                            <i class="bi bi-calendar-range"></i> Start Date
                        </label>
                        <input type="date" 
                               id="history_start_date" 
                               class="form-control" 
                               style="border: 2px solid #51087e; border-radius: 6px;"
                               value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="history_end_date" style="font-weight: 600; color: #51087e; font-size: 13px; margin-bottom: 5px; display: block;">
                            <i class="bi bi-calendar-check"></i> End Date
                        </label>
                        <input type="date" 
                               id="history_end_date" 
                               class="form-control" 
                               style="border: 2px solid #51087e; border-radius: 6px;"
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4" style="display: flex; align-items: flex-end; gap: 10px;">
                        <button onclick="applyDateFilter()" 
                                class="btn btn-primary" 
                                style="background: #51087e; border: none; padding: 10px 20px; border-radius: 6px; flex: 1;">
                            <i class="bi bi-funnel"></i> Apply Filter
                        </button>
                        <button onclick="resetDateFilter()" 
                                class="btn btn-secondary" 
                                style="padding: 10px 15px; border-radius: 6px;">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Export Section with Date Range -->
            <div class="export-section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px; border-radius: 8px; margin-bottom: 20px; color: white;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h6 style="margin: 0; font-weight: 600; color: white;">
                            <i class="bi bi-download"></i> Export Float History
                        </h6>
                        <small style="color: rgba(255,255,255,0.9);">Export filtered records to PDF report</small>
                    </div>
                    <button onclick="exportFilteredHistory()" 
                            class="btn btn-light" 
                            style="padding: 10px 25px; border-radius: 6px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                        <i class="bi bi-file-pdf"></i> Generate PDF Report
                    </button>
                </div>
            </div>

            <!-- History Table -->
            <div id="floatHistoryContent" style="min-height: 300px;">
                <div class="text-center" style="padding: 40px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Loading float history...</p>
                </div>
            </div>

            <!-- Summary Statistics -->
            <div id="floatHistorySummary" style="display: none; margin-top: 20px; padding: 20px; background: #f8f9fc; border-radius: 8px;">
                <h6 style="color: #51087e; font-weight: 700; margin-bottom: 15px; border-bottom: 2px solid #51087e; padding-bottom: 10px;">
                    <i class="bi bi-bar-chart-line"></i> Summary Statistics
                </h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="summary-card" style="background: linear-gradient(135deg, #20c997 0%, #17a589 100%); color: white; padding: 15px; border-radius: 8px; text-align: center;">
                            <h3 style="margin: 0; font-size: 28px; font-weight: 700;" id="summary_total_resets">0</h3>
                            <small style="opacity: 0.9;">Total Resets</small>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="summary-card" style="background: linear-gradient(135deg, #20c997 0%, #17a589 100%); color: white; padding: 15px; border-radius: 8px; text-align: center;">
                            <h3 style="margin: 0; font-size: 28px; font-weight: 700;" id="summary_average_closing">KSh 0</h3>
                            <small style="opacity: 0.9;">Average Closing</small>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="summary-card" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: white; padding: 15px; border-radius: 8px; text-align: center;">
                            <h3 style="margin: 0; font-size: 28px; font-weight: 700;" id="summary_unique_days">0</h3>
                            <small style="opacity: 0.9;">Days Tracked</small>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="summary-card" style="background: linear-gradient(135deg, #5c7cfa 0%, #4263eb 100%); color: white; padding: 15px; border-radius: 8px; text-align: center;">
                            <h3 style="margin: 0; font-size: 24px; font-weight: 700;" id="summary_highest_closing">KSh 0</h3>
                            <small style="opacity: 0.9;">Highest Closing</small>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="summary-card" style="background: linear-gradient(135deg, #868e96 0%, #6c757d 100%); color: white; padding: 15px; border-radius: 8px; text-align: center;">
                            <h3 style="margin: 0; font-size: 24px; font-weight: 700;" id="summary_lowest_closing">KSh 0</h3>
                            <small style="opacity: 0.9;">Lowest Closing</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    overflow-y: auto;
    padding: 20px;
}

.modal-content-large {
    background: white;
    max-width: 1200px;
    margin: 0 auto;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    background: linear-gradient(135deg, #51087e 0%, #6e1a9e 100%);
    color: white;
    padding: 20px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 20px;
    font-weight: 700;
}

.modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 28px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s;
    line-height: 1;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.modal-body {
    padding: 25px;
}

.float-history-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.float-history-table thead {
    background: #51087e;
    color: white;
}

.float-history-table thead th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.float-history-table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: all 0.2s;
}

.float-history-table tbody tr:hover {
    background: #f8f9fc;
}

.float-history-table tbody td {
    padding: 12px;
    font-size: 14px;
}

.pagination-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
    padding: 15px;
}

.pagination-controls button {
    padding: 8px 16px;
    border: 2px solid #51087e;
    background: white;
    color: #51087e;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.pagination-controls button:hover:not(:disabled) {
    background: #51087e;
    color: white;
}

.pagination-controls button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.pagination-info {
    font-weight: 600;
    color: #51087e;
}
</style>

<script>
let currentPage = 1;
let totalPages = 1;
let currentStartDate = '<?php echo date('Y-m-d', strtotime('-30 days')); ?>';
let currentEndDate = '<?php echo date('Y-m-d'); ?>';

function openFloatHistoryModal() {
    document.getElementById('floatHistoryModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    loadFloatHistory();
}

function closeFloatHistoryModal() {
    document.getElementById('floatHistoryModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function applyDateFilter() {
    const startDate = document.getElementById('history_start_date').value;
    const endDate = document.getElementById('history_end_date').value;
    
    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        alert('Start date must be before end date');
        return;
    }
    
    currentStartDate = startDate;
    currentEndDate = endDate;
    currentPage = 1;
    loadFloatHistory();
}

function resetDateFilter() {
    document.getElementById('history_start_date').value = '<?php echo date('Y-m-d', strtotime('-30 days')); ?>';
    document.getElementById('history_end_date').value = '<?php echo date('Y-m-d'); ?>';
    currentStartDate = '<?php echo date('Y-m-d', strtotime('-30 days')); ?>';
    currentEndDate = '<?php echo date('Y-m-d'); ?>';
    currentPage = 1;
    loadFloatHistory();
}

function loadFloatHistory(page = 1) {
    currentPage = page;
    const url = `../controllers/get_float_history.php?page=${page}&limit=10&start_date=${currentStartDate}&end_date=${currentEndDate}`;
    
    document.getElementById('floatHistoryContent').innerHTML = `
        <div class="text-center" style="padding: 40px;">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3">Loading float history...</p>
        </div>
    `;
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data); // Debug log
            if (data.success) {
                displayFloatHistory(data.data, data.pagination);
                displaySummary(data.summary);
                totalPages = data.pagination.total_pages;
            } else {
                document.getElementById('floatHistoryContent').innerHTML = 
                    `<div class="alert alert-danger">Failed to load float history: ${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading float history:', error);
            document.getElementById('floatHistoryContent').innerHTML = 
                `<div class="alert alert-danger">Error loading float history. Please check console for details.<br>Error: ${error.message}</div>`;
        });
}

function displayFloatHistory(data, pagination) {
    if (data.length === 0) {
        document.getElementById('floatHistoryContent').innerHTML = 
            '<div class="alert alert-info text-center p-4"><i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i><p class="mt-3">No float history records found for the selected period</p></div>';
        return;
    }
    
    let html = `
        <div style="overflow-x: auto;">
            <table class="float-history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th style="text-align: right;">Closing Float (KSh)</th>
                        <th>Reset By</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.forEach(row => {
        const date = new Date(row.date);
        const time = new Date(row.created_at);
        const closingFloat = parseFloat(row.closing_float);
        
        let floatColor = '#495057';
        if (closingFloat > 50000) floatColor = '#28a745';
        else if (closingFloat < 10000) floatColor = '#dc3545';
        else floatColor = '#ff8c00';
        
        html += `
            <tr>
                <td><strong>${date.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</strong></td>
                <td>${time.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}</td>
                <td style="text-align: right; font-weight: 700; color: ${floatColor}; font-size: 15px;">
                    ${closingFloat.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                </td>
                <td>${row.reset_by_full_name}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    
    // Add pagination
    html += `
        <div class="pagination-controls">
            <button onclick="loadFloatHistory(1)" ${currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-double-left"></i> First
            </button>
            <button onclick="loadFloatHistory(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i> Previous
            </button>
            <span class="pagination-info">Page ${pagination.current_page} of ${pagination.total_pages}</span>
            <button onclick="loadFloatHistory(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                Next <i class="fas fa-chevron-right"></i>
            </button>
            <button onclick="loadFloatHistory(${totalPages})" ${currentPage === totalPages ? 'disabled' : ''}>
                Last <i class="fas fa-chevron-double-right"></i>
            </button>
        </div>
    `;
    
    document.getElementById('floatHistoryContent').innerHTML = html;
}

function displaySummary(summary) {
    if (!summary || summary.total_resets === 0) {
        document.getElementById('floatHistorySummary').style.display = 'none';
        return;
    }
    
    document.getElementById('floatHistorySummary').style.display = 'block';
    document.getElementById('summary_total_resets').textContent = summary.total_resets.toLocaleString();
    document.getElementById('summary_unique_days').textContent = summary.unique_days.toLocaleString();
    document.getElementById('summary_average_closing').textContent = 'KSh ' + 
        summary.average_closing.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('summary_highest_closing').textContent = 'KSh ' + 
        summary.highest_closing.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('summary_lowest_closing').textContent = 'KSh ' + 
        summary.lowest_closing.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function exportFilteredHistory() {
    const startDate = document.getElementById('history_start_date').value;
    const endDate = document.getElementById('history_end_date').value;
    
    if (!startDate || !endDate) {
        alert('Please select date range first');
        return;
    }
    
    window.open(`../controllers/export_float_history.php?start_date=${startDate}&end_date=${endDate}`, '_blank');
}

// Close modal when clicking outside
document.getElementById('floatHistoryModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeFloatHistoryModal();
    }
});
</script>