<?php

namespace Drupal\b24_commerce\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines dynamic local tasks.
 */
class DynamicMappingTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $order_types = array_reverse(\Drupal::service('entity_type.bundle.info')
      ->getBundleInfo('commerce_order'));
    $parent_type = key($order_types);
    foreach ($order_types as $order_type_id => $order_type) {
      $this->derivatives["b24_commerce.mapping.$order_type_id"] = [
        'title' => $order_type['label'],
        'route_name' => $order_type_id !== $parent_type ? "b24_commerce.mapping.$order_type_id" : "b24_commerce.mapping",
        'base_route' => "b24_commerce.mapping",
      ] + $base_plugin_definition;
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
