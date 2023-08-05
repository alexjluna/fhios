<?php

namespace Drupal\products\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Importer plugins.
 */
interface ImporterPluginInterface extends PluginInspectionInterface {

  /**
   * Performs the import.
   *
   * Returns TRUE if the import was successful or FALSE otherwise.
   */
  public function import() : bool;

}
