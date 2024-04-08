<?php

namespace Drupal\smart_content\Decision;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\smart_content\AttachedJavaScriptInterface;
use Drupal\smart_content\Decision\Storage\DecisionStorageInterface;
use Drupal\smart_content\Reaction\ReactionInterface;
use Drupal\smart_content\SegmentSetStorage\SegmentSetStorageInterface;

/**
 * Defines an interface for ReactionSet storage plugins.
 */
interface DecisionInterface extends PluginInspectionInterface, AttachedJavaScriptInterface {

  /**
   * Returns the reactions of the ReactionSet.
   *
   * @return \Drupal\smart_content\Reaction\ReactionPluginCollection
   *   The reactions.
   */
  public function getReactions();

  /**
   * Gets the segment for a given UUID.
   *
   * @param string $id
   *   The ID of the reaction to retrieve.
   *
   * @return \Drupal\smart_content\Reaction\ReactionInterface
   *   The reaction.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the expected ID does not exist.
   */
  public function getReaction($id);

  /**
   * Check if reaction exists.
   *
   * @param string $id
   *   The ID of the reaction to retrieve.
   *
   * @return bool
   *   If reaction exists.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the expected ID does not exist.
   */
  public function hasReaction($id);

  /**
   * Helper method to set a reaction.
   *
   * @param string $instance_id
   *   The reaction id, usually the segment Uuid.
   * @param \Drupal\smart_content\Reaction\ReactionInterface $reaction
   *   The reaction.
   *
   * @return $this
   */
  public function setReaction($instance_id, ReactionInterface $reaction);

  /**
   * Removes a given reaction from the ReactionSet.
   *
   * @param string $id
   *   The ID of the reaction to remove.
   *
   * @return $this
   */
  public function removeReaction($id);

  /**
   * Appends a reaction to the end of the reaction array.
   *
   * @param \Drupal\smart_content\Reaction\ReactionInterface $reaction
   *   The reaction.
   *
   * @return \Drupal\smart_content\Decision\DecisionInterface
   *   Return $this.
   */
  public function appendReaction(ReactionInterface $reaction);

  /**
   * Gets the plugin collections used by this object.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection[]
   *   An array of plugin collections, keyed by the property name they use to
   *   store their configuration.
   */
  public function getPluginCollections();

  /**
   * Sets the segment set storage plugin.
   *
   * @param \Drupal\smart_content\SegmentSetStorage\SegmentSetStorageInterface $segment_set_storage
   *   The segment_set_storage plugin.
   *
   * @return \Drupal\smart_content\Decision\DecisionInterface
   *   Return $this.
   */
  public function setSegmentSetStorage(SegmentSetStorageInterface $segment_set_storage);

  /**
   * Gets the segment set storage plugin.
   *
   * @return \Drupal\smart_content\SegmentSetStorage\SegmentSetStorageInterface
   *   The segment set storage plugin.
   */
  public function getSegmentSetStorage();

  /**
   * Retrieve the AjaxResponse for a Reaction.
   *
   * @param \Drupal\smart_content\Reaction\ReactionInterface $reaction
   *   The Reaction.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function getResponse(ReactionInterface $reaction);

  /**
   * Attaches javascript settings and libraries.
   *
   * @param array $element
   *   A render array to attach settings to.
   *
   * @return array
   *   The render array with settings attached.
   */
  public function attach(array $element);

  /**
   * Refreshes the token with a new Uuid.
   *
   * @return $this
   *   Return $this.
   */
  public function refreshToken();

  /**
   * Gets the token.
   *
   * @return string
   *   The token.
   */
  public function getToken();

  /**
   * Check if token exists.
   *
   * @return bool
   *   If token exists.
   */
  public function hasToken();

  /**
   * Sets the storage id from the decision storage plugin.
   *
   * @param \Drupal\smart_content\Decision\Storage\DecisionStorageInterface $decision_storage
   *   The decision storage plugin.
   *
   * @return $this
   *   Return $this.
   */
  public function setStorage(DecisionStorageInterface $decision_storage);

  /**
   * Returns the id of the decision storage plugin.
   *
   * @return string
   *   The decision storage plugin id.
   */
  public function getStorageId();

}
