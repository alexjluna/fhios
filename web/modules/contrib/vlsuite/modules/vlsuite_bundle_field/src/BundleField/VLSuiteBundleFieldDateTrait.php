<?php

namespace Drupal\vlsuite_bundle_field\BundleField;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * VLSuite bundle field date trait.
 */
trait VLSuiteBundleFieldDateTrait {

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = [];
    $fields['vlsuite_date'] = BundleFieldDefinition::create('datetime')
      ->setName('vlsuite_date')
      ->setLabel(t('Date'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setSetting('datetime_type', 'date')
      // @code ->setRevisionable(TRUE) @endcode
      // Display
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
        'label' => 'hidden',
      ])
      // Form display.
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
      ]);

    return $fields;
  }

}
