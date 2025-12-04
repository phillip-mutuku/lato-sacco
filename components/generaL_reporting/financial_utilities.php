<?php
// components/general_reporting/financial_utilities.php

class FinancialUtilities {
    
    /**
     * Initialize date filters from request parameters
     */
    public static function initializeFilters() {
        $current_year = date('Y');
        $current_month = date('m');
        
        // Default to current month
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        // Get filter type (credit, debit, or all)
        $filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'all';
        
        return [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'filter_type' => $filter_type,
            'opening_date' => self::getOpeningDate($start_date),
            'closing_date' => $end_date
        ];
    }
    
    /**
     * Get the opening date (day before start date)
     */
    private static function getOpeningDate($start_date) {
        $date = new DateTime($start_date);
        $date->modify('-1 day');
        return $date->format('Y-m-d');
    }
    
    /**
     * Format currency with KSh symbol
     */
    public static function formatCurrency($amount, $show_sign = false) {
        $formatted = 'KSh ' . number_format(abs($amount), 2);
        
        if ($show_sign && $amount < 0) {
            return '(' . $formatted . ')';
        }
        
        return $formatted;
    }
    
    /**
     * Format date for display
     */
    public static function formatDate($date) {
        if (empty($date)) return 'N/A';
        return date('M d, Y', strtotime($date));
    }
    
    /**
     * Calculate percentage
     */
    public static function calculatePercentage($part, $total) {
        if ($total == 0) return 0;
        return round(($part / $total) * 100, 2);
    }
    
    /**
     * Get period description for display
     */
    public static function getPeriodDescription($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        
        if ($interval->days <= 7) {
            return 'Weekly Report';
        } elseif ($interval->days <= 31) {
            return 'Monthly Report';
        } elseif ($interval->days <= 92) {
            return 'Quarterly Report';
        } else {
            return 'Annual Report';
        }
    }
}
?>