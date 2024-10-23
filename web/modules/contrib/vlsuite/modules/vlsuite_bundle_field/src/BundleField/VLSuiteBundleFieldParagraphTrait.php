<?php

namespace Drupal\vlsuite_bundle_field\BundleField;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * VLSuite bundle field paragraph trait.
 */
trait VLSuiteBundleFieldParagraphTrait {

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions): array {
    $fields = [];
    $fields['vlsuite_paragraph'] = BundleFieldDefinition::create('entity_reference_revisions')
      ->setName('vlsuite_paragraph')
      ->setLabel(t('Paragraph'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      // @code ->setRevisionable(TRUE) @endcode
      ->setSetting('target_type', 'paragraph')
      ->setSetting('handler', 'default')
      // Display.
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_revisions_entity_view',
      ])
      // Form display.
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'paragraphs',
        'settings' => [
          'edit_mode' => 'open',
          'add_mode' => 'button',
          'features' => [
            'duplicate' => FALSE,
            'collapse_edit_all' => FALSE,
            'add_above' => FALSE,
          ],
        ],
      ]);
    return $fields;
  }

}
