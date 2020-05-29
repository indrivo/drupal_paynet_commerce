<?php

namespace Drupal\commerce_paynetmd;

use Drupal\commerce_paynetmd\PaynetCode;

class PaynetResult {
  public $Code;
  public $Message;
  public $Data;
  public function IsOk() {
    return $this->Code === PaynetCode::SUCCESS;
  }
}
