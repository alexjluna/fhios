<?php

namespace Drupal\smart_content\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ReactionSet storage item annotation object.
 *
 * @see \Drupal\smart_content\Plugin\ReactionSetStorageManager
 * @see plugin_api
 *
 * @Annotation
 */
class SmartDecisionStorage extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
