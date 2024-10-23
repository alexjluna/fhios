<?php

namespace Drupal\vlsuite_layout_tabs\Plugin\Layout;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vlsuite_layout\Plugin\Layout\VLSuiteLayoutBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * VLSuite Layout tabs base.
 */
abstract class VLSuiteLayoutTabsBase extends VLSuiteLayoutBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $tabs_base = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('vlsuite_utility_classes.helper'),
      $container->get('vlsuite_slider.helper'),
      $container->get('vlsuite_animations.helper')
    );
    $tabs_base->setPluginDefinitionRegions($configuration['tabs'] ?? []);
    $tabs_base->pluginDefinition->setIconMap($tabs_base->getPluginDefinitionIconMap());
    return $tabs_base;
  }

  /**
   * Get plugin definition icon map.
   *
   * @return array
   *   Icon map.
   */
  abstract protected function getPluginDefinitionIconMap();

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions() {
    return [
      '100' => '100%',
      '80-auto' => '80%',
      '60-auto' => '60%',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultWidth() {
    return '100';
  }

  /**
   * Get default tabs definition.
   *
   * @return array
   *   Default tabs definition.
   */
  protected function getDefaultTabs() {
    return [
      'tab_0' => [
        'title' => (string) $this->t('First'),
      ],
      'tab_1' => [
        'title' => (string) $this->t('Second'),
      ],
    ];
  }

  /**
   * Get default tab.
   *
   * @return string
   *   Default tab.
   */
  protected function getDefaultTab() {
    return 'tab_0';
  }

  /**
   * Get tabs definition.
   *
   * @return array
   *   Tabs definition.
   */
  protected function getTabsDefinition() {
    return $this->configuration['tabs'] ?? $this->getDefaultTabs();
  }

  /**
   * Set plugin definition regions.
   *
   * @param array $tabs
   *   Tabs to set as regions.
   */
  protected function setPluginDefinitionRegions(array $tabs = []) {
    $regionMap = [];
    $regionMap['top'] = ['label' => $this->t('Top')];
    $tabs_loop = !empty($tabs) ? $tabs : $this->getTabsDefinition();
    foreach ($tabs_loop as $tab_index => $tab) {
      $regionMap[$tab_index] = ['label' => $tab['title']];
    }
    $regionMap['bottom'] = ['label' => $this->t('Bottom')];
    $this->pluginDefinition->setRegions($regionMap);
    $this->pluginDefinition->setDefaultRegion('tab_1');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration() + [
      'tabs' => $this->getDefaultTabs(),
      'tabs_default' => $this->getDefaultTab(),
    ];
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $tabs = $form_state->getCompleteFormState()->getValue(['layout_settings', 'tabs'], $this->getTabsDefinition());
    $default_tabs_options = [];
    foreach ($tabs as $tab_index => $tab) {
      $default_tabs_options[$tab_index] = $tab['title'];
    }
    $form['tabs_default'] = [
      '#type' => 'select',
      '#title' => $this->t('Select default tab to show'),
      '#description' => $this->t('At least two tabs are mandatory, when removal blocks will be preserved per position (always preserve region blocks from beginning).'),
      '#default_value' => isset($tabs[$this->configuration['tabs_default']]) ? $this->configuration['tabs_default'] : 'tab_0',
      '#required' => TRUE,
      '#weight' => -2,
      '#options' => $default_tabs_options,
    ];
    $form['tabs'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#weight' => -1,
      '#attributes' => ['id' => 'tabs-wrapper'],
    ];
    foreach ($tabs as $tab_index => $tab) {
      $form['tabs'][$tab_index] = [
        '#type' => 'details',
        '#open' => empty($tab['title'] ?? NULL),
        '#title' => $this->t('@title (@tab_index)', [
          '@title' => $tab['title'] ?? $this->t('New'),
          '@tab_index' => $tab_index,
        ]),
      ];
      $form['tabs'][$tab_index]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $tab['title'] ?? NULL,
        '#required' => TRUE,
      ];
      $form['tabs'][$tab_index]['icon'] = \Drupal::service('vlsuite_icon_font.helper')->getIconFontIconFormElement($tab['icon'] ?? NULL);
      $form['tabs'][$tab_index]['remove'] = [
        '#tree' => FALSE,
        '#name' => 'remove_' . $tab_index . '_settings',
        '#type' => 'submit',
        '#attributes' => ['class' => ['button--danger']],
        '#value' => $this->t('Remove'),
        '#disabled' => count($tabs) <= 2,
        '#submit' => [[static::class, 'buildConfigurationFormRemoveTab']],
        '#tab_index' => $tab_index,
        '#ajax' => [
          'callback' => [static::class, 'buildConfigurationFormRefreshTabsAjaxCallback'],
          'wrapper' => 'tabs-wrapper',
          'disable-refocus' => TRUE,
        ],
      ];
    }
    $form['tabs']['add_tab'] = [
      '#type' => 'submit',
      '#name' => 'add_tab_' . count($tabs) . '_settings',
      '#tree' => FALSE,
      '#value' => $this->t('Add new tab'),
      '#submit' => [[static::class, 'buildConfigurationFormAddTab']],
      '#ajax' => [
        'callback' => [static::class, 'buildConfigurationFormRefreshTabsAjaxCallback'],
        'wrapper' => 'tabs-wrapper',
        'disable-refocus' => TRUE,
      ],
    ];
    return $form;
  }

  /**
   * Configuration form add tab.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public static function buildConfigurationFormAddTab(array $form, FormStateInterface $form_state) {
    $tabs = $form_state->getValue(['layout_settings', 'tabs'], []);
    $tabs['tab_' . (count($tabs))] = [];
    $form_state->setValue(['layout_settings', 'tabs'], $tabs);
    $form_state->setRebuild();
  }

  /**
   * Configuration form remove tab.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public static function buildConfigurationFormRemoveTab(array $form, FormStateInterface $form_state) {
    $tab_index = $form_state->getTriggeringElement()['#tab_index'];
    $user_input = $form_state->getUserInput();
    unset($user_input['layout_settings']['tabs'][$tab_index]);
    foreach (array_values($user_input['layout_settings']['tabs']) as $tab_delta => $tab) {
      $user_input['layout_settings']['tabs']['tab_' . $tab_delta] = $tab;
    }
    $form_state->setUserInput($user_input);
    $tabs = $form_state->getValue(['layout_settings', 'tabs'], []);
    unset($tabs[$tab_index]);
    $tabs_processed = [];
    foreach (array_values($tabs) as $tab_delta => $tab) {
      $tabs_processed['tab_' . $tab_delta] = $tab;
    }
    $form_state->setValue(['layout_settings', 'tabs'], $tabs_processed);
    $form_state->setRebuild();
  }

  /**
   * Refresh tabs ajax callback..
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Tabs form element for ajax callback.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public static function buildConfigurationFormRefreshTabsAjaxCallback(array $form, FormStateInterface $form_state) {
    return $form['layout_settings']['tabs'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['tabs'] = $form_state->getValue('tabs', []);
    $default_tab = $form_state->getValue('tabs_default', 'tab_0');
    $this->configuration['tabs_default'] = isset($this->configuration['tabs'][$default_tab]) ? $default_tab : 'tab_0';
  }

  /**
   * {@inheritDoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $grid_classes = NULL;
    // Main col classes with grid related classes will be used to wrap all, use
    // from first main region. Also in preview disable column widths live
    // previewer.
    foreach ($this->getPluginDefinition()->getRegionNames() as $region_name) {
      if ($region_name != 'top' && $region_name != 'bottom') {
        $grid_classes = $grid_classes ?? $build['#' . $region_name . '_col_attributes']->getClass('class');
        $build['#' . $region_name . '_col_attributes']->removeAttribute('class');
        if ($this->inPreview) {
          $build['#' . $region_name . '_col_attributes']->offsetGet('data-vlsuite-utility-classes-live-previewer-identifiers');
          $live_identifiers = 'data-vlsuite-utility-classes-live-previewer-identifiers';
          $build['#' . $region_name . '_col_attributes']->setAttribute($live_identifiers, str_replace($this->getPluginDefinition()->id() . ':column_widths,', '', (string) $build['#' . $region_name . '_col_attributes']->offsetGet($live_identifiers)));
        }
      }
    }
    $build['#grid_classes'] = $grid_classes;
    $build['#identifier_prefix'] = Html::getUniqueId(Html::cleanCssIdentifier($this->getPluginDefinition()->id()));
    return $build;
  }

}
