<?php

namespace Drupal\vlsuite_bundle_field\BundleField;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * VLSuite bundle field text trait.
 */
trait VLSuiteBundleFieldTextTrait {

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = [];
    $fields['vlsuite_text'] = BundleFieldDefinition::create('text_long')
      ->setName('vlsuite_text')
      ->setLabel(t('Text'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setCardinality(1)
      // @code ->setRevisionable(TRUE) @endcode
      // Display
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'hidden',
      ])
      // Form display.
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ]);
    return $fields;
  }

}
