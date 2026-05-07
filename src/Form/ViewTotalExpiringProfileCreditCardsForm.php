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

class ViewTotalExpiringProfileCreditCardsForm extends FormBase {

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
    return 'custom_example_total_expiring_profile_cc_view_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array|AjaxResponse
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Rx profile expiring
    $profile_cc_expiring_ids = $this->database->select('dashboard_count_by_date', 'n')
      ->condition('n.dashboard_date', date('Y-m-d'))
      ->fields('n', ['profile_cc_expiring_ids'])
      ->execute()
      ->fetchField();

    $profile_cc_links = '<table cellspacing="0" cellpadding="5" class="profile-cc-link-list">';
    $profile_cc_links .= '<thead>';
    $profile_cc_links .= '  <tr>';
    $profile_cc_links .= '    <th>Customer Profile</th>';
    $profile_cc_links .= '    <th>Payment Profile</th>';
    $profile_cc_links .= '    <th>Notification Date</th>';
    $profile_cc_links .= '  </tr>';
    $profile_cc_links .= '</thead>';
    $profile_cc_links .= '<tbody>';

    if (!empty($profile_cc_expiring_ids)) {
      $rows = explode(',', $profile_cc_expiring_ids);
      if (count($rows) > 0) {
        foreach ($rows as $row) {
          $links = explode('|', $row);
          $customer_profile_id = (isset($links[0]) && !empty($links[0]) ? $links[0] : 0);
          $customer_payment_profile_id = (isset($links[1]) && !empty($links[1]) ? $links[1] : 0);
          $expire_date = (isset($links[2]) && !empty($links[2]) ? date('Y-m-d', strtotime($links[2])) : '');

          $profile_cc_links .= '  <tr>';
          $profile_cc_links .= '    <td>' . $customer_profile_id . '</td>';
          $profile_cc_links .= '    <td>' . $customer_payment_profile_id . '</td>';
          $profile_cc_links .= '    <td>' . $expire_date . '</td>';
          $profile_cc_links .= '  </tr>';
        }
      }
    }
    else {
      $profile_cc_links .= '  <tr>';
      $profile_cc_links .= '    <td colspan="3">Sorry! No information found!</td>';
      $profile_cc_links .= '  </tr>';
    }

    $profile_cc_links .= '</tbody>';
    $profile_cc_links .= '</table>';

    $form['results'] = [
      '#markup' => Markup::create($profile_cc_links),
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
