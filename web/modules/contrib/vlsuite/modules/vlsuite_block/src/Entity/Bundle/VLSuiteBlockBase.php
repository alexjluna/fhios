<?php

namespace Drupal\vlsuite_block\Entity\Bundle;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * VLSuiteBlockContentBase block content base class.
 */
abstract class VLSuiteBlockBase extends BlockContent {

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    return $fields;
  }

  /**
   * Get utility classes definitions.
   *
   * @return array
   *   Utility classes definitions for bundle.
   */
  public static function getUtilityClassesDefinitions() {
    return [];
  }

}
