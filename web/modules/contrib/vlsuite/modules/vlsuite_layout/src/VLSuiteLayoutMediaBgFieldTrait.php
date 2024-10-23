<?php

namespace Drupal\vlsuite_layout;

/**
 * VLSuite layout media background field trait.
 *
 * @see \Drupal\vlsuite_block\Plugin\Block\VLSuiteMediaBgFieldBlock;
 */
trait VLSuiteLayoutMediaBgFieldTrait {

  /**
   * Get media background from block entity media field.
   *
   * @param array $regions
   *   Regions.
   *
   * @return int
   *   Media bg from entity field.
   */
  protected function getMediaBgFieldId(array $regions) {
    $media_id = 0;
    foreach ($regions as $region) {
      foreach ($region as $block) {
        if (($block['#base_plugin_id'] ?? NULL) == 'vlsuite_block_media_bg_block' && !empty($block['content']['#media_bg_id'])) {
          $media_id = $block['content']['#media_bg_id'];
          break;
        }
      }
    }
    return $media_id;
  }

}
