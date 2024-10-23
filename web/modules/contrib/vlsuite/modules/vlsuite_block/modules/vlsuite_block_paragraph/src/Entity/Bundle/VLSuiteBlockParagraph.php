<?php

namespace Drupal\vlsuite_block_paragraph\Entity\Bundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\vlsuite_block\Entity\Bundle\VLSuiteBlockBase;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldParagraphTrait;

/**
 * Bundle class for VLSuiteBlockParagraph.
 */
class VLSuiteBlockParagraph extends VLSuiteBlockBase {

  const BUNDLE_KEY = 'vlsuite_paragraph';

  use VLSuiteBundleFieldParagraphTrait {
    bundleFieldDefinitions as bundleFieldDefinitionsParagraph;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    $fields += self::bundleFieldDefinitionsParagraph($entity_type, $bundle, $base_field_definitions);

    return $fields;
  }

}
