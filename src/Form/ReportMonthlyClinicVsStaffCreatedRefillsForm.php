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

class ReportMonthlyClinicVsStaffCreatedRefillsForm extends FormBase {

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
    return 'custom_example_report_monthly_clinic_vs_staff_list_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array|AjaxResponse
   */
  public function buildForm(array $form, FormStateInterface $form_state, $action_type = NULL, $month = NULL, $year = NULL) {
    if (empty($action_type)) {
      throw new AccessDeniedHttpException('Sorry! No valid Action type reference found.');
    }

    if (empty($month)) {
      throw new AccessDeniedHttpException('Sorry! No valid month reference found.');
    }

    if (empty($year)) {
      throw new AccessDeniedHttpException('Sorry! No valid year reference found.');
    }

    $html = '';

    if ($action_type == 'created') {
      $subquery = $this->database->select('user__roles', 'ur');
      $subquery->condition('ur.roles_target_id', ['clinic_provider_representative', 'testing_admin', 'provider'], 'IN');
      $subquery->addField('ur', 'entity_id', 'uid');
      $subquery->distinct();

      $query = $this->database->select('node_field_data', 'n');
      $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
      $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
      $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
      $query->innerJoin('node__field_rx_amount', 'ra', "ra.entity_id = n.nid and ra.bundle = 'rx'");
      $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
      $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $year], '=');
      $query->where("MONTH(od.field_order_date_value) = :order_date_value", [':order_date_value' => $month], '=');
      $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
      $query->condition('n.type', 'rx');
      $query->condition('n.status', 1);
      $query->condition('u.uid', $subquery, 'IN');
      $query->fields('n', ['nid', 'title']);
      $query->addExpression('od.field_order_date_value', 'rx_date');
      $query->addExpression('ra.field_rx_amount_value', 'amount');
      $query->addExpression('bt.field_rx_base_type_value', 'base_type');
      $results = $query->execute()->fetchAll();

      $total_amount = 0;

      $rx_links = '<h5>Clinic: Rx Created</h5>';
      $rx_links .= '<table cellspacing="0" cellpadding="5" class="rx-link-list">';
      $rx_links .= '<thead>';
      $rx_links .= '  <tr>';
      $rx_links .= '    <th>Rx</th>';
      $rx_links .= '    <th>Rx Date</th>';
      $rx_links .= '    <th>Rx Base Type</th>';
      $rx_links .= '    <th class="amount">Amount</th>';
      $rx_links .= '  </tr>';
      $rx_links .= '</thead>';
      $rx_links .= '<tbody>';

      if (count($results) > 0) {
        foreach ($results as $row) {
          $rx_links .= '  <tr>';
          $rx_links .= '    <td><a target="_blank" href="/node/' . $row->nid . '">' . $row->title . '</a></td>';
          $rx_links .= '    <td>' . (isset($row->rx_date) && !empty($row->rx_date) ? date('m/d/Y', strtotime($row->rx_date)) : '') . '</td>';
          $rx_links .= '    <td>' . (isset($row->base_type) && !empty($row->base_type) ? $row->base_type : 'SLIT') . '</td>';
          $rx_links .= '    <td class="amount">' . (isset($row->amount) && !empty($row->amount) ? '$ ' . number_format($row->amount,2) : '') . '</td>';
          $rx_links .= '  </tr>';

          $total_amount = $total_amount + floatval($row->amount);
        }
        $rx_links .= '  <tr class="summary">';
        $rx_links .= '    <td class="summary-label" colspan="3">Total Amount: </td>';
        $rx_links .= '    <td class="summary-value">$ ' . number_format($total_amount,2) . '</td>';
        $rx_links .= '  </tr>';
      }
      else {
        $rx_links .= '  <tr>';
        $rx_links .= '    <td colspan="4">Sorry! No rx information found!</td>';
        $rx_links .= '  </tr>';
      }

      $rx_links .= '</tbody>';
      $rx_links .= '</table>';

      // Contents
      $html .= $rx_links;
      $html .= '<br /><br />';

      $subquery = $this->database->select('user__roles', 'ur');
      $subquery->condition('ur.roles_target_id', ['pharmacy', 'clinical_support', 'field_rep', 'order_manager', 'administrator', 'website_manager', 'call_center'], 'IN');
      $subquery->addField('ur', 'entity_id', 'uid');
      $subquery->distinct();

      $query = $this->database->select('node_field_data', 'n');
      $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
      $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
      $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
      $query->innerJoin('node__field_rx_amount', 'ra', "ra.entity_id = n.nid and ra.bundle = 'rx'");
      $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
      $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $year], '=');
      $query->where("MONTH(od.field_order_date_value) = :order_date_value", [':order_date_value' => $month], '=');
      $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
      $query->condition('n.type', 'rx');
      $query->condition('n.status', 1);
      $query->condition('u.uid', $subquery, 'IN');
      $query->fields('n', ['nid', 'title']);
      $query->addExpression('od.field_order_date_value', 'rx_date');
      $query->addExpression('ra.field_rx_amount_value', 'amount');
      $query->addExpression('bt.field_rx_base_type_value', 'base_type');
      $results = $query->execute()->fetchAll();

      $total_amount = 0;

      $rx_links = '<h5>Staff: Rx Created</h5>';
      $rx_links .= '<table cellspacing="0" cellpadding="5" class="rx-link-list">';
      $rx_links .= '<thead>';
      $rx_links .= '  <tr>';
      $rx_links .= '    <th>Rx</th>';
      $rx_links .= '    <th>Rx Date</th>';
      $rx_links .= '    <th>Rx Base Type</th>';
      $rx_links .= '    <th class="amount">Amount</th>';
      $rx_links .= '  </tr>';
      $rx_links .= '</thead>';
      $rx_links .= '<tbody>';

      if (count($results) > 0) {
        foreach ($results as $row) {
          $rx_links .= '  <tr>';
          $rx_links .= '    <td><a target="_blank" href="/node/' . $row->nid . '">' . $row->title . '</a></td>';
          $rx_links .= '    <td>' . (isset($row->rx_date) && !empty($row->rx_date) ? date('m/d/Y', strtotime($row->rx_date)) : '') . '</td>';
          $rx_links .= '    <td>' . (isset($row->base_type) && !empty($row->base_type) ? $row->base_type : 'SLIT') . '</td>';
          $rx_links .= '    <td class="amount">' . (isset($row->amount) && !empty($row->amount) ? '$ ' . number_format($row->amount,2) : '') . '</td>';
          $rx_links .= '  </tr>';

          $total_amount = $total_amount + floatval($row->amount);
        }

        $rx_links .= '  <tr class="summary">';
        $rx_links .= '    <td class="summary-label" colspan="3">Total Amount: </td>';
        $rx_links .= '    <td class="summary-value">$ ' . number_format($total_amount,2) . '</td>';
        $rx_links .= '  </tr>';
      }
      else {
        $rx_links .= '  <tr>';
        $rx_links .= '    <td colspan="4">Sorry! No rx information found!</td>';
        $rx_links .= '  </tr>';
      }

      $rx_links .= '</tbody>';
      $rx_links .= '</table>';

      // Contents
      $html .= $rx_links;
      $html .= '<br /><br />';
    }
    elseif ($action_type == 'refills') {
      $subquery = $this->database->select('user__roles', 'ur');
      $subquery->condition('ur.roles_target_id', ['clinic_provider_representative', 'testing_admin', 'provider'], 'IN');
      $subquery->addField('ur', 'entity_id', 'uid');
      $subquery->distinct();

      $query = $this->database->select('node_field_data', 'n');
      $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
      $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
      $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
      $query->innerJoin('node__field_rx_amount', 'ra', "ra.entity_id = n.nid and ra.bundle = 'rx'");
      $query->innerJoin('node__field_refill_rx_id', 'rrx', "rrx.entity_id = n.nid and rrx.bundle = 'rx'");
      $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
      $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $year], '=');
      $query->where("MONTH(od.field_order_date_value) = :order_date_value", [':order_date_value' => $month], '=');
      $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
      $query->condition('n.type', 'rx');
      $query->condition('n.status', 1);
      $query->condition('u.uid', $subquery, 'IN');
      $query->fields('n', ['nid', 'title']);
      $query->addExpression('od.field_order_date_value', 'rx_date');
      $query->addExpression('ra.field_rx_amount_value', 'amount');
      $query->addExpression('bt.field_rx_base_type_value', 'base_type');
      $results = $query->execute()->fetchAll();

      $total_amount = 0;

      $rx_links = '<h5>Clinic: Rx Refills</h5>';
      $rx_links .= '<table cellspacing="0" cellpadding="5" class="rx-link-list">';
      $rx_links .= '<thead>';
      $rx_links .= '  <tr>';
      $rx_links .= '    <th>Rx</th>';
      $rx_links .= '    <th>Rx Date</th>';
      $rx_links .= '    <th>Rx Base Type</th>';
      $rx_links .= '    <th class="amount">Amount</th>';
      $rx_links .= '  </tr>';
      $rx_links .= '</thead>';
      $rx_links .= '<tbody>';

      if (count($results) > 0) {
        foreach ($results as $row) {
          $rx_links .= '  <tr>';
          $rx_links .= '    <td><a target="_blank" href="/node/' . $row->nid . '">' . $row->title . '</a></td>';
          $rx_links .= '    <td>' . (isset($row->rx_date) && !empty($row->rx_date) ? date('m/d/Y', strtotime($row->rx_date)) : '') . '</td>';
          $rx_links .= '    <td>' . (isset($row->base_type) && !empty($row->base_type) ? $row->base_type : 'SLIT') . '</td>';
          $rx_links .= '    <td class="amount">' . (isset($row->amount) && !empty($row->amount) ? '$ ' . number_format($row->amount,2) : '') . '</td>';
          $rx_links .= '  </tr>';

          $total_amount = $total_amount + floatval($row->amount);
        }
        $rx_links .= '  <tr class="summary">';
        $rx_links .= '    <td class="summary-label" colspan="3">Total Amount: </td>';
        $rx_links .= '    <td class="summary-value">$ ' . number_format($total_amount,2) . '</td>';
        $rx_links .= '  </tr>';
      }
      else {
        $rx_links .= '  <tr>';
        $rx_links .= '    <td colspan="4">Sorry! No rx information found!</td>';
        $rx_links .= '  </tr>';
      }

      $rx_links .= '</tbody>';
      $rx_links .= '</table>';

      // Contents
      $html .= $rx_links;
      $html .= '<br /><br />';

      $subquery = $this->database->select('user__roles', 'ur');
      $subquery->condition('ur.roles_target_id', ['pharmacy', 'clinical_support', 'field_rep', 'order_manager', 'administrator', 'website_manager', 'call_center'], 'IN');
      $subquery->addField('ur', 'entity_id', 'uid');
      $subquery->distinct();

      $query = $this->database->select('node_field_data', 'n');
      $query->innerJoin('node__field_workflow_status', 'ws', "ws.entity_id = n.nid AND ws.bundle = 'rx'");
      $query->innerJoin('node__field_order_date', 'od', "od.entity_id = n.nid AND od.bundle = 'rx'");
      $query->innerJoin('node__field_rx_base_type', 'bt', "bt.entity_id = n.nid AND bt.bundle = 'rx'");
      $query->innerJoin('node__field_rx_amount', 'ra', "ra.entity_id = n.nid and ra.bundle = 'rx'");
      $query->innerJoin('node__field_refill_rx_id', 'rrx', "rrx.entity_id = n.nid and rrx.bundle = 'rx'");
      $query->innerJoin('users_field_data', 'u', "n.uid = u.uid");
      $query->where("YEAR(od.field_order_date_value) = :order_date_value", [':order_date_value' => $year], '=');
      $query->where("MONTH(od.field_order_date_value) = :order_date_value", [':order_date_value' => $month], '=');
      $query->condition('ws.field_workflow_status_value', ['Cancelled'], 'NOT IN');
      $query->condition('n.type', 'rx');
      $query->condition('n.status', 1);
      $query->condition('u.uid', $subquery, 'IN');
      $query->fields('n', ['nid', 'title']);
      $query->addExpression('od.field_order_date_value', 'rx_date');
      $query->addExpression('ra.field_rx_amount_value', 'amount');
      $query->addExpression('bt.field_rx_base_type_value', 'base_type');
      $results = $query->execute()->fetchAll();

      $total_amount = 0;

      $rx_links = '<h5>Staff: Rx Refills</h5>';
      $rx_links .= '<table cellspacing="0" cellpadding="5" class="rx-link-list">';
      $rx_links .= '<thead>';
      $rx_links .= '  <tr>';
      $rx_links .= '    <th>Rx</th>';
      $rx_links .= '    <th>Rx Date</th>';
      $rx_links .= '    <th>Rx Base Type</th>';
      $rx_links .= '    <th class="amount">Amount</th>';
      $rx_links .= '  </tr>';
      $rx_links .= '</thead>';
      $rx_links .= '<tbody>';

      if (count($results) > 0) {
        foreach ($results as $row) {
          $rx_links .= '  <tr>';
          $rx_links .= '    <td><a target="_blank" href="/node/' . $row->nid . '">' . $row->title . '</a></td>';
          $rx_links .= '    <td>' . (isset($row->rx_date) && !empty($row->rx_date) ? date('m/d/Y', strtotime($row->rx_date)) : '') . '</td>';
          $rx_links .= '    <td>' . (isset($row->base_type) && !empty($row->base_type) ? $row->base_type : 'SLIT') . '</td>';
          $rx_links .= '    <td class="amount">' . (isset($row->amount) && !empty($row->amount) ? '$ ' . number_format($row->amount,2) : '') . '</td>';
          $rx_links .= '  </tr>';

          $total_amount = $total_amount + floatval($row->amount);
        }
        $rx_links .= '  <tr class="summary">';
        $rx_links .= '    <td class="summary-label" colspan="3">Total Amount: </td>';
        $rx_links .= '    <td class="summary-value">$ ' . number_format($total_amount,2) . '</td>';
        $rx_links .= '  </tr>';
      }
      else {
        $rx_links .= '  <tr>';
        $rx_links .= '    <td colspan="4">Sorry! No rx information found!</td>';
        $rx_links .= '  </tr>';
      }

      $rx_links .= '</tbody>';
      $rx_links .= '</table>';

      // Contents
      $html .= $rx_links;
      $html .= '<br /><br />';
    }

    $form['results'] = [
      '#markup' => Markup::create($html),
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
