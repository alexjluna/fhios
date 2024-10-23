<?php

namespace Drupal\vlsuite_bundle_field\BundleField;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * VLSuite bundle field attachments trait.
 */
trait VLSuiteBundleFieldAttachmentsTrait {

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = [];
    $fields['vlsuite_attachments'] = BundleFieldDefinition::create('entity_reference')
      ->setName('vlsuite_attachments')
      ->setLabel(t('Attachments'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setCardinality(BundleFieldDefinition::CARDINALITY_UNLIMITED)
      // @code ->setRevisionable(TRUE) @endcode
      ->setSetting('target_type', 'media')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'vlsuite_document' => 'vlsuite_document',
        ],
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
          'media_types' => ['vlsuite_document'],
        ],
      ]);
    return $fields;
  }

}
