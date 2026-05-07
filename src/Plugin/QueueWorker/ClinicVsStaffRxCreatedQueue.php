<?php

namespace Drupal\custom_example\Plugin\QueueWorker;

use Drupal\Core\Annotation\QueueWorker;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\mrs_base\Service\BaseHelper;
use Drupal\custom_example\Service\DashboardHelper;
use Drupal\custom_example\Service\DashboardManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'custom_example_clinic_vs_staff_rx_created_queue' queue worker.
 *
 * @QueueWorker(
 *   id = "custom_example_clinic_vs_staff_rx_created_queue",
 *   title = @Translation("Clinic vs Staff Rx Created Queue"),
 *   cron = {"time" = 60},
 * )
 */
class ClinicVsStaffRxCreatedQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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
   * Base Helper service.
   *
   * @var \Drupal\mrs_base\Service\BaseHelper
   */
  protected $baseHelper;

  /**
   * Dashboard Helper service.
   *
   * @var \Drupal\custom_example\Service\DashboardHelper
   */
  protected $dashboardHelper;

  /**
   * Dashboard Manager service.
   *
   * @var \Drupal\custom_example\Service\DashboardManager
   */
  protected $dashboardManager;

  /**
   * Constructs a new instance.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_account,
    Connection $database,
    LoggerChannelFactoryInterface $logger,
    MessengerInterface $messenger,
    ConfigFactoryInterface $config_factory,
    BaseHelper $base_helper,
    DashboardHelper $dashboard_helper,
    DashboardManager $dashboard_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->account = $current_account;
    $this->database = $database;
    $this->logger = $logger->get('custom_example');
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
    $this->baseHelper = $base_helper;
    $this->dashboardHelper = $dashboard_helper;
    $this->dashboardManager = $dashboard_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('database'),
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('config.factory'),
      $container->get('base.helper'),
      $container->get('dashboard.helper'),
      $container->get('dashboard.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->logger->info('custom_example_clinic_vs_staff_rx_created_worker run completed @ ' . date('Y-m-d H:i:s'));

    /** Rx Created by Clinic **/

    // Today
    $data = $this->dashboardManager->getClinicTotalRxCreatedByRxTypeByDate(date("Y-m-d"));
    $clinic_scit_created = !empty($data['scit_created']) ? $data['scit_created'] : 0;
    $clinic_slit_created = !empty($data['slit_created']) ? $data['slit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'clinic_scit_created' => $clinic_scit_created,
        'clinic_slit_created' => $clinic_slit_created,
        'clinic_rx_create_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'clinic_scit_created' => $clinic_scit_created,
        'clinic_slit_created' => $clinic_slit_created,
        'clinic_rx_create_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // This week
    $data = $this->dashboardManager->getClinicTotalRxCreatedByRxTypeByWeek(date("Y"), date("W"));
    $clinic_scit_created = !empty($data['scit_created']) ? $data['scit_created'] : 0;
    $clinic_slit_created = !empty($data['slit_created']) ? $data['slit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByWeek(
      [
        'dashboard_year', date('Y'),
        'dashboard_month', date('n'),
        'dashboard_week', date('W'),
        'clinic_scit_created' => $clinic_scit_created,
        'clinic_slit_created' => $clinic_slit_created,
        'clinic_rx_create_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'clinic_scit_created' => $clinic_scit_created,
        'clinic_slit_created' => $clinic_slit_created,
        'clinic_rx_create_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'dashboard_week' => date('W'),
      ]
    );

    // This month
    $data = $this->dashboardManager->getClinicTotalRxCreatedByRxTypeByMonth(date("Y"), date("n"));
    $clinic_scit_created = !empty($data['scit_created']) ? $data['scit_created'] : 0;
    $clinic_slit_created = !empty($data['slit_created']) ? $data['slit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year', date('Y'),
        'dashboard_month', date('n'),
        'clinic_scit_created' => $clinic_scit_created,
        'clinic_slit_created' => $clinic_slit_created,
        'clinic_rx_create_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'clinic_scit_created' => $clinic_scit_created,
        'clinic_slit_created' => $clinic_slit_created,
        'clinic_rx_create_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );


    /** Rx Created by Staff **/

    // Today
    $data = $this->dashboardManager->getStaffTotalRxCreatedByRxTypeByDate(date("Y-m-d"));
    $staff_scit_created = !empty($data['scit_created']) ? $data['scit_created'] : 0;
    $staff_slit_created = !empty($data['slit_created']) ? $data['slit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => date('Y-m-d'),
        'staff_scit_created' => $staff_scit_created,
        'staff_slit_created' => $staff_slit_created,
        'staff_rx_create_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'staff_scit_created' => $staff_scit_created,
        'staff_slit_created' => $staff_slit_created,
        'staff_rx_create_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'dashboard_date' => date('Y-m-d'),
      ]
    );

    // This week
    $data = $this->dashboardManager->getStaffTotalRxCreatedByRxTypeByWeek(date("Y"), date("W"));
    $staff_scit_created = !empty($data['scit_created']) ? $data['scit_created'] : 0;
    $staff_slit_created = !empty($data['slit_created']) ? $data['slit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByWeek(
      [
        'dashboard_year', date('Y'),
        'dashboard_month', date('n'),
        'dashboard_week', date('W'),
        'staff_scit_created' => $staff_scit_created,
        'staff_slit_created' => $staff_slit_created,
        'staff_rx_create_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'staff_scit_created' => $staff_scit_created,
        'staff_slit_created' => $staff_slit_created,
        'staff_rx_create_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
        'dashboard_week' => date('W'),
      ]
   );


    // This month
    $data = $this->dashboardManager->getStaffTotalRxCreatedByRxTypeByMonth(date("Y"), date("n"));
    $staff_scit_created = !empty($data['scit_created']) ? $data['scit_created'] : 0;
    $staff_slit_created = !empty($data['slit_created']) ? $data['slit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByMonth(
      [
        'dashboard_year', date('Y'),
        'dashboard_month', date('n'),
        'staff_scit_created' => $staff_scit_created,
        'staff_slit_created' => $staff_slit_created,
        'staff_rx_create_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'staff_scit_created' => $staff_scit_created,
        'staff_slit_created' => $staff_slit_created,
        'staff_rx_create_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'dashboard_year' => date('Y'),
        'dashboard_month' => date('n'),
      ]
    );
  }

}
