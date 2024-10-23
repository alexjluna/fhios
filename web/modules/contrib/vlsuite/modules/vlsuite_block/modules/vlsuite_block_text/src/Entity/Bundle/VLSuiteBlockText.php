<?php

namespace Drupal\vlsuite_block_text\Entity\Bundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\vlsuite_block\Entity\Bundle\VLSuiteBlockBase;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldTextTrait;

/**
 * Bundle class for VLSuiteBlockText.
 */
class VLSuiteBlockText extends VLSuiteBlockBase {

  const BUNDLE_KEY = 'vlsuite_text';

  use VLSuiteBundleFieldTextTrait {
    bundleFieldDefinitions as bundleFieldDefinitionsText;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    $fields += self::bundleFieldDefinitionsText($entity_type, $bundle, $base_field_definitions);

    return $fields;
  }

}
