<?php

function dashboard_clean_all_popup($ajax='') {
  if($ajax) {
	ctools_include('ajax');
	ctools_include('modal');
    ctools_modal_add_js();
	
	$form_state = array(
	  'ajax' => TRUE,
	  'title' => t('Clean All Data'),
	);

	// Use ctools to generate ajax instructions for the browser to create
	// a form in a modal popup.
	$output = ctools_modal_form_wrapper('dashboard_clean_all', $form_state);

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
	return drupal_get_form('dashboard_clean_all');
  }
}

/**
 * Implementation of dashboard_clean_all().
 *
 *   Truncate all data
 */
function dashboard_clean_all($form, &$form_state) {
  global $base_url, $user;

  drupal_add_css(drupal_get_path('module', 'dashboard').'/dashboard.css');
  drupal_add_js(drupal_get_path('module', 'dashboard').'/dashboard.js');
  
  ctools_include('ajax');
  ctools_include('modal');
  ctools_modal_add_js();

  $message  = '<h4>Do you want to clean all data for dashboard?</h4>';
  $message .= '<p>This will delete all data for dashboard. This action can not be undone.</p>';

  $form['results'] = array(
    '#markup' => $message,
    '#prefix' => '<div id="dashboard_list_wrapper">',
    '#suffix' => '</div>',
  );

  $form['save'] = array(
	'#type' => 'submit',
	'#value' => t('Yes, Clean It Up'),
	'#ajax' => array(
		'callback' => 'dashboard_clean_all_ajax_save_callback',  
		'wrapper' => 'dashboard-clean-all-form-wrapper',
    	'method' => 'replace',
    	'effect' => 'fade',		
	),
	'#id' => 'dashboard_clean_all_btn',
	'#name' => 'dashboard_clean_all_btn',
  );
  
  $form['cancel'] = array(
    '#markup' => l(t('No'), '#',
       array(
         'attributes' => array(
           'class' => 'close-btn btn ctools-close-modal'
          ),
         'external' => TRUE
       )
     )
  );

  $form['#prefix'] = '<div id="dashboard-clean-all-form-wrapper">';
  $form['#suffix'] = '</div>';
  $form['#id'] = 'dashboard_clean_all_form';

  return $form;
}

/**
 * Submit handler for save
 *
 * @see dashboard_clean_all()
 */
function dashboard_clean_all_ajax_save_callback($form, &$form_state) {
  global $base_url, $user;
  
  ctools_include('ajax');
  ctools_include('modal');
  ctools_modal_add_js();

  if (!empty($form_state['executed'])) {
	$sql = 'TRUNCATE dashboard_billing_by_date';
	db_query($sql);

	$sql = 'TRUNCATE dashboard_count_by_date';
	db_query($sql);

	$sql = 'TRUNCATE dashboard_billing_by_week';
	db_query($sql);

	$sql = 'TRUNCATE dashboard_billing_by_month';
	db_query($sql);

	ctools_add_js('ajax-responder');
	$output = array();
	//$output[] = ctools_modal_command_dismiss();

	$html = '<p style="font-size:16px;text-align:center;margin-bottom:10px;">'.t('Dashboard data has been cleaned up successful.').'</p>';
	$html .= '<div style="text-align:center;margin-top:20px;"><a style="background-color: #2DADDC;color: #FFF;font-size: 14px;padding: 10px 15px;border: 0;margin-top: 10px;" href="#" class="close-btn ctools-close-modal">Close</a></div>';

	$output[] = ajax_command_replace('#dashboard-clean-all-form-wrapper', $html);		
  }

  print ajax_render($output);
  exit();
}


?>