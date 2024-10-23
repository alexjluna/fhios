<?php

namespace Drupal\vlsuite_layout_tabs\Plugin\Layout;

/**
 * VLSuite layout tabs horizontal.
 *
 * @Layout(
 *   id = "vlsuite_layout_tabs_horizontal",
 *   label = @Translation("Tabs horizontal with optional top & bottom (VLSuite)"),
 *   path = "layouts/tabs-horizontal",
 *   template = "vlsuite-layout-tabs-horizontal",
 *   library = "vlsuite_layout_tabs/tabs-horizontal"
 * )
 */
class VLSuiteLayoutTabsHorizontal extends VLSuiteLayoutTabsBase {

  /**
   * {@inheritdoc}
   */
  protected function getPluginDefinitionIconMap() {
    return [
      ['top'],
      ['tab_0', 'tab_1'],
      ['tab_0'],
      ['bottom'],
    ];
  }

}
