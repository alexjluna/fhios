<?php

namespace Drupal\vlsuite_layout\Plugin\Layout;

/**
 * Configurable four column layout plugin class.
 */
class VLSuiteLayoutFourCols extends VLSuiteLayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions() {
    return [
      '25-25-25-25' => '25%/25%/25%/25%',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultWidth() {
    return '25-25-25-25';
  }

}
