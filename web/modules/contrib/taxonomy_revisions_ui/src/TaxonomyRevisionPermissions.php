<?php

namespace Drupal\taxonomy_revisions_ui;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for each taxonomy_term revision type.
 */
class TaxonomyRevisionPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * TaxonomyRevisionPermissions constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of vocabulary revision type permissions.
   *
   * @return array
   *   The Vocabulary permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function taxonomyRevisionTypePermissions(): array {
    $perms = [];
    // Generate taxonomy revision permissions for all vocabulary.
    $taxonomy_vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
    /** @var \Drupal\taxonomy\VocabularyInterface $vocabulary */
    foreach ($taxonomy_vocabulary as $vocabulary) {
      $perms += $this->buildPermissions($vocabulary);
    }
    return $perms;
  }

  /**
   * Returns a list of revision permissions for a given vocabulary.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   *   The Vocabulary types.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(VocabularyInterface $vocabulary): array {
    $type_id = $vocabulary->id();
    $type_params = ['%type_name' => $vocabulary->label()];

    return [
      "view $type_id revisions" => [
        'title' => $this->t('%type_name: View terms revisions', $type_params),
        'description' => $this->t('To view a revision, you also need "access content" permission.'),
      ],
      "revert $type_id revisions" => [
        'title' => $this->t('%type_name: Revert terms revisions', $type_params),
        'description' => $this->t('To revert a revision, you also need permission to edit the taxonomy term.'),
      ],
      "delete $type_id revisions" => [
        'title' => $this->t('%type_name: Delete terms revisions', $type_params),
        'description' => $this->t('To delete a revision, you also need permission to delete the taxonomy term.'),
      ],
    ];
  }

}
