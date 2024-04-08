<?php

namespace Drupal\smart_content_block;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A block plugin collection.
 */
class BlockPluginCollection extends DefaultLazyPluginCollection {

  /**
   * Adds an initialized plugin.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   An instantiated Block plugin.
   */
  public function add(BlockPluginInterface $block) {
    // Generate a unique instance id and add to collection.
    $instance_id = $this->generateUniquePluginId($block, (array) $this->getInstanceIds());
    parent::set($instance_id, $block);
  }

  /**
   * Stores an initialized plugin.
   *
   * @param string $instance_id
   *   The ID of the plugin instance being stored.
   * @param mixed $block
   *   An instantiated Block plugin.
   */
  public function set($instance_id, $block) {
    if (!$block instanceof BlockPluginInterface) {
      throw new \InvalidArgumentException('$block must be instance of BlockPluginInterface');
    }
    parent::set($instance_id, $block);
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
   * Map form values to instances.
   *
   * @param array $values
   *   Array of values.
   *
   * @return $this
   *   Return $this.
   */
  public function mapWeightValues(array $values) {
    foreach ($values as $block_id) {
      if (!$this->has($block_id)) {
        throw new \InvalidArgumentException('$block_id must be an existing instance.');
      }
    }
    $this->originalOrder = array_combine($values, $values);
    return $this;
  }

}
