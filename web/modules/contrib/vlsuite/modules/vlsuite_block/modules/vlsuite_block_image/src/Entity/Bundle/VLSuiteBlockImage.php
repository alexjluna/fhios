<?php

namespace Drupal\vlsuite_block_image\Entity\Bundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\vlsuite_block\Entity\Bundle\VLSuiteBlockBase;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldCtaTrait;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldImageTrait;

/**
 * Bundle class for VLSuiteBlockImage.
 */
class VLSuiteBlockImage extends VLSuiteBlockBase {

  const BUNDLE_KEY = 'vlsuite_image';

  use VLSuiteBundleFieldImageTrait {
    VLSuiteBundleFieldImageTrait::bundleFieldDefinitions as bundleFieldDefinitionsImage;
  }

  use VLSuiteBundleFieldCtaTrait {
    VLSuiteBundleFieldCtaTrait::bundleFieldDefinitions as bundleFieldDefinitionsCta;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    $fields += self::bundleFieldDefinitionsImage($entity_type, $bundle, $base_field_definitions);
    $fields += self::bundleFieldDefinitionsCta($entity_type, $bundle, $base_field_definitions);
    $fields['vlsuite_link']->setLabel(t('Linked to (optional)'));
    $fields['vlsuite_link']->setRequired(FALSE);
    $fields['vlsuite_link']->setSetting('title', DRUPAL_DISABLED);

    return $fields;
  }

}
