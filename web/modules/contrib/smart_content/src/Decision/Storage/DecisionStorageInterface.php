<?php

namespace Drupal\smart_content\Decision\Storage;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\smart_content\Decision\DecisionInterface;

/**
 * Defines an interface for decision storage plugins.
 */
interface DecisionStorageInterface extends PluginInspectionInterface, ConfigurableInterface {

  /**
   * Get the decision plugin.
   *
   * @return \Drupal\smart_content\Decision\DecisionInterface
   *   The decision plugin.
   */
  public function getDecision();

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

  /**
   * Check if decision plugin is set.
   *
   * @return bool
   *   If decision exists.
   */
  public function hasDecision();

  /**
   * Save the storage instance.
   *
   * @return $this
   *   Return $this.
   */
  public function save();

  /**
   * Create new revision if supported.
   *
   * @param bool $value
   *   True/False.
   */
  public function setNewRevision($value = TRUE);

  /**
   * Delete the storage instance.
   *
   * @return $this
   *   Return $this.
   */
  public function delete();

  /**
   * Check if storage instance is new.
   *
   * @return bool
   *   Return is/is not new.
   */
  public function isNew();

  /**
   * Deletes tokens for instance.
   *
   * @return $this
   *   Return $this.
   */
  public function deleteTokens();

  /**
   * Store a token for a given instance.
   *
   * @return $this
   *   Return $this.
   */
  public function registerToken();

  /**
   * Load a decision from a token.
   *
   * @param string $token
   *   The uuid token.
   */
  public function loadDecisionFromToken($token);

}
