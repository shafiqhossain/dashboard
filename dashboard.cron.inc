<?php
// $Id: dashboard.cron.inc,v 1.0 2017/10/01 12:55:37 shafiq Exp $

/**
 * @file
 * Dashboard cron callbacks.
 */


/**
 * Dashboard cron
 *
 */
function dashboard_cron_page() {
  //$interval = variable_get('dashboard_cron_interval', 2 * 60 * 60);  //2 hours
  //if (time() >= variable_get('dashboard_cron_next_execution', 0)) {

    //1
    $queue = DrupalQueue::get('dashboard_total_amount_collected');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 1;
    $queue->createItem($item);

    //2
    $queue = DrupalQueue::get('dashboard_total_rx_created');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 2;
    $queue->createItem($item);

    //3
    $queue = DrupalQueue::get('dashboard_total_rx_refills');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 3;
    $queue->createItem($item);

    //4
    $queue = DrupalQueue::get('dashboard_total_rx_payments');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 4;
    $queue->createItem($item);

    //5
    $queue = DrupalQueue::get('dashboard_total_rx_pendings');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 5;
    $queue->createItem($item);

    //6
    $queue = DrupalQueue::get('dashboard_total_rx_scheduled');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 6;
    $queue->createItem($item);

    //7
    $queue = DrupalQueue::get('dashboard_total_rx_shipment_refills');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 7;
    $queue->createItem($item);

    //8
    $queue = DrupalQueue::get('dashboard_total_rx_expiring');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 8;
    $queue->createItem($item);

    //9
    $queue = DrupalQueue::get('dashboard_total_expiring_arb');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 9;
    $queue->createItem($item);

    //10
    $queue = DrupalQueue::get('dashboard_total_expiring_cc');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 10;
    $queue->createItem($item);

    //11
    $queue = DrupalQueue::get('dashboard_total_profile_expiring_cc');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 11;
    $queue->createItem($item);

    //12
    $queue = DrupalQueue::get('dashboard_order_amount_collected');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 12;
    $queue->createItem($item);

    //13
    $queue = DrupalQueue::get('dashboard_total_order_submitted');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 13;
    $queue->createItem($item);

    //14
    $queue = DrupalQueue::get('dashboard_total_clinics');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 14;
    $queue->createItem($item);

    //15
    $queue = DrupalQueue::get('dashboard_silent_post_summary');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 15;
    $queue->createItem($item);

    //16
    $queue = DrupalQueue::get('dashboard_po_amount_collected');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 16;
    $queue->createItem($item);

    //17
    $queue = DrupalQueue::get('dashboard_total_po_created');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 17;
    $queue->createItem($item);

    //18
    $queue = DrupalQueue::get('dashboard_total_sales_amount');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 18;
    $queue->createItem($item);

    //19
    $queue = DrupalQueue::get('dashboard_clinic_vs_staff_rx_created');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 19;
    $queue->createItem($item);

    //20
    $queue = DrupalQueue::get('dashboard_clinic_vs_staff_rx_refills');
    $item = new stdClass();
    $item->created = time();
    $item->sequence = 20;
    $queue->createItem($item);

    //variable_set('dashboard_cron_next_execution', time() + $interval);
    watchdog('dashboard', 'Cron run completed @ '.date('Y-m-d H:i:s'));
  //}
  
  $message = 'All jobs have been scheduled for cron.';
  
  return $message;
}


?>
