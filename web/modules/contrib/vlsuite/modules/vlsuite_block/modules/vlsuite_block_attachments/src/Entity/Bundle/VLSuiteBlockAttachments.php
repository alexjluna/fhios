<?php

namespace Drupal\vlsuite_block_attachments\Entity\Bundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\vlsuite_block\Entity\Bundle\VLSuiteBlockBase;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldAttachmentsTrait;

/**
 * Bundle class for VLSuiteBlockAttachments.
 */
class VLSuiteBlockAttachments extends VLSuiteBlockBase {

  const BUNDLE_KEY = 'vlsuite_attachments';

  use VLSuiteBundleFieldAttachmentsTrait {
    bundleFieldDefinitions as bundleFieldDefinitionsAttachments;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    $fields += self::bundleFieldDefinitionsAttachments($entity_type, $bundle, $base_field_definitions);

    return $fields;
  }

}
