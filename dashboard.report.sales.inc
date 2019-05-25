<?php

function dashboard_report_daily_sales_popup($ajax='') {
  if($ajax) {
	ctools_include('ajax');
	ctools_include('modal');
    ctools_modal_add_js();
	
	$form_state = array(
	  'ajax' => TRUE,
	  'title' => t("Today's Total Sales"),
	);

	// Use ctools to generate ajax instructions for the browser to create
	// a form in a modal popup.
	$output = ctools_modal_form_wrapper('dashboard_report_daily_sales', $form_state);

	// If the form has been submitted, there may be additional instructions
	// such as dismissing the modal popup.
	if(!empty($form_state['ajax_commands'])) {
	  $output = $form_state['ajax_commands'];
	}

	// Return the ajax instructions to the browser via ajax_render().
	print ajax_render($output);
	drupal_exit();
  }
  else {
	return drupal_get_form('dashboard_report_daily_sales');
  }
}

/**
 * Implementation of dashboard_report_daily_sales().
 *
 *   Daily sales
 */
function dashboard_report_daily_sales($form, &$form_state) {
  global $base_url, $user;

  drupal_add_css(drupal_get_path('module', 'dashboard').'/dashboard.css');
  drupal_add_js(drupal_get_path('module', 'dashboard').'/dashboard.js');
  
  ctools_include('ajax');
  ctools_include('modal');
  ctools_modal_add_js();
  
  //today : rx amount
  $sql  = "SELECT n.nid, n.title, td.field_tx_date_value as tx_date, am.field_rx_amount_value as amount, ts.field_tx_status_value as status, bt.field_rx_base_type_value as base_type, rt.field_rxtype_value as rx_type ";
  $sql .= "FROM {node} n ";
  $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
  $sql .= "INNER JOIN {field_data_field_rx_base_type} bt ON bt.entity_id = n.nid AND bt.bundle = 'rx' ";
  $sql .= "INNER JOIN {field_data_field_rxtype} rt ON rt.entity_id = n.nid AND rt.bundle = 'rx' ";
  $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid AND fc.bundle = 'rx' ";
  $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value AND td.bundle = 'field_billing_info' ";
  $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value AND am.bundle = 'field_billing_info' ";
  $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value AND ts.bundle = 'field_billing_info' ";
  $sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Invoice', 'Paid', 'Signed', 'Successful - Fee', 'Successful - PSCC', 'Manual') ";
  $sql .= "AND DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = '".date("Y-m-d")."' ";
  $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
  $sql .= "AND n.type = 'rx' ";
  $sql .= "AND n.status = 1 ";
  $sql .= "ORDER BY td.field_tx_date_value DESC ";
  $results = db_query($sql)->fetchAll();

  $rx_links = '<table cellspacing="0" cellpadding="5" class="rx-link-list">';
  $rx_links .= '<thead>';
  $rx_links .= '  <tr>';
  $rx_links .= '    <th>Rx</th>';
  $rx_links .= '    <th>Transaction Date</th>';
  $rx_links .= '    <th>Base Type</th>';
  $rx_links .= '    <th>Rx Type</th>';
  $rx_links .= '    <th class="amount">Amount</th>';
  $rx_links .= '  </tr>';
  $rx_links .= '</thead>';
  $rx_links .= '<tbody>';
  
  $total_amount = 0;
  
  if(count($results)>0) {
    foreach($results as $row) {
	  $rx_type = (isset($row->rx_type) ? $row->rx_type : '');
	  if($rx_type == 0) {
		$rx_type_value = t('Build-Up Phase - Six months (1-5M)');
	  }
	  else if($rx_type == 1) {
		$rx_type_value = t('Build-Up Phase with Maintenance Dose - 12 months (1-5M, Two 5M refills)');
	  }
	  else if($rx_type == 2) {
		$rx_type_value = t('Annual Maintenance Refill Dose - 12 months (Four 5M Refills)');
	  }
	  else if($rx_type == 3) {
		$rx_type_value = t('Refill kit');
	  }
	  else if($rx_type == 4) {
		$rx_type_value = t('Build-Up Phase (30 days)');
	  }
	  else if($rx_type == 5) {
		$rx_type_value = t('Build-Up Phase (90 days)');
	  }
	  else if($rx_type == 6) {
		$rx_type_value = t('Annual Maintenance Refill Dose (30 days)');
	  }
	  else if($rx_type == 7) {
		$rx_type_value = t('Annual Maintenance Refill Dose (90 days)');
	  }
	  else if($rx_type == 8) {
		$rx_type_value = t('Build-Up Phase (1st year)');
	  }
	  else if($rx_type == 9) {
		$rx_type_value = t('Maintenance Phase');
	  }
	  else {
		$rx_type_value = '-';
	  }

  	  $rx_links .= '  <tr>';
  	  $rx_links .= '    <td><a target="_blank" href="/node/'.$row->nid.'">'.$row->title.'</a></td>';
  	  $rx_links .= '    <td>'.(isset($row->tx_date) && !empty($row->tx_date) ? date('m/d/Y', strtotime($row->tx_date)) : '').'</td>';
  	  $rx_links .= '    <td>'.(isset($row->base_type) && !empty($row->base_type) ? $row->base_type : '').'</td>';
  	  $rx_links .= '    <td>'.$rx_type_value.'</td>';
  	  $rx_links .= '    <td class="amount">'.(isset($row->amount) && !empty($row->amount) ? '$ '.number_format($row->amount,2) : '').'</td>';
  	  $rx_links .= '  </tr>';
  	  
  	  $total_amount = $total_amount + floatval($row->amount);
    }
    $daily_total_rx_amount = $total_amount;
    
  	$rx_links .= '  <tr class="summary">';
  	$rx_links .= '    <td class="summary-label" colspan="4">Total Amount: </td>';
  	$rx_links .= '    <td class="summary-value">$ '.number_format($total_amount,2).'</td>';
  	$rx_links .= '  </tr>';
  }
  else {
    $rx_links .= '  <tr>';
    $rx_links .= '    <td colspan="5">Sorry! No rx information found!</td>';
    $rx_links .= '  </tr>';
  }
  $rx_links .= '</tbody>';
  $rx_links .= '</table>';


  //today : po amount
  $sql  = "SELECT n.nid, n.title, pod.field_po_date_value as tx_date, CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2)) as amount ";
  $sql .= "FROM {node} n ";
  $sql .= "INNER JOIN {field_data_field_amount} pom ON pom.entity_id = n.nid ";
  $sql .= "INNER JOIN {field_data_field_po_date} pod ON pod.entity_id = n.nid ";
  $sql .= "INNER JOIN {field_data_field_po_type} pot ON pot.entity_id = n.nid ";
  $sql .= "WHERE DATE_FORMAT(pod.field_po_date_value, '%Y-%m-%d') = '".date("Y-m-d")."' ";
  $sql .= "AND n.type = 'po' ";
  $sql .= "AND n.status = 1 ";
  $sql .= "AND pot.field_po_type_value = 1 ";
  $sql .= "ORDER BY pod.field_po_date_value DESC ";
  $results = db_query($sql)->fetchAll();

  $po_links = '<table cellspacing="0" cellpadding="5" class="rx-link-list">';
  $po_links .= '<thead>';
  $po_links .= '  <tr>';
  $po_links .= '    <th>PO</th>';
  $po_links .= '    <th>Transaction Date</th>';
  $po_links .= '    <th class="amount">Amount</th>';
  $po_links .= '  </tr>';
  $po_links .= '</thead>';
  $po_links .= '<tbody>';
  
  $total_amount = 0;
  $daily_total_po_amount = 0;
  
  if(count($results)>0) {
    foreach($results as $row) {
  	  $po_links .= '  <tr>';
  	  $po_links .= '    <td><a target="_blank" href="/node/'.$row->nid.'">'.$row->title.'</a></td>';
  	  $po_links .= '    <td>'.(isset($row->tx_date) && !empty($row->tx_date) ? date('m/d/Y', strtotime($row->tx_date)) : '').'</td>';
  	  $po_links .= '    <td class="amount">'.(isset($row->amount) && !empty($row->amount) ? '$ '.number_format($row->amount,2) : '').'</td>';
  	  $po_links .= '  </tr>';
  	  
  	  $total_amount = $total_amount + floatval($row->amount);
    }
    $daily_total_po_amount = $total_amount;
    
  	$po_links .= '  <tr class="summary">';
  	$po_links .= '    <td class="summary-label" colspan="2">Total Amount: </td>';
  	$po_links .= '    <td class="summary-value">$ '.number_format($total_amount,2).'</td>';
  	$po_links .= '  </tr>';
  }
  else {
    $po_links .= '  <tr>';
    $po_links .= '    <td colspan="3">Sorry! No PO information found!</td>';
    $po_links .= '  </tr>';
  }
  $po_links .= '</tbody>';
  $po_links .= '</table>';


  //today : store amount
  $sql  = "SELECT ord.order_id, DATE_FORMAT(FROM_UNIXTIME(ord.created), '%Y-%m-%d') as tx_date, ord.uid, ot.commerce_order_total_amount / 100 as amount ";
  $sql .= "FROM {commerce_order} ord ";
  $sql .= "INNER JOIN {field_data_commerce_order_total} ot ON ot.entity_id = ord.order_id AND ot.bundle = 'commerce_order' ";
  $sql .= "WHERE ord.status IN ('completed', 'processing', 'pending', 'checkout_complete') ";
  $sql .= "AND ot.entity_type = 'commerce_order' ";
  $sql .= "AND DATE_FORMAT(FROM_UNIXTIME(ord.created), '%Y-%m-%d') = '".date("Y-m-d")."' ";
  $sql .= "ORDER BY ord.created DESC ";
  $results = db_query($sql)->fetchAll();
  
  $order_links = '<table cellspacing="0" cellpadding="5" class="rx-link-list">';
  $order_links .= '<thead>';
  $order_links .= '  <tr>';
  $order_links .= '    <th>Order</th>';
  $order_links .= '    <th>Order Date</th>';
  $order_links .= '    <th class="amount">Amount</th>';
  $order_links .= '  </tr>';
  $order_links .= '</thead>';
  $order_links .= '<tbody>';
  
  $total_amount = 0;
  $daily_total_store_amount = 0;
  
  if(count($results)>0) {
    foreach($results as $row) {
  	  $order_links .= '  <tr>';
  	  $order_links .= '    <td><a target="_blank" href="/user/'.$row->uid.'/orders/'.$row->order_id.'">Order# '.$row->order_id.'</a></td>';
  	  $order_links .= '    <td>'.(isset($row->tx_date) && !empty($row->tx_date) ? date('m/d/Y', strtotime($row->tx_date)) : '').'</td>';
  	  $order_links .= '    <td class="amount">'.(isset($row->amount) && !empty($row->amount) ? '$ '.number_format($row->amount,2) : '').'</td>';
  	  $order_links .= '  </tr>';
  	  
  	  $total_amount = $total_amount + floatval($row->amount);
    }
    $daily_total_store_amount = $total_amount;

  	$order_links .= '  <tr class="summary">';
  	$order_links .= '    <td class="summary-label" colspan="2">Total Amount: </td>';
  	$order_links .= '    <td class="summary-value">$ '.number_format($total_amount,2).'</td>';
  	$order_links .= '  </tr>';
  }
  else {
    $order_links .= '  <tr>';
    $order_links .= '    <td colspan="3">Sorry! No order information found!</td>';
    $order_links .= '  </tr>';
  }
  $order_links .= '</tbody>';
  $order_links .= '</table>';


  //total sales amount
  $daily_total_sales_amount = $daily_total_rx_amount + $daily_total_po_amount + $daily_total_store_amount;

  $all_links = '';
  $all_links .= '<h3>RX</h3>';
  $all_links .= $rx_links;
  $all_links .= '<br /><hr /><br />';
  $all_links .= '<h3>PO</h3>';
  $all_links .= $po_links;
  $all_links .= '<br /><hr /><br />';
  $all_links .= '<h3>Order</h3>';
  $all_links .= $order_links;
  $all_links .= '<br /><hr /><br />';
  $all_links .= '<strong>Total Sales Amount: '.number_format($daily_total_sales_amount,2).'</strong>';

  $form['results'] = array(
    '#markup' => $all_links,
    '#prefix' => '<div id="dashboard_list_wrapper">',
    '#suffix' => '</div>',
  );


  $form['cancel'] = array(
    '#markup' => l(t('Close'), '#',
       array(
         'attributes' => array(
           'class' => 'close-btn ctools-close-modal'
          ),
         'external' => TRUE
       )
     )
  );


  return $form;
}


function dashboard_report_monthly_sales_popup($monthno='', $yearno='', $ajax='') {
  if($ajax) {
	ctools_include('ajax');
	ctools_include('modal');
    ctools_modal_add_js();
	
	$form_state = array(
	  'ajax' => TRUE,
	  'title' => t("Monthly Sales Amount - ").$yearno.'/'.$monthno,
	  'monthno' => $monthno,
	  'yearno' => $yearno,
	);

	// Use ctools to generate ajax instructions for the browser to create
	// a form in a modal popup.
	$output = ctools_modal_form_wrapper('dashboard_report_monthly_sales', $form_state);

	// If the form has been submitted, there may be additional instructions
	// such as dismissing the modal popup.
	if(!empty($form_state['ajax_commands'])) {
	  $output = $form_state['ajax_commands'];
	}

	// Return the ajax instructions to the browser via ajax_render().
	print ajax_render($output);
	drupal_exit();
  }
  else {
	return drupal_get_form('dashboard_report_monthly_sales', $monthno, $yearno);
  }
}

/**
 * Implementation of dashboard_report_monthly_sales().
 *
 *   Sales
 */
function dashboard_report_monthly_sales($form, &$form_state, $month='', $year='') {
  global $base_url, $user;

  drupal_add_css(drupal_get_path('module', 'dashboard').'/dashboard.css');
  drupal_add_js(drupal_get_path('module', 'dashboard').'/dashboard.js');
  
  ctools_include('ajax');
  ctools_include('modal');
  ctools_modal_add_js();
  
  $monthno = $month;
  if(!$monthno) {
    $monthno = $form_state['monthno'];
  }
  if(!$monthno) {
    drupal_set_message(t('Sorry! No valid month reference found.'));

	ctools_add_js('ajax-responder');
	$commands = array();
	$commands[] = ctools_modal_command_dismiss();
	$commands[] = ctools_ajax_command_redirect('dashboard/message');
	print ajax_render($commands);
	exit;
  }

  $yearno = $year;
  if(!$yearno) {
    $yearno = $form_state['yearno'];
  }
  if(!$yearno) {
    drupal_set_message(t('Sorry! No valid year reference found.'));

	ctools_add_js('ajax-responder');
	$commands = array();
	$commands[] = ctools_modal_command_dismiss();
	$commands[] = ctools_ajax_command_redirect('dashboard/message');
	print ajax_render($commands);
	exit;
  }

  //monthly : rx amount
  $sql  = "SELECT n.nid, n.title, td.field_tx_date_value as tx_date, am.field_rx_amount_value as amount, ts.field_tx_status_value as status, bt.field_rx_base_type_value as base_type, rt.field_rxtype_value as rx_type ";
  $sql .= "FROM {node} n ";
  $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
  $sql .= "INNER JOIN {field_data_field_rx_base_type} bt ON bt.entity_id = n.nid AND bt.bundle = 'rx' ";
  $sql .= "INNER JOIN {field_data_field_rxtype} rt ON rt.entity_id = n.nid AND rt.bundle = 'rx' ";
  $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid AND fc.bundle = 'rx' ";
  $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value AND td.bundle = 'field_billing_info' ";
  $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value AND am.bundle = 'field_billing_info' ";
  $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value AND ts.bundle = 'field_billing_info' ";
  $sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Invoice', 'Paid', 'Signed', 'Successful - Fee', 'Successful - PSCC', 'Manual') ";
  $sql .= "AND YEAR(td.field_tx_date_value) = '".$yearno."' ";
  $sql .= "AND MONTH(td.field_tx_date_value) = '".$monthno."' ";
  $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
  $sql .= "AND n.type = 'rx' ";
  $sql .= "AND n.status = 1 ";
  $sql .= "ORDER BY td.field_tx_date_value DESC ";
  $results = db_query($sql)->fetchAll();

  $rx_links = '<table cellspacing="0" cellpadding="5" class="rx-link-list">';
  $rx_links .= '<thead>';
  $rx_links .= '  <tr>';
  $rx_links .= '    <th>Rx</th>';
  $rx_links .= '    <th>Transaction Date</th>';
  $rx_links .= '    <th>Base Type</th>';
  $rx_links .= '    <th>Rx Type</th>';
  $rx_links .= '    <th class="amount">Amount</th>';
  $rx_links .= '  </tr>';
  $rx_links .= '</thead>';
  $rx_links .= '<tbody>';
  
  $total_amount = 0;
  
  if(count($results)>0) {
    foreach($results as $row) {
	  $rx_type = (isset($row->rx_type) ? $row->rx_type : '');
	  if($rx_type == 0) {
		$rx_type_value = t('Build-Up Phase - Six months (1-5M)');
	  }
	  else if($rx_type == 1) {
		$rx_type_value = t('Build-Up Phase with Maintenance Dose - 12 months (1-5M, Two 5M refills)');
	  }
	  else if($rx_type == 2) {
		$rx_type_value = t('Annual Maintenance Refill Dose - 12 months (Four 5M Refills)');
	  }
	  else if($rx_type == 3) {
		$rx_type_value = t('Refill kit');
	  }
	  else if($rx_type == 4) {
		$rx_type_value = t('Build-Up Phase (30 days)');
	  }
	  else if($rx_type == 5) {
		$rx_type_value = t('Build-Up Phase (90 days)');
	  }
	  else if($rx_type == 6) {
		$rx_type_value = t('Annual Maintenance Refill Dose (30 days)');
	  }
	  else if($rx_type == 7) {
		$rx_type_value = t('Annual Maintenance Refill Dose (90 days)');
	  }
	  else if($rx_type == 8) {
		$rx_type_value = t('Build-Up Phase (1st year)');
	  }
	  else if($rx_type == 9) {
		$rx_type_value = t('Maintenance Phase');
	  }
	  else {
		$rx_type_value = '-';
	  }

  	  $rx_links .= '  <tr>';
  	  $rx_links .= '    <td><a target="_blank" href="/node/'.$row->nid.'">'.$row->title.'</a></td>';
  	  $rx_links .= '    <td>'.(isset($row->tx_date) && !empty($row->tx_date) ? date('m/d/Y', strtotime($row->tx_date)) : '').'</td>';
  	  $rx_links .= '    <td>'.(isset($row->base_type) && !empty($row->base_type) ? $row->base_type : '').'</td>';
  	  $rx_links .= '    <td>'.$rx_type_value.'</td>';
  	  $rx_links .= '    <td class="amount">'.(isset($row->amount) && !empty($row->amount) ? '$ '.number_format($row->amount,2) : '').'</td>';
  	  $rx_links .= '  </tr>';
  	  
  	  $total_amount = $total_amount + floatval($row->amount);
    }
    $monthly_total_rx_amount = $total_amount;
    
  	$rx_links .= '  <tr class="summary">';
  	$rx_links .= '    <td class="summary-label" colspan="4">Total Amount: </td>';
  	$rx_links .= '    <td class="summary-value">$ '.number_format($total_amount,2).'</td>';
  	$rx_links .= '  </tr>';
  }
  else {
    $rx_links .= '  <tr>';
    $rx_links .= '    <td colspan="5">Sorry! No rx information found!</td>';
    $rx_links .= '  </tr>';
  }
  $rx_links .= '</tbody>';
  $rx_links .= '</table>';


  //monthly : po amount
  $sql  = "SELECT n.nid, n.title, pod.field_po_date_value as tx_date, CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2)) as amount ";
  $sql .= "FROM {node} n ";
  $sql .= "INNER JOIN {field_data_field_amount} pom ON pom.entity_id = n.nid ";
  $sql .= "INNER JOIN {field_data_field_po_date} pod ON pod.entity_id = n.nid ";
  $sql .= "INNER JOIN {field_data_field_po_type} pot ON pot.entity_id = n.nid ";
  $sql .= "WHERE YEAR(pod.field_po_date_value) = '".$yearno."' ";
  $sql .= "AND MONTH(pod.field_po_date_value) = '".$monthno."' ";
  $sql .= "AND n.type = 'po' ";
  $sql .= "AND n.status = 1 ";
  $sql .= "AND pot.field_po_type_value = 1 ";
  $sql .= "ORDER BY pod.field_po_date_value DESC ";
  $results = db_query($sql)->fetchAll();

  $po_links = '<table cellspacing="0" cellpadding="5" class="rx-link-list">';
  $po_links .= '<thead>';
  $po_links .= '  <tr>';
  $po_links .= '    <th>PO</th>';
  $po_links .= '    <th>Transaction Date</th>';
  $po_links .= '    <th class="amount">Amount</th>';
  $po_links .= '  </tr>';
  $po_links .= '</thead>';
  $po_links .= '<tbody>';
  
  $total_amount = 0;
  $monthly_total_po_amount = 0;
  
  if(count($results)>0) {
    foreach($results as $row) {
  	  $po_links .= '  <tr>';
  	  $po_links .= '    <td><a target="_blank" href="/node/'.$row->nid.'">'.$row->title.'</a></td>';
  	  $po_links .= '    <td>'.(isset($row->tx_date) && !empty($row->tx_date) ? date('m/d/Y', strtotime($row->tx_date)) : '').'</td>';
  	  $po_links .= '    <td class="amount">'.(isset($row->amount) && !empty($row->amount) ? '$ '.number_format($row->amount,2) : '').'</td>';
  	  $po_links .= '  </tr>';
  	  
  	  $total_amount = $total_amount + floatval($row->amount);
    }
    $monthly_total_po_amount = $total_amount;
    
  	$po_links .= '  <tr class="summary">';
  	$po_links .= '    <td class="summary-label" colspan="2">Total Amount: </td>';
  	$po_links .= '    <td class="summary-value">$ '.number_format($total_amount,2).'</td>';
  	$po_links .= '  </tr>';
  }
  else {
    $po_links .= '  <tr>';
    $po_links .= '    <td colspan="3">Sorry! No PO information found!</td>';
    $po_links .= '  </tr>';
  }
  $po_links .= '</tbody>';
  $po_links .= '</table>';


  //monthly : store amount
  $sql  = "SELECT ord.order_id, DATE_FORMAT(FROM_UNIXTIME(ord.created), '%Y-%m-%d') as tx_date, ord.uid, ot.commerce_order_total_amount / 100 as amount ";
  $sql .= "FROM {commerce_order} ord ";
  $sql .= "INNER JOIN {field_data_commerce_order_total} ot ON ot.entity_id = ord.order_id AND ot.bundle = 'commerce_order' ";
  $sql .= "WHERE ord.status IN ('completed', 'processing', 'pending', 'checkout_complete') ";
  $sql .= "AND ot.entity_type = 'commerce_order' ";
  $sql .= "AND YEAR(FROM_UNIXTIME(ord.created)) = '".$yearno."' ";
  $sql .= "AND MONTH(FROM_UNIXTIME(ord.created)) = '".$monthno."' ";
  $sql .= "ORDER BY ord.created DESC ";
  $results = db_query($sql)->fetchAll();
  
  $order_links = '<table cellspacing="0" cellpadding="5" class="rx-link-list">';
  $order_links .= '<thead>';
  $order_links .= '  <tr>';
  $order_links .= '    <th>Order</th>';
  $order_links .= '    <th>Order Date</th>';
  $order_links .= '    <th class="amount">Amount</th>';
  $order_links .= '  </tr>';
  $order_links .= '</thead>';
  $order_links .= '<tbody>';
  
  $total_amount = 0;
  $monthly_total_store_amount = 0;
  
  if(count($results)>0) {
    foreach($results as $row) {
  	  $order_links .= '  <tr>';
  	  $order_links .= '    <td><a target="_blank" href="/user/'.$row->uid.'/orders/'.$row->order_id.'">Order# '.$row->order_id.'</a></td>';
  	  $order_links .= '    <td>'.(isset($row->tx_date) && !empty($row->tx_date) ? date('m/d/Y', strtotime($row->tx_date)) : '').'</td>';
  	  $order_links .= '    <td class="amount">'.(isset($row->amount) && !empty($row->amount) ? '$ '.number_format($row->amount,2) : '').'</td>';
  	  $order_links .= '  </tr>';

  	  $total_amount = $total_amount + floatval($row->amount);
    }
    $monthly_total_store_amount = $total_amount;

  	$order_links .= '  <tr class="summary">';
  	$order_links .= '    <td class="summary-label" colspan="2">Total Amount: </td>';
  	$order_links .= '    <td class="summary-value">$ '.number_format($total_amount,2).'</td>';
  	$order_links .= '  </tr>';
  }
  else {
    $order_links .= '  <tr>';
    $order_links .= '    <td colspan="3">Sorry! No order information found!</td>';
    $order_links .= '  </tr>';
  }
  $order_links .= '</tbody>';
  $order_links .= '</table>';


  //total sales amount
  $monthly_total_sales_amount = $monthly_total_rx_amount + $monthly_total_po_amount + $monthly_total_store_amount;

  $all_links = '';
  $all_links .= '<h3>Rx</h3>';
  $all_links .= $rx_links;
  $all_links .= '<br /><hr /><br />';
  $all_links .= '<h3>PO</h3>';
  $all_links .= $po_links;
  $all_links .= '<br /><hr /><br />';
  $all_links .= '<h3>Order</h3>';
  $all_links .= $order_links;
  $all_links .= '<br /><hr /><br />';
  $all_links .= '<strong>Total Sales Amount: '.number_format($monthly_total_sales_amount,2).'</strong>';

  $form['results'] = array(
    '#markup' => $all_links,
    '#prefix' => '<div id="dashboard_list_wrapper">',
    '#suffix' => '</div>',
  );

  $form['cancel'] = array(
    '#markup' => l(t('Close'), '#',
       array(
         'attributes' => array(
           'class' => 'close-btn ctools-close-modal'
          ),
         'external' => TRUE
       )
     )
  );


  return $form;
}

?>