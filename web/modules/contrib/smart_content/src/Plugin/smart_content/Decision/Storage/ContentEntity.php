<?php

namespace Drupal\smart_content\Plugin\smart_content\Decision\Storage;

use Drupal\Core\Entity\RevisionableInterface;
use Drupal\smart_content\Decision\Storage\DecisionStorageEntityBase;
use Drupal\smart_content\Decision\Storage\RevisionableParentEntityUsageInterface;
use Drupal\smart_content\Entity\DecisionContent;
use Drupal\smart_content\Entity\DecisionEntityInterface;

/**
 * Provides a 'content_entity' ReactionSetStorage.
 *
 * @SmartDecisionStorage(
 *  id = "content_entity",
 *  label = @Translation("Content Entity"),
 * )
 */
class ContentEntity extends DecisionStorageEntityBase implements RevisionableParentEntityUsageInterface {

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = [] + parent::getConfiguration();
    if ($entity = $this->getEntity()) {
      $configuration['id'] = $this->getEntity()->id();
      $configuration['vid'] = $this->getEntity()->getRevisionId();
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'id' => NULL,
      'vid' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function loadDecisionFromToken($token) {
    $query = \Drupal::database()->select('decision_content_token', 'd');
    $query->condition('token', $token);
    $query->addField('d', 'id');
    $query->addField('d', 'vid');
    $query->addField('d', 'langcode');
    $result = $query->execute()->fetchAssoc();
    if (!empty($result)) {
      // We load the entity, which subsequently loads the decision.
      $this->loadEntityFromConfig($result);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->deleteTokens();
    $this->deleteUsage();
    if ($entity = $this->getEntity()) {
      $entity->delete();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function registerToken() {
    $entity = $this->getEntity();
    \Drupal::database()->insert('decision_content_token')
      ->fields([
        'id' => $entity->id(),
        'vid' => $entity->getRevisionId(),
        'langcode' => $entity->language()->getId(),
        'token' => $entity->getDecision()->getToken(),
      ])->execute();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTokens() {
    if ($entity = $this->getEntity()) {
      \Drupal::database()->delete('decision_content_token')
        ->condition('id', $entity->id())
        ->execute();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addUsage(RevisionableInterface $parent_entity) {
    \Drupal::database()->insert('decision_content_usage')
      ->fields([
        'parent_entity_type' => $parent_entity->getEntityTypeId(),
        'parent_entity_id' => $parent_entity->id(),
        'parent_entity_vid' => $parent_entity->getRevisionId(),
        'decision_entity_id' => $this->getEntity()->id(),
        'decision_entity_vid' => $this->getEntity()->getRevisionId(),
      ])->execute();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsage() {
    $query = \Drupal::database()->select('decision_content_usage', 'd');
    $query->condition('decision_entity_id', $this->getEntity()->id());
    $query->condition('decision_entity_vid', $this->getEntity()
      ->getRevisionId());
    $query->addField('d', 'parent_entity_type');
    $query->addField('d', 'parent_entity_id');
    $query->addField('d', 'parent_entity_vid');
    $query->addField('d', 'decision_entity_id');
    $query->addField('d', 'decision_entity_vid');
    return $query->execute()->fetchAssoc();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteUsage() {
    if ($entity = $this->getEntity()) {
      \Drupal::database()->delete('decision_content_usage')
        ->condition('decision_entity_id', $entity->id())
        ->execute();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntityFromConfig($configuration) {
    if (!empty($configuration['vid'])) {
      $this->entity = \Drupal::entityTypeManager()
        ->getStorage('smart_content_decision_content')
        ->loadRevision($configuration['vid']);
    }
    elseif (!empty($configuration['id'])) {
      $this->entity = DecisionContent::load($configuration['id']);
    }

    if (!$this->entity instanceof DecisionEntityInterface) {
      $this->entity = DecisionContent::create();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function getUsageByParentEntity(RevisionableInterface $parent_entity) {
    $query = \Drupal::database()->select('decision_content_usage', 'd');
    $query->condition('parent_entity_type', $parent_entity->getEntityTypeId());
    $query->condition('parent_entity_id', $parent_entity->id());
    $query->addField('d', 'parent_entity_type');
    $query->addField('d', 'parent_entity_id');
    $query->addField('d', 'parent_entity_vid');
    $query->addField('d', 'decision_entity_id');
    $query->addField('d', 'decision_entity_vid');
    return $query->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public static function deleteByParent(RevisionableInterface $parent_entity) {
    $usage = static::getUsageByParentEntity($parent_entity);
    if ($usage) {
      $ids = array_unique(array_column($usage, 'decision_entity_id'));
      foreach ($ids as $id) {
        $configuration = [
          'plugin_id' => 'content_entity',
          'id' => $id,
        ];
        $storage = \Drupal::service('plugin.manager.smart_content.decision_storage')
          ->createInstance('content_entity', $configuration);
        $storage->delete();
      }
    }

  }

}
