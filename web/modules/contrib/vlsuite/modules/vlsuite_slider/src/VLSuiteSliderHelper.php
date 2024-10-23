<?php

namespace Drupal\vlsuite_slider;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Helper "VLSuiteSliderHelper" service object.
 */
class VLSuiteSliderHelper {

  use StringTranslationTrait;

  const SLIDER_KEY = 'vlsuite_slider';
  const SLIDER_SCOPE_ALL = 'all';
  const SLIDER_DATA_ATTRIBUTE = 'data-vlsuite-slider';
  const SLIDER_SMALL_SIZE = 768;
  const SLIDER_MEDIUM_SIZE = 1366;
  const SLIDER_AUTO_HEIGHT = TRUE;

  /**
   * Get slider form element.
   *
   * @param array $defaults
   *   Config for default values.
   * @param array $scope_options
   *   Scrope options where to apply (e.g: regions for section).
   *
   * @return array
   *   Form element.
   */
  public function getSliderFormElement(array $defaults, array $scope_options) {
    $element = [
      '#type' => 'details',
      '#title' => $this->t('Slider'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    $element['active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#description' => $this->t('Each scope element will be one slide when active (top & bottom regions excluded).'),
      '#default_value' => $defaults['active'] ?? NULL,
    ];
    $element['scope'] = [
      '#type' => 'select',
      '#title' => $this->t('Scope where to enable'),
      '#options' => $scope_options,
      '#empty_option' => $this->t('- All -'),
      '#empty_value' => 'all',
      '#access' => count($scope_options) > 1,
      '#default_value' => $defaults['scope'] ?? key($scope_options),
    ];
    $element['slides_per_view'] = [
      '#type' => 'number',
      '#min' => 1,
      '#step' => '0.1',
      '#title' => $this->t('Slides per view'),
      '#default_value' => $defaults['slides_per_view'] ?? NULL,
    ];
    $element['slides_per_view_medium'] = [
      '#type' => 'number',
      '#min' => 1,
      '#step' => '0.1',
      '#title' => $this->t('Slides per view (medium screens)'),
      '#description' => $this->t('Leave empty to use same as previous.'),
      '#default_value' => $defaults['slides_per_view_medium'] ?? NULL,
    ];
    $element['slides_per_view_small'] = [
      '#type' => 'number',
      '#min' => 1,
      '#step' => '0.1',
      '#title' => $this->t('Slides per view (small screens)'),
      '#description' => $this->t('Leave empty to use same as previous.'),
      '#default_value' => $defaults['slides_per_view_small'] ?? NULL,
    ];
    $element['rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Rows'),
      '#min' => 1,
      '#description' => $this->t('Number of rows, leave empty to use default (1) with auto height.'),
      '#default_value' => $defaults['rows'] ?? NULL,
    ];
    $element['space_between'] = [
      '#type' => 'number',
      '#title' => $this->t('Space between'),
      '#min' => 1,
      '#description' => $this->t('Space between elements (in px), leave empty to use default (16).'),
      '#default_value' => $defaults['space_between'] ?? NULL,
    ];
    $element['autoplay'] = [
      '#type' => 'number',
      '#title' => $this->t('Autoplay'),
      '#min' => 1,
      '#description' => $this->t('Autoplay in seconds, leave empty to not use. Example: 3.'),
      '#default_value' => $defaults['autoplay'] ?? NULL,
    ];
    $element['navigation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Navigation'),
      '#description' => $this->t('Show navigation prev / next'),
      '#default_value' => $defaults['navigation'] ?? NULL,
    ];
    $element['pagination'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pagination'),
      '#description' => $this->t('Show pagination'),
      '#default_value' => $defaults['pagination'] ?? NULL,
    ];

    return $element;
  }

  /**
   * Get slider form element when submit.
   *
   * @param array $slider_raw_config
   *   Form element result raw.
   *
   * @return array
   *   Form element result.
   */
  public function getSliderFormElementSubmit(array $slider_raw_config) {
    return array_filter($slider_raw_config);
  }

  /**
   * Get slider data attribute value (options serialized from config).
   *
   * @param array $slider_config
   *   Slider config.
   * @param string $scope_selector
   *   Scope selector wher carousel should init.
   * @param string $scope_items_selector
   *   Items selector scope (relative to previous) for slide elements.
   *
   * @return string
   *   Slider opctions serialized.
   */
  public function getSliderDataAttributeValue(array $slider_config, string $scope_selector = ':scope', string $scope_items_selector = ':scope > div') {
    $value = Json::encode($slider_config + [
      'scope_selector' => $scope_selector,
      'scope_items_selector' => $scope_items_selector,
      'medium_size' => self::SLIDER_MEDIUM_SIZE,
      'small_size' => self::SLIDER_SMALL_SIZE,
      'auto_height' => self::SLIDER_AUTO_HEIGHT,
    ]);
    return $value;
  }

  /**
   * Check is active.
   *
   * @param array $slider_config
   *   Slider config.
   *
   * @return bool
   *   Active or not.
   */
  public function isActive(array $slider_config) {
    return $slider_config['active'] ?? FALSE;
  }

  /**
   * Get slider defined scope in config.
   *
   * @param array $slider_config
   *   Slider config.
   *
   * @return string
   *   Scope.
   */
  public function getScope(array $slider_config) {
    $scope = NULL;
    if ($this->isActive($slider_config)) {
      $scope = $slider_config['scope'] ?? self::SLIDER_SCOPE_ALL;
    }
    return $scope;
  }

  /**
   * Attach library.
   *
   * @param array $build
   *   Build.
   * @param array $slider_config
   *   Slider config.
   */
  public function attachLibrary(array &$build, array $slider_config) {
    if ($this->isActive($slider_config)) {
      $build['#attached']['library'][] = 'vlsuite_slider/slider';
    }
  }

}
