<?php

namespace Drupal\vlsuite_modal\Form;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides VLSuite - Modal configuration form.
 */
final class VLSuiteModalSettingsForm extends ConfigFormBase {

  /**
   * The library discovery.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * Constructs a new NegotiationUrlForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LibraryDiscoveryInterface $library_discovery) {
    parent::__construct($config_factory);
    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vlsuite_modal_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['vlsuite_modal.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('vlsuite_modal.settings');

    $form['options'] = [
      '#type' => 'fieldset',
      '#description' => $this->t('Options to apply for below enabled services.'),
      '#title' => $this->t('Options'),
    ];
    $form['options']['modal_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $config->get('modal_width'),
      '#description' => $this->t(
        'Width in pixels with no units (e.g. "<code>768</code>"). See <a href=":link">the jQuery Dialog documentation</a> for more details.',
        [':link' => 'https://api.jqueryui.com/dialog/#option-width']
      ),
      '#size' => 20,
      '#required' => TRUE,
    ];
    $form['options']['modal_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $config->get('modal_height'),
      '#description' => $this->t(
        'Height in pixels with no units (e.g. "<code>768</code>") or "auto" for automatic height. See <a href=":link">the jQuery Dialog documentation</a> for more details.',
        [':link' => 'https://api.jqueryui.com/dialog/#option-height']
      ),
      '#size' => 20,
      '#required' => TRUE,
    ];
    $form['options']['modal_autoresize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto resize'),
      '#default_value' => $config->get('modal_autoresize'),
      '#description' => $this->t('Allow modal to automatically resize and enable scrolling for dialog content. If enabled, the ability to drag and resize the dialog will be disabled.'),
    ];

    $form['layout_builder'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Layout builder'),
    ];
    $form['layout_builder']['layout_builder_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable for layout builder'),
      '#description' => $this->t('Use modal instead off screen for all layout builder "layout" pages related links & contextual links.'),
      '#default_value' => $config->get('layout_builder_enabled'),
    ];
    $form['layout_builder']['layout_builder_admin_inherited'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use admin theme appearance for modal pages'),
      '#description' => $this->t('Inherit appearance dinamically into layout builder "layout" pages (for modal loaded pages, not for main pages, media library widget, drag & drop tables, ...). Main active theme style may interfere, use carefully, this will load original admin theme dependecies, not simulated.'),
      '#default_value' => $config->get('layout_builder_admin_inherited'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $width = $form_state->getValue('modal_width');
    if ((!is_numeric($width) || $width < 1)) {
      $form_state->setErrorByName('modal_width', $this->t('Width must be a positive number.'));
    }
    $height = $form_state->getValue('modal_height');
    if ((!is_numeric($height) || $height < 1) && $height !== 'auto') {
      $form_state->setErrorByName('modal_height', $this->t('Height must be a positive number or "auto".'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vlsuite_modal.settings')
      ->set('modal_width', $form_state->getValue('modal_width'))
      ->set('modal_height', $form_state->getValue('modal_height'))
      ->set('modal_autoresize', $form_state->getValue('modal_autoresize'))
      ->set('layout_builder_enabled', $form_state->getValue('layout_builder_enabled'))
      ->set('layout_builder_admin_inherited', $form_state->getValue('layout_builder_admin_inherited'))
      ->save();
    $this->libraryDiscovery->clearCachedDefinitions();

    parent::submitForm($form, $form_state);
  }

}
