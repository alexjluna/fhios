<?php

namespace Drupal\Tests\taxonomy_revisions_ui\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides base class with common methods to use in tests.
 */
abstract class TaxonomyRevisionsTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The taxonomy storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $taxonomyStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $this->taxonomyStorage = $entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * Creates a new user and logs in.
   *
   * @param array $permissions
   *   List of permissions to assign to the user.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createUserWithPermissionsAndLogin(array $permissions) {
    $user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($user);
  }

  /**
   * Creates a new revision for a given taxonomy term.
   *
   * @param \Drupal\Core\Entity\EntityInterface $taxonomy_term
   *   Taxonomy term to create revision in.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A taxonomy_term object with up to date revision information.
   */
  protected function createTaxonomyRevision(EntityInterface $taxonomy_term) {
    $taxonomy_term->setName($this->randomMachineName());
    $taxonomy_term->setNewRevision();
    $taxonomy_term->save();

    return $taxonomy_term;
  }

  /**
   * Loads the oldest revision from taxonomy_term.
   *
   * @param \Drupal\Core\Entity\EntityInterface $taxonomy_term
   *   The taxonomy_term from which to load revision.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The revision entity.
   */
  protected function loadOldestRevisionId(EntityInterface $taxonomy_term) {
    $result = $this->taxonomyStorage->getQuery()
      ->allRevisions()
      ->condition('tid', $taxonomy_term->id())
      ->sort('vid')
      ->accessCheck(FALSE)
      ->range(NULL, 1)
      ->execute();

    $revisionId = array_keys($result) ? array_keys($result)[0] : NULL;

    return $this->taxonomyStorage->loadRevision($revisionId);
  }

}
