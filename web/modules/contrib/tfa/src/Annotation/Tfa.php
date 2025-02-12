<?php

namespace Drupal\tfa\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a TFA annotation object.
 *
 * @Annotation
 */
final class Tfa extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

  /**
   * The helper metadata for setup plugin.
   *
   * @var string[]
   */
  public array $helpLinks;

  /**
   * The messages to be displayed during setup steps.
   *
   * @var string[]
   */
  public array $setupMessages;

}
