<?php

namespace Drupal\vlsuite_icon_font\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'vlsuite_icon_font_icon' entity field type.
 *
 * @FieldType(
 *   id = "vlsuite_icon_font_icon",
 *   label = @Translation("Icon (VLSuite Icon font)"),
 *   description = @Translation("A VLSuite icon font icon."),
 *   category = @Translation("Visual Layout Suite (VLSuite)"),
 *   default_widget = "vlsuite_icon_font_icon",
 *   default_formatter = "vlsuite_icon_font_icon"
 * )
 */
class VLSuiteIconFontIconItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['value']->setLabel(new TranslatableMarkup('Icon'));

    return $properties;
  }

}
