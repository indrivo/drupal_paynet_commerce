<?php

namespace Drupal\commerce_paynetmd;

/**
 * Class PaynetConfig
 *  default values.
 *
 * @package Drupal\commerce_paynetmd
 */
class PaynetConfig {
  const PAYNET_BASE_API_URL =  'https://paynet.md:4446';
  const PAYNET_BASE_UI_URL =  "https://paynet.md/acquiring/setecom";
  const PAYNET_BASE_UI_SERVER_URL = "https://paynet.md/acquiring/getecom";

  const TEST_PAYNET_BASE_API_URL =  'https://test.paynet.md:4446';
  const TEST_PAYNET_BASE_UI_URL =  "https://test.paynet.md/acquiring/setecom";
  const TEST_PAYNET_BASE_UI_SERVER_URL = "https://test.paynet.md/acquiring/getecom";

  const MERCHANT_CODE = '896157';
  const MERCHANT_USER = '177892';
  const MERCHANT_USER_PASS = 'hit.The.top@01';
  const MERCHANT_MODE = FALSE;
}
