<?php

namespace Drupal\commerce_paynetmd\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_paynetmd\PaynetCommerceService;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_paynetmd\PaynetConfig;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the NetPay offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "paynet_redirect",
 *   label = @Translation("PayNet"),
 *   display_label = @Translation("PayNet"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_paynetmd\Plugin\PluginForm\RedirectCheckout\RedirectCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "maestro", "mastercard", "visa",
 *   },
 * )
 */
class PayNetRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'redirect_method' => 'post',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // A real gateway would always know which redirect method should be used,
    // it's made configurable here for test purposes.
    $form['redirect_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Redirect method'),
      '#options' => [
        'post' => $this->t('Redirect via POST (automatic)'),
        'post_manual' => $this->t('Redirect via POST (manual)'),
      ],
      '#default_value' => $this->configuration['redirect_method'],
    ];

    $form['live_configs'] = [
      '#type'  => 'details',
      '#title' => $this->t('Live configs'),
      '#required' => TRUE,
      '#open'  => FALSE,
      'inline_holder' => [
        '#type' => 'container',
        '#attributes'  => [
          'class' => 'form--inline clearfix',
        ],
        'paynet_api_base_ui_url' => [
          '#type' => 'textfield',
          '#title' => $this->t('API base URL'),
          '#required' => TRUE,
          '#default_value' => $this->configuration['paynet_api_base_ui_url'] ?: PaynetConfig::PAYNET_BASE_API_URL,
        ],
        'paynet_base_ui_url' => [
          '#type' => 'textfield',
          '#title' => $this->t('Base UI URL'),
          '#required' => TRUE,
          '#default_value' => $this->configuration['paynet_base_ui_url'] ?: PaynetConfig::PAYNET_BASE_UI_URL,
        ],
        'paynet_base_ui_server_url' => [
          '#type' => 'textfield',
          '#title' => $this->t('Base UI server URL'),
          '#required' => TRUE,
          '#default_value' => $this->configuration['paynet_base_ui_server_url'] ?: PaynetConfig::PAYNET_BASE_UI_SERVER_URL,
        ],
      ]
    ];

    $form['test_configs'] = [
      '#type'  => 'details',
      '#title' => $this->t('Test configs'),
      '#required' => TRUE,
      '#open'  => FALSE,
      'inline_holder' => [
        '#type' => 'container',
        '#attributes'  => [
          'class' => 'form--inline clearfix',
        ],
        'test_paynet_api_base_ui_url' => [
          '#type' => 'textfield',
          '#title' => $this->t('API base URL'),
          '#required' => TRUE,
          '#default_value' => $this->configuration['test_paynet_api_base_ui_url'] ?: PaynetConfig::TEST_PAYNET_BASE_API_URL,
        ],
        'test_paynet_base_ui_url' => [
          '#type' => 'textfield',
          '#title' => $this->t('Base UI URL'),
          '#required' => TRUE,
          '#default_value' => $this->configuration['test_paynet_base_ui_url'] ?: PaynetConfig::TEST_PAYNET_BASE_UI_URL,
        ],
        'test_paynet_base_ui_server_url' => [
          '#type' => 'textfield',
          '#title' => $this->t('Base UI server URL'),
          '#required' => TRUE,
          '#default_value' => $this->configuration['test_paynet_base_ui_server_url'] ?: PaynetConfig::TEST_PAYNET_BASE_UI_SERVER_URL,
        ],
      ]
    ];

    $form['id_partner'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Partner\'s ID'),
      '#description' => $this->t('Merchant code'),
      '#default_value' => $this->configuration['id_partner'] ?: PaynetConfig::MERCHANT_CODE,
      '#maxlength' => 64,
      '#size' => 64,
    ];

    $form['id_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User\'s ID'),
      '#description' => $this->t('Merchant user'),
      '#default_value' => $this->configuration['id_user'] ?: PaynetConfig::MERCHANT_USER,
      '#maxlength' => 64,
      '#size' => 64,
    ];

    $form['merchant_user_pass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Merchant user pass'),
      '#default_value' => $this->configuration['merchant_user_pass'] ?: PaynetConfig::MERCHANT_USER_PASS,
      '#maxlength' => 64,
      '#size' => 64,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      foreach ($values as $key => $value) {
        if (is_array($value)) {
          foreach ($value as $sub_key => $sub_array) {
            foreach ($sub_array as $s_key => $s_item) {
              $this->configuration[$s_key] = $s_item;
            }
          }
        }
        else {
          $this->configuration[$key] = $value;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    \Drupal::logger('Foo test')->info($request->getQueryString());

//1 – Registered
//2 – Customer Verified
//3 – Initialized to be Paid
//4 – Paid

//Code - Error code
//Message - Error description

//    /** @var PaynetCommerceService $api */
//    $api = \Drupal::service('commerce_paynetmd.panet_commerce_api');
//
//    $checkObj = $api->PaymentGet($order->id());
//    if($checkObj->IsOk()) {
//      if($checkObj->Data[0]['Status'] === 4) {
//        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
//        $payment = $payment_storage->create([
//          'state' => 'authorization',
//          'amount' => $order->getBalance(),
//          'payment_gateway' => $this->entityId,
//          'order_id' => $order->id(),
//          'remote_id' => '',
//          'remote_state' => $request->query->get('EventType'),
//        ]);
//        $payment->save();
//
//        $message = $this->t('PayNet payment gateway confirmed your payment.');
//        \Drupal::messenger()->addStatus($message);
//      }
//    }
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    parent::onNotify($request);
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    parent::onCancel($order, $request);
  }

}
