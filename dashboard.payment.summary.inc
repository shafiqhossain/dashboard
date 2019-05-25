<?php

function dashboard_rx_payments_summary_refresh($ajax='', $return_type=0) {
  global $base_url, $user;
  
  if($ajax) {
	ctools_include('ajax');
	//ctools_include('modal');
    //ctools_modal_add_js();
	
    //this month
    $sql  = "SELECT ts.field_tx_status_value as 'status_type', SUM(am.field_rx_amount_value) as total ";
    $sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid and fc.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value and td.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value and am.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value and ts.bundle = 'field_billing_info' ";
    $sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Successful - Fee', 'Successful - PSCC', 'Paid', 'Manual', 'Declined', 'Error', 'Refund', 'Invoice', 'Void') ";
    $sql .= "AND YEAR(td.field_tx_date_value) = '".date('Y')."' ";
    $sql .= "AND MONTH(td.field_tx_date_value) = '".date('n')."' ";
    $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $sql .= "GROUP BY ts.field_tx_status_value ";
    $results = db_query($sql)->fetchAll();

    $total_denied_payment = 0;
    $total_successful_payment = 0;
    $total_refund_payment = 0;
    $total_void_payment = 0;
    $total_error_payment = 0;
    $total_invoice_payment = 0;
  
    if(count($results)>0) {
      foreach($results as $row) {
        if($row->status_type == 'Successful' || $row->status_type == 'Successful - Fee' || $row->status_type == 'Successful - PSCC' || $row->status_type == 'Paid' || $row->status_type == 'Manual') {
  		  $total_successful_payment = $total_successful_payment + $row->total;
        }
        else if($row->status_type == 'Declined') {
  		  $total_error_payment = $total_error_payment + $row->total;
        }
        else if($row->status_type == 'Error') {
  		  $total_denied_payment = $total_denied_payment + $row->total;
        }
        else if($row->status_type == 'Refund') {
  		  $total_refund_payment = $total_refund_payment + $row->total;
        }
        else if($row->status_type == 'Void') {
  		  $total_void_payment = $total_void_payment + $row->total;
        }
      }
    }

    //invoice payment
    $sql  = "SELECT SUM(am.field_rx_amount_value) as total ";
    $sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid and fc.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value and td.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value and am.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value and ts.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_paymenttype} pt ON pt.entity_id = n.nid ";
    $sql .= "WHERE ts.field_tx_status_value IN ('Payment Pending') ";
    $sql .= "AND YEAR(td.field_tx_date_value) = '".date('Y')."' ";
    $sql .= "AND MONTH(td.field_tx_date_value) = '".date('n')."' ";
    $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $sql .= "AND pt.field_paymenttype_value = 3 ";  //Invoice
    $total_invoice_payment = db_query($sql)->fetchField();
    if(empty($total_invoice_payment)) $total_invoice_payment = 0;


    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_month} (dashboard_year, dashboard_month, total_denied_payment, total_successful_payment, total_refund_payment, total_void_payment, total_error_payment, total_invoice_payment, rx_payment_last_update) ";
    $sql_insert .= "VALUES (:dashboard_year, :dashboard_month, :total_denied_payment, :total_successful_payment, :total_refund_payment, :total_void_payment, :total_error_payment, :total_invoice_payment, :rx_payment_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_denied_payment=VALUES(total_denied_payment), total_successful_payment=VALUES(total_successful_payment), total_refund_payment=VALUES(total_refund_payment), total_void_payment=VALUES(total_void_payment), total_error_payment=VALUES(total_error_payment), total_invoice_payment=VALUES(total_invoice_payment), rx_payment_last_update='".date('Y-m-d H:i:s')."'";
    $sql_values = array(
      ':dashboard_year' => date('Y'),
      ':dashboard_month' => date('n'),
	  ':total_denied_payment' => $total_denied_payment,
	  ':total_successful_payment' => $total_successful_payment,
	  ':total_refund_payment' => $total_refund_payment,
	  ':total_void_payment' => $total_void_payment,
	  ':total_error_payment' => $total_error_payment,
	  ':total_invoice_payment' => $total_invoice_payment,
	  ':rx_payment_last_update' => date('Y-m-d H:i:s'),
    );
    db_query($sql_insert, $sql_values);


	/** sales amount **/

    //this month : rx amount
    $sql  = "SELECT SUM(am.field_rx_amount_value) as total ";
    $sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid and fc.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value and td.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value and am.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value and ts.bundle = 'field_billing_info' ";
    $sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Invoice', 'Paid', 'Signed', 'Successful - Fee', 'Successful - PSCC', 'Manual') ";
    $sql .= "AND YEAR(td.field_tx_date_value) = '".date('Y')."' ";
    $sql .= "AND MONTH(td.field_tx_date_value) = '".date('n')."' ";
    $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $monthly_total_rx_amount = db_query($sql)->fetchField();
    if(empty($monthly_total_rx_amount)) $monthly_total_rx_amount = 0;
  
    //this month : po amount
    $sql  = "SELECT SUM(CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2))) as total ";
    $sql .= "FROM {node} n ";
    $sql .= "INNER JOIN {field_data_field_amount} pom ON pom.entity_id = n.nid ";
    $sql .= "INNER JOIN {field_data_field_po_date} pod ON pod.entity_id = n.nid ";
    $sql .= "INNER JOIN {field_data_field_po_type} pot ON pot.entity_id = n.nid ";
    $sql .= "WHERE YEAR(pod.field_po_date_value) = '".date('Y')."' ";
    $sql .= "AND MONTH(pod.field_po_date_value) = '".date('n')."' ";
    $sql .= "AND n.type = 'po' ";
    $sql .= "AND n.status = 1 ";
    $sql .= "AND pot.field_po_type_value = 1 ";

    $monthly_total_po_amount = db_query($sql)->fetchField();
    if(empty($monthly_total_po_amount)) $monthly_total_po_amount = 0;

    //this month : store amount
    $sql  = "SELECT SUM(ot.commerce_order_total_amount) as total ";
    $sql .= "FROM {commerce_order} ord ";
    $sql .= "INNER JOIN {field_data_commerce_order_total} ot ON ot.entity_id = ord.order_id AND ot.bundle = 'commerce_order' ";
    $sql .= "WHERE ord.status IN ('completed', 'processing', 'pending', 'checkout_complete') ";
    $sql .= "AND ot.entity_type = 'commerce_order' ";
    $sql .= "AND YEAR(FROM_UNIXTIME(ord.created)) = '".date('Y')."' ";
    $sql .= "AND MONTH(FROM_UNIXTIME(ord.created)) = '".date('n')."' ";
    $monthly_total_order_amount = db_query($sql)->fetchField();
    if(empty($monthly_total_order_amount)) $monthly_total_order_amount = 0;

    $monthly_total_order_amount = $monthly_total_order_amount / 100;

    //total sales amount
    $monthly_total_sales_amount = $monthly_total_rx_amount + $monthly_total_po_amount + $monthly_total_order_amount;

    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_month} (dashboard_year, dashboard_month, total_sales_amount, total_sales_amount_last_update) VALUES (:dashboard_year, :dashboard_month, :total_sales_amount, :total_sales_amount_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_sales_amount=VALUES(total_sales_amount), total_sales_amount_last_update='".date('Y-m-d H:i:s')."'";
    $sql_values = array(
      ':dashboard_year' => date('Y'),
      ':dashboard_month' => date('n'),
      ':total_sales_amount' => $monthly_total_sales_amount,
      ':total_sales_amount_last_update' => date('Y-m-d H:i:s'),
    );
    db_query($sql_insert, $sql_values);


    $sql  = "SELECT n.dashboard_year, n.dashboard_month, n.total_denied_payment, n.total_successful_payment, ";
    $sql .= "n.total_refund_payment, n.total_void_payment, n.total_error_payment, n.total_invoice_payment, n.total_sales_amount ";
    $sql .= "FROM {dashboard_billing_by_month} n ";
    //$sql .= "WHERE n.dashboard_year = '".date('Y')."' ";
    $sql .= "ORDER BY n.dashboard_year DESC, n.dashboard_month DESC ";
    $sql .= "LIMIT 0,12 ";
    $results = db_query($sql)->fetchAll();

	$html  = '<table class="rx-payment-comparison-table" cellpadding="5" cellspacing="0">';
	$html .= '<thead>';
	$html .= '  <tr>';
	$html .= '	  <th rowspan="2">Month</th>';
	$html .= '    <th colspan="3">Payments Summary&nbsp;<span title="Click on the figures, will display detail report"><i class="fa fa-info-circle" aria-hidden="true">&nbsp;</i></span></th>';
	$html .= '	  <th>Total&nbsp;<span title="Total of all rx amount (except declined and refund), store and po amount. Click on the figures, will display detail report"><i class="fa fa-info-circle" aria-hidden="true">&nbsp;</i></span></th>';
	$html .= '  </tr>';
	$html .= '  <tr>';
	$html .= '	  <th>Successful</th>';
	$html .= '	  <th>Refund</th>';
	$html .= '	  <th>Invoice</th>';
	$html .= '	  <th>Sales</th>';
	$html .= '	</tr>';
	$html .= '</thead>';
	$html .= '<tbody>';

	if(count($results)>0) {
	  foreach($results as $row) {
	    if($row->dashboard_month == 1) {
		  $html .= '<tr>';
		  $html .= '<td class="month-name">Jan, '.$row->dashboard_year.'</td>';
		  $html .= '<td class="payment-successful"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/successful/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-refund"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/refund/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-invoice"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/invoice/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-sales"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/sales/monthly/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 2) {
		  $html .= '<tr>';
		  $html .= '<td class="month-name">Feb, '.$row->dashboard_year.'</td>';
		  $html .= '<td class="payment-successful"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/successful/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-refund"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/refund/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-invoice"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/invoice/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-sales"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/sales/monthly/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 3) {
		  $html .= '<tr>';
		  $html .= '<td class="month-name">Mar, '.$row->dashboard_year.'</td>';
		  $html .= '<td class="payment-successful"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/successful/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-refund"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/refund/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-invoice"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/invoice/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-sales"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/sales/monthly/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 4) {
		  $html .= '<tr>';
		  $html .= '<td class="month-name">Apr, '.$row->dashboard_year.'</td>';
		  $html .= '<td class="payment-successful"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/successful/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-refund"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/refund/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-invoice"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/invoice/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-sales"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/sales/monthly/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 5) {
		  $html .= '<tr>';
		  $html .= '<td class="month-name">May, '.$row->dashboard_year.'</td>';
		  $html .= '<td class="payment-successful"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/successful/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-refund"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/refund/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-invoice"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/invoice/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-sales"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/sales/monthly/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 6) {
		  $html .= '<tr>';
		  $html .= '<td class="month-name">Jun, '.$row->dashboard_year.'</td>';
		  $html .= '<td class="payment-successful"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/successful/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-refund"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/refund/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-invoice"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/invoice/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-sales"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/sales/monthly/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 7) {
		  $html .= '<tr>';
		  $html .= '<td class="month-name">Jul, '.$row->dashboard_year.'</td>';
		  $html .= '<td class="payment-successful"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/successful/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-refund"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/refund/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-invoice"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/invoice/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-sales"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/sales/monthly/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 8) {
		  $html .= '<tr>';
		  $html .= '<td class="month-name">Aug, '.$row->dashboard_year.'</td>';
		  $html .= '<td class="payment-successful"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/successful/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-refund"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/refund/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-invoice"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/invoice/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-sales"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/sales/monthly/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 9) {
		  $html .= '<tr>';
		  $html .= '<td class="month-name">Sep, '.$row->dashboard_year.'</td>';
		  $html .= '<td class="payment-successful"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/successful/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-refund"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/refund/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-invoice"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/invoice/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-sales"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/sales/monthly/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 10) {
		  $html .= '<tr>';
		  $html .= '<td class="month-name">Oct, '.$row->dashboard_year.'</td>';
		  $html .= '<td class="payment-successful"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/successful/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-refund"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/refund/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-invoice"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/invoice/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-sales"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/sales/monthly/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 11) {
		  $html .= '<tr>';
		  $html .= '<td class="month-name">Nov, '.$row->dashboard_year.'</td>';
		  $html .= '<td class="payment-successful"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/successful/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-refund"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/refund/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-invoice"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/invoice/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-sales"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/sales/monthly/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 12) {
		  $html .= '<tr>';
		  $html .= '<td class="month-name">Dec, '.$row->dashboard_year.'</td>';
		  $html .= '<td class="payment-successful"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/successful/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-refund"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/refund/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-invoice"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/monthly/invoice/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00).'</a></td>';
		  $html .= '<td class="payment-sales"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/sales/monthly/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">$ '.(isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00).'</a></td>';
		  $html .= '</tr>';
	    }
	  }
	}

	$html .= '</tbody>';
	$html .= '</table>';

    //Today: billing_by_date
    $today = date('Y-m-d');
    $sql1 = "SELECT * FROM {dashboard_billing_by_date} WHERE DATE_FORMAT(dashboard_date, '%Y-%m-%d')=:dashboard_date LIMIT 1 ";
    $today_billing_by_date = db_query($sql1, array(':dashboard_date' => $today))->fetchObject();
    $last_time = (isset($today_billing_by_date->rx_payment_last_update) && !empty($today_billing_by_date->rx_payment_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->rx_payment_last_update)) : '');

	$data = '<script type="text/javascript">
	  Drupal.attachBehaviors();
	</script>';

	ctools_add_js('ajax-responder');
	$output = array();

	$output[] = ajax_command_invoke('#monthly-table-payments', 'html', array($html));
	$output[] = ajax_command_invoke('#monthly-payments .dashboard-block-links .last-update-block', 'html', array($last_time));
	$output[] = ajax_command_invoke('#dashboard-refresh-wrapper', 'html', array($data));

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