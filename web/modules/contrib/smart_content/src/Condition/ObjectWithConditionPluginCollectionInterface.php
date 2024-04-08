<?php

namespace Drupal\smart_content\Condition;

use Drupal\Core\Plugin\ObjectWithPluginCollectionInterface;

/**
 * Object with ConditionPluginInterface.
 *
 * Todo: Use or remove this.
 *
 * @package Drupal\smart_content\Condition
 */
interface ObjectWithConditionPluginCollectionInterface extends ObjectWithPluginCollectionInterface {

  /**
   * Returns the plugin collection of conditions.
   *
   * @return \Drupal\smart_content\Condition\ConditionPluginCollection[]
   *   The conditions.
   */
  public function getConditions();

  /**
   * Gets the Condition by instance id.
   *
   * The instance id is typically the key from array of conditions.
   *
   * @param string $instance_id
   *   The instance ID of the condition to retrieve.
   *
   * @return \Drupal\smart_content\Condition\ConditionInterface
   *   The condition.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the expected ID does not exist.
   */
  public function getCondition($instance_id);

}
