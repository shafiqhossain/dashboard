<?php

function dashboard_settings_popup($ajax='') {
  if($ajax) {
	ctools_include('ajax');
	ctools_include('modal');
    ctools_modal_add_js();
	
	$form_state = array(
	  'ajax' => TRUE,
	  'title' => t('Settings'),
	);

	// Use ctools to generate ajax instructions for the browser to create
	// a form in a modal popup.
	$output = ctools_modal_form_wrapper('dashboard_settings', $form_state);

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
	return drupal_get_form('dashboard_settings');
  }
}

/**
 * Implementation of dashboard_settings().
 *
 *   Dashboard Settings
 */
function dashboard_settings($form, &$form_state) {
  global $base_url, $user;

  drupal_add_css(drupal_get_path('module', 'dashboard').'/dashboard.css');
  drupal_add_js(drupal_get_path('module', 'dashboard').'/dashboard.js');
  
  ctools_include('ajax');
  ctools_include('modal');
  ctools_modal_add_js();


  $form['dashboard_rx_order_amount'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Rx and Store Amount'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_rx_order_amount']) ? $form_state['values']['dashboard_rx_order_amount'] : variable_get('dashboard_rx_order_amount',0),
  );
  $form['dashboard_po_amount'] = array(
	'#type' => 'checkbox', 
	'#title' => t('PO and PO Refund'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_po_amount']) ? $form_state['values']['dashboard_po_amount'] : variable_get('dashboard_po_amount',0),
  );
  $form['dashboard_sales_amount'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Sales Amount'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_sales_amount']) ? $form_state['values']['dashboard_sales_amount'] : variable_get('dashboard_sales_amount',0),
  );
  $form['dashboard_rx_created_refills'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Rx Created and Refills'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_rx_created_refills']) ? $form_state['values']['dashboard_rx_created_refills'] : variable_get('dashboard_rx_created_refills',0),
  );
  $form['dashboard_rx_payments'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Rx Payments'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_rx_payments']) ? $form_state['values']['dashboard_rx_payments'] : variable_get('dashboard_rx_payments',0),
  );
  $form['dashboard_order_submitted'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Store Order Submitted'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_order_submitted']) ? $form_state['values']['dashboard_order_submitted'] : variable_get('dashboard_order_submitted',0),
  );
  $form['dashboard_rx_pending'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Rx Pending'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_rx_pending']) ? $form_state['values']['dashboard_rx_pending'] : variable_get('dashboard_rx_pending',0),
  );
  $form['dashboard_rx_scheduled'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Rx Scheduled'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_rx_scheduled']) ? $form_state['values']['dashboard_rx_scheduled'] : variable_get('dashboard_rx_scheduled',0),
  );
  $form['dashboard_rx_upcoming_refills'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Rx Upcoming Refills'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_rx_upcoming_refills']) ? $form_state['values']['dashboard_rx_upcoming_refills'] : variable_get('dashboard_rx_upcoming_refills',0),
  );
  $form['dashboard_expiring_rx'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Expiring Rx'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_expiring_rx']) ? $form_state['values']['dashboard_expiring_rx'] : variable_get('dashboard_expiring_rx',0),
  );
  $form['dashboard_expiring_arb'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Expiring ARB'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_expiring_arb']) ? $form_state['values']['dashboard_expiring_arb'] : variable_get('dashboard_expiring_arb',0),
  );
  $form['dashboard_expiring_cc'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Expiring Credit Cards'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_expiring_cc']) ? $form_state['values']['dashboard_expiring_cc'] : variable_get('dashboard_expiring_cc',0),
  );
  $form['dashboard_expiring_profile_cc'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Expiring Profile Credit Cards'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_expiring_profile_cc']) ? $form_state['values']['dashboard_expiring_profile_cc'] : variable_get('dashboard_expiring_profile_cc',0),
  );
  $form['dashboard_total_clinics'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Total Clinics'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_total_clinics']) ? $form_state['values']['dashboard_total_clinics'] : variable_get('dashboard_total_clinics',0),
  );
  $form['dashboard_silentpost'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Silent Post Summary'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_silentpost']) ? $form_state['values']['dashboard_silentpost'] : variable_get('dashboard_silentpost',0),
  );
  $form['dashboard_clinic_vs_staff'] = array(
	'#type' => 'checkbox', 
	'#title' => t('Clinic vs Staff Rx Create/Refills'),
	'#return_value' => 1,
	'#default_value' => isset($form_state['values']['dashboard_clinic_vs_staff']) ? $form_state['values']['dashboard_clinic_vs_staff'] : variable_get('dashboard_clinic_vs_staff',0),
  );
  
  $options = array();
  $options[0] = t('--None--');
  $options[1] = t('01 minutes');
  //$options[2] = t('02 minutes');
  $options[5] = t('05 minutes');
  $options[10] = t('10 minutes');
  $options[15] = t('15 minutes');
  $options[20] = t('20 minutes');
  $options[25] = t('25 minutes');
  $options[30] = t('30 minutes');
  $options[35] = t('35 minutes');
  $options[40] = t('40 minutes');
  $options[45] = t('45 minutes');
  $options[50] = t('50 minutes');
  $options[55] = t('55 minutes');
  $options[60] = t('60 minutes');

  $form['dashboard_refresh_interval'] = array(
	'#type' => 'select',
	'#title' => t('Auto Refresh Interval'),
	'#default_value' => isset($form_state['values']['dashboard_refresh_interval']) ? $form_state['values']['dashboard_refresh_interval'] : variable_get('dashboard_refresh_interval', 15),
	'#options' => $options,
	'#required' => FALSE,
  );

  $form['save'] = array(
	'#type' => 'submit',
	'#value' => t('Save'),
	'#ajax' => array(
		'callback' => 'dashboard_settings_ajax_save_callback',  
		'wrapper' => 'dashboard-settings-form-wrapper',
    	'method' => 'replace',
    	'effect' => 'fade',		
	),
	'#id' => 'dashboard_settings_btn',
	'#name' => 'dashboard_settings_btn',
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

  $form['#prefix'] = '<div id="dashboard-settings-form-wrapper">';
  $form['#suffix'] = '</div>';
  $form['#id'] = 'dashboard_settings_form';

  return $form;
}

/**
 * Submit handler for save
 *
 * @see dashboard_settings()
 */
function dashboard_settings_ajax_save_callback($form, &$form_state) {
  global $base_url, $user;
  
  ctools_include('ajax');
  ctools_include('modal');
  ctools_modal_add_js();

  if (!empty($form_state['executed'])) {
    /** Generate data for today, this week and this month **/

	variable_set('dashboard_rx_order_amount', $form_state['values']['dashboard_rx_order_amount']);
	variable_set('dashboard_po_amount', $form_state['values']['dashboard_po_amount']);
	variable_set('dashboard_sales_amount', $form_state['values']['dashboard_sales_amount']);
	variable_set('dashboard_rx_created_refills', $form_state['values']['dashboard_rx_created_refills']);
	variable_set('dashboard_rx_payments', $form_state['values']['dashboard_rx_payments']);
	variable_set('dashboard_order_submitted', $form_state['values']['dashboard_order_submitted']);
	variable_set('dashboard_rx_pending', $form_state['values']['dashboard_rx_pending']);
	variable_set('dashboard_rx_scheduled', $form_state['values']['dashboard_rx_scheduled']);
	variable_set('dashboard_rx_upcoming_refills', $form_state['values']['dashboard_rx_upcoming_refills']);
	variable_set('dashboard_expiring_rx', $form_state['values']['dashboard_expiring_rx']);
	variable_set('dashboard_expiring_arb', $form_state['values']['dashboard_expiring_arb']);
	variable_set('dashboard_expiring_cc', $form_state['values']['dashboard_expiring_cc']);
	variable_set('dashboard_expiring_profile_cc', $form_state['values']['dashboard_expiring_profile_cc']);
	variable_set('dashboard_total_clinics', $form_state['values']['dashboard_total_clinics']);
	variable_set('dashboard_silentpost', $form_state['values']['dashboard_silentpost']);
    variable_set('dashboard_clinic_vs_staff', $form_state['values']['dashboard_clinic_vs_staff']);
	variable_set('dashboard_refresh_interval', $form_state['values']['dashboard_refresh_interval']);


	ctools_add_js('ajax-responder');
	$output = array();
	//$output[] = ctools_modal_command_dismiss();

	$html = '<p style="font-size:16px;text-align:center;margin-bottom:10px;">'.t('Dashboard settings has been saved successfully. <br />Refresh interval will be changed on next page load.').'</p>';
	$html .= '<div style="text-align:center;margin-top:20px;"><a style="background-color: #2DADDC;color: #FFF;font-size: 14px;padding: 10px 15px;border: 0;margin-top: 10px;" href="#" class="close-btn ctools-close-modal">Close</a></div>';

	$output[] = ajax_command_replace('#dashboard-settings-form-wrapper', $html);		
  }

  print ajax_render($output);
  exit();
}


?>