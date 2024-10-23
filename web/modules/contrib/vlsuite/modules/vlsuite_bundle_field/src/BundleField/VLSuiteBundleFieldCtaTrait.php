<?php

namespace Drupal\vlsuite_bundle_field\BundleField;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\BundleFieldDefinition;
use Drupal\link\LinkItemInterface;

/**
 * VLSuite bundle field cta trait.
 */
trait VLSuiteBundleFieldCtaTrait {

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = [];
    $fields['vlsuite_link'] = BundleFieldDefinition::create('link')
      ->setName('vlsuite_link')
      ->setLabel(t('Link'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setCardinality(1)
      // @code ->setRevisionable(TRUE) @endcode
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => DRUPAL_REQUIRED,
      ])
      // Display.
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'link',
        'settings' => ['trim_length' => '60'],
        'label' => 'hidden',
      ])
      // Form display.
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'link_default',
      ]);
    return $fields;
  }

}
