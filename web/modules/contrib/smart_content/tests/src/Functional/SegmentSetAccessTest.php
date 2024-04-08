<?php

namespace Drupal\Tests\smart_content\Functional;

use Drupal\Core\Url;
use Drupal\smart_content\Entity\SegmentSetConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Contains functional test cases for access control to smart segments.
 *
 * @group smart_content
 */
class SegmentSetAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   *
   * @todo Fix the schema definitions in a separate issue.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['smart_content'];

  /**
   * A user that is not authorized to administer smart content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $unauthorizedUser;

  /**
   * A user that is authorized to administer smart content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $authorizedUser;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   If user creation fails.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->unauthorizedUser = $this->drupalCreateUser([]);
    $this->authorizedUser = $this->drupalCreateUser(['administer smart content']);

    // Create a dummy segment set.
    SegmentSetConfig::create([
      'id' => 1,
      'label' => '1',
      'settings' => [],
    ])->save();
  }

  /**
   * Test case for segment set access control mechanisms.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAccess() {
    $assert = $this->assertSession();

    $entity_routes = [
      Url::fromRoute('entity.smart_content_segment_set.collection'),
      Url::fromRoute('entity.smart_content_segment_set.add_form'),
      Url::fromRoute('entity.smart_content_segment_set.canonical', ['smart_content_segment_set' => '1']),
      Url::fromRoute('entity.smart_content_segment_set.edit_form', ['smart_content_segment_set' => '1']),
      Url::fromRoute('entity.smart_content_segment_set.delete_form', ['smart_content_segment_set' => '1']),
    ];

    // Ensure that anonymous folks do not have access to things.
    foreach ($entity_routes as $entity_route) {
      $this->drupalGet($entity_route);
      $assert->statusCodeEquals(403);
    }

    // Ensure that unprivileged folks do not have access to things.
    $this->drupalLogin($this->unauthorizedUser);
    foreach ($entity_routes as $entity_route) {
      $this->drupalGet($entity_route);
      $assert->statusCodeEquals(403);
    }

    // Ensure that privileged folks do have access to things.
    $this->drupalLogin($this->authorizedUser);
    foreach ($entity_routes as $entity_route) {
      $this->drupalGet($entity_route);
      $assert->statusCodeEquals(200);
    }
  }

}
