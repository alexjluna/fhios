<?php

namespace Drupal\servicio_luna\Guard;

use Drupal\Core\Entity\EntityInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

class FulfillmentOrderGuard implements GuardInterface {

  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    if ($transition->getId() == 'cancel') {
      // Do not cancel orders with filled items.
      foreach ($entity->getItems() as $order_item) {
        if ($order_item->get('field_fulfillment_state')->first()->getId() == 'filled') {
          return FALSE;
        }
      }
      return TRUE;
    }
    if ($transition->getId() == 'fulfill') {
      // All items must be filled or backordered. At least one must be filled.
      $has_filled_item = FALSE;
      foreach ($entity->getItems() as $order_item) {
        $inventory_state = $order_item->get('field_fulfillment_state')->first()->getId();
        if ($inventory_state != 'filled' && $inventory_state != 'backordered') {
          return FALSE;
        }
        $has_filled_item = $inventory_state == 'filled' ? TRUE : $has_filled_item;
      }
      return $has_filled_item;
    }

    // All other transitions.
    return TRUE;
  }

}
