<?php

namespace Drupal\smart_content\SegmentSetStorage;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the segment set storage plugin manager.
 */
class SegmentSetStorageManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * Constructs a new SegmentSetStorageManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/smart_content/SegmentSetStorage', $namespaces,
      $module_handler, 'Drupal\smart_content\SegmentSetStorage\SegmentSetStorageInterface', 'Drupal\smart_content\Annotation\SmartSegmentSetStorage');

    $this->alterInfo('smart_content_segment_set_storage_info');
    $this->setCacheBackend($cache_backend, 'smart_content_segment_set_storage_plugins');
  }

  /**
   * Build options array from defined segment storage.
   *
   * @param bool $include_custom
   *   Include 'inline' definition.
   *
   * @return array
   *   An array of options.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getFormOptions($include_custom = TRUE) {
    $options = [];
    foreach ($this->getDefinitions() as $id => $definition) {
      if ($definition['global']) {
        if (isset($definition['group'])) {
          $group_label = (string) $definition['group'];
          $options[$group_label][$id] = $definition['label'];
        }
        else {
          $options[$id] = $definition['label'];
        }
      }
    }
    // @todo: Can we allow inline to be extensible?
    if ($include_custom) {
      $definition = $this->getDefinition('inline');
      $options['inline'] = $definition['label'];
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
