<?php

namespace Drupal\vlsuite_icon_font;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Helper "VLSuiteIconFontHelper" service object.
 */
class VLSuiteIconFontHelper {

  use StringTranslationTrait;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a "VLSuiteIconFontHelper" object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Get icon map.
   *
   * @return array
   *   From configuration.
   */
  public function getIconFontMap() {
    $icon_map = [
      'main_classes' => $this->configFactory->get('vlsuite_icon_font.settings')->get('main_classes'),
      'replacement' => $this->configFactory->get('vlsuite_icon_font.settings')->get('replacement'),
      'list' => array_map('trim', explode("\n", $this->configFactory->get('vlsuite_icon_font.settings')->get('list'))),
    ];
    return $icon_map;
  }

  /**
   * Get icon form element.
   *
   * @param string $default_icon
   *   Default icon.
   *
   * @return array
   *   Icon form element.
   */
  public function getIconFontIconFormElement(string $default_icon = NULL) {
    $vlsuite_icon_font_configure_url = Url::fromRoute('vlsuite_icon_font.settings');
    $description = $vlsuite_icon_font_configure_url->access() ? $this->t('Choose icon, start typing for autocomplete suggestions. You can configure <a href=":link" target="_blank">here</a>.',
        [':link' => $vlsuite_icon_font_configure_url->toString()]) : $this->t('Choose icon, start typing for autocomplete suggestions.');
    $icon_classes = array_merge(['vlsuite-icon-font-icon-form-element__icon'], explode(' ', $this->configFactory->get('vlsuite_icon_font.settings')->get('main_classes') ?? ''));
    return [
      '#attached' => ['library' => ['vlsuite_icon_font/icon_form_element']],
      '#type' => 'textfield',
      '#default_value' => $default_icon ?? NULL,
      '#title' => $this->t('Icon'),
      '#description' => $description,
      '#attributes' => ['class' => ['vlsuite-icon-font-icon-form-element__input']],
      '#wrapper_attributes' => [
        'class' => ['vlsuite-icon-font-icon-form-element'],
        'data-vlsuite-icon-font-replacement' => $this->configFactory->get('vlsuite_icon_font.settings')->get('replacement') ?? '',
      ],
      '#autocomplete_route_name' => 'vlsuite_icon_font.autocomplete',
      '#element_validate' => [
        [static::class, 'validateIconFontIconFormElement'],
      ],
      '#field_suffix' => [
        'span' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => ['class' => $icon_classes],
        ],
      ],
    ];
  }

  /**
   * Validate icon form element.
   *
   * @param array $element
   *   Icon element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public static function validateIconFontIconFormElement(array &$element, FormStateInterface $form_state) {
    if (!empty($element['#value']) && !in_array($element['#value'], array_map('trim', explode("\n", \Drupal::configFactory()->get('vlsuite_icon_font.settings')->get('list'))))) {
      $form_state->setError($element, t('Please choose a valid icon, current ":icon" is not valid.', [':icon' => $element['#value']]));
    }
  }

  /**
   * Build renderable icon.
   *
   * @param string $icon
   *   Icon to build.
   *
   * @return array
   *   Renderable icon font icon.
   */
  public function buildIconFontIcon(string $icon) {
    $icon_classes = explode(' ', $this->configFactory->get('vlsuite_icon_font.settings')->get('main_classes') ?? '');
    $replacement = $this->configFactory->get('vlsuite_icon_font.settings')->get('replacement') ?? NULL;
    $renderable = [
      '#attached' => ['library' => ['vlsuite_icon_font/font']],
      '#theme' => 'vlsuite_icon_font',
      '#attributes' => ['class' => $icon_classes],
    ];
    $is_valid = in_array($icon, array_map('trim', explode("\n", $this->configFactory->get('vlsuite_icon_font.settings')->get('list'))));
    if ($is_valid && $replacement === 'text') {
      $renderable['#icon'] = $icon;
    }
    elseif ($is_valid && $replacement === 'class') {
      $renderable['#attributes']['class'][] = $icon;
    }
    else {
      $renderable = NULL;
    }
    return $renderable;
  }

}
