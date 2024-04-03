<?php

namespace Drupal\Tests\taxonomy_revisions_ui\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\user\Entity\User;

/**
 * Tests taxonomy revisions UI.
 *
 * @group taxonomy_revisions_ui
 */
class TaxonomyRevisionsUiTest extends TaxonomyRevisionsTestBase {

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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User to test taxonomy revisions tab.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->vocabulary = $this->createVocabulary('test');
  }

  /**
   * Tests reverting a revision.
   */
  public function testRevert() {
    $taxonomy = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Test taxonomy term',
    ]);
    $taxonomy->save();
    $user = $this->drupalCreateUser([
      'administer taxonomy',
    ]);
    $this->createTaxonomyRevision($taxonomy);
    $this->assertRevisionsListStatusCode($user, $taxonomy, 200);
    $this->clickLink('Revert');
    $this->assertSession()->statusCodeEquals(200);
    $this->getSession()->getPage()->pressButton('Revert');
    $this->assertSession()->pageTextContains('Test taxonomy term has been reverted to the revision from');
  }

  /**
   * Logs in a user, visits Term's revisions list page and asserts status code.
   *
   * @param \Drupal\user\Entity\User $user
   *   User to log in.
   * @param \Drupal\Core\Entity\EntityInterface $taxonomy
   *   Taxonomy term from which to load revisions list.
   * @param int $expectedStatusCode
   *   Expected status code when visiting revisions list.
   */
  protected function assertRevisionsListStatusCode(User $user, EntityInterface $taxonomy, int $expectedStatusCode) {
    $this->drupalLogin($user);
    $this->drupalGet("/taxonomy/{$taxonomy->id()}/revisions");
    $this->assertSession()->statusCodeEquals($expectedStatusCode);
  }

}
