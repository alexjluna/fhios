<?php

namespace Drupal\smart_content_block;

use Drupal\block\Entity\Block;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\smart_content\Decision\Storage\DecisionStorageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to entity events related to Inline Blocks.
 *
 * @internal
 *   This is an internal utility class wrapping hook implementations.
 */
class ConfigBlockEntityOperations implements ContainerInjectionInterface {

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
  public function handlePreDelete(EntityInterface $entity) {

    if (!$this->isConfigBlockEntity($entity)) {
      return;
    }

    $configuration = $entity->get('settings');
    $decision_storage_configuration = $configuration['decision_storage'];
    $decision_storage = $this->decisionStorageManager->createInstance($decision_storage_configuration['plugin_id'], $decision_storage_configuration);
    $decision_storage->delete();
  }

  /**
   * Handles saving a parent entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity.
   */
  public function handlePreSave(EntityInterface $entity) {
    if (!$this->isConfigBlockEntity($entity)) {
      return;
    }
    $configuration = $entity->get('settings');
    if (isset($configuration['decision_storage_serialized'])) {
      $decision_storage = unserialize($configuration['decision_storage_serialized']);
      $decision_storage->save();
      $configuration['decision_storage'] = $decision_storage->getConfiguration();
      unset($configuration['decision_storage_serialized']);
      $entity->set('settings', $configuration);
    }
  }

  /**
   * Helper function to check if entity is type decision block.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   Is a decision block.
   */
  public function isConfigBlockEntity(EntityInterface $entity) {
    return ($entity instanceof Block && $entity->getPluginId() == 'smart_content_decision_block');
  }

}
