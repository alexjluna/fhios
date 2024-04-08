<?php

namespace Drupal\smart_content\Condition;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\smart_content\Condition\Group\ConditionGroupManager;

/**
 * Provides the Smart condition plugin manager.
 */
class ConditionManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * The condition group plugin manager.
   *
   * @var \Drupal\smart_content\Condition\Group\ConditionGroupManager
   */
  protected $conditionGroupManager;

  /**
   * Constructor for SmartConditionManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\smart_content\Condition\Group\ConditionGroupManager $condition_group_manager
   *   The condition group plugin manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConditionGroupManager $condition_group_manager) {
    parent::__construct('Plugin/smart_content/Condition', $namespaces, $module_handler, 'Drupal\smart_content\Condition\ConditionInterface', 'Drupal\smart_content\Annotation\SmartCondition');
    $this->alterInfo('smart_content_info');
    $this->setCacheBackend($cache_backend, 'smart_content_plugins');
    $this->conditionGroupManager = $condition_group_manager;
  }

  /**
   * Helper method to return a list of conditions by group.
   *
   * @return array
   *   An array of condition options.
   */
  public function getFormOptions() {
    // todo: Add support for group and condition weights.
    $options = [];
    $condition_group_definitions = $this->conditionGroupManager->getDefinitions();
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if ($definition['group'] !== 'hidden') {
        if (isset($condition_group_definitions[$definition['group']])) {
          $label = $condition_group_definitions[$definition['group']]['label'];
          $options[$label->render()][$plugin_id] = $definition['label'];
        }
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'broken';
  }

}
