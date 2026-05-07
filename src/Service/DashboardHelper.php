<?php

namespace Drupal\custom_example\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;


/**
 * Dashboard Helper Class.
 *
 * @package Drupal\custom_example
 */
class DashboardHelper {
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
   * @var QueueFactory
   */
  protected $queueFactory;

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
    QueueFactory $queue
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $current_account;
    $this->database = $database;
    $this->logger = $logger->get('custom_example');
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
    $this->queueFactory = $queue;
  }

  /**
   * Compare the present and past and return the change status with marker
   */
  public function getChangeStatus($present, $past) {
    $change = '';
    $change_marker = '';
    if ($present > $past && $past != 0) {  // increase
      $increase = ($present - $past) / $past * 100;
      $change = number_format($increase,0) . '%';
      $change_marker = '<i class="fa fa-sort-desc" aria-hidden="true">&nbsp;</i>';
    }
    elseif ($present < $past && $past != 0) {  // decrease
      $decrease = ($past - $present) / $past * 100;
      $change = number_format($decrease,0) . '%';
      $change_marker = '<i class="fa fa-sort-asc" aria-hidden="true">&nbsp;</i>';
    }
    elseif ($present > $past && $past == 0) {  // increase
      $increase = 100;
      $change = $increase . '%';
      $change_marker = '<i class="fa fa-sort-desc" aria-hidden="true">&nbsp;</i>';
    }
    elseif ($present < $past && $present == 0) {  // decrease
      $decrease = 100;
      $change = $decrease . '%';
      $change_marker = '<i class="fa fa-sort-asc" aria-hidden="true">&nbsp;</i>';
    }
    elseif ($present == $past && $present == 0 && $past == 0) {  // decrease
      $decrease = 0;
      $change = $decrease . '%';
      $change_marker = '';
    }
    elseif ($present == $past && $present != 0 && $past != 0) {  // decrease
      $decrease = 0;
      $change = $decrease . '%';
      $change_marker = '';
    }

    $status = [];
    $status['change'] = $change;
    $status['marker'] = $change_marker;

    return $status;
  }

  /**
   * Dashboard cron
   *
   */
  public function processQueuePages() {
    // 1
    $queue = $this->queueFactory->get('custom_example_total_amount_collected_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 1
    ]);


    // 2
    $queue = $this->queueFactory->get('custom_example_total_rx_created_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 2
    ]);

    // 3
    $queue = $this->queueFactory->get('custom_example_total_rx_refills_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 3
    ]);

    // 4
    $queue = $this->queueFactory->get('custom_example_total_rx_payments_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 4
    ]);

    // 5
    $queue = $this->queueFactory->get('custom_example_total_rx_pending_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 5
    ]);

    // 6
    $queue = $this->queueFactory->get('custom_example_total_rx_scheduled_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 6
    ]);

    // 7
    $queue = $this->queueFactory->get('custom_example_total_rx_shipment_refills_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 7
    ]);

    // 8
    $queue = $this->queueFactory->get('custom_example_total_rx_expiring_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 8
    ]);

    // 9
    $queue = $this->queueFactory->get('custom_example_total_expiring_arb_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 9
    ]);

    // 10
    $queue = $this->queueFactory->get('custom_example_total_expiring_cc_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 10
    ]);

    // 11
    $queue = $this->queueFactory->get('custom_example_total_profile_expiring_cc_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 11
    ]);

    // 12
    $queue = $this->queueFactory->get('custom_example_order_amount_collected_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 12
    ]);

    // 13
    $queue = $this->queueFactory->get('custom_example_total_order_submitted_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 13
    ]);

    // 14
    $queue = $this->queueFactory->get('custom_example_total_clinics_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 14
    ]);

    // 15
    $queue = $this->queueFactory->get('custom_example_silent_post_summary_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 15
    ]);

    // 16
    $queue = $this->queueFactory->get('custom_example_po_amount_collected_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 16
    ]);

    // 17
    $queue = $this->queueFactory->get('custom_example_total_po_created_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 17
    ]);

    // 18
    $queue = $this->queueFactory->get('custom_example_total_sales_amount_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 18
    ]);

    // 19
    $queue = $this->queueFactory->get('custom_example_clinic_vs_staff_rx_created_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 19
    ]);

    // 20
    $queue = $this->queueFactory->get('custom_example_clinic_vs_staff_rx_refills_queue');
    $queue->createQueue();
    $queue->createItem([
      'created' => time(),
      'sequence' => 20
    ]);

    $this->logger->info('Cron run completed @ ' . date('Y-m-d H:i:s'));

    $message = $this->t('All jobs have been scheduled for cron.');

    return $message;
  }


}
