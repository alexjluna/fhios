<?php

namespace Drupal\smart_content\Reaction;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * Provides a plugin collection for reactions.
 */
class ReactionPluginCollection extends DefaultLazyPluginCollection {

  /**
   * Adds an initialized plugin.
   *
   * @param \Drupal\smart_content\Reaction\ReactionInterface $reaction
   *   An instantiated Reaction plugin.
   */
  public function add(ReactionInterface $reaction) {
    // Key reaction by segment_id.
    parent::set($reaction->getSegmentDependencyId(), $reaction);
  }

  /**
   * Stores an initialized plugin.
   *
   * @param string $instance_id
   *   The ID of the plugin instance being stored.
   * @param mixed $reaction
   *   The reaction plugin instance.
   */
  public function set($instance_id, $reaction) {
    if (!$reaction instanceof ReactionInterface) {
      throw new \InvalidArgumentException('$reaction must be instance of ReactionInterface');
    }
    if ($instance_id !== $reaction->getSegmentDependencyId()) {
      throw new \InvalidArgumentException('$instance_id must match $reaction->getSegmentDependency()');
    }
    parent::set($instance_id, $reaction);
  }

}
