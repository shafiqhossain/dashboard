<?php

/**
 * @file
 * Contains Drupal\custom_example\Form\DashboardSettingsForm.
 */

namespace Drupal\custom_example\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class DashboardSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_example_settings_form';
  }
   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('custom_example.settings');

    $form['mrdash_rx_order_amount'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rx and Store Amount'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_rx_order_amount') ? $form_state->getValue('mrdash_rx_order_amount') : $config->get('mrdash_rx_order_amount'),
    ];
    $form['mrdash_po_amount'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('PO and PO Refund'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_po_amount') ? $form_state->getValue('mrdash_po_amount') : $config->get('mrdash_po_amount'),
    ];
    $form['mrdash_sales_amount'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sales Amount'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_sales_amount') ? $form_state->getValue('mrdash_sales_amount') : $config->get('mrdash_sales_amount'),
    ];
    $form['mrdash_rx_created_refills'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rx Created and Refills'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_rx_created_refills') ? $form_state->getValue('mrdash_rx_created_refills') : $config->get('mrdash_rx_created_refills'),
    ];
    $form['mrdash_rx_payments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rx Payments'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_rx_payments') ? $form_state->getValue('mrdash_rx_payments') : $config->get('mrdash_rx_payments'),
    ];
    $form['mrdash_order_submitted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Store Order Submitted'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_order_submitted') ? $form_state->getValue('mrdash_order_submitted') : $config->get('mrdash_order_submitted'),
    ];
    $form['mrdash_rx_pending'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rx Pending'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_rx_pending') ? $form_state->getValue('mrdash_rx_pending') : $config->get('mrdash_rx_pending'),
    ];
    $form['mrdash_rx_scheduled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rx Scheduled'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_rx_scheduled') ? $form_state->getValue('mrdash_rx_scheduled') : $config->get('mrdash_rx_scheduled'),
    ];
    $form['mrdash_rx_upcoming_refills'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rx Upcoming Refills'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_rx_upcoming_refills') ? $form_state->getValue('mrdash_rx_upcoming_refills') : $config->get('mrdash_rx_upcoming_refills'),
    ];
    $form['mrdash_expiring_rx'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expiring Rx'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_expiring_rx') ? $form_state->getValue('mrdash_expiring_rx') : $config->get('mrdash_expiring_rx'),
    ];
    $form['mrdash_expiring_arb'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expiring ARB'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_expiring_arb') ? $form_state->getValue('mrdash_expiring_arb') : $config->get('mrdash_expiring_arb'),
    ];
    $form['mrdash_expiring_cc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expiring Credit Cards'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_expiring_cc') ? $form_state->getValue('mrdash_expiring_cc') : $config->get('mrdash_expiring_cc'),
    ];
    $form['mrdash_expiring_profile_cc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expiring Profile Credit Cards'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_expiring_profile_cc') ? $form_state->getValue('mrdash_expiring_profile_cc') : $config->get('mrdash_expiring_profile_cc'),
    ];
    $form['mrdash_total_clinics'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Total Clinics'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_total_clinics') ? $form_state->getValue('mrdash_total_clinics') : $config->get('mrdash_total_clinics'),
    ];
    $form['mrdash_silentpost'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Silent Post Summary'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_silentpost') ? $form_state->getValue('mrdash_silentpost') : $config->get('mrdash_silentpost'),
    ];
    $form['mrdash_clinic_vs_staff'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clinic vs Staff Rx Create/Refills'),
      '#return_value' => 1,
      '#default_value' => $form_state->hasValue('mrdash_clinic_vs_staff') ? $form_state->getValue('mrdash_clinic_vs_staff') : $config->get('mrdash_clinic_vs_staff'),
    ];

    $options = [];
    $options[0] = $this->t('--None--');
    $options[1] = $this->t('01 minutes');
    // $options[2] = $this->t('02 minutes');
    $options[5] = $this->t('05 minutes');
    $options[10] = $this->t('10 minutes');
    $options[15] = $this->t('15 minutes');
    $options[20] = $this->t('20 minutes');
    $options[25] = $this->t('25 minutes');
    $options[30] = $this->t('30 minutes');
    $options[35] = $this->t('35 minutes');
    $options[40] = $this->t('40 minutes');
    $options[45] = $this->t('45 minutes');
    $options[50] = $this->t('50 minutes');
    $options[55] = $this->t('55 minutes');
    $options[60] = $this->t('60 minutes');

    $form['mrsdash_refresh_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Auto Refresh Interval'),
      '#default_value' => $form_state->hasValue('mrsdash_refresh_interval') ? $form_state->getValue('mrsdash_refresh_interval') : $config->get('mrsdash_refresh_interval'),
      '#options' => $options,
      '#required' => FALSE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
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
    $form['#prefix'] = '<div id="mrs-dashboard-settings-form-wrapper">';
    $form['#suffix'] = '</div>';

    return $form;
  }
   /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  function saveCallback(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $html = '<h3 class="status-message info">' . $this->t('Dashboard settings has been saved successfully. <br />Refresh interval will be changed on next page load.') . '</h3>';
    $ajax_response->addCommand(new InvokeCommand('#mrs-dashboard-settings-form-wrapper', 'html', [$html]));

    $this->configFactory()->getEditable('custom_example.settings')
      ->set('mrdash_rx_order_amount', $form_state->getValue('mrdash_rx_order_amount'))
      ->set('mrdash_po_amount', $form_state->getValue('mrdash_po_amount'))
      ->set('mrdash_sales_amount', $form_state->getValue('mrdash_sales_amount'))
      ->set('mrdash_rx_created_refills', $form_state->getValue('mrdash_rx_created_refills'))
      ->set('mrdash_rx_payments', $form_state->getValue('mrdash_rx_payments'))
      ->set('mrdash_order_submitted', $form_state->getValue('mrdash_order_submitted'))
      ->set('mrdash_rx_pending', $form_state->getValue('mrdash_rx_pending'))
      ->set('mrdash_rx_scheduled', $form_state->getValue('mrdash_rx_scheduled'))
      ->set('mrdash_rx_upcoming_refills', $form_state->getValue('mrdash_rx_upcoming_refills'))
      ->set('mrdash_expiring_rx', $form_state->getValue('mrdash_expiring_rx'))
      ->set('mrdash_expiring_arb', $form_state->getValue('mrdash_expiring_arb'))
      ->set('mrdash_expiring_cc', $form_state->getValue('mrdash_expiring_cc'))
      ->set('mrdash_expiring_profile_cc', $form_state->getValue('mrdash_expiring_profile_cc'))
      ->set('mrdash_total_clinics', $form_state->getValue('mrdash_total_clinics'))
      ->set('mrdash_silentpost', $form_state->getValue('mrdash_silentpost'))
      ->set('mrdash_clinic_vs_staff', $form_state->getValue('mrdash_clinic_vs_staff'))
      ->set('mrsdash_refresh_interval', $form_state->getValue('mrsdash_refresh_interval'))
      ->save();

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
