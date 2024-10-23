<?php

namespace Drupal\vlsuite_layout\Plugin\Layout;

use Drupal\vlsuite_layout\VLSuiteLayoutHeadingsMenuTrait;

/**
 * Configurable two column layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class VLSuiteLayoutTwoCols extends VLSuiteLayoutBase {

  use VLSuiteLayoutHeadingsMenuTrait;

  const COLS_EQUAL = 6;

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions() {
    return [
      '50-50' => '50%/50%',
      '40-60' => '40%/60%',
      '60-40' => '60%/40%',
      '33-67' => '33%/67%',
      '67-33' => '67%/33%',
      '25-75' => '25%/75%',
      '75-25' => '75%/25%',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultWidth() {
    return '50-50';
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $this->headingsMenuBuildAlter($regions, $build, 'first', 'second');
    return $build;
  }

}
