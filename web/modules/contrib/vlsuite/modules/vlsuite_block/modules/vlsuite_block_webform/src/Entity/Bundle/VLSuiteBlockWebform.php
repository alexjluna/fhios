<?php

namespace Drupal\vlsuite_block_webform\Entity\Bundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\vlsuite_block\Entity\Bundle\VLSuiteBlockBase;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldWebformTrait;

/**
 * Bundle class for VLSuiteBlockWebform.
 */
class VLSuiteBlockWebform extends VLSuiteBlockBase {

  const BUNDLE_KEY = 'vlsuite_webform';

  use VLSuiteBundleFieldWebformTrait {
    bundleFieldDefinitions as bundleFieldDefinitionsWebform;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    $fields += self::bundleFieldDefinitionsWebform($entity_type, $bundle, $base_field_definitions);

    return $fields;
  }

}
