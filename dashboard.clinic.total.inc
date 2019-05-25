<?php

function dashboard_total_clinics_refresh($ajax='', $return_type=0) {
  global $base_url, $user;
  
  if($ajax) {
	ctools_include('ajax');
	//ctools_include('modal');
    //ctools_modal_add_js();
	
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'clinic')
      ->propertyCondition('status', NODE_PUBLISHED);

    $result = $query->execute();
    $items_nids = array();
    if (isset($result['node'])) {
      $items_nids = array_keys($result['node']);
    }
    $total_clinics_today = count($items_nids);
    if(empty($total_clinics_today)) $total_clinics_today = 0;
	
	$time = date('Y-m-d H:i:s');

    //update the table
    $sql_insert = "INSERT INTO {dashboard_count_by_date} (dashboard_date, total_clinics, total_clinics_last_update) VALUES (:dashboard_date,:total_clinics, :total_clinics_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_clinics=VALUES(total_clinics), total_clinics_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => date('Y-m-d'),
      ':total_clinics' => $total_clinics_today,
      ':total_clinics_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);


    //yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_clinics_yesterday = 0;

    $sql  = "SELECT n.total_clinics ";
    $sql .= "FROM {dashboard_count_by_date} n ";
    $sql .= "WHERE DATE_FORMAT(n.dashboard_date, '%Y-%m-%d') = '".$yesterday."' ";
    $result = db_query($sql)->fetchObject();

    $total_clinics_yesterday = (isset($result->total_clinics) ? $result->total_clinics : 0);
    if(empty($total_clinics_yesterday)) $total_clinics_yesterday = 0;
	
	/*
	  Increase = New Number - Original Number
	  % increase = Increase ÷ Original Number × 100.
	  Decrease = Original Number - New Number
	  % decrease = Decrease ÷ Original Number × 100
	*/

    $status = dashboard_change_status($total_clinics_today, $total_clinics_yesterday);
    $change = $status['change'];
    $change_marker = $status['marker'];
	$last_time = date('F j, Y H:i:s', strtotime($time));

	ctools_add_js('ajax-responder');
	$output = array();

	$output[] = ajax_command_invoke('#clinics-summary .dashboard-block-amount', 'html', array(number_format($total_clinics_today,0)));
	$output[] = ajax_command_invoke('#clinics-summary .dashboard-block-amount-percent .amount-percent', 'html', array($change));
	$output[] = ajax_command_invoke('#clinics-summary .dashboard-block-amount-percent .amount-percent-marker', 'html', array($change_marker));
	$output[] = ajax_command_invoke('#clinics-summary .dashboard-block-links .last-update-block', 'html', array($last_time));

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