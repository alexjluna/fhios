<?php

namespace Drupal\vlsuite_icon_font\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vlsuite_icon_font\VLSuiteIconFontHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'vlsuite_icon_font_icon' widget.
 *
 * @FieldWidget(
 *   id = "vlsuite_icon_font_icon",
 *   label = @Translation("Icon (VLSuite Icon font)"),
 *   field_types = {
 *     "vlsuite_icon_font_icon"
 *   }
 * )
 */
class VLSuiteIconFontIconWidget extends WidgetBase {

  /**
   * The icon font helper.
   *
   * @var \Drupal\vlsuite_icon_font\VLSuiteIconFontHelper
   */
  protected $iconFontHelper;

  /**
   * Constructs a "VLSuiteIconFontIconWidget" widget class.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\vlsuite_icon_font\VLSuiteIconFontHelper $icon_font_helper
   *   The icon font helper.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, VLSuiteIconFontHelper $icon_font_helper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->iconFontHelper = $icon_font_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('vlsuite_icon_font.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $default_form_element = $this->iconFontHelper->getIconFontIconFormElement($items[$delta]->value ?? NULL);
    if (empty($element['#description']) && $element['#description'] == '') {
      unset($element['#description']);
    }
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->value ?? NULL,
      '#maxlength' => $this->getFieldSetting('max_length'),
    ] + $default_form_element;

    return $element;
  }

}
