<?php

namespace Drupal\commerce_paynetmd\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_paynetmd\PaynetCommerceService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PayNetResponseController.
 */
class PayNetResponseController extends ControllerBase {

  /**
   * Constructs a new OrderReassignForm object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   */
  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->order = $current_route_match->getParameter('commerce_order');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_route_match'));
  }

  public function successResponse() {
    $order_id = is_numeric(\Drupal::request()->query->get('id')) ? \Drupal::request()->query->get('id') : NULL;
    if ($order_id) {
      /** @var PaynetCommerceService $api */
      $api = \Drupal::service('commerce_paynetmd.panet_commerse_api');
      // $order = Order::load($order_id);

      $checkObj = $api->PaymentGet($order_id);
      $text = "<h3>-------------------  returning object  ----------------</h3>";
      if($checkObj->IsOk()) {
        $text .= "<h3>-------------------  main parameters  ----------------</h3>";
        $text .= " Status: ".$checkObj->Data[0]['Status'] ;
        if($checkObj->Data[0]['Status'] === 4) {
          $text .= "<br><b>Paynet payment gateway confirmed your payment !!!</b>";
        }
        $text .= "<br> Merchant invoice: ".$checkObj->Data[0]['Invoice'] ;
        $text .= "<br> Amount of payment: ".$checkObj->Data[0]['Amount'] ;
      }
    }
    return [
      '#type' => 'markup',
      '#markup' => Markup::create($text),
    ];
  }

  public function cancelResponse() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: cancelResponse')
    ];
  }

  public function callbackResponse() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: callbackResponse')
    ];
  }

}
