<?php

namespace Drupal\b24_commerce\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dynamic settings page routes for all existing order bundles.
 *
 * @package Drupal\b24_commerce\Routing
 */
class B24CommerceRoutes implements ContainerInjectionInterface {

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * Constructs a B24CommerceRoutes object.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * Instantiates a new instance of this class.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Returns routes for settings configuration forms for all needed bundles.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  public function routes() {
    $collection = new RouteCollection();
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('commerce_order');
    $bundles = array_reverse($bundles);
    $first_bundle = key($bundles);
    foreach ($bundles as $bundle_id => $bundle) {
      $path = ($bundle_id !== $first_bundle) ? "/admin/config/b24/commerce/mapping/$bundle_id" : "/admin/config/b24/commerce/mapping";
      $route = new Route($path);
      $route
        ->addDefaults([
          '_form' => '\Drupal\b24_commerce\Form\MappingForm',
          '_title' => ($bundle_id !== $first_bundle) ? $bundle['label'] : 'Fields mapping',
          'commerce_order_type' => $bundle_id,
        ])
        ->addRequirements([
          '_permission' => 'administer b24 configuration',
        ]);

      ($bundle_id !== $first_bundle) ?
        $collection->add("b24_commerce.mapping.$bundle_id", $route) :
        $collection->add("b24_commerce.mapping", $route);
    }

    return $collection;
  }

}
