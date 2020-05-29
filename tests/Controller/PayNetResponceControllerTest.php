<?php

namespace Drupal\commerce_paynetmd\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the commerce_paynetmd module.
 */
class PayNetResponceControllerTest extends WebTestBase {


  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "commerce_paynetmd PayNetResponceController's controller functionality",
      'description' => 'Test Unit for module commerce_paynetmd and controller PayNetResponceController.',
      'group' => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests commerce_paynetmd functionality.
   */
  public function testPayNetResponceController() {
    // Check that the basic functions of module commerce_paynetmd.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
