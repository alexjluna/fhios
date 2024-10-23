<?php

namespace Drupal\vlsuite_animations;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Helper "VLSuiteAnimationsHelper" service object.
 */
class VLSuiteAnimationsHelper {

  use StringTranslationTrait;

  const ANIMATIONS_KEY = 'vlsuite_animations';
  const ANIMATIONS_DATA_ATTRIBUTE = 'data-vlsuite-animations';
  const USE_ANIMATIONS_PERM = 'use vlsuite animations';

  /**
   * Animation apply at options.
   *
   * @var array
   */
  protected $animationApplyAtOptions;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a "VLSuiteAnimationsHelper" object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory instance.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Get animations map classes.
   *
   * @return array
   *   Animations map classes by identifiers.
   */
  public function getAnimationsMapClasses() {
    $animations_config = $this->configFactory->get('vlsuite_animations.settings')->get('animations');
    $animations_map = [];
    foreach ($animations_config as $animation_identifier => $animation) {
      $animations_map[$animation_identifier] = $animation['classes'];
    }
    return [
      'is_playing_classes' => $this->configFactory->get('vlsuite_animations.settings')->get('is_playing_classes'),
      'is_shown_classes' => $this->configFactory->get('vlsuite_animations.settings')->get('is_shown_classes'),
      'infinite_classes' => $this->configFactory->get('vlsuite_animations.settings')->get('infinite_classes'),
      'main_classes' => $this->configFactory->get('vlsuite_animations.settings')->get('main_classes'),
      'root_margin' => $this->configFactory->get('vlsuite_animations.settings')->get('root_margin'),
      'threshold' => $this->configFactory->get('vlsuite_animations.settings')->get('threshold'),
      'animations' => $animations_map,
    ];
  }

  /**
   * Get animations form element.
   *
   * @param array $defaults
   *   Config for default values.
   * @param array $scope_options
   *   Scrope options where to apply (e.g: regions for section).
   *
   * @return array
   *   Form element.
   */
  public function getAnimationsFormElement(array $defaults, array $scope_options) {
    $element = [
      '#type' => 'details',
      '#title' => $this->t('Animations'),
      '#description' => $this->t('Use wisely to avoid browser low performance, especially with infinite animations.'),
      '#attached' => [
        'library' => ['vlsuite_animations/previewer'],
        'drupalSettings' => [
          'vlsuite_animations_map' => $this->getAnimationsMapClasses(),
        ],
      ],
      '#attributes' => ['class' => ['vlsuite-animations']],
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    $element['previewer'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['vlsuite-animations__previewer']],
      'text' => ['#markup' => $this->t('Previewer')],
    ];
    $has_any = FALSE;
    $apply_at_options = $this->getAnimationApplyAtOptions();
    foreach ($apply_at_options as $apply_at_key => $definition) {
      $apply_at_list = $this->getAnimationsApplyAtList($apply_at_key);
      $options = [];
      foreach ($apply_at_list as $apply_at_option_key => $apply_at_option_config) {
        $options[$apply_at_option_key] = $apply_at_option_config['visual_name'];
      }
      $element[$apply_at_key] = [
        '#type' => 'select',
        '#title' => $definition['title'],
        '#description' => $definition['description'],
        '#access' => !empty($options),
        '#options' => $options,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $defaults[$apply_at_key] ?? NULL,
        '#attributes' => [
          'class' => ['vlsuite-animations__previewer-option'],
        ],
      ];
      $has_any = $has_any ? $has_any : !empty($options);
    }
    $element['#access'] = $has_any;

    return $element;
  }

  /**
   * Get animations form element when submit.
   *
   * @param array $animations_raw_config
   *   Form element result raw.
   *
   * @return array
   *   Form element result.
   */
  public function getAnimationsFormElementSubmit(array $animations_raw_config) {
    return array_filter($animations_raw_config);
  }

  /**
   * Get animations data attribute value (options serialized from config).
   *
   * @param array $animations_config
   *   Slider config.
   *
   * @return string
   *   Animations options serialized.
   */
  public function getAnimationsDataAttributeValue(array $animations_config) {
    $options = [];
    if (!empty($animations_config['entrance'])) {
      $options = ['entrance' => $animations_config['entrance']];
    }
    elseif (!empty($animations_config['infinite'])) {
      $options = ['infinite' => $animations_config['infinite']];
    }
    else {
      $options = $animations_config;
    }
    $value = Json::encode($options);
    return $value;
  }

  /**
   * Check is active.
   *
   * @param array $animations_config
   *   Slider config.
   *
   * @return bool
   *   Active or not.
   */
  public function isActive(array $animations_config) {
    return !empty($animations_config);
  }

  /**
   * Attach library.
   *
   * @param array $build
   *   Build.
   * @param array $animations_config
   *   Slider config.
   */
  public function attachLibrary(array &$build, array $animations_config) {
    if ($this->isActive($animations_config)) {
      $build['#attached']['drupalSettings']['vlsuite_animations_map'] = $this->getAnimationsMapClasses();
      $build['#attached']['library'][] = 'vlsuite_animations/animations';
    }
  }

  /**
   * Get animation apply at options.
   *
   * @return array
   *   Options.
   */
  public function getAnimationApplyAtOptions() {
    if (empty($this->animationApplyAtOptions)) {
      $apply_at_options = [];
      $this->moduleHandler->alter('vlsuite_animations_animation_apply_at_options', $apply_at_options);
      $this->animationApplyAtOptions = $apply_at_options;
    }
    return $this->animationApplyAtOptions;
  }

  /**
   * Get animations apply at list.
   *
   * @param string $apply_at
   *   Apply at.
   *
   * @return array
   *   Animations for passed apply at.
   */
  public function getAnimationsApplyAtList(string $apply_at) {
    $animations = $this->configFactory->get('vlsuite_animations.settings')->get('animations');
    $animations_apply_at = [];
    foreach ($animations as $animation_identifier => $animation) {
      if (!empty($animation['apply_at'][$apply_at])) {
        unset($animation['apply_at']);
        $animations_apply_at[$animation_identifier] = $animation;
      }
    }
    return $animations_apply_at;
  }

}
