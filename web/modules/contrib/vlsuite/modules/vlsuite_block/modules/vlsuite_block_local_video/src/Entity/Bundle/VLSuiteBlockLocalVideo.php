<?php

namespace Drupal\vlsuite_block_local_video\Entity\Bundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\vlsuite_block\Entity\Bundle\VLSuiteBlockBase;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldLocalVideoTrait;

/**
 * Bundle class for VLSuiteBlockLocalVideo.
 */
class VLSuiteBlockLocalVideo extends VLSuiteBlockBase {

  const BUNDLE_KEY = 'vlsuite_local_video';

  use VLSuiteBundleFieldLocalVideoTrait {
    bundleFieldDefinitions as bundleFieldDefinitionsLocalVideo;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    $fields += self::bundleFieldDefinitionsLocalVideo($entity_type, $bundle, $base_field_definitions);

    return $fields;
  }

}
