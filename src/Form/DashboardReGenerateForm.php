<?php

namespace Drupal\custom_example\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueFactoryInterface;
use Drupal\Core\Url;
use Drupal\mrs_base\Service\BaseHelper;
use Drupal\custom_example\Service\DashboardHelper;
use Drupal\custom_example\Service\DashboardManager;
use Drupal\node\NodeInterface;
use \Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DashboardReGenerateForm extends FormBase {

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
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
  * The list of available modules.
  *
  * @var \Drupal\Core\Extension\ModuleExtensionList
  */
  protected $extensionListModule;

  /**
   * The list of available themes.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $extensionListTheme;

  /**
   * @var QueueFactory
   */
  protected $queueFactory;

  /**
   * The base helper service.
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
   * The node id.
   *
   * @var int
   */
  private $nid;


  /**
   * Constructs class.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_account,
    Connection $database,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger,
    ModuleExtensionList $extension_list_module,
    ThemeExtensionList $extension_list_theme,
    QueueFactory $queue_factory,
    BaseHelper $base_helper,
    DashboardHelper $dashboard_helper,
    DashboardManager $dashboard_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $current_account;
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->logger = $logger->get('custom_example');
    $this->extensionListModule = $extension_list_module;
    $this->extensionListTheme = $extension_list_theme;
    $this->queueFactory = $queue_factory;
    $this->baseHelper = $base_helper;
    $this->dashboardHelper = $dashboard_helper;
    $this->dashboardManager = $dashboard_manager;
  }

  /**
   * Creates a new Controller.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   *   A new Controller object.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('extension.list.module'),
      $container->get('extension.list.theme'),
      $container->get('queue'),
      $container->get('base.helper'),
      $container->get('dashboard.helper'),
      $container->get('dashboard.manager')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'dashboard_regenerate_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array|AjaxResponse
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $message  = '<h4>Do you want to re-generate all data for dashboard?</h4>';
    $message .= '<p>This will overwrite all current, yesterday, last 7 weeks, last 12 months data for dashboard. This action can not be undone.</p>';

    $form['results'] = [
      '#markup' => $message,
      '#prefix' => '<div id="custom_example_list_wrapper">',
      '#suffix' => '</div>',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Re-generate'),
      '#attributes' => [
        'class' => [
          'btn-submit',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'saveCallback'],
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    $form['actions']['close'] = [
      '#type' => 'submit',
      '#value' => $this->t('Close'),
      '#attributes' => [
        'class' => [
          'btn-submit',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'cancelCallback'],
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    $form['#attached']['library'][] = 'custom_example/dashboard-style';
    $form['#prefix'] = '<div id="mrs-dashboard-update-all-form-wrapper">';
    $form['#suffix'] = '</div>';

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  function saveCallback(array &$form, FormStateInterface $form_state) {
    /** Generate data for today, this week and this month **/

    // dummy item
    $item = [
      'created' => time(),
      'sequence' => 1
    ];

    // Billing: Total amount collected today/week/month
    $queue = $this->queueFactory->get('custom_example_total_amount_collected_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Billing: Total SLIT/SCIT created today/week/month
    $queue = $this->queueFactory->get('custom_example_total_rx_created_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Billing: Total SCIT/SLIT Refills today/week/month
    $queue = $this->queueFactory->get('custom_example_total_rx_refills_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Billing: Denied payments, Successful payments, Refund payments, Void payments, Error payments, Invoice payments
    $queue = $this->queueFactory->get('custom_example_total_rx_payments_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Rx: RX�s with pending payment that is more than 3 days past the schedule date
    $queue = $this->queueFactory->get('custom_example_total_rx_pending_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Rx: RX�s scheduled for shipment but has payment issues
    $queue = $this->queueFactory->get('custom_example_total_rx_scheduled_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Rx: Upcoming RX for shipment refills
    // Show RXs that are 10 days from scheduled date in shipping record
    $queue = $this->queueFactory->get('custom_example_total_rx_shipment_refills_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Rx: Expiring RX
    $queue = $this->queueFactory->get('custom_example_total_rx_expiring_queue');
    $queue->createQueue();
    $queue->createItem($item);

    //Rx: Expiring ARB
    $queue = $this->queueFactory->get('custom_example_total_expiring_arb_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Rx: Expiring Credit Cards
    $queue = $this->queueFactory->get('custom_example_total_expiring_cc_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Payment Profile: Expiring Credit Cards
    $queue = $this->queueFactory->get('custom_example_total_profile_expiring_cc_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Billing: Total order amount collected today/week/month
    $queue = $this->queueFactory->get('custom_example_order_amount_collected_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Order: Total order submitted
    $queue = $this->queueFactory->get('custom_example_total_order_submitted_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Billing: Total po amount collected today/week/month
    $queue = $this->queueFactory->get('custom_example_po_amount_collected_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Billing: Total po created
    $queue = $this->queueFactory->get('custom_example_total_po_created_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Clinics: Total count
    $queue = $this->queueFactory->get('custom_example_total_clinics_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Silent Post: Summary
    $queue = $this->queueFactory->get('custom_example_silent_post_summary_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Total sales amount
    $queue = $this->queueFactory->get('custom_example_total_sales_amount_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Rx created by clinic and staff
    $queue = $this->queueFactory->get('custom_example_clinic_vs_staff_rx_created_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Refills created by clinic and staff
    $queue = $this->queueFactory->get('custom_example_clinic_vs_staff_rx_refills_queue');
    $queue->createQueue();
    $queue->createItem($item);

    // Reference time for all
    $time = date('Y-m-d H:i:s');

    // rx amount: past 7 weeks
    for ($i = 1; $i <= 7; $i++) {
      $past_week = date('W', strtotime("-" . $i . " week"));
      $past_week_month = date('n', strtotime("-" . $i . " week"));
      $past_week_year = date('Y', strtotime("-" . $i . " week"));
      $rx_total_week = $this->dashboardManager->getTotalRxAmountByWeek($past_week_year, $past_week);

      // Update the table
      $this->dashboardManager->setBillingInfoByWeek(
        [
          'dashboard_year' => $past_week_year,
          'dashboard_month' => $past_week_month,
          'dashboard_week' => $past_week,
          'total_collected_amount' => $rx_total_week,
          'collected_amount_last_update' => $time,
        ],
        [
          'total_collected_amount' => $rx_total_week,
          'collected_amount_last_update' => $time,
        ],
        [
          'dashboard_year' => $past_week_year,
          'dashboard_month' => $past_week_month,
          'dashboard_week' => $past_week,
        ]
      );
    }
    $this->logger->info( 'Re-generate rx amount for last 7 weeks, completed.');

    // Rx amount: past 12 months
    for ($i = 1; $i <= 12; $i++) {
      $past_month = date('n', strtotime("-" . $i ." month"));
      $past_month_year = date('Y', strtotime("-" . $i ." month"));
      $rx_total_month = $this->dashboardManager->getTotalRxAmountByMonth($past_month_year, $past_month);

      // Update the table
      $this->dashboardManager->setBillingInfoByMonth(
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
          'total_collected_amount' => $rx_total_month,
          'collected_amount_last_update' => $time,
        ],
        [
          'total_collected_amount' => $rx_total_month,
          'collected_amount_last_update' => $time,
        ],
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
        ]
      );
    }
    $this->logger->info('Re-generate rx amount for last 12 months, completed.');


    // Order amount: past 7 weeks
    for ($i = 1; $i <= 7; $i++) {
      $past_week = date('W', strtotime("-".$i." week"));
      $past_week_month = date('n', strtotime("-".$i." week"));
      $past_week_year = date('Y', strtotime("-".$i." week"));
      $order_total_week = $this->dashboardManager->getOrderTotalAmountByWeek($past_week_year, $past_week);

      // Update the table
      $this->dashboardManager->setBillingInfoByWeek(
        [
          'dashboard_year' => $past_week_year,
          'dashboard_month' => $past_week_month,
          'dashboard_week' => $past_week,
          'total_order_amount' => $order_total_week / 100,
          'order_amount_last_update' => $time,
        ],
        [
          'total_order_amount' => $order_total_week / 100,
          'order_amount_last_update' => $time,
        ],
        [
          'dashboard_year' => $past_week_year,
          'dashboard_month' => $past_week_month,
          'dashboard_week' => $past_week,
        ]
      );
    }
    $this->logger->info('Re-generate order amount for last 7 weeks, completed.');


    // Order amount: past 12 months
    for ($i = 1; $i <= 12; $i++) {
      $past_month = date('n', strtotime("-" . $i ." month"));
      $past_month_year = date('Y', strtotime("-" . $i . " month"));
      $order_total_month = $this->dashboardManager->getOrderTotalAmountByMonth($past_month_year, $past_month);

      // Update the table
      $this->dashboardManager->setBillingInfoByMonth(
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
          'total_order_amount' => $order_total_month / 100,
          'order_amount_last_update' => $time,
        ],
        [
          'total_order_amount' => $order_total_month / 100,
          'order_amount_last_update' => $time,
        ],
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
        ]
      );
    }
    $this->logger->info('Re-generate order amount for last 12 months, completed.');


    // Order amount: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_yesterday = $this->dashboardManager->getOrderTotalAmountByDate($yesterday);

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => $yesterday,
        'total_order_amount' => $total_yesterday / 100,
        'order_amount_last_update' => $time,
      ],
      [
        'total_order_amount' => $total_yesterday / 100,
        'order_amount_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );
    $this->logger->info('Re-generate order amount for yesterday, completed.');


    // Rx amount: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_yesterday = $this->dashboardManager->getTotalRxAmountByDate($yesterday);

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => $yesterday,
        'total_collected_amount' => $total_yesterday,
        'collected_amount_last_update' => $time,
      ],
      [
        'total_collected_amount' => $total_yesterday,
        'collected_amount_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );
    $this->logger->info( 'Re-generate rx collected amount for yesterday, completed.');


    // Total clinics
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $total_clinics_yesterday = $this->dashboardManager->getTotalClinicsByDate();

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => $yesterday,
        'total_clinics' => $total_clinics_yesterday,
        'total_clinics_last_update' => $time,
      ],
      [
        'total_clinics' => $total_clinics_yesterday,
        'total_clinics_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );
    $this->logger->info( 'Re-generate total clinics, completed.');


    // Rx created: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $data = $this->dashboardManager->getTotalRxCreatedByDate($yesterday);
    $total_scit_created_yesterday = !empty($data['total_scit_created']) ? $data['total_scit_created'] : 0;
    $total_slit_created_yesterday = !empty($data['total_slit_created']) ? $data['total_slit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => $yesterday,
        'total_scit_created' => $total_scit_created_yesterday,
        'total_slit_created' => $total_slit_created_yesterday,
        'rx_create_last_update' => $time,
      ],
      [
        'total_scit_created' => $total_scit_created_yesterday,
        'total_slit_created' => $total_slit_created_yesterday,
        'rx_create_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );
    $this->logger->info( 'Re-generate rx created for yesterday, completed.');


    // Expiring arb subscription: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $date_reference = date('Y-m-d', strtotime('+29 days'));
    $data = $this->dashboardManager->getTotalExpiringArbByDate($yesterday, $date_reference);
    $total_subscriptions_yesterday = !empty($data['total_subscriptions']) ? $data['total_subscriptions'] : 0;
    $total_subscriptions_ids = !empty($data['total_subscriptions_ids']) ? $data['total_subscriptions_ids'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => $yesterday,
        'arb_expiring' => $total_subscriptions_yesterday,
        'arb_expiring_ids' => $total_subscriptions_ids,
        'arb_expiring_last_update' => $time,
      ],
      [
        'arb_expiring' => $total_subscriptions_yesterday,
        'arb_expiring_ids' => $total_subscriptions_ids,
        'arb_expiring_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );
    $this->logger->info( 'Re-generate expiring arb for yesterday, completed.');


    // Expiring credit cards: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $date_reference = date('Y-m-d', strtotime('+29 days'));
    $data = $this->dashboardManager->getTotalExpiringCc($yesterday, $date_reference);
    $total_subscriptions_yesterday = !empty($data['total_subscriptions']) ? $data['total_subscriptions'] : 0;
    $total_subscriptions_ids = !empty($data['total_subscriptions_ids']) ? $data['total_subscriptions_ids'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => $yesterday,
        'cc_expiring' => $total_subscriptions_yesterday,
        'cc_expiring_ids' => $total_subscriptions_ids,
        'cc_expiring_last_update' => $time,
      ],
      [
        'cc_expiring' => $total_subscriptions_yesterday,
        'cc_expiring_ids' => $total_subscriptions_ids,
        'cc_expiring_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );
    $this->logger->info( 'Re-generate expiring credit cards for yesterday, completed.');


    // Expiring profile credit cards: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $date_reference = date('Y-m-d', strtotime('+29 days'));
    $data = $this->dashboardManager->getTotalExpiringProfileCcByDate($yesterday, $date_reference);
    $total_profiles_yesterday = !empty($data['total_profiles']) ? $data['total_profiles'] : 0;
    $total_profiles_ids = !empty($data['total_profiles_ids']) ? $data['total_profiles_ids'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => $yesterday,
        'profile_cc_expiring' => $total_profiles_yesterday,
        'profile_cc_expiring_ids' => $total_profiles_ids,
        'profile_cc_last_update' => $time,
      ],
      [
        'profile_cc_expiring' => $total_profiles_yesterday,
        'profile_cc_expiring_ids' => $total_profiles_ids,
        'profile_cc_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );
    $this->logger->info( 'Re-generate expiring profile credit cards for yesterday, completed.');


    // Expiring rx: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $date_reference = date('Y-m-d', strtotime('+9 days'));
    $data = $this->dashboardManager->getTotalExpiringRxByDate($yesterday, $date_reference);
    $total_rx_yesterday = !empty($data['total_rx']) ? $data['total_rx'] : 0;
    $total_rx_nids = !empty($data['total_rx_nids']) ? $data['total_rx_nids'] : '';


    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => $yesterday,
        'rx_expiring' => $total_rx_yesterday,
        'rx_expiring_nids' => $total_rx_nids,
        'rx_expiring_last_update' => $time,
      ],
      [
        'rx_expiring' => $total_rx_yesterday,
        'rx_expiring_nids' => $total_rx_nids,
        'rx_expiring_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );

    $this->logger->info( 'Re-generate expiring rx for yesterday, completed.');

    // Payments: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $data = $this->dashboardManager->getRxPaymentsByDate($yesterday);
    $total_denied_payment_yesterday = !empty($data['total_denied_payment']) ? $data['total_denied_payment'] : 0;
    $total_successful_payment_yesterday = !empty($data['total_successful_payment']) ? $data['total_successful_payment'] : 0;
    $total_refund_payment_yesterday = !empty($data['total_refund_payment']) ? $data['total_refund_payment'] : 0;
    $total_void_payment_yesterday = !empty($data['total_void_payment']) ? $data['total_void_payment'] : 0;
    $total_error_payment_yesterday = !empty($data['total_error_payment']) ? $data['total_error_payment'] : 0;

    // Invoice payment
    $total_invoice_payment_yesterday = $this->dashboardManager->getRxInvoicePaymentByDate($yesterday);;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => $yesterday,
        'total_denied_payment' => $total_denied_payment_yesterday,
        'total_successful_payment' => $total_successful_payment_yesterday,
        'total_refund_payment' => $total_refund_payment_yesterday,
        'total_void_payment' => $total_void_payment_yesterday,
        'total_error_payment' => $total_error_payment_yesterday,
        'total_invoice_payment' => $total_invoice_payment_yesterday,
        'rx_payment_last_update' => $time,
      ],
      [
        'total_denied_payment' => $total_denied_payment_yesterday,
        'total_successful_payment' => $total_successful_payment_yesterday,
        'total_refund_payment' => $total_refund_payment_yesterday,
        'total_void_payment' => $total_void_payment_yesterday,
        'total_error_payment' => $total_error_payment_yesterday,
        'total_invoice_payment' => $total_invoice_payment_yesterday,
        'rx_payment_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );
    $this->logger->info( 'Re-generate payment summary for yesterday, completed.');


    // Pending rx: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $date_reference = date('Y-m-d', strtotime('-4 days'));

    $data = $this->dashboardManager->getTotalRxPendingByDate($yesterday, $date_reference);;
    $total_rx_yesterday = !empty($data['total_rx']) ? $data['total_rx'] : 0;
    $total_rx_nids = !empty($data['total_rx_nids']) ? $data['total_rx_nids'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => $yesterday,
        'rx_pending' => $total_rx_yesterday,
        'rx_pending_nids' => $total_rx_nids,
        'rx_pending_last_update' => $time,
      ],
      [
        'rx_pending' => $total_rx_yesterday,
        'rx_pending_nids' => $total_rx_nids,
        'rx_pending_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );

    $this->logger->info('Re-generate pending rx for yesterday, completed.');

    // Refills: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $data = $this->dashboardManager->getTotalRxRefillsByDate($yesterday);
    $total_scit_refill_yesterday = !empty($data['total_scit_refill']) ? $data['total_scit_refill'] : 0;
    $total_slit_refill_yesterday = !empty($data['total_slit_refill']) ? $data['total_slit_refill'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => $yesterday,
        'total_scit_refill' => $total_scit_refill_yesterday,
        'total_slit_refill' => $total_slit_refill_yesterday,
        'rx_refill_last_update' => $time,
      ],
      [
        'total_scit_refill' => $total_scit_refill_yesterday,
        'total_slit_refill' => $total_slit_refill_yesterday,
        'rx_refill_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );
    $this->logger->info( 'Re-generate rx refills for yesterday, completed.');


    // Rx created: last 12 months
    for ($i = 1; $i <= 12; $i++) {
      $past_month = date('n', strtotime("-" . $i . " month"));
      $past_month_year = date('Y', strtotime("-" . $i . " month"));
      $data = $this->dashboardManager->getTotalRxCreatedByMonth($past_month_year, $past_month);
      $total_scit_created = !empty($data['total_scit_created']) ? $data['total_scit_created'] : 0;
      $total_slit_created = !empty($data['total_slit_created']) ? $data['total_slit_created'] : 0;

      // Update the table
      $this->dashboardManager->setBillingInfoByMonth(
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
          'total_scit_created' => $total_scit_created,
          'total_slit_created' => $total_slit_created,
          'rx_create_last_update' => $time,
        ],
        [
          'dashboard_month' => $past_month,
          'total_scit_created' => $total_scit_created,
          'total_slit_created' => $total_slit_created,
          'rx_create_last_update' => $time,
        ],
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
        ]
      );
    }
    $this->logger->info('Re-generate rx created for last 12 months, completed.');


    // Rx refills: last 12 months
    for ($i = 1; $i <= 12; $i++) {
      $past_month = date('n', strtotime("-" . $i . " month"));
      $past_month_year = date('Y', strtotime("-" . $i . " month"));
      $data = $this->dashboardManager->getTotalRxRefillsByMonth($past_month_year, $past_month);
      $total_scit_refill = !empty($data['total_scit_refill']) ? $data['total_scit_refill'] : 0;
      $total_slit_refill = !empty($data['total_slit_refill']) ? $data['total_slit_refill'] : 0;

      // Update the table
      $this->dashboardManager->setBillingInfoByMonth(
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
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
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
        ]
      );
    }
    $this->logger->info( 'Re-generate upcoming rx refills for last 12 months, completed.');


    // Payment summary: last 12 months
    for ($i = 1; $i <= 12; $i++) {
      $past_month = date('n', strtotime("-".$i." month"));
      $past_month_year = date('Y', strtotime("-".$i." month"));

      $data = $this->dashboardManager->getRxPaymentsByMonth($past_month_year, $past_month);
      $total_denied_payment = !empty($data['total_denied_payment']) ? $data['total_denied_payment'] : 0;
      $total_successful_payment = !empty($data['total_successful_payment']) ? $data['total_successful_payment'] : 0;
      $total_refund_payment = !empty($data['total_refund_payment']) ? $data['total_refund_payment'] : 0;
      $total_void_payment = !empty($data['total_void_payment']) ? $data['total_void_payment'] : 0;
      $total_error_payment = !empty($data['total_error_payment']) ? $data['total_error_payment'] : 0;

      // Invoice payment
      $total_invoice_payment = $this->dashboardManager->getRxInvoicePaymentByMonth($past_month_year, $past_month);;

      // Update the table
      $this->dashboardManager->setBillingInfoByMonth(
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
          'total_denied_payment' => $total_denied_payment,
          'total_successful_payment' => $total_successful_payment,
          'total_refund_payment' => $total_refund_payment,
          'total_void_payment' => $total_void_payment,
          'total_error_payment' => $total_error_payment,
          'total_invoice_payment' => $total_invoice_payment,
          'rx_payment_last_update' => $time,
        ],
        [
          'total_denied_payment' => $total_denied_payment,
          'total_successful_payment' => $total_successful_payment,
          'total_refund_payment' => $total_refund_payment,
          'total_void_payment' => $total_void_payment,
          'total_error_payment' => $total_error_payment,
          'total_invoice_payment' => $total_invoice_payment,
          'rx_payment_last_update' => $time,
        ],
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
        ]
      );
    }
    $this->logger->info( 'Re-generate payment summary for last 12 months, completed.');


    // Rx scheduled: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $date_reference = date('Y-m-d', strtotime('+2 days'));
    $data = $this->dashboardManager->getTotalRxScheduledByDate($yesterday, $date_reference);
    $total_rx_yesterday = !empty($data['total_rx']) ? $data['total_rx'] : 0;
    $total_rx_nids = !empty($data['total_rx_nids']) ? $data['total_rx_nids'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => $yesterday,
        'rx_scheduled' => $total_rx_yesterday,
        'rx_scheduled_nids' => $total_rx_nids,
        'rx_scheduled_last_update' => $time,
      ],
      [
        'rx_scheduled' => $total_rx_yesterday,
        'rx_scheduled_nids' => $total_rx_nids,
        'rx_scheduled_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday
      ]
    );
    $this->logger->info( 'Re-generate rx scheduled for delivery for yesterday, completed.');

    // Upcoming rx refills
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $date_reference = date('Y-m-d', strtotime('+9 days'));
    $data = $this->dashboardManager->getTotalUpcomingRxRefillsByDate($yesterday, $date_reference);
    $total_rx_yesterday = !empty($data['total_rx']) ? $data['total_rx'] : 0;
    $total_rx_nids = !empty($data['total_rx_nids']) ? $data['total_rx_nids'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => $yesterday,
        'rx_refills' => $total_rx_yesterday,
        'rx_refills_nids' => $total_rx_nids,
        'rx_refills_last_update' => $time,
      ],
      [
        'rx_refills' => $total_rx_yesterday,
        'rx_refills_nids' => $total_rx_nids,
        'rx_refills_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday
      ]
    );

    // Silentpost summary: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $data = $this->dashboardManager->getSilentPostSummaryByYear(date('Y'));
    $total_resolved_yesterday = !empty($data['total_resolved']) ? $data['total_resolved'] : 0;
    $total_pending_yesterday = !empty($data['total_pending']) ? $data['total_pending'] : 0;

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => $yesterday,
        'silent_post_resolved' => $total_resolved_yesterday,
        'silent_post_pending' => $total_pending_yesterday,
        'silent_post_last_update' => $time,
      ],
      [
        'silent_post_resolved' => $total_resolved_yesterday,
        'silent_post_pending' => $total_pending_yesterday,
        'silent_post_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday
      ]
    );
    $this->logger->info( 'Re-generate silent post summary for yesterday, completed.');


    // Total submitted orders: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $data = $this->dashboardManager->getOrdersByDate($yesterday);
    $total_orders_yesterday = !empty($data['total_orders']) ? $data['total_orders'] : 0;
    $total_orders_ids = !empty($data['total_orders_ids']) ? $data['total_orders_ids'] : '';

    // Update the table
    $this->dashboardManager->setBillingCountByDate(
      [
        'dashboard_date' => $yesterday,
        'order_submitted' => $total_orders_yesterday,
        'order_ids' => $total_orders_ids,
        'order_last_update' => $time,
      ],
      [
        'order_submitted' => $total_orders_yesterday,
        'order_ids' => $total_orders_ids,
        'order_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday
      ]
    );
    $this->logger->info( 'Re-generate total order submitted for yesterday, completed.');

    // Total po amount / po refund amount: yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $data = $this->dashboardManager->getPoAmountByTypeByDate($yesterday);
    $total_po_amount = !empty($data['total_po_amount']) ? $data['total_po_amount'] : 0;
    $total_po_refund_amount = !empty($data['total_po_refund_amount']) ? $data['total_po_refund_amount'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => $yesterday,
        'total_po_amount' => abs($total_po_amount),
        'total_po_refund_amount' => abs($total_po_refund_amount),
        'po_amount_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'total_po_amount' => abs($total_po_amount),
        'total_po_refund_amount' => abs($total_po_refund_amount),
        'po_amount_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'dashboard_date' => $yesterday
      ]
    );

    // Total po amount / po refund amount: past 7 weeks
    for ($i = 1; $i <= 7; $i++) {
      $past_week = date('W', strtotime("-" . $i . " week"));
      $past_week_month = date('n', strtotime("-" . $i . " week"));
      $past_week_year = date('Y', strtotime("-" . $i . " week"));

      $data = $this->dashboardManager->getPoAmountByTypeByWeek($past_week_year, $past_week);
      $total_po_amount = !empty($data['total_po_amount']) ? $data['total_po_amount'] : 0;
      $total_po_refund_amount = !empty($data['total_po_refund_amount']) ? $data['total_po_refund_amount'] : 0;

      // Update the table
      $this->dashboardManager->setBillingInfoByWeek(
        [
          'dashboard_year' => $past_week_year,
          'dashboard_month' => $past_week_month,
          'dashboard_week' => $past_week,
          'total_po_amount' => abs($total_po_amount),
          'total_po_refund_amount' => abs($total_po_refund_amount),
          'po_amount_last_update' => date('Y-m-d H:i:s'),
        ],
        [
          'total_po_amount' => abs($total_po_amount),
          'total_po_refund_amount' => abs($total_po_refund_amount),
          'po_amount_last_update' => date('Y-m-d H:i:s'),
        ],
        [
          'dashboard_year' => $past_week_year,
          'dashboard_month' => $past_week_month,
          'dashboard_week' => $past_week,
        ]
      );
    }


    // po amount / po refund amount: last 12 months
    for ($i = 1; $i <= 12; $i++) {
      $past_month = date('n', strtotime("-" . $i . " month"));
      $past_month_year = date('Y', strtotime("-" . $i . " month"));

      $data = $this->dashboardManager->getPoAmountByTypeByMonth($past_month_year, $past_month);
      $total_po_amount = !empty($data['total_po_amount']) ? $data['total_po_amount'] : 0;
      $total_po_refund_amount = !empty($data['total_po_refund_amount']) ? $data['total_po_refund_amount'] : 0;

      // Update the table
      $this->dashboardManager->setBillingInfoByMonth(
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
          'total_po_amount' => abs($total_po_amount),
          'total_po_refund_amount' => abs($total_po_refund_amount),
          'po_amount_last_update' => date('Y-m-d H:i:s'),
        ],
        [
          'total_po_amount' => abs($total_po_amount),
          'total_po_refund_amount' => abs($total_po_refund_amount),
          'po_amount_last_update' => date('Y-m-d H:i:s'),
        ],
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
        ]
      );
    }
    $this->logger->info('Re-generate total po amount and po refund amount for yesterday, last 7 weeks and last 12 months, completed.');


    // Total po / po refund : yesterday
    $yesterday = date('Y-m-d', strtotime('-1 days'));

    $data = $this->dashboardManager->getPoCreatedByDate($yesterday);
    $total_po = !empty($data['total_po']) ? $data['total_po'] : 0;
    $total_po_refund = !empty($data['total_po_refund']) ? $data['total_po_refund'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => $yesterday,
        'total_po_created' => abs($total_po),
        'total_po_refund_created' => abs($total_po_refund),
        'po_create_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'total_po_created' => abs($total_po),
        'total_po_refund_created' => abs($total_po_refund),
        'po_create_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );


    // Total po / po refund : past 7 weeks
    for ($i = 1; $i <= 7; $i++) {
      $past_week = date('W', strtotime("-".$i." week"));
      $past_week_month = date('n', strtotime("-".$i." week"));
      $past_week_year = date('Y', strtotime("-".$i." week"));

      $data = $this->dashboardManager->getPoCreatedByWeek($past_week_year, $past_week_month);
      $total_po = !empty($data['total_po']) ? $data['total_po'] : 0;
      $total_po_refund = !empty($data['total_po_refund']) ? $data['total_po_refund'] : 0;

      // Update the table
      $this->dashboardManager->setBillingInfoByWeek(
        [
          'dashboard_year' => $past_week_year,
          'dashboard_month' => $past_week_month,
          'dashboard_week' => $past_week,
          'total_po_created' => abs($total_po),
          'total_po_refund_created' => abs($total_po_refund),
          'po_create_last_update' => date('Y-m-d H:i:s'),
        ],
        [
          'total_po_created' => abs($total_po),
          'total_po_refund_created' => abs($total_po_refund),
          'po_create_last_update' => date('Y-m-d H:i:s'),
        ],
        [
          'dashboard_year' => $past_week_year,
          'dashboard_month' => $past_week_month,
          'dashboard_week' => $past_week,
        ]
      );
    }

    // Total po / Total po refund : last 12 months
    for ($i = 1; $i <= 12; $i++) {
      $past_month = date('n', strtotime("-" . $i . " month"));
      $past_month_year = date('Y', strtotime("-" . $i . " month"));

      $data = $this->dashboardManager->getPoCreatedByMonth($past_month_year, $past_month);
      $total_po = !empty($data['total_po']) ? $data['total_po'] : 0;
      $total_po_refund = !empty($data['total_po_refund']) ? $data['total_po_refund'] : 0;

      // Update the table
      $this->dashboardManager->setBillingInfoByMonth(
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
          'total_po_created' => abs($total_po),
          'total_po_refund_created' => abs($total_po_refund),
          'po_create_last_update' => date('Y-m-d H:i:s'),
        ],
        [
          'total_po_created' => abs($total_po),
          'total_po_refund_created' => abs($total_po_refund),
          'po_create_last_update' => date('Y-m-d H:i:s'),
        ],
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
        ]
      );
    }
    $this->logger->info( 'Re-generate total po created and po refund created for yesterday, last 7 weeks and last 12 months, completed.');


    /** total sales amount **/
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 days'));

    // Today : rx amount
    $today_total_rx_amount = $this->dashboardManager->getTotalSalesAmountByDate($today);

    // Today : po amount
    $today_total_po_amount = $this->dashboardManager->getTotalPoAmountByDate($today);

    // Today : store amount
    $today_total_store_amount = $this->dashboardManager->getOrderTotalAmountByDate($today);
    $today_total_store_amount = $today_total_store_amount / 100;

    // Total sales amount
    $today_total_sales_amount = $today_total_rx_amount + $today_total_po_amount + $today_total_store_amount;

    // Update the table : today
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => $today,
        'total_sales_amount' => $today_total_sales_amount,
        'total_sales_amount_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'total_sales_amount' => $today_total_sales_amount,
        'total_sales_amount_last_update' => date('Y-m-d H:i:s'),
      ],
      [
        'dashboard_date' => $today,
      ]
    );


    // Sales amount: past 31 days
    for ($i = 1; $i <= 31; $i++) {
      $pastday = date('Y-m-d', strtotime('-' . $i . ' days'));

      // Past day : rx amount
      $pastday_total_rx_amount = $this->dashboardManager->getTotalSalesAmountByDate($pastday);

      // Past day : po amount
      $pastday_total_po_amount = $this->dashboardManager->getTotalPoAmountByDate($pastday);

      // Past day : store amount
      $pastday_total_store_amount = $this->dashboardManager->getOrderTotalAmountByDate($pastday);
      $pastday_total_store_amount = $pastday_total_store_amount / 100;

      // Total sales amount
      $pastday_total_sales_amount = $pastday_total_rx_amount + $pastday_total_po_amount + $pastday_total_store_amount;

      // Update the table : yesterday
      $this->dashboardManager->setBillingInfoByDate(
        [
          'dashboard_date' => $pastday,
          'total_sales_amount' => $pastday_total_sales_amount,
          'total_sales_amount_last_update' => date('Y-m-d H:i:s'),
        ],
        [
          'total_sales_amount' => $pastday_total_sales_amount,
          'total_sales_amount_last_update' => date('Y-m-d H:i:s'),
        ],
        [
          'dashboard_date' => $pastday
        ]
      );
    }

    // Sales amount: past 7 weeks
    for ($i = 1; $i <= 7; $i++) {
      $past_week = date('W', strtotime("-" . $i . " week"));
      $past_week_month = date('n', strtotime("-" . $i . " week"));
      $past_week_year = date('Y', strtotime("-" . $i . " week"));

      $weekly_total_rx_amount = $this->dashboardManager->getTotalSalesAmountByWeek($past_week_year, $past_week);

      // This week : po amount
      $weekly_total_po_amount = $this->dashboardManager->getTotalPoAmountByWeek($past_week_year, $past_week);

      // This week : store amount
      $weekly_total_store_amount = $this->dashboardManager->getOrderTotalAmountByWeek($past_week_year, $past_week);
      $weekly_total_store_amount = $weekly_total_store_amount / 100;

      // Total sales amount
      $weekly_total_sales_amount = $weekly_total_rx_amount + $weekly_total_po_amount + $weekly_total_store_amount;

      // Update the table
      $this->dashboardManager->setBillingInfoByWeek(
        [
          'dashboard_year' => $past_week_year,
          'dashboard_month' => $past_week_month,
          'dashboard_week' => $past_week,
          'total_sales_amount' => $weekly_total_sales_amount,
          'total_sales_amount_last_update' => date('Y-m-d H:i:s'),
        ],
        [
          'total_sales_amount' => $weekly_total_sales_amount,
          'total_sales_amount_last_update' => date('Y-m-d H:i:s'),
        ],
        [
          'dashboard_year' => $past_week_year,
          'dashboard_month' => $past_week_month,
          'dashboard_week' => $past_week,
        ],
      );
    }

    // Sales amount: last 12 months
    for ($i = 1; $i <= 12; $i++) {
      $past_month = date('n', strtotime("-" . $i . " month"));
      $past_month_year = date('Y', strtotime("-" . $i . " month"));

      // This month : rx amount
      $monthly_total_rx_amount = $this->dashboardManager->getTotalSalesAmountByMonth($past_month_year, $past_month);

      // This month : po amount
      $monthly_total_po_amount = $this->dashboardManager->getTotalPoAmountByMonth($past_month_year, $past_month);

      // This month : store amount
      $monthly_total_order_amount = $this->dashboardManager->getOrderTotalAmountByMonth($past_month_year, $past_month);
      $monthly_total_order_amount = $monthly_total_order_amount / 100;

      // Total sales amount
      $monthly_total_sales_amount = $monthly_total_rx_amount + $monthly_total_po_amount + $monthly_total_order_amount;

      // Update the table
      $this->dashboardManager->setBillingInfoByMonth(
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
          'total_sales_amount' => $monthly_total_sales_amount,
          'total_sales_amount_last_update' => date('Y-m-d H:i:s'),
        ],
        [
          'total_sales_amount' => $monthly_total_sales_amount,
          'total_sales_amount_last_update' => date('Y-m-d H:i:s'),
        ],
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
        ]
      );
    }
    $this->logger->info('Re-generate total sales amount for yesterday, last 7 weeks and last 12 months, completed.');

    // Rx created: yesterday by clinic
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $data = $this->dashboardManager->getClinicTotalRxCreatedByRxTypeByDate($yesterday);
    $clinic_scit_created_yesterday = !empty($data['scit_created']) ? $data['scit_created'] : 0;
    $clinic_slit_created_yesterday = !empty($data['slit_created']) ? $data['slit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => $yesterday,
        'clinic_scit_created' => $clinic_scit_created_yesterday,
        'clinic_slit_created' => $clinic_slit_created_yesterday,
        'clinic_rx_create_last_update' => $time,
      ],
      [
        'clinic_scit_created' => $clinic_scit_created_yesterday,
        'clinic_slit_created' => $clinic_slit_created_yesterday,
        'clinic_rx_create_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );
    $this->logger->info('Re-generate rx created by clinic for yesterday, completed.');

    // Rx created by clinic : last 12 months
    for ($i = 1; $i <= 12; $i++) {
      $past_month = date('n', strtotime("-" . $i . " month"));
      $past_month_year = date('Y', strtotime("-" . $i . " month"));
      $data = $this->dashboardManager->getClinicTotalRxCreatedByRxTypeByMonth($past_month_year, $past_month);
      $clinic_scit_created = !empty($data['scit_created']) ? $data['scit_created'] : 0;
      $clinic_slit_created = !empty($data['slit_created']) ? $data['slit_created'] : 0;

      // Update the table
      $this->dashboardManager->setBillingInfoByMonth(
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
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
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
        ]
      );
    }
    $this->logger->info('Re-generate rx created by clinic for last 12 months, completed.');


    // Rx created: yesterday by staff
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $data = $this->dashboardManager->getStaffTotalRxCreatedByRxTypeByDate($yesterday);
    $staff_scit_created_yesterday = !empty($data['scit_created']) ? $data['scit_created'] : 0;
    $staff_slit_created_yesterday = !empty($data['slit_created']) ? $data['slit_created'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => $yesterday,
        'staff_scit_created' => $staff_scit_created_yesterday,
        'staff_slit_created' => $staff_slit_created_yesterday,
        'staff_rx_create_last_update' => $time,
      ],
      [
        'staff_scit_created' => $staff_scit_created_yesterday,
        'staff_slit_created' => $staff_slit_created_yesterday,
        'staff_rx_create_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );

    $this->logger->info( 'Re-generate rx created by staff for yesterday, completed.');

    // Rx created by staff : last 12 months
    for ($i = 1; $i <= 12; $i++) {
      $past_month = date('n', strtotime("-" . $i . " month"));
      $past_month_year = date('Y', strtotime("-" . $i . " month"));
      $data = $this->dashboardManager->getStaffTotalRxCreatedByRxTypeByMonth($past_month_year, $past_month);
      $staff_scit_created = !empty($data['scit_created']) ? $data['scit_created'] : 0;
      $staff_slit_created = !empty($data['slit_created']) ? $data['slit_created'] : 0;

      // Update the table
      $this->dashboardManager->setBillingInfoByMonth(
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
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
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
        ]
      );
    }
    $this->logger->info('Re-generate rx created by staff for last 12 months, completed.');

    // Refills: yesterday by clinic
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $data = $this->dashboardManager->getClinicTotalRxRefillByRxTypeByDate($yesterday);
    $clinic_scit_refill_yesterday = !empty($data['scit_refill']) ? $data['scit_refill'] : 0;
    $clinic_slit_refill_yesterday = !empty($data['slit_refill']) ? $data['slit_refill'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => $yesterday,
        'clinic_scit_refill' => $clinic_scit_refill_yesterday,
        'clinic_slit_refill' => $clinic_slit_refill_yesterday,
        'clinic_rx_refill_last_update' => $time,
      ],
      [
        'clinic_scit_refill' => $clinic_scit_refill_yesterday,
        'clinic_slit_refill' => $clinic_slit_refill_yesterday,
        'clinic_rx_refill_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );

    $this->logger->info( 'Re-generate rx refills by clinic for yesterday, completed.');

    // Rx refills by clinic: last 12 months
    for ($i = 1; $i <= 12; $i++) {
      $past_month = date('n', strtotime("-" . $i . " month"));
      $past_month_year = date('Y', strtotime("-" . $i . " month"));
      $data = $this->dashboardManager->getClinicTotalRxRefillByRxTypeByMonth($past_month_year, $past_month);
      $clinic_scit_refill = !empty($data['scit_refill']) ? $data['scit_refill'] : 0;
      $clinic_slit_refill = !empty($data['slit_refill']) ? $data['slit_refill'] : 0;

      // Update the table
      $this->dashboardManager->setBillingInfoByMonth(
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
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
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
        ]
      );
    }
    $this->logger->info('Re-generate rx refills by clinic for last 12 months, completed.');

    // Refills: yesterday by staff
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    $data = $this->dashboardManager->getStaffTotalRxRefillByRxTypeByDate($yesterday);
    $staff_scit_refill_yesterday = !empty($data['scit_refill']) ? $data['scit_refill'] : 0;
    $staff_slit_refill_yesterday = !empty($data['slit_refill']) ? $data['slit_refill'] : 0;

    // Update the table
    $this->dashboardManager->setBillingInfoByDate(
      [
        'dashboard_date' => $yesterday,
        'staff_scit_refill' => $staff_scit_refill_yesterday,
        'staff_slit_refill' => $staff_slit_refill_yesterday,
        'staff_rx_refill_last_update' => $time,
      ],
      [
        'staff_scit_refill' => $staff_scit_refill_yesterday,
        'staff_slit_refill' => $staff_slit_refill_yesterday,
        'staff_rx_refill_last_update' => $time,
      ],
      [
        'dashboard_date' => $yesterday,
      ]
    );
    $this->logger->info('Re-generate rx refills by staff for yesterday, completed.');

    // Rx refills by staff: last 12 months
    for ($i = 1; $i <= 12; $i++) {
      $past_month = date('n', strtotime("-" . $i . " month"));
      $past_month_year = date('Y', strtotime("-" . $i . " month"));
      $data = $this->dashboardManager->getStaffTotalRxRefillByRxTypeByMonth($past_month_year,$past_month );
      $staff_scit_refill = !empty($data['scit_refill']) ? $data['scit_refill'] : 0;
      $staff_slit_refill = !empty($data['slit_refill']) ? $data['slit_refill'] : 0;

      // Update the table
      $this->dashboardManager->setBillingInfoByMonth(
        [
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
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
          'dashboard_year' => $past_month_year,
          'dashboard_month' => $past_month,
        ]
      );
    }

    $this->logger->info('Re-generate rx refills by staff for last 12 months, completed.');

    $ajax_response = new AjaxResponse();
    $html = '<h3 class="status-message info">' . $this->t('Dashboard data has been re-generated successfully.') . '</h3>';
    $ajax_response->addCommand(new InvokeCommand('#mrs-dashboard-update-all-form-wrapper', 'html', [$html]));

    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function cancelCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
