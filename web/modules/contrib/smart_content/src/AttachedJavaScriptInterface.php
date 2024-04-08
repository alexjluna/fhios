<?php

namespace Drupal\smart_content;

/**
 * Provides an interface for classes providing JS libraries and settings.
 *
 * Used to provide common interface for attaching libraries in settings for
 * classes and child classes.
 * todo: Potentially rename, as libraries can have css files.
 *
 * @package Drupal\smart_content
 */
interface AttachedJavaScriptInterface {

  /**
   * Get JS drupalSettings for self and children.
   *
   * @return array
   *   Array of JS settings.
   */
  public function getAttachedSettings();

  /**
   * Get JS libraries for self and children.
   *
   * @return array
   *   Array of JS libraries.
   */
  public function getLibraries();

}
