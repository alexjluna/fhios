<?php

namespace Drupal\vlsuite_modal\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\AjaxBasePageNegotiator;
use Drupal\vlsuite_modal\VLSuiteModalHelper;

/**
 * Theme negotiator.
 */
class VLSuiteModalThemeNegotiator extends AjaxBasePageNegotiator {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // Inherit admin theme library definitions to have assets loaded before
    // modal opens, not using iframe due complexity & some limititations like
    // not having modal actions, auto height not working, ...
    // Also avoid replicate some specific theme styles, use originals.
    // This implementation consist:
    // 1. Build library dinamically with admin theme libraries as dependencies.
    // 2. Library discovery collector used to mix & ensure styles for lb page.
    // 3. Layout builder will attach dinamically generated library.
    // 4. Layout builder will toggle active theme by theme negotiator.
    // @see
    // VLSuiteModalLayoutBuilderPreRender::preRender().
    // VLSuiteModalLibraryDiscoveryCollector::getCid().
    // VLSuiteModalLibraryDiscoveryCollector::resolveCacheMiss().
    $applies = parent::applies($route_match);
    $route_name = $route_match->getRouteName();
    $route_name_condition = VLSuiteModalHelper::layoutBuilderLayoutBuilderRouteNameCheck($route_name);

    if ($applies && $route_name_condition && VLSuiteModalHelper::isLayoutBuilderAdminInheritedEnabled() && VLSuiteModalHelper::isLayoutBuilderEnabled()) {
      $applies = TRUE;
    }
    else {
      $applies = FALSE;
    }
    return $applies;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    $valid_theme = parent::determineActiveTheme($route_match);
    if (!empty($valid_theme)) {
      $admin = $this->configFactory->get('system.theme')->get('admin');
      $default = $this->configFactory->get('system.theme')->get('default');
      $theme = NULL;
      $is_vlsuite_modal = ($this->requestStack->getCurrentRequest()->request->all('dialogOptions')['target'] ?? NULL) == VLSuiteModalHelper::DIALOG_TARGET;
      // @note name = op (update) will trigger default theme, form errors are
      // unknown at this point (response still not calculated), recommended
      // adding ife + clientside validation when available for these forms.
      $is_block_form = str_contains($this->requestStack->getCurrentRequest()->request->get('_triggering_element_name') ?? '', 'block_form');
      $is_settings = str_contains($this->requestStack->getCurrentRequest()->request->get('_triggering_element_name') ?? '', 'settings');
      $is_import_section_route = $route_match->getRouteName() == 'section_library.import_section_from_library';
      $is_duplicate_block_route = $route_match->getRouteName() == 'vlsuite_layout_builder.duplicate_block';

      if (($is_vlsuite_modal || $is_block_form || $is_settings) && !$is_import_section_route && !$is_duplicate_block_route) {
        $theme = $admin;
      }
      else {
        $theme = $default;
      }
      return $theme;
    }
  }

}
