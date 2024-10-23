<?php

namespace Drupal\vlsuite_block_remote_video\Entity\Bundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\vlsuite_block\Entity\Bundle\VLSuiteBlockBase;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldRemoteVideoTrait;

/**
 * Bundle class for VLSuiteBlockRemoteVideo.
 */
class VLSuiteBlockRemoteVideo extends VLSuiteBlockBase {

  const BUNDLE_KEY = 'vlsuite_remote_video';

  use VLSuiteBundleFieldRemoteVideoTrait {
    bundleFieldDefinitions as bundleFieldDefinitionsRemoteVideo;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    $fields += self::bundleFieldDefinitionsRemoteVideo($entity_type, $bundle, $base_field_definitions);

    return $fields;
  }

}
