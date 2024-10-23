<?php

namespace Drupal\vlsuite_bundle_field\BundleField;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * VLSuite bundle field icon trait.
 */
trait VLSuiteBundleFieldIconTrait {

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = [];
    $fields['vlsuite_icon'] = BundleFieldDefinition::create('entity_reference')
      ->setName('vlsuite_icon')
      ->setLabel(t('Icon'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setCardinality(1)
      // @code ->setRevisionable(TRUE) @endcode
      ->setSetting('target_type', 'media')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'vlsuite_icon' => 'vlsuite_icon',
        ],
      ])
      // Display.
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'media_responsive_thumbnail',
        'settings' => ['responsive_image_style' => 'vlsuite_original_optimized_icon'],
        'label' => 'hidden',
      ])
      // Form display.
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'media_library_widget',
        'region' => 'content',
        'settings' => [
          'media_types' => ['vlsuite_icon'],
        ],
      ]);
    return $fields;
  }

}
