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
use Drupal\Core\Url;
use Drupal\mrs_base\Service\BaseHelper;
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

class DashboardCleanAllForm extends FormBase {

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
    QueueFactory $queue,
    BaseHelper $base_helper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $current_account;
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->logger = $logger->get('custom_example');
    $this->extensionListModule = $extension_list_module;
    $this->extensionListTheme = $extension_list_theme;
    $this->queueFactory = $queue;
    $this->baseHelper = $base_helper;
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
      $container->get('base.helper')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'custom_example_clean_all_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array|AjaxResponse
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $message  = '<h4>Do you want to clean all data for dashboard?</h4>';
    $message .= '<p>This will delete all data for dashboard. This action can not be undone.</p>';

    $form['results'] = [
      '#markup' => $message,
      '#prefix' => '<div id="custom_example_list_wrapper">',
      '#suffix' => '</div>',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Yes, Clean It Up'),
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
      '#value' => $this->t('No'),
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
    $form['#prefix'] = '<div id="mrs-dashboard-clean-all-form-wrapper">';
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
    $sql = 'TRUNCATE dashboard_billing_by_date';
    $this->database->query($sql)->execute();

    $sql = 'TRUNCATE dashboard_count_by_date';
    $this->database->query($sql)->execute();

    $sql = 'TRUNCATE dashboard_billing_by_week';
    $this->database->query($sql)->execute();

    $sql = 'TRUNCATE dashboard_billing_by_month';
    $this->database->query($sql)->execute();

    $ajax_response = new AjaxResponse();
    $html = '<h3 class="status-message info">' . $this->t('Dashboard data has been cleaned up successful.') . '</h3>';
    $ajax_response->addCommand(new InvokeCommand('#mrs-dashboard-clean-all-form-wrapper', 'html', [$html]));

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
