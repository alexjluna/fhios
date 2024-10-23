<?php

namespace Drupal\vlsuite_modal;

use Drupal\Core\Asset\LibraryDiscoveryCollector;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeInitializationInterface;

/**
 * VLSuite modal library discovery collector.
 */
class VLSuiteModalLibraryDiscoveryCollector extends LibraryDiscoveryCollector {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The theme initialization.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Set current route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function setCurrentRouteMatch(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Set theme initiazilation.
   *
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization.
   */
  public function setThemeInitialization(ThemeInitializationInterface $theme_initialization) {
    $this->themeInitialization = $theme_initialization;
  }

  /**
   * Set config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory instance.
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCid() {
    if (!isset($this->cid)) {
      $this->cid = parent::getCid();
      $current_route_name = $this->routeMatch->getRouteName();
      if (!empty($current_route_name) && str_contains($current_route_name, 'layout_builder')) {
        $this->cid .= ':vlsuite_modal_layout_builder_inherited';
      }
    }

    return $this->cid;
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveCacheMiss($key) {
    $library_definitions = $this->getLibraryDefinitions($key);
    if (VLSuiteModalHelper::layoutBuilderLayoutBuilderRouteNameCheck($this->routeMatch->getRouteName())) {
      $admin_theme_name = $this->configFactory->get('system.theme')->get('admin');
      if ($admin_theme_name != $this->themeManager->getActiveTheme()->getName() &&
        VLSuiteModalHelper::isLayoutBuilderEnabled() &&
        VLSuiteModalHelper::isLayoutBuilderAdminInheritedEnabled()) {
        $original_active_theme = $this->themeManager->getActiveTheme();
        $this->themeManager->setActiveTheme($this->themeInitialization->getActiveThemeByName($admin_theme_name));
        $admin_theme_definitions = $this->getLibraryDefinitions($key);
        $this->themeManager->setActiveTheme($original_active_theme);
        $library_definitions = $admin_theme_definitions;
      }
    }
    $this->storage[$key] = $library_definitions;
    $this->persist($key);

    return $this->storage[$key];
  }

}
