<?php
/**
 * Implementation of dashboard_sections_refresh().
 *
 *   Dashboard Sections Auto Refresh
 */
function dashboard_sections_refresh() {
  global $base_url, $user;

  ctools_include('ajax');
  //ctools_include('modal');
  //ctools_modal_add_js();

  ctools_add_js('ajax-responder');
  $output = array();
  
  $ajax = 'nojs';

  //Rx and Store Amount
  $mrdash_rx_order_amount = variable_get('mrdash_rx_order_amount', 0);
  if($mrdash_rx_order_amount) {
    include_once('dashboard.amount.rx.inc');
	$data = dashboard_total_rx_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.amount.order.inc');
	$data = dashboard_total_order_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.amount.monthly.inc');
	$data = dashboard_total_monthly_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.amount.weekly.inc');
	$data = dashboard_total_weekly_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //PO and PO Refund
  $mrdash_po_amount = variable_get('mrdash_po_amount', 0);
  if($mrdash_po_amount) {
    include_once('dashboard.amount.po.inc');
	$data = dashboard_total_po_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.amount.monthly.inc');
	$data = dashboard_total_monthly_po_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.amount.weekly.inc');
	$data = dashboard_total_weekly_po_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //sales amount
  $mrdash_sales_amount = variable_get('mrdash_sales_amount', 0);
  if($mrdash_sales_amount) {
    include_once('dashboard.amount.sales.inc');
	$data = dashboard_daily_total_sales_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.amount.monthly.inc');
	$data = dashboard_monthly_total_sales_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.amount.weekly.inc');
	$data = dashboard_weekly_total_sales_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  
  //Rx Created and Refills
  $mrdash_rx_created_refills = variable_get('mrdash_rx_created_refills', 0);
  if($mrdash_rx_created_refills) {
    include_once('dashboard.created.rx.inc');
	$data = dashboard_total_slit_scit_created_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.refills.rx.inc');
	$data = dashboard_total_slit_scit_refills_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.rx.summary.inc');
	$data = dashboard_rx_slit_vs_scit_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Rx Payments
  $mrdash_rx_payments = variable_get('mrdash_rx_payments', 0);
  if($mrdash_rx_payments) {
    include_once('dashboard.payment.rx.inc');
	$data = dashboard_total_payments_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.payment.summary.inc');
	$data = dashboard_rx_payments_summary_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Store Order Submitted
  $mrdash_order_submitted = variable_get('mrdash_order_submitted', 0);
  if($mrdash_order_submitted) {
    include_once('dashboard.submitted.order.inc');
	$data = dashboard_total_order_submitted_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }
  
  //Rx Pending
  $mrdash_rx_pending = variable_get('mrdash_rx_pending', 0);
  if($mrdash_rx_pending) {
    include_once('dashboard.pending.rx.inc');
	$data = dashboard_total_rx_pending_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Rx Scheduled
  $mrdash_rx_scheduled = variable_get('mrdash_rx_scheduled', 0);
  if($mrdash_rx_scheduled) {
    include_once('dashboard.scheduled.rx.inc');
	$data = dashboard_total_rx_scheduled_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Rx Upcoming Refills
  $mrdash_rx_upcoming_refills = variable_get('mrdash_rx_upcoming_refills', 0);
  if($mrdash_rx_upcoming_refills) {
    include_once('dashboard.refills.rx.inc');
	$data = dashboard_total_upcoming_rx_refills_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Expiring Rx
  $mrdash_expiring_rx = variable_get('mrdash_expiring_rx', 0);
  if($mrdash_expiring_rx) {
    include_once('dashboard.expiring.rx.inc');
	$data = dashboard_total_expiring_rx_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Expiring ARB
  $mrdash_expiring_arb = variable_get('mrdash_expiring_arb', 0);
  if($mrdash_expiring_arb) {
    include_once('dashboard.expiring.arb.inc');
	$data = dashboard_total_expiring_arb_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Expiring Credit Cards
  $mrdash_expiring_cc = variable_get('mrdash_expiring_cc', 0);
  if($mrdash_expiring_cc) {
    include_once('dashboard.expiring.cc.inc');
	$data = dashboard_total_expiring_cc_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Expiring Profile Credit Cards
  $mrdash_expiring_profile_cc = variable_get('mrdash_expiring_profile_cc', 0);
  if($mrdash_expiring_profile_cc) {
    include_once('dashboard.expiring.profile_cc.inc');
	$data = dashboard_total_expiring_profile_cc_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Total Clinics
  $mrdash_total_clinics = variable_get('mrdash_total_clinics', 0);
  if($mrdash_total_clinics) {
    include_once('dashboard.clinic.total.inc');
	$data = dashboard_total_clinics_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Silent Post Summary
  $mrdash_silentpost = variable_get('mrdash_silentpost', 0);
  if($mrdash_silentpost) {
    include_once('dashboard.silentpost.summary.inc');
	$data = dashboard_silent_post_summary_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }


  //Clinic vs Staff
  $mrdash_clinic_vs_staff = variable_get('mrdash_clinic_vs_staff', 0);
  if($mrdash_clinic_vs_staff) {
    include_once('dashboard.created.rx.inc');
	$data = dashboard_clinic_vs_staff_rx_created_daily_refresh($ajax, 1);
	$output = array_merge($output, $data);

	$data = dashboard_clinic_vs_staff_rx_created_monthly_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.refills.rx.inc');
	$data = dashboard_clinic_vs_staff_rx_refill_daily_refresh($ajax, 1);
	$output = array_merge($output, $data);

	$data = dashboard_clinic_vs_staff_rx_refill_monthly_refresh($ajax, 1);
	$output = array_merge($output, $data);


    include_once('dashboard.rx.summary.inc');
	$data = dashboard_clinic_vs_staff_rx_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  // Return the ajax instructions to the browser via ajax_render().
  print ajax_render($output);
  //exit();
  drupal_exit();
}

/**
 * Implementation of dashboard_sections_refresh_home().
 *
 *   Dashboard Sections Auto Refresh for Home Page
 */
function dashboard_sections_refresh_home() {
  global $base_url, $user;

  ctools_include('ajax');
  //ctools_include('modal');
  //ctools_modal_add_js();

  ctools_add_js('ajax-responder');
  $output = array();
  
  $ajax = 'nojs';
  
  //sales

  //Rx and Store Amount
  $mrdash_rx_order_amount = variable_get('mrdash_rx_order_amount', 0);
  if($mrdash_rx_order_amount) {
    include_once('dashboard.amount.monthly.inc');
	$data = dashboard_total_monthly_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.amount.weekly.inc');
	$data = dashboard_total_weekly_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //PO and PO Refund
  $mrdash_po_amount = variable_get('mrdash_po_amount', 0);
  if($mrdash_po_amount) {
    include_once('dashboard.amount.monthly.inc');
	$data = dashboard_total_monthly_po_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.amount.weekly.inc');
	$data = dashboard_total_weekly_po_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //sales amount
  $mrdash_sales_amount = variable_get('mrdash_sales_amount', 0);
  if($mrdash_sales_amount) {
    include_once('dashboard.amount.sales.inc');
	$data = dashboard_daily_total_sales_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.amount.monthly.inc');
	$data = dashboard_monthly_total_sales_amount_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Rx Payments
  $mrdash_rx_payments = variable_get('mrdash_rx_payments', 0);
  if($mrdash_rx_payments) {
    include_once('dashboard.payment.rx.inc');
	$data = dashboard_total_payments_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.payment.summary.inc');
	$data = dashboard_rx_payments_summary_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //rx

  //Store Order Submitted
  $mrdash_order_submitted = variable_get('mrdash_order_submitted', 0);
  if($mrdash_order_submitted) {
    include_once('dashboard.submitted.order.inc');
	$data = dashboard_total_order_submitted_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Rx Pending
  $mrdash_rx_pending = variable_get('mrdash_rx_pending', 0);
  if($mrdash_rx_pending) {
    include_once('dashboard.pending.rx.inc');
	$data = dashboard_total_rx_pending_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Rx Scheduled
  $mrdash_rx_scheduled = variable_get('mrdash_rx_scheduled', 0);
  if($mrdash_rx_scheduled) {
    include_once('dashboard.scheduled.rx.inc');
	$data = dashboard_total_rx_scheduled_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Rx Upcoming Refills
  $mrdash_rx_upcoming_refills = variable_get('mrdash_rx_upcoming_refills', 0);
  if($mrdash_rx_upcoming_refills) {
    include_once('dashboard.refills.rx.inc');
	$data = dashboard_total_upcoming_rx_refills_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Expiring Rx
  $mrdash_expiring_rx = variable_get('mrdash_expiring_rx', 0);
  if($mrdash_expiring_rx) {
    include_once('dashboard.expiring.rx.inc');
	$data = dashboard_total_expiring_rx_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Expiring ARB
  $mrdash_expiring_arb = variable_get('mrdash_expiring_arb', 0);
  if($mrdash_expiring_arb) {
    include_once('dashboard.expiring.arb.inc');
	$data = dashboard_total_expiring_arb_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Expiring Credit Cards
  $mrdash_expiring_cc = variable_get('mrdash_expiring_cc', 0);
  if($mrdash_expiring_cc) {
    include_once('dashboard.expiring.cc.inc');
	$data = dashboard_total_expiring_cc_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Silent Post Summary
  $mrdash_silentpost = variable_get('mrdash_silentpost', 0);
  if($mrdash_silentpost) {
    include_once('dashboard.silentpost.summary.inc');
	$data = dashboard_silent_post_summary_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Clinic vs Staff
  $mrdash_clinic_vs_staff = variable_get('mrdash_clinic_vs_staff', 0);
  if($mrdash_clinic_vs_staff) {
    include_once('dashboard.created.rx.inc');
	$data = dashboard_clinic_vs_staff_rx_created_monthly_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.refills.rx.inc');
	$data = dashboard_clinic_vs_staff_rx_refill_monthly_refresh($ajax, 1);
	$output = array_merge($output, $data);

    include_once('dashboard.rx.summary.inc');
	$data = dashboard_clinic_vs_staff_rx_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }

  //Rx Created and Refills
  $mrdash_rx_created_refills = variable_get('mrdash_rx_created_refills', 0);
  if($mrdash_rx_created_refills) {
    include_once('dashboard.rx.summary.inc');
	$data = dashboard_rx_slit_vs_scit_refresh($ajax, 1);
	$output = array_merge($output, $data);
  }


  // Return the ajax instructions to the browser via ajax_render().
  print ajax_render($output);
  //exit();
  drupal_exit();
}

?>