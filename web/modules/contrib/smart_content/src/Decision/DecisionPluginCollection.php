<?php

namespace Drupal\smart_content\Decision;

use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Confirms set plugins are of type DecisionInterface.
 */
class DecisionPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * Stores an initialized plugin.
   *
   * @param string $instance_id
   *   The ID of the plugin instance being stored.
   * @param mixed $decision
   *   An instantiated Condition plugin.
   */
  public function set($instance_id, $decision) {
    if (!$decision instanceof DecisionInterface) {
      throw new \InvalidArgumentException('$decision must be instance of DecisionInterface');
    }
    parent::set($instance_id, $decision);
  }

}
