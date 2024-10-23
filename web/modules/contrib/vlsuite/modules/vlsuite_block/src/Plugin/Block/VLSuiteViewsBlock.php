<?php

namespace Drupal\vlsuite_block\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\ViewExecutableFactory;
use Drupal\vlsuite_slider\VLSuiteSliderHelper;
use Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the VLSuite block: list (views) plugin type.
 *
 * @Block(
 *   id = "vlsuite_block_views_block",
 *   admin_label = @Translation("VLSuite: Views block"),
 *   category = @Translation("VLSuite: List block (views)"),
 *   deriver = "Drupal\vlsuite_block\Plugin\Derivative\VLSuiteViewsBlockDeriver"
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
final class VLSuiteViewsBlock extends ViewsBlock {

  /**
   * VLSuite Utility classes' helper.
   *
   * @var \Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper
   */
  private $utilityClassesHelper;

  /**
   * The slider helper.
   *
   * @var \Drupal\vlsuite_slider\VLSuiteSliderHelper
   */
  protected $sliderHelper;

  /**
   * Constructs a VLSuiteViewsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views\ViewExecutableFactory $executable_factory
   *   The view executable factory.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The views storage.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper $utility_classes_helper
   *   The utility classes helper.
   * @param \Drupal\vlsuite_slider\VLSuiteSliderHelper $slider_helper
   *   VLSuite slider helper.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ViewExecutableFactory $executable_factory,
    EntityStorageInterface $storage,
    AccountInterface $user,
    VLSuiteUtilityClassesHelper $utility_classes_helper,
    VLSuiteSliderHelper $slider_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $executable_factory, $storage, $user);

    $this->utilityClassesHelper = $utility_classes_helper;
    $this->sliderHelper = $slider_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('views.executable'),
      $container->get('entity_type.manager')->getStorage('view'),
      $container->get('current_user'),
      $container->get('vlsuite_utility_classes.helper'),
      $container->get('vlsuite_slider.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'grid_cols_widths' => NULL,
      VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $type_indicator = (string) $this->t('VLSuite: List block: @subtype', [
      '@subtype' => $this->view->getTitle() . ': ' . $this->view->getDisplay()->definition['title'],
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
    $form['views_label_checkbox']['#default_value'] = FALSE;
    $form['views_label_checkbox']['#type'] = 'hidden';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $slider_config = $this->configuration[VLSuiteSliderHelper::SLIDER_KEY] ?? [];
    $slider_active = $this->sliderHelper->isActive($slider_config);
    $grid_cols_widths = $this->configuration['grid_cols_widths'] ?? NULL;
    if (!empty($grid_cols_widths) && !$slider_active) {
      $style_plugin = $this->view->display_handler->getOption('style');
      if ($style_plugin['type'] !== 'html_list') {
        $style_plugin['type'] = 'html_list';
      }
      $style_plugin['options']['wrapper_class'] = NULL;
      $col_classes = $this->utilityClassesHelper->getColPercentageOptionClasses($grid_cols_widths);
      $style_plugin['options']['row_class'] = implode(' ', $col_classes);
      $style_plugin['options']['default_row_class'] = FALSE;
      $row_classes = $this->utilityClassesHelper->getRowClasses();
      $list_unstyled_classes = $this->utilityClassesHelper->getListUnstyledClasses();
      // 'row list-unstyled'
      $style_plugin['options']['class'] = implode(' ', array_merge($row_classes, $list_unstyled_classes));
      $this->view->display_handler->setOption('style', $style_plugin);
    }
    $build = parent::build();
    if ($slider_active) {
      $slider_scope = $this->sliderHelper->getScope($slider_config);
      $scope_default = $slider_scope === 'default' || $slider_scope === 'all';
      if ($scope_default) {
        $slider_attribute_value = $scope_default ? $this->sliderHelper->getSliderDataAttributeValue($slider_config, ':scope .view-content', ':scope > div') : NULL;
        $build['#attributes'][VLSuiteSliderHelper::SLIDER_DATA_ATTRIBUTE] = $slider_attribute_value;
      }
    }
    $build['#attached']['library'][] = 'vlsuite_block/block';
    $build['#attributes']['class'][] = 'vlsuite-block';
    $build['#attributes']['class'][] = 'vlsuite-block__' . Html::cleanCssIdentifier($this->getDerivativeId());
    $this->sliderHelper->attachLibrary($build, $slider_config);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['grid_cols_widths'] = [
      '#type' => 'select',
      '#title' => $this->t('Grid column widths'),
      '#empty_option' => $this->t('Default'),
      '#default_value' => $this->configuration['grid_cols_widths'],
      '#options' => [
        // @codingStandardsIgnoreStart
        // #options values usually have to run through t() for translation,
        // But because are numbers, in this case is not necessary.
        '100' => '100%',
        '50' => '50%/50%',
        '33' => '33%/34%/33%',
        '25' => '25%/25%/25%/25%',
        // @codingStandardsIgnoreEnd
      ],
      '#description' => $this->t('Choose the grid column widths for this list, make sure @items_per_block matches this setting (e.g: 3 or 6 - 33%/33%/33%)', [
        '@items_per_block' => $this->t('Items per block'),
      ]),
    ];
    $slider_scope_options = [
      'default' => $this->t('Default'),
    ];
    $form[VLSuiteSliderHelper::SLIDER_KEY] = $this->sliderHelper->getSliderFormElement($this->configuration[VLSuiteSliderHelper::SLIDER_KEY] ?? [], $slider_scope_options);
    $form[VLSuiteSliderHelper::SLIDER_KEY]['active']['#description'] = $this->t('Each view result will be one slide when active. Note: "Grid column widths" will not apply when active as it will be defined by slider options.');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['grid_cols_widths'] = $form_state->getValue('grid_cols_widths', '');
    $this->configuration[VLSuiteSliderHelper::SLIDER_KEY] = $this->sliderHelper->getSliderFormElementSubmit($form_state->getValue(VLSuiteSliderHelper::SLIDER_KEY, []));
    parent::blockSubmit($form, $form_state);
  }

}
