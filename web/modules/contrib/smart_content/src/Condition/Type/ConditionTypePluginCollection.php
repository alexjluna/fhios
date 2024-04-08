<?php

namespace Drupal\smart_content\Condition\Type;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\smart_content\Condition\ConditionInterface;

/**
 * A condition type plugin collection.
 *
 * @package Drupal\smart_content\Condition\Type
 */
class ConditionTypePluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The condition using this type.
   *
   * @var \Drupal\smart_content\Condition\ConditionInterface
   */
  protected $condition;

  /**
   * Constructs a new DefaultSingleLazyPluginCollection object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param \Drupal\smart_content\Condition\ConditionInterface $condition
   *   A condition plugin.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, ConditionInterface $condition) {
    $this->manager = $manager;
    $this->addInstanceId($instance_id, $configuration);
    $this->condition = $condition;
  }

  /**
   * {@inheritdoc}
   */
  public function &get($instance_id) {
    $condition_type = parent::get($instance_id);
    $condition_type->conditionInstance = $this->condition;
    return $condition_type;
  }

}
