<?php

namespace Drupal\smart_content_block;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\smart_content\Decision\Storage\DecisionStorageManager;
use Drupal\smart_content\Decision\Storage\RevisionableParentEntityUsageInterface;
use Drupal\smart_content\Plugin\smart_content\Decision\Storage\ConfigEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to entity events related to Inline Blocks.
 *
 * TODO: Test with smart content block in default value?
 *
 * @internal
 *   This is an internal utility class wrapping hook implementations.
 */
class BlockFieldEntityOperations implements ContainerInjectionInterface {

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
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The parent entity.
   */
  public function handlePreDelete(ContentEntityInterface $entity) {
    if (!$block_fields = $this->getBlockFields($entity)) {
      return;
    }
    foreach ($block_fields as $definition) {
      foreach ($entity->get($definition->getName()) as $item) {
        $item_value = $item->getValue();
        $configuration = $item_value['settings'];
        if ($item_value['plugin_id'] == 'smart_content_decision_block') {
          $decision_storage = $this->getDecisionStorageFromConfiguration($configuration);
          $decision_storage->delete();
        }
      }
    }
  }

  /**
   * Convert config decision storage to content decision storage.
   *
   * @param \Drupal\smart_content\Plugin\smart_content\Decision\Storage\ConfigEntity $decision_storage
   *   The decision storage config plugin.
   *
   * @return \Drupal\smart_content\Plugin\smart_content\Decision\Storage\ContentEntity
   *   The decision storage content plugin.
   */
  protected function convertConfigToContent(ConfigEntity $decision_storage) {
    $decision = clone $decision_storage->getDecision();
    $decision_storage = $this->decisionStorageManager->createInstance('content_entity');
    $decision_storage->setDecision($decision);
    return $decision_storage;
  }

  /**
   * Gets the decision storage instance from configuration.
   *
   * @param array $configuration
   *   The configuration array.
   *
   * @return \Drupal\smart_content\Decision\Storage\DecisionStorageInterface
   *   The decision storage instance.
   */
  protected function getDecisionStorageFromConfiguration(array $configuration) {
    if (isset($configuration['decision_storage_serialized'])) {
      $decision_storage = unserialize($configuration['decision_storage_serialized']);
    }
    else {
      $decision_storage = $this->decisionStorageManager->createInstance($configuration['decision_storage']['plugin_id'], $configuration['decision_storage']);
    }
    return $decision_storage;
  }

  /**
   * Handles saving a parent entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The parent entity.
   */
  public function handlePreSave(ContentEntityInterface $entity) {

    if (!$block_fields = $this->getBlockFields($entity)) {
      return;
    }
    // This is not ideal, but currently the new revision flag is removed in post
    // save, so we have no way of telling if it's a new revision or not in
    // hook_update.  @see https://www.drupal.org/project/drupal/issues/3065633.
    $entity->updateSmartContentUsage = FALSE;
    foreach ($block_fields as $definition) {
      foreach ($entity->get($definition->getName()) as $item) {
        $item_value = $item->getValue();
        $configuration = $item_value['settings'];
        if ($item_value['plugin_id'] == 'smart_content_decision_block') {
          $decision_storage = $this->getDecisionStorageFromConfiguration($configuration);
          unset($configuration['decision_storage_serialized']);
          if ($decision_storage instanceof ConfigEntity) {
            $decision_storage = $this->convertConfigToContent($decision_storage);
          }

          if ($entity->isNewRevision()) {
            $decision_storage->setNewRevision();
            $entity->updateSmartContentUsage = TRUE;
          }
          $decision_storage->save();
          $configuration['decision_storage'] = $decision_storage->getConfiguration();
          $item_value['settings'] = $configuration;
          $item->setValue($item_value);
        }
      }
    }
  }

  /**
   * Handles inserting a parent entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The parent entity.
   */
  public function handleInsert(ContentEntityInterface $entity) {
    if (!$block_fields = $this->getBlockFields($entity)) {
      return;
    }
  }

  /**
   * Handles updating a parent entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The parent entity.
   */
  public function handleUpdate(ContentEntityInterface $entity) {

    if (!$block_fields = $this->getBlockFields($entity)) {
      return;
    }

    foreach ($block_fields as $definition) {
      foreach ($entity->get($definition->getName()) as $item) {
        $item_value = $item->getValue();
        $configuration = $item_value['settings'];
        if ($item_value['plugin_id'] == 'smart_content_decision_block') {
          $decision_storage = $this->getDecisionStorageFromConfiguration($configuration);
          if ($decision_storage instanceof RevisionableParentEntityUsageInterface) {
            $decision_storage->addUsage($entity);
          }
        }

      }
    }

  }

  /**
   * Get the block field definitions from entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return array
   *   Array of block field definitions.
   */
  protected function getBlockFields(ContentEntityInterface $entity) {
    $fields = [];
    foreach ($entity->getFieldDefinitions() as $definition) {
      if ($definition->getType() == 'block_field') {
        $fields[] = $definition;
      }
    }
    return $fields;
  }

}
