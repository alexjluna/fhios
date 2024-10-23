<?php

namespace Drupal\vlsuite_collection_card\Entity\Bundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\vlsuite_block\Entity\Bundle\VLSuiteBlockBase;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldBackgroundTrait;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldCtaTrait;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldIconTrait;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldMediaTrait;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldTextTrait;

/**
 * Bundle class for VLSuiteCollectionCard.
 */
class VLSuiteCollectionCard extends VLSuiteBlockBase {

  const BUNDLE_KEY = 'vlsuite_collection_card';

  use VLSuiteBundleFieldCtaTrait {
    VLSuiteBundleFieldCtaTrait::bundleFieldDefinitions as bundleFieldDefinitionsCta;
  }

  use VLSuiteBundleFieldTextTrait {
    VLSuiteBundleFieldTextTrait::bundleFieldDefinitions as bundleFieldDefinitionsText;
  }


  use VLSuiteBundleFieldMediaTrait {
    VLSuiteBundleFieldMediaTrait::bundleFieldDefinitions as bundleFieldDefinitionsMedia;
  }

  use VLSuiteBundleFieldBackgroundTrait {
    VLSuiteBundleFieldBackgroundTrait::bundleFieldDefinitions as bundleFieldDefinitionsBackground;
  }

  use VLSuiteBundleFieldIconTrait {
    VLSuiteBundleFieldIconTrait::bundleFieldDefinitions as bundleFieldDefinitionsIcon;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    $fields += self::bundleFieldDefinitionsText($entity_type, $bundle, $base_field_definitions);
    $fields += self::bundleFieldDefinitionsMedia($entity_type, $bundle, $base_field_definitions);
    $fields['vlsuite_media']->setRequired(FALSE);
    $fields['vlsuite_media']->setDescription(t('To be used when appropriate based on the selected view mode.'));
    $fields += self::bundleFieldDefinitionsBackground($entity_type, $bundle, $base_field_definitions);
    $fields['vlsuite_background']->setRequired(FALSE);
    $fields['vlsuite_background']->setDescription(t('To be used when appropriate based on the selected view mode.'));
    $fields += self::bundleFieldDefinitionsIcon($entity_type, $bundle, $base_field_definitions);
    $fields['vlsuite_icon']->setRequired(FALSE);
    $fields += self::bundleFieldDefinitionsCta($entity_type, $bundle, $base_field_definitions);
    $fields['vlsuite_link']->setRequired(FALSE);

    return $fields;
  }

}
