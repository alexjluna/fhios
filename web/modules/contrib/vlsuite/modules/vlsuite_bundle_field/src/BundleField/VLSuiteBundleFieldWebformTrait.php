<?php

namespace Drupal\vlsuite_bundle_field\BundleField;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * VLSuite bundle field webform video trait.
 */
trait VLSuiteBundleFieldWebformTrait {

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = [];
    $fields['vlsuite_webform'] = BundleFieldDefinition::create('webform')
      ->setName('vlsuite_webform')
      ->setLabel(t('Webform'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      // @code ->setRevisionable(TRUE) @endcode
      ->setSetting('handler', 'default')
      // Display.
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'webform_entity_reference_entity_view',
        'settings' => [
          'source_entity' => FALSE,
        ],
      ])
      // Form display.
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'webform_entity_reference_select',
        'settings' => [
          'default_data' => FALSE,
        ],
      ]);
    return $fields;
  }

}
