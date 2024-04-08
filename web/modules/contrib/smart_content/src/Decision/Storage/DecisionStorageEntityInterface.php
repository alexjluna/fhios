<?php

namespace Drupal\smart_content\Decision\Storage;

use Drupal\smart_content\Entity\DecisionEntityInterface;

/**
 * Provides an interface for decision storage with entity based storage.
 */
interface DecisionStorageEntityInterface {

  /**
   * Loads the entity from the storage configuration.
   *
   * @param array $configuration
   *   The storage configuration.
   *
   * @return $this
   *   Return $this.
   */
  public function loadEntityFromConfig(array $configuration);

  /**
   * Set the entity.
   *
   * @param \Drupal\smart_content\Entity\DecisionEntityInterface $entity
   *   The decision entity.
   *
   * @return $this
   *   Return $this.
   */
  public function setEntity(DecisionEntityInterface $entity);

  /**
   * Get the entity.
   *
   * @return \Drupal\smart_content\Entity\DecisionEntityInterface
   *   The decision entity.
   */
  public function getEntity();

}
