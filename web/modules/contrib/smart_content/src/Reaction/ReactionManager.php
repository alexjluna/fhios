<?php

namespace Drupal\smart_content\Reaction;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Reaction plugin manager.
 */
class ReactionManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/smart_content/Reaction', $namespaces,
      $module_handler, 'Drupal\smart_content\Reaction\ReactionInterface', 'Drupal\smart_content\Annotation\SmartReaction');

    $this->alterInfo('smart_content_reaction_info');
    $this->setCacheBackend($cache_backend, 'smart_content_reaction_plugins');
  }

}
