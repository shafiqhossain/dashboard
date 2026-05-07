<?php

namespace Drupal\custom_example\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\Markup;
use Drupal\mrs_base\Service\BaseHelper;
use Drupal\custom_example\Service\DashboardHelper;
use Drupal\custom_example\Service\DashboardManager;
use \Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ReportDailyPaymentsForm extends FormBase {

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
   * The base helper service.
   *
   * @var \Drupal\mrs_base\Service\BaseHelper
   */
  protected $baseHelper;

  /**
   * The dashboard helper service.
   *
   * @var \Drupal\custom_example\Service\DashboardHelper
   */
  protected $dashboardHelper;

  /**
   * The dashboard manager service.
   *
   * @var DashboardManager
   */
  protected $dashboardManager;


  /**
   * Constructs class.
   *
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_account,
    Connection $database,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger,
    ModuleExtensionList $extension_list_module,
    ThemeExtensionList $extension_list_theme,
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
      $container->get('base.helper'),
      $container->get('dashboard.helper'),
      $container->get('dashboard.manager')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'custom_example_report_daily_payments_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array|AjaxResponse
   */
  public function buildForm(array $form, FormStateInterface $form_state, $payment_type = NULL) {
    if (empty($payment_type)) {
      throw new AccessDeniedHttpException('Sorry! No valid payment type reference found.');
    }

    // Today
    if ($payment_type == 'successful') {
      $query = $this->database->select('node_field_data', 'n');
      $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
      //@todo: Check the join with paragraph fields
      $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
      $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
      $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
      $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
      $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
      $query->condition('ts.field_tx_status_value', ['Successful', 'Successful - Fee', 'Successful - PSCC', 'Paid', 'Manual'], 'IN');
      $query->where("DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = :tx_date_value", [':tx_date_value' => date("Y-m-d")], '=');
      $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
      $query->condition('n.type', 'rx');
      $query->condition('n.status', 1);
      $query->fields('n', ['nid', 'title']);
      $query->addExpression('td.field_tx_date_value', 'tx_date');
      $query->addExpression('am.field_rx_amount_value', 'amount');
    }
    elseif ($payment_type == 'refund') {
      $query = $this->database->select('node_field_data', 'n');
      $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
      //@todo: Check the join with paragraph fields
      $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
      $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
      $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
      $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
      $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
      $query->condition('ts.field_tx_status_value', ['Refund'], 'IN');
      $query->where("DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = :tx_date_value", [':tx_date_value' => date("Y-m-d")], '=');
      $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
      $query->condition('n.type', 'rx');
      $query->condition('n.status', 1);
      $query->fields('n', ['nid', 'title']);
      $query->addExpression('td.field_tx_date_value', 'tx_date');
      $query->addExpression('am.field_rx_amount_value', 'amount');
    }
    elseif ($payment_type == 'invoice') {
      $query = $this->database->select('node_field_data', 'n');
      $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
      //@todo: Check the join with paragraph fields
      $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
      $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
      $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
      $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
      $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
      $query->innerJoin('node__field_paymenttype', 'pt', "pt.entity_id = n.nid");
      $query->condition('ts.field_tx_status_value', ['Payment Pending'], 'IN');
      $query->where("DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = :tx_date_value", [':tx_date_value' => date("Y-m-d")], '=');
      $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
      $query->condition('n.type', 'rx');
      $query->condition('n.status', 1);
      $query->condition('pt.field_paymenttype_value', 3);  // Invoice
      $query->fields('n', ['nid', 'title']);
      $query->addExpression('td.field_tx_date_value', 'tx_date');
      $query->addExpression('am.field_rx_amount_value', 'amount');
    }
    elseif ($payment_type == 'error') {
      $query = $this->database->select('node_field_data', 'n');
      $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
      //@todo: Check the join with paragraph fields
      $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
      $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
      $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
      $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
      $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
      $query->condition('ts.field_tx_status_value', ['Error'], 'IN');
      $query->where("DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = :tx_date_value", [':tx_date_value' => date("Y-m-d")], '=');
      $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
      $query->condition('n.type', 'rx');
      $query->condition('n.status', 1);
      $query->fields('n', ['nid', 'title']);
      $query->addExpression('td.field_tx_date_value', 'tx_date');
      $query->addExpression('am.field_rx_amount_value', 'amount');
    }
    elseif ($payment_type == 'void') {
      $query = $this->database->select('node_field_data', 'n');
      $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
      //@todo: Check the join with paragraph fields
      $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
      $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
      $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
      $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
      $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
      $query->condition('ts.field_tx_status_value', ['Void'], 'IN');
      $query->where("DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = :tx_date_value", [':tx_date_value' => date("Y-m-d")], '=');
      $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
      $query->condition('n.type', 'rx');
      $query->condition('n.status', 1);
      $query->fields('n', ['nid', 'title']);
      $query->addExpression('td.field_tx_date_value', 'tx_date');
      $query->addExpression('am.field_rx_amount_value', 'amount');
    }
    elseif ($payment_type == 'denied') {
      $query = $this->database->select('node_field_data', 'n');
      $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
      //@todo: Check the join with paragraph fields
      $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
      $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
      $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
      $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
      $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
      $query->condition('ts.field_tx_status_value', ['Declined'], 'IN');
      $query->where("DATE_FORMAT(td.field_tx_date_value, '%Y-%m-%d') = :tx_date_value", [':tx_date_value' => date("Y-m-d")], '=');
      $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
      $query->condition('n.type', 'rx');
      $query->condition('n.status', 1);
      $query->fields('n', ['nid', 'title']);
      $query->addExpression('td.field_tx_date_value', 'tx_date');
      $query->addExpression('am.field_rx_amount_value', 'amount');
    }
    $results = $query->execute()->fetchAll();

    $total_amount = 0;

    $rx_links = '<table cellspacing="0" cellpadding="5" class="rx-link-list">';
    $rx_links .= '<thead>';
    $rx_links .= '  <tr>';
    $rx_links .= '    <th>Rx</th>';
    $rx_links .= '    <th>Transaction Date</th>';
    $rx_links .= '    <th class="amount">Amount</th>';
    $rx_links .= '  </tr>';
    $rx_links .= '</thead>';
    $rx_links .= '<tbody>';

    if (count($results) > 0) {
      foreach ($results as $row) {
        $rx_links .= '  <tr>';
        $rx_links .= '    <td><a target="_blank" href="/node/' . $row->nid . '">' . $row->title . '</a></td>';
        $rx_links .= '    <td>' . (isset($row->tx_date) && !empty($row->tx_date) ? date('m/d/Y', strtotime($row->tx_date)) : '') . '</td>';
        $rx_links .= '    <td class="amount">' . (isset($row->amount) && !empty($row->amount) ? '$ ' . number_format($row->amount,2) : '') . '</td>';
        $rx_links .= '  </tr>';

        $total_amount = $total_amount + floatval($row->amount);
      }
      $rx_links .= '  <tr class="summary">';
      $rx_links .= '    <td class="summary-label" colspan="2">Total Amount: </td>';
      $rx_links .= '    <td class="summary-value">$ ' . number_format($total_amount,2) . '</td>';
      $rx_links .= '  </tr>';
    }
    else {
      $rx_links .= '  <tr>';
      $rx_links .= '    <td colspan="3">Sorry! No payment information found!</td>';
      $rx_links .= '  </tr>';
    }

    $rx_links .= '</tbody>';
    $rx_links .= '</table>';

    $form['results'] = [
      '#markup' => Markup::create($rx_links),
      '#prefix' => '<div id="custom_example_list_wrapper">',
      '#suffix' => '</div>',
    ];

    $form['actions']['#type'] = 'actions';
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

  /**
   * {@inheritdoc}
   */
  public function cancelCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
