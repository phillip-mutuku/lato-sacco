<?php
// components/general_reporting/reports_filter.php

class ReportsFilter {
    
    public static function renderFilterForm($current_start, $current_end, $current_type) {
        ob_start();
        ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-primary">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-filter"></i> Report Filters
                </h6>
            </div>
            <div class="card-body">
                <form method="GET" action="" id="reportFilterForm">
                    <div class="row">
                        <!-- Start Date -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="start_date" class="font-weight-bold">Start Date:</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="start_date" 
                                       name="start_date" 
                                       value="<?php echo htmlspecialchars($current_start); ?>"
                                       required>
                            </div>
                        </div>
                        
                        <!-- End Date -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="end_date" class="font-weight-bold">End Date:</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="end_date" 
                                       name="end_date" 
                                       value="<?php echo htmlspecialchars($current_end); ?>"
                                       required>
                            </div>
                        </div>
                        
                        <!-- Filter Type -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_type" class="font-weight-bold">Filter Type:</label>
                                <select class="form-control" id="filter_type" name="filter_type">
                                    <option value="all" <?php echo $current_type === 'all' ? 'selected' : ''; ?>>
                                        Both (Debit & Credit)
                                    </option>
                                    <option value="debit" <?php echo $current_type === 'debit' ? 'selected' : ''; ?>>
                                        Debit Only (Income)
                                    </option>
                                    <option value="credit" <?php echo $current_type === 'credit' ? 'selected' : ''; ?>>
                                        Credit Only (Expenses)
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="d-block">&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Date Filters -->
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label class="font-weight-bold">Quick Filters:</label>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('today')">
                                    Today
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('week')">
                                    This Week
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('month')">
                                    This Month
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('quarter')">
                                    This Quarter
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('year')">
                                    This Year
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('last_month')">
                                    Last Month
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('last_year')">
                                    Last Year
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        function setDateRange(period) {
            const today = new Date();
            let startDate, endDate;
            
            switch(period) {
                case 'today':
                    startDate = endDate = formatDate(today);
                    break;
                    
                case 'week':
                    const weekStart = new Date(today);
                    weekStart.setDate(today.getDate() - today.getDay());
                    startDate = formatDate(weekStart);
                    endDate = formatDate(today);
                    break;
                    
                case 'month':
                    startDate = formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
                    endDate = formatDate(new Date(today.getFullYear(), today.getMonth() + 1, 0));
                    break;
                    
                case 'quarter':
                    const quarter = Math.floor(today.getMonth() / 3);
                    startDate = formatDate(new Date(today.getFullYear(), quarter * 3, 1));
                    endDate = formatDate(new Date(today.getFullYear(), quarter * 3 + 3, 0));
                    break;
                    
                case 'year':
                    startDate = formatDate(new Date(today.getFullYear(), 0, 1));
                    endDate = formatDate(new Date(today.getFullYear(), 11, 31));
                    break;
                    
                case 'last_month':
                    startDate = formatDate(new Date(today.getFullYear(), today.getMonth() - 1, 1));
                    endDate = formatDate(new Date(today.getFullYear(), today.getMonth(), 0));
                    break;
                    
                case 'last_year':
                    startDate = formatDate(new Date(today.getFullYear() - 1, 0, 1));
                    endDate = formatDate(new Date(today.getFullYear() - 1, 11, 31));
                    break;
            }
            
            document.getElementById('start_date').value = startDate;
            document.getElementById('end_date').value = endDate;
        }
        
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        // Validate dates before submission
        document.getElementById('reportFilterForm').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            
            if (startDate > endDate) {
                e.preventDefault();
                alert('Start date cannot be after end date!');
                return false;
            }
            
            // Check if date range is too large (more than 2 years)
            const daysDiff = (endDate - startDate) / (1000 * 60 * 60 * 24);
            if (daysDiff > 730) {
                if (!confirm('You have selected a date range of more than 2 years. This may take a while to process. Continue?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
?>