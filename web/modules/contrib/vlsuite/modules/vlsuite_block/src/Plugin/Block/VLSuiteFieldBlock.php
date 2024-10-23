<?php

namespace Drupal\vlsuite_block\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Plugin\Block\FieldBlock;
use Drupal\vlsuite_animations\VLSuiteAnimationsHelper;
use Drupal\vlsuite_slider\VLSuiteSliderHelper;
use Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that renders a field from an entity.
 *
 * @Block(
 *   id = "vlsuite_block_field_block",
 *   admin_label = @Translation("VLSuite: Field block"),
 *   category = @Translation("VLSuite: Field blocks"),
 *   deriver = "\Drupal\vlsuite_block\Plugin\Derivative\VLSuiteFieldBlockDeriver",
 * )
 */
final class VLSuiteFieldBlock extends FieldBlock {

  /**
   * The utility classes helper.
   *
   * @var \Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper
   */
  protected $utilityClassesHelper;

  /**
   * Utility classes definitions.
   *
   * @var array
   */
  protected $utilityClassesDefinitions;

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

  /**
   * Constructs a new VLSuiteFieldBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_manager
   *   The formatter manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper $utility_classes_helper
   *   The utility classes' helper.
   * @param \Drupal\vlsuite_slider\VLSuiteSliderHelper $slider_helper
   *   VLSuite slider helper.
   * @param \Drupal\vlsuite_animations\VLSuiteAnimationsHelper $animations_helper
   *   VLSuite animations helper.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityFieldManagerInterface $entity_field_manager,
    FormatterPluginManager $formatter_manager,
    ModuleHandlerInterface $module_handler,
    LoggerInterface $logger,
    VLSuiteUtilityClassesHelper $utility_classes_helper,
    VLSuiteSliderHelper $slider_helper,
    VLSuiteAnimationsHelper $animations_helper
  ) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager, $formatter_manager, $module_handler, $logger);

    $this->utilityClassesHelper = $utility_classes_helper;
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
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.formatter'),
      $container->get('module_handler'),
      $container->get('logger.channel.layout_builder'),
      $container->get('vlsuite_utility_classes.helper'),
      $container->get('vlsuite_slider.helper'),
      $container->get('vlsuite_animations.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'formatter' => [
        'label' => 'hidden',
        'type' => $this->pluginDefinition['default_formatter'],
        'settings' => [],
        'third_party_settings' => [],
      ],
      VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY => [],
      VLSuiteAnimationsHelper::ANIMATIONS_KEY => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $type_indicator = (string) $this->t('VLSuite: Field block: @subtype', [
      '@subtype' => $this->getFieldDefinition()->getLabel(),
    ]);
    $form['type_indicator'] = [
      '#type' => 'html_tag',
      '#tag' => 'strong',
      '#value' => $type_indicator,
    ];
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['admin_label']['#access'] = FALSE;
    $form['label_display']['#type'] = 'hidden';
    $form['label_display']['#default_value'] = FALSE;
    $form['label']['#type'] = 'hidden';
    if (!empty($form['formatter']['type']['#options']) && count($form['formatter']['type']['#options']) == 1 && !empty($form['formatter']['type']['#default_value'])) {
      $form['formatter']['type']['#type'] = 'hidden';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $this->utilityClassesHelper->buildApplyUtilityClasses($this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] ?? [], $build);
    $slider_config = $this->configuration[VLSuiteSliderHelper::SLIDER_KEY] ?? [];
    $slider_active = $this->sliderHelper->isActive($slider_config);
    if ($slider_active) {
      $slider_scope = $this->sliderHelper->getScope($slider_config);
      $scope_default = $slider_scope === 'default' || $slider_scope === 'all';
      if ($scope_default) {
        $slider_attribute_value = $scope_default ? $this->sliderHelper->getSliderDataAttributeValue($slider_config, ':scope > .field__items', ':scope > .field__item') : NULL;
        $build['#attributes'][VLSuiteSliderHelper::SLIDER_DATA_ATTRIBUTE] = $slider_attribute_value;
      }
    }
    $this->sliderHelper->attachLibrary($build, $slider_config);
    $animations_config = $this->configuration[VLSuiteAnimationsHelper::ANIMATIONS_KEY] ?? [];
    if ($this->animationsHelper->isActive($animations_config)) {
      $animations_attribute_value = $this->animationsHelper->getAnimationsDataAttributeValue($animations_config);
      $build['#attributes'][VLSuiteAnimationsHelper::ANIMATIONS_DATA_ATTRIBUTE] = $animations_attribute_value;
      $this->animationsHelper->attachLibrary($build, $animations_config);
    }
    $build['#attached']['library'][] = 'vlsuite_block/block';
    $build['#attributes']['class'][] = 'vlsuite-block';
    $build['#attributes']['class'][] = 'vlsuite-block__' . Html::cleanCssIdentifier($this->getDerivativeId());
    if ($this->inPreview) {
      $this->setUtilityClassesDefinitions(_vlsuite_block_utility_classes_apply_to_enabled_definitions($this->entityTypeId, $this->bundle, $this->fieldName));
      $this->utilityClassesHelper->buildLivePreviewer($this->getUtilityClassesDefinitions(), $build, $this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] ?? []);
    }
    return $build;
  }

  /**
   * Get utility classes definitions.
   *
   * @return array
   *   Definitions.
   */
  public function getUtilityClassesDefinitions() {
    return $this->utilityClassesDefinitions ?? [];
  }

  /**
   * Set utility classes definitions.
   *
   * @param array $definitions
   *   Definitions.
   */
  public function setUtilityClassesDefinitions(array $definitions) {
    $this->utilityClassesDefinitions = $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    if ($this->isMultiple()) {
      $slider_scope_options = [
        'default' => $this->t('Default'),
      ];
      $form[VLSuiteSliderHelper::SLIDER_KEY] = $this->sliderHelper->getSliderFormElement($this->configuration[VLSuiteSliderHelper::SLIDER_KEY] ?? [], $slider_scope_options);
      $form[VLSuiteSliderHelper::SLIDER_KEY]['active']['#description'] = $this->t('Each item will be one slide when active.');
    }
    $form[VLSuiteAnimationsHelper::ANIMATIONS_KEY] = $this->animationsHelper->getAnimationsFormElement($this->configuration[VLSuiteAnimationsHelper::ANIMATIONS_KEY] ?? [], []);
    $this->setUtilityClassesDefinitions(_vlsuite_block_utility_classes_apply_to_enabled_definitions($this->entityTypeId, $this->bundle, $this->fieldName));
    $form[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] = $this->utilityClassesHelper->getUtilitiesApplyToListFormElement($this->getUtilityClassesDefinitions(), $this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] ?? []);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] = $this->utilityClassesHelper->getUtilitiesApplyToListFormElementSubmit($form_state->getValue(VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY, []));
    if ($this->isMultiple()) {
      $this->configuration[VLSuiteSliderHelper::SLIDER_KEY] = $this->sliderHelper->getSliderFormElementSubmit($form_state->getValue(VLSuiteSliderHelper::SLIDER_KEY, []));
    }
    $this->configuration[VLSuiteAnimationsHelper::ANIMATIONS_KEY] = $this->animationsHelper->getAnimationsFormElementSubmit($form_state->getValue(VLSuiteAnimationsHelper::ANIMATIONS_KEY, []));
    parent::blockSubmit($form, $form_state);
  }

  /**
   * Field is multiple.
   *
   * @return bool
   *   Is multiple.
   */
  private function isMultiple() {
    return ($this->getFieldDefinition() instanceof FieldStorageDefinitionInterface && $this->getFieldDefinition()->isMultiple()) ||
      ($this->getFieldDefinition() instanceof FieldDefinitionInterface && $this->getFieldDefinition()->getFieldStorageDefinition() instanceof FieldStorageDefinitionInterface && $this->getFieldDefinition()->getFieldStorageDefinition()->isMultiple());
  }

}
