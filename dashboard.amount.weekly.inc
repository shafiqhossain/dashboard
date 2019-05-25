<?php

function dashboard_total_weekly_amount_refresh($ajax='', $return_type=0) {
  global $base_url, $user;
  
  if($ajax) {
	ctools_include('ajax');
	//ctools_include('modal');
    //ctools_modal_add_js();
	
    //this week
    $sql  = "SELECT SUM(am.field_rx_amount_value) as total ";
    $sql .= "FROM {node} n ";
    $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid and fc.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value and td.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value and am.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value and ts.bundle = 'field_billing_info' ";
    $sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Successful - Fee', 'Successful - PSCC', 'Paid') ";
    $sql .= "AND YEAR(td.field_tx_date_value) = '".date('Y')."' ";
    $sql .= "AND WEEKOFYEAR(td.field_tx_date_value) = '".date("W")."' ";
    $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $rx_total_week = db_query($sql)->fetchField();
    if(empty($rx_total_week)) $rx_total_week = 0;
    
    $time = date('Y-m-d H:i:s');

    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_week} (dashboard_year, dashboard_month, dashboard_week, total_collected_amount, collected_amount_last_update) VALUES (:dashboard_year, :dashboard_month, :dashboard_week, :total_collected_amount, :collected_amount_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_collected_amount=VALUES(total_collected_amount), collected_amount_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_year' => date('Y'),
      ':dashboard_month' => date('n'),
      ':dashboard_week' => date('W'),
      ':total_collected_amount' => $rx_total_week,
      ':collected_amount_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);


    //rx past week
	$past_week = date('W', strtotime("-1 week"));
	$past_week_month = date('n', strtotime("-1 week"));
	$past_week_year = date('Y', strtotime("-1 week"));

    $sql  = "SELECT n.total_collected_amount as total ";
    $sql .= "FROM {dashboard_billing_by_week} n ";
    $sql .= "WHERE n.dashboard_year = '".$past_week_year."' ";
    $sql .= "AND n.dashboard_month = '".$past_week_month."' ";
    $sql .= "AND n.dashboard_week = '".$past_week."' ";
    $rx_total_past_week = db_query($sql)->fetchField();
    if(empty($rx_total_past_week)) $rx_total_past_week = 0;


    //order amount: this week
    $sql  = "SELECT SUM(ot.commerce_order_total_amount) as total ";
    $sql .= "FROM {commerce_order} ord ";
    $sql .= "INNER JOIN {field_data_commerce_order_total} ot ON ot.entity_id = ord.order_id AND ot.bundle = 'commerce_order' ";
    $sql .= "WHERE ord.status IN ('completed', 'processing', 'pending', 'checkout_complete') ";
    $sql .= "AND ot.entity_type = 'commerce_order' ";
    $sql .= "AND YEAR(FROM_UNIXTIME(ord.created)) = '".date('Y')."' ";
    $sql .= "AND WEEKOFYEAR(FROM_UNIXTIME(ord.created)) = '".date("W")."' ";
    $order_total_week = db_query($sql)->fetchField();
    if(empty($order_total_week)) $order_total_week = 0;

    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_week} (dashboard_year, dashboard_month, dashboard_week, total_order_amount, order_amount_last_update) VALUES (:dashboard_year, :dashboard_month, :dashboard_week, :total_order_amount, :order_amount_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_order_amount=VALUES(total_order_amount), order_amount_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_year' => date('Y'),
      ':dashboard_month' => date('n'),
      ':dashboard_week' => date('W'),
      ':total_order_amount' => $order_total_week / 100,
      ':order_amount_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);

    //order past week
	$past_week = date('W', strtotime("-1 week"));
	$past_week_month = date('n', strtotime("-1 week"));
	$past_week_year = date('Y', strtotime("-1 week"));
    $sql  = "SELECT n.total_order_amount as total ";
    $sql .= "FROM {dashboard_billing_by_week} n ";
    $sql .= "WHERE n.dashboard_year = '".$past_week_year."' ";
    $sql .= "AND n.dashboard_month = '".$past_week_month."' ";
    $sql .= "AND n.dashboard_week = '".$past_week."' ";
    $order_total_past_week = db_query($sql)->fetchField();
    if(empty($order_total_past_week)) $order_total_past_week = 0;

	
	/*
	  Increase = New Number - Original Number
	  % increase = Increase ÷ Original Number × 100.
	  Decrease = Original Number - New Number
	  % decrease = Decrease ÷ Original Number × 100
	*/

    $status = dashboard_change_status($rx_total_week, $rx_total_past_week);
    $rx_change = $status['change'];
    $rx_change_marker = $status['marker'];

    $status = dashboard_change_status($order_total_week/100, $order_total_past_week);
    $order_change = $status['change'];
    $order_change_marker = $status['marker'];

	$last_time = date('F j, Y H:i:s', strtotime($time));

	ctools_add_js('ajax-responder');
	$output = array();

	$output[] = ajax_command_invoke('#weekly-total-amount .dashboard-block-amount .rx-amount', 'html', array(number_format($rx_total_week,2)));
	$output[] = ajax_command_invoke('#weekly-total-amount .dashboard-block-amount .order-amount', 'html', array(number_format($order_total_week/100,2)));
	$output[] = ajax_command_invoke('#weekly-total-amount .dashboard-block-amount-percent .rx-amount-percent', 'html', array($rx_change));
	$output[] = ajax_command_invoke('#weekly-total-amount .dashboard-block-amount-percent .order-amount-percent', 'html', array($order_change));
	$output[] = ajax_command_invoke('#weekly-total-amount .dashboard-block-amount-percent .rx-amount-percent-marker', 'html', array($rx_change_marker));
	$output[] = ajax_command_invoke('#weekly-total-amount .dashboard-block-amount-percent .order-amount-percent-marker', 'html', array($order_change_marker));
	$output[] = ajax_command_invoke('#weekly-total-amount .dashboard-block-links .last-update-block', 'html', array($last_time));

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


function dashboard_total_weekly_po_amount_refresh($ajax='', $return_type=0) {
  global $base_url, $user;
  
  if($ajax) {
	ctools_include('ajax');
	//ctools_include('modal');
    //ctools_modal_add_js();

    $time = date('Y-m-d H:i:s');
	
    //po amount: this week
    $sql  = "SELECT pot.field_po_type_value as po_type, SUM(CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2))) as total ";
    $sql .= "FROM {node} n ";
    $sql .= "INNER JOIN {field_data_field_amount} pom ON pom.entity_id = n.nid ";
    $sql .= "INNER JOIN {field_data_field_po_date} pod ON pod.entity_id = n.nid ";
    $sql .= "INNER JOIN {field_data_field_po_type} pot ON pot.entity_id = n.nid ";
    $sql .= "WHERE YEAR(pod.field_po_date_value) = '".date('Y')."' ";
    $sql .= "AND WEEKOFYEAR(pod.field_po_date_value) = '".date("W")."' ";
    $sql .= "AND n.type = 'po' ";
    $sql .= "AND n.status = 1 ";
    $sql .= "GROUP BY pot.field_po_type_value ";

    $results = db_query($sql)->fetchAll();
  
    $total_po_amount = 0;
    $total_po_refund_amount = 0;
  
    if(count($results)>0) {
      foreach($results as $row) {
        if($row->po_type == 1) {
  		  $total_po_amount = $row->total;
        }
        else if($row->po_type == 2) {  //refund
  		  $total_po_refund_amount = $row->total;
        }
      }
    }
    if(empty($total_po_amount)) $total_po_amount = 0;
    if(empty($total_po_refund_amount)) $total_po_refund_amount = 0;

    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_week} (dashboard_year, dashboard_month, dashboard_week, total_po_amount, total_po_refund_amount, po_amount_last_update) VALUES (:dashboard_year, :dashboard_month, :dashboard_week, :total_po_amount, :total_po_refund_amount, :po_amount_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_po_amount=VALUES(total_po_amount), total_po_refund_amount=VALUES(total_po_refund_amount), po_amount_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_year' => date('Y'),
      ':dashboard_month' => date('n'),
      ':dashboard_week' => date('W'),
      ':total_po_amount' => abs($total_po_amount),
      ':total_po_refund_amount' => abs($total_po_refund_amount),
      ':po_amount_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);


    //po amount: past week
	$past_week = date('W', strtotime("-1 week"));
	$past_week_month = date('n', strtotime("-1 week"));
	$past_week_year = date('Y', strtotime("-1 week"));

    $sql  = "SELECT n.total_po_amount, n.total_po_refund_amount ";
    $sql .= "FROM {dashboard_billing_by_week} n ";
    $sql .= "WHERE n.dashboard_year = '".$past_week_year."' ";
    $sql .= "AND n.dashboard_month = '".$past_week_month."' ";
    $sql .= "AND n.dashboard_week = '".$past_week."' ";
    $result = db_query($sql)->fetchObject();

	$total_po_amount_past_week = (isset($result->total_po_amount) && !empty($result->total_po_amount) ? $result->total_po_amount : 0);
	$total_po_refund_amount_past_week = (isset($result->total_po_refund_amount) && !empty($result->total_po_refund_amount) ? $result->total_po_refund_amount : 0);
	
	/*
	  Increase = New Number - Original Number
	  % increase = Increase ÷ Original Number × 100.
	  Decrease = Original Number - New Number
	  % decrease = Decrease ÷ Original Number × 100
	*/

    $status = dashboard_change_status(abs($total_po_amount), abs($total_po_amount_past_week));
    $po_change = $status['change'];
    $po_change_marker = $status['marker'];

    $status = dashboard_change_status(abs($total_po_refund_amount), abs($total_po_refund_amount_past_week));
    $po_refund_change = $status['change'];
    $po_refund_change_marker = $status['marker'];

	$last_time = date('F j, Y H:i:s', strtotime($time));

	ctools_add_js('ajax-responder');
	$output = array();

	$output[] = ajax_command_invoke('#weekly-total-po-amount .dashboard-block-amount .po-amount', 'html', array(number_format($total_po_amount,2)));
	$output[] = ajax_command_invoke('#weekly-total-po-amount .dashboard-block-amount .po-refund-amount', 'html', array(number_format($total_po_refund_amount,2)));
	$output[] = ajax_command_invoke('#weekly-total-po-amount .dashboard-block-amount-percent .po-amount-percent', 'html', array($po_change));
	$output[] = ajax_command_invoke('#weekly-total-po-amount .dashboard-block-amount-percent .po-refund-amount-percent', 'html', array($po_refund_change));
	$output[] = ajax_command_invoke('#weekly-total-po-amount .dashboard-block-amount-percent .po-amount-percent-marker', 'html', array($po_change_marker));
	$output[] = ajax_command_invoke('#weekly-total-po-amount .dashboard-block-amount-percent .po-refund-amount-percent-marker', 'html', array($po_refund_change_marker));
	$output[] = ajax_command_invoke('#weekly-total-po-amount .dashboard-block-links .last-update-block', 'html', array($last_time));

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


function dashboard_weekly_total_sales_amount_refresh($ajax='', $return_type=0) {
  global $base_url, $user;
  
  if($ajax) {
	ctools_include('ajax');
	//ctools_include('modal');
    //ctools_modal_add_js();

    $time = date('Y-m-d H:i:s');
	
    //this week : rx amount
    $sql  = "SELECT SUM(am.field_rx_amount_value) as total ";
    $sql .= "FROM {node} n ";
    $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid and fc.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value and td.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value and am.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value and ts.bundle = 'field_billing_info' ";
    $sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Invoice', 'Paid', 'Signed', 'Successful - Fee', 'Successful - PSCC', 'Manual') ";
    $sql .= "AND YEAR(td.field_tx_date_value) = '".date('Y')."' ";
    $sql .= "AND WEEKOFYEAR(td.field_tx_date_value) = '".date("W")."' ";
    $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $weekly_total_rx_amount = db_query($sql)->fetchField();
    if(empty($weekly_total_rx_amount)) $weekly_total_rx_amount = 0;

    //this week : po amount
    $sql  = "SELECT SUM(CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2))) as total ";
    $sql .= "FROM {node} n ";
    $sql .= "INNER JOIN {field_data_field_amount} pom ON pom.entity_id = n.nid ";
    $sql .= "INNER JOIN {field_data_field_po_date} pod ON pod.entity_id = n.nid ";
    $sql .= "INNER JOIN {field_data_field_po_type} pot ON pot.entity_id = n.nid ";
    $sql .= "WHERE YEAR(pod.field_po_date_value) = '".date('Y')."' ";
    $sql .= "AND WEEKOFYEAR(pod.field_po_date_value) = '".date("W")."' ";
    $sql .= "AND n.type = 'po' ";
    $sql .= "AND n.status = 1 ";
    $sql .= "AND pot.field_po_type_value = 1 ";

    $weekly_total_po_amount = db_query($sql)->fetchField();
    if(empty($weekly_total_po_amount)) $weekly_total_po_amount = 0;

    //this week : store amount
    $sql  = "SELECT SUM(ot.commerce_order_total_amount) as total ";
    $sql .= "FROM {commerce_order} ord ";
    $sql .= "INNER JOIN {field_data_commerce_order_total} ot ON ot.entity_id = ord.order_id AND ot.bundle = 'commerce_order' ";
    $sql .= "WHERE ord.status IN ('completed', 'processing', 'pending', 'checkout_complete') ";
    $sql .= "AND ot.entity_type = 'commerce_order' ";
    $sql .= "AND YEAR(FROM_UNIXTIME(ord.created)) = '".date('Y')."' ";
    $sql .= "AND WEEKOFYEAR(FROM_UNIXTIME(ord.created)) = '".date("W")."' ";
    $weekly_total_store_amount = db_query($sql)->fetchField();
    if(empty($weekly_total_store_amount)) $weekly_total_store_amount = 0;
  
    $weekly_total_store_amount = $weekly_total_store_amount / 100;
  
    //total sales amount
    $weekly_total_sales_amount = $weekly_total_rx_amount + $weekly_total_po_amount + $weekly_total_store_amount;

    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_week} (dashboard_year, dashboard_month, dashboard_week, total_sales_amount, total_sales_amount_last_update) VALUES (:dashboard_year, :dashboard_month, :dashboard_week, :total_sales_amount, :total_sales_amount_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_sales_amount=VALUES(total_sales_amount), total_sales_amount_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_year' => date('Y'),
      ':dashboard_month' => date('n'),
      ':dashboard_week' => date('W'),
      ':total_sales_amount' => $weekly_total_sales_amount,
      ':total_sales_amount_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    

    //rx past week
	$past_week = date('W', strtotime("-1 week"));
	$past_week_month = date('n', strtotime("-1 week"));
	$past_week_year = date('Y', strtotime("-1 week"));

    $sql  = "SELECT n.total_sales_amount as total ";
    $sql .= "FROM {dashboard_billing_by_week} n ";
    $sql .= "WHERE n.dashboard_year = '".$past_week_year."' ";
    $sql .= "AND n.dashboard_month = '".$past_week_month."' ";
    $sql .= "AND n.dashboard_week = '".$past_week."' ";
    $past_weekly_total_sales_amount = db_query($sql)->fetchField();
    if(empty($past_weekly_total_sales_amount)) $past_weekly_total_sales_amount = 0;


	/*
	  Increase = New Number - Original Number
	  % increase = Increase ÷ Original Number × 100.
	  Decrease = Original Number - New Number
	  % decrease = Decrease ÷ Original Number × 100
	*/

    $status = dashboard_change_status($weekly_total_sales_amount, $past_weekly_total_sales_amount);
    $change = $status['change'];
    $change_marker = $status['marker'];

	$last_time = date('F j, Y H:i:s', strtotime($time));

	ctools_add_js('ajax-responder');
	$output = array();

	$output[] = ajax_command_invoke('#weekly-sales-amount .dashboard-block-amount', 'html', array(number_format($weekly_total_sales_amount,2)));
	$output[] = ajax_command_invoke('#weekly-sales-amount .dashboard-block-amount-percent .amount-percent', 'html', array($change));
	$output[] = ajax_command_invoke('#weekly-sales-amount .dashboard-block-amount-percent .amount-percent-marker', 'html', array($change_marker));
	$output[] = ajax_command_invoke('#weekly-sales-amount .dashboard-block-links .last-update-block', 'html', array($last_time));

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