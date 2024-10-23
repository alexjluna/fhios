<?php

namespace Drupal\vlsuite_bundle_field\BundleField;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * VLSuite bundle field string trait.
 */
trait VLSuiteBundleFieldStringTrait {

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = [];
    $fields['vlsuite_string'] = BundleFieldDefinition::create('string')
      ->setName('vlsuite_string')
      ->setLabel(t('String'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setCardinality(1)
      // @code ->setRevisionable(TRUE) @endcode
      // Display
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'hidden',
      ])
      // Form display.
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ]);

    return $fields;
  }

}
