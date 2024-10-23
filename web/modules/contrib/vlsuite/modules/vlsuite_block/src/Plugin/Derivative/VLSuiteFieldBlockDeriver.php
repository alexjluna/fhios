<?php

namespace Drupal\vlsuite_block\Plugin\Derivative;

use Drupal\layout_builder\Plugin\Derivative\FieldBlockDeriver;

/**
 * Provides entity field block definitions for every field.
 *
 * @internal
 *   Plugin derivers are internal.
 */
class VLSuiteFieldBlockDeriver extends FieldBlockDeriver {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    parent::getDerivativeDefinitions($base_plugin_definition);
    foreach (array_keys($this->derivatives) as $derivative_id) {
      $this->derivatives[$derivative_id]['category'] = $base_plugin_definition['category'];
    }
    return $this->derivatives;
  }

}
