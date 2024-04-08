<?php

namespace Drupal\smart_content\Tests;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides automated tests for the smart_content module.
 */
class ReactionControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "smart_content ReactionController's controller functionality",
      'description' => 'Test Unit for module smart_content and controller ReactionController.',
      'group' => 'Other',
    ];
  }

  /**
   * Tests smart_content functionality.
   */
  public function testReactionController() {
    // Check that the basic functions of module smart_content.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
