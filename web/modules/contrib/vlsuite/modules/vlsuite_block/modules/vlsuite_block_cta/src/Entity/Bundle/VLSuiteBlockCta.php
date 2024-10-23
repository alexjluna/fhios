<?php

namespace Drupal\vlsuite_block_cta\Entity\Bundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\vlsuite_block\Entity\Bundle\VLSuiteBlockBase;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldCtaTrait;
use Drupal\vlsuite_bundle_field\BundleField\VLSuiteBundleFieldIconFontIconTrait;

/**
 * Bundle class for VLSuiteBlockCTA.
 */
class VLSuiteBlockCta extends VLSuiteBlockBase {

  const BUNDLE_KEY = 'vlsuite_cta';

  use VLSuiteBundleFieldCtaTrait {
    VLSuiteBundleFieldCtaTrait::bundleFieldDefinitions as bundleFieldDefinitionsCta;
  }

  use VLSuiteBundleFieldIconFontIconTrait {
    VLSuiteBundleFieldIconFontIconTrait::bundleFieldDefinitions as bundleFieldDefinitionsIconFontIcon;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    $fields += self::bundleFieldDefinitionsCta($entity_type, $bundle, $base_field_definitions);
    $fields += self::bundleFieldDefinitionsIconFontIcon($entity_type, $bundle, $base_field_definitions);
    $fields['vlsuite_icon_font_icon']->setRequired(FALSE);
    $fields['vlsuite_icon_font_icon']->setLabel(t('Link font icon'));

    return $fields;
  }

}
