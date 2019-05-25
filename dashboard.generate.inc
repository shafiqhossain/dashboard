<?php

function dashboard_update_all_popup($ajax='') {
  if($ajax) {
	ctools_include('ajax');
	ctools_include('modal');
    ctools_modal_add_js();
	
	$form_state = array(
	  'ajax' => TRUE,
	  'title' => t('Re-generate All Data'),
	);

	// Use ctools to generate ajax instructions for the browser to create
	// a form in a modal popup.
	$output = ctools_modal_form_wrapper('dashboard_update_all', $form_state);

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
	return drupal_get_form('dashboard_update_all');
  }
}

/**
 * Implementation of dashboard_update_all().
 *
 *   Re-generate all data
 */
function dashboard_update_all($form, &$form_state) {
  global $base_url, $user;

  drupal_add_css(drupal_get_path('module', 'dashboard').'/dashboard.css');
  drupal_add_js(drupal_get_path('module', 'dashboard').'/dashboard.js');
  
  ctools_include('ajax');
  ctools_include('modal');
  ctools_modal_add_js();

  $message  = '<h4>Do you want to re-generate all data for dashboard?</h4>';
  $message .= '<p>This will overwrite all current, yesterday, last 7 weeks, last 12 months data for dashboard. This action can not be undone.</p>';

  $form['results'] = array(
    '#markup' => $message,
    '#prefix' => '<div id="dashboard_list_wrapper">',
    '#suffix' => '</div>',
  );

  $form['save'] = array(
	'#type' => 'submit',
	'#value' => t('Re-generate'),
	'#ajax' => array(
		'callback' => 'dashboard_update_all_ajax_save_callback',  
		'wrapper' => 'dashboard-update-all-form-wrapper',
    	'method' => 'replace',
    	'effect' => 'fade',		
	),
	'#id' => 'dashboard_update_all_btn',
	'#name' => 'dashboard_update_all_btn',
  );
  
  $form['cancel'] = array(
    '#markup' => l(t('Close'), '#',
       array(
         'attributes' => array(
           'class' => 'close-btn btn ctools-close-modal'
          ),
         'external' => TRUE
       )
     )
  );

  $form['#prefix'] = '<div id="dashboard-update-all-form-wrapper">';
  $form['#suffix'] = '</div>';
  $form['#id'] = 'dashboard_update_all_form';

  return $form;
}

/**
 * Submit handler for save
 *
 * @see dashboard_update_all()
 */
function dashboard_update_all_ajax_save_callback($form, &$form_state) {
  global $base_url, $user;
  
  ctools_include('ajax');
  ctools_include('modal');
  ctools_modal_add_js();

  if (!empty($form_state['executed'])) {
    /** Generate data for today, this week and this month **/
	
	//dummayr item
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 1;

	//Billing: Total amount collected today/week/month
	dashboard_total_amount_collected_worker($item);

	//Billing: Total SLIT/SCIT created today/week/month
	dashboard_total_rx_created_worker($item);

	//Billing: Total SCIT/SLIT Refills today/week/month
	dashboard_total_rx_refills_worker($item);

	//Billing: Denied payments, Successful payments, Refund payments, Void payments, Error payments, Invoice payments
	dashboard_total_rx_payments_worker($item);

	//Rx: RX’s with pending payment that is more than 3 days past the schedule date
	dashboard_total_rx_pendings_worker($item);

	//Rx: RX’s scheduled for shipment but has payment issues
	dashboard_total_rx_scheduled_worker($item);

	//Rx: Upcoming RX for shipment refills
	//Show RXs that are 10 days from scheduled date in shipping record
	dashboard_total_rx_shipment_refills_worker($item);

	//Rx: Expiring RX
	dashboard_total_rx_expiring_worker($item);

	//Rx: Expiring ARB
	dashboard_total_expiring_arb_worker($item);

	//Rx: Expiring Credit Cards
	dashboard_total_expiring_cc_worker($item);

	//Payment Profile: Expiring Credit Cards
	dashboard_total_profile_expiring_cc_worker($item);

	//Billing: Total order amount collected today/week/month
	dashboard_order_amount_collected_worker($item);

	//Order: Total order submitted
	dashboard_total_order_submitted_worker($item);

	//Billing: Total po amount collected today/week/month
	dashboard_po_amount_collected_worker($item);

	//Billing: Total po created
	dashboard_total_po_created_worker($item);

	//Clinics: Total count
	dashboard_total_clinics_worker($item);

	//Silent Post: Summary
	dashboard_silent_post_summary_worker($item);
	
	//total sales amount
	dashboard_total_sales_amount_worker($item);

    //Rx created by clinic and staff
    dashboard_clinic_vs_staff_rx_created_worker($item);

    //Refills created by clinic and staff
    dashboard_clinic_vs_staff_rx_refills_worker($item);
	
	//reference time for all
    $time = date('Y-m-d H:i:s');

    //rx amount: past 7 weeks
    for($i=1; $i<=7; $i++) {
	  $past_week = date('W', strtotime("-".$i." week"));
	  $past_week_month = date('n', strtotime("-".$i." week"));
	  $past_week_year = date('Y', strtotime("-".$i." week"));
	  $rx_total_week = 0;
	  
      $sql  = "SELECT SUM(am.field_rx_amount_value) as total ";
      $sql .= "FROM {node} n ";
  	  $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid and fc.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value and td.bundle = 'field_billing_info' ";
      $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value and am.bundle = 'field_billing_info' ";
      $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value and ts.bundle = 'field_billing_info' ";
      $sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Successful - Fee', 'Successful - PSCC', 'Paid') ";
      $sql .= "AND YEAR(td.field_tx_date_value) = '".$past_week_year."' ";
      $sql .= "AND WEEKOFYEAR(td.field_tx_date_value) = '".$past_week."' ";
      $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
      $sql .= "AND n.type = 'rx' ";
      $sql .= "AND n.status = 1 ";
      $rx_total_week = db_query($sql)->fetchField();
      if(empty($rx_total_week)) $rx_total_week = 0;
    

      //update the table
      $sql_insert = "INSERT INTO {dashboard_billing_by_week} (dashboard_year, dashboard_month, dashboard_week, total_collected_amount, collected_amount_last_update) VALUES (:dashboard_year, :dashboard_month, :dashboard_week, :total_collected_amount, :collected_amount_last_update) ";
      $sql_insert .= "ON DUPLICATE KEY UPDATE total_collected_amount=VALUES(total_collected_amount), collected_amount_last_update='".$time."'";
      $sql_values = array(
        ':dashboard_year' => $past_week_year,
        ':dashboard_month' => $past_week_month,
        ':dashboard_week' => $past_week,
        ':total_collected_amount' => $rx_total_week,
        ':collected_amount_last_update' => $time,
      );
      db_query($sql_insert, $sql_values);
    }
    watchdog('dashboard', 'Re-generate rx amount for last 7 weeks, completed.');

    //rx amount: past 12 months
    for($i=1; $i<=12; $i++) {
	  $past_month = date('n', strtotime("-".$i." month"));
	  $past_month_year = date('Y', strtotime("-".$i." month"));
	  $rx_total_month = 0;

      $sql  = "SELECT SUM(am.field_rx_amount_value) as total ";
      $sql .= "FROM {node} n ";
  	  $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid and fc.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value and td.bundle = 'field_billing_info' ";
      $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value and am.bundle = 'field_billing_info' ";
      $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value and ts.bundle = 'field_billing_info' ";
      $sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Successful - Fee', 'Successful - PSCC', 'Paid') ";
      $sql .= "AND YEAR(td.field_tx_date_value) = '".$past_month_year."' ";
      $sql .= "AND MONTH(td.field_tx_date_value) = '".$past_month."' ";
      $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
      $sql .= "AND n.type = 'rx' ";
      $sql .= "AND n.status = 1 ";
      $rx_total_month = db_query($sql)->fetchField();
      if(empty($rx_total_month)) $rx_total_month = 0;

      //update the table
      $sql_insert = "INSERT INTO {dashboard_billing_by_month} (dashboard_year, dashboard_month, total_collected_amount, collected_amount_last_update) VALUES (:dashboard_year, :dashboard_month, :total_collected_amount, :collected_amount_last_update) ";
      $sql_insert .= "ON DUPLICATE KEY UPDATE total_collected_amount=VALUES(total_collected_amount), collected_amount_last_update='".$time."'";
      $sql_values = array(
        ':dashboard_year' => $past_month_year,
        ':dashboard_month' => $past_month,
        ':total_collected_amount' => $rx_total_month,
        ':collected_amount_last_update' => $time,
      );
      db_query($sql_insert, $sql_values);
    }
    watchdog('dashboard', 'Re-generate rx amount for last 12 months, completed.');


    //order amount: past 7 weeks
    for($i=1; $i<=7; $i++) {
	  $past_week = date('W', strtotime("-".$i." week"));
	  $past_week_month = date('n', strtotime("-".$i." week"));
	  $past_week_year = date('Y', strtotime("-".$i." week"));
	  $order_total_week = 0;

      $sql  = "SELECT SUM(ot.commerce_order_total_amount) as total ";
      $sql .= "FROM {commerce_order} ord ";
      $sql .= "INNER JOIN {field_data_commerce_order_total} ot ON ot.entity_id = ord.order_id AND ot.bundle = 'commerce_order' ";
      $sql .= "WHERE ord.status IN ('completed', 'processing', 'pending', 'checkout_complete') ";
      $sql .= "AND ot.entity_type = 'commerce_order' ";
      $sql .= "AND YEAR(FROM_UNIXTIME(ord.created)) = '".$past_week_year."' ";
      $sql .= "AND WEEKOFYEAR(FROM_UNIXTIME(ord.created)) = '".$past_week."' ";
      $order_total_week = db_query($sql)->fetchField();
      if(empty($order_total_week)) $order_total_week = 0;

      //update the table
      $sql_insert = "INSERT INTO {dashboard_billing_by_week} (dashboard_year, dashboard_month, dashboard_week, total_order_amount, order_amount_last_update) VALUES (:dashboard_year, :dashboard_month, :dashboard_week, :total_order_amount, :order_amount_last_update) ";
      $sql_insert .= "ON DUPLICATE KEY UPDATE total_order_amount=VALUES(total_order_amount), order_amount_last_update='".$time."'";
      $sql_values = array(
        ':dashboard_year' => $past_week_year,
        ':dashboard_month' => $past_week_month,
        ':dashboard_week' => $past_week,
        ':total_order_amount' => $order_total_week / 100,
        ':order_amount_last_update' => $time,
      );
      db_query($sql_insert, $sql_values);
    }
    watchdog('dashboard', 'Re-generate order amount for last 7 weeks, completed.');


    //order amount: past 12 months
    for($i=1; $i<=12; $i++) {
	  $past_month = date('n', strtotime("-".$i." month"));
	  $past_month_year = date('Y', strtotime("-".$i." month"));
	  $order_total_month = 0;

      $sql  = "SELECT SUM(ot.commerce_order_total_amount) as total ";
      $sql .= "FROM {commerce_order} ord ";
      $sql .= "INNER JOIN {field_data_commerce_order_total} ot ON ot.entity_id = ord.order_id AND ot.bundle = 'commerce_order' ";
      $sql .= "WHERE ord.status IN ('completed', 'processing', 'pending', 'checkout_complete') ";
      $sql .= "AND ot.entity_type = 'commerce_order' ";
      $sql .= "AND YEAR(FROM_UNIXTIME(ord.created)) = '".$past_month_year."' ";
      $sql .= "AND MONTH(FROM_UNIXTIME(ord.created)) = '".$past_month."' ";
      $order_total_month = db_query($sql)->fetchField();
      if(empty($order_total_month)) $order_total_month = 0;

      //update the table
      $sql_insert = "INSERT INTO {dashboard_billing_by_month} (dashboard_year, dashboard_month, total_order_amount, order_amount_last_update) VALUES (:dashboard_year, :dashboard_month, :total_order_amount, :order_amount_last_update) ";
      $sql_insert .= "ON DUPLICATE KEY UPDATE total_order_amount=VALUES(total_order_amount), order_amount_last_update='".$time."'";
      $sql_values = array(
        ':dashboard_year' => $past_month_year,
        ':dashboard_month' => $past_month,
        ':total_order_amount' => $order_total_month / 100,
        ':order_amount_last_update' => $time,
      );
      db_query($sql_insert, $sql_values);
	}
    watchdog('dashboard', 'Re-generate order amount for last 12 months, completed.');


    //order amount: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_yesterday = 0;
    
    $sql  = "SELECT SUM(ot.commerce_order_total_amount) as total ";
    $sql .= "FROM {commerce_order} ord ";
    $sql .= "INNER JOIN {field_data_commerce_order_total} ot ON ot.entity_id = ord.order_id AND ot.bundle = 'commerce_order' ";
    $sql .= "WHERE ord.status IN ('completed', 'processing', 'pending', 'checkout_complete') ";
    $sql .= "AND ot.entity_type = 'commerce_order' ";
    $sql .= "AND DATE_FORMAT(FROM_UNIXTIME(ord.created), '%Y-%m-%d') = '".$yesterday."' ";
    $total_yesterday = db_query($sql)->fetchField();
    if(empty($total_yesterday)) $total_yesterday = 0;
    

    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_date} (dashboard_date, total_order_amount, order_amount_last_update) VALUES (:dashboard_date,:total_order_amount,:order_amount_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_order_amount=VALUES(total_order_amount), order_amount_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':total_order_amount' => $total_yesterday / 100,
      ':order_amount_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate order amount for yesterday, completed.');


    //rx amount: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_yesterday = 0;
    
    $sql  = "SELECT SUM(am.field_rx_amount_value) as total ";
    $sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid and fc.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value and td.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value and am.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value and ts.bundle = 'field_billing_info' ";
    $sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Successful - Fee', 'Successful - PSCC', 'Paid') ";
    $sql .= "AND DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = '".$yesterday."' ";
    $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $total_yesterday = db_query($sql)->fetchField();
    if(empty($total_yesterday)) $total_yesterday = 0;

    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_date} (dashboard_date, total_collected_amount, collected_amount_last_update) VALUES (:dashboard_date,:total_collected_amount,:collected_amount_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_collected_amount=VALUES(total_collected_amount), collected_amount_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':total_collected_amount' => $total_yesterday,
      ':collected_amount_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate rx collected amount for yesterday, completed.');


	//total clinics
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_clinics_yesterday = 0;

    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'clinic')
      ->propertyCondition('status', NODE_PUBLISHED);

    $result = $query->execute();
    $items_nids = array();
    if (isset($result['node'])) {
      $items_nids = array_keys($result['node']);
    }
    $total_clinics_yesterday = count($items_nids);
    if(empty($total_clinics_yesterday)) $total_clinics_yesterday = 0;
	
    //update the table
    $sql_insert = "INSERT INTO {dashboard_count_by_date} (dashboard_date, total_clinics, total_clinics_last_update) VALUES (:dashboard_date,:total_clinics, :total_clinics_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_clinics=VALUES(total_clinics), total_clinics_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':total_clinics' => $total_clinics_yesterday,
      ':total_clinics_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate total clinics, completed.');


    //rx created: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_scit_created_yesterday = 0;
    $total_slit_created_yesterday = 0;

    $sql  = "SELECT bt.field_rx_base_type_value as 'base_type', COUNT(*) as total ";
    $sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_order_date} od ON od.entity_id = n.nid and od.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_rx_base_type} bt ON bt.entity_id = n.nid and bt.bundle = 'rx' ";
    $sql .= "WHERE DATE_FORMAT(od.field_order_date_value, '%Y-%m-%d') = '".$yesterday."' ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
    $sql .= "GROUP BY bt.field_rx_base_type_value ";
    $results = db_query($sql)->fetchAll();

    if(count($results)>0) {
      foreach($results as $row) {
        if($row->base_type == 'SCIT') {
  		  $total_scit_created_yesterday = $row->total;
        }
        else {
  		  $total_slit_created_yesterday = $row->total;
        }
      }
    }
	
    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_date} (dashboard_date, total_scit_created, total_slit_created, rx_create_last_update) VALUES (:dashboard_date, :total_scit_created, :total_slit_created, :rx_create_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_scit_created=VALUES(total_scit_created), total_slit_created=VALUES(total_slit_created), rx_create_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':total_scit_created' => $total_scit_created_yesterday,
      ':total_slit_created' => $total_slit_created_yesterday,
      ':rx_create_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate rx created for yesterday, completed.');


    //expiring arb subscription: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $date_reference = date('Y-m-d', strtotime('+29 days'));
    $total_subscriptions_yesterday = 0;
    $total_subscriptions_ids = '';

    $sql  = "SELECT n.subscription_id, n.subscription_end_date ";
    $sql .= "FROM {authorizenet_profiles} n ";
    $sql .= "WHERE n.subscription_end_date BETWEEN '".$yesterday."' AND '".$date_reference."' ";
    $sql .= "AND n.profile_status = 1 ";
    $results = db_query($sql)->fetchAll();

    if(count($results)>0) {
      foreach($results as $row) {
  	    ++$total_subscriptions_yesterday;
  	    if(!empty($total_subscriptions_ids)) $total_subscriptions_ids .= ',';
  	    $total_subscriptions_ids .= $row->subscription_id.'|'.$row->subscription_end_date;
      }
    }
    
    //update the table
    $sql_insert = "INSERT INTO {dashboard_count_by_date} (dashboard_date, arb_expiring, arb_expiring_ids, arb_expiring_last_update) VALUES (:dashboard_date, :arb_expiring, :arb_expiring_ids, :arb_expiring_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE arb_expiring=VALUES(arb_expiring), arb_expiring_ids=VALUES(arb_expiring_ids), arb_expiring_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':arb_expiring' => $total_subscriptions_yesterday,
      ':arb_expiring_ids' => $total_subscriptions_ids,
      ':arb_expiring_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate expiring arb for yesterday, completed.');


    //expiring credit cards: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $date_reference = date('Y-m-d', strtotime('+29 days'));
    $total_subscriptions_yesterday = 0;
    $total_subscriptions_ids = '';

    $sql  = "SELECT n.subscription_id, n.cc_notification_date ";
    $sql .= "FROM {authorizenet_profiles} n ";
    $sql .= "WHERE n.cc_notification_date BETWEEN '".$yesterday."' AND '".$date_reference."' ";
    $sql .= "AND n.profile_status = 1 ";
    $results = db_query($sql)->fetchAll();

    if(count($results)>0) {
      foreach($results as $row) {
  	    ++$total_subscriptions_yesterday;
  	    if(!empty($total_subscriptions_ids)) $total_subscriptions_ids .= ',';
  	    $total_subscriptions_ids .= $row->subscription_id.'|'.$row->cc_notification_date;
      }
    }
    
    //update the table
    $sql_insert = "INSERT INTO {dashboard_count_by_date} (dashboard_date, cc_expiring, cc_expiring_ids, cc_expiring_last_update) VALUES (:dashboard_date, :cc_expiring, :cc_expiring_ids, :cc_expiring_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE cc_expiring=VALUES(cc_expiring), cc_expiring_ids=VALUES(cc_expiring_ids), cc_expiring_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':cc_expiring' => $total_subscriptions_yesterday,
      ':cc_expiring_ids' => $total_subscriptions_ids,
      ':cc_expiring_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate expiring credit cards for yesterday, completed.');


    //expiring profile credit cards: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $date_reference = date('Y-m-d', strtotime('+29 days'));
    $total_profiles_yesterday = 0;
    $total_profiles_ids = '';

    $sql  = "SELECT n.customer_profile_id, n.customer_payment_profile_id, n.cc_notification_date ";
    $sql .= "FROM {authorizenet_customer_payment_profiles} n ";
    $sql .= "WHERE n.cc_notification_date BETWEEN '".$yesterday."' AND '".$date_reference."' ";
    $sql .= "AND n.profile_status = 1 ";
    $results = db_query($sql)->fetchAll();

  
    if(count($results)>0) {
      foreach($results as $row) {
  	    ++$total_profiles_yesterday;
  	    if(!empty($total_profiles_ids)) $total_profiles_ids .= ',';
  	    $total_profiles_ids .= $row->customer_profile_id.'|'.$row->customer_payment_profile_id.'|'.$row->cc_notification_date;
      }
    }
    
    //update the table
    $sql_insert = "INSERT INTO {dashboard_count_by_date} (dashboard_date, profile_cc_expiring, profile_cc_expiring_ids, profile_cc_last_update) VALUES (:dashboard_date, :profile_cc_expiring, :profile_cc_expiring_ids, :profile_cc_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE profile_cc_expiring=VALUES(profile_cc_expiring), profile_cc_expiring_ids=VALUES(profile_cc_expiring_ids), profile_cc_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':profile_cc_expiring' => $total_profiles_yesterday,
      ':profile_cc_expiring_ids' => $total_profiles_ids,
      ':profile_cc_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate expiring profile credit cards for yesterday, completed.');


    //expiring rx: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $date_reference = date('Y-m-d', strtotime('+9 days'));
    $total_rx_yesterday = 0;
    $total_rx_nids = array();
    $total_rx_arr = array();

    $sql  = "SELECT n.nid, ed.field_end_date_value as end_date, ra.field_rx_amount_value as amount ";
    $sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_end_date} ed ON ed.entity_id = n.nid AND ed.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_rx_amount} ra ON ra.entity_id = n.nid AND ed.bundle = 'rx' ";
    $sql .= "WHERE ed.field_end_date_value BETWEEN '".$yesterday."' AND '".$date_reference."' ";
    $sql .= "AND ws.field_workflow_status_value NOT IN ('Cancelled', 'Completed') ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $results = db_query($sql)->fetchAll();

    if(count($results)>0) {
      foreach($results as $row) {
  	    $total_rx_nids[$row->nid] = $row->nid.'|'.$row->amount;
  	    $total_rx_arr[$row->nid] = $row->nid;
      }
    }
	
	//count the rx
    $total_rx_yesterday = count($total_rx_arr);
    $total_rx_nids = implode(',', $total_rx_nids);
    
    //update the table
    $sql_insert = "INSERT INTO {dashboard_count_by_date} (dashboard_date, rx_expiring, rx_expiring_nids, rx_expiring_last_update) VALUES (:dashboard_date, :rx_expiring, :rx_expiring_nids, :rx_expiring_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE rx_expiring=VALUES(rx_expiring), rx_expiring_nids=VALUES(rx_expiring_nids), rx_expiring_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':rx_expiring' => $total_rx_yesterday,
      ':rx_expiring_nids' => $total_rx_nids,
      ':rx_expiring_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate expiring rx for yesterday, completed.');


    //payments: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_denied_payment_yesterday = 0;
    $total_successful_payment_yesterday = 0;
    $total_refund_payment_yesterday = 0;
    $total_void_payment_yesterday = 0;
    $total_error_payment_yesterday = 0;
    $total_invoice_payment_yesterday = 0;

    $sql  = "SELECT ts.field_tx_status_value as 'status_type', SUM(am.field_rx_amount_value) as total ";
    $sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid and fc.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value and td.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value and am.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value and ts.bundle = 'field_billing_info' ";
    $sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Successful - Fee', 'Successful - PSCC', 'Paid', 'Manual', 'Declined', 'Error', 'Refund', 'Invoice', 'Void') ";
    $sql .= "AND DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = '".$yesterday."' ";
    $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $sql .= "GROUP BY ts.field_tx_status_value ";
    $results = db_query($sql)->fetchAll();
  
    if(count($results)>0) {
      foreach($results as $row) {
        if($row->status_type == 'Successful' || $row->status_type == 'Successful - Fee' || $row->status_type == 'Successful - PSCC' || $row->status_type == 'Paid' || $row->status_type == 'Manual') {
  		  $total_successful_payment_yesterday = $total_successful_payment_yesterday + $row->total;
        }
        else if($row->status_type == 'Declined') {
  		  $total_error_payment_yesterday = $total_error_payment_yesterday + $row->total;
        }
        else if($row->status_type == 'Error') {
  		  $total_denied_payment_yesterday = $total_denied_payment_yesterday + $row->total;
        }
        else if($row->status_type == 'Refund') {
  		  $total_refund_payment_yesterday = $total_refund_payment_yesterday + $row->total;
        }
        else if($row->status_type == 'Void') {
  		  $total_void_payment_yesterday = $total_void_payment_yesterday + $row->total;
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
    $sql .= "AND DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = '".$yesterday."' ";
    $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $sql .= "AND pt.field_paymenttype_value = 3 ";  //Invoice
    $total_invoice_payment_yesterday = db_query($sql)->fetchField();
    if(empty($total_invoice_payment_yesterday)) $total_invoice_payment_yesterday = 0;

    
    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_date} (dashboard_date, total_denied_payment, total_successful_payment, total_refund_payment, total_void_payment, total_error_payment, total_invoice_payment, rx_payment_last_update) ";
    $sql_insert .= "VALUES (:dashboard_date, :total_denied_payment, :total_successful_payment, :total_refund_payment, :total_void_payment, :total_error_payment, :total_invoice_payment, :rx_payment_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_denied_payment=VALUES(total_denied_payment), total_successful_payment=VALUES(total_successful_payment), total_refund_payment=VALUES(total_refund_payment), total_void_payment=VALUES(total_void_payment), total_error_payment=VALUES(total_error_payment), total_invoice_payment=VALUES(total_invoice_payment), rx_payment_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
	  ':total_denied_payment' => $total_denied_payment_yesterday,
	  ':total_successful_payment' => $total_successful_payment_yesterday,
	  ':total_refund_payment' => $total_refund_payment_yesterday,
	  ':total_void_payment' => $total_void_payment_yesterday,
	  ':total_error_payment' => $total_error_payment_yesterday,
	  ':total_invoice_payment' => $total_invoice_payment_yesterday,
	  ':rx_payment_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate payment summary for yesterday, completed.');


    //pending rx: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $date_reference = date('Y-m-d', strtotime('-4 days'));
    $total_rx_yesterday = 0;
    $total_rx_nids = array();
    $total_rx_arr = array();

    $sql  = "SELECT n.nid, am.field_rx_amount_value as amount ";
    $sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_subscription_no} sub ON sub.entity_id = n.nid AND sub.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid AND fc.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value AND td.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value AND am.bundle = 'field_billing_info' ";
    $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value AND ts.bundle = 'field_billing_info' ";
    $sql .= "WHERE ts.field_tx_status_value = 'Payment Pending' ";
    $sql .= "AND td.field_tx_date_value BETWEEN '".$yesterday."' AND '".$date_reference."' ";
    $sql .= "AND ws.field_workflow_status_value NOT IN ('Cancelled', 'Completed') ";
    $sql .= "AND sub.field_subscription_no_value != '' ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $results = db_query($sql)->fetchAll();

    if(count($results)>0) {
      foreach($results as $row) {
  	    $total_rx_nids[$row->nid] = $row->nid.'|'.$row->amount;
    	$total_rx_arr[$row->nid] = $row->nid;
      }
    }
	
	//count
	$total_rx_yesterday = count($total_rx_arr);
	$total_rx_nids = implode(',', $total_rx_nids);
    
    //update the table
    $sql_insert = "INSERT INTO {dashboard_count_by_date} (dashboard_date, rx_pending, rx_pending_nids, rx_pending_last_update) VALUES (:dashboard_date,:rx_pending, :rx_pending_nids, :rx_pending_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE rx_pending=VALUES(rx_pending), rx_pending_nids=VALUES(rx_pending_nids), rx_pending_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':rx_pending' => $total_rx_yesterday,
      ':rx_pending_nids' => $total_rx_nids,
      ':rx_pending_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate pending rx for yesterday, completed.');


    //refills: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_scit_refill_yesterday = 0;
    $total_slit_refill_yesterday = 0;

    $sql  = "SELECT bt.field_rx_base_type_value as 'base_type', COUNT(*) as total ";
    $sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_order_date} od ON od.entity_id = n.nid and od.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_rx_base_type} bt ON bt.entity_id = n.nid and bt.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_refill_rx_id} rrx ON rrx.entity_id = n.nid and rrx.bundle = 'rx' ";
    $sql .= "WHERE DATE_FORMAT(od.field_order_date_value, '%Y-%m-%d') = '".$yesterday."' ";
    $sql .= "AND ws.field_workflow_status_value NOT IN ('Cancelled', 'Completed') ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $sql .= "GROUP BY bt.field_rx_base_type_value ";
    $results = db_query($sql)->fetchAll();

    if(count($results)>0) {
      foreach($results as $row) {
        if($row->base_type == 'SCIT') {
  		  $total_scit_refill_yesterday = $row->total;
        }
        else {
  		  $total_slit_refill_yesterday = $row->total;
        }
      }
    }
    
    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_date} (dashboard_date, total_slit_refill, total_scit_refill, rx_refill_last_update) VALUES (:dashboard_date, :total_slit_refill, :total_scit_refill, :rx_refill_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_scit_refill=VALUES(total_scit_refill), total_slit_refill=VALUES(total_slit_refill), rx_refill_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':total_scit_refill' => $total_scit_refill_yesterday,
      ':total_slit_refill' => $total_slit_refill_yesterday,
      ':rx_refill_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate rx refills for yesterday, completed.');


    //rx created: last 12 months
    for($i=1;$i<=12;$i++) {
	  $past_month = date('n', strtotime("-".$i." month"));
	  $past_month_year = date('Y', strtotime("-".$i." month"));
      $total_scit_created = 0;
      $total_slit_created = 0;

      $sql  = "SELECT bt.field_rx_base_type_value as 'base_type', COUNT(*) as total ";
      $sql .= "FROM {node} n ";
  	  $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_order_date} od ON od.entity_id = n.nid and od.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_rx_base_type} bt ON bt.entity_id = n.nid and bt.bundle = 'rx' ";
      $sql .= "WHERE YEAR(od.field_order_date_value) = '".$past_month_year."' ";
      $sql .= "AND MONTH(od.field_order_date_value) = '".$past_month."' ";
      $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
      $sql .= "AND n.type = 'rx' ";
      $sql .= "AND n.status = 1 ";
      $sql .= "GROUP BY bt.field_rx_base_type_value ";
      $results = db_query($sql)->fetchAll();

      if(count($results)>0) {
        foreach($results as $row) {
          if($row->base_type == 'SCIT') {
  		    $total_scit_created = $row->total;
          }
          else {
  		    $total_slit_created = $row->total;
          }
        }
      }
  
      //update the table
      $sql_insert = "INSERT INTO {dashboard_billing_by_month} (dashboard_year, dashboard_month, total_scit_created, total_slit_created, rx_create_last_update) VALUES (:dashboard_year, :dashboard_month, :total_scit_created, :total_slit_created, :rx_create_last_update) ";
      $sql_insert .= "ON DUPLICATE KEY UPDATE total_scit_created=VALUES(total_scit_created), total_slit_created=VALUES(total_slit_created), rx_create_last_update='".$time."'";
      $sql_values = array(
        ':dashboard_year' => $past_month_year,
        ':dashboard_month' => $past_month,
        ':total_scit_created' => $total_scit_created,
        ':total_slit_created' => $total_slit_created,
        ':rx_create_last_update' => $time,
      );
      db_query($sql_insert, $sql_values);
    }
    watchdog('dashboard', 'Re-generate rx created for last 12 months, completed.');

	
    //rx refills: last 12 months
    for($i=1;$i<=12;$i++) {
	  $past_month = date('n', strtotime("-".$i." month"));
	  $past_month_year = date('Y', strtotime("-".$i." month"));
      $total_scit_refill = 0;
      $total_slit_refill = 0;

      $sql  = "SELECT bt.field_rx_base_type_value as 'base_type', COUNT(*) as total ";
      $sql .= "FROM {node} n ";
  	  $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_order_date} od ON od.entity_id = n.nid and od.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_rx_base_type} bt ON bt.entity_id = n.nid and bt.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_refill_rx_id} rrx ON rrx.entity_id = n.nid and rrx.bundle = 'rx' ";
      $sql .= "WHERE YEAR(od.field_order_date_value) = '".$past_month_year."' ";
      $sql .= "AND MONTH(od.field_order_date_value) = '".$past_month."' ";
      $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
      $sql .= "AND n.type = 'rx' ";
      $sql .= "AND n.status = 1 ";
      $sql .= "GROUP BY bt.field_rx_base_type_value ";
      $results = db_query($sql)->fetchAll();

      if(count($results)>0) {
        foreach($results as $row) {
          if($row->base_type == 'SCIT') {
  		    $total_scit_refill = $row->total;
          }
          else {
  		    $total_slit_refill = $row->total;
          }
        }
      }
  
      //update the table
      $sql_insert = "INSERT INTO {dashboard_billing_by_month} (dashboard_year, dashboard_month, total_scit_refill, total_slit_refill, rx_refill_last_update) VALUES (:dashboard_year, :dashboard_month, :total_scit_refill, :total_slit_refill, :rx_refill_last_update) ";
      $sql_insert .= "ON DUPLICATE KEY UPDATE total_scit_refill=VALUES(total_scit_refill), total_slit_refill=VALUES(total_slit_refill), rx_refill_last_update='".$time."'";
      $sql_values = array(
        ':dashboard_year' => $past_month_year,
        ':dashboard_month' => $past_month,
        ':total_scit_refill' => $total_scit_refill,
        ':total_slit_refill' => $total_slit_refill,
        ':rx_refill_last_update' => $time,
      );
      db_query($sql_insert, $sql_values);
    }
    watchdog('dashboard', 'Re-generate upcoming rx refills for last 12 months, completed.');


    //payment summary: last 12 months
    for($i=1;$i<=12;$i++) {
	  $past_month = date('n', strtotime("-".$i." month"));
	  $past_month_year = date('Y', strtotime("-".$i." month"));

      $total_denied_payment = 0;
      $total_successful_payment = 0;
      $total_refund_payment = 0;
      $total_void_payment = 0;
      $total_error_payment = 0;
      $total_invoice_payment = 0;

      $sql  = "SELECT ts.field_tx_status_value as 'status_type', SUM(am.field_rx_amount_value) as total ";
      $sql .= "FROM {node} n ";
  	  $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid and fc.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value and td.bundle = 'field_billing_info' ";
      $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value and am.bundle = 'field_billing_info' ";
      $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value and ts.bundle = 'field_billing_info' ";
      $sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Successful - Fee', 'Successful - PSCC', 'Paid', 'Manual', 'Declined', 'Error', 'Refund', 'Invoice', 'Void') ";
      $sql .= "AND YEAR(td.field_tx_date_value) = '".$past_month_year."' ";
      $sql .= "AND MONTH(td.field_tx_date_value) = '".$past_month."' ";
      $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
      $sql .= "AND n.type = 'rx' ";
      $sql .= "AND n.status = 1 ";
      $sql .= "GROUP BY ts.field_tx_status_value ";
      $results = db_query($sql)->fetchAll();

  
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
	  $sql .= "AND YEAR(td.field_tx_date_value) = '".$past_month_year."' ";
	  $sql .= "AND MONTH(td.field_tx_date_value) = '".$past_month."' ";
	  $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
	  $sql .= "AND n.type = 'rx' ";
	  $sql .= "AND n.status = 1 ";
	  $sql .= "AND pt.field_paymenttype_value = 3 ";  //Invoice
	  $total_invoice_payment = db_query($sql)->fetchField();
	  if(empty($total_invoice_payment)) $total_invoice_payment = 0;


      //update the table
      $sql_insert = "INSERT INTO {dashboard_billing_by_month} (dashboard_year, dashboard_month, total_denied_payment, total_successful_payment, total_refund_payment, total_void_payment, total_error_payment, total_invoice_payment, rx_payment_last_update) ";
      $sql_insert .= "VALUES (:dashboard_year, :dashboard_month, :total_denied_payment, :total_successful_payment, :total_refund_payment, :total_void_payment, :total_error_payment, :total_invoice_payment, :rx_payment_last_update) ";
      $sql_insert .= "ON DUPLICATE KEY UPDATE total_denied_payment=VALUES(total_denied_payment), total_successful_payment=VALUES(total_successful_payment), total_refund_payment=VALUES(total_refund_payment), total_void_payment=VALUES(total_void_payment), total_error_payment=VALUES(total_error_payment), total_invoice_payment=VALUES(total_invoice_payment), rx_payment_last_update='".$time."'";
      $sql_values = array(
        ':dashboard_year' => $past_month_year,
        ':dashboard_month' => $past_month,
	    ':total_denied_payment' => $total_denied_payment,
	    ':total_successful_payment' => $total_successful_payment,
	    ':total_refund_payment' => $total_refund_payment,
	    ':total_void_payment' => $total_void_payment,
	    ':total_error_payment' => $total_error_payment,
	    ':total_invoice_payment' => $total_invoice_payment,
	    ':rx_payment_last_update' => $time,
      );
      db_query($sql_insert, $sql_values);
    }
    watchdog('dashboard', 'Re-generate payment summary for last 12 months, completed.');


    //rx scheduled: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $date_reference = date('Y-m-d', strtotime('+2 days'));
    $total_rx_yesterday = 0;
    $total_rx_nids = array();
    $total_rx_arr = array();

    $sql  = "SELECT n.nid, sn.field_sequence_number_value as sequence ";
    $sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_shipping_info} fc ON fc.entity_id = n.nid AND fc.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_scheduled_date} sd ON sd.entity_id = fc.field_shipping_info_value AND sd.bundle = 'field_shipping_info' ";
    $sql .= "INNER JOIN {field_data_field_shipping_status} ss ON ss.entity_id = fc.field_shipping_info_value AND ss.bundle = 'field_shipping_info' ";
    $sql .= "INNER JOIN {field_data_field_sequence_number} sn ON sn.entity_id = fc.field_shipping_info_value AND sn.bundle = 'field_shipping_info' ";
    $sql .= "WHERE ss.field_shipping_status_value = 'Scheduled' ";
    $sql .= "AND sd.field_scheduled_date_value BETWEEN '".$yesterday."' AND '".$date_reference."' ";
    $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $results = db_query($sql)->fetchAll();
  
    if(count($results)>0) {
      foreach($results as $row) {
	    $bill_amount = 0;
	    $node = node_load($row->nid, NULL, TRUE);
	    $rx_amount = (isset($node->field_rx_amount['und'][0]['value']) ? floatval($node->field_rx_amount['und'][0]['value']) : 0);
	    $sequence = (isset($row->sequence) && !empty($row->sequence) ? $row->sequence : 1);

	    $bills = isset($node->field_billing_info['und']) ? $node->field_billing_info['und'] : array();
	    if(count($bills)>0) {
		  foreach($bills as $bill) {
			$entity = entity_load_single('field_collection_item', $bill['value']);

			$field_tx_date = (isset($entity->field_tx_date['und'][0]['value']) ? date('Y-m-d', strtotime($entity->field_tx_date['und'][0]['value'])) : '');
			$field_rx_amount = (isset($entity->field_rx_amount['und'][0]['value']) ? '$ '.$entity->field_rx_amount['und'][0]['value'] : 0);
			$field_tx_status = (isset($entity->field_tx_status['und'][0]['value']) ? $entity->field_tx_status['und'][0]['value'] : '');
			if($field_tx_status == 'Successful') {
			  $bill_amount = $bill_amount + $field_rx_amount;
			}
		  }
	    }

	    $bill_check_amount_1 = $rx_amount * 1;
	    $bill_check_amount_2 = $rx_amount * 3;
	    $bill_check_amount_3 = $rx_amount * 6;
	    $bill_check_amount_4 = $rx_amount * 9;

	    if($sequence == 1 && $bill_amount < $bill_check_amount_1) {
	      $total_rx_nids[$row->nid] = $row->nid;
	      $total_rx_arr[$row->nid] = $row->nid;
	    }
	    else if($sequence == 2 && $bill_amount < $bill_check_amount_2) {
	      $total_rx_nids[$row->nid] = $row->nid;
	      $total_rx_arr[$row->nid] = $row->nid;
	    }
	    else if($sequence == 3 && $bill_amount < $bill_check_amount_3) {
	      $total_rx_nids[$row->nid] = $row->nid;
	      $total_rx_arr[$row->nid] = $row->nid;
	    }
	    else if($sequence == 4 && $bill_amount < $bill_check_amount_4) {
	      $total_rx_nids[$row->nid] = $row->nid;
	      $total_rx_arr[$row->nid] = $row->nid;
	    }
      }
    }
	
    //count total rx
    $total_rx_yesterday = count($total_rx_arr);
    $total_rx_nids = implode(',', $total_rx_nids);
    
    //update the table
    $sql_insert = "INSERT INTO {dashboard_count_by_date} (dashboard_date, rx_scheduled, rx_scheduled_nids, rx_scheduled_last_update) VALUES (:dashboard_date,:rx_scheduled, :rx_scheduled_nids, :rx_scheduled_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE rx_scheduled=VALUES(rx_scheduled), rx_scheduled_nids=VALUES(rx_scheduled_nids), rx_scheduled_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':rx_scheduled' => $total_rx_yesterday,
      ':rx_scheduled_nids' => $total_rx_nids,
      ':rx_scheduled_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate rx scheduled for delivery for yesterday, completed.');

    //upcoming rx refills
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $date_reference = date('Y-m-d', strtotime('+9 days'));
    $total_rx_yesterday = 0;
    $total_rx_nids = array();
	$total_rx_arr = array();

    $sql  = "SELECT n.nid, sd.field_scheduled_date_value as scheduled_date ";
    $sql .= "FROM {node} n ";
    $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_shipping_info} fc ON fc.entity_id = n.nid AND fc.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_scheduled_date} sd ON sd.entity_id = fc.field_shipping_info_value AND sd.bundle = 'field_shipping_info' ";
    $sql .= "INNER JOIN {field_data_field_shipping_status} ss ON ss.entity_id = fc.field_shipping_info_value AND ss.bundle = 'field_shipping_info' ";
    $sql .= "WHERE ss.field_shipping_status_value = 'Scheduled' ";
    $sql .= "AND sd.field_scheduled_date_value BETWEEN '".$yesterday."' AND '".$date_reference."' ";
    $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $results = db_query($sql)->fetchAll();
  
    if(count($results)>0) {
      foreach($results as $row) {
	    $scheduled_date = (isset($row->scheduled_date) ? date('Y-m-d', strtotime($row->scheduled_date)) : '');

	    $total_rx_nids[$row->nid] = $row->nid.'|'.$scheduled_date;
	    $total_rx_arr[$row->nid] = $row->nid;
      }
    }

    //count total rx
    $total_rx_yesterday = count($total_rx_arr);
    $total_rx_nids = implode(',', $total_rx_nids);
	
    //update the table
    $sql_insert = "INSERT INTO {dashboard_count_by_date} (dashboard_date, rx_refills, rx_refills_nids, rx_refills_last_update) VALUES (:dashboard_date,:rx_refills, :rx_refills_nids, :rx_refills_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE rx_refills=VALUES(rx_refills), rx_refills_nids=VALUES(rx_refills_nids), rx_refills_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':rx_refills' => $total_rx_yesterday,
      ':rx_refills_nids' => $total_rx_nids,
      ':rx_refills_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);


    //silentpost summary: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_resolved_yesterday = 0;
    $total_pending_yesterday = 0;

    $sql  = "SELECT n.resolved as 'resolved_type', COUNT(*) as total ";
    $sql .= "FROM {silentpost} n ";
    $sql .= "WHERE FROM_UNIXTIME(n.post_date, '%Y') = :current_year ";
    $sql .= "GROUP BY n.resolved ";

    $values = array(
      ':current_year' => date('Y'),
    );
    $results = db_query($sql, $values)->fetchAll();
  
    if(count($results)>0) {
      foreach($results as $row) {
        if($row->resolved_type == 1) {  //Yes
  		  $total_resolved_yesterday = $row->total;
        }
        else if($row->resolved_type == 2) {  //No
  		  $total_pending_yesterday = $row->total;
        }
      }
    }
    
    //update the table
    $sql_insert = "INSERT INTO {dashboard_count_by_date} (dashboard_date, silent_post_resolved, silent_post_pending, silent_post_last_update) VALUES (:dashboard_date,:silent_post_resolved, :silent_post_pending, :silent_post_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE silent_post_resolved=VALUES(silent_post_resolved), silent_post_pending=VALUES(silent_post_pending), silent_post_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':silent_post_resolved' => $total_resolved_yesterday,
      ':silent_post_pending' => $total_pending_yesterday,
      ':silent_post_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate silent post summary for yesterday, completed.');


    //total submitted orders: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_orders_yesterday = 0;
    $total_orders_ids = '';

    $sql  = "SELECT ord.order_id, ot.commerce_order_total_amount as total, ord.uid ";
    $sql .= "FROM {commerce_order} ord ";
    $sql .= "INNER JOIN {field_data_commerce_order_total} ot ON ot.entity_id = ord.order_id AND ot.bundle = 'commerce_order' ";
    $sql .= "WHERE ord.status IN ('completed', 'processing', 'pending', 'checkout_complete') ";
    $sql .= "AND ot.entity_type = 'commerce_order' ";
    $sql .= "AND DATE_FORMAT(FROM_UNIXTIME(ord.created), '%Y-%m-%d') = '".$yesterday."' ";
    $results = db_query($sql)->fetchAll();

    if(count($results)>0) {
      foreach($results as $row) {
  	    ++$total_orders_yesterday;
  	    if(!empty($total_orders_ids)) $total_orders_ids .= ',';
  	    $total_orders_ids .= $row->order_id.'|'.($row->total/100).'|'.$row->uid;
      }
    }
    
    //update the table
    $sql_insert = "INSERT INTO {dashboard_count_by_date} (dashboard_date, order_submitted, order_ids, order_last_update) VALUES (:dashboard_date,:order_submitted, :order_ids, :order_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE order_submitted=VALUES(order_submitted), order_ids=VALUES(order_ids), order_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':order_submitted' => $total_orders_yesterday,
      ':order_ids' => $total_orders_ids,
      ':order_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate total order submitted for yesterday, completed.');


    //total po amount / po refund amount: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));

    $sql  = "SELECT pot.field_po_type_value as po_type, SUM(CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2))) as total ";
    $sql .= "FROM {node} n ";
    $sql .= "INNER JOIN {field_data_field_amount} pom ON pom.entity_id = n.nid ";
    $sql .= "INNER JOIN {field_data_field_po_date} pod ON pod.entity_id = n.nid ";
    $sql .= "INNER JOIN {field_data_field_po_type} pot ON pot.entity_id = n.nid ";
    $sql .= "WHERE DATE_FORMAT(pod.field_po_date_value, '%Y-%m-%d') = '".$yesterday."' ";
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
    $sql_insert = "INSERT INTO {dashboard_billing_by_date} (dashboard_date, total_po_amount, total_po_refund_amount, po_amount_last_update) VALUES (:dashboard_date, :total_po_amount, :total_po_refund_amount, :po_amount_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_po_amount=VALUES(total_po_amount), total_po_refund_amount=VALUES(total_po_refund_amount), po_amount_last_update='".date('Y-m-d H:i:s')."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':total_po_amount' => abs($total_po_amount),
      ':total_po_refund_amount' => abs($total_po_refund_amount),
      ':po_amount_last_update' => date('Y-m-d H:i:s'),
    );
    db_query($sql_insert, $sql_values);


    //total po amount / po refund amount: past 7 weeks
    for($i=1; $i<=7; $i++) {
	  $past_week = date('W', strtotime("-".$i." week"));
	  $past_week_month = date('n', strtotime("-".$i." week"));
	  $past_week_year = date('Y', strtotime("-".$i." week"));

      	  $sql  = "SELECT pot.field_po_type_value as po_type, SUM(CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2))) as total ";
  	  $sql .= "FROM {node} n ";
  	  $sql .= "INNER JOIN {field_data_field_amount} pom ON pom.entity_id = n.nid ";
  	  $sql .= "INNER JOIN {field_data_field_po_date} pod ON pod.entity_id = n.nid ";
  	  $sql .= "INNER JOIN {field_data_field_po_type} pot ON pot.entity_id = n.nid ";
  	  $sql .= "WHERE YEAR(pod.field_po_date_value) = '".$past_week_year."' ";
  	  $sql .= "AND WEEKOFYEAR(pod.field_po_date_value) = '".$past_week."' ";
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
	  $sql_insert .= "ON DUPLICATE KEY UPDATE total_po_amount=VALUES(total_po_amount), total_po_refund_amount=VALUES(total_po_refund_amount), po_amount_last_update='".date('Y-m-d H:i:s')."'";
	  $sql_values = array(
		':dashboard_year' => $past_week_year,
		':dashboard_month' => $past_week_month,
		':dashboard_week' => $past_week,
		':total_po_amount' => abs($total_po_amount),
		':total_po_refund_amount' => abs($total_po_refund_amount),
		':po_amount_last_update' => date('Y-m-d H:i:s'),
	  );
	  db_query($sql_insert, $sql_values);
    }

  
    //po amount / po refund amount: last 12 months
    for($i=1;$i<=12;$i++) {
	  $past_month = date('n', strtotime("-".$i." month"));
	  $past_month_year = date('Y', strtotime("-".$i." month"));

	  $sql  = "SELECT pot.field_po_type_value as po_type, SUM(CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2))) as total ";
	  $sql .= "FROM {node} n ";
	  $sql .= "INNER JOIN {field_data_field_amount} pom ON pom.entity_id = n.nid ";
	  $sql .= "INNER JOIN {field_data_field_po_date} pod ON pod.entity_id = n.nid ";
	  $sql .= "INNER JOIN {field_data_field_po_type} pot ON pot.entity_id = n.nid ";
	  $sql .= "WHERE YEAR(pod.field_po_date_value) = '".$past_month_year."' ";
	  $sql .= "AND MONTH(pod.field_po_date_value) = '".$past_month."' ";
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
	  $sql_insert = "INSERT INTO {dashboard_billing_by_month} (dashboard_year, dashboard_month, total_po_amount, total_po_refund_amount, po_amount_last_update) VALUES (:dashboard_year, :dashboard_month, :total_po_amount, :total_po_refund_amount, :po_amount_last_update) ";
	  $sql_insert .= "ON DUPLICATE KEY UPDATE total_po_amount=VALUES(total_po_amount), total_po_refund_amount=VALUES(total_po_refund_amount), po_amount_last_update='".date('Y-m-d H:i:s')."'";
	  $sql_values = array(
		':dashboard_year' => $past_month_year,
		':dashboard_month' => $past_month,
		':total_po_amount' => abs($total_po_amount),
		':total_po_refund_amount' => abs($total_po_refund_amount),
		':po_amount_last_update' => date('Y-m-d H:i:s'),
	  );
	  db_query($sql_insert, $sql_values);
    }
    watchdog('dashboard', 'Re-generate total po amount and po refund amount for yesterday, last 7 weeks and last 12 months, completed.');


    //total po / po refund : yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));

    $sql  = "SELECT pot.field_po_type_value as po_type, COUNT(*) as total ";
    $sql .= "FROM {node} n ";
    $sql .= "INNER JOIN {field_data_field_po_date} pod ON pod.entity_id = n.nid ";
    $sql .= "INNER JOIN {field_data_field_po_type} pot ON pot.entity_id = n.nid ";
    $sql .= "WHERE DATE_FORMAT(pod.field_po_date_value, '%Y-%m-%d') = '".$yesterday."' ";
    $sql .= "AND n.type = 'po' ";
    $sql .= "AND n.status = 1 ";
    $sql .= "GROUP BY pot.field_po_type_value ";

    $results = db_query($sql)->fetchAll();
  
    $total_po = 0;
    $total_po_refund = 0;
  
    if(count($results)>0) {
      foreach($results as $row) {
        if($row->po_type == 1) {
  	  $total_po = $row->total;
        }
        else if($row->po_type == 2) {  //refund
  	  $total_po_refund = $row->total;
        }
      }
    }
    if(empty($total_po)) $total_po = 0;
    if(empty($total_po_refund)) $total_po_refund = 0;

    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_date} (dashboard_date, total_po_created, total_po_refund_created, po_create_last_update) VALUES (:dashboard_date, :total_po_created, :total_po_refund_created, :po_create_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE total_po_created=VALUES(total_po_created), total_po_refund_created=VALUES(total_po_refund_created), po_create_last_update='".date('Y-m-d H:i:s')."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':total_po_created' => abs($total_po),
      ':total_po_refund_created' => abs($total_po_refund),
      ':po_create_last_update' => date('Y-m-d H:i:s'),
    );
    db_query($sql_insert, $sql_values);


    //total po amount / po refund amount: past 7 weeks
	for($i=1; $i<=7; $i++) {
	  $past_week = date('W', strtotime("-".$i." week"));
	  $past_week_month = date('n', strtotime("-".$i." week"));
	  $past_week_year = date('Y', strtotime("-".$i." week"));

	  $sql  = "SELECT pot.field_po_type_value as po_type, COUNT(*) as total ";
	  $sql .= "FROM {node} n ";
	  $sql .= "INNER JOIN {field_data_field_po_date} pod ON pod.entity_id = n.nid ";
	  $sql .= "INNER JOIN {field_data_field_po_type} pot ON pot.entity_id = n.nid ";
	  $sql .= "WHERE YEAR(pod.field_po_date_value) = '".$past_week_year."' ";
	  $sql .= "AND WEEKOFYEAR(pod.field_po_date_value) = '".$past_week."' ";
	  $sql .= "AND n.type = 'po' ";
	  $sql .= "AND n.status = 1 ";
	  $sql .= "GROUP BY pot.field_po_type_value ";

	  $results = db_query($sql)->fetchAll();

	  $total_po = 0;
	  $total_po_refund = 0;

	  if(count($results)>0) {
		foreach($results as $row) {
		  if($row->po_type == 1) {
			$total_po = $row->total;
		  }
		  else if($row->po_type == 2) {  //refund
			$total_po_refund = $row->total;
		  }
		}
	  }
	  if(empty($total_po)) $total_po = 0;
	  if(empty($total_po_refund)) $total_po_refund = 0;

	  //update the table
	  $sql_insert = "INSERT INTO {dashboard_billing_by_week} (dashboard_year, dashboard_month, dashboard_week, total_po_created, total_po_refund_created, po_create_last_update) VALUES (:dashboard_year, :dashboard_month, :dashboard_week, :total_po_created, :total_po_refund_created, :po_create_last_update) ";
	  $sql_insert .= "ON DUPLICATE KEY UPDATE total_po_created=VALUES(total_po_created), total_po_refund_created=VALUES(total_po_refund_created), po_create_last_update='".date('Y-m-d H:i:s')."'";
	  $sql_values = array(
		':dashboard_year' => $past_week_year,
		':dashboard_month' => $past_week_month,
		':dashboard_week' => $past_week,
		':total_po_created' => abs($total_po),
		':total_po_refund_created' => abs($total_po_refund),
		':po_create_last_update' => date('Y-m-d H:i:s'),
	  );
	  db_query($sql_insert, $sql_values);
	}

    //po amount / po refund amount: last 12 months
    for($i=1;$i<=12;$i++) {
	  $past_month = date('n', strtotime("-".$i." month"));
	  $past_month_year = date('Y', strtotime("-".$i." month"));

	  $sql  = "SELECT pot.field_po_type_value as po_type, COUNT(*) as total ";
	  $sql .= "FROM {node} n ";
	  $sql .= "INNER JOIN {field_data_field_po_date} pod ON pod.entity_id = n.nid ";
	  $sql .= "INNER JOIN {field_data_field_po_type} pot ON pot.entity_id = n.nid ";
	  $sql .= "WHERE YEAR(pod.field_po_date_value) = '".$past_month_year."' ";
	  $sql .= "AND MONTH(pod.field_po_date_value) = '".$past_month."' ";
	  $sql .= "AND n.type = 'po' ";
	  $sql .= "AND n.status = 1 ";
	  $sql .= "GROUP BY pot.field_po_type_value ";

	  $results = db_query($sql)->fetchAll();

	  $total_po = 0;
	  $total_po_refund = 0;

	  if(count($results)>0) {
		foreach($results as $row) {
		  if($row->po_type == 1) {
			$total_po = $row->total;
		  }
		  else if($row->po_type == 2) {  //refund
			$total_po_refund = $row->total;
		  }
		}
	  }
	  if(empty($total_po)) $total_po = 0;
	  if(empty($total_po_refund)) $total_po_refund = 0;


	  //update the table
	  $sql_insert = "INSERT INTO {dashboard_billing_by_month} (dashboard_year, dashboard_month, total_po_created, total_po_refund_created, po_create_last_update) VALUES (:dashboard_year, :dashboard_month, :total_po_created, :total_po_refund_created, :po_create_last_update) ";
	  $sql_insert .= "ON DUPLICATE KEY UPDATE total_po_created=VALUES(total_po_created), total_po_refund_created=VALUES(total_po_refund_created), po_create_last_update='".date('Y-m-d H:i:s')."'";
	  $sql_values = array(
		':dashboard_year' => $past_month_year,
		':dashboard_month' => $past_month,
		':total_po_created' => abs($total_po),
		':total_po_refund_created' => abs($total_po_refund),
		':po_create_last_update' => date('Y-m-d H:i:s'),
	  );
	  db_query($sql_insert, $sql_values);
	}
    watchdog('dashboard', 'Re-generate total po created and po refund created for yesterday, last 7 weeks and last 12 months, completed.');


  	/** total sales amount **/
  	$today = date('Y-m-d');
  	$yesterday = date('Y-m-d', strtotime('-1 days'));

  	//today : rx amount
  	$sql  = "SELECT SUM(am.field_rx_amount_value) as total ";
  	$sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
  	$sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid and fc.bundle = 'rx' ";
  	$sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value and td.bundle = 'field_billing_info' ";
  	$sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value and am.bundle = 'field_billing_info' ";
  	$sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value and ts.bundle = 'field_billing_info' ";
  	$sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Invoice', 'Paid', 'Signed', 'Successful - Fee', 'Successful - PSCC', 'Manual') ";
  	$sql .= "AND DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = '".$today."' ";
    	$sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
  	$sql .= "AND n.type = 'rx' ";
  	$sql .= "AND n.status = 1 ";
  	$today_total_rx_amount = db_query($sql)->fetchField();
  	if(empty($today_total_rx_amount)) $today_total_rx_amount = 0;

  	//today : po amount
  	$sql  = "SELECT SUM(CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2))) as total ";
  	$sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_amount} pom ON pom.entity_id = n.nid ";
  	$sql .= "INNER JOIN {field_data_field_po_date} pod ON pod.entity_id = n.nid ";
  	$sql .= "INNER JOIN {field_data_field_po_type} pot ON pot.entity_id = n.nid ";
  	$sql .= "WHERE DATE_FORMAT(pod.field_po_date_value, '%Y-%m-%d') = '".$today."' ";
  	$sql .= "AND n.type = 'po' ";
  	$sql .= "AND n.status = 1 ";
  	$sql .= "AND pot.field_po_type_value = 1 ";
  	$today_total_po_amount = db_query($sql)->fetchField();
  	if(empty($today_total_po_amount)) $today_total_po_amount = 0;

  	//today : store amount
  	$sql  = "SELECT SUM(ot.commerce_order_total_amount) as total ";
  	$sql .= "FROM {commerce_order} ord ";
  	$sql .= "INNER JOIN {field_data_commerce_order_total} ot ON ot.entity_id = ord.order_id AND ot.bundle = 'commerce_order' ";
  	$sql .= "WHERE ord.status IN ('completed', 'processing', 'pending', 'checkout_complete') ";
  	$sql .= "AND ot.entity_type = 'commerce_order' ";
  	$sql .= "AND DATE_FORMAT(FROM_UNIXTIME(ord.created), '%Y-%m-%d') = '".$today."' ";
  	$today_total_store_amount = db_query($sql)->fetchField();
  	if(empty($today_total_store_amount)) $today_total_store_amount = 0;
  	$today_total_store_amount = $today_total_store_amount / 100;
  
  	//total sales amount
  	$today_total_sales_amount = $today_total_rx_amount + $today_total_po_amount + $today_total_store_amount;

  	//update the table : today
  	$sql_insert = "INSERT INTO {dashboard_billing_by_date} (dashboard_date, total_sales_amount, total_sales_amount_last_update) VALUES (:dashboard_date, :total_sales_amount, :total_sales_amount_last_update) ";
  	$sql_insert .= "ON DUPLICATE KEY UPDATE total_sales_amount=VALUES(total_sales_amount), total_sales_amount_last_update='".date('Y-m-d H:i:s')."'";
  	$sql_values = array(
          ':dashboard_date' => $today,
          ':total_sales_amount' => $today_total_sales_amount,
          ':total_sales_amount_last_update' => date('Y-m-d H:i:s'),
	);
	db_query($sql_insert, $sql_values);


    //sales amount: past 31 days
	for($i=1;$i<=31;$i++) {
  	  $pastday = date('Y-m-d', strtotime('-'.$i.' days'));

  	  //pastday : rx amount
  	  $sql  = "SELECT SUM(am.field_rx_amount_value) as total ";
  	  $sql .= "FROM {node} n ";
  	  $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
  	  $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid and fc.bundle = 'rx' ";
  	  $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value and td.bundle = 'field_billing_info' ";
  	  $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value and am.bundle = 'field_billing_info' ";
  	  $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value and ts.bundle = 'field_billing_info' ";
  	  $sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Invoice', 'Paid', 'Signed', 'Successful - Fee', 'Successful - PSCC', 'Manual') ";
  	  $sql .= "AND DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = '".$pastday."' ";
      	  $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
  	  $sql .= "AND n.type = 'rx' ";
  	  $sql .= "AND n.status = 1 ";
  	  $pastday_total_rx_amount = db_query($sql)->fetchField();
  	  if(empty($pastday_total_rx_amount)) $pastday_total_rx_amount = 0;

  	  //pastday : po amount
  	  $sql  = "SELECT SUM(CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2))) as total ";
  	  $sql .= "FROM {node} n ";
  	  $sql .= "INNER JOIN {field_data_field_amount} pom ON pom.entity_id = n.nid ";
  	  $sql .= "INNER JOIN {field_data_field_po_date} pod ON pod.entity_id = n.nid ";
  	  $sql .= "INNER JOIN {field_data_field_po_type} pot ON pot.entity_id = n.nid ";
  	  $sql .= "WHERE DATE_FORMAT(pod.field_po_date_value, '%Y-%m-%d') = '".$pastday."' ";
  	  $sql .= "AND n.type = 'po' ";
  	  $sql .= "AND n.status = 1 ";
  	  $sql .= "AND pot.field_po_type_value = 1 ";
  	  $pastday_total_po_amount = db_query($sql)->fetchField();
  	  if(empty($pastday_total_po_amount)) $pastday_total_po_amount = 0;

  	  //pastday : store amount
  	  $sql  = "SELECT SUM(ot.commerce_order_total_amount) as total ";
  	  $sql .= "FROM {commerce_order} ord ";
  	  $sql .= "INNER JOIN {field_data_commerce_order_total} ot ON ot.entity_id = ord.order_id AND ot.bundle = 'commerce_order' ";
  	  $sql .= "WHERE ord.status IN ('completed', 'processing', 'pending', 'checkout_complete') ";
  	  $sql .= "AND ot.entity_type = 'commerce_order' ";
  	  $sql .= "AND DATE_FORMAT(FROM_UNIXTIME(ord.created), '%Y-%m-%d') = '".$pastday."' ";
  	  $pastday_total_store_amount = db_query($sql)->fetchField();
  	  if(empty($pastday_total_store_amount)) $pastday_total_store_amount = 0;
  	  $pastday_total_store_amount = $pastday_total_store_amount / 100;

  	  //total sales amount
  	  $pastday_total_sales_amount = $pastday_total_rx_amount + $pastday_total_po_amount + $pastday_total_store_amount;

	  //update the table : yesterday
	  $sql_insert = "INSERT INTO {dashboard_billing_by_date} (dashboard_date, total_sales_amount, total_sales_amount_last_update) VALUES (:dashboard_date, :total_sales_amount, :total_sales_amount_last_update) ";
	  $sql_insert .= "ON DUPLICATE KEY UPDATE total_sales_amount=VALUES(total_sales_amount), total_sales_amount_last_update='".date('Y-m-d H:i:s')."'";
	  $sql_values = array(
        ':dashboard_date' => $pastday,
        ':total_sales_amount' => $pastday_total_sales_amount,
        ':total_sales_amount_last_update' => date('Y-m-d H:i:s'),
	  );
	  db_query($sql_insert, $sql_values);
	}
	

    //sales amount: past 7 weeks
	for($i=1; $i<=7; $i++) {
	  $past_week = date('W', strtotime("-".$i." week"));
	  $past_week_month = date('n', strtotime("-".$i." week"));
	  $past_week_year = date('Y', strtotime("-".$i." week"));

	  $sql  = "SELECT SUM(am.field_rx_amount_value) as total ";
	  $sql .= "FROM {node} n ";
  	  $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
	  $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid and fc.bundle = 'rx' ";
	  $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value and td.bundle = 'field_billing_info' ";
	  $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value and am.bundle = 'field_billing_info' ";
	  $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value and ts.bundle = 'field_billing_info' ";
	  $sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Invoice', 'Paid', 'Signed', 'Successful - Fee', 'Successful - PSCC', 'Manual') ";
	  $sql .= "AND YEAR(td.field_tx_date_value) = '".$past_week_year."' ";
	  $sql .= "AND WEEKOFYEAR(td.field_tx_date_value) = '".$past_week."' ";
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
	  $sql .= "WHERE YEAR(pod.field_po_date_value) = '".$past_week_year."' ";
	  $sql .= "AND WEEKOFYEAR(pod.field_po_date_value) = '".$past_week."' ";
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
	  $sql .= "AND YEAR(FROM_UNIXTIME(ord.created)) = '".$past_week_year."' ";
	  $sql .= "AND WEEKOFYEAR(FROM_UNIXTIME(ord.created)) = '".$past_week."' ";
	  $weekly_total_store_amount = db_query($sql)->fetchField();
	  if(empty($weekly_total_store_amount)) $weekly_total_store_amount = 0;

	  $weekly_total_store_amount = $weekly_total_store_amount / 100;

	  //total sales amount
	  $weekly_total_sales_amount = $weekly_total_rx_amount + $weekly_total_po_amount + $weekly_total_store_amount;

	  //update the table
	  $sql_insert = "INSERT INTO {dashboard_billing_by_week} (dashboard_year, dashboard_month, dashboard_week, total_sales_amount, total_sales_amount_last_update) VALUES (:dashboard_year, :dashboard_month, :dashboard_week, :total_sales_amount, :total_sales_amount_last_update) ";
	  $sql_insert .= "ON DUPLICATE KEY UPDATE total_sales_amount=VALUES(total_sales_amount), total_sales_amount_last_update='".date('Y-m-d H:i:s')."'";
	  $sql_values = array(
		':dashboard_year' => $past_week_year,
		':dashboard_month' => $past_week_month,
		':dashboard_week' => $past_week,
		':total_sales_amount' => $weekly_total_sales_amount,
		':total_sales_amount_last_update' => date('Y-m-d H:i:s'),
	  );
	  db_query($sql_insert, $sql_values);
	}

    //sales amount: last 12 months
    for($i=1;$i<=12;$i++) {
	  $past_month = date('n', strtotime("-".$i." month"));
	  $past_month_year = date('Y', strtotime("-".$i." month"));

	  //this month : rx amount
	  $sql  = "SELECT SUM(am.field_rx_amount_value) as total ";
	  $sql .= "FROM {node} n ";
  	  $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
	  $sql .= "INNER JOIN {field_data_field_billing_info} fc ON fc.entity_id = n.nid and fc.bundle = 'rx' ";
	  $sql .= "INNER JOIN {field_data_field_tx_date} td ON td.entity_id = fc.field_billing_info_value and td.bundle = 'field_billing_info' ";
	  $sql .= "INNER JOIN {field_data_field_rx_amount} am ON am.entity_id = fc.field_billing_info_value and am.bundle = 'field_billing_info' ";
	  $sql .= "INNER JOIN {field_data_field_tx_status} ts ON ts.entity_id = fc.field_billing_info_value and ts.bundle = 'field_billing_info' ";
	  $sql .= "WHERE ts.field_tx_status_value IN ('Successful', 'Invoice', 'Paid', 'Signed', 'Successful - Fee', 'Successful - PSCC', 'Manual') ";
	  $sql .= "AND YEAR(td.field_tx_date_value) = '".$past_month_year."' ";
	  $sql .= "AND MONTH(td.field_tx_date_value) = '".$past_month."' ";
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
	  $sql .= "WHERE YEAR(pod.field_po_date_value) = '".$past_month_year."' ";
	  $sql .= "AND MONTH(pod.field_po_date_value) = '".$past_month."' ";
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
	  $sql .= "AND YEAR(FROM_UNIXTIME(ord.created)) = '".$past_month_year."' ";
	  $sql .= "AND MONTH(FROM_UNIXTIME(ord.created)) = '".$past_month."' ";
	  $monthly_total_order_amount = db_query($sql)->fetchField();
	  if(empty($monthly_total_order_amount)) $monthly_total_order_amount = 0;

	  $monthly_total_order_amount = $monthly_total_order_amount / 100;

	  //total sales amount
	  $monthly_total_sales_amount = $monthly_total_rx_amount + $monthly_total_po_amount + $monthly_total_order_amount;

	  //update the table
	  $sql_insert = "INSERT INTO {dashboard_billing_by_month} (dashboard_year, dashboard_month, total_sales_amount, total_sales_amount_last_update) VALUES (:dashboard_year, :dashboard_month, :total_sales_amount, :total_sales_amount_last_update) ";
	  $sql_insert .= "ON DUPLICATE KEY UPDATE total_sales_amount=VALUES(total_sales_amount), total_sales_amount_last_update='".date('Y-m-d H:i:s')."'";
	  $sql_values = array(
		':dashboard_year' => $past_month_year,
		':dashboard_month' => $past_month,
		':total_sales_amount' => $monthly_total_sales_amount,
		':total_sales_amount_last_update' => date('Y-m-d H:i:s'),
	  );
	  db_query($sql_insert, $sql_values);
	}
    watchdog('dashboard', 'Re-generate total sales amount for yesterday, last 7 weeks and last 12 months, completed.');

    //rx created: yesterday by clinic
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $clinic_scit_created_yesterday = 0;
    $clinic_slit_created_yesterday = 0;

    $sql  = "SELECT bt.field_rx_base_type_value as 'base_type', COUNT(*) as total ";
    $sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_order_date} od ON od.entity_id = n.nid and od.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_rx_base_type} bt ON bt.entity_id = n.nid and bt.bundle = 'rx' ";
    $sql .= "INNER JOIN {users} u ON n.uid = u.uid ";
    //$sql .= "INNER JOIN {users_roles} ur ON u.uid = ur.uid ";
    $sql .= "WHERE DATE_FORMAT(od.field_order_date_value, '%Y-%m-%d') = '".$yesterday."' ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
    //$sql .= "AND ur.rid IN (7, 16, 12) ";  
	$sql .= "AND u.uid IN (SELECT DISTINCT uid FROM users_roles WHERE rid IN (7, 16, 12)) "; //Clinic / Provider Representative, Testing Admin, Provider
    $sql .= "GROUP BY bt.field_rx_base_type_value ";
    $results = db_query($sql)->fetchAll();

    if(count($results)>0) {
      foreach($results as $row) {
        if($row->base_type == 'SCIT') {
  		  $clinic_scit_created_yesterday = $row->total;
        }
        else {
  		  $clinic_slit_created_yesterday = $row->total;
        }
      }
    }
	
    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_date} (dashboard_date, clinic_scit_created, clinic_slit_created, clinic_rx_create_last_update) VALUES (:dashboard_date, :clinic_scit_created, :clinic_slit_created, :clinic_rx_create_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE clinic_scit_created=VALUES(clinic_scit_created), clinic_slit_created=VALUES(clinic_slit_created), clinic_rx_create_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':clinic_scit_created' => $clinic_scit_created_yesterday,
      ':clinic_slit_created' => $clinic_slit_created_yesterday,
      ':clinic_rx_create_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate rx created by clinic for yesterday, completed.');

    //rx created by clinic : last 12 months
    for($i=1;$i<=12;$i++) {
	  $past_month = date('n', strtotime("-".$i." month"));
	  $past_month_year = date('Y', strtotime("-".$i." month"));
      $clinic_scit_created = 0;
      $clinic_slit_created = 0;

      $sql  = "SELECT bt.field_rx_base_type_value as 'base_type', COUNT(*) as total ";
      $sql .= "FROM {node} n ";
  	  $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_order_date} od ON od.entity_id = n.nid and od.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_rx_base_type} bt ON bt.entity_id = n.nid and bt.bundle = 'rx' ";
      $sql .= "INNER JOIN {users} u ON n.uid = u.uid ";
      //$sql .= "INNER JOIN {users_roles} ur ON u.uid = ur.uid ";
      $sql .= "WHERE YEAR(od.field_order_date_value) = '".$past_month_year."' ";
      $sql .= "AND MONTH(od.field_order_date_value) = '".$past_month."' ";
      $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
      $sql .= "AND n.type = 'rx' ";
      $sql .= "AND n.status = 1 ";
      //$sql .= "AND ur.rid IN (7, 16, 12) ";
	  $sql .= "AND u.uid IN (SELECT DISTINCT uid FROM users_roles WHERE rid IN (7, 16, 12)) "; //Clinic / Provider Representative, Testing Admin, Provider
      $sql .= "GROUP BY bt.field_rx_base_type_value ";
      $results = db_query($sql)->fetchAll();

      if(count($results)>0) {
        foreach($results as $row) {
          if($row->base_type == 'SCIT') {
  		    $clinic_scit_created = $row->total;
          }
          else {
  		    $clinic_slit_created = $row->total;
          }
        }
      }
  
      //update the table
      $sql_insert = "INSERT INTO {dashboard_billing_by_month} (dashboard_year, dashboard_month, clinic_scit_created, clinic_slit_created, clinic_rx_create_last_update) VALUES (:dashboard_year, :dashboard_month, :clinic_scit_created, :clinic_slit_created, :clinic_rx_create_last_update) ";
      $sql_insert .= "ON DUPLICATE KEY UPDATE clinic_scit_created=VALUES(clinic_scit_created), clinic_slit_created=VALUES(clinic_slit_created), clinic_rx_create_last_update='".$time."'";
      $sql_values = array(
        ':dashboard_year' => $past_month_year,
        ':dashboard_month' => $past_month,
        ':clinic_scit_created' => $clinic_scit_created,
        ':clinic_slit_created' => $clinic_slit_created,
        ':clinic_rx_create_last_update' => $time,
      );
      db_query($sql_insert, $sql_values);
    }
    watchdog('dashboard', 'Re-generate rx created by clinic for last 12 months, completed.');


    //rx created: yesterday by staff
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $staff_scit_created_yesterday = 0;
    $staff_slit_created_yesterday = 0;

    $sql  = "SELECT bt.field_rx_base_type_value as 'base_type', COUNT(*) as total ";
    $sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_order_date} od ON od.entity_id = n.nid and od.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_rx_base_type} bt ON bt.entity_id = n.nid and bt.bundle = 'rx' ";
    $sql .= "INNER JOIN {users} u ON n.uid = u.uid ";
    //$sql .= "INNER JOIN {users_roles} ur ON u.uid = ur.uid ";
    $sql .= "WHERE DATE_FORMAT(od.field_order_date_value, '%Y-%m-%d') = '".$yesterday."' ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
    //$sql .= "AND ur.rid IN (13, 11, 8, 5, 3, 4, 18) ";  
	$sql .= "AND u.uid IN (SELECT DISTINCT uid FROM users_roles WHERE rid IN (13, 11, 8, 5, 3, 4, 18)) "; //Pharmacy, Clinical Support, Field Rep, Order Manager, Administrator, Website Manager, Call Center
    $sql .= "GROUP BY bt.field_rx_base_type_value ";
    $results = db_query($sql)->fetchAll();

    if(count($results)>0) {
      foreach($results as $row) {
        if($row->base_type == 'SCIT') {
  		  $staff_scit_created_yesterday = $row->total;
        }
        else {
  		  $staff_slit_created_yesterday = $row->total;
        }
      }
    }
	
    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_date} (dashboard_date, staff_scit_created, staff_slit_created, staff_rx_create_last_update) VALUES (:dashboard_date, :staff_scit_created, :staff_slit_created, :staff_rx_create_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE staff_scit_created=VALUES(staff_scit_created), staff_slit_created=VALUES(staff_slit_created), staff_rx_create_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':staff_scit_created' => $staff_scit_created_yesterday,
      ':staff_slit_created' => $staff_slit_created_yesterday,
      ':staff_rx_create_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate rx created by staff for yesterday, completed.');

    //rx created by staff : last 12 months
    for($i=1;$i<=12;$i++) {
	  $past_month = date('n', strtotime("-".$i." month"));
	  $past_month_year = date('Y', strtotime("-".$i." month"));
      $staff_scit_created = 0;
      $staff_slit_created = 0;

      $sql  = "SELECT bt.field_rx_base_type_value as 'base_type', COUNT(*) as total ";
      $sql .= "FROM {node} n ";
  	  $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_order_date} od ON od.entity_id = n.nid and od.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_rx_base_type} bt ON bt.entity_id = n.nid and bt.bundle = 'rx' ";
      $sql .= "INNER JOIN {users} u ON n.uid = u.uid ";
      //$sql .= "INNER JOIN {users_roles} ur ON u.uid = ur.uid ";
      $sql .= "WHERE YEAR(od.field_order_date_value) = '".$past_month_year."' ";
      $sql .= "AND MONTH(od.field_order_date_value) = '".$past_month."' ";
      $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
      $sql .= "AND n.type = 'rx' ";
      $sql .= "AND n.status = 1 ";
      //$sql .= "AND ur.rid IN (13, 11, 8, 5, 3, 4, 18) ";
	  $sql .= "AND u.uid IN (SELECT DISTINCT uid FROM users_roles WHERE rid IN (13, 11, 8, 5, 3, 4, 18)) "; //Pharmacy, Clinical Support, Field Rep, Order Manager, Administrator, Website Manager, Call Center
      $sql .= "GROUP BY bt.field_rx_base_type_value ";
      $results = db_query($sql)->fetchAll();

      if(count($results)>0) {
        foreach($results as $row) {
          if($row->base_type == 'SCIT') {
  		    $staff_scit_created = $row->total;
          }
          else {
  		    $staff_slit_created = $row->total;
          }
        }
      }
  
      //update the table
      $sql_insert = "INSERT INTO {dashboard_billing_by_month} (dashboard_year, dashboard_month, staff_scit_created, staff_slit_created, staff_rx_create_last_update) VALUES (:dashboard_year, :dashboard_month, :staff_scit_created, :staff_slit_created, :staff_rx_create_last_update) ";
      $sql_insert .= "ON DUPLICATE KEY UPDATE staff_scit_created=VALUES(staff_scit_created), staff_slit_created=VALUES(staff_slit_created), staff_rx_create_last_update='".$time."'";
      $sql_values = array(
        ':dashboard_year' => $past_month_year,
        ':dashboard_month' => $past_month,
        ':staff_scit_created' => $staff_scit_created,
        ':staff_slit_created' => $staff_slit_created,
        ':staff_rx_create_last_update' => $time,
      );
      db_query($sql_insert, $sql_values);
    }
    watchdog('dashboard', 'Re-generate rx created by staff for last 12 months, completed.');


    //refills: yesterday by clinic
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $clinic_scit_refill_yesterday = 0;
    $clinic_slit_refill_yesterday = 0;

    $sql  = "SELECT bt.field_rx_base_type_value as 'base_type', COUNT(*) as total ";
    $sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_order_date} od ON od.entity_id = n.nid and od.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_rx_base_type} bt ON bt.entity_id = n.nid and bt.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_refill_rx_id} rrx ON rrx.entity_id = n.nid and rrx.bundle = 'rx' ";
    $sql .= "INNER JOIN {users} u ON n.uid = u.uid ";
    //$sql .= "INNER JOIN {users_roles} ur ON u.uid = ur.uid ";
    $sql .= "WHERE DATE_FORMAT(od.field_order_date_value, '%Y-%m-%d') = '".$yesterday."' ";
    $sql .= "AND ws.field_workflow_status_value NOT IN ('Cancelled', 'Completed') ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    //$sql .= "AND ur.rid IN (7, 16, 12) ";
	$sql .= "AND u.uid IN (SELECT DISTINCT uid FROM users_roles WHERE rid IN (7, 16, 12)) "; //Clinic / Provider Representative, Testing Admin, Provider
    $sql .= "GROUP BY bt.field_rx_base_type_value ";
    $results = db_query($sql)->fetchAll();

    if(count($results)>0) {
      foreach($results as $row) {
        if($row->base_type == 'SCIT') {
  		  $clinic_scit_refill_yesterday = $row->total;
        }
        else {
  		  $clinic_slit_refill_yesterday = $row->total;
        }
      }
    }
    
    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_date} (dashboard_date, clinic_slit_refill, clinic_scit_refill, clinic_rx_refill_last_update) VALUES (:dashboard_date, :clinic_slit_refill, :clinic_scit_refill, :clinic_rx_refill_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE clinic_scit_refill=VALUES(clinic_scit_refill), clinic_slit_refill=VALUES(clinic_slit_refill), clinic_rx_refill_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':clinic_scit_refill' => $clinic_scit_refill_yesterday,
      ':clinic_slit_refill' => $clinic_slit_refill_yesterday,
      ':clinic_rx_refill_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate rx refills by clinic for yesterday, completed.');

    //rx refills by clinic: last 12 months
    for($i=1;$i<=12;$i++) {
	  $past_month = date('n', strtotime("-".$i." month"));
	  $past_month_year = date('Y', strtotime("-".$i." month"));
      $clinic_scit_refill = 0;
      $clinic_slit_refill = 0;

      $sql  = "SELECT bt.field_rx_base_type_value as 'base_type', COUNT(*) as total ";
      $sql .= "FROM {node} n ";
  	  $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_order_date} od ON od.entity_id = n.nid and od.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_rx_base_type} bt ON bt.entity_id = n.nid and bt.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_refill_rx_id} rrx ON rrx.entity_id = n.nid and rrx.bundle = 'rx' ";
      $sql .= "INNER JOIN {users} u ON n.uid = u.uid ";
      //$sql .= "INNER JOIN {users_roles} ur ON u.uid = ur.uid ";
      $sql .= "WHERE YEAR(od.field_order_date_value) = '".$past_month_year."' ";
      $sql .= "AND MONTH(od.field_order_date_value) = '".$past_month."' ";
      $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
      $sql .= "AND n.type = 'rx' ";
      $sql .= "AND n.status = 1 ";
      //$sql .= "AND ur.rid IN (7, 16, 12) ";
	  $sql .= "AND u.uid IN (SELECT DISTINCT uid FROM users_roles WHERE rid IN (7, 16, 12)) "; //Clinic / Provider Representative, Testing Admin, Provider
      $sql .= "GROUP BY bt.field_rx_base_type_value ";
      $results = db_query($sql)->fetchAll();

      if(count($results)>0) {
        foreach($results as $row) {
          if($row->base_type == 'SCIT') {
  		    $clinic_scit_refill = $row->total;
          }
          else {
  		    $clinic_slit_refill = $row->total;
          }
        }
      }
  
      //update the table
      $sql_insert = "INSERT INTO {dashboard_billing_by_month} (dashboard_year, dashboard_month, clinic_scit_refill, clinic_slit_refill, clinic_rx_refill_last_update) VALUES (:dashboard_year, :dashboard_month, :clinic_scit_refill, :clinic_slit_refill, :clinic_rx_refill_last_update) ";
      $sql_insert .= "ON DUPLICATE KEY UPDATE clinic_scit_refill=VALUES(clinic_scit_refill), clinic_slit_refill=VALUES(clinic_slit_refill), clinic_rx_refill_last_update='".$time."'";
      $sql_values = array(
        ':dashboard_year' => $past_month_year,
        ':dashboard_month' => $past_month,
        ':clinic_scit_refill' => $clinic_scit_refill,
        ':clinic_slit_refill' => $clinic_slit_refill,
        ':clinic_rx_refill_last_update' => $time,
      );
      db_query($sql_insert, $sql_values);
    }
    watchdog('dashboard', 'Re-generate rx refills by clinic for last 12 months, completed.');

    //refills: yesterday by staff
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $staff_scit_refill_yesterday = 0;
    $staff_slit_refill_yesterday = 0;

    $sql  = "SELECT bt.field_rx_base_type_value as 'base_type', COUNT(*) as total ";
    $sql .= "FROM {node} n ";
  	$sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_order_date} od ON od.entity_id = n.nid and od.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_rx_base_type} bt ON bt.entity_id = n.nid and bt.bundle = 'rx' ";
    $sql .= "INNER JOIN {field_data_field_refill_rx_id} rrx ON rrx.entity_id = n.nid and rrx.bundle = 'rx' ";
    $sql .= "INNER JOIN {users} u ON n.uid = u.uid ";
    //$sql .= "INNER JOIN {users_roles} ur ON u.uid = ur.uid ";
    $sql .= "WHERE DATE_FORMAT(od.field_order_date_value, '%Y-%m-%d') = '".$yesterday."' ";
    $sql .= "AND ws.field_workflow_status_value NOT IN ('Cancelled', 'Completed') ";
    $sql .= "AND n.type = 'rx' ";
    $sql .= "AND n.status = 1 ";
    //$sql .= "AND ur.rid IN (13, 11, 8, 5, 3, 4, 18) ";
	$sql .= "AND u.uid IN (SELECT DISTINCT uid FROM users_roles WHERE rid IN (13, 11, 8, 5, 3, 4, 18)) "; //Pharmacy, Clinical Support, Field Rep, Order Manager, Administrator, Website Manager, Call Center
    $sql .= "GROUP BY bt.field_rx_base_type_value ";
    $results = db_query($sql)->fetchAll();

    if(count($results)>0) {
      foreach($results as $row) {
        if($row->base_type == 'SCIT') {
  		  $staff_scit_refill_yesterday = $row->total;
        }
        else {
  		  $staff_slit_refill_yesterday = $row->total;
        }
      }
    }
    
    //update the table
    $sql_insert = "INSERT INTO {dashboard_billing_by_date} (dashboard_date, staff_slit_refill, staff_scit_refill, staff_rx_refill_last_update) VALUES (:dashboard_date, :staff_slit_refill, :staff_scit_refill, :staff_rx_refill_last_update) ";
    $sql_insert .= "ON DUPLICATE KEY UPDATE staff_scit_refill=VALUES(staff_scit_refill), staff_slit_refill=VALUES(staff_slit_refill), staff_rx_refill_last_update='".$time."'";
    $sql_values = array(
      ':dashboard_date' => $yesterday,
      ':staff_scit_refill' => $staff_scit_refill_yesterday,
      ':staff_slit_refill' => $staff_slit_refill_yesterday,
      ':staff_rx_refill_last_update' => $time,
    );
    db_query($sql_insert, $sql_values);
    watchdog('dashboard', 'Re-generate rx refills by staff for yesterday, completed.');

    //rx refills by staff: last 12 months
    for($i=1;$i<=12;$i++) {
	  $past_month = date('n', strtotime("-".$i." month"));
	  $past_month_year = date('Y', strtotime("-".$i." month"));
      $staff_scit_refill = 0;
      $staff_slit_refill = 0;

      $sql  = "SELECT bt.field_rx_base_type_value as 'base_type', COUNT(*) as total ";
      $sql .= "FROM {node} n ";
  	  $sql .= "INNER JOIN {field_data_field_workflow_status} ws ON ws.entity_id = n.nid AND ws.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_order_date} od ON od.entity_id = n.nid and od.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_rx_base_type} bt ON bt.entity_id = n.nid and bt.bundle = 'rx' ";
      $sql .= "INNER JOIN {field_data_field_refill_rx_id} rrx ON rrx.entity_id = n.nid and rrx.bundle = 'rx' ";
      $sql .= "INNER JOIN {users} u ON n.uid = u.uid ";
      //$sql .= "INNER JOIN {users_roles} ur ON u.uid = ur.uid ";
      $sql .= "WHERE YEAR(od.field_order_date_value) = '".$past_month_year."' ";
      $sql .= "AND MONTH(od.field_order_date_value) = '".$past_month."' ";
      $sql .= "AND ws.field_workflow_status_value != 'Cancelled' ";
      $sql .= "AND n.type = 'rx' ";
      $sql .= "AND n.status = 1 ";
      //$sql .= "AND ur.rid IN (13, 11, 8, 5, 3, 4, 18) ";
	  $sql .= "AND u.uid IN (SELECT DISTINCT uid FROM users_roles WHERE rid IN (13, 11, 8, 5, 3, 4, 18)) "; //Pharmacy, Clinical Support, Field Rep, Order Manager, Administrator, Website Manager, Call Center
      $sql .= "GROUP BY bt.field_rx_base_type_value ";
      $results = db_query($sql)->fetchAll();

      if(count($results)>0) {
        foreach($results as $row) {
          if($row->base_type == 'SCIT') {
  		    $staff_scit_refill = $row->total;
          }
          else {
  		    $staff_slit_refill = $row->total;
          }
        }
      }
  
      //update the table
      $sql_insert = "INSERT INTO {dashboard_billing_by_month} (dashboard_year, dashboard_month, staff_scit_refill, staff_slit_refill, staff_rx_refill_last_update) VALUES (:dashboard_year, :dashboard_month, :staff_scit_refill, :staff_slit_refill, :staff_rx_refill_last_update) ";
      $sql_insert .= "ON DUPLICATE KEY UPDATE staff_scit_refill=VALUES(staff_scit_refill), staff_slit_refill=VALUES(staff_slit_refill), staff_rx_refill_last_update='".$time."'";
      $sql_values = array(
        ':dashboard_year' => $past_month_year,
        ':dashboard_month' => $past_month,
        ':staff_scit_refill' => $staff_scit_refill,
        ':staff_slit_refill' => $staff_slit_refill,
        ':staff_rx_refill_last_update' => $time,
      );
      db_query($sql_insert, $sql_values);
    }
    watchdog('dashboard', 'Re-generate rx refills by staff for last 12 months, completed.');

	ctools_add_js('ajax-responder');
	$output = array();
	//$output[] = ctools_modal_command_dismiss();

	$html = '<p style="font-size:16px;text-align:center;margin-bottom:10px;">'.t('Dashboard data has been re-generated successfully.').'</p>';
	$html .= '<div style="text-align:center;margin-top:20px;"><a style="background-color: #2DADDC;color: #FFF;font-size: 14px;padding: 10px 15px;border: 0;margin-top: 10px;" href="#" class="close-btn ctools-close-modal">Close</a></div>';

	$output[] = ajax_command_replace('#dashboard-update-all-form-wrapper', $html);		
  }

  print ajax_render($output);
  exit();
}


?>