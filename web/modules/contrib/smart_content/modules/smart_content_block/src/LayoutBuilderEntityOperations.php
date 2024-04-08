<?php

namespace Drupal\smart_content_block;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\smart_content\Decision\Storage\DecisionStorageManager;
use Drupal\smart_content\Decision\Storage\RevisionableParentEntityUsageInterface;
use Drupal\smart_content\Plugin\smart_content\Decision\Storage\ConfigEntity;
use Drupal\smart_content\Plugin\smart_content\Decision\Storage\ContentEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to entity events related to Inline Blocks.
 *
 * @internal
 *   This is an internal utility class wrapping hook implementations.
 */
class LayoutBuilderEntityOperations implements ContainerInjectionInterface {

  use LayoutEntityHelperTrait;

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

    if (!$this->isLayoutCompatibleEntity($entity)) {
      return;
    }

    $components = $this->getSmartContentBlockComponents($entity);

    foreach ($components as $component) {
      $plugin = $component->getPlugin();
      $configuration = $plugin->getConfiguration();
      $decision_storage = $this->getDecisionStorageFromConfiguration($configuration);
      $is_content_valid = $entity instanceof ContentEntityInterface && $decision_storage instanceof ContentEntity;
      $is_config_valid = $entity instanceof ConfigEntityInterface && $decision_storage instanceof ConfigEntity;
      // We assume config entities belong to config, and content entities
      // belong to content.
      if ($is_content_valid || $is_config_valid) {
        $decision_storage->delete();
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
      $decision_storage = $decision_storage = $this->decisionStorageManager->createInstance($configuration['decision_storage']['plugin_id'], $configuration['decision_storage']);
    }
    return $decision_storage;
  }

  /**
   * Handles saving a parent entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity.
   */
  public function handlePreSave(EntityInterface $entity) {
    if (!$this->isLayoutCompatibleEntity($entity)) {
      return;
    }
    // This is not ideal, but currently the new revision flag is removed in post
    // save, so we have no way of telling if it's a new revision or not in
    // hook_update.  @see https://www.drupal.org/project/drupal/issues/3065633.
    $entity->updateSmartContentUsage = FALSE;

    $duplicate_blocks = $this->originalEntityUsesDefaultStorage($entity);
    $components = $this->getSmartContentBlockComponents($entity);
    foreach ($components as $component) {
      $plugin = $component->getPlugin();
      $configuration = $plugin->getConfiguration();

      $decision_storage = $this->getDecisionStorageFromConfiguration($configuration);
      unset($configuration['decision_storage_serialized']);

      if ($entity instanceof ContentEntityInterface) {
        // @todo: Confirm this isNew check avoids copying storage on default
        // layouts.
        if ($duplicate_blocks || $decision_storage->isNew()) {
          if ($decision_storage instanceof ConfigEntity) {
            $decision_storage = $this->convertConfigToContent($decision_storage);
          }
        }
        // We only save ContentEntity storage because we know it's unique to
        // this entity.
        if ($decision_storage instanceof ContentEntity) {

          if ($entity->isNewRevision()) {
            $decision_storage->setNewRevision();
            $entity->updateSmartContentUsage = TRUE;
          }

          $decision_storage->save();
          $configuration['decision_storage'] = $decision_storage->getConfiguration();

          $plugin->setConfiguration($configuration);
          $component->setConfiguration($plugin->getConfiguration());
        }

      }
      else {
        if ($entity instanceof ConfigEntityInterface) {
          $decision_storage->save();
          $configuration['decision_storage'] = $decision_storage->getConfiguration();

          $plugin->setConfiguration($configuration);
          $component->setConfiguration($plugin->getConfiguration());
        }
      }
    }
  }

  /**
   * Handles inserting a parent entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity.
   */
  public function handleInsert(EntityInterface $entity) {
    if (!$this->isLayoutCompatibleEntity($entity)) {
      return;
    }
    // @todo: remove?
  }

  /**
   * Handles updating a parent entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity.
   */
  public function handleUpdate(EntityInterface $entity) {
    if (!$this->isLayoutCompatibleEntity($entity)) {
      return;
    }
    if ($entity->updateSmartContentUsage) {
      $components = $this->getSmartContentBlockComponents($entity);
      foreach ($components as $component) {
        $plugin = $component->getPlugin();
        $configuration = $plugin->getConfiguration();
        $decision_storage = $this->getDecisionStorageFromConfiguration($configuration);
        if ($decision_storage instanceof RevisionableParentEntityUsageInterface) {
          $decision_storage->addUsage($entity);
        }
      }
    }

  }

  /**
   * Iterate through layout builder components and find decision blocks.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The layout builder entity.
   *
   * @return array
   *   An array of decision blocks.
   */
  protected function getSmartContentBlockComponents(EntityInterface $entity) {
    $components = [];
    foreach ($this->getEntitySections($entity) as $section) {
      foreach ($section->getComponents() as $component) {
        $plugin = $component->getPlugin();
        if ($plugin->getPluginId() == 'smart_content_decision_block') {
          $components[] = $component;
        }
      }
    }
    return $components;
  }

}
