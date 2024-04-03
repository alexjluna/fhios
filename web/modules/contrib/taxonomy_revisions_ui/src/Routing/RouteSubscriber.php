<?php

namespace Drupal\taxonomy_revisions_ui\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('entity.taxonomy_term.revision');
    if (!$route) {
      return;
    }

    $route->setRequirement('_entity_access', 'taxonomy_term.view revision');
  }

}
