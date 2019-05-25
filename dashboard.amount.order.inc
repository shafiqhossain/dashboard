<?php

function dashboard_total_order_amount_refresh($ajax='', $return_type=0) {
  global $base_url, $user;
  
  if($ajax) {
	ctools_include('ajax');
	//ctools_include('modal');
    //ctools_modal_add_js();
	
    //today
    $sql  = "SELECT SUM(ot.commerce_order_total_amount) as total ";
    $sql .= "FROM {commerce_order} ord ";
    $sql .= "INNER JOIN {field_data_commerce_order_total} ot ON ot.entity_id = ord.order_id AND ot.bundle = 'commerce_order' ";
    $sql .= "WHERE ord.status IN ('completed', 'processing', 'pending', 'checkout_complete') ";
    $sql .= "AND ot.entity_type = 'commerce_order' ";
    $sql .= "AND DATE_FORMAT(FROM_UNIXTIME(ord.created), '%Y-%m-%d') = '".date("Y-m-d")."' ";
    $total_today = db_query($sql)->fetchField();
    if(empty($total_today)) $total_today = 0;
    
    $time = date('Y-m-d H:i:s');

    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_date} (dashboard_date, total_order_amount, order_amount_last_update) VALUES (:dashboard_date,:total_order_amount,:order_amount_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_order_amount=VALUES(total_order_amount), order_amount_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => date('Y-m-d'),
      ':total_order_amount' => $total_today / 100,
      ':order_amount_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);

    //yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $sql  = "SELECT n.total_order_amount as total ";
    $sql .= "FROM {dashboard_billing_by_date} n ";
    $sql .= "WHERE DATE_FORMAT(n.dashboard_date, '%Y-%m-%d') = '".$yesterday."' ";
    $total_yesterday = db_query($sql)->fetchField();
    if(empty($total_yesterday)) $total_yesterday = 0;
	
	/*
	  Increase = New Number - Original Number
	  % increase = Increase ÷ Original Number × 100.
	  Decrease = Original Number - New Number
	  % decrease = Decrease ÷ Original Number × 100
	*/

    $status = dashboard_change_status($total_today / 100, $total_yesterday);
    $change = $status['change'];
    $change_marker = $status['marker'];

	$last_time = date('F j, Y H:i:s', strtotime($time));

	ctools_add_js('ajax-responder');
	$output = array();

	$output[] = ajax_command_invoke('#daily-total-order-amount .dashboard-block-amount', 'html', array(number_format($total_today/100,2)));
	$output[] = ajax_command_invoke('#daily-total-order-amount .dashboard-block-amount-percent .amount-percent', 'html', array($change));
	$output[] = ajax_command_invoke('#daily-total-order-amount .dashboard-block-amount-percent .amount-percent-marker', 'html', array($change_marker));
	$output[] = ajax_command_invoke('#daily-total-order-amount .dashboard-block-links .last-update-block', 'html', array($last_time));

	if($return_type==1) {  //return for auto refresh
	  return $output;
	}
	else {
	  // Return the ajax instructions to the browser via ajax_render().
      print ajax_render($output);
      //exit();
	  drupal_exit();
	}
  }
}

?>