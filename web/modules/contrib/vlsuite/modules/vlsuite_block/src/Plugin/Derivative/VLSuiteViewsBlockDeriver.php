<?php

namespace Drupal\vlsuite_block\Plugin\Derivative;

use Drupal\views\Plugin\Derivative\ViewsBlock;

/**
 * Provides block plugin definitions for all Views block displays.
 *
 * @internal
 *   Plugin derivers are internal.
 */
class VLSuiteViewsBlockDeriver extends ViewsBlock {

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
