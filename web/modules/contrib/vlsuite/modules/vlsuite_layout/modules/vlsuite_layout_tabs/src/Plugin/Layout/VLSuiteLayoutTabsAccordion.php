<?php

namespace Drupal\vlsuite_layout_tabs\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;

/**
 * VLSuite layout tabs accordion.
 *
 * @Layout(
 *   id = "vlsuite_layout_tabs_accordion",
 *   label = @Translation("Tabs accordion with optional top & bottom (VLSuite)"),
 *   path = "layouts/tabs-accordion",
 *   template = "vlsuite-layout-tabs-accordion",
 *   library = "vlsuite_layout_tabs/tabs-accordion"
 * )
 */
class VLSuiteLayoutTabsAccordion extends VLSuiteLayoutTabsBase {

  /**
   * {@inheritdoc}
   */
  protected function getPluginDefinitionIconMap() {
    return [
      ['top'],
      ['tab_0'],
      ['tab_1'],
      ['tab_1'],
      ['bottom'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration() + [
      'tabs_always_open' => FALSE,
    ];
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['tabs_always_open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always open'),
      '#description' => $this->t('Mark to allow open each accordion tab without closing others.'),
      '#default_value' => $this->configuration['tabs_always_open'] ?? FALSE,
      '#weight' => -2,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['tabs_always_open'] = $form_state->getValue('tabs_always_open', FALSE);
  }

  /**
   * {@inheritDoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build['#tabs_always_open'] = $this->configuration['tabs_always_open'] ?? FALSE;
    return $build;
  }
}
