<?php

namespace Drupal\vlsuite_block\Plugin\Derivative;

use Drupal\Component\Plugin\PluginBase;
use Drupal\layout_builder\Plugin\Derivative\FieldBlockDeriver;

/**
 * Entity field block definitions for each field that can be used as section bg.
 *
 * @internal
 *   Plugin derivers are internal.
 */
class VLSuiteMediaBgFieldBlockDeriver extends FieldBlockDeriver {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    parent::getDerivativeDefinitions($base_plugin_definition);
    foreach (array_keys($this->derivatives) as $derivative_id) {
      $this->derivatives[$derivative_id]['category'] = $base_plugin_definition['category'];
      [$entity_type_id, $bundle, $field_name] = explode(PluginBase::DERIVATIVE_SEPARATOR, $derivative_id, 3);
      $field_definition = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle)[$field_name];
      $target_type = $field_definition->getType() == 'entity_reference' ? $field_definition->getFieldStorageDefinition()->getSetting('target_type') : NULL;
      $allowed_bundles = $target_type === 'media' ? array_keys($field_definition->getSetting('handler_settings')['target_bundles'] ?? []) : [];
      $bg_types = array_keys(\Drupal::configFactory()->get('vlsuite_media.settings')->get('bg_types') ?? []);
      if (!($target_type === 'media' && !empty(array_intersect($allowed_bundles, $bg_types)))) {
        unset($this->derivatives[$derivative_id]);
      }
    }
    return $this->derivatives;
  }

}
