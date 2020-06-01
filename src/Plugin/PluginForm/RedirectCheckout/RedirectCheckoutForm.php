<?php

namespace Drupal\commerce_paynetmd\Plugin\PluginForm\RedirectCheckout;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_paynetmd\PaynetCommerceService;
use Drupal\commerce_price\Entity\Currency;
use Drupal\Core\Language\LanguageInterface;
use Drupal\commerce_paynetmd\PaynetRequest;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class RedirectCheckoutForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var PaymentInterface $payment */
    $payment = $this->entity;
    /** @var OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $redirect_method = $payment_gateway_plugin->getConfiguration()['redirect_method'];
    $remove_js = ($redirect_method == 'post_manual');
    /** @var OrderInterface $order */
    $order = $payment->getOrder();
    if (in_array($redirect_method, ['post', 'post_manual'])) {
      switch ($payment_gateway_plugin->getMode()) {
        case 'test':
          $redirect_url = $payment_gateway_plugin->getConfiguration()['test_paynet_base_ui_server_url'];
          break;

        case 'live':
          $redirect_url = $payment_gateway_plugin->getConfiguration()['paynet_base_ui_server_url'];
          break;

        default:
          $redirect_url = 'https://test.paynet.md/acquiring/getecom';
          break;
      }

      $redirect_method = 'post';
    }
    else {
      if ($order->getBillingProfile()->get('address')->family_name == 'FAIL') {
        throw new PaymentGatewayException('Could not get the redirect URL.');
      }
      $redirect_url = Url::fromRoute('commerce_payment_example.dummy_redirect_302', [], ['absolute' => TRUE])->toString();
    }

    /** @var PaynetCommerceService $api */
    $api = \Drupal::service('commerce_paynetmd.panet_commerce_api');
    $api->setPayment($payment);
    $prequest = new PaynetRequest();
    $prequest->ExternalID = $order->id();
    // @todo: currency code doesn't work if currency is not MDL (498).
    $prequest->Currency = Currency::load($order->getTotalPrice()->getCurrencyCode())->getNumericCode();
    // @todo: create these routs.
    $link_success = Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $prequest->ExternalID,
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
    $link_cancell = Url::fromRoute('commerce_payment.checkout.cancel', [
      'commerce_order' => $prequest->ExternalID,
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
    $prequest->LinkSuccess = $link_success; // "http://localhost:8080/psp/ok?id=" . $prequest->ExternalID;
    $prequest->LinkCancel =  $link_cancell; // "http://localhost:8080/psp/cancel?id=" . $prequest->ExternalID;
    $prequest->Lang = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();
    $prequest->Products = [];
    foreach ($order->getItems() as $product) {
      $prequest->Products[] = [
        'LineNo' => $product->id(),
        'Code' => '',
        'Barcode' => '',
        'Name' => $product->label(),
        'Description' => '',
        'Quantity' => (int) $product->getQuantity() * 100,
        'UnitPrice' => $product->getUnitPrice()->getNumber(),
      ];
    }
    $prequest->Service = [[
      'Name' => \Drupal::config('system.site')->get('name'),
      'Description'=> \Drupal::config('system.site')->get('slogan'),
      'Amount' => $prequest->Amount,
      'Products' => $prequest->Products,
    ]];
    $prequest->Customer = [
      'Code' => '',
      'Address' => '',
      'Name' => '',
    ];
    $paymentRegObj = $api->PaymentReg($prequest);
    $data = $paymentRegObj->Data;

    if ($data) {
      $form = $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
    }
    if ($remove_js) {
      // Disable the javascript that auto-clicks the Submit button.
      unset($form['#attached']['library']);
    }

    return $form;
  }

}
