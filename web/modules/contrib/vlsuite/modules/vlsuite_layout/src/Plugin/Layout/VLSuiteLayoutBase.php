<?php

namespace Drupal\vlsuite_layout\Plugin\Layout;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Template\Attribute;
use Drupal\media\MediaInterface;
use Drupal\vlsuite_animations\VLSuiteAnimationsHelper;
use Drupal\vlsuite_layout\VLSuiteLayoutMediaBgFieldTrait;
use Drupal\vlsuite_slider\VLSuiteSliderHelper;
use Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * VLSuite Layout base.
 */
abstract class VLSuiteLayoutBase extends LayoutDefault implements PluginFormInterface, ContainerFactoryPluginInterface {

  use VLSuiteLayoutMediaBgFieldTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The utility classes helper.
   *
   * @var \Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper
   */
  protected $utilityClassHelper;

  /**
   * The slider helper.
   *
   * @var \Drupal\vlsuite_slider\VLSuiteSliderHelper
   */
  protected $sliderHelper;

  /**
   * The animations helper.
   *
   * @var \Drupal\vlsuite_animations\VLSuiteAnimationsHelper
   */
  protected $animationsHelper;

  const APPLY_TO_SECTION = 'vlsuite_layout:section';
  const APPLY_TO_MEDIA_BG = 'vlsuite_layout:media_bg';
  const APPLY_TO_ROW = 'vlsuite_layout:row';
  const APPLY_TO_REGION_TOP = 'vlsuite_layout:region_top';
  const APPLY_TO_MAIN_REGIONS = 'vlsuite_layout:main_regions';
  const APPLY_TO_REGION_BOTTOM = 'vlsuite_layout:region_bottom';

  /**
   * Constructs a new VLSuiteInlineBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory.
   * @param \Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper $utility_classes_helper
   *   VLSuite Utility classes helper.
   * @param \Drupal\vlsuite_slider\VLSuiteSliderHelper $slider_helper
   *   VLSuite slider helper.
   * @param \Drupal\vlsuite_animations\VLSuiteAnimationsHelper $animations_helper
   *   VLSuite animations helper.
   */
  final public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactory $config_factory,
    VLSuiteUtilityClassesHelper $utility_classes_helper,
    VLSuiteSliderHelper $slider_helper,
    VLSuiteAnimationsHelper $animations_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->utilityClassHelper = $utility_classes_helper;
    $this->sliderHelper = $slider_helper;
    $this->animationsHelper = $animations_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('vlsuite_utility_classes.helper'),
      $container->get('vlsuite_slider.helper'),
      $container->get('vlsuite_animations.helper')
    );
  }

  /**
   * Get utility classes definitions.
   *
   * @return array
   *   Definitions.
   */
  public static function getUtilityClassesDefinitions() {
    $utility_classes_definitions = [];
    $utility_classes_definitions[self::APPLY_TO_MEDIA_BG] = [
      'admin_title' => t('VLSuite - Media background'),
      'form_element_title' => t('Media background'),
    ];
    $utility_classes_definitions[self::APPLY_TO_SECTION] = [
      'admin_title' => t('VLSuite - Section (layout)'),
      'form_element_title' => t('Section'),
    ];
    $utility_classes_definitions[self::APPLY_TO_ROW] = [
      'admin_title' => t('VLSuite - All regions (layout)'),
      'form_element_title' => t('All regions'),
    ];
    $utility_classes_definitions[self::APPLY_TO_REGION_TOP] = [
      'admin_title' => t('VLSuite - Region top (layout)'),
      'form_element_title' => t('Region top'),
    ];
    $utility_classes_definitions[self::APPLY_TO_MAIN_REGIONS] = [
      'admin_title' => t('VLSuite - Main regions (layout)'),
      'form_element_title' => t('Main regions'),
    ];
    $utility_classes_definitions[self::APPLY_TO_REGION_BOTTOM] = [
      'admin_title' => t('VLSuite - Region bottom (layout)'),
      'form_element_title' => t('Region bottom'),
    ];
    return $utility_classes_definitions;
  }

  /**
   * Get width options.
   *
   * @return array
   *   Width options.
   */
  abstract protected function getWidthOptions();

  /**
   * Get default width.
   *
   * @return string
   *   Default width.
   */
  abstract protected function getDefaultWidth();

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['column_widths'] = $this->getDefaultWidth();
    $configuration['identifier'] = NULL;
    $configuration['edge_to_edge_bg'] = TRUE;
    $configuration['optional_regions'] = TRUE;
    $configuration[VLSuiteSliderHelper::SLIDER_KEY] = [];
    $configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] = [];
    $configuration[VLSuiteAnimationsHelper::ANIMATIONS_KEY] = [];
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $width_options = $this->getWidthOptions();
    $form['column_widths'] = [
      '#type' => count($width_options) > 1 ? 'select' : 'value',
      '#title' => $this->t('Main column widths'),
      '#default_value' => $this->configuration['column_widths'],
      '#options' => $this->getWidthOptions(),
      '#description' => $this->t('Choose the main column widths for this layout.'),
    ];
    $form['optional_regions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show optional regions'),
      '#description' => $this->t('Show or not optional regions top & bottom. NOTE: Any related region content will not be deleted, just not shown when disabled.'),
      '#default_value' => $this->configuration['optional_regions'] ?? NULL,
    ];
    $form['edge_to_edge'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Edge to edge'),
      '#default_value' => $this->configuration['edge_to_edge'] ?? NULL,
    ];
    $form['edge_to_edge_bg'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Edge to edge background'),
      '#default_value' => $this->configuration['edge_to_edge_bg'] ?? NULL,
    ];
    $media_bg_bundles = array_keys($this->configFactory->get('vlsuite_media.settings')->get('bg_types') ?? []);
    $form['media_bg'] = [
      '#type' => !empty($media_bg_bundles) ? 'media_library' : 'hidden',
      '#allowed_bundles' => $media_bg_bundles,
      '#title' => $this->t('Media background (Image / video)'),
      '#default_value' => $this->configuration['media_bg'] ?? NULL,
    ];
    $slider_scope_options = array_filter(array_combine($this->getPluginDefinition()->getRegionNames(), $this->getPluginDefinition()->getRegionLabels()), function ($region_key) {
        return $region_key !== 'top' && $region_key !== 'bottom';
    }, ARRAY_FILTER_USE_KEY);
    $form[VLSuiteSliderHelper::SLIDER_KEY] = $this->sliderHelper->getSliderFormElement($this->configuration[VLSuiteSliderHelper::SLIDER_KEY] ?? [], $slider_scope_options);
    $form[VLSuiteSliderHelper::SLIDER_KEY]['active']['#description'] = $this->t('Each block will be one slide when active (top & bottom regions excluded).');
    $form[VLSuiteSliderHelper::SLIDER_KEY]['scope']['#title'] = $this->t('Main region/s where to enable');
    $form[VLSuiteAnimationsHelper::ANIMATIONS_KEY] = $this->animationsHelper->getAnimationsFormElement($this->configuration[VLSuiteAnimationsHelper::ANIMATIONS_KEY] ?? [], []);
    $form[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] = $this->utilityClassHelper->getUtilitiesApplyToListFormElement(self::getUtilityClassesDefinitions(), $this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] ?? []);
    $form['identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Identifier'),
      '#description' => $this->t('Section identifier for anchor linking'),
      '#default_value' => $this->configuration['identifier'] ?? NULL,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['optional_regions'] = $form_state->getValue('optional_regions');
    $this->configuration['column_widths'] = $form_state->getValue('column_widths');
    $this->configuration['edge_to_edge'] = $form_state->getValue('edge_to_edge');
    $this->configuration['media_bg'] = $form_state->getValue('media_bg');
    $this->configuration['edge_to_edge_bg'] = $form_state->getValue('edge_to_edge_bg');
    $this->configuration['identifier'] = $form_state->getValue('identifier');
    $this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] = $this->utilityClassHelper->getUtilitiesApplyToListFormElementSubmit($form_state->getValue(VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY, []));
    $this->configuration[VLSuiteSliderHelper::SLIDER_KEY] = $this->sliderHelper->getSliderFormElementSubmit($form_state->getValue(VLSuiteSliderHelper::SLIDER_KEY, []));
    $this->configuration[VLSuiteAnimationsHelper::ANIMATIONS_KEY] = $this->animationsHelper->getAnimationsFormElementSubmit($form_state->getValue(VLSuiteAnimationsHelper::ANIMATIONS_KEY, []));
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    if (empty($build['#attributes']) || !$build['#attributes'] instanceof Attribute) {
      $build['#attributes'] = new Attribute($build['#attributes'] ?? []);
    }
    $build['#attributes']->addClass('vlsuite-layout');
    $build['#attributes']->addClass(Html::cleanCssIdentifier($this->getPluginDefinition()->id()));
    $identifier = $this->configuration['identifier'];
    if (!empty($identifier)) {
      $build['#attributes']->setAttribute('id', $identifier);
    }
    $edge_to_edge = $this->configuration['edge_to_edge'] ?? FALSE;
    if (!$edge_to_edge) {
      $build['#attributes']->addClass($this->utilityClassHelper->getContainerClasses());
    }

    $section_utility_clases_config = $this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY][self::APPLY_TO_SECTION] ?? [];

    $section_utility_classes = $this->utilityClassHelper->getUtilitiesApplyToClasses(self::APPLY_TO_SECTION, $section_utility_clases_config);
    $section_classes = array_merge([
      'vlsuite-layout-bg-wrapper',
      Html::cleanCssIdentifier($this->getPluginDefinition()->id() . '-bg-wrapper'),
    ], $section_utility_classes);

    $edge_to_edge_bg = $this->configuration['edge_to_edge_bg'] ?? NULL;
    if (!empty($edge_to_edge_bg)) {
      if (empty($build['#theme_wrappers']['container']['#attributes']) || !$build['#theme_wrappers']['container']['#attributes'] instanceof Attribute) {
        $build['#theme_wrappers']['container']['#attributes'] = new Attribute($build['#theme_wrappers']['container']['#attributes'] ?? []);
      }
      $build['#theme_wrappers']['container']['#attributes']->addClass($section_classes);
      if ($this->inPreview) {
        $this->utilityClassHelper->applyLivePreviewerAttributes($build['#theme_wrappers']['container']['#attributes'], self::APPLY_TO_SECTION, $section_utility_clases_config);
      }
    }
    else {
      $build['#attributes']->addClass($section_classes);
      if ($this->inPreview) {
        $this->utilityClassHelper->applyLivePreviewerAttributes($build['#attributes'], self::APPLY_TO_SECTION, $section_utility_clases_config);
      }
    }
    $media_bg_field = $this->getMediaBgFieldId($regions);
    $media_bg = $media_bg_field !== 0 ? $media_bg_field : ($this->configuration['media_bg'] ?? NULL);
    if (!empty($media_bg)) {
      $look_up_for_media = $this->entityTypeManager->getStorage('media')->load($media_bg);
      if ($look_up_for_media instanceof MediaInterface) {
        $media_bg_utility_clases_config = $this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY][self::APPLY_TO_MEDIA_BG] ?? [];
        $media_bg_utility_classes = $this->utilityClassHelper->getUtilitiesApplyToClasses(self::APPLY_TO_MEDIA_BG, $media_bg_utility_clases_config);
        $build['media_bg'] = ['#type' => 'container'];
        $build['media_bg']['media'] = $this->entityTypeManager->getViewBuilder('media')->view($look_up_for_media, 'vlsuite_background');
        $build['media_bg']['#attributes']['class'][] = 'vlsuite-layout-bg-wrapper__bg-media';
        $build['media_bg']['#attributes']['class'] = array_merge($build['media_bg']['#attributes']['class'], $media_bg_utility_classes);
        if ($this->inPreview) {
          $this->utilityClassHelper->applyLivePreviewerAttributes($build['media_bg']['#attributes'], self::APPLY_TO_MEDIA_BG, $media_bg_utility_clases_config);
        }
      }
    }

    $build['#row_attributes'] = new Attribute();
    $build['#row_attributes']->addClass($this->utilityClassHelper->getRowClasses());
    $row_utility_clases_config = $this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY][self::APPLY_TO_ROW] ?? [];
    $row_utility_classes = $this->utilityClassHelper->getUtilitiesApplyToClasses(self::APPLY_TO_ROW, $row_utility_clases_config);
    $build['#row_attributes']->addClass($row_utility_classes);

    if ($this->inPreview) {
      $this->utilityClassHelper->applyLivePreviewerAttributes($build['#row_attributes'], self::APPLY_TO_ROW, $row_utility_clases_config);
    }

    $top_utility_classes_config = $this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY][self::APPLY_TO_REGION_TOP] ?? [];
    $top_utility_classes = $this->utilityClassHelper->getUtilitiesApplyToClasses(self::APPLY_TO_REGION_TOP, $top_utility_classes_config);
    $col_100_classes = $this->utilityClassHelper->getColPercentageOptionClasses('100');
    $top_classes = $top_utility_classes;

    $main_utility_classes_config = $this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY][self::APPLY_TO_MAIN_REGIONS] ?? [];
    $main_utility_classes = $this->utilityClassHelper->getUtilitiesApplyToClasses(static::APPLY_TO_MAIN_REGIONS, $main_utility_classes_config);
    $main_classes = $main_utility_classes;

    $bottom_utility_classes_config = $this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY][self::APPLY_TO_REGION_BOTTOM] ?? [];
    $bottom_utility_classes = $this->utilityClassHelper->getUtilitiesApplyToClasses(self::APPLY_TO_REGION_BOTTOM, $bottom_utility_classes_config);
    $bottom_classes = $bottom_utility_classes;

    $column_widths = $this->configuration['column_widths'];
    $column_widths_items = explode('-', $column_widths);
    $auto = in_array('auto', $column_widths_items);
    $auto_classes = $this->utilityClassHelper->getColPercentageOptionClasses('auto');

    $slider_config = $this->configuration[VLSuiteSliderHelper::SLIDER_KEY] ?? [];
    $slider_scope = $this->sliderHelper->getScope($slider_config);
    $slider_attribute_value = $slider_scope ? $this->sliderHelper->getSliderDataAttributeValue($slider_config, ':scope > .vlsuite-layout__region', ':scope > .vlsuite-block') : NULL;
    foreach ($this->getPluginDefinition()->getRegionNames() as $region_name) {
      if ($region_name == 'top') {
        $build['#' . $region_name . '_col_attributes'] = new Attribute(['class' => $col_100_classes]);
        $build['#' . $region_name . '_class'] = $top_classes;
        if ($this->inPreview) {
          $this->utilityClassHelper->applyLivePreviewerAttributes($build['#' . $region_name . '_col_attributes'], self::APPLY_TO_REGION_TOP, $top_utility_classes_config);
        }
      }
      elseif ($region_name == 'bottom') {
        $build['#' . $region_name . '_col_attributes'] = new Attribute(['class' => $col_100_classes]);
        $build['#' . $region_name . '_class'] = $bottom_classes;
        if ($this->inPreview) {
          $this->utilityClassHelper->applyLivePreviewerAttributes($build['#' . $region_name . '_col_attributes'], self::APPLY_TO_REGION_BOTTOM, $bottom_utility_classes_config);
        }
      }
      else {
        $build['#' . $region_name . '_col_attributes'] = new Attribute([
          'class' => $this->utilityClassHelper->getColPercentageOptionClasses(array_shift($column_widths_items)),
        ]);
        $build['#' . $region_name . '_col_attributes']->addClass($auto ? $auto_classes : []);
        $build['#' . $region_name . '_class'] = $main_classes;
        if ($this->inPreview) {
          $this->utilityClassHelper->applyLivePreviewerAttributes($build['#' . $region_name . '_col_attributes'], self::APPLY_TO_MAIN_REGIONS, [$this->getBaseId() . ':column_widths' => $column_widths] + $main_utility_classes_config, [$this->getBaseId() . ':column_widths' => TRUE]);
        }
        if ($slider_scope && ($slider_scope === VLSuiteSliderHelper::SLIDER_SCOPE_ALL || $slider_scope === $region_name)) {
          $build['#' . $region_name . '_col_attributes']->setAttribute(VLSuiteSliderHelper::SLIDER_DATA_ATTRIBUTE, $slider_attribute_value);
        }
      }
    }
    $build['#attached']['library'][] = 'vlsuite_layout/layout';
    if ($this->inPreview) {
      $build['#attached']['drupalSettings']['vlsuite_utility_classes_map'][$this->getBaseId() . ':column_widths'] = [
        'visual_name' => $this->t('Column widths'),
        'values' => $this->getWidthOptions(),
      ];
    }
    $animations_config = $this->configuration[VLSuiteAnimationsHelper::ANIMATIONS_KEY] ?? [];
    if ($this->animationsHelper->isActive($animations_config)) {
      $animations_attribute_value = $this->animationsHelper->getAnimationsDataAttributeValue($animations_config);
      if (!empty($edge_to_edge_bg)) {
        $build['#theme_wrappers']['container']['#attributes']->setAttribute(VLSuiteAnimationsHelper::ANIMATIONS_DATA_ATTRIBUTE, $animations_attribute_value);
      }
      else {
        $build['#attributes']->setAttribute(VLSuiteAnimationsHelper::ANIMATIONS_DATA_ATTRIBUTE, $animations_attribute_value);
      }
      $this->animationsHelper->attachLibrary($build, $animations_config);
    }
    $this->sliderHelper->attachLibrary($build, $slider_config);
    return $build;
  }

}
