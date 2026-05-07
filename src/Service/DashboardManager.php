<?php

namespace Drupal\custom_example\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;


/**
 * Dashboard Manager Class.
 *
 * @package Drupal\custom_example
 */
class DashboardManager {
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
    ConfigFactoryInterface $config_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $current_account;
    $this->database = $database;
    $this->logger = $logger->get('custom_example');
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
  }


  public function getBillingInfoByDate($date) {
    $result = $this->database->select('dashboard_billing_by_date', 'a')
      ->fields('a')
      ->where("DATE_FORMAT(a.dashboard_date, '%Y-%m-%d') = :dashboard_date", [':dashboard_date' => $date])
      ->execute()
      ->fetchObject();

    return $result;
  }

  public function getCountInfoByDate($date) {
    $result = $this->database->select('dashboard_count_by_date', 'a')
      ->fields('a')
      ->where("DATE_FORMAT(a.dashboard_date, '%Y-%m-%d') = :dashboard_date", [':dashboard_date' => $date])
      ->execute()
      ->fetchObject();

    return $result;
  }

  public function getBillingInfoByDateRange($from_date, $to_date) {
    $results = $this->database->select('dashboard_billing_by_date', 'a')
      ->fields('a')
      ->where("DATE_FORMAT(a.dashboard_date, '%Y-%m-%d') BETWEEN :from_date AND :to_date", [
        ':from_date' => $from_date,
        ':to_date' => $to_date,
      ])
      ->execute()
      ->fetchAll();

    return $results;
  }

  public function getBillingInfoByWeek($week_year, $week_month, $week) {
    $result = $this->database->select('dashboard_billing_by_week', 'a')
      ->fields('a')
      ->condition('a.dashboard_year', $week_year)
      ->condition('a.dashboard_month', $week_month)
      ->condition('a.dashboard_week', $week)
      ->execute()
      ->fetchObject();

    return $result;
  }

  public function getBillingInfoByMonth($year, $month) {
    $result = $this->database->select('dashboard_billing_by_month', 'a')
      ->fields('a')
      ->condition('a.dashboard_year', $year)
      ->condition('a.dashboard_month', $month)
      ->execute()
      ->fetchObject();

    return $result;
  }

  public function getBillingInfoByYear($year) {
    $results = $this->database->select('dashboard_billing_by_month', 'a')
      ->fields('a')
      ->condition('a.dashboard_year', $year)
      ->orderBy('a.dashboard_month', 'ASC')
      ->execute()
      ->fetchAll();

    return $results;
  }

  public function getBillingInfoByMonthByTwelveMonths() {
    $results = $this->database->select('dashboard_billing_by_month', 'a')
      ->fields('a')
      ->range(0, 12)
      ->orderBy('a.dashboard_year', 'DESC')
      ->orderBy('a.dashboard_month', 'DESC')
      ->execute()
      ->fetchAll();

    return $results;
  }

  public function getBillingInfoByDateAndKey($date, $key) {
    $query = $this->database->select('dashboard_billing_by_date', 'n');
    $query->where("DATE_FORMAT(n.dashboard_date, '%Y-%m-%d') = :dashboard_date", [':dashboard_date' => $date]);
    $query->fields('n');
    $result = $query->execute()->fetchObject();

    $value = 0; // Default
    if ($result && isset($result->$key)) {
      $value = $result->$key;
    }

    return $value;
  }

  public function getBillingCountByDateAndKey($date, $key) {
    $query = $this->database->select('dashboard_count_by_date', 'n');
    $query->where("DATE_FORMAT(n.dashboard_date, '%Y-%m-%d') = :dashboard_date", [':dashboard_date' => $date]);
    $query->fields('n');
    $result = $query->execute()->fetchObject();

    $value = 0; // Default
    if ($result && isset($result->$key)) {
      $value = $result->$key;
    }

    return $value;
  }

  public function getBillingInfoByWeekAndKey($year, $month, $week, $key) {
    $query = $this->database->select('dashboard_billing_by_week', 'n');
    $query->condition("n.dashboard_year", $year, '=');
    $query->condition("n.dashboard_month", $month, '=');
    $query->condition("n.dashboard_week", $week, '=');
    $query->fields('n');
    $result = $query->execute()->fetchObject();

    $value = 0; // Default
    if ($result && isset($result->$key)) {
      $value = $result->$key;
    }

    return $value;
  }

  public function getBillingInfoByMonthAndKey($year, $month, $key) {
    $query = $this->database->select('dashboard_billing_by_month', 'n');
    $query->condition("n.dashboard_year", $year, '=');
    $query->condition("n.dashboard_month", $month, '=');
    $query->fields('n');
    $result = $query->execute()->fetchObject();

    $value = 0; // Default
    if ($result && isset($result->$key)) {
      $value = $result->$key;
    }

    return $value;
  }

  public function setBillingInfoByDate($insert_arr, $update_arr, $key_arr) {
    $this->database->merge('dashboard_billing_by_date')
      ->insertFields($insert_arr)
      ->updateFields($update_arr)
      ->keys($key_arr)
      ->execute();
  }

  public function setBillingCountByDate($insert_arr, $update_arr, $key_arr) {
    $this->database->merge('dashboard_count_by_date')
      ->insertFields($insert_arr)
      ->updateFields($update_arr)
      ->keys($key_arr)
      ->execute();
  }

  public function setBillingInfoByWeek($insert_arr, $update_arr, $key_arr) {
    $this->database->merge('dashboard_billing_by_week')
      ->insertFields($insert_arr)
      ->updateFields($update_arr)
      ->keys($key_arr)
      ->execute();
  }

  public function setBillingInfoByMonth($insert_arr, $update_arr, $key_arr) {
    $this->database->merge('dashboard_billing_by_month')
      ->insertFields($insert_arr)
      ->updateFields($update_arr)
      ->keys($key_arr)
      ->execute();
  }


  public function getTotalRxAmountByDate($date) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
    $query->condition('ts.field_tx_status_value', ['Successful', 'Successful - Fee', 'Successful - PSCC', 'Paid'], 'IN');
    $query->where("DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = :tx_date_value", [':tx_date_value' => $date], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression('SUM(am.field_rx_amount_value)', 'total');
    $total = $query->execute()->fetchField();
    if (empty($total)) {
      $total = 0;
    }

    return $total;
  }

  public function getTotalRxAmountByWeek($year, $week) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info'");
    $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info'");
    $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info'");
    $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info'");
    $query->condition('ts.field_tx_status_value', ['Successful', 'Successful - Fee', 'Successful - PSCC', 'Paid'], 'IN');
    $query->where("YEAR(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $year], '=');
    $query->where("WEEKOFYEAR(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $week], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression('SUM(am.field_rx_amount_value)', 'total');
    $total = $query->execute()->fetchField();
    if (empty($total)) {
      $total = 0;
    }

    return $total;
  }

  public function getTotalRxAmountByMonth($year, $month) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    //@todo: Check the join with paragraph fields
    $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info'");
    $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info'");
    $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info'");
    $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info'");
    $query->condition('ts.field_tx_status_value', ['Successful', 'Successful - Fee', 'Successful - PSCC', 'Paid'], 'IN');
    $query->where("YEAR(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $year], '=');
    $query->where("MONTH(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $month], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression('SUM(am.field_rx_amount_value)', 'total');
    $total = $query->execute()->fetchField();
    if (empty($total)) {
      $total = 0;
    }

    return $total;
  }

  public function getOrderTotalAmountByDate($date) {
    $query = $this->database->select('commerce_order', 'ord');
    $query->condition('ord.state', ['completed', 'processing', 'pending', 'checkout_complete'], 'IN');
    $query->where("DATE_FORMAT(FROM_UNIXTIME(ord.created), '%Y-%m-%d') = :created", [':created' => $date], '=');
    $query->addExpression('SUM(ord.total_price__number)', 'total');
    $total = $query->execute()->fetchField();
    if (empty($total)) {
      $total = 0;
    }

    return $total;
  }

  public function getOrderTotalAmountByWeek($year, $week) {
    $query = $this->database->select('commerce_order', 'ord');
    $query->condition('ord.state', ['completed', 'processing', 'pending', 'checkout_complete'], 'IN');
    $query->where("YEAR(FROM_UNIXTIME(ord.created)) = :created", [':created' => $year], '=');
    $query->where("WEEKOFYEAR(FROM_UNIXTIME(ord.created)) = :created", [':created' => $week], '=');
    $query->addExpression('SUM(ord.total_price__number)', 'total');
    $total = $query->execute()->fetchField();
    if (empty($total)) {
      $total = 0;
    }

    return $total;
  }

  public function getOrderTotalAmountByMonth($year, $month) {
    $query = $this->database->select('commerce_order', 'ord');
    $query->condition('ord.state', ['completed', 'processing', 'pending', 'checkout_complete'], 'IN');
    $query->where("YEAR(FROM_UNIXTIME(ord.created)) = :created", [':created' => $year], '=');
    $query->where("MONTH(FROM_UNIXTIME(ord.created)) = :created", [':created' => $month], '=');
    $query->addExpression('SUM(ord.total_price__number)', 'total');
    $total = $query->execute()->fetchField();
    if (empty($total)) {
      $total = 0;
    }

    return $total;
  }

  public function getOrdersByDate($date) {
    $query = $this->database->select('commerce_order', 'ord');
    $query->condition('ord.state', ['completed', 'processing', 'pending', 'checkout_complete'], 'IN');
    $query->where("DATE_FORMAT(FROM_UNIXTIME(ord.created), '%Y-%m-%d') = :created", [':created' => $date], '=');
    $query->fields('ord', ['order_id', 'uid']);
    $query->addExpression('ord.total_price__number', 'total');
    $results = $query->execute()->fetchAll();

    $total_orders = 0;
    $total_orders_ids = '';

    if (count($results) > 0) {
      foreach ($results as $row) {
        ++$total_orders;
        if (!empty($total_orders_ids)) {
          $total_orders_ids .= ',';
        }
        $total_orders_ids .= $row->order_id . '|' . $row->total . '|' . $row->uid;
      }
    }

    return [
      'total_orders' => $total_orders,
      'total_orders_ids' => $total_orders_ids,
    ];
  }

  public function getOrdersByWeek($year, $week) {
    $query = $this->database->select('commerce_order', 'ord');
    $query->condition('ord.state', ['completed', 'processing', 'pending', 'checkout_complete'], 'IN');
    $query->where("YEAR(FROM_UNIXTIME(ord.created)) = :created", [':created' => $year], '=');
    $query->where("WEEKOFYEAR(FROM_UNIXTIME(ord.created)) = :created", [':created' => $week], '=');
    $query->fields('ord', ['order_id', 'uid']);
    $query->addExpression('ord.total_price__number', 'total');
    $results = $query->execute()->fetchAll();

    $total_orders = 0;
    $total_orders_ids = '';

    if (count($results) > 0) {
      foreach ($results as $row) {
        ++$total_orders;
        if (!empty($total_orders_ids)) {
          $total_orders_ids .= ',';
        }
        $total_orders_ids .= $row->order_id . '|' . $row->total . '|' . $row->uid;
      }
    }

    return [
      'total_orders' => $total_orders,
      'total_orders_ids' => $total_orders_ids,
    ];
  }

  public function getOrdersByMonth($year, $month) {
    $query = $this->database->select('commerce_order', 'ord');
    $query->condition('ord.state', ['completed', 'processing', 'pending', 'checkout_complete'], 'IN');
    $query->where("YEAR(FROM_UNIXTIME(ord.created)) = :created", [':created' => $year], '=');
    $query->where("MONTH(FROM_UNIXTIME(ord.created)) = :creatd", [':created' => $month], '=');
    $query->fields('ord', ['order_id', 'uid']);
    $query->addExpression('ord.total_price__number', 'total');
    $results = $query->execute()->fetchAll();

    $total_orders = 0;
    $total_orders_ids = '';

    if (count($results) > 0) {
      foreach ($results as $row) {
        ++$total_orders;
        if (!empty($total_orders_ids)) {
          $total_orders_ids .= ',';
        }
        $total_orders_ids .= $row->order_id . '|' . $row->total . '|' . $row->uid;
      }
    }

    return [
      'total_orders' => $total_orders,
      'total_orders_ids' => $total_orders_ids,
    ];
  }

  public function getPoAmountByTypeByDate($date) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_amount', 'pom', "pom.entity_id = n.nid");
    $query->innerJoin('node__field_po_date', 'pod', "pod.entity_id = n.nid");
    $query->innerJoin('node__field_po_type', 'pot', "pot.entity_id = n.nid");
    $query->where("DATE_FORMAT(pod.field_po_date_value, '%Y-%m-%d') = :po_date_value", [':po_date_value' => $date], '=');
    $query->condition('n.type', 'po');
    $query->condition('n.status', 1);
    $query->addExpression('pot.field_po_type_value', 'po_type');
    $query->addExpression("SUM(CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2)))", 'total');
    $query->groupBy('pot.field_po_type_value');
    $results = $query->execute()->fetchAll();

    $total_po_amount = 0;
    $total_po_refund_amount = 0;

    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->po_type == 1) {
          $total_po_amount = $row->total;
        }
        elseif ($row->po_type == 2) {  // Refund
          $total_po_refund_amount = $row->total;
        }
      }
    }

    if (empty($total_po_amount)) {
      $total_po_amount = 0;
    }

    if (empty($total_po_refund_amount)) {
      $total_po_refund_amount = 0;
    }

    return [
      'total_po_amount' => $total_po_amount,
      'total_po_refund_amount' => $total_po_refund_amount,
    ];
  }

  public function getPoAmountByTypeByWeek($year, $week) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_amount', 'pom', "pom.entity_id = n.nid");
    $query->innerJoin('node__field_po_date', 'pod', "pod.entity_id = n.nid");
    $query->innerJoin('node__field_po_type', 'pot', "pot.entity_id = n.nid");
    $query->where("YEAR(pod.field_po_date_value) = :po_date_value", [':po_date_value' => $year], '=');
    $query->where("WEEKOFYEAR(pod.field_po_date_value) = :po_date_value", [':po_date_value' => $week], '=');
    $query->condition('n.type', 'po');
    $query->condition('n.status', 1);
    $query->addExpression('pot.field_po_type_value', 'po_type');
    $query->addExpression("SUM(CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2)))", 'total');
    $query->groupBy('pot.field_po_type_value');
    $results = $query->execute()->fetchAll();

    $total_po_amount = 0;
    $total_po_refund_amount = 0;

    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->po_type == 1) {
          $total_po_amount = $row->total;
        }
        elseif ($row->po_type == 2) {  // Refund
          $total_po_refund_amount = $row->total;
        }
      }
    }

    if (empty($total_po_amount)) {
      $total_po_amount = 0;
    }

    if (empty($total_po_refund_amount)) {
      $total_po_refund_amount = 0;
    }

    return [
      'total_po_amount' => $total_po_amount,
      'total_po_refund_amount' => $total_po_refund_amount,
    ];
  }

  public function getPoAmountByTypeByMonth($year, $month) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_amount', 'pom', "pom.entity_id = n.nid");
    $query->innerJoin('node__field_po_date', 'pod', "pod.entity_id = n.nid");
    $query->innerJoin('node__field_po_type', 'pot', "pot.entity_id = n.nid");
    $query->where("YEAR(pod.field_po_date_value) = :po_date_value", [':po_date_value' => $year], '=');
    $query->where("MONTH(pod.field_po_date_value) = :po_date_value", [':po_date_value' => $month], '=');
    $query->condition('n.type', 'po');
    $query->condition('n.status', 1);
    $query->addExpression('pot.field_po_type_value', 'po_type');
    $query->addExpression("SUM(CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2)))", 'total');
    $query->groupBy('pot.field_po_type_value');
    $results = $query->execute()->fetchAll();

    $total_po_amount = 0;
    $total_po_refund_amount = 0;

    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->po_type == 1) {
          $total_po_amount = $row->total;
        }
        elseif ($row->po_type == 2) {  // Refund
          $total_po_refund_amount = $row->total;
        }
      }
    }

    if (empty($total_po_amount)) {
      $total_po_amount = 0;
    }
    if (empty($total_po_refund_amount)) {
      $total_po_refund_amount = 0;
    }

    return [
      'total_po_amount' => $total_po_amount,
      'total_po_refund_amount' => $total_po_refund_amount,
    ];
  }

  public function getPoCreatedByDate($date) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_po_date', 'pod', "pod.entity_id = n.nid");
    $query->innerJoin('node__field_po_type','pot',"pot.entity_id = n.nid");
    $query->where("DATE_FORMAT(pod.field_po_date_value, '%Y-%m-%d') = :po_date_value", [':po_date_value' => $date], '=');
    $query->condition('n.type', 'po');
    $query->condition('n.status', 1);
    $query->addExpression('pot.field_po_type_value', 'po_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('pot.field_po_type_value');
    $results = $query->execute()->fetchAll();

    $total_po = 0;
    $total_po_refund = 0;

    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->po_type == 1) {
          $total_po = $row->total;
        }
        elseif ($row->po_type == 2) {  // Refund
          $total_po_refund = $row->total;
        }
      }
    }

    if (empty($total_po)) {
      $total_po = 0;
    }

    if (empty($total_po_refund)) {
      $total_po_refund = 0;
    }

    return [
      'total_po' => $total_po,
      'total_po_refund' => $total_po_refund,
    ];
  }

  public function getPoCreatedByWeek($year, $week) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_po_date', 'pod', "pod.entity_id = n.nid");
    $query->innerJoin('node__field_po_type','pot',"pot.entity_id = n.nid");
    $query->where("YEAR(pod.field_po_date_value) = :po_date_value", [':po_date_value' => $year], '=');
    $query->where("WEEKOFYEAR(pod.field_po_date_value) = :po_date_value", [':po_date_value' => $week], '=');
    $query->condition('n.type', 'po');
    $query->condition('n.status', 1);
    $query->addExpression('pot.field_po_type_value', 'po_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('pot.field_po_type_value');
    $results = $query->execute()->fetchAll();

    $total_po = 0;
    $total_po_refund = 0;

    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->po_type == 1) {
          $total_po = $row->total;
        }
        elseif ($row->po_type == 2) {  // Refund
          $total_po_refund = $row->total;
        }
      }
    }

    if (empty($total_po)) {
      $total_po = 0;
    }

    if (empty($total_po_refund)) {
      $total_po_refund = 0;
    }

    return [
      'total_po' => $total_po,
      'total_po_refund' => $total_po_refund,
    ];
  }

  public function getPoCreatedByMonth($year, $month) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_po_date', 'pod', "pod.entity_id = n.nid");
    $query->innerJoin('node__field_po_type','pot',"pot.entity_id = n.nid");
    $query->where("YEAR(pod.field_po_date_value) = :po_date_value", [':po_date_value' => $year], '=');
    $query->where("MONTH(pod.field_po_date_value) = :po_date_value", [':po_date_value' => $month], '=');
    $query->condition('n.type', 'po');
    $query->condition('n.status', 1);
    $query->addExpression('pot.field_po_type_value', 'po_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('pot.field_po_type_value');
    $results = $query->execute()->fetchAll();

    $total_po = 0;
    $total_po_refund = 0;

    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->po_type == 1) {
          $total_po = $row->total;
        }
        elseif ($row->po_type == 2) {  // Refund
          $total_po_refund = $row->total;
        }
      }
    }

    if (empty($total_po)) {
      $total_po = 0;
    }

    if (empty($total_po_refund)) {
      $total_po_refund = 0;
    }

    return [
      'total_po' => $total_po,
      'total_po_refund' => $total_po_refund,
    ];
  }

  public function getTotalSalesAmountByDate($date) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
    $query->condition('ts.field_tx_status_value', ['Successful', 'Invoice', 'Paid', 'Signed', 'Successful - Fee', 'Successful - PSCC', 'Manual'], 'IN');
    $query->where("DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = :tx_date_value", [':tx_date_value' => $date], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression("SUM(am.field_rx_amount_value)", 'total');
    $total = $query->execute()->fetchField();

    return $total;
  }

  public function getTotalSalesAmountByWeek($year, $week) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
    $query->condition('ts.field_tx_status_value', ['Successful', 'Invoice', 'Paid', 'Signed', 'Successful - Fee', 'Successful - PSCC', 'Manual'], 'IN');
    $query->where("YEAR(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $year], '=');
    $query->where("WEEKOFYEAR(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $week], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression("SUM(am.field_rx_amount_value)", 'total');
    $total = $query->execute()->fetchField();

    return $total;
  }

  public function getTotalSalesAmountByMonth($year, $month) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
    $query->condition('ts.field_tx_status_value', ['Successful', 'Invoice', 'Paid', 'Signed', 'Successful - Fee', 'Successful - PSCC', 'Manual'], 'IN');
    $query->where("YEAR(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $year], '=');
    $query->where("MONTH(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $month], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression("SUM(am.field_rx_amount_value)", 'total');
    $total = $query->execute()->fetchField();

    return $total;
  }

  public function getTotalPoAmountByDate($date) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_amount', 'pom', "pom.entity_id = n.nid");
    $query->innerJoin('node__field_po_date', 'pod', "pod.entity_id = n.nid");
    $query->innerJoin('node__field_po_type', 'pot', "pot.entity_id = n.nid");
    $query->where("DATE_FORMAT(pod.field_po_date_value, '%Y-%m-%d') = :po_date_value", [':po_date_value' => $date], '=');
    $query->condition('n.type', 'po');
    $query->condition('n.status', 1);
    $query->condition('pot.field_po_type_value', 1);
    $query->addExpression("SUM(CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2)))", 'total');
    $total = $query->execute()->fetchField();

    return $total;
  }

  public function getTotalPoAmountByWeek($year, $week) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_amount', 'pom', "pom.entity_id = n.nid");
    $query->innerJoin('node__field_po_date', 'pod', "pod.entity_id = n.nid");
    $query->innerJoin('node__field_po_type', 'pot', "pot.entity_id = n.nid");
    $query->where("YEAR(pod.field_po_date_value) = :po_date_value", [':po_date_value' => $year], '=');
    $query->where("WEEKOFYEAR(pod.field_po_date_value) = :po_date_value", [':po_date_value' => $week], '=');
    $query->condition('n.type', 'po');
    $query->condition('n.status', 1);
    $query->condition('pot.field_po_type_value', 1);
    $query->addExpression("SUM(CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2)))", 'total');
    $total = $query->execute()->fetchField();

    return $total;
  }

  public function getTotalPoAmountByMonth($year, $month) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_amount', 'pom', "pom.entity_id = n.nid");
    $query->innerJoin('node__field_po_date', 'pod', "pod.entity_id = n.nid");
    $query->innerJoin('node__field_po_type', 'pot', "pot.entity_id = n.nid");
    $query->where("YEAR(pod.field_po_date_value) = :po_date_value", [':po_date_value' => $year], '=');
    $query->where("MONTH(pod.field_po_date_value) = :po_date_value", [':po_date_value' => $month], '=');
    $query->condition('n.type', 'po');
    $query->condition('n.status', 1);
    $query->condition('pot.field_po_type_value', 1);
    $query->addExpression("SUM(CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2)))", 'total');
    $total = $query->execute()->fetchField();

    return $total;
  }

  public function getTotalRxCreatedByDate($date) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid and od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid and bt.bundle = 'rx'");
    $query->where("DATE_FORMAT(od.field_order_date_value, '%Y-%m-%d') = :order_date_value", [':order_date_value' => $date], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $total_scit_created = 0;
    $total_slit_created = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $total_scit_created = $row->total;
        }
        else {
          $total_slit_created = $row->total;
        }
      }
    }

    return [
      'total_scit_created' => $total_scit_created,
      'total_slit_created' => $total_slit_created,
    ];
  }

  public function getTotalRxCreatedByWeek($year, $week) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid and od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid and bt.bundle = 'rx'");
    $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $year], '=');
    $query->where("WEEKOFYEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $week], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $total_scit_created = 0;
    $total_slit_created = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $total_scit_created = $row->total;
        }
        else {
          $total_slit_created = $row->total;
        }
      }
    }

    return [
      'total_scit_created' => $total_scit_created,
      'total_slit_created' => $total_slit_created,
    ];
  }

  public function getTotalRxCreatedByMonth($year, $month) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid and od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid and bt.bundle = 'rx'");
    $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $year], '=');
    $query->where("MONTH(od.field_order_date_value) = :order_date_value", [':order_date_value' => $month], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $total_scit_created = 0;
    $total_slit_created = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $total_scit_created = $row->total;
        }
        else {
          $total_slit_created = $row->total;
        }
      }
    }

    return [
      'total_scit_created' => $total_scit_created,
      'total_slit_created' => $total_slit_created,
    ];
  }

  public function getTotalRxRefillsByDate($date) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid and od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid and bt.bundle = 'rx'");
    $query->innerJoin('node__field_refill_rx_id', 'rrx', "rrx.entity_id = n.nid and rrx.bundle = 'rx'");
    $query->where("DATE_FORMAT(od.field_order_date_value, '%Y-%m-%d') = :order_date_value", [':order_date_value' => $date], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $total_scit_refill = 0;
    $total_slit_refill = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $total_scit_refill = $row->total;
        }
        else {
          $total_slit_refill = $row->total;
        }
      }
    }

    return [
      'total_scit_refill' => $total_scit_refill,
      'total_slit_refill' => $total_slit_refill,
    ];
  }

  public function getTotalRxRefillsByWeek($year, $week) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid and od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid and bt.bundle = 'rx'");
    $query->innerJoin('node__field_refill_rx_id', 'rrx', "rrx.entity_id = n.nid and rrx.bundle = 'rx'");
    $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $year], '=');
    $query->where("WEEKOFYEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $week], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $total_scit_refill = 0;
    $total_slit_refill = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $total_scit_refill = $row->total;
        }
        else {
          $total_slit_refill = $row->total;
        }
      }
    }

    return [
      'total_scit_refill' => $total_scit_refill,
      'total_slit_refill' => $total_slit_refill,
    ];
  }

  public function getTotalRxRefillsByMonth($year, $month) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid and od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid and bt.bundle = 'rx'");
    $query->innerJoin('node__field_refill_rx_id', 'rrx', "rrx.entity_id = n.nid and rrx.bundle = 'rx'");
    $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $year], '=');
    $query->where("MONTH(od.field_order_date_value) = :order_date_value", [':order_date_value' => $month], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $total_scit_refill = 0;
    $total_slit_refill = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $total_scit_refill = $row->total;
        }
        else {
          $total_slit_refill = $row->total;
        }
      }
    }

    return [
      'total_scit_refill' => $total_scit_refill,
      'total_slit_refill' => $total_slit_refill,
    ];
  }

  public function getRxPaymentsByDate($date) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
    $query->condition('ts.field_tx_status_value', ['Successful', 'Successful - Fee', 'Successful - PSCC', 'Paid', 'Manual', 'Declined', 'Error', 'Refund', 'Invoice', 'Void'], 'IN');
    $query->where("DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = :tx_date_value", [':tx_date_value' => $date], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression('ts.field_tx_status_value', 'status_type');
    $query->addExpression('SUM(am.field_rx_amount_value)', 'total');
    $query->groupBy('ts.field_tx_status_value');
    $results = $query->execute()->fetchAll();

    $total_denied_payment = 0;
    $total_successful_payment = 0;
    $total_refund_payment = 0;
    $total_void_payment = 0;
    $total_error_payment = 0;
    $total_invoice_payment = 0;

    if (count($results) > 0) {
      foreach ($results as $row) {
        if (
          $row->status_type == 'Successful' ||
          $row->status_type == 'Successful - Fee' ||
          $row->status_type == 'Successful - PSCC' ||
          $row->status_type == 'Paid' ||
          $row->status_type == 'Manual'
        ) {
          $total_successful_payment = $total_successful_payment + $row->total;
        }
        elseif ($row->status_type == 'Declined') {
          $total_error_payment = $total_error_payment + $row->total;
        }
        elseif ($row->status_type == 'Error') {
          $total_denied_payment = $total_denied_payment + $row->total;
        }
        elseif ($row->status_type == 'Refund') {
          $total_refund_payment = $total_refund_payment + $row->total;
        }
        elseif ($row->status_type == 'Void') {
          $total_void_payment = $total_void_payment + $row->total;
        }
      }
    }

    return [
      'total_successful_payment' => $total_successful_payment,
      'total_error_payment' => $total_error_payment,
      'total_denied_payment' => $total_denied_payment,
      'total_refund_payment' => $total_refund_payment,
      'total_void_payment' => $total_void_payment,
    ];
  }

  public function getRxPaymentsByWeek($year, $week) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
    $query->condition('ts.field_tx_status_value', ['Successful', 'Successful - Fee', 'Successful - PSCC', 'Paid', 'Manual', 'Declined', 'Error', 'Refund', 'Invoice', 'Void'], 'IN');
    $query->where("YEAR(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $year], '=');
    $query->where("WEEKOFYEAR(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $week], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression('ts.field_tx_status_value', 'status_type');
    $query->addExpression('SUM(am.field_rx_amount_value)', 'total');
    $query->groupBy('ts.field_tx_status_value');
    $results = $query->execute()->fetchAll();

    $total_denied_payment = 0;
    $total_successful_payment = 0;
    $total_refund_payment = 0;
    $total_void_payment = 0;
    $total_error_payment = 0;
    $total_invoice_payment = 0;

    if (count($results) > 0) {
      foreach ($results as $row) {
        if (
          $row->status_type == 'Successful' ||
          $row->status_type == 'Successful - Fee' ||
          $row->status_type == 'Successful - PSCC' ||
          $row->status_type == 'Paid' ||
          $row->status_type == 'Manual'
        ) {
          $total_successful_payment = $total_successful_payment + $row->total;
        }
        elseif ($row->status_type == 'Declined') {
          $total_error_payment = $total_error_payment + $row->total;
        }
        elseif ($row->status_type == 'Error') {
          $total_denied_payment = $total_denied_payment + $row->total;
        }
        elseif ($row->status_type == 'Refund') {
          $total_refund_payment = $total_refund_payment + $row->total;
        }
        elseif ($row->status_type == 'Void') {
          $total_void_payment = $total_void_payment + $row->total;
        }
      }
    }

    return [
      'total_successful_payment' => $total_successful_payment,
      'total_error_payment' => $total_error_payment,
      'total_denied_payment' => $total_denied_payment,
      'total_refund_payment' => $total_refund_payment,
      'total_void_payment' => $total_void_payment,
    ];
  }

  public function getRxPaymentsByMonth($year, $month) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
    $query->condition('ts.field_tx_status_value', ['Successful', 'Successful - Fee', 'Successful - PSCC', 'Paid', 'Manual', 'Declined', 'Error', 'Refund', 'Invoice', 'Void'], 'IN');
    $query->where("YEAR(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $year], '=');
    $query->where("MONTH(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $month], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->addExpression('ts.field_tx_status_value', 'status_type');
    $query->addExpression('SUM(am.field_rx_amount_value)', 'total');
    $query->groupBy('ts.field_tx_status_value');
    $results = $query->execute()->fetchAll();

    $total_denied_payment = 0;
    $total_successful_payment = 0;
    $total_refund_payment = 0;
    $total_void_payment = 0;
    $total_error_payment = 0;
    $total_invoice_payment = 0;

    if (count($results) > 0) {
      foreach ($results as $row) {
        if (
          $row->status_type == 'Successful' ||
          $row->status_type == 'Successful - Fee' ||
          $row->status_type == 'Successful - PSCC' ||
          $row->status_type == 'Paid' ||
          $row->status_type == 'Manual'
        ) {
          $total_successful_payment = $total_successful_payment + $row->total;
        }
        elseif ($row->status_type == 'Declined') {
          $total_error_payment = $total_error_payment + $row->total;
        }
        elseif ($row->status_type == 'Error') {
          $total_denied_payment = $total_denied_payment + $row->total;
        }
        elseif ($row->status_type == 'Refund') {
          $total_refund_payment = $total_refund_payment + $row->total;
        }
        elseif ($row->status_type == 'Void') {
          $total_void_payment = $total_void_payment + $row->total;
        }
      }
    }

    return [
      'total_successful_payment' => $total_successful_payment,
      'total_error_payment' => $total_error_payment,
      'total_denied_payment' => $total_denied_payment,
      'total_refund_payment' => $total_refund_payment,
      'total_void_payment' => $total_void_payment,
    ];
  }

  public function getRxInvoicePaymentByDate($date) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
    $query->innerJoin('node__field_paymenttype', 'pt', "pt.entity_id = n.nid");
    $query->condition('ts.field_tx_status_value', ['Payment Pending'], 'IN');
    $query->where("DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = :tx_date_value", [':tx_date_value' => $date], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('pt.field_paymenttype_value', 3); // Invoice
    $query->addExpression('SUM(am.field_rx_amount_value)', 'total');
    $total_invoice_payment = $query->execute()->fetchField();
    if (empty($total_invoice_payment)) {
      $total_invoice_payment = 0;
    }

    return $total_invoice_payment;
  }

  public function getRxInvoicePaymentByWeek($year, $week) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
    $query->innerJoin('node__field_paymenttype', 'pt', "pt.entity_id = n.nid");
    $query->condition('ts.field_tx_status_value', ['Payment Pending'], 'IN');
    $query->where("YEAR(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $year], '=');
    $query->where("WEEKOFYEAR(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $week], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('pt.field_paymenttype_value', 3); // Invoice
    $query->addExpression('SUM(am.field_rx_amount_value)', 'total');
    $total_invoice_payment = $query->execute()->fetchField();
    if (empty($total_invoice_payment)) {
      $total_invoice_payment = 0;
    }

    return $total_invoice_payment;
  }

  public function getRxInvoicePaymentByMonth($year, $month) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
    $query->innerJoin('node__field_paymenttype', 'pt', "pt.entity_id = n.nid ");
    $query->condition('ts.field_tx_status_value', ['Payment Pending'], 'IN');
    $query->where("YEAR(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $year], '=');
    $query->where("MONTH(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $month], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('pt.field_paymenttype_value', 3);  // Invoice
    $query->addExpression('SUM(am.field_rx_amount_value)', 'total');
    $total_invoice_payment = $query->execute()->fetchField();

    if (empty($total_invoice_payment)) {
      $total_invoice_payment = 0;
    }

    return $total_invoice_payment;
  }

  public function getTotalRxPendingByDate($date, $date_reference = '') {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_subscription_no', 'sub',"sub.entity_id = n.nid AND sub.bundle = 'rx'");
    $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
    $query->condition('ts.field_tx_status_value', ['Payment Pending'], 'IN');

    if (!empty($date_reference)) {
      $query->condition("td.field_tx_date_value", [$date, $date_reference], 'BETWEEN');
    }
    else {
      $query->condition('td.field_tx_date_value', $date, '<=');
    }

    $query->condition('sub.field_subscription_no_value', '', '<>');
    $query->condition('ws.field_workflow_status_value', ['Cancelled', 'Completed'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->fields('n', ['nid']);
    $query->addExpression('am.field_rx_amount_value', 'amount');
    $results = $query->execute()->fetchAll();

    $total_rx_nids = [];
    $total_rx_arr = [];

    if (count($results) > 0) {
      foreach ($results as $row) {
        $total_rx_nids[$row->nid] = $row->nid . '|' . $row->amount;
        $total_rx_arr[$row->nid] = $row->nid;
      }
    }

    // Count
    $total_rx = count($total_rx_arr);
    $total_rx_nids = implode(',', $total_rx_nids);

    return [
      'total_rx' => $total_rx,
      'total_rx_nids' => $total_rx_nids,
    ];
  }

  public function getTotalRxScheduledByDate($date, $date_reference = '') {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_shipping_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_shipping_info_target_id and p.type = 'shipping_info'");
    $query->innerJoin('paragraph__field_scheduled_date', 'sd', "sd.entity_id = fc.field_shipping_info_target_id AND sd.bundle = 'shipping_info'");
    $query->innerJoin('paragraph__field_shipping_status', 'ss', "ss.entity_id = fc.field_shipping_info_target_id AND ss.bundle = 'shipping_info'");
    $query->innerJoin('paragraph__field_sequence_number', 'sn', "sn.entity_id = fc.field_shipping_info_target_id AND sn.bundle = 'shipping_info'");
    $query->condition('ss.field_shipping_status_value', ['Scheduled'], 'IN');

    if (!empty($date_reference)) {
      $query->condition('sd.field_scheduled_date_value', [$date, $date_reference], 'BETWEEN');
    }
    else {
      $query->condition('sd.field_scheduled_date_value', $date, '<=');
    }

    $query->condition('ws.field_workflow_status_value', ['Cancelled', 'Completed'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->fields('n', ['nid']);
    $query->addExpression('sn.field_sequence_number_value', 'sequence');
    $results = $query->execute()->fetchAll();

    $total_rx = 0;
    $total_rx_nids = [];
    $total_rx_arr = [];

    if (count($results) > 0) {
      foreach ($results as $row) {
        $bill_amount = 0;
        /** @var \Drupal\node\NodeInterface $node */
        $node = $this->entityTypeManager->getStorage('node')->load($row->nid);
        $rx_amount = $node->hasField('field_rx_amount') && !$node->get('field_rx_amount')->isEmpty() ?
          floatval($node->get('field_rx_amount')->value) : 0;
        $sequence = (isset($row->sequence) && !empty($row->sequence) ? $row->sequence : 1);

        $bills = $node->hasField('field_billing_info') && !$node->get('field_billing_info')->isEmpty() ?
          $node->get('field_billing_info')->referencedEntities() : [];
        if (count($bills) > 0) {
          /** @var \Drupal\paragraphs\ParagraphInterface $entity */
          foreach ($bills as $entity) {
            $field_tx_date = $entity->hasField('field_tx_date') && !$entity->get('field_tx_date')->isEmpty() ?
              date('Y-m-d', strtotime($entity->get('field_tx_date')->value)) : '';
            $field_rx_amount = $entity->hasField('field_rx_amount') && !$entity->get('field_rx_amount')->isEmpty() ?
              '$ ' . $entity->get('field_rx_amount')->value : 0;
            $field_tx_status = $entity->hasField('field_tx_status') && !$entity->get('field_tx_status')->isEmpty() ?
              $entity->get('field_tx_status')->value : '';
            if ($field_tx_status == 'Successful') {
              $bill_amount = $bill_amount + floatval($field_rx_amount);
            }
          }
        }

        $bill_check_amount_1 = $rx_amount * 1;
        $bill_check_amount_2 = $rx_amount * 3;
        $bill_check_amount_3 = $rx_amount * 6;
        $bill_check_amount_4 = $rx_amount * 9;

        if ($sequence == 1 && $bill_amount < $bill_check_amount_1) {
          $total_rx_nids[$row->nid] = $row->nid;
          $total_rx_arr[$row->nid] = $row->nid;
        }
        elseif ($sequence == 2 && $bill_amount < $bill_check_amount_2) {
          $total_rx_nids[$row->nid] = $row->nid;
          $total_rx_arr[$row->nid] = $row->nid;
        }
        elseif ($sequence == 3 && $bill_amount < $bill_check_amount_3) {
          $total_rx_nids[$row->nid] = $row->nid;
          $total_rx_arr[$row->nid] = $row->nid;
        }
        elseif ($sequence == 4 && $bill_amount < $bill_check_amount_4) {
          $total_rx_nids[$row->nid] = $row->nid;
          $total_rx_arr[$row->nid] = $row->nid;
        }
      }
    }

    // Count
    $total_rx = count($total_rx_arr);
    $total_rx_nids = implode(',', $total_rx_nids);

    return [
      'total_rx' => $total_rx,
      'total_rx_nids' => $total_rx_nids,
    ];
  }

  public function getTotalUpcomingRxRefillsByDate($from_date, $to_date) {
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_shipping_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_shipping_info_target_id and p.type = 'shipping_info'");
    $query->innerJoin('paragraph__field_scheduled_date', 'sd', "sd.entity_id = fc.field_shipping_info_target_id AND sd.bundle = 'shipping_info'");
    $query->innerJoin('paragraph__field_shipping_status', 'ss', "ss.entity_id = fc.field_shipping_info_target_id AND ss.bundle = 'shipping_info'");
    $query->condition('ss.field_shipping_status_value', ['Scheduled'], 'IN');
    $query->condition('sd.field_scheduled_date_value', [$from_date, $to_date], 'BETWEEN');
    $query->condition('ws.field_workflow_status_value', ['Cancelled', 'Completed'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->fields('n', ['nid']);
    $query->addExpression('sd.field_scheduled_date_value', 'scheduled_date');
    $results = $query->execute()->fetchAll();

    $total_rx_nids = [];
    $total_rx_arr = [];

    if (count($results) > 0) {
      foreach ($results as $row) {
        $scheduled_date = (isset($row->scheduled_date) ? date('Y-m-d', strtotime($row->scheduled_date)) : '');

        $total_rx_nids[$row->nid] = $row->nid . '|' . $scheduled_date;
        $total_rx_arr[$row->nid] = $row->nid;
      }
    }

    // Count total rx
    $total_rx = count($total_rx_arr);
    $total_rx_nids = implode(',', $total_rx_nids);

    return [
      'total_rx' => $total_rx,
      'total_rx_nids' => $total_rx_nids,
    ];
  }

  public function getTotalExpiringRxByDate($date, $date_reference = '') {
    if (empty($date_reference)) {
      $date_reference = date('Y-m-d', strtotime('+10 days'));
    }

    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_end_date', 'ed', "ed.entity_id = n.nid AND ed.bundle = 'rx'");
    $query->innerJoin('node__field_rx_amount', 'ra', "ra.entity_id = n.nid AND ed.bundle = 'rx'");
    $query->condition('ed.field_end_date_value', [$date, $date_reference], 'BETWEEN');
    $query->condition('ws.field_workflow_status_value', ['Cancelled', 'Completed'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->fields('n', ['nid']);
    $query->addExpression('ed.field_end_date_value', 'end_date');
    $query->addExpression('ra.field_rx_amount_value', 'amount');
    $results = $query->execute()->fetchAll();

    $total_rx_nids = [];
    $total_rx_arr = [];

    if (count($results) > 0) {
      foreach ($results as $row) {
        $total_rx_nids[$row->nid] = $row->nid . '|' . $row->amount;
        $total_rx_arr[$row->nid] = $row->nid;
      }
    }

    // Count
    $total_rx = count($total_rx_arr);
    $total_rx_nids = implode(',', $total_rx_nids);

    return [
      'total_rx' => $total_rx,
      'total_rx_nids' => $total_rx_nids,
    ];
  }

  public function getTotalExpiringArbByDate($date, $date_reference = '') {
    if (empty($date_reference)) {
      $date_reference = date('Y-m-d', strtotime('+30 days'));
    }

    $query = $this->database->select('authorizenet_profiles', 'n');
    $query->condition('n.subscription_end_date', [$date, $date_reference], 'BETWEEN');
    $query->condition('n.profile_status', 1);
    $query->fields('n', ['subscription_id', 'subscription_end_date']);
    $results = $query->execute()->fetchAll();

    $total_subscriptions = 0;
    $total_subscriptions_ids = '';

    if (count($results) > 0) {
      foreach ($results as $row) {
        ++$total_subscriptions;
        if (!empty($total_subscriptions_ids)) {
          $total_subscriptions_ids .= ',';
        }
        $total_subscriptions_ids .= $row->subscription_id . '|' . $row->subscription_end_date;
      }
    }

    return [
      'total_subscriptions' => $total_subscriptions,
      'total_subscriptions_ids' => $total_subscriptions_ids,
    ];
  }

  public function getTotalExpiringCc($date, $date_reference = '') {
    if (empty($date_reference)) {
      $date_reference = date('Y-m-d', strtotime('+30 days'));
    }

    $query = $this->database->select('authorizenet_profiles', 'n');
    $query->condition('n.cc_notification_date', [$date, $date_reference], 'BETWEEN');
    $query->condition('n.profile_status', 1);
    $query->fields('n', ['subscription_id', 'cc_notification_date']);
    $results = $query->execute()->fetchAll();

    $total_subscriptions = 0;
    $total_subscriptions_ids = '';

    if (count($results) > 0) {
      foreach ($results as $row) {
        ++$total_subscriptions;
        if (!empty($total_subscriptions_ids)) {
          $total_subscriptions_ids .= ',';
        }
        $total_subscriptions_ids .= $row->subscription_id . '|' . $row->cc_notification_date;
      }
    }

    return [
      'total_subscriptions' => $total_subscriptions,
      'total_subscriptions_ids' => $total_subscriptions_ids,
    ];
  }

  public function getTotalExpiringProfileCcByDate($date, $date_reference = '') {
    if (empty($date_reference)) {
      $date_reference = date('Y-m-d', strtotime('+30 days'));
    }

    $query = $this->database->select('authorizenet_customer_payment_profiles', 'n');
    $query->condition('n.cc_notification_date', [$date, $date_reference], 'BETWEEN');
    $query->condition('n.profile_status', 1);
    $query->fields('n', ['customer_profile_id', 'customer_payment_profile_id', 'cc_notification_date']);
    $results = $query->execute()->fetchAll();

    $total_profiles = 0;
    $total_profiles_ids = '';

    if (count($results) > 0) {
      foreach ($results as $row) {
        ++$total_profiles;
        if (!empty($total_profiles_ids)) {
          $total_profiles_ids .= ',';
        }
        $total_profiles_ids .= $row->customer_profile_id . '|' . $row->customer_payment_profile_id . '|' . $row->cc_notification_date;
      }
    }

    return [
      'total_profiles' => $total_profiles,
      'total_profiles_ids' => $total_profiles_ids,
    ];
  }

  public function getTotalClinicsByDate() {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->condition('type', 'clinic')
      ->condition('status', 1)
      ->accessCheck(FALSE);

    $items_nids = $query->execute();
    $total_clinics = count($items_nids);
    if (empty($total_clinics)) {
      $total_clinics = 0;
    }

    return $total_clinics;
  }

  public function getSilentPostSummaryByYear($year) {
    $query = $this->database->select('silentpost', 'n');
    $query->where("FROM_UNIXTIME(n.post_date, '%Y') = :post_date", [':post_date' => $year], '=');
    $query->addExpression('n.resolved', 'resolved_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('n.resolved');
    $results = $query->execute()->fetchAll();

    $total_resolved = 0;
    $total_pending = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->resolved_type == 1) {  // Yes
          $total_resolved = $row->total;
        }
        elseif ($row->resolved_type == 2) {  // No
          $total_pending = $row->total;
        }
      }
    }

    return [
      'total_resolved' => $total_resolved,
      'total_pending' => $total_pending,
    ];
  }

  public function getClinicTotalRxCreatedByRxTypeByDate($date) {
    $subquery = $this->database->select('user__roles', 'ur');
    $subquery->condition('ur.roles_target_id', ['clinic_provider_representative', 'testing_admin', 'provider'], 'IN');
    $subquery->addField('ur', 'entity_id', 'uid');
    $subquery->distinct();

    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
    $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
    $query->where("DATE_FORMAT(od.field_order_date_value, '%Y-%m-%d') = :order_date_value", [':order_date_value' => $date], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('u.uid', $subquery, 'IN');
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $clinic_scit_created = 0;
    $clinic_slit_created = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $clinic_scit_created = $row->total;
        }
        else {
          $clinic_slit_created = $row->total;
        }
      }
    }

    return [
      'slit_created' => $clinic_slit_created,
      'scit_created' => $clinic_scit_created,
    ];
  }

  public function getClinicTotalRxCreatedByRxTypeByWeek($year, $week) {
    $subquery = $this->database->select('user__roles', 'ur');
    $subquery->condition('ur.roles_target_id', ['clinic_provider_representative', 'testing_admin', 'provider'], 'IN');
    $subquery->addField('ur', 'entity_id', 'uid');
    $subquery->distinct();

    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
    $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
    $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $year], '=');
    $query->where("WEEKOFYEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $week], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('u.uid', $subquery, 'IN');
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $clinic_scit_created = 0;
    $clinic_slit_created = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $clinic_scit_created = $row->total;
        }
        else {
          $clinic_slit_created = $row->total;
        }
      }
    }

    return [
      'slit_created' => $clinic_slit_created,
      'scit_created' => $clinic_scit_created,
    ];
  }

  public function getClinicTotalRxCreatedByRxTypeByMonth($year, $month) {
    $subquery = $this->database->select('user__roles', 'ur');
    $subquery->condition('ur.roles_target_id', ['clinic_provider_representative', 'testing_admin', 'provider'], 'IN');
    $subquery->addField('ur', 'entity_id', 'uid');
    $subquery->distinct();

    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
    $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
    $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $year], '=');
    $query->where("MONTH(od.field_order_date_value) = :order_date_value", [':order_date_value' => $month], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('u.uid', $subquery, 'IN');
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $clinic_scit_created = 0;
    $clinic_slit_created = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $clinic_scit_created = $row->total;
        }
        else {
          $clinic_slit_created = $row->total;
        }
      }
    }

    return [
      'slit_created' => $clinic_slit_created,
      'scit_created' => $clinic_scit_created,
    ];
  }

  public function getStaffTotalRxCreatedByRxTypeByDate($date) {
    $subquery = $this->database->select('user__roles', 'ur');
    $subquery->condition('ur.roles_target_id', ['pharmacy', 'clinical_support', 'field_rep', 'order_manager', 'administrator', 'website_manager', 'call_center'], 'IN');
    $subquery->addField('ur', 'entity_id', 'uid');
    $subquery->distinct();

    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
    $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
    $query->where("DATE_FORMAT(od.field_order_date_value, '%Y-%m-%d') = :order_date_value", [':order_date_value' => $date], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('u.uid', $subquery, 'IN');
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $staff_scit_created = 0;
    $staff_slit_created = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $staff_scit_created = $row->total;
        }
        else {
          $staff_slit_created = $row->total;
        }
      }
    }

    return [
      'slit_created' => $staff_slit_created,
      'scit_created' => $staff_scit_created,
    ];
  }

  public function getStaffTotalRxCreatedByRxTypeByWeek($year, $week) {
    $subquery = $this->database->select('user__roles', 'ur');
    $subquery->condition('ur.roles_target_id', ['pharmacy', 'clinical_support', 'field_rep', 'order_manager', 'administrator', 'website_manager', 'call_center'], 'IN');
    $subquery->addField('ur', 'entity_id', 'uid');
    $subquery->distinct();

    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
    $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
    $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $year], '=');
    $query->where("WEEKOFYEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $week], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('u.uid', $subquery, 'IN');
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $staff_scit_created = 0;
    $staff_slit_created = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $staff_scit_created = $row->total;
        }
        else {
          $staff_slit_created = $row->total;
        }
      }
    }


    return [
      'slit_created' => $staff_slit_created,
      'scit_created' => $staff_scit_created,
    ];
  }

  public function getStaffTotalRxCreatedByRxTypeByMonth($year, $month) {
    $subquery = $this->database->select('user__roles', 'ur');
    $subquery->condition('ur.roles_target_id', ['pharmacy', 'clinical_support', 'field_rep', 'order_manager', 'administrator', 'website_manager', 'call_center'], 'IN');
    $subquery->addField('ur', 'entity_id', 'uid');
    $subquery->distinct();

    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
    $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
    $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $year], '=');
    $query->where("MONTH(od.field_order_date_value) = :order_date_value", [':order_date_value' => $month], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('u.uid', $subquery, 'IN');
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $staff_scit_created = 0;
    $staff_slit_created = 0;
    if (count($results)>0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $staff_scit_created = $row->total;
        }
        else {
          $staff_slit_created = $row->total;
        }
      }
    }

    return [
      'slit_created' => $staff_slit_created,
      'scit_created' => $staff_scit_created,
    ];
  }

  public function getClinicTotalRxRefillByRxTypeByDate($date) {
    $subquery = $this->database->select('user__roles', 'ur');
    $subquery->condition('ur.roles_target_id', ['clinic_provider_representative', 'testing_admin', 'provider'], 'IN');
    $subquery->addField('ur', 'entity_id', 'uid');
    $subquery->distinct();

    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
    $query->innerJoin('node__field_refill_rx_id', 'rrx', "rrx.entity_id = n.nid and rrx.bundle = 'rx'");
    $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
    $query->where("DATE_FORMAT(od.field_order_date_value, '%Y-%m-%d') = :order_date_value", [':order_date_value' => $date], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('u.uid', $subquery, 'IN');
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $clinic_scit_refill = 0;
    $clinic_slit_refill = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $clinic_scit_refill = $row->total;
        }
        else {
          $clinic_slit_refill = $row->total;
        }
      }
    }

    return [
      'slit_refill' => $clinic_slit_refill,
      'scit_refill' => $clinic_scit_refill,
    ];
  }

  public function getClinicTotalRxRefillByRxTypeByWeek($year, $week) {
    $subquery = $this->database->select('user__roles', 'ur');
    $subquery->condition('ur.roles_target_id', ['clinic_provider_representative', 'testing_admin', 'provider'], 'IN');
    $subquery->addField('ur', 'entity_id', 'uid');
    $subquery->distinct();

    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
    $query->innerJoin('node__field_refill_rx_id', 'rrx', "rrx.entity_id = n.nid and rrx.bundle = 'rx'");
    $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
    $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $year], '=');
    $query->where("WEEKOFYEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $week], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('u.uid', $subquery, 'IN');
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $clinic_scit_refill = 0;
    $clinic_slit_refill = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $clinic_scit_refill = $row->total;
        }
        else {
          $clinic_slit_refill = $row->total;
        }
      }
    }

    return [
      'slit_refill' => $clinic_slit_refill,
      'scit_refill' => $clinic_scit_refill,
    ];
  }

  public function getClinicTotalRxRefillByRxTypeByMonth($year, $month) {
    $subquery = $this->database->select('user__roles', 'ur');
    $subquery->condition('ur.roles_target_id', ['clinic_provider_representative', 'testing_admin', 'provider'], 'IN');
    $subquery->addField('ur', 'entity_id', 'uid');
    $subquery->distinct();

    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
    $query->innerJoin('node__field_refill_rx_id', 'rrx', "rrx.entity_id = n.nid and rrx.bundle = 'rx'");
    $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
    $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $year], '=');
    $query->where("MONTH(od.field_order_date_value) = :order_date_value", [':order_date_value' => $month], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('u.uid', $subquery, 'IN');
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $clinic_scit_refill = 0;
    $clinic_slit_refill = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $clinic_scit_refill = $row->total;
        }
        else {
          $clinic_slit_refill = $row->total;
        }
      }
    }

    return [
      'slit_refill' => $clinic_slit_refill,
      'scit_refill' => $clinic_scit_refill,
    ];
  }

  public function getStaffTotalRxRefillByRxTypeByDate($date) {
    $subquery = $this->database->select('user__roles', 'ur');
    $subquery->condition('ur.roles_target_id', ['pharmacy', 'clinical_support', 'field_rep', 'order_manager', 'administrator', 'website_manager', 'call_center'], 'IN');
    $subquery->addField('ur', 'entity_id', 'uid');
    $subquery->distinct();

    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
    $query->innerJoin('node__field_refill_rx_id', 'rrx', "rrx.entity_id = n.nid and rrx.bundle = 'rx'");
    $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
    $query->where("DATE_FORMAT(od.field_order_date_value, '%Y-%m-%d') = :order_date_value", [':order_date_value' => $date], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('u.uid', $subquery, 'IN');
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $staff_scit_refill = 0;
    $staff_slit_refill = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $staff_scit_refill = $row->total;
        }
        else {
          $staff_slit_refill = $row->total;
        }
      }
    }

    return [
      'slit_refill' => $staff_slit_refill,
      'scit_refill' => $staff_scit_refill,
    ];
  }

  public function getStaffTotalRxRefillByRxTypeByWeek($year, $week) {
    $subquery = $this->database->select('user__roles', 'ur');
    $subquery->condition('ur.roles_target_id', ['pharmacy', 'clinical_support', 'field_rep', 'order_manager', 'administrator', 'website_manager', 'call_center'], 'IN');
    $subquery->addField('ur', 'entity_id', 'uid');
    $subquery->distinct();

    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
    $query->innerJoin('node__field_refill_rx_id', 'rrx', "rrx.entity_id = n.nid and rrx.bundle = 'rx'");
    $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
    $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => date("Y")], '=');
    $query->where("WEEKOFYEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => date("W")], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('u.uid', $subquery, 'IN');
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $staff_scit_refill = 0;
    $staff_slit_refill = 0;
    if (count($results)>0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $staff_scit_refill = $row->total;
        }
        else {
          $staff_slit_refill = $row->total;
        }
      }
    }

    return [
      'slit_refill' => $staff_slit_refill,
      'scit_refill' => $staff_scit_refill,
    ];
  }

  public function getStaffTotalRxRefillByRxTypeByMonth($year, $month) {
    $subquery = $this->database->select('user__roles', 'ur');
    $subquery->condition('ur.roles_target_id', ['pharmacy', 'clinical_support', 'field_rep', 'order_manager', 'administrator', 'website_manager', 'call_center'], 'IN');
    $subquery->addField('ur', 'entity_id', 'uid');
    $subquery->distinct();

    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
    $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
    $query->innerJoin('node__field_refill_rx_id', 'rrx', "rrx.entity_id = n.nid and rrx.bundle = 'rx'");
    $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
    $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => date("Y")], '=');
    $query->where("MONTH(od.field_order_date_value) = :order_date_value", [':order_date_value' => date("n")], '=');
    $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->condition('u.uid', $subquery, 'IN');
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('COUNT(*)', 'total');
    $query->groupBy('bt.field_rx_base_type_value');
    $results = $query->execute()->fetchAll();

    $staff_scit_refill = 0;
    $staff_slit_refill = 0;
    if (count($results) > 0) {
      foreach ($results as $row) {
        if ($row->base_type == 'SCIT') {
          $staff_scit_refill = $row->total;
        }
        else {
          $staff_slit_refill = $row->total;
        }
      }
    }

    return [
      'slit_refill' => $staff_slit_refill,
      'scit_refill' => $staff_scit_refill,
    ];
  }


}
