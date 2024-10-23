<?php

namespace Drupal\vlsuite_bundle_field\BundleField;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * VLSuite bundle field medias trait.
 */
trait VLSuiteBundleFieldMediasTrait {

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = [];
    $media_bundles = array_keys(\Drupal::configFactory()->get('vlsuite_media.settings')->get('media_types') ?? []);
    $fields['vlsuite_medias'] = BundleFieldDefinition::create('entity_reference')
      ->setName('vlsuite_medias')
      ->setLabel(t('Medias'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setCardinality(BundleFieldDefinition::CARDINALITY_UNLIMITED)
      // @code ->setRevisionable(TRUE) @endcode
      ->setSetting('target_type', 'media')
      ->setSetting('handler_settings', [
        'target_bundles' => array_combine($media_bundles, $media_bundles),
      ])
      // Display.
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_entity_view',
        'settings' => ['view_mode' => 'default'],
        'label' => 'hidden',
      ])
      // Form display.
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'media_library_widget',
        'region' => 'content',
        'settings' => [
          'media_types' => $media_bundles,
        ],
      ]);
    return $fields;
  }

}
