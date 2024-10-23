<?php

namespace Drupal\vlsuite_bundle_field\BundleField;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * VLSuite bundle field icon font icon trait.
 */
trait VLSuiteBundleFieldIconFontIconTrait {

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = [];
    $fields['vlsuite_icon_font_icon'] = BundleFieldDefinition::create('vlsuite_icon_font_icon')
      ->setName('vlsuite_icon_font_icon')
      ->setLabel(t('Icon'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      // @code ->setRevisionable(TRUE) @endcode
      // Display
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'vlsuite_icon_font_icon',
        'label' => 'hidden',
      ])
      // Form display.
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'vlsuite_icon_font_icon',
      ]);

    return $fields;
  }

}
