<?php

namespace Drupal\smart_content\Entity;

use Drupal\smart_content\Decision\DecisionInterface;

/**
 * Provides common interface of entities that store decision plugins.
 */
interface DecisionEntityInterface {

  /**
   * Get the decision plugin.
   *
   * @return \Drupal\smart_content\Decision\DecisionInterface
   *   The decision plugin.
   */
  public function getDecision();

  /**
   * Check if the decision plugin exists.
   *
   * @return bool
   *   Bool indicating if plugin exists.
   */
  public function hasDecision();

  /**
   * Set the decision plugin.
   *
   * @param \Drupal\smart_content\Decision\DecisionInterface $decision
   *   The decision plugin.
   *
   * @return $this
   *   Return $this.
   */
  public function setDecision(DecisionInterface $decision);

}
