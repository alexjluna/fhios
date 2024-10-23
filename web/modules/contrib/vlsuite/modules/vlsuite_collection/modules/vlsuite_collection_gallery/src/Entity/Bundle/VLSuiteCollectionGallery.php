<?php

namespace Drupal\vlsuite_collection_gallery\Entity\Bundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\vlsuite_block\Entity\Bundle\VLSuiteBlockBase;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldMediasTrait;

/**
 * Bundle class for VLSuiteCollectionGallery.
 */
class VLSuiteCollectionGallery extends VLSuiteBlockBase {

  const BUNDLE_KEY = 'vlsuite_collection_gallery';

  use VLSuiteBundleFieldMediasTrait {
    VLSuiteBundleFieldMediasTrait::bundleFieldDefinitions as bundleFieldDefinitionsMedias;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    $fields += self::bundleFieldDefinitionsMedias($entity_type, $bundle, $base_field_definitions);

    return $fields;
  }

}
