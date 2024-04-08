<?php

namespace Drupal\smart_content\Decision\Storage;

use Drupal\Core\Entity\RevisionableInterface;
use Drupal\smart_content\Decision\DecisionInterface;
use Drupal\smart_content\Entity\DecisionEntityInterface;

/**
 * Base class for ReactionSet storage plugins.
 */
abstract class DecisionStorageEntityBase extends DecisionStorageBase implements DecisionStorageEntityInterface {

  /**
   * The decision entity.
   *
   * @var \Drupal\smart_content\Entity\DecisionEntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->loadEntityFromConfig($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(DecisionEntityInterface $entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDecision() {
    return $this->getEntity()->hasDecision();
  }

  /**
   * {@inheritdoc}
   */
  public function setDecision(DecisionInterface $decision) {
    $this->getEntity()->setDecision($decision);
    return parent::setDecision($decision);
  }

  /**
   * {@inheritdoc}
   */
  public function getDecision() {
    $this->decision = $this->getEntity()->getDecision();
    return $this->decision;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $is_new = $this->getEntity()->isNew();
    $is_revisionable = ($this->getEntity() instanceof RevisionableInterface && $this->getEntity()->isNewRevision());
    if ($is_revisionable) {
      $this->getDecision()->refreshToken();
    }
    $this->getDecision()->setStorage($this);
    $this->getEntity()->save();
    if ($is_new || $is_revisionable) {
      $this->registerToken();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($value = TRUE) {
    if ($this->getEntity() instanceof RevisionableInterface) {
      $this->getEntity()->setNewRevision($value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->getEntity()->delete();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return $this->entity->isNew();
  }

}
