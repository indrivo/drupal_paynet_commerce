<?php

namespace Drupal\commerce_paynetmd;

class PaynetRequest {
  public $ExternalDate;
  public $ExternalID;

  /**
   * Gets the currency code.
   *
   * @return integer
   *   The currency code.
   */
  public $Currency = '498';

  public $Merchant;

  /**
   * URL to the page when payment is approved.
   * @var string
   */
  public $LinkSuccess;

  /**
   * URL to the page when payment is pending or canceled.
   * @var string
   */
  public $LinkCancel;

  public $ExpiryDate;

  /**
   * @var string
   *  ru, ro, en
   */
  public $Lang;

  public $Service = [];
  public $Products = [];
  public $Customer = [];
  public $Amount;
}
