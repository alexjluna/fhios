<?php

namespace Drupal\vlsuite_layout\Plugin\Layout;

/**
 * Configurable two column layout plugin class.
 */
class VLSuiteLayoutOneCol extends VLSuiteLayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions() {
    return [
      '100' => '100%',
      '80-auto' => '80%',
      '60-auto' => '60%',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultWidth() {
    return '100';
  }

}
