<?php

namespace Drupal\vlsuite_block_icon\Entity\Bundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\vlsuite_block\Entity\Bundle\VLSuiteBlockBase;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldCtaTrait;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldIconFontIconTrait;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldIconTrait;

/**
 * Bundle class for VLSuiteBlockIcon.
 */
class VLSuiteBlockIcon extends VLSuiteBlockBase {

  const BUNDLE_KEY = 'vlsuite_icon';

  use VLSuiteBundleFieldIconTrait {
    VLSuiteBundleFieldIconTrait::bundleFieldDefinitions as bundleFieldDefinitionsIcon;
  }

  use VLSuiteBundleFieldIconFontIconTrait {
    VLSuiteBundleFieldIconFontIconTrait::bundleFieldDefinitions as bundleFieldDefinitionsIconFontIcon;
  }

  use VLSuiteBundleFieldCtaTrait {
    VLSuiteBundleFieldCtaTrait::bundleFieldDefinitions as bundleFieldDefinitionsCta;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    $fields += self::bundleFieldDefinitionsIcon($entity_type, $bundle, $base_field_definitions);
    $fields['vlsuite_icon']->setLabel(t('Icon'));
    $fields['vlsuite_icon']->setDescription(t('NOTE: This icon will be shown when available over "Font icon".'));
    $fields['vlsuite_icon']->setRequired(FALSE);
    $fields += self::bundleFieldDefinitionsIconFontIcon($entity_type, $bundle, $base_field_definitions);
    $fields['vlsuite_icon_font_icon']->setRequired(FALSE);
    $fields['vlsuite_icon_font_icon']->setLabel(t('Font icon'));
    $fields += self::bundleFieldDefinitionsCta($entity_type, $bundle, $base_field_definitions);
    $fields['vlsuite_link']->setLabel(t('Linked to (optional)'));
    $fields['vlsuite_link']->setRequired(FALSE);
    $fields['vlsuite_link']->setSetting('title', DRUPAL_DISABLED);
    return $fields;
  }

}
