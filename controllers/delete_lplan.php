<?php
	require_once'../config/class.php';
	session_start();
	
	if(ISSET($_REQUEST['lplan_id'])){
		$lplan_id = $_REQUEST['lplan_id'];
		$db = new db_class();
		$db->delete_lplan($lplan_id);
		header('location:../models/loan_plan.php');
	}
?>	