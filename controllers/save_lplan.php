<?php
	require_once'../config/class.php';
	if(ISSET($_POST['save'])){
		$db=new db_class();
		$lplan_month=$_POST['lplan_month'];
		$lplan_interest=$_POST['lplan_interest'];
		$lplan_penalty=$_POST['lplan_penalty'];
		
		$db->save_lplan($lplan_month,$lplan_interest,$lplan_penalty);
		
		header("location: ../models/loan_plan.php");
	}
?>