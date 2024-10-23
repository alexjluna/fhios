<?php

namespace Drupal\vlsuite_block\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Override  controller.
 */
class VLSuiteBlockRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('layout_builder.choose_block')) {
      $defaults = $route->getDefaults();
      $defaults['_controller'] = '\Drupal\vlsuite_block\Controller\VLSuiteBlockChooseBlockController::build';
      $route->setDefaults($defaults);
    }
  }

}
