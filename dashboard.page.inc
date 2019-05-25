<?php
// $Id: dashboard.page.inc,v 1.0 2019/05/01 12:55:37 shafiq Exp $

/**
 * @file
 * Dashboard page callbacks.
 */


/**
 * Page builder for dashboard page
 *
 */
function dashboard_view_page() {
  global $user, $base_url;

  //add ajax lib
  drupal_add_library('system', 'drupal.ajax');
  drupal_add_js('/sites/all/libraries/highcharts/code/highcharts.js');
  drupal_add_js('/sites/all/libraries/highcharts/code/modules/exporting.js');

  drupal_add_css(drupal_get_path('module', 'dashboard').'/dashboard.css');
  drupal_add_js(drupal_get_path('module', 'dashboard').'/dashboard.js');
  
  $today = date('Y-m-d');
  $yesterday = date('Y-m-d', strtotime('-1 days'));
  $last31day = date('Y-m-d', strtotime('-31 days'));

  $this_week = date('W');
  $this_week_month = date('n');
  $this_week_year = date('Y');
  $past_week = date('W', strtotime("-1 week"));
  $past_week_month = date('n', strtotime("-1 week"));
  $past_week_year = date('Y', strtotime("-1 week"));

  $this_month = date('n');
  $this_month_year = date('Y');
  $past_month = date('n', strtotime("-1 month"));
  $past_month_year = date('Y', strtotime("-1 month"));
  
  $this_year = date('Y');
  $past_year = date('Y', strtotime("-1 years"));

  //Today: billing_by_date
  $sql1 = "SELECT * FROM {dashboard_billing_by_date} WHERE DATE_FORMAT(dashboard_date, '%Y-%m-%d')=:dashboard_date LIMIT 1 ";
  $today_billing_by_date = db_query($sql1, array(':dashboard_date' => $today))->fetchObject();

  //Yesterday: billing_by_date
  $sql2 = "SELECT * FROM {dashboard_billing_by_date} WHERE DATE_FORMAT(dashboard_date, '%Y-%m-%d')=:dashboard_date LIMIT 1 ";
  $yesterday_billing_by_date = db_query($sql2, array(':dashboard_date' => $yesterday))->fetchObject();
  
  //This week: billing_by_week
  $sql3 = 'SELECT * FROM {dashboard_billing_by_week} WHERE dashboard_year=:dashboard_year AND  dashboard_month=:dashboard_month AND dashboard_week=:dashboard_week LIMIT 1 ';
  $this_week_billing_by_week = db_query($sql3, array(':dashboard_year' => $this_week_year, ':dashboard_month' => $this_week_month, ':dashboard_week' => $this_week ))->fetchObject();

  //Past week: billing_by_week
  $sql4 = 'SELECT * FROM {dashboard_billing_by_week} WHERE dashboard_year=:dashboard_year AND  dashboard_month=:dashboard_month AND dashboard_week=:dashboard_week LIMIT 1 ';
  $past_week_billing_by_week = db_query($sql4, array(':dashboard_year' => $past_week_year, ':dashboard_month' => $past_week_month, ':dashboard_week' => $past_week ))->fetchObject();

  //This month: billing_by_month
  $sql5 = 'SELECT * FROM {dashboard_billing_by_month} WHERE dashboard_year=:dashboard_year AND dashboard_month=:dashboard_month LIMIT 1 ';
  $this_month_billing_by_month = db_query($sql5, array(':dashboard_year' => $this_month_year, ':dashboard_month' => $this_month ))->fetchObject();

  //Past month: billing_by_month
  $sql6 = 'SELECT * FROM {dashboard_billing_by_month} WHERE dashboard_year=:dashboard_year AND dashboard_month=:dashboard_month LIMIT 1 ';
  $past_month_billing_by_month = db_query($sql6, array(':dashboard_year' => $past_month_year, ':dashboard_month' => $past_month ))->fetchObject();
  
  //Today: count_by_date
  $sql7 = "SELECT * FROM {dashboard_count_by_date} WHERE DATE_FORMAT(dashboard_date, '%Y-%m-%d')=:dashboard_date LIMIT 1 ";
  $today_count_by_date = db_query($sql7, array(':dashboard_date' => $today ))->fetchObject();

  //Yesterday: count_by_date
  $sql8 = "SELECT * FROM {dashboard_count_by_date} WHERE DATE_FORMAT(dashboard_date, '%Y-%m-%d')=:dashboard_date LIMIT 1 ";
  $yesterday_count_by_date = db_query($sql8, array(':dashboard_date' => $yesterday ))->fetchObject();

  //This Year: billing_by_month
  $sql9 = 'SELECT * FROM {dashboard_billing_by_month} WHERE dashboard_year=:dashboard_year ORDER BY dashboard_month ASC ';
  $this_year_billing_by_month = db_query($sql9, array(':dashboard_year' => $this_year))->fetchAll();

  //Past Year: billing_by_month
  $sql13 = 'SELECT * FROM {dashboard_billing_by_month} WHERE dashboard_year=:dashboard_year ORDER BY dashboard_month ASC ';
  $past_year_billing_by_month = db_query($sql13, array(':dashboard_year' => $past_year))->fetchAll();

  //Last 31 Days: billing_by_date
  $sql10 = "SELECT * FROM {dashboard_billing_by_date} WHERE DATE_FORMAT(dashboard_date, '%Y-%m-%d') BETWEEN :dashboard_date1 AND :dashboard_date2 ORDER BY dashboard_date ASC ";
  $last31_billing_by_date = db_query($sql10, array(':dashboard_date1' => $last31day, ':dashboard_date2' => $today))->fetchAll();

  //Last 12 months: billing_by_month
  $last12_billing_by_month = array();
  $dashboard_year = date('Y');
  $total_year_month_count = 0;
  $total_year_month_rec = 12;
  $count = 0;

  While(true) {
    //print $dashboard_year.' - '.$total_year_month_count.' - '.$total_year_month_rec.'<br>';
    $sql11 = 'SELECT * FROM {dashboard_billing_by_month} WHERE dashboard_year=:dashboard_year ORDER BY dashboard_year DESC, dashboard_month DESC LIMIT 0,'.$total_year_month_rec;
    //$sql11 = 'SELECT * FROM {dashboard_billing_by_month} ORDER BY dashboard_year DESC, dashboard_month DESC LIMIT 0,12 ';
    $last12 = db_query($sql11, array(':dashboard_year' => $dashboard_year))->fetchAll();
    //print_r($last12);
    $last12_billing_by_month = array_merge($last12_billing_by_month, $last12);

    $total_year_month_count += count($last12);
    if($total_year_month_count < 12) {
      $total_year_month_rec = 12 - $total_year_month_count;
      $dashboard_year = $dashboard_year - 1;
      ++$count;
    }
    else {
	  break;
    }
    
    if($count>4) break;
  }

  //Last 12 months: billing_by_month
  $chart_last12_billing_by_month = array();
  $dashboard_year = date('Y');
  $total_year_month_count = 0;
  $total_year_month_rec = 12;
  $count = 0;

  While(true) {
    //print $dashboard_year.' - '.$total_year_month_count.' - '.$total_year_month_rec.'<br>';
    $sql12 = 'SELECT * FROM {dashboard_billing_by_month} WHERE dashboard_year=:dashboard_year ORDER BY dashboard_year DESC, dashboard_month DESC LIMIT 0,'.$total_year_month_rec;
    //$sql11 = 'SELECT * FROM {dashboard_billing_by_month} WHERE dashboard_year=:dashboard_year ORDER BY dashboard_year DESC, dashboard_month DESC LIMIT 0,'.$total_year_month_rec;
    $last12 = db_query($sql12, array(':dashboard_year' => $dashboard_year))->fetchAll();
    //print_r($last12);
    $chart_last12_billing_by_month = array_merge($chart_last12_billing_by_month, $last12);

    $total_year_month_count += count($last12);
    if($total_year_month_count < 12) {
      $total_year_month_rec = 12 - $total_year_month_count;
      $dashboard_year = $dashboard_year - 1;
      ++$count;
    }
    else {
	  break;
    }

    if($count>4) break;
  }


  //id: daily-total-rx-amount
  $total_rx_amount_date = date('m/d/Y');
  $date_total_rx_amount = (isset($today_billing_by_date->total_collected_amount) && !empty($today_billing_by_date->total_collected_amount) ? number_format($today_billing_by_date->total_collected_amount,2) : 0.00);
  $date_total_rx_amount2 = (isset($today_billing_by_date->total_collected_amount) && !empty($today_billing_by_date->total_collected_amount) ? $today_billing_by_date->total_collected_amount : 0.00);
  $date_rx_amount_last_update = (isset($today_billing_by_date->collected_amount_last_update) && !empty($today_billing_by_date->collected_amount_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->collected_amount_last_update)) : '');

  $yesterday_total_rx_amount = (isset($yesterday_billing_by_date->total_collected_amount) && !empty($yesterday_billing_by_date->total_collected_amount) ? number_format($yesterday_billing_by_date->total_collected_amount,2) : 0.00);
  $yesterday_total_rx_amount2 = (isset($yesterday_billing_by_date->total_collected_amount) && !empty($yesterday_billing_by_date->total_collected_amount) ? $yesterday_billing_by_date->total_collected_amount : 0.00);
  $status = dashboard_change_status($date_total_rx_amount2, $yesterday_total_rx_amount2);
  $date_total_rx_amount_percent = $status['change'];
  $date_total_rx_amount_marker = $status['marker'];
  
  
  //id: daily-total-order-amount
  $total_order_amount_date = date('m/d/Y');
  $date_total_order_amount = (isset($today_billing_by_date->total_order_amount) && !empty($today_billing_by_date->total_order_amount) ? number_format($today_billing_by_date->total_order_amount,2) : 0.00);
  $date_total_order_amount2 = (isset($today_billing_by_date->total_order_amount) && !empty($today_billing_by_date->total_order_amount) ? $today_billing_by_date->total_order_amount : 0.00);
  $date_order_amount_last_update = (isset($today_billing_by_date->order_amount_last_update) && !empty($today_billing_by_date->order_amount_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->order_amount_last_update)) : '');

  $yesterday_total_order_amount = (isset($yesterday_billing_by_date->total_order_amount) && !empty($yesterday_billing_by_date->total_order_amount) ? number_format($yesterday_billing_by_date->total_order_amount,2) : 0.00);
  $yesterday_total_order_amount2 = (isset($yesterday_billing_by_date->total_order_amount) && !empty($yesterday_billing_by_date->total_order_amount) ? $yesterday_billing_by_date->total_order_amount : 0.00);
  $status = dashboard_change_status($date_total_order_amount2, $yesterday_total_order_amount2);
  $date_total_order_amount_percent = $status['change'];
  $date_total_order_amount_marker = $status['marker'];


  //id: weekly-total-amount
  $total_weekly_amount_week = date('W');
  $weekly_total_rx_amount = (isset($this_week_billing_by_week->total_collected_amount) && !empty($this_week_billing_by_week->total_collected_amount) ? number_format($this_week_billing_by_week->total_collected_amount, 2) : 0.00);
  $weekly_total_rx_amount2 = (isset($this_week_billing_by_week->total_collected_amount) && !empty($this_week_billing_by_week->total_collected_amount) ? $this_week_billing_by_week->total_collected_amount : 0.00);
  $weekly_total_order_amount = (isset($this_week_billing_by_week->total_order_amount) && !empty($this_week_billing_by_week->total_order_amount) ? number_format($this_week_billing_by_week->total_order_amount,2) : 0.00);
  $weekly_total_order_amount2 = (isset($this_week_billing_by_week->total_order_amount) && !empty($this_week_billing_by_week->total_order_amount) ? $this_week_billing_by_week->total_order_amount : 0.00);
  $weekly_rx_amount_last_update = (isset($this_week_billing_by_week->collected_amount_last_update) && !empty($this_week_billing_by_week->collected_amount_last_update) ? date('F j, Y H:i:s', strtotime($this_week_billing_by_week->collected_amount_last_update)) : '');
  
  $past_weekly_total_rx_amount = (isset($past_week_billing_by_week->total_collected_amount) && !empty($past_week_billing_by_week->total_collected_amount) ? number_format($past_week_billing_by_week->total_collected_amount, 2) : 0.00);
  $past_weekly_total_rx_amount2 = (isset($past_week_billing_by_week->total_collected_amount) && !empty($past_week_billing_by_week->total_collected_amount) ? $past_week_billing_by_week->total_collected_amount : 0.00);
  $status = dashboard_change_status($weekly_total_rx_amount2, $past_weekly_total_rx_amount2);
  $weekly_total_rx_amount_percent = $status['change'];
  $weekly_total_rx_amount_marker = $status['marker'];

  $past_weekly_total_order_amount = (isset($past_week_billing_by_week->total_order_amount) && !empty($past_week_billing_by_week->total_order_amount) ? number_format($past_week_billing_by_week->total_order_amount,2) : 0.00);
  $past_weekly_total_order_amount2 = (isset($past_week_billing_by_week->total_order_amount) && !empty($past_week_billing_by_week->total_order_amount) ? $past_week_billing_by_week->total_order_amount : 0.00);
  $status = dashboard_change_status($weekly_total_order_amount2, $past_weekly_total_order_amount2);
  $weekly_total_order_amount_percent = $status['change'];
  $weekly_total_order_amount_marker = $status['marker'];

  
  //id: monthly-total-amount
  $total_monthly_amount_month = date('F');
  $total_monthly_amount_year = date('Y');
  $monthly_total_rx_amount = (isset($this_month_billing_by_month->total_collected_amount) && !empty($this_month_billing_by_month->total_collected_amount) ? number_format($this_month_billing_by_month->total_collected_amount, 2) : 0.00);
  $monthly_total_rx_amount2 = (isset($this_month_billing_by_month->total_collected_amount) && !empty($this_month_billing_by_month->total_collected_amount) ? $this_month_billing_by_month->total_collected_amount : 0.00);
  $monthly_total_order_amount = (isset($this_month_billing_by_month->total_order_amount) && !empty($this_month_billing_by_month->total_order_amount) ? number_format($this_month_billing_by_month->total_order_amount,2) : 0.00);
  $monthly_total_order_amount2 = (isset($this_month_billing_by_month->total_order_amount) && !empty($this_month_billing_by_month->total_order_amount) ? $this_month_billing_by_month->total_order_amount : 0.00);
  $monthly_rx_amount_last_update = (isset($this_month_billing_by_month->collected_amount_last_update) && !empty($this_month_billing_by_month->collected_amount_last_update) ? date('F j, Y H:i:s', strtotime($this_month_billing_by_month->collected_amount_last_update)) : '');

  $past_monthly_total_rx_amount = (isset($past_month_billing_by_month->total_collected_amount) && !empty($past_month_billing_by_month->total_collected_amount) ? number_format($past_month_billing_by_month->total_collected_amount, 2) : 0.00);
  $past_monthly_total_rx_amount2 = (isset($past_month_billing_by_month->total_collected_amount) && !empty($past_month_billing_by_month->total_collected_amount) ? $past_month_billing_by_month->total_collected_amount : 0.00);

  $status = dashboard_change_status($monthly_total_rx_amount2, $past_monthly_total_rx_amount2);
  $monthly_total_rx_amount_percent = $status['change'];
  $monthly_total_rx_amount_marker = $status['marker'];

  $past_monthly_total_order_amount = (isset($past_month_billing_by_month->total_order_amount) && !empty($past_month_billing_by_month->total_order_amount) ? number_format($past_month_billing_by_month->total_order_amount,2) : 0.00);
  $past_monthly_total_order_amount2 = (isset($past_month_billing_by_month->total_order_amount) && !empty($past_month_billing_by_month->total_order_amount) ? $past_month_billing_by_month->total_order_amount : 0.00);
  $status = dashboard_change_status($monthly_total_order_amount2, $past_monthly_total_order_amount2);
  $monthly_total_order_amount_percent = $status['change'];
  $monthly_total_order_amount_marker = $status['marker'];


  //id: daily-total-po-amount
  $total_po_amount_date = date('m/d/Y');
  $daily_total_po_amount = (isset($today_billing_by_date->total_po_amount) && !empty($today_billing_by_date->total_po_amount) ? number_format($today_billing_by_date->total_po_amount,2) : 0.00);
  $daily_total_po_amount2 = (isset($today_billing_by_date->total_po_amount) && !empty($today_billing_by_date->total_po_amount) ? $today_billing_by_date->total_po_amount : 0.00);
  $daily_total_po_created = (isset($today_billing_by_date->total_po_created) && !empty($today_billing_by_date->total_po_created) ? number_format($today_billing_by_date->total_po_created,0) : 0);
  $daily_total_po_created2 = (isset($today_billing_by_date->total_po_created) && !empty($today_billing_by_date->total_po_created) ? $today_billing_by_date->total_po_created : 0);
  $date_po_amount_last_update = (isset($today_billing_by_date->po_amount_last_update) && !empty($today_billing_by_date->po_amount_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->po_amount_last_update)) : '');

  $yesterday_total_po_amount = (isset($yesterday_billing_by_date->total_po_amount) && !empty($yesterday_billing_by_date->total_po_amount) ? number_format($yesterday_billing_by_date->total_po_amount,2) : 0.00);
  $yesterday_total_po_amount2 = (isset($yesterday_billing_by_date->total_po_amount) && !empty($yesterday_billing_by_date->total_po_amount) ? $yesterday_billing_by_date->total_po_amount : 0.00);
  $status = dashboard_change_status($daily_total_po_amount2, $yesterday_total_po_amount2);
  $daily_total_po_amount_percent = $status['change'];
  $daily_total_po_amount_marker = $status['marker'];

  $yesterday_total_po_created = (isset($yesterday_billing_by_date->total_po_created) && !empty($yesterday_billing_by_date->total_po_created) ? number_format($yesterday_billing_by_date->total_po_created,0) : 0);
  $yesterday_total_po_created2 = (isset($yesterday_billing_by_date->total_po_created) && !empty($yesterday_billing_by_date->total_po_created) ? $yesterday_billing_by_date->total_po_created : 0);
  $status = dashboard_change_status($daily_total_po_created2, $yesterday_total_po_created2);
  $daily_total_po_created_percent = $status['change'];
  $daily_total_po_created_marker = $status['marker'];
  
  
  //id: daily-total-po-refund-amount
  $total_po_refund_amount_date = date('m/d/Y');
  $daily_total_po_refund_amount = (isset($today_billing_by_date->total_po_refund_amount) && !empty($today_billing_by_date->total_po_refund_amount) ? number_format($today_billing_by_date->total_po_refund_amount,2) : 0.00);
  $daily_total_po_refund_amount2 = (isset($today_billing_by_date->total_po_refund_amount) && !empty($today_billing_by_date->total_po_refund_amount) ? $today_billing_by_date->total_po_refund_amount : 0.00);
  $daily_total_po_refund_created = (isset($today_billing_by_date->total_po_refund_created) && !empty($today_billing_by_date->total_po_refund_created) ? number_format($today_billing_by_date->total_po_refund_created,0) : 0);
  $daily_total_po_refund_created2 = (isset($today_billing_by_date->total_po_refund_created) && !empty($today_billing_by_date->total_po_refund_created) ? $today_billing_by_date->total_po_refund_created : 0);
  $date_po_refund_amount_last_update = (isset($today_billing_by_date->po_amount_last_update) && !empty($today_billing_by_date->po_amount_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->po_amount_last_update)) : '');

  $yesterday_total_po_refund_amount = (isset($yesterday_billing_by_date->total_po_refund_amount) && !empty($yesterday_billing_by_date->total_po_refund_amount) ? number_format($yesterday_billing_by_date->total_po_refund_amount,2) : 0.00);
  $yesterday_total_po_refund_amount2 = (isset($yesterday_billing_by_date->total_po_refund_amount) && !empty($yesterday_billing_by_date->total_po_refund_amount) ? $yesterday_billing_by_date->total_po_refund_amount : 0.00);
  $status = dashboard_change_status($daily_total_po_refund_amount2, $yesterday_total_po_refund_amount2);
  $daily_total_po_refund_amount_percent = $status['change'];
  $daily_total_po_refund_amount_marker = $status['marker'];

  $yesterday_total_po_refund_created = (isset($yesterday_billing_by_date->total_po_refund_created) && !empty($yesterday_billing_by_date->total_po_refund_created) ? number_format($yesterday_billing_by_date->total_po_refund_created,0) : 0);
  $yesterday_total_po_refund_created2 = (isset($yesterday_billing_by_date->total_po_refund_created) && !empty($yesterday_billing_by_date->total_po_refund_created) ? $yesterday_billing_by_date->total_po_refund_created : 0);
  $status = dashboard_change_status($daily_total_po_refund_created2, $yesterday_total_po_refund_created2);
  $daily_total_po_refund_created_percent = $status['change'];
  $daily_total_po_refund_created_marker = $status['marker'];
  
  
  //id: weekly-total-po-amount
  $total_weekly_po_amount_week = date('W');
  $weekly_total_po_amount = (isset($this_week_billing_by_week->total_po_amount) && !empty($this_week_billing_by_week->total_po_amount) ? number_format($this_week_billing_by_week->total_po_amount, 2) : 0.00);
  $weekly_total_po_amount2 = (isset($this_week_billing_by_week->total_po_amount) && !empty($this_week_billing_by_week->total_po_amount) ? $this_week_billing_by_week->total_po_amount : 0.00);
  $weekly_total_po_refund_amount = (isset($this_week_billing_by_week->total_po_refund_amount) && !empty($this_week_billing_by_week->total_po_refund_amount) ? number_format($this_week_billing_by_week->total_po_refund_amount,2) : 0.00);
  $weekly_total_po_refund_amount2 = (isset($this_week_billing_by_week->total_po_refund_amount) && !empty($this_week_billing_by_week->total_po_refund_amount) ? $this_week_billing_by_week->total_po_refund_amount : 0.00);
  $weekly_po_amount_last_update = (isset($this_week_billing_by_week->po_amount_last_update) && !empty($this_week_billing_by_week->po_amount_last_update) ? date('F j, Y H:i:s', strtotime($this_week_billing_by_week->po_amount_last_update)) : '');
  
  $past_weekly_total_po_amount = (isset($past_week_billing_by_week->total_po_amount) && !empty($past_week_billing_by_week->total_po_amount) ? number_format($past_week_billing_by_week->total_po_amount, 2) : 0.00);
  $past_weekly_total_po_amount2 = (isset($past_week_billing_by_week->total_po_amount) && !empty($past_week_billing_by_week->total_po_amount) ? $past_week_billing_by_week->total_po_amount : 0.00);
  $status = dashboard_change_status($weekly_total_po_amount2, $past_weekly_total_po_amount2);
  $weekly_total_po_amount_percent = $status['change'];
  $weekly_total_po_amount_marker = $status['marker'];

  $past_weekly_total_po_refund_amount = (isset($past_week_billing_by_week->total_po_refund_amount) && !empty($past_week_billing_by_week->total_po_refund_amount) ? number_format($past_week_billing_by_week->total_po_refund_amount,2) : 0.00);
  $past_weekly_total_po_refund_amount2 = (isset($past_week_billing_by_week->total_po_refund_amount) && !empty($past_week_billing_by_week->total_po_refund_amount) ? $past_week_billing_by_week->total_po_refund_amount : 0.00);
  $status = dashboard_change_status($weekly_total_po_refund_amount2, $past_weekly_total_po_refund_amount2);
  $weekly_total_po_refund_amount_percent = $status['change'];
  $weekly_total_po_refund_amount_marker = $status['marker'];


  //id: monthly-total-po-amount
  $total_monthly_po_amount_month = date('F');
  $total_monthly_po_amount_year = date('Y');
  $monthly_total_po_amount = (isset($this_month_billing_by_month->total_po_amount) && !empty($this_month_billing_by_month->total_po_amount) ? number_format($this_month_billing_by_month->total_po_amount, 2) : 0.00);
  $monthly_total_po_amount2 = (isset($this_month_billing_by_month->total_po_amount) && !empty($this_month_billing_by_month->total_po_amount) ? $this_month_billing_by_month->total_po_amount : 0.00);
  $monthly_total_po_refund_amount = (isset($this_month_billing_by_month->total_po_refund_amount) && !empty($this_month_billing_by_month->total_po_refund_amount) ? number_format($this_month_billing_by_month->total_po_refund_amount,2) : 0.00);
  $monthly_total_po_refund_amount2 = (isset($this_month_billing_by_month->total_po_refund_amount) && !empty($this_month_billing_by_month->total_po_refund_amount) ? $this_month_billing_by_month->total_po_refund_amount : 0.00);
  $monthly_po_amount_last_update = (isset($this_month_billing_by_month->po_amount_last_update) && !empty($this_month_billing_by_month->po_amount_last_update) ? date('F j, Y H:i:s', strtotime($this_month_billing_by_month->po_amount_last_update)) : '');

  $past_monthly_total_po_amount = (isset($past_month_billing_by_month->total_po_amount) && !empty($past_month_billing_by_month->total_po_amount) ? number_format($past_month_billing_by_month->total_po_amount, 2) : 0.00);
  $past_monthly_total_po_amount2 = (isset($past_month_billing_by_month->total_po_amount) && !empty($past_month_billing_by_month->total_po_amount) ? $past_month_billing_by_month->total_po_amount : 0.00);
  $status = dashboard_change_status($monthly_total_po_amount2, $past_monthly_total_po_amount2);
  $monthly_total_po_amount_percent = $status['change'];
  $monthly_total_po_amount_marker = $status['marker'];

  $past_monthly_total_po_refund_amount = (isset($past_month_billing_by_month->total_po_refund_amount) && !empty($past_month_billing_by_month->total_po_refund_amount) ? number_format($past_month_billing_by_month->total_po_refund_amount,2) : 0.00);
  $past_monthly_total_po_refund_amount2 = (isset($past_month_billing_by_month->total_po_refund_amount) && !empty($past_month_billing_by_month->total_po_refund_amount) ? $past_month_billing_by_month->total_po_refund_amount : 0.00);
  $status = dashboard_change_status($monthly_total_po_refund_amount2, $past_monthly_total_po_refund_amount2);
  $monthly_total_po_refund_amount_percent = $status['change'];
  $monthly_total_po_refund_amount_marker = $status['marker'];
  

  //id: monthly-chart-rx-amount
  /*
  $total_monthly_rx_amount_year = date('Y');
  $chart_monthly_rx_amounts = '';

  $data = array();
  if(count($this_year_billing_by_month)>0) {
    foreach($this_year_billing_by_month as $row) {
      $data[] = $row->total_collected_amount;
    }
  }
  if(count($data)>0) {
    $chart_monthly_rx_amounts = implode(',', $data);
  }
  */
  $chart_monthly_rx_amounts = '';
  $data = array();
  
  $min_year = date('Y');
  $max_year = date('Y');

  if(count($last12_billing_by_month)>0) {
    foreach($last12_billing_by_month as $row) {
      if($row->dashboard_year < $min_year) {
  	    $min_year = $row->dashboard_year;
      }
      if($row->dashboard_year > $max_year) {
  	    $max_year = $row->dashboard_year;
      }
      
      $monthno = $row->dashboard_month;
      if(strlen($monthno)==1) $monthno = '0'.$monthno;

      $data[$row->dashboard_year.$monthno] = '{name: "'.dashboard_get_monthname($row->dashboard_month).'-'.$row->dashboard_year.'",y: '.$row->total_collected_amount.'}';
    }
    ksort($data);
  }

  if(count($data)>0) {
    $chart_monthly_rx_amounts = implode(',', $data);
  }
  $total_monthly_rx_amount_year = $min_year.' - '.$max_year;


  //id: monthly-chart-order-amount
  /*
  $total_monthly_order_amount_year = date('Y');
  $chart_monthly_order_amounts = '';

  $data = array();
  if(count($this_year_billing_by_month)>0) {
    foreach($this_year_billing_by_month as $row) {
      $data[] = $row->total_order_amount;
    }
  }
  if(count($data)>0) {
    $chart_monthly_order_amounts = implode(',', $data);
  }
  */
  $chart_monthly_order_amounts = '';
  $data = array();
  
  $min_year = date('Y');
  $max_year = date('Y');

  if(count($last12_billing_by_month)>0) {
    foreach($last12_billing_by_month as $row) {
      if($row->dashboard_year < $min_year) {
  	    $min_year = $row->dashboard_year;
      }
      if($row->dashboard_year > $max_year) {
  	    $max_year = $row->dashboard_year;
      }
      
      $monthno = $row->dashboard_month;
      if(strlen($monthno)==1) $monthno = '0'.$monthno;

      $data[$row->dashboard_year.$monthno] = '{name: "'.dashboard_get_monthname($row->dashboard_month).'-'.$row->dashboard_year.'",y: '.$row->total_order_amount.'}';
    }
    ksort($data);
  }

  if(count($data)>0) {
    $chart_monthly_order_amounts = implode(',', $data);
  }
  $total_monthly_order_amount_year = $min_year.' - '.$max_year;


  //id: monthly-chart-rx-and-order
  $prev_year = date('Y', strtotime('-1 years'));
  $current_year = date('Y');

  $chart_compare_monthly_rx_label_prev = 'Rx - '.$prev_year;
  $chart_compare_monthly_rx_label_current = 'Rx - '.$current_year;
  $chart_compare_monthly_order_label_prev = 'Order - '.$prev_year;
  $chart_compare_monthly_order_label_current = 'Order - '.$current_year;
  $chart_compare_monthly_po_label_prev = 'PO - '.$prev_year;
  $chart_compare_monthly_po_label_current = 'PO - '.$current_year;

  $chart_compare_monthly_rx_amounts_prev = '';
  $chart_compare_monthly_rx_amounts_current = '';
  $chart_compare_monthly_order_amounts_prev = '';
  $chart_compare_monthly_order_amounts_current = '';
  $chart_compare_monthly_po_amounts_prev = '';
  $chart_compare_monthly_po_amounts_current = '';

  $rx_data_prev = array();
  $rx_data_current = array();
  $order_data_prev = array();
  $order_data_current = array();
  $po_data_prev = array();
  $po_data_current = array();
  
  //initialize the array
  for($i=1;$i<=12;++$i) {
    $k = $i;
    if(strlen($k)==1) $k = '0'.$k;

    $rx_data_prev[$k] = 0;
    $rx_data_current[$k] = 0;
    $order_data_prev[$k] = 0;
    $order_data_current[$k] = 0;
    $po_data_prev[$k] = 0;
    $po_data_current[$k] = 0;
  }

  //this year
  if(count($this_year_billing_by_month)>0) {
    foreach($this_year_billing_by_month as $row) {
      $monthno = $row->dashboard_month;
      if(strlen($monthno)==1) $monthno = '0'.$monthno;
	  
      $rx_data_current[$monthno] = $row->total_collected_amount;
      $order_data_current[$monthno] = $row->total_order_amount;
      $po_data_current[$monthno] = $row->total_po_amount;
    }
  }
  
  //prev year
  if(count($past_year_billing_by_month)>0) {
    foreach($past_year_billing_by_month as $row) {
      $monthno = $row->dashboard_month;
      if(strlen($monthno)==1) $monthno = '0'.$monthno;
	  
      $rx_data_prev[$monthno] = $row->total_collected_amount;
      $order_data_prev[$monthno] = $row->total_order_amount;
      $po_data_prev[$monthno] = $row->total_po_amount;
    }
  }

  $chart_compare_monthly_rx_amounts_prev = implode(',', $rx_data_prev);
  $chart_compare_monthly_rx_amounts_current = implode(',', $rx_data_current);

  $chart_compare_monthly_order_amounts_prev = implode(',', $order_data_prev);
  $chart_compare_monthly_order_amounts_current = implode(',', $order_data_current);

  $chart_compare_monthly_po_amounts_prev = implode(',', $po_data_prev);
  $chart_compare_monthly_po_amounts_current = implode(',', $po_data_current);

  $chart_compare_monthly_amount_year = $prev_year.' - '.$current_year;


  //id: daily-rx-slit-created
  $total_slit_scit_created_date = date('m/d/Y');
  $date_total_slit_created = (isset($today_billing_by_date->total_slit_created) && !empty($today_billing_by_date->total_slit_created) ? $today_billing_by_date->total_slit_created : 0);
  $date_slit_scit_created_last_update = (isset($today_billing_by_date->rx_create_last_update) && !empty($today_billing_by_date->rx_create_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->rx_create_last_update)) : '');

  $past_date_total_slit_created = (isset($yesterday_billing_by_date->total_slit_created) && !empty($yesterday_billing_by_date->total_slit_created) ? $yesterday_billing_by_date->total_slit_created : 0);
  $status = dashboard_change_status($date_total_slit_created, $past_date_total_slit_created);
  $date_total_slit_created_percent = $status['change'];
  $date_total_slit_created_marker = $status['marker'];

  
  //id: daily-rx-scit-created
  $total_slit_scit_created_date = date('m/d/Y');
  $date_total_scit_created = (isset($today_billing_by_date->total_scit_created) && !empty($today_billing_by_date->total_scit_created) ? $today_billing_by_date->total_scit_created : 0);
  $date_slit_scit_created_last_update = (isset($today_billing_by_date->rx_create_last_update) && !empty($today_billing_by_date->rx_create_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->rx_create_last_update)) : '');

  $past_date_total_scit_created = (isset($yesterday_billing_by_date->total_scit_created) && !empty($yesterday_billing_by_date->total_scit_created) ? $yesterday_billing_by_date->total_scit_created : 0);
  $status = dashboard_change_status($date_total_scit_created, $past_date_total_scit_created);
  $date_total_scit_created_percent = $status['change'];
  $date_total_scit_created_marker = $status['marker'];


  //id: daily-rx-slit-refills
  $total_slit_refills_date = date('m/d/Y');
  $date_total_slit_refills = (isset($today_billing_by_date->total_slit_refill) && !empty($today_billing_by_date->total_slit_refill) ? $today_billing_by_date->total_slit_refill : 0);
  $date_slit_scit_refills_last_update = (isset($today_billing_by_date->rx_refill_last_update) && !empty($today_billing_by_date->rx_refill_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->rx_refill_last_update)) : '');

  $past_date_total_slit_refills = (isset($yesterday_billing_by_date->total_slit_refill) && !empty($yesterday_billing_by_date->total_slit_refill) ? $yesterday_billing_by_date->total_slit_refill : 0);
  $status = dashboard_change_status($date_total_slit_refills, $past_date_total_slit_refills);
  $date_total_slit_refills_percent = $status['change'];
  $date_total_slit_refills_marker = $status['marker'];
  
  
  //id: daily-rx-scit-refills
  $total_scit_refills_date = date('m/d/Y');
  $date_total_scit_refills = (isset($today_billing_by_date->total_scit_refill) && !empty($today_billing_by_date->total_scit_refill) ? $today_billing_by_date->total_scit_refill : 0);
  $date_slit_scit_refills_last_update = (isset($today_billing_by_date->rx_refill_last_update) && !empty($today_billing_by_date->rx_refill_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->rx_refill_last_update)) : '');

  $past_date_total_scit_refills = (isset($yesterday_billing_by_date->total_scit_refill) && !empty($yesterday_billing_by_date->total_scit_refill) ? $yesterday_billing_by_date->total_scit_refill : 0);
  $status = dashboard_change_status($date_total_scit_refills, $past_date_total_scit_refills);
  $date_total_scit_refills_percent = $status['change'];
  $date_total_scit_refills_marker = $status['marker'];

  
  //id: monthly-chart-slit-vs-scit-created
  /*
  $monthly_slit_vs_scit_created_year = date('Y');
  $chart_monthly_compare_slit_created = '';
  $chart_monthly_compare_scit_created = '';

  $slit_data = array();
  $scit_data = array();
  if(count($this_year_billing_by_month)>0) {
    foreach($this_year_billing_by_month as $row) {
      $slit_data[] = $row->total_slit_created;
      $scit_data[] = $row->total_scit_created;
    }
  }
  if(count($slit_data)>0) {
    $chart_monthly_compare_slit_created = implode(',', $slit_data);
  }
  if(count($scit_data)>0) {
    $chart_monthly_compare_scit_created = implode(',', $scit_data);
  }
  */
  $chart_monthly_compare_slit_created = '';
  $chart_monthly_compare_scit_created = '';

  $slit_data = array();
  $scit_data = array();
  
  $min_year = date('Y');
  $max_year = date('Y');

  if(count($last12_billing_by_month)>0) {
    foreach($last12_billing_by_month as $row) {
      if($row->dashboard_year < $min_year) {
  	    $min_year = $row->dashboard_year;
      }
      if($row->dashboard_year > $max_year) {
  	    $max_year = $row->dashboard_year;
      }
      
      $monthno = $row->dashboard_month;
      if(strlen($monthno)==1) $monthno = '0'.$monthno;

      $slit_data[$row->dashboard_year.$monthno] = '{name: "'.dashboard_get_monthname($row->dashboard_month).'-'.$row->dashboard_year.'",y: '.$row->total_slit_created.'}';
      $scit_data[$row->dashboard_year.$monthno] = '{name: "'.dashboard_get_monthname($row->dashboard_month).'-'.$row->dashboard_year.'",y: '.$row->total_scit_created.'}';
    }
    ksort($slit_data);
    ksort($scit_data);
  }


  if(count($slit_data)>0) {
    $chart_monthly_compare_slit_created = implode(',', $slit_data);
  }
  if(count($scit_data)>0) {
    $chart_monthly_compare_scit_created = implode(',', $scit_data);
  }
  
  $monthly_slit_vs_scit_created_year = $min_year.' - '.$max_year;


  //id: monthly-chart-slit-vs-scit-refills
  $current_year = date('Y');
  $prev_year = date('Y', strtotime("-1 years"));
  
  $chart_monthly_compare_slit_refills_label_prev = 'SLIT - '.$prev_year;
  $chart_monthly_compare_slit_refills_label_current = 'SLIT - '.$current_year;
  $chart_monthly_compare_scit_refills_label_prev = 'SCIT - '.$prev_year;
  $chart_monthly_compare_scit_refills_label_current = 'SCIT - '.$current_year;

  $chart_monthly_compare_slit_refills_prev = '';
  $chart_monthly_compare_slit_refills_current = '';
  $chart_monthly_compare_scit_refills_prev = '';
  $chart_monthly_compare_scit_refills_current = '';

  $slit_data_prev = array();
  $slit_data_current = array();
  $scit_data_prev = array();
  $scit_data_current = array();

  //initialize the array
  for($i=1;$i<=12;++$i) {
    $k = $i;
    if(strlen($k)==1) $k = '0'.$k;

    $slit_data_prev[$k] = 0;
    $slit_data_current[$k] = 0;
    $scit_data_prev[$k] = 0;
    $scit_data_current[$k] = 0;
  }
  

  //this year
  if(count($this_year_billing_by_month)>0) {
    foreach($this_year_billing_by_month as $row) {
      $monthno = $row->dashboard_month;
      if(strlen($monthno)==1) $monthno = '0'.$monthno;
	  
      $slit_data_current[$monthno] = $row->total_slit_refill;
      $scit_data_current[$monthno] = $row->total_scit_refill;
    }
  }
  
  //prev year
  if(count($past_year_billing_by_month)>0) {
    foreach($past_year_billing_by_month as $row) {
      $monthno = $row->dashboard_month;
      if(strlen($monthno)==1) $monthno = '0'.$monthno;
	  
      $slit_data_prev[$monthno] = $row->total_slit_refill;
      $scit_data_prev[$monthno] = $row->total_scit_refill;
    }
  }


  $chart_monthly_compare_slit_refills_prev = implode(',', $slit_data_prev);
  $chart_monthly_compare_slit_refills_current = implode(',', $slit_data_current);
  $chart_monthly_compare_scit_refills_prev = implode(',', $scit_data_prev);
  $chart_monthly_compare_scit_refills_current = implode(',', $scit_data_current);
  
  $monthly_slit_vs_scit_refills_year = $prev_year.' - '.$current_year;


  //id: monthly-slit-vs-scit
  $rx_slit_vs_scit_last_update = (isset($today_billing_by_date->rx_create_last_update) && !empty($today_billing_by_date->rx_create_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->rx_create_last_update)) : '');

  $html  = '<table class="slit-vs-scit-comparison-table" cellpadding="5" cellspacing="0">';
  $html .= '<thead>';
  $html .= '  <tr>';
  $html .= '	<th colspan="5">SLIT vs. SCIT</th>';
  $html .= '  </tr>';
  $html .= '  <tr>';
  $html .= '	<th rowspan="2">Month</th>';
  $html .= '    <th colspan="2">SLIT&nbsp;<span title="Click on the figures, will display detail report"><i class="fa fa-info-circle" aria-hidden="true">&nbsp;</i></span></th>';
  $html .= '    <th colspan="2">SCIT&nbsp;<span title="Click on the figures, will display detail report"><i class="fa fa-info-circle" aria-hidden="true">&nbsp;</i></span></th>';
  $html .= '  </tr>';
  $html .= '  <tr>';
  $html .= '	  <th>Created</th>';
  $html .= '	  <th>Refills</th>';
  $html .= '	  <th>Created</th>';
  $html .= '	  <th>Refills</th>';
  $html .= '	</tr>';
  $html .= '</thead>';
  $html .= '<tbody>';


  if(count($last12_billing_by_month)>0) {
	foreach($last12_billing_by_month as $row) {
	    if($row->dashboard_month == 1) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Jan, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 2) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Feb, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 3) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Mar, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 4) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Apr, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 5) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">May, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 6) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Jun, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 7) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Jul, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 8) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Aug, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboarddashboard/report/rx_created_refills/monthly/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 9) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Sep, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 10) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Oct, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 11) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Nov, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 12) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Dec, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/monthly/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	}
  }


  $html .= '</tbody>';
  $html .= '</table>';
  
  $comparison_table_slit_vs_scit = $html;

  //id: daily-rx-successful-payment
  $total_successful_payment_date = date('m/d/Y');
  $date_total_successful_payment = (isset($today_billing_by_date->total_successful_payment) && !empty($today_billing_by_date->total_successful_payment) ? number_format($today_billing_by_date->total_successful_payment,2) : 0.00);
  $date_total_successful_payment2 = (isset($today_billing_by_date->total_successful_payment) && !empty($today_billing_by_date->total_successful_payment) ? $today_billing_by_date->total_successful_payment : 0.00);
  $date_successful_payment_last_update = (isset($today_billing_by_date->rx_payment_last_update) && !empty($today_billing_by_date->rx_payment_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->rx_payment_last_update)) : '');
  
  $past_date_total_successful_payment = (isset($yesterday_billing_by_date->total_successful_payment) && !empty($yesterday_billing_by_date->total_successful_payment) ? number_format($yesterday_billing_by_date->total_successful_payment,2) : 0.00);
  $past_date_total_successful_payment2 = (isset($yesterday_billing_by_date->total_successful_payment) && !empty($yesterday_billing_by_date->total_successful_payment) ? $yesterday_billing_by_date->total_successful_payment : 0.00);
  $status = dashboard_change_status($date_total_successful_payment2, $past_date_total_successful_payment2);
  $date_total_successful_payment_percent = $status['change'];
  $date_total_successful_payment_marker = $status['marker'];


  //id: daily-rx-refund-payment
  $total_refund_payment_date = date('m/d/Y');
  $date_total_refund_payment = (isset($today_billing_by_date->total_refund_payment) && !empty($today_billing_by_date->total_refund_payment) ? number_format($today_billing_by_date->total_refund_payment,2) : 0.00);
  $date_total_refund_payment2 = (isset($today_billing_by_date->total_refund_payment) && !empty($today_billing_by_date->total_refund_payment) ? $today_billing_by_date->total_refund_payment : 0.00);
  $date_refund_payment_last_update = (isset($today_billing_by_date->rx_payment_last_update) && !empty($today_billing_by_date->rx_payment_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->rx_payment_last_update)) : '');

  $past_date_total_refund_payment = (isset($yesterday_billing_by_date->total_refund_payment) && !empty($yesterday_billing_by_date->total_refund_payment) ? number_format($yesterday_billing_by_date->total_refund_payment,2) : 0.00);
  $past_date_total_refund_payment2 = (isset($yesterday_billing_by_date->total_refund_payment) && !empty($yesterday_billing_by_date->total_refund_payment) ? $yesterday_billing_by_date->total_refund_payment : 0.00);
  $status = dashboard_change_status($date_total_refund_payment2, $past_date_total_refund_payment2);
  $date_total_refund_payment_percent = $status['change'];
  $date_total_refund_payment_marker = $status['marker'];
  

  //id: daily-rx-invoice-payment
  $total_invoice_payment_date = date('m/d/Y');
  $date_total_invoice_payment = (isset($today_billing_by_date->total_invoice_payment) && !empty($today_billing_by_date->total_invoice_payment) ? number_format($today_billing_by_date->total_invoice_payment,2) : 0.00);
  $date_total_invoice_payment2 = (isset($today_billing_by_date->total_invoice_payment) && !empty($today_billing_by_date->total_invoice_payment) ? $today_billing_by_date->total_invoice_payment : 0.00);
  $date_invoice_payment_last_update = (isset($today_billing_by_date->rx_payment_last_update) && !empty($today_billing_by_date->rx_payment_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->rx_payment_last_update)) : '');

  $past_date_total_invoice_payment = (isset($yesterday_billing_by_date->total_invoice_payment) && !empty($yesterday_billing_by_date->total_invoice_payment) ? number_format($yesterday_billing_by_date->total_invoice_payment,2) : 0.00);
  $past_date_total_invoice_payment2 = (isset($yesterday_billing_by_date->total_invoice_payment) && !empty($yesterday_billing_by_date->total_invoice_payment) ? $yesterday_billing_by_date->total_invoice_payment : 0.00);
  $status = dashboard_change_status($date_total_invoice_payment2, $past_date_total_invoice_payment2);
  $date_total_invoice_payment_percent = $status['change'];
  $date_total_invoice_payment_marker = $status['marker'];


  //id: daily-rx-denied-payment
  $total_denied_payment_date = date('m/d/Y');
  $date_total_denied_payment = (isset($today_billing_by_date->total_denied_payment) && !empty($today_billing_by_date->total_denied_payment) ? number_format($today_billing_by_date->total_denied_payment,2) : 0.00);
  $date_total_denied_payment2 = (isset($today_billing_by_date->total_denied_payment) && !empty($today_billing_by_date->total_denied_payment) ? $today_billing_by_date->total_denied_payment : 0.00);
  $date_denied_payment_last_update = (isset($today_billing_by_date->rx_payment_last_update) && !empty($today_billing_by_date->rx_payment_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->rx_payment_last_update)) : '');

  $past_date_total_denied_payment = (isset($yesterday_billing_by_date->total_denied_payment) && !empty($yesterday_billing_by_date->total_denied_payment) ? number_format($yesterday_billing_by_date->total_denied_payment,2) : 0.00);
  $past_date_total_denied_payment2 = (isset($yesterday_billing_by_date->total_denied_payment) && !empty($yesterday_billing_by_date->total_denied_payment) ? $yesterday_billing_by_date->total_denied_payment : 0.00);
  $status = dashboard_change_status($date_total_denied_payment2, $past_date_total_denied_payment2);
  $date_total_denied_payment_percent = $status['change'];
  $date_total_denied_payment_marker = $status['marker'];

  
  //id: daily-rx-void-payment
  $total_void_payment_date = date('m/d/Y');
  $date_total_void_payment = (isset($today_billing_by_date->total_void_payment) && !empty($today_billing_by_date->total_void_payment) ? number_format($today_billing_by_date->total_void_payment,2) : 0.00);
  $date_total_void_payment2 = (isset($today_billing_by_date->total_void_payment) && !empty($today_billing_by_date->total_void_payment) ? $today_billing_by_date->total_void_payment : 0.00);
  $date_void_payment_last_update = (isset($today_billing_by_date->rx_payment_last_update) && !empty($today_billing_by_date->rx_payment_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->rx_payment_last_update)) : '');

  $past_date_total_void_payment = (isset($yesterday_billing_by_date->total_void_payment) && !empty($yesterday_billing_by_date->total_void_payment) ? number_format($yesterday_billing_by_date->total_void_payment,2) : 0.00);
  $past_date_total_void_payment2 = (isset($yesterday_billing_by_date->total_void_payment) && !empty($yesterday_billing_by_date->total_void_payment) ? $yesterday_billing_by_date->total_void_payment : 0.00);
  $status = dashboard_change_status($date_total_void_payment2, $past_date_total_void_payment2);
  $date_total_void_payment_percent = $status['change'];
  $date_total_void_payment_marker = $status['marker'];

  
  //id: daily-rx-error-payment
  $total_error_payment_date = date('m/d/Y');
  $date_total_error_payment = (isset($today_billing_by_date->total_error_payment) && !empty($today_billing_by_date->total_error_payment) ? number_format($today_billing_by_date->total_error_payment,2) : 0.00);
  $date_total_error_payment2 = (isset($today_billing_by_date->total_error_payment) && !empty($today_billing_by_date->total_error_payment) ? $today_billing_by_date->total_error_payment : 0.00);
  $date_error_payment_last_update = (isset($today_billing_by_date->rx_payment_last_update) && !empty($today_billing_by_date->rx_payment_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->rx_payment_last_update)) : '');

  $past_date_total_error_payment = (isset($yesterday_billing_by_date->total_error_payment) && !empty($yesterday_billing_by_date->total_error_payment) ? number_format($yesterday_billing_by_date->total_error_payment,2) : 0.00);
  $past_date_total_error_payment2 = (isset($yesterday_billing_by_date->total_error_payment) && !empty($yesterday_billing_by_date->total_error_payment) ? $yesterday_billing_by_date->total_error_payment : 0.00);
  $status = dashboard_change_status($date_total_error_payment2, $past_date_total_error_payment2);
  $date_total_error_payment_percent = $status['change'];
  $date_total_error_payment_marker = $status['marker'];

  //id: daily-sales-amount
  $daily_sales_amount_date = date('m/d/Y');
  $daily_sales_amount = (isset($today_billing_by_date->total_sales_amount) && !empty($today_billing_by_date->total_sales_amount) ? number_format($today_billing_by_date->total_sales_amount,2) : 0.00);
  $daily_sales_amount2 = (isset($today_billing_by_date->total_sales_amount) && !empty($today_billing_by_date->total_sales_amount) ? $today_billing_by_date->total_sales_amount : 0.00);
  $daily_sales_amount_last_update = (isset($today_billing_by_date->total_sales_amount_last_update) && !empty($today_billing_by_date->total_sales_amount_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->total_sales_amount_last_update)) : '');

  $past_daily_sales_amount = (isset($yesterday_billing_by_date->total_sales_amount) && !empty($yesterday_billing_by_date->total_sales_amount) ? number_format($yesterday_billing_by_date->total_sales_amount,2) : 0.00);
  $past_daily_sales_amount2 = (isset($yesterday_billing_by_date->total_sales_amount) && !empty($yesterday_billing_by_date->total_sales_amount) ? $yesterday_billing_by_date->total_sales_amount : 0.00);
  $status = dashboard_change_status($daily_sales_amount2, $past_daily_sales_amount2);
  $daily_sales_amount_percent = $status['change'];
  $daily_sales_amount_marker = $status['marker'];
  
  //id: monthly-sales-amount
  $monthly_sales_amount_month = date('M');
  $monthly_sales_amount_year = date('Y');
  $monthly_sales_amount = (isset($this_month_billing_by_month->total_sales_amount) && !empty($this_month_billing_by_month->total_sales_amount) ? number_format($this_month_billing_by_month->total_sales_amount, 2) : 0.00);
  $monthly_sales_amount2 = (isset($this_month_billing_by_month->total_sales_amount) && !empty($this_month_billing_by_month->total_sales_amount) ? $this_month_billing_by_month->total_sales_amount : 0.00);
  $monthly_sales_amount_last_update = (isset($this_month_billing_by_month->total_sales_amount_last_update) && !empty($this_month_billing_by_month->total_sales_amount_last_update) ? date('F j, Y H:i:s', strtotime($this_month_billing_by_month->total_sales_amount_last_update)) : '');

  $past_monthly_sales_amount = (isset($past_month_billing_by_month->total_sales_amount) && !empty($past_month_billing_by_month->total_sales_amount) ? number_format($past_month_billing_by_month->total_sales_amount, 2) : 0.00);
  $past_monthly_sales_amount2 = (isset($past_month_billing_by_month->total_sales_amount) && !empty($past_month_billing_by_month->total_sales_amount) ? $past_month_billing_by_month->total_sales_amount : 0.00);
  $status = dashboard_change_status($monthly_sales_amount2, $past_monthly_sales_amount2);
  $monthly_sales_amount_percent = $status['change'];
  $monthly_sales_amount_marker = $status['marker'];

  //id: daily-chart-sales-amount
  $total_daily_sales_amount_range = '';
  $chart_daily_sales_amounts = '';

  $data = array();
  $min_date = date('Y-m-d');
  $max_date = date('Y-m-d');
  if(count($last31_billing_by_date)>0) {
    foreach($last31_billing_by_date as $row) {
      if(date('Y-m-d', strtotime($row->dashboard_date)) < $min_date) {
  	    $min_date = date('Y-m-d', strtotime($row->dashboard_date));
      }
      if(date('Y-m-d', strtotime($row->dashboard_date)) > $max_date) {
  	    $max_date = date('Y-m-d', strtotime($row->dashboard_date));
      }
      $data[] = '["'.$row->dashboard_date.'",'.$row->total_sales_amount.']';
    }
  }
  if(count($data)>0) {
    $total_daily_sales_amount_range = date('m/d/Y', strtotime($min_date)).' - '.date('m/d/Y', strtotime($max_date));
    $chart_daily_sales_amounts = implode(',', $data);
  }


  //id: monthly-chart-sales-amount
  $chart_monthly_sales_amounts = '';
  
  $min_year = date('Y');
  $max_year = date('Y');
  $data = array();

  if(count($last12_billing_by_month)>0) {
    foreach($last12_billing_by_month as $row) {
      if($row->dashboard_year < $min_year) {
  	    $min_year = $row->dashboard_year;
      }
      if($row->dashboard_year > $max_year) {
  	    $max_year = $row->dashboard_year;
      }
      
      $monthno = $row->dashboard_month;
      if(strlen($monthno)==1) $monthno = '0'.$monthno;

      $data[$row->dashboard_year.$monthno] = '{name: "'.dashboard_get_monthname($row->dashboard_month).'-'.$row->dashboard_year.'",y: '.$row->total_sales_amount.'}';
    }
    ksort($data);
  }


  if(count($data)>0) {
    $chart_monthly_sales_amounts = implode(',', $data);
  }
  
  $total_monthly_sales_amount_year = $min_year.' - '.$max_year;



  //id: monthly-payments
  $rx_payments_last_update = (isset($today_billing_by_date->rx_payment_last_update) && !empty($today_billing_by_date->rx_payment_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->rx_payment_last_update)) : '');

  $html  = '<table class="rx-payment-comparison-table" cellpadding="5" cellspacing="0">';
  $html .= '<thead>';
  $html .= '  <tr>';
  $html .= '	<th rowspan="2">Month</th>';
  $html .= '    <th colspan="3">Payments Summary&nbsp;<span title="Click on the figures, will display detail report"><i class="fa fa-info-circle" aria-hidden="true">&nbsp;</i></span></th>';
  $html .= '	<th>Total&nbsp;<span title="Total of all rx amount (except declined and refund), store and po amount. Click on the figures, will display detail report"><i class="fa fa-info-circle" aria-hidden="true">&nbsp;</i></span></th>';
  $html .= '  </tr>';
  $html .= '  <tr>';
  $html .= '	<th>Successful</th>';
  $html .= '	<th>Refund</th>';
  $html .= '	<th>Invoice</th>';
  $html .= '	<th>Sales</th>';
  $html .= '  </tr>';
  $html .= '</thead>';
  $html .= '<tbody>';

  if(count($last12_billing_by_month)>0) {
	foreach($last12_billing_by_month as $row) {
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
  
  $rx_payments_comparison = $html;


  //id: daily-order-submitted
  $total_order_submitted_date = date('m/d/Y');
  $date_total_order_submitted = (isset($today_count_by_date->order_submitted) && !empty($today_count_by_date->order_submitted) ? $today_count_by_date->order_submitted : 0);
  $date_order_submitted_last_update = (isset($today_count_by_date->order_last_update) && !empty($today_count_by_date->order_last_update) ? date('F j, Y H:i:s', strtotime($today_count_by_date->order_last_update)) : '');

  $past_date_total_order_submitted = (isset($yesterday_count_by_date->order_submitted) && !empty($yesterday_count_by_date->order_submitted) ? $yesterday_count_by_date->order_submitted : 0);
  $status = dashboard_change_status($date_total_order_submitted, $past_date_total_order_submitted);
  $date_total_order_submitted_percent = $status['change'];
  $date_total_order_submitted_marker = $status['marker'];


  //id: rx-pending
  $total_rx_pending_date = date('m/d/Y');
  $date_total_rx_pending = (isset($today_count_by_date->rx_pending) && !empty($today_count_by_date->rx_pending) ? $today_count_by_date->rx_pending : 0);
  $date_rx_pending_last_update = (isset($today_count_by_date->rx_pending_last_update) && !empty($today_count_by_date->rx_pending_last_update) ? date('F j, Y H:i:s', strtotime($today_count_by_date->rx_pending_last_update)) : '');

  $past_date_total_rx_pending = (isset($yesterday_count_by_date->rx_pending) && !empty($yesterday_count_by_date->rx_pending) ? $yesterday_count_by_date->rx_pending : 0);
  $status = dashboard_change_status($date_total_rx_pending, $past_date_total_rx_pending);
  $date_total_rx_pending_percent = $status['change'];
  $date_total_rx_pending_marker = $status['marker'];


  //id: rx-scheduled
  $total_rx_scheduled_date = date('m/d/Y');
  $date_total_rx_scheduled = (isset($today_count_by_date->rx_scheduled) && !empty($today_count_by_date->rx_scheduled) ? $today_count_by_date->rx_scheduled : 0);
  $date_rx_scheduled_last_update = (isset($today_count_by_date->rx_scheduled_last_update) && !empty($today_count_by_date->rx_scheduled_last_update) ? date('F j, Y H:i:s', strtotime($today_count_by_date->rx_scheduled_last_update)) : '');

  $past_date_total_rx_scheduled = (isset($yesterday_count_by_date->rx_scheduled) && !empty($yesterday_count_by_date->rx_scheduled) ? $yesterday_count_by_date->rx_scheduled : 0);
  $status = dashboard_change_status($date_total_rx_scheduled, $past_date_total_rx_scheduled);
  $date_total_rx_scheduled_percent = $status['change'];
  $date_total_rx_scheduled_marker = $status['marker'];
  
  
  //id: rx-refills
  $total_rx_refills_date = date('m/d/Y');
  $date_total_rx_refills = (isset($today_count_by_date->rx_refills) && !empty($today_count_by_date->rx_refills) ? $today_count_by_date->rx_refills : 0);
  $date_rx_refills_last_update = (isset($today_count_by_date->rx_refills_last_update) && !empty($today_count_by_date->rx_refills_last_update) ? date('F j, Y H:i:s', strtotime($today_count_by_date->rx_refills_last_update)) : '');

  $past_date_total_rx_refills = (isset($yesterday_count_by_date->rx_refills) && !empty($yesterday_count_by_date->rx_refills) ? $yesterday_count_by_date->rx_refills : 0);
  $status = dashboard_change_status($date_total_rx_refills, $past_date_total_rx_refills);
  $date_total_rx_refills_percent = $status['change'];
  $date_total_rx_refills_marker = $status['marker'];


  //id: expiring-rx
  $total_expiring_rx_date = date('m/d/Y');
  $date_total_expiring_rx = (isset($today_count_by_date->rx_expiring) && !empty($today_count_by_date->rx_expiring) ? $today_count_by_date->rx_expiring : 0);
  $date_expiring_rx_last_update = (isset($today_count_by_date->rx_expiring_last_update) && !empty($today_count_by_date->rx_expiring_last_update) ? date('F j, Y H:i:s', strtotime($today_count_by_date->rx_expiring_last_update)) : '');

  $past_date_total_expiring_rx = (isset($yesterday_count_by_date->rx_expiring) && !empty($yesterday_count_by_date->rx_expiring) ? $yesterday_count_by_date->rx_expiring : 0);
  $status = dashboard_change_status($date_total_expiring_rx, $past_date_total_expiring_rx);
  $date_total_expiring_rx_percent = $status['change'];
  $date_total_expiring_rx_marker = $status['marker'];


  //id: expiring-arb
  $total_expiring_arb_date = date('m/d/Y');
  $date_total_expiring_arb = (isset($today_count_by_date->arb_expiring) && !empty($today_count_by_date->arb_expiring) ? $today_count_by_date->arb_expiring : 0);
  $date_expiring_arb_last_update = (isset($today_count_by_date->arb_expiring_last_update) && !empty($today_count_by_date->arb_expiring_last_update) ? date('F j, Y H:i:s', strtotime($today_count_by_date->arb_expiring_last_update)) : '');

  $past_date_total_expiring_arb = (isset($yesterday_count_by_date->arb_expiring) && !empty($yesterday_count_by_date->arb_expiring) ? $yesterday_count_by_date->arb_expiring : 0);
  $status = dashboard_change_status($date_total_expiring_arb, $past_date_total_expiring_arb);
  $date_total_expiring_arb_percent = $status['change'];
  $date_total_expiring_arb_marker = $status['marker'];
  

  //id: expiring-cc
  $total_expiring_cc_date = date('m/d/Y');
  $date_total_expiring_cc = (isset($today_count_by_date->cc_expiring) && !empty($today_count_by_date->cc_expiring) ? $today_count_by_date->cc_expiring : 0);
  $date_expiring_cc_last_update = (isset($today_count_by_date->cc_expiring_last_update) && !empty($today_count_by_date->cc_expiring_last_update) ? date('F j, Y H:i:s', strtotime($today_count_by_date->cc_expiring_last_update)) : '');

  $past_date_total_expiring_cc = (isset($yesterday_count_by_date->cc_expiring) && !empty($yesterday_count_by_date->cc_expiring) ? $yesterday_count_by_date->cc_expiring : 0);
  $status = dashboard_change_status($date_total_expiring_cc, $past_date_total_expiring_cc);
  $date_total_expiring_cc_percent = $status['change'];
  $date_total_expiring_cc_marker = $status['marker'];
  

  //id: expiring-profile-cc
  $total_expiring_profile_cc_date = date('m/d/Y');
  $date_total_expiring_profile_cc = (isset($today_count_by_date->profile_cc_expiring) && !empty($today_count_by_date->profile_cc_expiring) ? $today_count_by_date->profile_cc_expiring : 0);
  $date_expiring_profile_cc_last_update = (isset($today_count_by_date->profile_cc_last_update) && !empty($today_count_by_date->profile_cc_last_update) ? date('F j, Y H:i:s', strtotime($today_count_by_date->profile_cc_last_update)) : '');

  $past_date_total_expiring_profile_cc = (isset($yesterday_count_by_date->profile_cc_expiring) && !empty($yesterday_count_by_date->profile_cc_expiring) ? $yesterday_count_by_date->profile_cc_expiring : 0);
  $status = dashboard_change_status($date_total_expiring_profile_cc, $past_date_total_expiring_profile_cc);
  $date_total_expiring_profile_cc_percent = $status['change'];
  $date_total_expiring_profile_cc_marker = $status['marker'];
  

  //id: clinics-summary
  $total_clinics_date = date('m/d/Y');
  $date_total_clinics = (isset($today_count_by_date->total_clinics) && !empty($today_count_by_date->total_clinics) ? $today_count_by_date->total_clinics : 0);
  $date_total_clinics_last_update = (isset($today_count_by_date->total_clinics_last_update) && !empty($today_count_by_date->total_clinics_last_update) ? date('F j, Y H:i:s', strtotime($today_count_by_date->total_clinics_last_update)) : '');

  $past_date_total_clinics = (isset($yesterday_count_by_date->total_clinics) && !empty($yesterday_count_by_date->total_clinics) ? $yesterday_count_by_date->total_clinics : 0);
  $status = dashboard_change_status($date_total_clinics, $past_date_total_clinics);
  $date_total_clinics_percent = $status['change'];
  $date_total_clinics_percent_marker = $status['marker'];
  

  //id: silent-post-summary
  $silent_post_summary_date = date('Y');
  $silent_post_resolved = (isset($today_count_by_date->silent_post_resolved) && !empty($today_count_by_date->silent_post_resolved) ? $today_count_by_date->silent_post_resolved : 0);
  $silent_post_pending = (isset($today_count_by_date->silent_post_pending) && !empty($today_count_by_date->silent_post_pending) ? $today_count_by_date->silent_post_pending : 0);
  $silent_post_summary_last_update = (isset($today_count_by_date->silent_post_last_update) && !empty($today_count_by_date->silent_post_last_update) ? date('F j, Y H:i:s', strtotime($today_count_by_date->silent_post_last_update)) : '');

  $past_silent_post_resolved = (isset($yesterday_count_by_date->silent_post_resolved) && !empty($yesterday_count_by_date->silent_post_resolved) ? $yesterday_count_by_date->silent_post_resolved : 0);
  $status = dashboard_change_status($silent_post_resolved, $past_silent_post_resolved);
  $silent_post_resolved_percent = $status['change'];
  $silent_post_resolved_marker = $status['marker'];

  $past_silent_post_pending = (isset($yesterday_count_by_date->silent_post_pending) && !empty($yesterday_count_by_date->silent_post_pending) ? $yesterday_count_by_date->silent_post_pending : 0);
  $status = dashboard_change_status($silent_post_pending, $past_silent_post_pending);
  $silent_post_pending_percent = $status['change'];
  $silent_post_pending_marker = $status['marker'];

  //id: daily-clinic-vs-staff-rx-created
  $clinic_vs_staff_rx_created_date = date('m/d/Y');
  $date_clinic_total_slit_created = (isset($today_billing_by_date->clinic_slit_created) && !empty($today_billing_by_date->clinic_slit_created) ? $today_billing_by_date->clinic_slit_created : 0);
  $date_clinic_total_scit_created = (isset($today_billing_by_date->clinic_scit_created) && !empty($today_billing_by_date->clinic_scit_created) ? $today_billing_by_date->clinic_scit_created : 0);
  $date_staff_total_slit_created = (isset($today_billing_by_date->staff_slit_created) && !empty($today_billing_by_date->staff_slit_created) ? $today_billing_by_date->staff_slit_created : 0);
  $date_staff_total_scit_created = (isset($today_billing_by_date->staff_scit_created) && !empty($today_billing_by_date->staff_scit_created) ? $today_billing_by_date->staff_scit_created : 0);

  $past_date_clinic_total_slit_created = (isset($yesterday_billing_by_date->clinic_slit_created) && !empty($yesterday_billing_by_date->clinic_slit_created) ? $yesterday_billing_by_date->clinic_slit_created : 0);
  $status = dashboard_change_status($date_clinic_total_slit_created, $past_date_clinic_total_slit_created);
  $date_clinic_total_slit_created_percent = $status['change'];
  $date_clinic_total_slit_created_marker = $status['marker'];

  $past_date_clinic_total_scit_created = (isset($yesterday_billing_by_date->clinic_scit_created) && !empty($yesterday_billing_by_date->clinic_scit_created) ? $yesterday_billing_by_date->clinic_scit_created : 0);
  $status = dashboard_change_status($date_clinic_total_scit_created, $past_date_clinic_total_scit_created);
  $date_clinic_total_scit_created_percent = $status['change'];
  $date_clinic_total_scit_created_marker = $status['marker'];
 
  $past_date_staff_total_slit_created = (isset($yesterday_billing_by_date->staff_slit_created) && !empty($yesterday_billing_by_date->staff_slit_created) ? $yesterday_billing_by_date->staff_slit_created : 0);
  $status = dashboard_change_status($date_staff_total_slit_created, $past_date_staff_total_slit_created);
  $date_staff_total_slit_created_percent = $status['change'];
  $date_staff_total_slit_created_marker = $status['marker'];
  
  $past_date_staff_total_scit_created = (isset($yesterday_billing_by_date->staff_scit_created) && !empty($yesterday_billing_by_date->staff_scit_created) ? $yesterday_billing_by_date->staff_scit_created : 0);
  $status = dashboard_change_status($date_staff_total_scit_created, $past_date_staff_total_scit_created);
  $date_staff_total_scit_created_percent = $status['change'];
  $date_staff_total_scit_created_marker = $status['marker'];
  
  $daily_clinic_vs_staff_rx_created_last_update = (isset($today_billing_by_date->clinic_rx_create_last_update) && !empty($today_billing_by_date->clinic_rx_create_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->clinic_rx_create_last_update)) : '');

  //id: monthly-clinic-vs-staff-rx-created
  $clinic_vs_staff_rx_created_month = date('F');
  $monthly_clinic_total_slit_created = (isset($this_month_billing_by_month->clinic_slit_created) && !empty($this_month_billing_by_month->clinic_slit_created) ? number_format($this_month_billing_by_month->clinic_slit_created, 0) : 0);
  $monthly_clinic_total_scit_created = (isset($this_month_billing_by_month->clinic_scit_created) && !empty($this_month_billing_by_month->clinic_scit_created) ? number_format($this_month_billing_by_month->clinic_scit_created, 0) : 0);
  $monthly_staff_total_slit_created = (isset($this_month_billing_by_month->staff_slit_created) && !empty($this_month_billing_by_month->staff_slit_created) ? number_format($this_month_billing_by_month->staff_slit_created, 0) : 0);
  $monthly_staff_total_scit_created = (isset($this_month_billing_by_month->staff_scit_created) && !empty($this_month_billing_by_month->staff_scit_created) ? number_format($this_month_billing_by_month->staff_scit_created, 0) : 0);
  
  $past_monthly_clinic_total_slit_created = (isset($past_month_billing_by_month->clinic_slit_created) && !empty($past_month_billing_by_month->clinic_slit_created) ? $past_month_billing_by_month->clinic_slit_created : 0);
  $status = dashboard_change_status($monthly_clinic_total_slit_created, $past_monthly_clinic_total_slit_created);
  $monthly_clinic_total_slit_created_percent = $status['change'];
  $monthly_clinic_total_slit_created_marker = $status['marker'];

  $past_monthly_clinic_total_scit_created = (isset($past_month_billing_by_month->clinic_slit_created) && !empty($past_month_billing_by_month->clinic_slit_created) ? $past_month_billing_by_month->clinic_slit_created : 0);
  $status = dashboard_change_status($monthly_clinic_total_scit_created, $past_monthly_clinic_total_scit_created);
  $monthly_clinic_total_scit_created_percent = $status['change'];
  $monthly_clinic_total_scit_created_marker = $status['marker'];

  $past_monthly_staff_total_slit_created = (isset($past_month_billing_by_month->clinic_slit_created) && !empty($past_month_billing_by_month->clinic_slit_created) ? $past_month_billing_by_month->clinic_slit_created : 0);
  $status = dashboard_change_status($monthly_staff_total_slit_created, $past_monthly_staff_total_slit_created);
  $monthly_staff_total_slit_created_percent = $status['change'];
  $monthly_staff_total_slit_created_marker = $status['marker'];

  $past_monthly_staff_total_scit_created = (isset($past_month_billing_by_month->clinic_slit_created) && !empty($past_month_billing_by_month->clinic_slit_created) ? $past_month_billing_by_month->clinic_slit_created : 0);
  $status = dashboard_change_status($monthly_staff_total_scit_created, $past_monthly_staff_total_scit_created);
  $monthly_staff_total_scit_created_percent = $status['change'];
  $monthly_staff_total_scit_created_marker = $status['marker'];
  
  $monthly_clinic_vs_staff_rx_created_last_update = (isset($today_billing_by_date->clinic_rx_create_last_update) && !empty($today_billing_by_date->clinic_rx_create_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->clinic_rx_create_last_update)) : '');

  //id: daily-clinic-vs-staff-rx-refill
  $clinic_vs_staff_rx_refill_date = date('m/d/Y');
  $date_clinic_total_slit_refill = (isset($today_billing_by_date->clinic_slit_refill) && !empty($today_billing_by_date->clinic_slit_refill) ? $today_billing_by_date->clinic_slit_refill : 0);
  $date_clinic_total_scit_refill = (isset($today_billing_by_date->clinic_scit_refill) && !empty($today_billing_by_date->clinic_scit_refill) ? $today_billing_by_date->clinic_scit_refill : 0);
  $date_staff_total_slit_refill = (isset($today_billing_by_date->staff_slit_refill) && !empty($today_billing_by_date->staff_slit_refill) ? $today_billing_by_date->staff_slit_refill : 0);
  $date_staff_total_scit_refill = (isset($today_billing_by_date->staff_scit_refill) && !empty($today_billing_by_date->staff_scit_refill) ? $today_billing_by_date->staff_scit_refill : 0);

  $past_date_clinic_total_slit_refill = (isset($yesterday_billing_by_date->clinic_slit_refill) && !empty($yesterday_billing_by_date->clinic_slit_refill) ? $yesterday_billing_by_date->clinic_slit_refill : 0);
  $status = dashboard_change_status($date_clinic_total_slit_refill, $past_date_clinic_total_slit_refill);
  $date_clinic_total_slit_refill_percent = $status['change'];
  $date_clinic_total_slit_refill_marker = $status['marker'];

  $past_date_clinic_total_scit_refill = (isset($yesterday_billing_by_date->clinic_scit_refill) && !empty($yesterday_billing_by_date->clinic_scit_refill) ? $yesterday_billing_by_date->clinic_scit_refill : 0);
  $status = dashboard_change_status($date_clinic_total_scit_refill, $past_date_clinic_total_scit_refill);
  $date_clinic_total_scit_refill_percent = $status['change'];
  $date_clinic_total_scit_refill_marker = $status['marker'];
  
  $past_date_staff_total_slit_refill = (isset($yesterday_billing_by_date->staff_slit_refill) && !empty($yesterday_billing_by_date->staff_slit_refill) ? $yesterday_billing_by_date->staff_slit_refill : 0);
  $status = dashboard_change_status($date_staff_total_slit_refill, $past_date_staff_total_slit_refill);
  $date_staff_total_slit_refill_percent = $status['change'];
  $date_staff_total_slit_refill_marker = $status['marker'];
  
  $past_date_staff_total_scit_refill = (isset($yesterday_billing_by_date->staff_scit_refill) && !empty($yesterday_billing_by_date->staff_scit_refill) ? $yesterday_billing_by_date->staff_scit_refill : 0);
  $status = dashboard_change_status($date_staff_total_scit_refill, $past_date_staff_total_scit_refill);
  $date_staff_total_scit_refill_percent = $status['change'];
  $date_staff_total_scit_refill_marker = $status['marker'];
  
  $daily_clinic_vs_staff_rx_refill_last_update = (isset($today_billing_by_date->clinic_rx_refill_last_update) && !empty($today_billing_by_date->clinic_rx_refill_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->clinic_rx_refill_last_update)) : '');


  //id: monthly-clinic-vs-staff-rx-refill
  $clinic_vs_staff_rx_refill_month = date('F');
  
  $monthly_clinic_total_slit_refill = (isset($this_month_billing_by_month->clinic_slit_refill) && !empty($this_month_billing_by_month->clinic_slit_refill) ? number_format($this_month_billing_by_month->clinic_slit_refill, 0) : 0);
  $monthly_clinic_total_scit_refill = (isset($this_month_billing_by_month->clinic_scit_refill) && !empty($this_month_billing_by_month->clinic_scit_refill) ? number_format($this_month_billing_by_month->clinic_scit_refill, 0) : 0);
  $monthly_staff_total_slit_refill = (isset($this_month_billing_by_month->staff_slit_refill) && !empty($this_month_billing_by_month->staff_slit_refill) ? number_format($this_month_billing_by_month->staff_slit_refill, 0) : 0);
  $monthly_staff_total_scit_refill = (isset($this_month_billing_by_month->staff_scit_refill) && !empty($this_month_billing_by_month->staff_scit_refill) ? number_format($this_month_billing_by_month->staff_scit_refill, 0) : 0);
  
  $past_monthly_clinic_total_slit_refill = (isset($past_month_billing_by_month->clinic_slit_refill) && !empty($past_month_billing_by_month->clinic_slit_refill) ? number_format($past_month_billing_by_month->clinic_slit_refill,0) : 0);
  $status = dashboard_change_status($monthly_clinic_total_slit_refill, $past_monthly_clinic_total_slit_refill);
  $monthly_clinic_total_slit_refill_percent = $status['change'];
  $monthly_clinic_total_slit_refill_marker = $status['marker'];

  $past_monthly_clinic_total_scit_refill = (isset($past_month_billing_by_month->clinic_scit_refill) && !empty($past_month_billing_by_month->clinic_scit_refill) ? number_format($past_month_billing_by_month->clinic_scit_refill,0) : 0);
  $status = dashboard_change_status($monthly_clinic_total_scit_refill, $past_monthly_clinic_total_scit_refill);
  $monthly_clinic_total_scit_refill_percent = $status['change'];
  $monthly_clinic_total_scit_refill_marker = $status['marker'];

  $past_monthly_staff_total_slit_refill = (isset($past_month_billing_by_month->staff_slit_refill) && !empty($past_month_billing_by_month->staff_slit_refill) ? number_format($past_month_billing_by_month->staff_slit_refill,0) : 0);
  $status = dashboard_change_status($monthly_staff_total_slit_refill, $past_monthly_staff_total_slit_refill);
  $monthly_staff_total_slit_refill_percent = $status['change'];
  $monthly_staff_total_slit_refill_marker = $status['marker'];

  $past_monthly_staff_total_scit_refill = (isset($past_month_billing_by_month->staff_scit_refill) && !empty($past_month_billing_by_month->staff_scit_refill) ? number_format($past_month_billing_by_month->staff_scit_refill,0) : 0);
  $status = dashboard_change_status($monthly_staff_total_scit_refill, $past_monthly_staff_total_scit_refill);
  $monthly_staff_total_scit_refill_percent = $status['change'];
  $monthly_staff_total_scit_refill_marker = $status['marker'];
  
  $monthly_clinic_vs_staff_rx_refill_last_update = (isset($today_billing_by_date->clinic_rx_refill_last_update) && !empty($today_billing_by_date->clinic_rx_refill_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->clinic_rx_refill_last_update)) : '');


  //id: monthly-chart-clinic-vs-staff-rx
  $monthly_clinic_vs_staff_rx_year = '';
  $chart_monthly_compare_clinic_slit_created = '';
  $chart_monthly_compare_clinic_scit_created = '';
  $chart_monthly_compare_staff_slit_created = '';
  $chart_monthly_compare_staff_scit_created = '';
  $chart_monthly_compare_clinic_slit_refill = '';
  $chart_monthly_compare_clinic_scit_refill = '';
  $chart_monthly_compare_staff_slit_refill = '';
  $chart_monthly_compare_staff_scit_refill = '';

  $data_clinic_slit_created = array();
  $data_clinic_slit_refill = array();
  $data_clinic_scit_created = array();
  $data_clinic_scit_refill = array();

  $data_staff_slit_created = array();
  $data_staff_slit_refill = array();
  $data_staff_scit_created = array();
  $data_staff_scit_refill = array();

  $min_year = date('Y');
  $max_year = date('Y');
  if(count($last12_billing_by_month)>0) {
    foreach($last12_billing_by_month as $row) {
      if($row->dashboard_year < $min_year) {
  	    $min_year = $row->dashboard_year;
      }
      if($row->dashboard_year > $max_year) {
  	    $max_year = $row->dashboard_year;
      }

      $data_clinic_slit_created[] = '["'.$row->dashboard_year.'-'.$row->dashboard_month.'",'.$row->clinic_slit_created.']';
      $data_clinic_slit_refill[] = '["'.$row->dashboard_year.'-'.$row->dashboard_month.'",'.$row->clinic_slit_refill.']';
      $data_clinic_scit_created[] = '["'.$row->dashboard_year.'-'.$row->dashboard_month.'",'.$row->clinic_scit_created.']';
      $data_clinic_scit_refill[] = '["'.$row->dashboard_year.'-'.$row->dashboard_month.'",'.$row->clinic_scit_refill.']';

      $data_staff_slit_created[] = '["'.$row->dashboard_year.'-'.$row->dashboard_month.'",'.$row->staff_slit_created.']';
      $data_staff_slit_refill[] = '["'.$row->dashboard_year.'-'.$row->dashboard_month.'",'.$row->staff_slit_refill.']';
      $data_staff_scit_created[] = '["'.$row->dashboard_year.'-'.$row->dashboard_month.'",'.$row->staff_scit_created.']';
      $data_staff_scit_refill[] = '["'.$row->dashboard_year.'-'.$row->dashboard_month.'",'.$row->staff_scit_refill.']';
    }
  }
  
  $monthly_clinic_vs_staff_rx_year = $min_year.' - '.$max_year;
  
  if(count($data_clinic_slit_created)>0) {
    $chart_monthly_compare_clinic_slit_created = implode(',', $data_clinic_slit_created);
  }
  if(count($data_clinic_slit_refill)>0) {
    $chart_monthly_compare_clinic_slit_refill = implode(',', $data_clinic_slit_refill);
  }
  if(count($data_clinic_scit_created)>0) {
    $chart_monthly_compare_clinic_scit_created = implode(',', $data_clinic_scit_created);
  }
  if(count($data_clinic_scit_refill)>0) {
    $chart_monthly_compare_clinic_scit_refill = implode(',', $data_clinic_scit_refill);
  }

  if(count($data_staff_slit_created)>0) {
    $chart_monthly_compare_staff_slit_created = implode(',', $data_staff_slit_created);
  }
  if(count($data_staff_slit_refill)>0) {
    $chart_monthly_compare_staff_slit_refill = implode(',', $data_staff_slit_refill);
  }
  if(count($data_staff_scit_created)>0) {
    $chart_monthly_compare_staff_scit_created = implode(',', $data_staff_scit_created);
  }
  if(count($data_staff_scit_refill)>0) {
    $chart_monthly_compare_staff_scit_refill = implode(',', $data_staff_scit_refill);
  }


  //id: monthly-compare-clinic-vs-staff-rx
  $comparison_table_clinic_vs_staff_rx = '';
  $clinic_vs_staff_rx_last_update = (isset($today_billing_by_date->clinic_rx_create_last_update) && !empty($today_billing_by_date->clinic_rx_create_last_update) ? date('F j, Y H:i:s', strtotime($today_billing_by_date->clinic_rx_create_last_update)) : '');

  $html  = '<table class="clinic-vs-staff-rx-comparison-table" cellpadding="5" cellspacing="0">';
  $html .= '<caption>Clinic vs. Staff Rx</caption>';
  $html .= '<thead>';
  $html .= '  <tr>';
  $html .= '	<th>&nbsp;</th>';
  $html .= '	<th colspan="4">Clinic&nbsp;<span title="Click on the figures, will display detail report"><i class="fa fa-info-circle" aria-hidden="true">&nbsp;</i></span></th>';
  $html .= '	<th colspan="4">Staff&nbsp;<span title="Click on the figures, will display detail report"><i class="fa fa-info-circle" aria-hidden="true">&nbsp;</i></span></th>';
  $html .= '  </tr>';
  $html .= '  <tr>';
  $html .= '	<th rowspan="2">Month</th>';
  $html .= '    <th colspan="2">SLIT</th>';
  $html .= '    <th colspan="2">SCIT</th>';
  $html .= '    <th colspan="2">SLIT</th>';
  $html .= '    <th colspan="2">SCIT</th>';
  $html .= '  </tr>';
  $html .= '  <tr>';
  $html .= '	<th>Created</th>';
  $html .= '	<th>Refills</th>';
  $html .= '	<th>Created</th>';
  $html .= '	<th>Refills</th>';
  $html .= '	<th>Created</th>';
  $html .= '	<th>Refills</th>';
  $html .= '	<th>Created</th>';
  $html .= '	<th>Refills</th>';
  $html .= '  </tr>';
  $html .= '</thead>';
  $html .= '<tbody>';

  if(count($last12_billing_by_month)>0) {
	foreach($last12_billing_by_month as $row) {
	    if($row->dashboard_month == 1) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Jan, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 2) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Feb, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 3) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Mar, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 4) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Apr, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 5) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">May, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 6) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Jun, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 7) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Jul, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 8) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Aug, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 9) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Sep, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 10) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Oct, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 11) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Nov, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	    else if($row->dashboard_month == 12) {
		  $html .= '<tr>';
		  $html .= '  <td class="month-name">Dec, '.$row->dashboard_year.'</td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0).'</a></td>';
		  $html .= '  <td class="created"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0).'</a></td>';
		  $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/'.$row->dashboard_month.'/'.$row->dashboard_year.'/nojs">'.(isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0).'</a></td>';
		  $html .= '</tr>';
	    }
	}
  }

  $html .= '  </tbody>';
  $html .= '</table>';
  $comparison_table_clinic_vs_staff_rx = $html;


  $dashboard_refresh_time = variable_get('dashboard_refresh_interval', 15);
 

  $variables = array(
	  //id: daily-total-rx-amount
	  'total_rx_amount_date' => $total_rx_amount_date,
	  'date_total_rx_amount' => $date_total_rx_amount,
	  'date_rx_amount_last_update' => $date_rx_amount_last_update,
	  'date_total_rx_amount_percent' => $date_total_rx_amount_percent,
	  'date_total_rx_amount_marker' => $date_total_rx_amount_marker,

	  //id: daily-total-order-amount
	  'total_order_amount_date' => $total_order_amount_date,
	  'date_total_order_amount' => $date_total_order_amount,
	  'date_order_amount_last_update' => $date_order_amount_last_update,
	  'date_total_order_amount_percent' => $date_total_order_amount_percent,
	  'date_total_order_amount_marker' => $date_total_order_amount_marker,

	  //id: weekly-total-amount
	  'total_weekly_amount_week' => $total_weekly_amount_week,
	  'weekly_total_rx_amount' => $weekly_total_rx_amount,
	  'weekly_total_order_amount' => $weekly_total_order_amount,
	  'weekly_rx_amount_last_update' => $weekly_rx_amount_last_update,
	  'weekly_total_rx_amount_percent' => $weekly_total_rx_amount_percent,
	  'weekly_total_rx_amount_marker' => $weekly_total_rx_amount_marker,
	  'weekly_total_order_amount_percent' => $weekly_total_order_amount_percent,
	  'weekly_total_order_amount_marker' => $weekly_total_order_amount_marker,

	  //id: monthly-total-amount
	  'total_monthly_amount_month' => $total_monthly_amount_month,
	  'total_monthly_amount_year' => $total_monthly_amount_year,
	  'monthly_total_rx_amount' => $monthly_total_rx_amount,
	  'monthly_total_order_amount' => $monthly_total_order_amount,
	  'monthly_rx_amount_last_update' => $monthly_rx_amount_last_update,
	  'monthly_total_rx_amount_percent' => $monthly_total_rx_amount_percent,
	  'monthly_total_rx_amount_marker' => $monthly_total_rx_amount_marker,
	  'monthly_total_order_amount_percent' => $monthly_total_order_amount_percent,
	  'monthly_total_order_amount_marker' => $monthly_total_order_amount_marker,

	  //id: daily-total-po-amount
	  'total_po_amount_date' => $total_po_amount_date,
	  'daily_total_po_amount' => $daily_total_po_amount,
	  'daily_total_po_created' => $daily_total_po_created,
	  'date_po_amount_last_update' => $date_po_amount_last_update,
	  'daily_total_po_amount_percent' => $daily_total_po_amount_percent,
	  'daily_total_po_amount_marker' => $daily_total_po_amount_marker,
	  'daily_total_po_created_percent' => $daily_total_po_created_percent,
	  'daily_total_po_created_marker' => $daily_total_po_created_marker,

	  //id: daily-total-po-refund-amount
	  'total_po_refund_amount_date' => $total_po_refund_amount_date,
	  'daily_total_po_refund_amount' => $daily_total_po_refund_amount,
	  'daily_total_po_refund_created' => $daily_total_po_refund_created,
	  'date_po_refund_amount_last_update' => $date_po_refund_amount_last_update,
	  'daily_total_po_refund_amount_percent' => $daily_total_po_refund_amount_percent,
	  'daily_total_po_refund_amount_marker' => $daily_total_po_refund_amount_marker,
	  'daily_total_po_refund_created_percent' => $daily_total_po_refund_created_percent,
	  'daily_total_po_refund_created_marker' => $daily_total_po_refund_created_marker,

	  //id: weekly-total-po-amount
	  'total_weekly_po_amount_week' => $total_weekly_po_amount_week,
	  'weekly_total_po_amount' => $weekly_total_po_amount,
	  'weekly_total_po_refund_amount' => $weekly_total_po_refund_amount,
	  'weekly_po_amount_last_update' => $weekly_po_amount_last_update,
	  'weekly_total_po_amount_percent' => $weekly_total_po_amount_percent,
	  'weekly_total_po_amount_marker' => $weekly_total_po_amount_marker,
	  'weekly_total_po_refund_amount_percent' => $weekly_total_po_refund_amount_percent,
	  'weekly_total_po_refund_amount_marker' => $weekly_total_po_refund_amount_marker,

	  //id: monthly-total-po-amount
	  'total_monthly_po_amount_month' => $total_monthly_po_amount_month,
	  'total_monthly_po_amount_year' => $total_monthly_po_amount_year,
	  'monthly_total_po_amount' => $monthly_total_po_amount,
	  'monthly_total_po_refund_amount' => $monthly_total_po_refund_amount,
	  'monthly_po_amount_last_update' => $monthly_po_amount_last_update,
	  'monthly_total_po_amount_percent' => $monthly_total_po_amount_percent,
	  'monthly_total_po_amount_marker' => $monthly_total_po_amount_marker,
	  'monthly_total_po_refund_amount_percent' => $monthly_total_po_refund_amount_percent,
	  'monthly_total_po_refund_amount_marker' => $monthly_total_po_refund_amount_marker,

	  //id: monthly-chart-rx-amount
	  'total_monthly_rx_amount_year' => $total_monthly_rx_amount_year,
	  'chart_monthly_rx_amounts' => $chart_monthly_rx_amounts,

	  //id: monthly-chart-order-amount
	  'total_monthly_order_amount_year' => $total_monthly_order_amount_year,
	  'chart_monthly_order_amounts' => $chart_monthly_order_amounts,

	  //id: monthly-chart-rx-and-order
	  'chart_compare_monthly_amount_year' => $chart_compare_monthly_amount_year,
	  'chart_compare_monthly_rx_label_prev' => $chart_compare_monthly_rx_label_prev,
	  'chart_compare_monthly_rx_amounts_prev' => $chart_compare_monthly_rx_amounts_prev,
	  'chart_compare_monthly_rx_label_current' => $chart_compare_monthly_rx_label_current,
	  'chart_compare_monthly_rx_amounts_current' => $chart_compare_monthly_rx_amounts_current,
	  'chart_compare_monthly_order_label_prev' => $chart_compare_monthly_order_label_prev,
	  'chart_compare_monthly_order_amounts_prev' => $chart_compare_monthly_order_amounts_prev,
	  'chart_compare_monthly_order_label_current' => $chart_compare_monthly_order_label_current,
	  'chart_compare_monthly_order_amounts_current' => $chart_compare_monthly_order_amounts_current,
	  'chart_compare_monthly_po_label_prev' => $chart_compare_monthly_po_label_prev,
	  'chart_compare_monthly_po_amounts_prev' => $chart_compare_monthly_po_amounts_prev,
	  'chart_compare_monthly_po_label_current' => $chart_compare_monthly_po_label_current,
	  'chart_compare_monthly_po_amounts_current' => $chart_compare_monthly_po_amounts_current,


	  //id: daily-rx-slit-created
	  'total_slit_scit_created_date' => $total_slit_scit_created_date,
	  'date_total_slit_created' => $date_total_slit_created,
	  'date_slit_scit_created_last_update' => $date_slit_scit_created_last_update,
	  'date_total_slit_created_percent' => $date_total_slit_created_percent,
	  'date_total_slit_created_marker' => $date_total_slit_created_marker,

	  //id: daily-rx-scit-created
	  'total_slit_scit_created_date' => $total_slit_scit_created_date,
	  'date_total_scit_created' => $date_total_scit_created,
	  'date_slit_scit_created_last_update' => $date_slit_scit_created_last_update,
	  'date_total_scit_created_percent' => $date_total_scit_created_percent,
	  'date_total_scit_created_marker' => $date_total_scit_created_marker,

	  //id: daily-rx-slit-refills
	  'total_slit_refills_date' => $total_slit_refills_date,
	  'date_total_slit_refills' => $date_total_slit_refills,
	  'date_slit_scit_refills_last_update' => $date_slit_scit_refills_last_update,
	  'date_total_slit_refills_percent' => $date_total_slit_refills_percent,
	  'date_total_slit_refills_marker' => $date_total_slit_refills_marker,

	  //id: daily-rx-scit-refills
	  'total_scit_refills_date' => $total_scit_refills_date,
	  'date_total_scit_refills' => $date_total_scit_refills,
	  'date_slit_scit_refills_last_update' => $date_slit_scit_refills_last_update,
	  'date_total_scit_refills_percent' => $date_total_scit_refills_percent,
	  'date_total_scit_refills_marker' => $date_total_scit_refills_marker,

	  //id: monthly-chart-slit-vs-scit-created
	  'monthly_slit_vs_scit_created_year' => $monthly_slit_vs_scit_created_year,
	  'chart_monthly_compare_slit_created' => $chart_monthly_compare_slit_created,
	  'chart_monthly_compare_scit_created' => $chart_monthly_compare_scit_created,

	  //id: monthly-chart-slit-vs-scit-refills
	  'monthly_slit_vs_scit_refills_year' => $monthly_slit_vs_scit_refills_year,
  	  'chart_monthly_compare_slit_refills_label_prev' => $chart_monthly_compare_slit_refills_label_prev,
  	  'chart_monthly_compare_slit_refills_label_current' => $chart_monthly_compare_slit_refills_label_current,
  	  'chart_monthly_compare_scit_refills_label_prev' => $chart_monthly_compare_scit_refills_label_prev,
  	  'chart_monthly_compare_scit_refills_label_current' => $chart_monthly_compare_scit_refills_label_current,
  	  'chart_monthly_compare_slit_refills_prev' => $chart_monthly_compare_slit_refills_prev,
  	  'chart_monthly_compare_slit_refills_current' => $chart_monthly_compare_slit_refills_current,
  	  'chart_monthly_compare_scit_refills_prev' => $chart_monthly_compare_scit_refills_prev,
  	  'chart_monthly_compare_scit_refills_current' => $chart_monthly_compare_scit_refills_current,

	  //id: monthly-slit-vs-scit
	  'rx_slit_vs_scit_last_update' => $rx_slit_vs_scit_last_update,
	  'comparison_table_slit_vs_scit' => $comparison_table_slit_vs_scit,

	  //id: daily-rx-successful-payment
	  'total_successful_payment_date' => $total_successful_payment_date,
	  'date_total_successful_payment' => $date_total_successful_payment,
	  'date_successful_payment_last_update' => $date_successful_payment_last_update,
	  'date_total_successful_payment_percent' => $date_total_successful_payment_percent,
	  'date_total_successful_payment_marker' => $date_total_successful_payment_marker,

	  //id: daily-rx-refund-payment
	  'total_refund_payment_date' => $total_refund_payment_date,
	  'date_total_refund_payment' => $date_total_refund_payment,
	  'date_refund_payment_last_update' => $date_refund_payment_last_update,
	  'date_total_refund_payment_percent' => $date_total_refund_payment_percent,
	  'date_total_refund_payment_marker' => $date_total_refund_payment_marker,

	  //id: daily-rx-invoice-payment
	  'total_invoice_payment_date' => $total_invoice_payment_date,
	  'date_total_invoice_payment' => $date_total_invoice_payment,
	  'date_invoice_payment_last_update' => $date_invoice_payment_last_update,
	  'date_total_invoice_payment_percent' => $date_total_invoice_payment_percent,
	  'date_total_invoice_payment_marker' => $date_total_invoice_payment_marker,

	  //id: daily-rx-denied-payment
	  'total_denied_payment_date' => $total_denied_payment_date,
	  'date_total_denied_payment' => $date_total_denied_payment,
	  'date_denied_payment_last_update' => $date_denied_payment_last_update,
	  'date_total_denied_payment_percent' => $date_total_denied_payment_percent,
	  'date_total_denied_payment_marker' => $date_total_denied_payment_marker,

	  //id: daily-rx-void-payment
	  'total_void_payment_date' => $total_void_payment_date,
	  'date_total_void_payment' => $date_total_void_payment,
	  'date_void_payment_last_update' => $date_void_payment_last_update,
	  'date_total_void_payment_percent' => $date_total_void_payment_percent,
	  'date_total_void_payment_marker' => $date_total_void_payment_marker,

	  //id: daily-rx-error-payment
	  'total_error_payment_date' => $total_error_payment_date,
	  'date_total_error_payment' => $date_total_error_payment,
	  'date_error_payment_last_update' => $date_error_payment_last_update,
	  'date_total_error_payment_percent' => $date_total_error_payment_percent,
	  'date_total_error_payment_marker' => $date_total_error_payment_marker,

	  //id: daily-sales-amount
	  'daily_sales_amount_date' => $daily_sales_amount_date,
	  'daily_sales_amount' => $daily_sales_amount,
	  'daily_sales_amount_last_update' => $daily_sales_amount_last_update,
	  'daily_sales_amount_percent' => $daily_sales_amount_percent,
	  'daily_sales_amount_marker' => $daily_sales_amount_marker,

	  //id: monthly-sales-amount
	  'monthly_sales_amount_month' => $monthly_sales_amount_month,
	  'monthly_sales_amount_year' => $monthly_sales_amount_year,
	  'monthly_sales_amount' => $monthly_sales_amount,
	  'monthly_sales_amount_last_update' => $monthly_sales_amount_last_update,
	  'monthly_sales_amount_percent' => $monthly_sales_amount_percent,
	  'monthly_sales_amount_marker' => $monthly_sales_amount_marker,

	  //id: daily-chart-sales-amount
	  'total_daily_sales_amount_range' => $total_daily_sales_amount_range,
	  'chart_daily_sales_amounts' => $chart_daily_sales_amounts,

	  //id: monthly-chart-sales-amount
	  'total_monthly_sales_amount_year' => $total_monthly_sales_amount_year,
	  'chart_monthly_sales_amounts' => $chart_monthly_sales_amounts,

	  //id: monthly-payments
	  'rx_payments_last_update' => $rx_payments_last_update,
	  'rx_payments_comparison' => $rx_payments_comparison,

	  //id: daily-order-submitted
	  'total_order_submitted_date' => $total_order_submitted_date,
	  'date_total_order_submitted' => $date_total_order_submitted,
	  'date_order_submitted_last_update' => $date_order_submitted_last_update,
	  'date_total_order_submitted_percent' => $date_total_order_submitted_percent,
	  'date_total_order_submitted_marker' => $date_total_order_submitted_marker,

	  //id: rx-pending
	  'total_rx_pending_date' => $total_rx_pending_date,
	  'date_total_rx_pending' => $date_total_rx_pending,
	  'date_rx_pending_last_update' => $date_rx_pending_last_update,
	  'date_total_rx_pending_percent' => $date_total_rx_pending_percent,
	  'date_total_rx_pending_marker' => $date_total_rx_pending_marker,

	  //id: rx-scheduled
	  'total_rx_scheduled_date' => $total_rx_scheduled_date,
	  'date_total_rx_scheduled' => $date_total_rx_scheduled,
	  'date_rx_scheduled_last_update' => $date_rx_scheduled_last_update,
	  'date_total_rx_scheduled_percent' => $date_total_rx_scheduled_percent,
	  'date_total_rx_scheduled_marker' => $date_total_rx_scheduled_marker,

	  //id: rx-refills
	  'total_rx_refills_date' => $total_rx_refills_date,
	  'date_total_rx_refills' => $date_total_rx_refills,
	  'date_rx_refills_last_update' => $date_rx_refills_last_update,
	  'date_total_rx_refills_percent' => $date_total_rx_refills_percent,
	  'date_total_rx_refills_marker' => $date_total_rx_refills_marker,

	  //id: expiring-rx
	  'total_expiring_rx_date' => $total_expiring_rx_date,
	  'date_total_expiring_rx' => $date_total_expiring_rx,
	  'date_expiring_rx_last_update' => $date_expiring_rx_last_update,
	  'date_total_expiring_rx_percent' => $date_total_expiring_rx_percent,
	  'date_total_expiring_rx_marker' => $date_total_expiring_rx_marker,

	  //id: expiring-arb
	  'total_expiring_arb_date' => $total_expiring_arb_date,
	  'date_total_expiring_arb' => $date_total_expiring_arb,
	  'date_expiring_arb_last_update' => $date_expiring_arb_last_update,
	  'date_total_expiring_arb_percent' => $date_total_expiring_arb_percent,
	  'date_total_expiring_arb_marker' => $date_total_expiring_arb_marker,

	  //id: expiring-cc
	  'total_expiring_cc_date' => $total_expiring_cc_date,
	  'date_total_expiring_cc' => $date_total_expiring_cc,
	  'date_expiring_cc_last_update' => $date_expiring_cc_last_update,
	  'date_total_expiring_cc_percent' => $date_total_expiring_cc_percent,
	  'date_total_expiring_cc_marker' => $date_total_expiring_cc_marker,

	  //id: expiring-profile-cc
	  'total_expiring_profile_cc_date' => $total_expiring_profile_cc_date,
	  'date_total_expiring_profile_cc' => $date_total_expiring_profile_cc,
	  'date_expiring_profile_cc_last_update' => $date_expiring_profile_cc_last_update,
	  'date_total_expiring_profile_cc_percent' => $date_total_expiring_profile_cc_percent,
	  'date_total_expiring_profile_cc_marker' => $date_total_expiring_profile_cc_marker,

	  //id: clinics-summary
	  'total_clinics_date' => $total_clinics_date,
	  'date_total_clinics' => $date_total_clinics,
	  'date_total_clinics_last_update' => $date_total_clinics_last_update,
	  'date_total_clinics_percent' => $date_total_clinics_percent,
	  'date_total_clinics_percent_marker' => $date_total_clinics_percent_marker,

	  //id: silent-post-summary
	  'silent_post_summary_date' => $silent_post_summary_date,
	  'silent_post_resolved' => $silent_post_resolved,
	  'silent_post_pending' => $silent_post_pending,
	  'silent_post_summary_last_update' => $silent_post_summary_last_update,
	  'silent_post_resolved_percent' => $silent_post_resolved_percent,
	  'silent_post_resolved_marker' => $silent_post_resolved_marker,
	  'silent_post_pending_percent' => $silent_post_pending_percent,
	  'silent_post_pending_marker' => $silent_post_pending_marker,

	  //id: daily-clinic-vs-staff-rx-created
	  'clinic_vs_staff_rx_created_date' => $clinic_vs_staff_rx_created_date,
	  'date_clinic_total_slit_created' => $date_clinic_total_slit_created,
	  'date_clinic_total_scit_created' => $date_clinic_total_scit_created,
	  'date_staff_total_slit_created' => $date_staff_total_slit_created,
	  'date_staff_total_scit_created' => $date_staff_total_scit_created,
	  'date_clinic_total_slit_created_percent' => $date_clinic_total_slit_created_percent,
	  'date_clinic_total_slit_created_marker' => $date_clinic_total_slit_created_marker,
	  'date_clinic_total_scit_created_percent' => $date_clinic_total_scit_created_percent,
	  'date_clinic_total_scit_created_marker' => $date_clinic_total_scit_created_marker,
	  'date_staff_total_slit_created_percent' => $date_staff_total_slit_created_percent,
	  'date_staff_total_slit_created_marker' => $date_staff_total_slit_created_marker,
	  'date_staff_total_scit_created_percent' => $date_staff_total_scit_created_percent,
	  'date_staff_total_scit_created_marker' => $date_staff_total_scit_created_marker,
	  'daily_clinic_vs_staff_rx_created_last_update' => $daily_clinic_vs_staff_rx_created_last_update,

	  //id: monthly-clinic-vs-staff-rx-created
	  'clinic_vs_staff_rx_created_month' => $clinic_vs_staff_rx_created_month,
	  'monthly_clinic_total_slit_created' => $monthly_clinic_total_slit_created,
	  'monthly_clinic_total_scit_created' => $monthly_clinic_total_scit_created,
	  'monthly_staff_total_slit_created' => $monthly_staff_total_slit_created,
	  'monthly_staff_total_scit_created' => $monthly_staff_total_scit_created,
	  'monthly_clinic_total_slit_created_percent' => $monthly_clinic_total_slit_created_percent,
	  'monthly_clinic_total_slit_created_marker' => $monthly_clinic_total_slit_created_marker,
	  'monthly_clinic_total_scit_created_percent' => $monthly_clinic_total_scit_created_percent,
	  'monthly_clinic_total_scit_created_marker' => $monthly_clinic_total_scit_created_marker,
	  'monthly_staff_total_slit_created_percent' => $monthly_staff_total_slit_created_percent,
	  'monthly_staff_total_slit_created_marker' => $monthly_staff_total_slit_created_marker,
	  'monthly_staff_total_scit_created_percent' => $monthly_staff_total_scit_created_percent,
	  'monthly_staff_total_scit_created_marker' => $monthly_staff_total_scit_created_marker,
	  'monthly_clinic_vs_staff_rx_created_last_update' => $monthly_clinic_vs_staff_rx_created_last_update,

	  //id: daily-clinic-vs-staff-rx-refill
	  'clinic_vs_staff_rx_refill_date' => $clinic_vs_staff_rx_refill_date,
	  'date_clinic_total_slit_refill' => $date_clinic_total_slit_refill,
	  'date_clinic_total_scit_refill' => $date_clinic_total_scit_refill,
	  'date_staff_total_slit_refill' => $date_staff_total_slit_refill,
	  'date_staff_total_scit_refill' => $date_staff_total_scit_refill,
	  'date_clinic_total_slit_refill_percent' => $date_clinic_total_slit_refill_percent,
	  'date_clinic_total_slit_refill_marker' => $date_clinic_total_slit_refill_marker,
	  'date_clinic_total_scit_refill_percent' => $date_clinic_total_scit_refill_percent,
	  'date_clinic_total_scit_refill_marker' => $date_clinic_total_scit_refill_marker,
	  'date_staff_total_slit_refill_percent' => $date_staff_total_slit_refill_percent,
	  'date_staff_total_slit_refill_marker' => $date_staff_total_slit_refill_marker,
	  'date_staff_total_scit_refill_percent' => $date_staff_total_scit_refill_percent,
	  'date_staff_total_scit_refill_marker' => $date_staff_total_scit_refill_marker,
	  'daily_clinic_vs_staff_rx_refill_last_update' => $daily_clinic_vs_staff_rx_refill_last_update,

	  //id: monthly-clinic-vs-staff-rx-refill
	  'clinic_vs_staff_rx_refill_month' => $clinic_vs_staff_rx_refill_month,
	  'monthly_clinic_total_slit_refill' => $monthly_clinic_total_slit_refill,
	  'monthly_clinic_total_scit_refill' => $monthly_clinic_total_scit_refill,
	  'monthly_staff_total_slit_refill' => $monthly_staff_total_slit_refill,
	  'monthly_staff_total_scit_refill' => $monthly_staff_total_scit_refill,
	  'monthly_clinic_total_slit_refill_percent' => $monthly_clinic_total_slit_refill_percent,
	  'monthly_clinic_total_slit_refill_marker' => $monthly_clinic_total_slit_refill_marker,
	  'monthly_clinic_total_scit_refill_percent' => $monthly_clinic_total_scit_refill_percent,
	  'monthly_clinic_total_scit_refill_marker' => $monthly_clinic_total_scit_refill_marker,
	  'monthly_staff_total_slit_refill_percent' => $monthly_staff_total_slit_refill_percent,
	  'monthly_staff_total_slit_refill_marker' => $monthly_staff_total_slit_refill_marker,
	  'monthly_staff_total_scit_refill_percent' => $monthly_staff_total_scit_refill_percent,
	  'monthly_staff_total_scit_refill_marker' => $monthly_staff_total_scit_refill_marker,
	  'monthly_clinic_vs_staff_rx_refill_last_update' => $monthly_clinic_vs_staff_rx_refill_last_update,

	  //id: monthly-chart-clinic-vs-staff-rx
	  'monthly_clinic_vs_staff_rx_year' => $monthly_clinic_vs_staff_rx_year,
	  'chart_monthly_compare_clinic_slit_created' => $chart_monthly_compare_clinic_slit_created,
	  'chart_monthly_compare_clinic_scit_created' => $chart_monthly_compare_clinic_scit_created,
	  'chart_monthly_compare_staff_slit_created' => $chart_monthly_compare_staff_slit_created,
	  'chart_monthly_compare_staff_scit_created' => $chart_monthly_compare_staff_scit_created,
	  'chart_monthly_compare_clinic_slit_refill' => $chart_monthly_compare_clinic_slit_refill,
	  'chart_monthly_compare_clinic_scit_refill' => $chart_monthly_compare_clinic_scit_refill,
	  'chart_monthly_compare_staff_slit_refill' => $chart_monthly_compare_staff_slit_refill,
	  'chart_monthly_compare_staff_scit_refill' => $chart_monthly_compare_staff_scit_refill,

	  //id: monthly-clinic-vs-staff-rx
	  'comparison_table_clinic_vs_staff_rx' => $comparison_table_clinic_vs_staff_rx,
	  'clinic_vs_staff_rx_last_update' => $clinic_vs_staff_rx_last_update,
	  
	  //refresh interval
	  'dashboard_refresh_time' => $dashboard_refresh_time,
	  'dashboard_last_refresh_time' => date('F j, Y H:i:s'),
  );


  return theme('dashboard_view_page', $variables);
}


?>