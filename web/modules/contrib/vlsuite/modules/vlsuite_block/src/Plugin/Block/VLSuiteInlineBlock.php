<?php

namespace Drupal\vlsuite_block\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\Plugin\Block\InlineBlock;
use Drupal\vlsuite_animations\VLSuiteAnimationsHelper;
use Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the VLSuite block: inline block plugin type.
 *
 * @Block(
 *  id = "vlsuite_block_inline_block",
 *  admin_label = @Translation("VLSuite: Inline block"),
 *  category = @Translation("VLSuite: Inline blocks"),
 *  deriver = "Drupal\layout_builder\Plugin\Derivative\InlineBlockDeriver",
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
final class VLSuiteInlineBlock extends InlineBlock {

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
   * The animations helper.
   *
   * @var \Drupal\vlsuite_animations\VLSuiteAnimationsHelper
   */
  protected $animationsHelper;

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
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper $utility_classes_helper
   *   The utility classes helper.
   * @param \Drupal\vlsuite_animations\VLSuiteAnimationsHelper $animations_helper
   *   VLSuite animations helper.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    AccountInterface $current_user,
    VLSuiteUtilityClassesHelper $utility_classes_helper,
    VLSuiteAnimationsHelper $animations_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_display_repository, $current_user);

    $this->utilityClassesHelper = $utility_classes_helper;
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
      $container->get('entity_display.repository'),
      $container->get('current_user'),
      $container->get('vlsuite_utility_classes.helper'),
      $container->get('vlsuite_animations.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY => [],
      VLSuiteAnimationsHelper::ANIMATIONS_KEY => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $type_indicator = (string) $this->t('VLSuite: Block: @subtype', [
      '@subtype' => $this->entityTypeManager->getStorage($this->getEntity()->getEntityType()->getBundleEntityType())->load($this->getEntity()->bundle())->label(),
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
    $form['label']['#default_value'] = $type_indicator;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $is_layout_builder_display = $this->entityDisplayRepository->getViewDisplay('block_content', $this->getEntity()->bundle(), $this->configuration['view_mode'] ?? EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE)->isLayoutBuilderEnabled();
    if (!$is_layout_builder_display) {
      $this->utilityClassesHelper->buildApplyUtilityClasses($this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] ?? [], $build);
    }
    $animations_config = $this->configuration[VLSuiteAnimationsHelper::ANIMATIONS_KEY] ?? [];
    if ($this->animationsHelper->isActive($animations_config)) {
      $animations_attribute_value = $this->animationsHelper->getAnimationsDataAttributeValue($animations_config);
      $build['#attributes'][VLSuiteAnimationsHelper::ANIMATIONS_DATA_ATTRIBUTE] = $animations_attribute_value;
      $this->animationsHelper->attachLibrary($build, $animations_config);
    }
    $build['#attached']['library'][] = 'vlsuite_block/block';
    $build['#attributes']['class'][] = 'vlsuite-block';
    $build['#attributes']['class'][] = 'vlsuite-block__' . Html::cleanCssIdentifier($this->getDerivativeId());
    if ($this->inPreview && !$is_layout_builder_display) {
      $this->setUtilityClassesDefinitions(_vlsuite_block_utility_classes_apply_to_enabled_definitions($this->getEntity()->getEntityTypeId(), $this->getEntity()->bundle()));
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
    $form[VLSuiteAnimationsHelper::ANIMATIONS_KEY] = $this->animationsHelper->getAnimationsFormElement($this->configuration[VLSuiteAnimationsHelper::ANIMATIONS_KEY] ?? [], []);
    if (!$this->entityDisplayRepository->getViewDisplay('block_content', $this->getEntity()->bundle(), $this->configuration['view_mode'] ?? EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE)->isLayoutBuilderEnabled()) {
      $this->setUtilityClassesDefinitions(_vlsuite_block_utility_classes_apply_to_enabled_definitions($this->getEntity()->getEntityTypeId(), $this->getEntity()->bundle()));
      $form[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] = $this->utilityClassesHelper->getUtilitiesApplyToListFormElement($this->getUtilityClassesDefinitions(), $this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] ?? []);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    if (!$this->entityDisplayRepository->getViewDisplay('block_content', $this->getEntity()->bundle(), $this->configuration['view_mode'] ?? EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE)->isLayoutBuilderEnabled()) {
      $this->configuration[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] = $this->utilityClassesHelper->getUtilitiesApplyToListFormElementSubmit($form_state->getValue(VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY, []));
    }
    $this->configuration[VLSuiteAnimationsHelper::ANIMATIONS_KEY] = $this->animationsHelper->getAnimationsFormElementSubmit($form_state->getValue(VLSuiteAnimationsHelper::ANIMATIONS_KEY, []));
    parent::blockSubmit($form, $form_state);
  }

}
