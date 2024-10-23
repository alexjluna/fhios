<?php

namespace Drupal\vlsuite_collection_stmt\Entity\Bundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\vlsuite_block\Entity\Bundle\VLSuiteBlockBase;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldBackgroundTrait;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldCtaTrait;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldImageTrait;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldTextTrait;

/**
 * Bundle class for VLSuiteCollectionStmt.
 */
class VLSuiteCollectionStmt extends VLSuiteBlockBase {

  const BUNDLE_KEY = 'vlsuite_collection_stmt';

  use VLSuiteBundleFieldTextTrait {
    VLSuiteBundleFieldTextTrait::bundleFieldDefinitions as bundleFieldDefinitionsText;
  }

  use VLSuiteBundleFieldCtaTrait {
    VLSuiteBundleFieldCtaTrait::bundleFieldDefinitions as bundleFieldDefinitionsCta;
  }

  use VLSuiteBundleFieldImageTrait {
    VLSuiteBundleFieldImageTrait::bundleFieldDefinitions as bundleFieldDefinitionsImage;
  }

  use VLSuiteBundleFieldBackgroundTrait {
    VLSuiteBundleFieldBackgroundTrait::bundleFieldDefinitions as bundleFieldDefinitionsBackground;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    $fields += self::bundleFieldDefinitionsText($entity_type, $bundle, $base_field_definitions);
    $fields['vlsuite_text_extra'] = clone $fields['vlsuite_text'];
    $fields['vlsuite_text_extra']->setName('vlsuite_text_extra')->setLabel(t('Author / Date / Position text'));
    $fields += self::bundleFieldDefinitionsImage($entity_type, $bundle, $base_field_definitions);
    $fields['vlsuite_image']->setRequired(FALSE);
    $fields['vlsuite_image']->setLabel(t('Image / Picture'));
    $fields += self::bundleFieldDefinitionsBackground($entity_type, $bundle, $base_field_definitions);
    $fields['vlsuite_background']->setRequired(FALSE);
    $fields += self::bundleFieldDefinitionsCta($entity_type, $bundle, $base_field_definitions);
    $fields['vlsuite_link']->setRequired(FALSE);

    return $fields;
  }

}
