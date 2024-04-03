<?php

namespace Drupal\taxonomy_revisions_ui\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting taxonomy_term revision.
 *
 * @internal
 */
class TaxonomyRevisionDeleteForm extends ConfirmFormBase {

  /**
   * Taxonomy term revision.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $revision;

  /**
   * Taxonomy term storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $taxonomyStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new TaxonomyRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\RevisionableStorageInterface $taxonomy_storage
   *   The revisionable storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(RevisionableStorageInterface $taxonomy_storage, DateFormatterInterface $date_formatter) {
    $this->taxonomyStorage = $taxonomy_storage;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('taxonomy_term'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_term_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format(
        $this->revision->getRevisionCreationTime()
      ),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.taxonomy_term.version_history', [
      'taxonomy_term' => $this->revision->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $taxonomy_term_revision = NULL) {
    $this->revision = $taxonomy_term_revision;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->taxonomyStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('@type: deleted %title revision %revision.', [
      '@type' => $this->revision->bundle(),
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
    ]);
    $this->messenger()->addStatus(
      $this->t('Revision from %revision-date of %title has been deleted.',
      [
        '%revision-date' => $this->dateFormatter->format(
          $this->revision->getRevisionCreationTime()
        ),
        '%title' => $this->revision->label(),
      ]
    ));
    $form_state->setRedirect('entity.taxonomy_term.canonical', [
      'taxonomy_term' => $this->revision->id(),
    ]);

    $revisionCount = $this->taxonomyStorage->getQuery()
      ->allRevisions()
      ->condition('mid', $this->revision->id())
      ->accessCheck()
      ->count()
      ->execute();

    if ($revisionCount > 1) {
      $form_state->setRedirect('entity.taxonomy_term.version_history', [
        'taxonomy_term' => $this->revision->id(),
      ]);
    }
  }

}
