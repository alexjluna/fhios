<?php

namespace Drupal\vlsuite_modal;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Disable aggregation on Vlsuite administrative pages.
 *
 * This is a workaround done as the current way to add admin assets to the UI
 * is not compatible with the way Vlsuite does.
 *
 * This is a short term fix
 * until https://www.drupal.org/project/drupal/issues/2195695 is fixed.
 */
class VLSuiteModalConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Constructs the config override.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack service.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (in_array('system.performance', $names) && $this->isLayoutPath()) {
      $overrides['system.performance'] = [
        'css' => [
          'preprocess' => FALSE,
        ],
        'js' => [
          'preprocess' => FALSE,
        ],
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'VLSuiteModalConfigOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    $metadata = new CacheableMetadata();
    $metadata->setCacheContexts(['url']);
    return $metadata;
  }

  /**
   * Check the current path is a layout builder path.
   *
   * @return bool
   *   TRUE if the route belongs to layout builder path.
   */
  protected function isLayoutPath() {
    // In some cases it seems request is empty in class constructor.
    // Instead just get it before usage in order to avoid getting empty request
    // from request stack.
    $request = $this->requestStack->getCurrentRequest();
    // Route provider is not added by dependency injection
    // because it produces circular dependency at config override
    // phase.
    /** @var \Drupal\Core\Routing\RouteProviderInterface $router */
    // @codingStandardsIgnoreLine
    if ($request instanceof Request) {
      $router = \Drupal::service('router.route_provider');
      $routes = $router->getRouteCollectionForRequest($request);

      foreach ($routes as $name => $route) {
        if (VLSuiteModalHelper::layoutBuilderLayoutBuilderRouteNameCheck($name)) {
          return TRUE;
        }
      }

    }

    return FALSE;
  }

}
