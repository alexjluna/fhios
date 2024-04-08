<?php

namespace Drupal\smart_content\Condition;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A condition plugin collection for lazy load conditions.
 *
 * Provides unique id generation and weight mapping.
 *
 * @package Drupal\smart_content\Condition
 */
class ConditionPluginCollection extends DefaultLazyPluginCollection {

  /**
   * Adds an initialized plugin.
   *
   * @param \Drupal\smart_content\Condition\ConditionInterface $condition
   *   An instantiated Condition plugin.
   */
  public function add(ConditionInterface $condition) {
    // Generate a unique instance id and add to collection.
    $instance_id = $this->generateUniquePluginId($condition, (array) $this->getInstanceIds());
    parent::set($instance_id, $condition);
  }

  /**
   * Stores an initialized plugin.
   *
   * @param string $instance_id
   *   The ID of the plugin instance being stored.
   * @param mixed $condition
   *   An instantiated Condition plugin.
   */
  public function set($instance_id, $condition) {
    if (!$condition instanceof Conditioninterface) {
      throw new \InvalidArgumentException('$condition must be instance of ConditionInterface');
    }
    parent::set($instance_id, $condition);
  }

  /**
   * Generates a unique ID for plugins.
   *
   * @param mixed $plugin
   *   A plugin.
   * @param array $existing_ids
   *   An array of existing plugin ids.
   *
   * @return string
   *   An id unique from the existing passed ones.
   */
  public static function generateUniquePluginId($plugin, array $existing_ids) {
    $count = 1;
    $machine_default = $plugin->getPluginId();
    while (in_array($machine_default, $existing_ids)) {
      $machine_default = $plugin->getPluginId() . '_' . ++$count;
    }
    return $machine_default;
  }

  /**
   * {@inheritdoc}
   */
  public function sortHelper($aID, $bID) {
    $a_weight = $this->get($aID)->getWeight();
    $b_weight = $this->get($bID)->getWeight();
    if ($a_weight == $b_weight) {
      return 0;
    }

    return ($a_weight < $b_weight) ? -1 : 1;
  }

  /**
   * Set conditions based on key|value pair of condition ids and weight.
   *
   * @param array $values
   *   The condition weights.
   *
   * @return $this
   *   Return $this.
   */
  public function mapFormWeightValues(array $values) {
    foreach ($values as $instance_id => $value) {
      $this->get($instance_id)->setWeight((int) $value['weight']);
    }
    return $this;
  }

}
