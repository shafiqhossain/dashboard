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

class ReportMonthlyTotalSalesForm extends FormBase {

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
    return 'custom_example_report_monthly_sales_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array|AjaxResponse
   */
  public function buildForm(array $form, FormStateInterface $form_state, $month = NULL, $year = NULL) {
    if (empty($month)) {
      throw new AccessDeniedHttpException('Sorry! No valid month reference found.');
    }

    if (empty($year)) {
      throw new AccessDeniedHttpException('Sorry! No valid year reference found.');
    }

    // Monthly : rx amount
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx' ");
    $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
    $query->innerJoin('node__field_rxtype','rt', "rt.entity_id = n.nid AND rt.bundle = 'rx'");
    $query->innerJoin('node__field_billing_info', 'fc', "fc.entity_id = n.nid and fc.bundle = 'rx' ");
    $query->innerJoin('paragraphs_item_field_data', 'p', "p.id = fc.field_billing_info_target_id and p.type = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_date', 'td', "td.entity_id = fc.field_billing_info_target_id and td.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_rx_amount', 'am', "am.entity_id = fc.field_billing_info_target_id and am.bundle = 'billing_info' ");
    $query->innerJoin('paragraph__field_tx_status', 'ts', "ts.entity_id = fc.field_billing_info_target_id and ts.bundle = 'billing_info' ");
    $query->condition('ts.field_tx_status_value', ['Successful', 'Invoice', 'Paid', 'Signed', 'Successful - Fee', 'Successful - PSCC', 'Manual'], 'IN');
    $query->where("YEAR(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $year], '=');
    $query->where("MONTH(td.field_tx_date_value) = :tx_date_value", [':tx_date_value' => $month], '=');
    $query->condition('ws.field_workflow_status_value', 'Cancelled', '!=');
    $query->condition('n.type', 'rx');
    $query->condition('n.status', 1);
    $query->fields('n', ['nid', 'title']);
    $query->addExpression('td.field_tx_date_value', 'tx_date');
    $query->addExpression('am.field_rx_amount_value', 'amount');
    $query->addExpression('ts.field_tx_status_value', 'status');
    $query->addExpression('bt.field_rx_base_type_value', 'base_type');
    $query->addExpression('rt.field_rxtype_value', 'rx_type');
    $query->orderBy('td.field_tx_date_value', 'DESC');
    $results = $query->execute()->fetchAll();

    $rx_links = '<table cellspacing="0" cellpadding="5" class="rx-link-list">';
    $rx_links .= '<thead>';
    $rx_links .= '  <tr>';
    $rx_links .= '    <th>Rx</th>';
    $rx_links .= '    <th>Transaction Date</th>';
    $rx_links .= '    <th>Base Type</th>';
    $rx_links .= '    <th>Rx Type</th>';
    $rx_links .= '    <th class="amount">Amount</th>';
    $rx_links .= '  </tr>';
    $rx_links .= '</thead>';
    $rx_links .= '<tbody>';

    $total_amount = 0;
    $monthly_total_rx_amount = 0;

    if (count($results) > 0) {
      foreach($results as $row) {
        $rx_type = (isset($row->rx_type) ? $row->rx_type : '');
        $rx_type_value = $this->baseHelper->getRxType($rx_type);

        $rx_links .= '  <tr>';
        $rx_links .= '    <td><a target="_blank" href="/node/' . $row->nid . '">' . $row->title . '</a></td>';
        $rx_links .= '    <td>' . (isset($row->tx_date) && !empty($row->tx_date) ? date('m/d/Y', strtotime($row->tx_date)) : '') . '</td>';
        $rx_links .= '    <td>' . (isset($row->base_type) && !empty($row->base_type) ? $row->base_type : '') . '</td>';
        $rx_links .= '    <td>' . $rx_type_value . '</td>';
        $rx_links .= '    <td class="amount">' . (isset($row->amount) && !empty($row->amount) ? '$ '.number_format($row->amount,2) : '') . '</td>';
        $rx_links .= '  </tr>';

        $total_amount = $total_amount + floatval($row->amount);
      }
      $monthly_total_rx_amount = $total_amount;

      $rx_links .= '  <tr class="summary">';
      $rx_links .= '    <td class="summary-label" colspan="4">Total Amount: </td>';
      $rx_links .= '    <td class="summary-value">$ ' . number_format($total_amount,2) . '</td>';
      $rx_links .= '  </tr>';
    }
    else {
      $rx_links .= '  <tr>';
      $rx_links .= '    <td colspan="5">Sorry! No rx information found!</td>';
      $rx_links .= '  </tr>';
    }
    $rx_links .= '</tbody>';
    $rx_links .= '</table>';


    // Monthly : po amount
    $query = $this->database->select('node_field_data', 'n');
    $query->innerJoin('node__field_amount', 'pom', "pom.entity_id = n.nid");
    $query->innerJoin('node__field_po_date', 'pod', "pod.entity_id = n.nid");
    $query->innerJoin('node__field_po_type', 'pot', "pot.entity_id = n.nid");
    $query->where("YEAR(pod.field_po_date_value) = :po_date_value", [':po_date_value' => $year], '=');
    $query->where("MONTH(pod.field_po_date_value) = :po_date_value", [':po_date_value' => $month], '=');
    $query->condition('n.type', 'po');
    $query->condition('n.status', 1);
    $query->condition('pot.field_po_type_value', 1);
    $query->fields('n', ['nid', 'title']);
    $query->addExpression('pod.field_po_date_value', 'tx_date');
    $query->addExpression("CAST(REPLACE(pom.field_amount_value, '$', '') AS DECIMAL(10,2))", 'amount');
    $query->orderBy('pod.field_po_date_value', 'DESC');
    $results = $query->execute()->fetchAll();

    $po_links = '<table cellspacing="0" cellpadding="5" class="rx-link-list">';
    $po_links .= '<thead>';
    $po_links .= '  <tr>';
    $po_links .= '    <th>PO</th>';
    $po_links .= '    <th>Transaction Date</th>';
    $po_links .= '    <th class="amount">Amount</th>';
    $po_links .= '  </tr>';
    $po_links .= '</thead>';
    $po_links .= '<tbody>';

    $total_amount = 0;
    $monthly_total_po_amount = 0;

    if (count($results) > 0) {
      foreach ($results as $row) {
        $po_links .= '  <tr>';
        $po_links .= '    <td><a target="_blank" href="/node/' . $row->nid . '">' . $row->title . '</a></td>';
        $po_links .= '    <td>' . (isset($row->tx_date) && !empty($row->tx_date) ? date('m/d/Y', strtotime($row->tx_date)) : '') . '</td>';
        $po_links .= '    <td class="amount">' . (isset($row->amount) && !empty($row->amount) ? '$ ' . number_format($row->amount,2) : '') . '</td>';
        $po_links .= '  </tr>';

        $total_amount = $total_amount + floatval($row->amount);
      }
      $monthly_total_po_amount = $total_amount;

      $po_links .= '  <tr class="summary">';
      $po_links .= '    <td class="summary-label" colspan="2">Total Amount: </td>';
      $po_links .= '    <td class="summary-value">$ ' . number_format($total_amount,2) . '</td>';
      $po_links .= '  </tr>';
    }
    else {
      $po_links .= '  <tr>';
      $po_links .= '    <td colspan="3">Sorry! No PO information found!</td>';
      $po_links .= '  </tr>';
    }
    $po_links .= '</tbody>';
    $po_links .= '</table>';

    // Monthly : store amount
    $query = $this->database->select('commerce_order', 'ord');
    $query->where("YEAR(FROM_UNIXTIME(ord.created)) = :created", [':created' => $year], '=');
    $query->where("MONTH(FROM_UNIXTIME(ord.created)) = :created", [':created' => $month], '=');
    $query->condition('ord.state', ['completed', 'processing', 'pending', 'checkout_complete'], 'IN');
    $query->fields('ord', ['order_id', 'uid']);
    $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(ord.created), '%Y-%m-%d')", 'tx_date');
    $query->addExpression("ord.total_price__number / 100", 'amount');
    $query->orderBy('ord.created', 'DESC');
    $results = $query->execute()->fetchAll();

    $order_links = '<table cellspacing="0" cellpadding="5" class="rx-link-list">';
    $order_links .= '<thead>';
    $order_links .= '  <tr>';
    $order_links .= '    <th>Order</th>';
    $order_links .= '    <th>Order Date</th>';
    $order_links .= '    <th class="amount">Amount</th>';
    $order_links .= '  </tr>';
    $order_links .= '</thead>';
    $order_links .= '<tbody>';

    $total_amount = 0;
    $monthly_total_store_amount = 0;

    if (count($results) > 0) {
      foreach ($results as $row) {
        $order_links .= '  <tr>';
        $order_links .= '    <td><a target="_blank" href="/user/' . $row->uid . '/orders/' . $row->order_id . '">Order# ' . $row->order_id . '</a></td>';
        $order_links .= '    <td>' . (isset($row->tx_date) && !empty($row->tx_date) ? date('m/d/Y', strtotime($row->tx_date)) : '') . '</td>';
        $order_links .= '    <td class="amount">' . (isset($row->amount) && !empty($row->amount) ? '$ ' . number_format($row->amount,2) : '') . '</td>';
        $order_links .= '  </tr>';

        $total_amount = $total_amount + floatval($row->amount);
      }
      $monthly_total_store_amount = $total_amount;

      $order_links .= '  <tr class="summary">';
      $order_links .= '    <td class="summary-label" colspan="2">Total Amount: </td>';
      $order_links .= '    <td class="summary-value">$ ' . number_format($total_amount,2) . '</td>';
      $order_links .= '  </tr>';
    }
    else {
      $order_links .= '  <tr>';
      $order_links .= '    <td colspan="3">Sorry! No order information found!</td>';
      $order_links .= '  </tr>';
    }
    $order_links .= '</tbody>';
    $order_links .= '</table>';


    // Total sales amount
    $monthly_total_sales_amount = $monthly_total_rx_amount + $monthly_total_po_amount + $monthly_total_store_amount;

    $all_links = '';
    $all_links .= '<h3>Rx</h3>';
    $all_links .= $rx_links;
    $all_links .= '<br /><hr /><br />';
    $all_links .= '<h3>PO</h3>';
    $all_links .= $po_links;
    $all_links .= '<br /><hr /><br />';
    $all_links .= '<h3>Order</h3>';
    $all_links .= $order_links;
    $all_links .= '<br /><hr /><br />';
    $all_links .= '<strong>Total Sales Amount: ' . number_format($monthly_total_sales_amount,2) . '</strong>';

    $form['results'] = [
      '#markup' => Markup::create($all_links),
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
