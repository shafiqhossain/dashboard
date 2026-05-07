<?php

namespace Drupal\custom_example\Service;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;


/**
 * Dashboard Refresh Manager Class.
 *
 * @package Drupal\custom_example
 */
class DashboardRefreshManager {
  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var AccountInterface $account
   */
  protected $account;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Dashboard helper service.
   *
   * @var \Drupal\custom_example\Service\DashboardHelper
   */
  protected $dashboardHelper;

  /**
   * Dashboard manager service.
   *
   * @var \Drupal\custom_example\Service\DashboardManager
   */
  protected $dashboardManager;

  /**
   * Constructs a new class.
   *
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_account,
    Connection $database,
    LoggerChannelFactoryInterface $logger,
    MessengerInterface $messenger,
    ConfigFactoryInterface $config_factory,
    DashboardHelper $dashboard_helper,
    DashboardManager $dashboard_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $current_account;
    $this->database = $database;
    $this->logger = $logger->get('custom_example');
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
    $this->dashboardHelper = $dashboard_helper;
    $this->dashboardManager = $dashboard_manager;
  }


  public function totalRxAmountDailyRefresh(AjaxResponse $ajax_response) {
    // Today
    $total_today = $this->dashboardManager->getTotalRxAmountByDate(date("Y-m-d"));
    $time = date('Y-m-d H:i:s');

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'total_collected_amount' => $total_today,
        'collected_amount_last_update' => $time,
      ],
      [
        'total_collected_amount' => $total_today,
        'collected_amount_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_collected_amount');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($total_today, $total_yesterday);
    $change = $status['change'];
    $change_marker = $status['marker'];
    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#daily-total-rx-amount .dashboard-block-amount', 'html', [number_format($total_today,2)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-rx-amount .dashboard-block-amount-percent .amount-percent', 'html', [$change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-rx-amount .dashboard-block-amount-percent .amount-percent-marker', 'html', [$change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-rx-amount .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function totalOrderAmountDailyRefresh(AjaxResponse $ajax_response) {
    // Today
    $total_today = $this->dashboardManager->getOrderTotalAmountByDate(date("Y-m-d"));
    $time = date('Y-m-d H:i:s');

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'total_order_amount' => $total_today / 100,
        'order_amount_last_update' => $time,
      ],
      [
        'total_order_amount' => $total_today / 100,
        'order_amount_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_order_amount');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($total_today / 100, $total_yesterday);
    $change = $status['change'];
    $change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#daily-total-order-amount .dashboard-block-amount', 'html', [number_format($total_today/100,2)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-order-amount .dashboard-block-amount-percent .amount-percent', 'html', [$change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-order-amount .dashboard-block-amount-percent .amount-percent-marker', 'html', [$change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-order-amount .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function totalRxAmountWeeklyRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // This week
    $rx_total_week = $this->dashboardManager->getTotalRxAmountByWeek(date("Y"), date("W"));

    // Update the table
    $this->dashboardManager->setBillingInfoByWeek(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'dashboard_week' => date('W'),
        'total_collected_amount' => $rx_total_week,
        'collected_amount_last_update' => $time,
      ],
      [
        'total_collected_amount' => $rx_total_week,
        'collected_amount_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'dashboard_week' => date('W'),
      ]
    );

    // Rx past week
    $past_week = date('W', strtotime("-1 week"));
    $past_week_month = date('n', strtotime("-1 week"));
    $past_week_year = date('Y', strtotime("-1 week"));
    $rx_total_past_week = $this->dashboardManager->getBillingInfoByWeekAndKey($past_week_year, $past_week_month, $past_week, 'total_collected_amount');


    // Order amount: this week
    $order_total_week = $this->dashboardManager->getOrderTotalAmountByWeek(date('Y'), date('W'));

    // Update the table
    $this->dashboardManager->setBillingInfoByWeek(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'dashboard_week' => date('W'),
        'total_order_amount' => $order_total_week / 100,
        'order_amount_last_update' => $time,
      ],
      [
        'total_order_amount' => $order_total_week / 100,
        'order_amount_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'dashboard_week' => date('W'),
      ]
    );

    // Order past week
    $past_week = date('W', strtotime("-1 week"));
    $past_week_month = date('n', strtotime("-1 week"));
    $past_week_year = date('Y', strtotime("-1 week"));
    $order_total_past_week = $this->dashboardManager->getBillingInfoByWeekAndKey($past_week_year, $past_week_month, $past_week, 'total_order_amount');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($rx_total_week, $rx_total_past_week);
    $rx_change = $status['change'];
    $rx_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($order_total_week/100, $order_total_past_week);
    $order_change = $status['change'];
    $order_change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#weekly-total-amount .dashboard-block-amount .rx-amount', 'html', [number_format($rx_total_week,2)]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-total-amount .dashboard-block-amount .order-amount', 'html', [number_format($order_total_week/100,2)]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-total-amount .dashboard-block-amount-percent .rx-amount-percent', 'html', [$rx_change]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-total-amount .dashboard-block-amount-percent .order-amount-percent', 'html', [$order_change]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-total-amount .dashboard-block-amount-percent .rx-amount-percent-marker', 'html', [$rx_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-total-amount .dashboard-block-amount-percent .order-amount-percent-marker', 'html', [$order_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-total-amount .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function totalRxAmountMonthlyRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // Rx amount: this month
    $rx_total_month = $this->dashboardManager->getTotalRxAmountByMonth(date('Y'), date('W'));

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'total_collected_amount' => $rx_total_month,
        'collected_amount_last_update' => $time,
      ],
      [
        'total_collected_amount' => $rx_total_month,
        'collected_amount_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    // Rx past month
    $past_month = date('n', strtotime("-1 month"));
    $past_month_year = date('Y', strtotime("-1 month"));
    $rx_total_past_month = $this->dashboardManager->getBillingInfoByMonthAndKey($past_month_year, $past_month, 'total_collected_amount');

    // Order amount: this month
    $order_total_month = $this->dashboardManager->getOrderTotalAmountByMonth(date('Y'), date('n'));

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'total_order_amount' => $order_total_month / 100,
        'order_amount_last_update' => $time,
      ],
      [
        'total_order_amount' => $order_total_month / 100,
        'order_amount_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    // Order past month
    $past_month = date('n', strtotime("-1 month"));
    $past_month_year = date('Y', strtotime("-1 month"));
    $order_total_past_month = $this->dashboardManager->getBillingInfoByMonthAndKey($past_month_year, $past_month, 'total_order_amount');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($rx_total_month, $rx_total_past_month);
    $rx_change = $status['change'];
    $rx_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($order_total_month/100, $order_total_past_month);
    $order_change = $status['change'];
    $order_change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#monthly-total-amount .dashboard-block-amount .rx-amount', 'html', [number_format($rx_total_month,2)]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-total-amount .dashboard-block-amount .order-amount', 'html', [number_format($order_total_month/100,2)]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-total-amount .dashboard-block-amount-percent .rx-amount-percent', 'html', [$rx_change]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-total-amount .dashboard-block-amount-percent .order-amount-percent', 'html', [$order_change]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-total-amount .dashboard-block-amount-percent .rx-amount-percent-marker', 'html', [$rx_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-total-amount .dashboard-block-amount-percent .order-amount-percent-marker', 'html', [$order_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-total-amount .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function totalPoDailyRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // Po amount: today
    $data = $this->dashboardManager->getPoAmountByTypeByDate(date("Y-m-d"));
    $total_po_amount = !empty($data['total_po_amount']) ? $data['total_po_amount'] : 0;
    $total_po_refund_amount = !empty($data['total_po_refund_amount']) ? $data['total_po_refund_amount'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'total_po_amount' => abs($total_po_amount),
        'total_po_refund_amount' => abs($total_po_refund_amount),
        'po_amount_last_update' => $time,
      ],
      [
        'total_po_amount' => abs($total_po_amount),
        'total_po_refund_amount' => abs($total_po_refund_amount),
        'po_amount_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_po_amount_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_po_amount');
    $total_po_refund_amount_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_po_refund_amount');


    // Po created: today
    $data = $this->dashboardManager->getPoCreatedByDate(date("Y-m-d"));
    $total_po = !empty($data['total_po']) ? $data['total_po'] : 0;
    $total_po_refund = !empty($data['total_po_refund']) ? $data['total_po_refund'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'total_po_created' => $total_po,
        'total_po_refund_created' => $total_po_refund,
        'po_create_last_update' => $time,
      ],
      [
        'total_po_created' => $total_po,
        'total_po_refund_created' => $total_po_refund,
        'po_create_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_po_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_po_created');
    $total_po_refund_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_po_refund_created');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus(abs($total_po_amount), abs($total_po_amount_yesterday));
    $po_amount_change = $status['change'];
    $po_amount_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus(abs($total_po_refund_amount), abs($total_po_refund_amount_yesterday));
    $po_refund_amount_change = $status['change'];
    $po_refund_amount_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($total_po, $total_po_yesterday);
    $po_change = $status['change'];
    $po_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($total_po_refund, $total_po_refund_yesterday);
    $po_refund_change = $status['change'];
    $po_refund_change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#daily-total-po-amount .dashboard-block-amount .po-amount', 'html', [number_format($total_po_amount,2)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-po-amount .dashboard-block-amount .po-created', 'html', [number_format($total_po,0)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-po-amount .dashboard-block-amount-percent .po-amount-percent', 'html', [$po_amount_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-po-amount .dashboard-block-amount-percent .po-created-percent', 'html', [$po_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-po-amount .dashboard-block-amount-percent .po-amount-percent-marker', 'html', [$po_amount_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-po-amount .dashboard-block-amount-percent .po-created-percent-marker', 'html', [$po_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-po-amount .dashboard-block-links .last-update-block', 'html', [$last_time]));

    $ajax_response->addCommand(new InvokeCommand('#daily-total-po-refund-amount .dashboard-block-amount .po-refund-amount', 'html', [number_format($total_po_refund_amount,2)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-po-refund-amount .dashboard-block-amount .po-refund-created', 'html', [number_format($total_po_refund,0)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-po-refund-amount .dashboard-block-amount-percent .po-refund-amount-percent', 'html', [$po_refund_amount_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-po-refund-amount .dashboard-block-amount-percent .po-refund-created-percent', 'html', [$po_refund_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-po-refund-amount .dashboard-block-amount-percent .po-refund-amount-percent-marker', 'html', [$po_refund_amount_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-po-refund-amount .dashboard-block-amount-percent .po-refund-created-percent-marker', 'html', [$po_refund_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-total-po-refund-amount .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function totalPoWeeklyRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // Po amount: this week
    $data = $this->dashboardManager->getPoAmountByTypeByWeek(date('Y'), date('W'));
    $total_po_amount = !empty($data['total_po_amount']) ? $data['total_po_amount'] : 0;
    $total_po_refund_amount = !empty($data['total_po_refund_amount']) ? $data['total_po_refund_amount'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByWeek(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'dashboard_week' => date('W'),
        'total_po_amount' => abs($total_po_amount),
        'total_po_refund_amount' => abs($total_po_refund_amount),
        'po_amount_last_update' => $time,
      ],
      [
        'total_po_amount' => abs($total_po_amount),
        'total_po_refund_amount' => abs($total_po_refund_amount),
        'po_amount_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'dashboard_week' => date('W'),
      ]
    );

    // Po amount: past week
    $past_week = date('W', strtotime("-1 week"));
    $past_week_month = date('n', strtotime("-1 week"));
    $past_week_year = date('Y', strtotime("-1 week"));
    $total_po_amount_past_week = $this->dashboardManager->getBillingInfoByWeekAndKey($past_week_year, $past_week_month, $past_week,'total_po_amount');
    $total_po_refund_amount_past_week = $this->dashboardManager->getBillingInfoByWeekAndKey($past_week_year, $past_week_month, $past_week,'total_po_refund_amount');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus(abs($total_po_amount), abs($total_po_amount_past_week));
    $po_change = $status['change'];
    $po_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus(abs($total_po_refund_amount), abs($total_po_refund_amount_past_week));
    $po_refund_change = $status['change'];
    $po_refund_change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#weekly-total-po-amount .dashboard-block-amount .po-amount', 'html', [number_format($total_po_amount,2)]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-total-po-amount .dashboard-block-amount .po-refund-amount', 'html', [number_format($total_po_refund_amount,2)]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-total-po-amount .dashboard-block-amount-percent .po-amount-percent', 'html', [$po_change]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-total-po-amount .dashboard-block-amount-percent .po-refund-amount-percent', 'html', [$po_refund_change]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-total-po-amount .dashboard-block-amount-percent .po-amount-percent-marker', 'html', [$po_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-total-po-amount .dashboard-block-amount-percent .po-refund-amount-percent-marker', 'html', [$po_refund_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-total-po-amount .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function totalPoMonthlyRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // Po amount: this month
    $data = $this->dashboardManager->getPoAmountByTypeByMonth(date('Y'), date('n'));
    $total_po_amount = !empty($data['total_po_amount']) ? $data['total_po_amount'] : 0;
    $total_po_refund_amount = !empty($data['total_po_refund_amount']) ? $data['total_po_refund_amount'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'total_po_amount' => abs($total_po_amount),
        'total_po_refund_amount' => abs($total_po_refund_amount),
        'po_amount_last_update' => $time,
      ],
      [
        'total_po_amount' => abs($total_po_amount),
        'total_po_refund_amount' => abs($total_po_refund_amount),
        'po_amount_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    // Po amount: past month
    $past_month = date('n', strtotime("-1 month"));
    $past_month_year = date('Y', strtotime("-1 month"));
    $total_po_amount_past_month = $this->dashboardManager->getBillingInfoByMonthAndKey($past_month_year, $past_month, 'total_po_amount');
    $total_po_refund_amount_past_month = $this->dashboardManager->getBillingInfoByMonthAndKey($past_month_year, $past_month, 'total_po_refund_amount');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus(abs($total_po_amount), abs($total_po_amount_past_month));
    $po_change = $status['change'];
    $po_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus(abs($total_po_refund_amount), abs($total_po_refund_amount_past_month));
    $po_refund_change = $status['change'];
    $po_refund_change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#monthly-total-po-amount .dashboard-block-amount .po-amount', 'html', [number_format($total_po_amount,2)]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-total-po-amount .dashboard-block-amount .po-refund-amount', 'html', [number_format($total_po_refund_amount,2)]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-total-po-amount .dashboard-block-amount-percent .po-amount-percent', 'html', [$po_change]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-total-po-amount .dashboard-block-amount-percent .po-refund-amount-percent', 'html', [$po_refund_change]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-total-po-amount .dashboard-block-amount-percent .po-amount-percent-marker', 'html', [$po_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-total-po-amount .dashboard-block-amount-percent .po-refund-amount-percent-marker', 'html', [$po_refund_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-total-po-amount .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function totalSalesAmountDailyRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // Today : rx amount
    $daily_total_rx_amount = $this->dashboardManager->getTotalSalesAmountByDate(date("Y-m-d"));

    // Today : po amount
    $daily_total_po_amount = $this->dashboardManager->getTotalPoAmountByDate(date("Y-m-d"));

    // Today : store amount
    $daily_total_store_amount = $this->dashboardManager->getOrderTotalAmountByDate(date("Y-m-d"));

    $daily_total_store_amount = $daily_total_store_amount / 100;

    // Total sales amount
    $daily_total_sales_amount = $daily_total_rx_amount + $daily_total_po_amount + $daily_total_store_amount;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'total_sales_amount' => $daily_total_sales_amount,
        'total_sales_amount_last_update' => $time,
      ],
      [
        'total_sales_amount' => $daily_total_sales_amount,
        'total_sales_amount_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $yesterday_total_sales_amount = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_sales_amount');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($daily_total_sales_amount, $yesterday_total_sales_amount);
    $change = $status['change'];
    $change_marker = $status['marker'];
    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#daily-sales-amount .dashboard-block-amount', 'html', [number_format($daily_total_sales_amount,2)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-sales-amount .dashboard-block-amount-percent .amount-percent', 'html', [$change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-sales-amount .dashboard-block-amount-percent .amount-percent-marker', 'html', [$change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-sales-amount .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }


  public function totalSalesAmountWeeklyRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // This week : rx amount
    $weekly_total_rx_amount = $this->dashboardManager->getTotalSalesAmountByWeek(date("Y"), date("W"));

    // This week : po amount
    $weekly_total_po_amount = $this->dashboardManager->getTotalPoAmountByWeek(date("Y"), date("W"));

    // This week : store amount
    $weekly_total_store_amount = $this->dashboardManager->getOrderTotalAmountByWeek(date("Y"), date("W"));

    $weekly_total_store_amount = $weekly_total_store_amount / 100;

    // Total sales amount
    $weekly_total_sales_amount = $weekly_total_rx_amount + $weekly_total_po_amount + $weekly_total_store_amount;

    // Update the table
    $this->dashboardManager->setBillingInfoByWeek(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'dashboard_week' => date('W'),
        'total_sales_amount' => $weekly_total_sales_amount,
        'total_sales_amount_last_update' => $time,
      ],
      [
        'total_sales_amount' => $weekly_total_sales_amount,
        'total_sales_amount_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'dashboard_week' => date('W'),
      ]
    );

    // Rx past week
    $past_week = date('W', strtotime("-1 week"));
    $past_week_month = date('n', strtotime("-1 week"));
    $past_week_year = date('Y', strtotime("-1 week"));
    $past_weekly_total_sales_amount = $this->dashboardManager->getBillingInfoByWeekAndKey($past_week_year, $past_week_month, $past_week, 'total_sales_amount');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($weekly_total_sales_amount, $past_weekly_total_sales_amount);
    $change = $status['change'];
    $change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#weekly-sales-amount .dashboard-block-amount', 'html', [number_format($weekly_total_sales_amount,2)]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-sales-amount .dashboard-block-amount-percent .amount-percent', 'html', [$change]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-sales-amount .dashboard-block-amount-percent .amount-percent-marker', 'html', [$change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#weekly-sales-amount .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function totalSalesAmountMonthlyRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // This month : rx amount
    $monthly_total_rx_amount = $this->dashboardManager->getTotalSalesAmountByMonth(date("Y"), date("n"));

    // This month : po amount
    $monthly_total_po_amount = $this->dashboardManager->getTotalPoAmountByMonth(date("Y"), date("n"));

    // This month : store amount
    $monthly_total_order_amount = $this->dashboardManager->getOrderTotalAmountByMonth(date("Y"), date("n"));

    $monthly_total_order_amount = $monthly_total_order_amount / 100;

    // Total sales amount
    $monthly_total_sales_amount = $monthly_total_rx_amount + $monthly_total_po_amount + $monthly_total_order_amount;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'total_sales_amount' => $monthly_total_sales_amount,
        'total_sales_amount_last_update' => $time,
      ],
      [
        'total_sales_amount' => $monthly_total_sales_amount,
        'total_sales_amount_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    // Sales amount past month
    $past_month = date('n', strtotime("-1 month"));
    $past_month_year = date('Y', strtotime("-1 month"));
    $past_monthly_total_sales_amount = $this->dashboardManager->getBillingInfoByMonthAndKey($past_month_year, $past_month, 'total_sales_amount');


    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($monthly_total_sales_amount, $past_monthly_total_sales_amount);
    $change = $status['change'];
    $change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#monthly-sales-amount .dashboard-block-amount', 'html', [number_format($monthly_total_sales_amount,2)]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-sales-amount .dashboard-block-amount-percent .amount-percent', 'html', [$change]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-sales-amount .dashboard-block-amount-percent .amount-percent-marker', 'html', [$change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-sales-amount .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getTotalSlitScitCreatedRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // Today
    $data = $this->dashboardManager->getTotalRxCreatedByDate(date("Y-m-d"));
    $total_scit_created_today = !empty($data['total_scit_created']) ? $data['total_scit_created'] : 0;
    $total_slit_created_today = !empty($data['total_slit_created']) ? $data['total_slit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'total_scit_created' => $total_scit_created_today,
        'total_slit_created' => $total_slit_created_today,
        'rx_create_last_update' => $time,
      ],
      [
        'total_scit_created' => $total_scit_created_today,
        'total_slit_created' => $total_slit_created_today,
        'rx_create_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_scit_created_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_scit_created');
    $total_slit_created_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_slit_created');

    /*
      Increase = New Number - Original Number
      % increase = Increase / Original Number * 100.
      Decrease = Original Number - New Number
      % decrease = Decrease / Original Number * 100
    */

    $status = $this->dashboardHelper->getChangeStatus($total_scit_created_today, $total_scit_created_yesterday);
    $scit_change = $status['change'];
    $scit_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($total_slit_created_today, $total_slit_created_yesterday);
    $slit_change = $status['change'];
    $slit_change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#daily-rx-scit-created .dashboard-block-amount', 'html', [number_format($total_scit_created_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-scit-created .dashboard-block-amount-percent .amount-percent', 'html', [$scit_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-scit-created .dashboard-block-amount-percent .amount-percent-marker', 'html', [$scit_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-scit-created .dashboard-block-links .last-update-block', 'html', [$last_time]));

    $ajax_response->addCommand(new InvokeCommand('#daily-rx-slit-created .dashboard-block-amount', 'html', [number_format($total_slit_created_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-slit-created .dashboard-block-amount-percent .amount-percent', 'html', [$slit_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-slit-created .dashboard-block-amount-percent .amount-percent-marker', 'html', [$slit_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-slit-created .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getTotalSlitScitRefillsRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // Today
    $data = $this->dashboardManager->getTotalRxRefillsByDate(date("Y-m-d"));
    $total_scit_refill_today = !empty($data['total_scit_refill']) ? $data['total_scit_refill'] : 0;
    $total_slit_refill_today = !empty($data['total_slit_refill']) ? $data['total_slit_refill'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'total_scit_refill' => $total_scit_refill_today,
        'total_slit_refill' => $total_slit_refill_today,
        'rx_refill_last_update' => $time,
      ],
      [
        'total_scit_refill' => $total_scit_refill_today,
        'total_slit_refill' => $total_slit_refill_today,
        'rx_refill_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_scit_refill_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_scit_refill');
    $total_slit_refill_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_slit_refill');

    /*
      Increase = New Number - Original Number
      % increase = Increase / Original Number * 100.
      Decrease = Original Number - New Number
      % decrease = Decrease / Original Number * 100
    */

    $status = $this->dashboardHelper->getChangeStatus($total_scit_refill_today, $total_scit_refill_yesterday);
    $scit_change = $status['change'];
    $scit_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($total_slit_refill_today, $total_slit_refill_yesterday);
    $slit_change = $status['change'];
    $slit_change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#daily-rx-scit-refills .dashboard-block-amount', 'html', [number_format($total_scit_refill_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-scit-refills .dashboard-block-amount-percent .amount-percent', 'html', [$scit_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-scit-refills .dashboard-block-amount-percent .amount-percent-marker', 'html', [$scit_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-scit-refills .dashboard-block-links .last-update-block', 'html', [$last_time]));

    $ajax_response->addCommand(new InvokeCommand('#daily-rx-slit-refills .dashboard-block-amount', 'html', [number_format($total_slit_refill_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-slit-refills .dashboard-block-amount-percent .amount-percent', 'html', [$slit_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-slit-refills .dashboard-block-amount-percent .amount-percent-marker', 'html', [$slit_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-slit-refills .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getRxSlitVsScitRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // This month
    $data = $this->dashboardManager->getTotalRxCreatedByMonth(date("Y"), date("n"));
    $total_scit_created = !empty($data['total_scit_created']) ? $data['total_scit_created'] : 0;
    $total_slit_created = !empty($data['total_slit_created']) ? $data['total_slit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'total_scit_created' => $total_scit_created,
        'total_slit_created' => $total_slit_created,
        'rx_create_last_update' => $time,
      ],
      [
        'total_scit_created' => $total_scit_created,
        'total_slit_created' => $total_slit_created,
        'rx_create_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    // This month
    $data = $this->dashboardManager->getTotalRxRefillsByMonth(date("Y"), date("n"));
    $total_scit_refill = !empty($data['total_scit_refill']) ? $data['total_scit_refill'] : 0;
    $total_slit_refill = !empty($data['total_slit_refill']) ? $data['total_slit_refill'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'total_scit_refill' => $total_scit_refill,
        'total_slit_refill' => $total_slit_refill,
        'rx_refill_last_update' => $time,
      ],
      [
        'total_scit_refill' => $total_scit_refill,
        'total_slit_refill' => $total_slit_refill,
        'rx_refill_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    $results = $this->dashboardManager->getBillingInfoByMonthByTwelveMonths();

    $html  = '<table class="slit-vs-scit-comparison-table" cellpadding="5" cellspacing="0">';
    $html .= '<thead>';
    $html .= '  <tr>';
    $html .= '	  <th colspan="5">SLIT vs. SCIT</th>';
    $html .= '  </tr>';
    $html .= '  <tr>';
    $html .= '	  <th rowspan="2">Month</th>';
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

    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->dashboard_month == 1) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Jan, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="use-ajax rx-display-link" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/rx_created_refills/monthly/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 2) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Feb, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 3) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Mar, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 4) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Apr, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" href="/custom_example/report/rx_created_refills/monthly/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" href="/custom_example/report/rx_created_refills/monthly/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" href="/custom_example/report/rx_created_refills/monthly/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" href="/custom_example/report/rx_created_refills/monthly/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 5) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">May, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 6) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Jun, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 7) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Jul, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 8) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Aug, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 9) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Sep, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 10) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Oct, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 11) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Nov, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 12) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Dec, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_created) && !empty($row->total_slit_created) ? $row->total_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_slit_refill) && !empty($row->total_slit_refill) ? $row->total_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_created) && !empty($row->total_scit_created) ? $row->total_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="rx-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' href="/custom_example/report/rx_created_refills/monthly/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->total_scit_refill) && !empty($row->total_scit_refill) ? $row->total_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
      }
    }

    $html .= '</tbody>';
    $html .= '</table>';

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $data = '<script type="text/javascript">Drupal.attachBehaviors();</script>';

    $ajax_response->addCommand(new InvokeCommand('#monthly-table-slit-vs-scit', 'html', [$html]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-slit-vs-scit .dashboard-block-links .last-update-block', 'html', [$last_time]));
    $ajax_response->addCommand(new InvokeCommand('#mrs-dashboard-refresh-wrapper', 'html', [$data]));

    return $ajax_response;
  }

  public function getTotalPaymentsRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // Today
    $data = $this->dashboardManager->getRxPaymentsByDate(date("Y-m-d"));
    $total_denied_payment_today = !empty($data['total_successful_payment']) ? $data['total_successful_payment'] : 0;
    $total_successful_payment_today = !empty($data['total_error_payment']) ? $data['total_error_payment'] : 0;
    $total_refund_payment_today = !empty($data['total_denied_payment']) ? $data['total_denied_payment'] : 0;
    $total_void_payment_today = !empty($data['total_refund_payment']) ? $data['total_refund_payment'] : 0;
    $total_error_payment_today = !empty($data['total_void_payment']) ? $data['total_void_payment'] : 0;

    // Invoice payment
    $total_invoice_payment_today = $this->dashboardManager->getRxInvoicePaymentByDate(date("Y-m-d"));

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'total_denied_payment' => $total_denied_payment_today,
        'total_successful_payment' => $total_successful_payment_today,
        'total_refund_payment' => $total_refund_payment_today,
        'total_void_payment' => $total_void_payment_today,
        'total_error_payment' => $total_error_payment_today,
        'total_invoice_payment' => $total_invoice_payment_today,
        'rx_payment_last_update' => $time,
      ],
      [
        'total_denied_payment' => $total_denied_payment_today,
        'total_successful_payment' => $total_successful_payment_today,
        'total_refund_payment' => $total_refund_payment_today,
        'total_void_payment' => $total_void_payment_today,
        'total_error_payment' => $total_error_payment_today,
        'total_invoice_payment' => $total_invoice_payment_today,
        'rx_payment_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_successful_payment_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_successful_payment');
    $total_refund_payment_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_refund_payment');
    $total_invoice_payment_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_invoice_payment');
    $total_denied_payment_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_denied_payment');
    $total_void_payment_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_void_payment');
    $total_error_payment_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'total_error_payment');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($total_successful_payment_today, $total_successful_payment_yesterday);
    $successful_change = $status['change'];
    $successful_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($total_refund_payment_today, $total_refund_payment_yesterday);
    $refund_change = $status['change'];
    $refund_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($total_invoice_payment_today, $total_invoice_payment_yesterday);
    $invoice_change = $status['change'];
    $invoice_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($total_denied_payment_today, $total_denied_payment_yesterday);
    $denied_change = $status['change'];
    $denied_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($total_void_payment_today, $total_void_payment_yesterday);
    $void_change = $status['change'];
    $void_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($total_error_payment_today, $total_error_payment_yesterday);
    $error_change = $status['change'];
    $error_change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#daily-rx-successful-payment .dashboard-block-amount', 'html', [number_format($total_successful_payment_today,2)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-successful-payment .dashboard-block-amount-percent .amount-percent', 'html', [$successful_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-successful-payment .dashboard-block-amount-percent .amount-percent-marker', 'html', [$successful_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-successful-payment .dashboard-block-links .last-update-block', 'html', [$last_time]));

    $ajax_response->addCommand(new InvokeCommand('#daily-rx-refund-payment .dashboard-block-amount', 'html', [number_format($total_refund_payment_today,2)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-refund-payment .dashboard-block-amount-percent .amount-percent', 'html', [$refund_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-refund-payment .dashboard-block-amount-percent .amount-percent-marker', 'html', [$refund_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-refund-payment .dashboard-block-links .last-update-block', 'html', [$last_time]));

    $ajax_response->addCommand(new InvokeCommand('#daily-rx-invoice-payment .dashboard-block-amount', 'html', [number_format($total_invoice_payment_today,2)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-invoice-payment .dashboard-block-amount-percent .amount-percent', 'html', [$invoice_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-invoice-payment .dashboard-block-amount-percent .amount-percent-marker', 'html', [$invoice_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-invoice-payment .dashboard-block-links .last-update-block', 'html', [$last_time]));

    $ajax_response->addCommand(new InvokeCommand('#daily-rx-denied-payment .dashboard-block-amount', 'html', [number_format($total_denied_payment_today,2)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-denied-payment .dashboard-block-amount-percent .amount-percent', 'html', [$denied_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-denied-payment .dashboard-block-amount-percent .amount-percent-marker', 'html', [$denied_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-denied-payment .dashboard-block-links .last-update-block', 'html', [$last_time]));

    $ajax_response->addCommand(new InvokeCommand('#daily-rx-void-payment .dashboard-block-amount', 'html', [number_format($total_void_payment_today,2)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-void-payment .dashboard-block-amount-percent .amount-percent', 'html', [$void_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-void-payment .dashboard-block-amount-percent .amount-percent-marker', 'html', [$void_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-void-payment .dashboard-block-links .last-update-block', 'html', [$last_time]));

    $ajax_response->addCommand(new InvokeCommand('#daily-rx-error-payment .dashboard-block-amount', 'html', [number_format($total_error_payment_today,2)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-error-payment .dashboard-block-amount-percent .amount-percent', 'html', [$error_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-error-payment .dashboard-block-amount-percent .amount-percent-marker', 'html', [$error_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-rx-error-payment .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getRxPaymentsSummaryRefresh(AjaxResponse $ajax_response) {
    // This month
    $data = $this->dashboardManager->getRxPaymentsByMonth(date("Y"), date("n"));
    $total_successful_payment = !empty($data['total_successful_payment']) ? $data['total_successful_payment'] : 0;
    $total_error_payment = !empty($data['total_error_payment']) ? $data['total_error_payment'] : 0;
    $total_denied_payment = !empty($data['total_denied_payment']) ? $data['total_denied_payment'] : 0;
    $total_refund_payment = !empty($data['total_refund_payment']) ? $data['total_refund_payment'] : 0;
    $total_void_payment = !empty($data['total_void_payment']) ? $data['total_void_payment'] : 0;

    // Invoice payment
    $total_invoice_payment = $this->dashboardManager->getRxInvoicePaymentByMonth(date("Y"), date("n"));

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'total_denied_payment' => $total_denied_payment,
        'total_successful_payment' => $total_successful_payment,
        'total_refund_payment' => $total_refund_payment,
        'total_void_payment' => $total_void_payment,
        'total_error_payment' => $total_error_payment,
        'total_invoice_payment' => $total_invoice_payment,
        'rx_payment_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'total_denied_payment' => $total_denied_payment,
        'total_successful_payment' => $total_successful_payment,
        'total_refund_payment' => $total_refund_payment,
        'total_void_payment' => $total_void_payment,
        'total_error_payment' => $total_error_payment,
        'total_invoice_payment' => $total_invoice_payment,
        'rx_payment_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    /** Sales amount **/

    // This month : rx amount
    $monthly_total_rx_amount = $this->dashboardManager->getTotalSalesAmountByMonth(date('Y'), date('n'));

    // This month : po amount
    $monthly_total_po_amount = $this->dashboardManager->getTotalPoAmountByMonth(date('Y'), date('n'));

    // This month : store amount
    $monthly_total_order_amount = $this->dashboardManager->getOrderTotalAmountByMonth(date('Y'), date('n'));
    $monthly_total_order_amount = $monthly_total_order_amount / 100;

    // Total sales amount
    $monthly_total_sales_amount = $monthly_total_rx_amount + $monthly_total_po_amount + $monthly_total_order_amount;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'total_sales_amount' => $monthly_total_sales_amount,
        'total_sales_amount_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'total_sales_amount' => $monthly_total_sales_amount,
        'total_sales_amount_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    // 12 months summary
    $results = $this->dashboardManager->getBillingInfoByMonthByTwelveMonths();

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

    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->dashboard_month == 1) {
          $html .= '<tr>';
          $html .= '<td class="month-name">Jan, ' . $row->dashboard_year . '</td>';
          $html .= '<td class="payment-successful"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/successful/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-refund"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/refund/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-invoice"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/invoice/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-sales"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/sales/monthly/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 2) {
          $html .= '<tr>';
          $html .= '<td class="month-name">Feb, ' . $row->dashboard_year . '</td>';
          $html .= '<td class="payment-successful"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/successful/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-refund"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/refund/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-invoice"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/invoice/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-sales"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/sales/monthly/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 3) {
          $html .= '<tr>';
          $html .= '<td class="month-name">Mar, ' . $row->dashboard_year . '</td>';
          $html .= '<td class="payment-successful"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/successful/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-refund"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/refund/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-invoice"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/invoice/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-sales"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/sales/monthly/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 4) {
          $html .= '<tr>';
          $html .= '<td class="month-name">Apr, ' . $row->dashboard_year . '</td>';
          $html .= '<td class="payment-successful"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/successful/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-refund"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/refund/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-invoice"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/invoice/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-sales"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/sales/monthly/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 5) {
          $html .= '<tr>';
          $html .= '<td class="month-name">May, ' . $row->dashboard_year . '</td>';
          $html .= '<td class="payment-successful"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/successful/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-refund"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/refund/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-invoice"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/invoice/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-sales"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/sales/monthly/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 6) {
          $html .= '<tr>';
          $html .= '<td class="month-name">Jun, ' . $row->dashboard_year . '</td>';
          $html .= '<td class="payment-successful"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/successful/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-refund"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/refund/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-invoice"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/invoice/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-sales"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/sales/monthly/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 7) {
          $html .= '<tr>';
          $html .= '<td class="month-name">Jul, ' . $row->dashboard_year . '</td>';
          $html .= '<td class="payment-successful"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/successful/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-refund"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/refund/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-invoice"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/invoice/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-sales"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/sales/monthly/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 8) {
          $html .= '<tr>';
          $html .= '<td class="month-name">Aug, ' . $row->dashboard_year . '</td>';
          $html .= '<td class="payment-successful"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/successful/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-refund"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/refund/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-invoice"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/invoice/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-sales"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/sales/monthly/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 9) {
          $html .= '<tr>';
          $html .= '<td class="month-name">Sep, ' . $row->dashboard_year . '</td>';
          $html .= '<td class="payment-successful"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/successful/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-refund"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/refund/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-invoice"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/invoice/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-sales"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/sales/monthly/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 10) {
          $html .= '<tr>';
          $html .= '<td class="month-name">Oct, ' . $row->dashboard_year . '</td>';
          $html .= '<td class="payment-successful"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/successful/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-refund"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/refund/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-invoice"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/invoice/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-sales"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/sales/monthly/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 11) {
          $html .= '<tr>';
          $html .= '<td class="month-name">Nov, ' . $row->dashboard_year . '</td>';
          $html .= '<td class="payment-successful"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/successful/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-refund"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/refund/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-invoice"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/invoice/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-sales"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/sales/monthly/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 12) {
          $html .= '<tr>';
          $html .= '<td class="month-name">Dec, ' . $row->dashboard_year . '</td>';
          $html .= '<td class="payment-successful"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/successful/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_successful_payment) && !empty($row->total_successful_payment) ? number_format($row->total_successful_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-refund"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/refund/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_refund_payment) && !empty($row->total_refund_payment) ? number_format($row->total_refund_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-invoice"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/payments/monthly/invoice/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_invoice_payment) && !empty($row->total_invoice_payment) ? number_format($row->total_invoice_payment,2) : 0.00) . '</a></td>';
          $html .= '<td class="payment-sales"><a class="payment-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/sales/monthly/' . $row->dashboard_month . '/' . $row->dashboard_year . '">$ ' . (isset($row->total_sales_amount) && !empty($row->total_sales_amount) ? number_format($row->total_sales_amount,2) : 0.00) . '</a></td>';
          $html .= '</tr>';
        }
      }
    }

    $html .= '</tbody>';
    $html .= '</table>';

    // Today: billing_by_date
    $today = date('Y-m-d');
    $rx_payment_last_update = $this->dashboardManager->getBillingInfoByDateAndKey($today, 'rx_payment_last_update');
    $last_time = !empty($rx_payment_last_update) ? date('F j, Y H:i:s', strtotime($rx_payment_last_update)) : '';

    $data = '<script type="text/javascript">Drupal.attachBehaviors();</script>';

    $ajax_response->addCommand(new InvokeCommand('#monthly-table-payments', 'html', [$html]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-payments .dashboard-block-links .last-update-block', 'html', [$last_time]));
    $ajax_response->addCommand(new InvokeCommand('#mrs-dashboard-refresh-wrapper', 'html', [$data]));

    return $ajax_response;
  }

  public function getTotalOrderSubmittedRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // Today
    $data = $this->dashboardManager->getOrdersByDate(date('Y-m-d'));
    $total_orders_ids = isset($data['total_orders_ids']) ? $data['total_orders_ids'] : '';
    $total_orders_today = isset($data['total_orders']) ? $data['total_orders'] : 0;

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'order_submitted' => $total_orders_today,
        'order_ids' => $total_orders_ids,
        'order_last_update' => $time,
      ],
      [
        'order_submitted' => $total_orders_today,
        'order_ids' => $total_orders_ids,
        'order_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_orders_yesterday = $this->dashboardManager->getBillingCountByDateAndKey($yesterday, 'order_submitted');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($total_orders_today, $total_orders_yesterday);
    $change = $status['change'];
    $change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#daily-order-submitted .dashboard-block-amount', 'html', [number_format($total_orders_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-order-submitted .dashboard-block-amount-percent .amount-percent', 'html', [$change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-order-submitted .dashboard-block-amount-percent .amount-percent-marker', 'html', [$change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-order-submitted .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getTotalRxPendingRefresh(AjaxResponse $ajax_response) {
    $date_reference = date('Y-m-d', strtotime('-3 days'));
    $time = date('Y-m-d H:i:s');

    $data = $this->dashboardManager->getTotalRxPendingByDate($date_reference);
    $total_rx_today = isset($data['total_rx']) ? $data['total_rx'] : 0;
    $total_rx_nids = isset($data['total_rx_nids']) ? $data['total_rx_nids'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'rx_pending' => $total_rx_today,
        'rx_pending_nids' => $total_rx_nids,
        'rx_pending_last_update' => $time,
      ],
      [
        'rx_pending' => $total_rx_today,
        'rx_pending_nids' => $total_rx_nids,
        'rx_pending_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_rx_yesterday = $this->dashboardManager->getBillingCountByDateAndKey($yesterday, 'rx_pending');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($total_rx_today, $total_rx_yesterday);
    $change = $status['change'];
    $change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time) );

    $ajax_response->addCommand(new InvokeCommand('#rx-pending .dashboard-block-amount', 'html', [number_format($total_rx_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#rx-pending .dashboard-block-amount-percent .amount-percent', 'html', [$change]));
    $ajax_response->addCommand(new InvokeCommand('#rx-pending .dashboard-block-amount-percent .amount-percent-marker', 'html', [$change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#rx-pending .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getTotalRxScheduledRefresh(AjaxResponse $ajax_response) {
    $date_reference = date('Y-m-d', strtotime('+3 days'));
    $time = date('Y-m-d H:i:s');

    $data = $this->dashboardManager->getTotalRxScheduledByDate($date_reference);
    $total_rx_today = isset($data['total_rx']) ? $data['total_rx'] : 0;
    $total_rx_nids = isset($data['total_rx_nids']) ? $data['total_rx_nids'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'rx_scheduled' => $total_rx_today,
        'rx_scheduled_nids' => $total_rx_nids,
        'rx_scheduled_last_update' => $time,
      ],
      [
        'rx_scheduled' => $total_rx_today,
        'rx_scheduled_nids' => $total_rx_nids,
        'rx_scheduled_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_rx_yesterday = $this->dashboardManager->getBillingCountByDateAndKey($yesterday, 'rx_scheduled');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($total_rx_today, $total_rx_yesterday);
    $change = $status['change'];
    $change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#rx-scheduled .dashboard-block-amount', 'html', [number_format($total_rx_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#rx-scheduled .dashboard-block-amount-percent .amount-percent', 'html', [$change]));
    $ajax_response->addCommand(new InvokeCommand('#rx-scheduled .dashboard-block-amount-percent .amount-percent-marker', 'html', [$change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#rx-scheduled .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getTotalUpcomingRxRefillsRefresh(AjaxResponse $ajax_response) {
    $date_reference1 = date('Y-m-d', strtotime('+1 days'));
    $date_reference2 = date('Y-m-d', strtotime('+11 days'));
    $time = date('Y-m-d H:i:s');

    $data = $this->dashboardManager->getTotalUpcomingRxRefillsByDate($date_reference1, $date_reference2);
    $total_rx_today = isset($data['total_rx']) ? $data['total_rx'] : 0;
    $total_rx_nids = isset($data['total_rx_nids']) ? $data['total_rx_nids'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'rx_refills' => $total_rx_today,
        'rx_refills_nids' => $total_rx_nids,
        'rx_refills_last_update' => $time,
      ],
      [
        'rx_refills' => $total_rx_today,
        'rx_refills_nids' => $total_rx_nids,
        'rx_refills_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_rx_yesterday = $this->dashboardManager->getBillingCountByDateAndKey($yesterday, 'rx_refills');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status =  $this->dashboardHelper->getChangeStatus($total_rx_today, $total_rx_yesterday);
    $change = $status['change'];
    $change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#rx-refills .dashboard-block-amount', 'html', [$total_rx_today]));
    $ajax_response->addCommand(new InvokeCommand('#rx-refills .dashboard-block-amount-percent .amount-percent', 'html', [$change]));
    $ajax_response->addCommand(new InvokeCommand('#rx-refills .dashboard-block-amount-percent .amount-percent-marker', 'html', [$change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#rx-refills .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getTotalExpiringRxRefresh(AjaxResponse $ajax_response) {
    $date_reference = date('Y-m-d', strtotime('+10 days'));
    $time = date('Y-m-d H:i:s');

    $data = $this->dashboardManager->getTotalExpiringRxByDate($date_reference);
    $total_rx_today = isset($data['total_rx']) ? $data['total_rx'] : 0;
    $total_rx_nids = isset($data['total_rx_nids']) ? $data['total_rx_nids'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'rx_expiring' => $total_rx_today,
        'rx_expiring_nids' => $total_rx_nids,
        'rx_expiring_last_update' => $time,
      ],
      [
        'rx_expiring' => $total_rx_today,
        'rx_expiring_nids' => $total_rx_nids,
        'rx_expiring_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_rx_yesterday = $this->dashboardManager->getBillingCountByDateAndKey($yesterday, 'rx_expiring');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($total_rx_today, $total_rx_yesterday);
    $change = $status['change'];
    $change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#expiring-rx .dashboard-block-amount', 'html', [number_format($total_rx_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#expiring-rx .dashboard-block-amount-percent .amount-percent', 'html', [$change]));
    $ajax_response->addCommand(new InvokeCommand('#expiring-rx .dashboard-block-amount-percent .amount-percent-marker', 'html', [$change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#expiring-rx .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getTotalExpiringArbRefresh(AjaxResponse $ajax_response) {
    $date_reference = date('Y-m-d', strtotime('+30 days'));
    $time = date('Y-m-d H:i:s');

    $data = $this->dashboardManager->getTotalExpiringArbByDate($date_reference);
    $total_subscriptions_today = isset($data['total_subscriptions']) ? $data['total_subscriptions'] : 0;
    $total_subscriptions_ids = isset($data['total_subscriptions_ids']) ? $data['total_subscriptions_ids'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'arb_expiring' => $total_subscriptions_today,
        'arb_expiring_ids' => $total_subscriptions_ids,
        'arb_expiring_last_update' => $time,
      ],
      [
        'arb_expiring' => $total_subscriptions_today,
        'arb_expiring_ids' => $total_subscriptions_ids,
        'arb_expiring_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_subscriptions_yesterday = $this->dashboardManager->getBillingCountByDateAndKey($yesterday, 'arb_expiring');

    /*
      Increase = New Number - Original Number
      % increase = Increase - Original Number / 100.
      Decrease = Original Number - New Number
      % decrease = Decrease - Original Number / 100
    */

    $status = $this->dashboardHelper->getChangeStatus($total_subscriptions_today, $total_subscriptions_yesterday);
    $change = $status['change'];
    $change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#expiring-arb .dashboard-block-amount', 'html', [number_format($total_subscriptions_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#expiring-arb .dashboard-block-amount-percent .amount-percent', 'html', [$change]));
    $ajax_response->addCommand(new InvokeCommand('#expiring-arb .dashboard-block-amount-percent .amount-percent-marker', 'html', [$change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#expiring-arb .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getTotalExpiringCcRefresh(AjaxResponse $ajax_response) {
    $date_reference = date('Y-m-d', strtotime('+30 days'));
    $time = date('Y-m-d H:i:s');

    $data = $this->dashboardManager->getTotalExpiringCc($date_reference);
    $total_subscriptions_today = isset($data['total_subscriptions']) ? $data['total_subscriptions'] : 0;
    $total_subscriptions_ids = isset($data['total_subscriptions_ids']) ? $data['total_subscriptions_ids'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'cc_expiring' => $total_subscriptions_today,
        'cc_expiring_ids' => $total_subscriptions_ids,
        'cc_expiring_last_update' => $time,
      ],
      [
        'cc_expiring' => $total_subscriptions_today,
        'cc_expiring_ids' => $total_subscriptions_ids,
        'cc_expiring_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_subscriptions_yesterday = $this->dashboardManager->getBillingCountByDateAndKey($yesterday, 'cc_expiring');

    /*
      Increase = New Number - Original Number
      % increase = Increase - Original Number / 100.
      Decrease = Original Number - New Number
      % decrease = Decrease - Original Number / 100
    */

    $status = $this->dashboardHelper->getChangeStatus($total_subscriptions_today, $total_subscriptions_yesterday);
    $change = $status['change'];
    $change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#expiring-cc .dashboard-block-amount', 'html', [number_format($total_subscriptions_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#expiring-cc .dashboard-block-amount-percent .amount-percent', 'html', [$change]));
    $ajax_response->addCommand(new InvokeCommand('#expiring-cc .dashboard-block-amount-percent .amount-percent-marker', 'html', [$change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#expiring-cc .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getTotalExpiringProfileCcRefresh(AjaxResponse $ajax_response) {
    $date_reference = date('Y-m-d', strtotime('+30 days'));
    $time = date('Y-m-d H:i:s');

    $data = $this->dashboardManager->getTotalExpiringProfileCcByDate($date_reference);
    $total_profiles_today = isset($data['total_profiles']) ? $data['total_profiles'] : 0;
    $total_profiles_ids = isset($data['total_profiles_ids']) ? $data['total_profiles_ids'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'profile_cc_expiring' => $total_profiles_today,
        'profile_cc_expiring_ids' => $total_profiles_ids,
        'profile_cc_last_update' => $time,
      ],
      [
        'profile_cc_expiring' => $total_profiles_today,
        'profile_cc_expiring_ids' => $total_profiles_ids,
        'profile_cc_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_profiles_yesterday = $this->dashboardManager->getBillingCountByDateAndKey($yesterday, 'profile_cc_expiring');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($total_profiles_today, $total_profiles_yesterday);
    $change = $status['change'];
    $change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#expiring-profile-cc .dashboard-block-amount', 'html', [number_format($total_profiles_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#expiring-profile-cc .dashboard-block-amount-percent .amount-percent', 'html', [$change]));
    $ajax_response->addCommand(new InvokeCommand('#expiring-profile-cc .dashboard-block-amount-percent .amount-percent-marker', 'html', [$change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#expiring-profile-cc .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getTotalClinicsRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    $total_clinics_today = $this->dashboardManager->getTotalClinicsByDate();

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'total_clinics' => $total_clinics_today,
        'total_clinics_last_update' => $time,
      ],
      [
        'total_clinics' => $total_clinics_today,
        'total_clinics_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_clinics_yesterday = $this->dashboardManager->getBillingCountByDateAndKey($yesterday, 'total_clinics');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($total_clinics_today, $total_clinics_yesterday);
    $change = $status['change'];
    $change_marker = $status['marker'];
    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#clinics-summary .dashboard-block-amount', 'html', [number_format($total_clinics_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#clinics-summary .dashboard-block-amount-percent .amount-percent', 'html', [$change]));
    $ajax_response->addCommand(new InvokeCommand('#clinics-summary .dashboard-block-amount-percent .amount-percent-marker', 'html', [$change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#clinics-summary .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getSilentPostSummaryRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    $data = $this->dashboardManager->getSilentPostSummaryByYear(date('Y'));
    $total_resolved_today = isset($data['total_resolved']) ? $data['total_resolved'] : 0;
    $total_pending_today = isset($data['total_pending']) ? $data['total_pending'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'silent_post_resolved' => $total_resolved_today,
        'silent_post_pending' => $total_pending_today,
        'silent_post_last_update' => $time,
      ],
      [
        'silent_post_resolved' => $total_resolved_today,
        'silent_post_pending' => $total_pending_today,
        'silent_post_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_resolved_yesterday = $this->dashboardManager->getBillingCountByDateAndKey($yesterday, 'silent_post_resolved');
    $total_pending_yesterday = $this->dashboardManager->getBillingCountByDateAndKey($yesterday, 'silent_post_pending');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($total_resolved_today, $total_resolved_yesterday);
    $resolved_change = $status['change'];
    $resolved_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($total_pending_today, $total_pending_yesterday);
    $pending_change = $status['change'];
    $pending_change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#silent-post-summary .dashboard-block-amount .silent-post-resolved', 'html', [number_format($total_resolved_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#silent-post-summary .dashboard-block-amount .silent-post-pending', 'html', [number_format($total_pending_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#silent-post-summary .dashboard-block-amount-percent .silent-post-resolved-percent', 'html', [$resolved_change]));
    $ajax_response->addCommand(new InvokeCommand('#silent-post-summary .dashboard-block-amount-percent .silent-post-pending-percent', 'html', [$pending_change]));
    $ajax_response->addCommand(new InvokeCommand('#silent-post-summary .dashboard-block-amount-percent .silent-post-resolved-percent-marker', 'html', [$resolved_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#silent-post-summary .dashboard-block-amount-percent .silent-post-pending-percent-marker', 'html', [$pending_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#silent-post-summary .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getClinicVsStaffRxCreatedDailyRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // Today - clinic
    $data = $this->dashboardManager->getClinicTotalRxCreatedByRxTypeByDate(date('Y-m-d'));
    $clinic_slit_created_today = isset($data['slit_created']) ? $data['slit_created'] : 0;
    $clinic_scit_created_today = isset($data['scit_created']) ? $data['scit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'clinic_scit_created' => $clinic_scit_created_today,
        'clinic_slit_created' => $clinic_slit_created_today,
        'clinic_rx_create_last_update' => $time,
      ],
      [
        'clinic_scit_created' => $clinic_scit_created_today,
        'clinic_slit_created' => $clinic_slit_created_today,
        'clinic_rx_create_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Today - staff
    $data = $this->dashboardManager->getStaffTotalRxCreatedByRxTypeByDate(date('Y-m-d'));
    $staff_slit_created_today = isset($data['slit_created']) ? $data['slit_created'] : 0;
    $staff_scit_created_today = isset($data['scit_created']) ? $data['scit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'staff_scit_created' => $staff_scit_created_today,
        'staff_slit_created' => $staff_slit_created_today,
        'staff_rx_create_last_update' => $time,
      ],
      [
        'staff_scit_created' => $staff_scit_created_today,
        'staff_slit_created' => $staff_slit_created_today,
        'staff_rx_create_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $clinic_slit_created_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'clinic_slit_created');
    $clinic_scit_created_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'clinic_scit_created');
    $staff_slit_created_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'staff_slit_created');
    $staff_scit_created_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'staff_scit_created');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($clinic_scit_created_today, $clinic_scit_created_yesterday);
    $clinic_scit_created_change = $status['change'];
    $clinic_scit_created_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($clinic_slit_created_today, $clinic_slit_created_yesterday);
    $clinic_slit_created_change = $status['change'];
    $clinic_slit_created_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($staff_scit_created_today, $staff_scit_created_yesterday);
    $staff_scit_created_change = $status['change'];
    $staff_scit_created_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($staff_slit_created_today, $staff_slit_created_yesterday);
    $staff_slit_created_change = $status['change'];
    $staff_slit_created_change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-created .clinic-slit-created', 'html', [number_format($clinic_slit_created_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-created .clinic-scit-created', 'html', [number_format($clinic_scit_created_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-created .staff-slit-created', 'html', [number_format($staff_slit_created_today,0)]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-created .staff-scit-created', 'html', [number_format($staff_scit_created_today,0)]));

    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-created .clinic-slit-created-percent', 'html', [$clinic_slit_created_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-created .clinic-slit-created-marker', 'html', [$clinic_slit_created_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-created .clinic-scit-created-percent', 'html', [$clinic_scit_created_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-created .clinic-scit-created-marker', 'html', [$clinic_scit_created_change_marker]));

    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-created .staff-slit-created-percent', 'html', [$staff_slit_created_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-created .staff-slit-created-marker', 'html', [$staff_slit_created_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-created .staff-scit-created-percent', 'html', [$staff_scit_created_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-created .staff-scit-created-marker', 'html', [$staff_scit_created_change_marker]));

    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-created .dashboard-block-links .last-update-block', 'html', [$last_time]));


    return $ajax_response;
  }

  public function getClinicVsStaffRxCreatedMonthlyRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // This month - clinic
    $data = $this->dashboardManager->getClinicTotalRxCreatedByRxTypeByMonth(date('Y'), date('n'));
    $clinic_slit_created = isset($data['slit_created']) ? $data['slit_created'] : 0;
    $clinic_scit_created = isset($data['scit_created']) ? $data['scit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'clinic_scit_created' => $clinic_scit_created,
        'clinic_slit_created' => $clinic_slit_created,
        'clinic_rx_create_last_update' => $time,
      ],
      [
        'clinic_scit_created' => $clinic_scit_created,
        'clinic_slit_created' => $clinic_slit_created,
        'clinic_rx_create_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    // This month - staff
    $data = $this->dashboardManager->getStaffTotalRxCreatedByRxTypeByMonth(date('Y'), date('n'));
    $staff_slit_created = isset($data['slit_created']) ? $data['slit_created'] : 0;
    $staff_scit_created = isset($data['scit_created']) ? $data['scit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'staff_scit_created' => $staff_scit_created,
        'staff_slit_created' => $staff_slit_created,
        'staff_rx_create_last_update' => $time,
      ],
      [
        'staff_scit_created' => $staff_scit_created,
        'staff_slit_created' => $staff_slit_created,
        'staff_rx_create_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    // Past month
    $past_month_year = date('Y', strtotime("-1 month"));
    $past_month = date('n', strtotime("-1 month"));
    $past_month_clinic_slit_created = $this->dashboardManager->getBillingInfoByMonthAndKey($past_month_year, $past_month,'clinic_slit_created');
    $past_month_clinic_scit_created = $this->dashboardManager->getBillingInfoByMonthAndKey($past_month_year, $past_month, 'clinic_scit_created');
    $past_month_staff_slit_created = $this->dashboardManager->getBillingInfoByMonthAndKey($past_month_year, $past_month, 'staff_slit_created');
    $past_month_staff_scit_created = $this->dashboardManager->getBillingInfoByMonthAndKey($past_month_year, $past_month, 'staff_scit_created');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($clinic_scit_created, $past_month_clinic_scit_created);
    $clinic_scit_created_change = $status['change'];
    $clinic_scit_created_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($clinic_slit_created, $past_month_clinic_slit_created);
    $clinic_slit_created_change = $status['change'];
    $clinic_slit_created_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($staff_scit_created, $past_month_staff_scit_created);
    $staff_scit_created_change = $status['change'];
    $staff_scit_created_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($staff_slit_created, $past_month_staff_slit_created);
    $staff_slit_created_change = $status['change'];
    $staff_slit_created_change_marker = $status['marker'];


    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-created .clinic-slit-created', 'html', [number_format($clinic_slit_created,0)]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-created .clinic-scit-created', 'html', [number_format($clinic_scit_created,0)]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-created .staff-slit-created', 'html', [number_format($staff_slit_created,0)]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-created .staff-scit-created', 'html', [number_format($staff_scit_created,0)]));

    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-created .clinic-slit-created-percent', 'html', [$clinic_slit_created_change]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-created .clinic-slit-created-marker', 'html', [$clinic_slit_created_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-created .clinic-scit-created-percent', 'html', [$clinic_scit_created_change]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-created .clinic-scit-created-marker', 'html', [$clinic_scit_created_change_marker]));

    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-created .staff-slit-created-percent', 'html', [$staff_slit_created_change]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-created .staff-slit-created-marker', 'html', [$staff_slit_created_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-created .staff-scit-created-percent', 'html', [$staff_scit_created_change]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-created .staff-scit-created-marker', 'html', [$staff_scit_created_change_marker]));

    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-created .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getClinicVsStaffRxRefillDailyRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // Today - clinic
    $data = $this->dashboardManager->getClinicTotalRxRefillByRxTypeByDate(date('Y-m-d'));
    $clinic_slit_refill = isset($data['slit_refill']) ? $data['slit_refill'] : 0;
    $clinic_scit_refill = isset($data['scit_refill']) ? $data['scit_refill'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'clinic_scit_refill' => $clinic_scit_refill,
        'clinic_slit_refill' => $clinic_slit_refill,
        'clinic_rx_refill_last_update' => $time,
      ],
      [
        'clinic_scit_refill' => $clinic_scit_refill,
        'clinic_slit_refill' => $clinic_slit_refill,
        'clinic_rx_refill_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // Clinic - yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $clinic_slit_refill_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'clinic_slit_refill');
    $clinic_scit_refill_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'clinic_scit_refill');

    // Today - staff
    $data = $this->dashboardManager->getStaffTotalRxRefillByRxTypeByDate(date('Y-m-d'));
    $staff_slit_refill = isset($data['slit_refill']) ? $data['slit_refill'] : 0;
    $staff_scit_refill = isset($data['scit_refill']) ? $data['scit_refill'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'staff_scit_refill' => $staff_scit_refill,
        'staff_slit_refill' => $staff_slit_refill,
        'staff_rx_refill_last_update' => $time,
      ],
      [
        'staff_scit_refill' => $staff_scit_refill,
        'staff_slit_refill' => $staff_slit_refill,
        'staff_rx_refill_last_update' => $time,
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );


    // Staff - yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $staff_slit_refill_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'staff_slit_refill');
    $staff_scit_refill_yesterday = $this->dashboardManager->getBillingInfoByDateAndKey($yesterday, 'staff_scit_refill');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($clinic_scit_refill, $clinic_scit_refill_yesterday);
    $clinic_scit_refill_change = $status['change'];
    $clinic_scit_refill_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($clinic_slit_refill, $clinic_slit_refill_yesterday);
    $clinic_slit_refill_change = $status['change'];
    $clinic_slit_refill_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($staff_scit_refill, $staff_scit_refill_yesterday);
    $staff_scit_refill_change = $status['change'];
    $staff_scit_refill_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($staff_slit_refill, $staff_slit_refill_yesterday);
    $staff_slit_refill_change = $status['change'];
    $staff_slit_refill_change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-refill .clinic-slit-refill', 'html', [$clinic_slit_refill]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-refill .clinic-scit-refill', 'html', [$clinic_scit_refill]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-refill .staff-slit-refill', 'html', [$staff_slit_refill]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-refill .staff-scit-refill', 'html', [$staff_scit_refill]));

    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-refill .clinic-slit-refill-percent', 'html', [$clinic_slit_refill_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-refill .clinic-slit-refill-marker', 'html', [$clinic_slit_refill_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-refill .clinic-scit-refill-percent', 'html', [$clinic_scit_refill_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-refill .clinic-scit-refill-marker', 'html', [$clinic_scit_refill_change_marker]));

    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-refill .staff-slit-refill-percent', 'html', [$staff_slit_refill_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-refill .staff-slit-refill-marker', 'html', [$staff_slit_refill_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-refill .staff-scit-refill-percent', 'html', [$staff_scit_refill_change]));
    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-refill .staff-scit-refill-marker', 'html', [$staff_scit_refill_change_marker]));

    $ajax_response->addCommand(new InvokeCommand('#daily-clinic-vs-staff-rx-refill .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getClinicVsStaffRxRefillMonthlyRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // This month - clinic
    $data = $this->dashboardManager->getClinicTotalRxRefillByRxTypeByMonth(date('Y'), date('n'));
    $clinic_slit_refill = isset($data['slit_refill']) ? $data['slit_refill'] : 0;
    $clinic_scit_refill = isset($data['scit_refill']) ? $data['scit_refill'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'clinic_scit_refill' => $clinic_scit_refill,
        'clinic_slit_refill' => $clinic_slit_refill,
        'clinic_rx_refill_last_update' => $time,
      ],
      [
        'clinic_scit_refill' => $clinic_scit_refill,
        'clinic_slit_refill' => $clinic_slit_refill,
        'clinic_rx_refill_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    // Clinic - past month
    $past_month_year = date('Y', strtotime("-1 month"));
    $past_month = date('n', strtotime("-1 month"));
    $past_month_clinic_slit_refill = $this->dashboardManager->getBillingInfoByMonthAndKey($past_month_year, $past_month, 'clinic_slit_refill');
    $past_month_clinic_scit_refill = $this->dashboardManager->getBillingInfoByMonthAndKey($past_month_year, $past_month, 'clinic_scit_refill');

    // This month - staff
    $data = $this->dashboardManager->getStaffTotalRxRefillByRxTypeByMonth(date('Y'), date('n'));
    $staff_slit_refill = isset($data['slit_refill']) ? $data['slit_refill'] : 0;
    $staff_scit_refill = isset($data['scit_refill']) ? $data['scit_refill'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'staff_scit_refill' => $staff_scit_refill,
        'staff_slit_refill' => $staff_slit_refill,
        'staff_rx_refill_last_update' => $time,
      ],
      [
        'staff_scit_refill' => $staff_scit_refill,
        'staff_slit_refill' => $staff_slit_refill,
        'staff_rx_refill_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    // Staff - past month
    $past_month_year = date('Y', strtotime("-1 month"));
    $past_month = date('n', strtotime("-1 month"));
    $past_month_staff_slit_refill = $this->dashboardManager->getBillingInfoByMonthAndKey($past_month_year, $past_month, 'staff_slit_refill');
    $past_month_staff_scit_refill = $this->dashboardManager->getBillingInfoByMonthAndKey($past_month_year, $past_month, 'staff_scit_refill');

    /*
      Increase = New Number - Original Number
      % increase = Increase � Original Number � 100.
      Decrease = Original Number - New Number
      % decrease = Decrease � Original Number � 100
    */

    $status = $this->dashboardHelper->getChangeStatus($clinic_scit_refill, $past_month_clinic_scit_refill);
    $clinic_scit_refill_change = $status['change'];
    $clinic_scit_refill_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($clinic_slit_refill, $past_month_clinic_slit_refill);
    $clinic_slit_refill_change = $status['change'];
    $clinic_slit_refill_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($staff_scit_refill, $past_month_staff_scit_refill);
    $staff_scit_refill_change = $status['change'];
    $staff_scit_refill_change_marker = $status['marker'];

    $status = $this->dashboardHelper->getChangeStatus($staff_slit_refill, $past_month_staff_slit_refill);
    $staff_slit_refill_change = $status['change'];
    $staff_slit_refill_change_marker = $status['marker'];

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-refill .clinic-slit-refill', 'html', [$clinic_slit_refill]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-refill .clinic-scit-refill', 'html', [$clinic_scit_refill]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-refill .staff-slit-refill', 'html', [$staff_slit_refill]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-refill .staff-scit-refill', 'html', [$staff_scit_refill]));

    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-refill .clinic-slit-refill-percent', 'html', [$clinic_slit_refill_change]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-refill .clinic-slit-refill-marker', 'html', [$clinic_slit_refill_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-refill .clinic-scit-refill-percent', 'html', [$clinic_scit_refill_change]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-refill .clinic-scit-refill-marker', 'html', [$clinic_scit_refill_change_marker]));

    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-refill .staff-slit-refill-percent', 'html', [$staff_slit_refill_change]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-refill .staff-slit-refill-marker', 'html', [$staff_slit_refill_change_marker]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-refill .staff-scit-refill-percent', 'html', [$staff_scit_refill_change]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-refill .staff-scit-refill-marker', 'html', [$staff_scit_refill_change_marker]));

    $ajax_response->addCommand(new InvokeCommand('#monthly-clinic-vs-staff-rx-refill .dashboard-block-links .last-update-block', 'html', [$last_time]));

    return $ajax_response;
  }

  public function getClinicVsStaffRxRefresh(AjaxResponse $ajax_response) {
    $time = date('Y-m-d H:i:s');

    // Clinic - rx created : this month
    $data = $this->dashboardManager->getClinicTotalRxCreatedByRxTypeByMonth(date('Y'), date('n'));
    $clinic_slit_created = isset($data['slit_created']) ? $data['slit_created'] : 0;
    $clinic_scit_created = isset($data['scit_created']) ? $data['scit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'clinic_scit_created' => $clinic_scit_created,
        'clinic_slit_created' => $clinic_slit_created,
        'clinic_rx_create_last_update' => $time,
      ],
      [
        'clinic_scit_created' => $clinic_scit_created,
        'clinic_slit_created' => $clinic_slit_created,
        'clinic_rx_create_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    // Staff - rx created : this month
    $data = $this->dashboardManager->getStaffTotalRxCreatedByRxTypeByMonth(date('Y'), date('n'));
    $staff_slit_created = isset($data['slit_created']) ? $data['slit_created'] : 0;
    $staff_scit_created = isset($data['scit_created']) ? $data['scit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'staff_scit_created' => $staff_scit_created,
        'staff_slit_created' => $staff_slit_created,
        'staff_rx_create_last_update' => $time,
      ],
      [
        'staff_scit_created' => $staff_scit_created,
        'staff_slit_created' => $staff_slit_created,
        'staff_rx_create_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    // Clinic - rx refills: this month
    $data = $this->dashboardManager->getClinicTotalRxRefillByRxTypeByMonth(date('Y'), date('n'));
    $clinic_slit_refill = isset($data['slit_refill']) ? $data['slit_refill'] : 0;
    $clinic_scit_refill = isset($data['scit_refill']) ? $data['scit_refill'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'clinic_scit_refill' => $clinic_scit_refill,
        'clinic_slit_refill' => $clinic_slit_refill,
        'clinic_rx_refill_last_update' => $time,
      ],
      [
        'clinic_scit_refill' => $clinic_scit_refill,
        'clinic_slit_refill' => $clinic_slit_refill,
        'clinic_rx_refill_last_update' => $time,
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    // Staff - rx refill: this month
    $data = $this->dashboardManager->getStaffTotalRxRefillByRxTypeByMonth(date('Y'), date('n'));
    $staff_slit_refill = isset($data['slit_refill']) ? $data['slit_refill'] : 0;
    $staff_scit_refill = isset($data['scit_refill']) ? $data['scit_refill'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'staff_scit_refill' => $staff_scit_refill,
        'staff_slit_refill' => $staff_slit_refill,
        'staff_rx_refill_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'staff_scit_refill' => $staff_scit_refill,
        'staff_slit_refill' => $staff_slit_refill,
        'staff_rx_refill_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );

    // 12 months info
    $results = $this->dashboardManager->getBillingInfoByMonthByTwelveMonths();

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

    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->dashboard_month == 1) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Jan, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 2) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Feb, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 3) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Mar, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 4) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Apr, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 5) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">May, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 6) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Jun, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 7) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Jul, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 8) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Aug, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 9) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Sep, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 10) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Oct, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 11) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Nov, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
        elseif ($row->dashboard_month == 12) {
          $html .= '<tr>';
          $html .= '  <td class="month-name">Dec, ' . $row->dashboard_year . '</td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_created) && !empty($row->clinic_slit_created) ? $row->clinic_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_slit_refill) && !empty($row->clinic_slit_refill) ? $row->clinic_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_created) && !empty($row->clinic_scit_created) ? $row->clinic_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/clinic/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->clinic_scit_refill) && !empty($row->clinic_scit_refill) ? $row->clinic_scit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_created) && !empty($row->staff_slit_created) ? $row->staff_slit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/slit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_slit_refill) && !empty($row->staff_slit_refill) ? $row->staff_slit_refill : 0) . '</a></td>';
          $html .= '  <td class="created"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/created/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_created) && !empty($row->staff_scit_created) ? $row->staff_scit_created : 0) . '</a></td>';
          $html .= '  <td class="refill"><a class="clinic-vs-staff-display-link use-ajax" data-dialog-options=\'{"dialogClass":"dashboard-dialog", "width":"800"}\' data-dialog-type="modal" href="/custom_example/report/clinic_vs_staff_created_refills/monthly/staff/scit/refills/' . $row->dashboard_month . '/' . $row->dashboard_year . '">' . (isset($row->staff_scit_refill) && !empty($row->staff_scit_refill) ? $row->staff_scit_refill : 0) . '</a></td>';
          $html .= '</tr>';
        }
      }
    }

    $html .= '  </tbody>';
    $html .= '</table>';

    $last_time = date('F j, Y H:i:s', strtotime($time));

    $data = '<script type="text/javascript">Drupal.attachBehaviors();</script>';

    $ajax_response->addCommand(new InvokeCommand('#monthly-table-clinic-vs-staff-rx', 'html', [$html]));
    $ajax_response->addCommand(new InvokeCommand('#monthly-compare-clinic-vs-staff-rx .last-update-block', 'html', [$last_time]));
    $ajax_response->addCommand(new InvokeCommand('#mrs-dashboard-refresh-wrapper', 'html', [$data]));

    return $ajax_response;
  }

}
