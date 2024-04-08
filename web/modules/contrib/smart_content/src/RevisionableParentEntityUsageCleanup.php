<?php

namespace Drupal\smart_content;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\smart_content\Decision\Storage\DecisionStorageManager;
use Drupal\smart_content\Decision\Storage\RevisionableParentEntityUsageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to entity events related to Inline Blocks.
 *
 * @internal
 *   This is an internal utility class wrapping hook implementations.
 */
class RevisionableParentEntityUsageCleanup implements ContainerInjectionInterface {

  /**
   * The decision storage plugin manager.
   *
   * @var \Drupal\smart_content\Decision\Storage\DecisionStorageInterface
   */
  protected $decisionStorageManager;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\smart_content\Decision\Storage\DecisionStorageManager $decisionStorageManager
   *   The decision storage manager.
   */
  public function __construct(DecisionStorageManager $decisionStorageManager) {
    $this->decisionStorageManager = $decisionStorageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.smart_content.decision_storage')
    );
  }

  /**
   * Handles entity tracking on deleting a parent entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity.
   */
  public function handleDelete(EntityInterface $entity) {

    if (!$entity instanceof ContentEntityInterface) {
      return;
    }
    // Get all decision storage plugin definitions.
    $definitions = $this->decisionStorageManager->getDefinitions();
    foreach ($definitions as $definition) {
      if (in_array(RevisionableParentEntityUsageInterface::class, class_implements($definition['class']))) {
        $definition['class']::deleteByParent($entity);
      }
    }
  }

}
