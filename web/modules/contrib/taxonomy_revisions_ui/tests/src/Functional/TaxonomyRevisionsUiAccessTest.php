<?php

namespace Drupal\Tests\taxonomy_revisions_ui\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests taxonomy revision pemissions.
 *
 * @group taxonomy_revisions_ui
 */
class TaxonomyRevisionsUiAccessTest extends TaxonomyRevisionsTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'taxonomy',
    'taxonomy_revisions_ui',
  ];

  /**
   * Tests access of revision operations.
   */
  public function testOperationsAccess() {
    // Authenticated role has "access content" permission by default,
    // so remove it to properly test permissions.
    /** @var \Drupal\user\RoleInterface $authenticatedRole */
    $authenticatedRole = \Drupal::entityTypeManager()->getStorage('user_role')->load('authenticated');
    $authenticatedRole->revokePermission('access content');
    $authenticatedRole->save();

    // Set up two taxonomy vocabulary.
    $vocabulary1 = $this->createVocabulary('test');
    $vocabulary2 = $this->createVocabulary('test');

    // Create a taxonomy of first type and load the oldest revision.
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = Term::create([
      'vid' => $vocabulary1->id(),
      'name' => 'Test taxomomy term 2',
    ]);
    $term->save();
    $this->createTaxonomyRevision($term);
    $termRevision = $this->loadOldestRevisionId($term);

    // Create a taxomomy_term of second type and load the oldest revision.
    /** @var \Drupal\taxonomy\TermInterface $term2 */
    $term2 = Term::create([
      'vid' => $vocabulary2->id(),
      'name' => 'Test taxomomy term 2',
    ]);
    $term2->save();
    $this->createTaxonomyRevision($term2);
    $term2Revision = $this->loadOldestRevisionId($term2);

    // Test that the user can view, revert or delete any revision with
    // administer taxomomy permission.
    $this->createUserWithPermissionsAndLogin([
      'administer taxonomy',
    ]);
    $this->assertRevisionViewStatusCode($termRevision, 200);
    $this->assertRevisionRevertStatusCode($termRevision, 200);
    $this->assertRevisionDeleteStatusCode($termRevision, 200);

    $this->assertRevisionViewStatusCode($term2Revision, 200);
    $this->assertRevisionRevertStatusCode($term2Revision, 200);
    $this->assertRevisionDeleteStatusCode($term2Revision, 200);

    // Test that the user can only view all revisions but not revert or delete.
    $this->createUserWithPermissionsAndLogin([
      'access content',
      'view all taxonomy revisions',
    ]);

    $this->assertRevisionViewStatusCode($termRevision, 200);
    $this->assertRevisionRevertStatusCode($termRevision, 403);
    $this->assertRevisionDeleteStatusCode($termRevision, 403);

    $this->assertRevisionViewStatusCode($term2Revision, 200);
    $this->assertRevisionRevertStatusCode($term2Revision, 403);
    $this->assertRevisionDeleteStatusCode($term2Revision, 403);

    // Test that the user can only revert all revisions but not view or delete.
    $this->createUserWithPermissionsAndLogin([
      "edit terms in {$termRevision->bundle()}",
      'revert all taxonomy revisions',
    ]);

    $this->assertRevisionViewStatusCode($termRevision, 403);
    $this->assertRevisionRevertStatusCode($termRevision, 200);
    $this->assertRevisionDeleteStatusCode($termRevision, 403);

    // Test that the user can only revert all revisions but not view or delete.
    $this->createUserWithPermissionsAndLogin([
      "edit terms in {$term2Revision->bundle()}",
      'revert all taxonomy revisions',
    ]);

    $this->assertRevisionViewStatusCode($term2Revision, 403);
    $this->assertRevisionRevertStatusCode($term2Revision, 200);
    $this->assertRevisionDeleteStatusCode($term2Revision, 403);

    // Test that the user can only delete all revisions but not view or revert.
    $this->createUserWithPermissionsAndLogin([
      "delete terms in {$termRevision->bundle()}",
      'delete all taxonomy revisions',
    ]);

    $this->assertRevisionViewStatusCode($termRevision, 403);
    $this->assertRevisionRevertStatusCode($termRevision, 403);
    $this->assertRevisionDeleteStatusCode($termRevision, 200);

    // Test that the user can only delete all revisions but not view or revert.
    $this->createUserWithPermissionsAndLogin([
      "delete terms in {$term2Revision->bundle()}",
      'delete all taxonomy revisions',
    ]);

    $this->assertRevisionViewStatusCode($term2Revision, 403);
    $this->assertRevisionRevertStatusCode($term2Revision, 403);
    $this->assertRevisionDeleteStatusCode($term2Revision, 200);

    // Test individual permissions on taxonomy_term type.
    $this->createUserWithPermissionsAndLogin([
      'access content',
      "view {$termRevision->bundle()} revisions",
    ]);

    $this->assertRevisionViewStatusCode($termRevision, 200);
    $this->assertRevisionRevertStatusCode($termRevision, 403);
    $this->assertRevisionDeleteStatusCode($termRevision, 403);
    $this->assertRevisionViewStatusCode($term2Revision, 403);
    $this->assertRevisionRevertStatusCode($term2Revision, 403);
    $this->assertRevisionDeleteStatusCode($term2Revision, 403);

    $this->createUserWithPermissionsAndLogin([
      "edit terms in {$termRevision->bundle()}",
      "revert {$termRevision->bundle()} revisions",
    ]);

    $this->assertRevisionViewStatusCode($termRevision, 403);
    $this->assertRevisionRevertStatusCode($termRevision, 200);
    $this->assertRevisionDeleteStatusCode($termRevision, 403);
    $this->assertRevisionViewStatusCode($term2Revision, 403);
    $this->assertRevisionRevertStatusCode($term2Revision, 403);
    $this->assertRevisionDeleteStatusCode($term2Revision, 403);

    $this->createUserWithPermissionsAndLogin([
      "delete terms in {$termRevision->bundle()}",
      "delete {$termRevision->bundle()} revisions",
    ]);

    $this->assertRevisionViewStatusCode($termRevision, 403);
    $this->assertRevisionRevertStatusCode($termRevision, 403);
    $this->assertRevisionDeleteStatusCode($termRevision, 200);
    $this->assertRevisionViewStatusCode($term2Revision, 403);
    $this->assertRevisionRevertStatusCode($term2Revision, 403);
    $this->assertRevisionDeleteStatusCode($term2Revision, 403);

    // Test that view taxonomy_term revision does not work without
    // access content permission.
    $this->createUserWithPermissionsAndLogin([
      "view {$termRevision->bundle()} revisions",
    ]);
    $this->assertRevisionViewStatusCode($termRevision, 403);

    // Test that revert taxonomy_term revision does not work without
    // edit taxonomy_term permission.
    $this->createUserWithPermissionsAndLogin([
      "revert {$termRevision->bundle()} revisions",
    ]);
    $this->assertRevisionRevertStatusCode($termRevision, 403);

    // Test that delete taxonomy_term revision does not work without
    // delete taxonomy_term permission.
    $this->createUserWithPermissionsAndLogin([
      "delete {$termRevision->bundle()} revisions",
    ]);
    $this->assertRevisionDeleteStatusCode($termRevision, 403);

    // Test that revert taxonomy_term revision does not work without
    // edit taxonomy_term revisions permission.
    $this->createUserWithPermissionsAndLogin([
      "edit terms in {$termRevision->bundle()}",
    ]);
    $this->assertRevisionRevertStatusCode($termRevision, 403);

    // Test that delete taxonomy_term revision does not work without
    // delete taxonomy_term revisions permission.
    $this->createUserWithPermissionsAndLogin([
      "delete terms in {$termRevision->bundle()}",
    ]);
    $this->assertRevisionDeleteStatusCode($termRevision, 403);
  }

  /**
   * Visits taxonomy_term revision view page and asserts status code.
   *
   * @param \Drupal\Core\Entity\EntityInterface $termRevision
   *   The taxonomy_term entity.
   * @param int $expectedStatusCode
   *   Expected status code when visiting revision view page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertRevisionViewStatusCode(EntityInterface $termRevision, $expectedStatusCode) {
    $this->drupalGet("/taxonomy/{$termRevision->id()}/revisions/{$termRevision->getRevisionId()}/view");
    $this->assertSession()->statusCodeEquals($expectedStatusCode);
  }

  /**
   * Visits taxonomy_term revision revert page and asserts status code.
   *
   * @param \Drupal\Core\Entity\EntityInterface $termRevision
   *   The taxonomy_term entity.
   * @param int $expectedStatusCode
   *   Expected status code when visiting revision revert page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertRevisionRevertStatusCode(EntityInterface $termRevision, $expectedStatusCode) {
    $this->drupalGet("/taxonomy/{$termRevision->id()}/revisions/{$termRevision->getRevisionId()}/revert");
    $this->assertSession()->statusCodeEquals($expectedStatusCode);
  }

  /**
   * Visits taxonomy_term revision delete page and asserts status code.
   *
   * @param \Drupal\Core\Entity\EntityInterface $termRevision
   *   The taxonomy_term entity.
   * @param int $expectedStatusCode
   *   Expected status code when visiting revisions list.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertRevisionDeleteStatusCode(EntityInterface $termRevision, $expectedStatusCode) {
    $this->drupalGet("/taxonomy/{$termRevision->id()}/revisions/{$termRevision->getRevisionId()}/delete");
    $this->assertSession()->statusCodeEquals($expectedStatusCode);
  }

}
