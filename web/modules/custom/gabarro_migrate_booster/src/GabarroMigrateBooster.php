<?php

namespace Drupal\gabarro_migrate_booster;

use Consolidation\AnnotatedCommand\AnnotationData;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Provides methods and utilities to enhance and manage migration processes.
 *
 * This class contains methods that enable or disable the migration booster,
 * reset implementation caches, and more.
 * for optimizing migrations, especially when using Drush.
 */
class GabarroMigrateBooster {

  /**
   * Indicates if altering is active.
   *
   * @var bool
   */
  protected static $alterActive;

  /**
   * Configurations for the migration booster.
   *
   * @var array
   */
  protected static $config;

  /**
   * Cache ID.
   *
   * @var string
   */
  const CID = 'gabarro_migrate_booster_enabled';

  /**
   * Initializes the drush command decides to enable or disable the booster.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input interface instance.
   * @param \Consolidation\AnnotatedCommand\AnnotationData $annotationData
   *   The annotation data.
   */
  public static function bootDrush(InputInterface $input, AnnotationData $annotationData) {
    if (in_array($annotationData['command'], static::getConfig('commands'))) {
      static::enable();
    }
    else {
      static::disable();
    }
  }

  /**
   * Disables the booster during Drupal and Drupal console initialization.
   */
  public static function bootDrupal() {
    static::disable();
  }

  /**
   * Enables the migration booster.
   *
   * It also resets the implementation cache and sets $alterActive.
   */
  public static function enable() {
    static::$alterActive = TRUE;
    static::reset();
  }

  /**
   * Disables the migration booster.
   *
   * This also involves resetting the implementation cache.
   */
  public static function disable() {
    static::reset();
  }

  /**
   * Resets the implementations cache.
   */
  public static function reset() {
    $module_handler = \Drupal::moduleHandler();
    $module_handler->resetImplementations();
  }

  /**
   * Implements hook_module_implementation_alter() to disable specific hooks.
   *
   * @param array $implementations
   *   The implementations to be altered.
   * @param string $hook
   *   The hook being invoked.
   *
   * @return void
   *   No return value.
   */
  public static function alter(array &$implementations, $hook) {
    if (!static::$alterActive) {
      return;
    }
    if (!$implementations) {
      return;
    }
    $hooks = static::getConfig('hooks');
    $modules = static::getConfig('modules');
    $disabled = [];
    if (isset($hooks[$hook])) {
      $disabled = array_intersect_key($implementations, array_flip($hooks[$hook]));
    }
    $disabled += array_intersect_key($implementations, array_flip($modules));
    $implementations = array_diff_key($implementations, $disabled);
    array_walk($disabled, function ($el, $key) use ($hook) {
      error_log('DISABLED: ' . $key . '_' . $hook);
    });
  }

  /**
   * Retrieves configuration values for the migration booster.
   *
   * @param string $key
   *   The configuration key to retrieve.
   *
   * @return array
   *   The configuration value(s) for the specified key.
   */
  protected static function getConfig($key) {
    if (!static::$config) {
      static::$config = \Drupal::config('gabarro_migrate_booster.settings')->get();
    }
    if ($key && isset(static::$config[$key])) {
      return static::$config[$key];
    }

    return [];
  }

}
