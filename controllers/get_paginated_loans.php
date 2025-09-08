<?php 
date_default_timezone_set("Africa/Nairobi"); 
require_once '../helpers/session.php'; 
require_once '../config/class.php'; 
$db = new db_class(); 

// Check if user is logged in 
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {     
    echo json_encode(['error' => 'Unauthorized access']);     
    exit(); 
}  

// Get request parameters 
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1; 
$start = isset($_POST['start']) ? intval($_POST['start']) : 0; 
$length = isset($_POST['length']) ? intval($_POST['length']) : 10; 
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';  

// Get total count and filtered count 
$totalRecords = $db->get_total_loans(); 
$totalFiltered = $db->get_total_loans($search);  

// Get paginated data 
$loans = $db->get_paginated_loans($start, $length, $search); 
$data = [];  

if ($loans) {     
    while ($row = $loans->fetch_assoc()) {         
        // Format status         
        $status_text = '';         
        $status_class = '';         
        switch((int)$row['status']) {             
            case 0:                 
                $status_class = 'badge-warning';                 
                $status_text = 'Pending Approval';                 
                break;             
            case 1:                 
                $status_class = 'badge-info';                 
                $status_text = 'Approved';                 
                break;             
            case 2:                 
                $status_class = 'badge-primary';                 
                $status_text = 'Disbursed';                 
                break;             
            case 3:                 
                $status_class = 'badge-success';                 
                $status_text = 'Completed';                 
                break;             
            case 4:                 
                $status_class = 'badge-danger';                 
                $status_text = 'Denied';                 
                break;         
        }                  
        
        // Client column         
        $client = '<p><small>Name: <strong>' . $row['last_name'] . ', ' . $row['first_name'] . ' (' . $row['shareholder_no'] . ')</strong></small></p>';         
        $client .= '<p><small>Phone Number: <strong>' . $row['phone_number'] . '</strong></small></p>';         
        $client .= '<p><small>Location: <strong>' . $row['location'] . '</strong></small></p>';                  
        
        // Status column         
        $status = '<span class="badge ' . $status_class . '">' . $status_text . '</span>';                  
        
        // Actions column with role-based restrictions
        $actions = '<div class="dropdown">';         
        $actions .= '<button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">Action</button>';         
        $actions .= '<div class="dropdown-menu">';         
        $actions .= '<a class="dropdown-item view-details" href="javascript:void(0)" data-id="' . $row['loan_id'] . '">View Details</a>';         
        
        $loanStatus = (int)$row['status'];
        $userRole = $_SESSION['role'];
        
        // Role-based edit/delete permissions
        if ($userRole === 'admin') {
            // Admins can edit/delete all loans regardless of status
            $actions .= '<a class="dropdown-item bg-warning text-white" href="#" data-toggle="modal" data-target="#updateloan' . $row['loan_id'] . '">Reschedule</a>';             
            $actions .= '<a class="dropdown-item bg-danger text-white" href="#" data-toggle="modal" data-target="#deleteloan' . $row['loan_id'] . '">Delete</a>';
        } else if ($userRole === 'manager') {
            // Managers can only edit/delete pending (0) or denied (4) loans
            if ($loanStatus === 0 || $loanStatus === 4) {
                $actions .= '<a class="dropdown-item bg-warning text-white" href="#" data-toggle="modal" data-target="#updateloan' . $row['loan_id'] . '">Reschedule</a>';             
                $actions .= '<a class="dropdown-item bg-danger text-white" href="#" data-toggle="modal" data-target="#deleteloan' . $row['loan_id'] . '">Delete</a>';
            } else {
                // Show disabled options for approved/disbursed/completed loans
                $actions .= '<a class="dropdown-item disabled text-muted" href="#" style="cursor: not-allowed;" title="Admin access required for this loan status">Reschedule (Admin Only)</a>';             
                $actions .= '<a class="dropdown-item disabled text-muted" href="#" style="cursor: not-allowed;" title="Admin access required for this loan status">Delete (Admin Only)</a>';
            }
        }
        
        
        $actions .= '</div></div>';                  
        
        $data[] = [             
            'client' => $client,             
            'ref_no' => $row['ref_no'],             
            'date_applied' => date("M d, Y", strtotime($row['date_applied'])),             
            'status' => $status,             
            'actions' => $actions,             
            'loan_id' => $row['loan_id']         
        ];     
    } 
}  

// Prepare response 
$response = [     
    'draw' => $draw,     
    'recordsTotal' => $totalRecords,     
    'recordsFiltered' => $totalFiltered,     
    'data' => $data 
];  

echo json_encode($response); 
?>