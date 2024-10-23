<?php

namespace Drupal\vlsuite_icon_font\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure VLSuite icon classes settings.
 */
final class VLSuiteIconFontSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vlsuite_icon_font_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vlsuite_icon_font.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['info'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('Info'),
      '#description' => $this->t('Default provided is <a href=":link" target="_blank">Material Icons</a>.',
      [':link' => 'https://fonts.google.com/icons?icon.set=Material+Icons']),
    ];

    $form['basics'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('Basics'),
    ];

    $form['basics']['font'] = [
      '#type' => 'textfield',
      '#field_suffix' => ['#type' => 'container'],
      '#required' => TRUE,
      '#title' => $this->t('Font external source'),
      '#description' => $this->t('Example for <a href=":link" target="_blank">Material icons</a> "//fonts.googleapis.com/css2?family=Material+Symbols+Outlined".',
      [':link' => 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined']),
      '#default_value' => $this->configFactory->get('vlsuite_icon_font.settings')->get('font'),
    ];

    $form['basics']['list'] = [
      '#type' => 'textarea',
      '#required' => TRUE,
      '#title' => $this->t('Icon list options, one per line'),
      '#description' => $this->t('Example "account_circle" or "house-user".'),
      '#default_value' => $this->configFactory->get('vlsuite_icon_font.settings')->get('list'),
    ];
    $form['basics']['main_classes'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Main class (or classes) to add always when an icon is used'),
      '#description' => $this->t('Example "material-symbols-outlined".'),
      '#default_value' => $this->configFactory->get('vlsuite_icon_font.settings')->get('main_classes'),
    ];

    $form['basics']['replacement'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Choose replacement method'),
      '#description' => $this->t('Adding selected icon option from list as class or as text.'),
      '#options' => [
        'class' => $this->t('Class'),
        'text' => $this->t('Text'),
      ],
      '#default_value' => $this->configFactory->get('vlsuite_icon_font.settings')->get('replacement'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vlsuite_icon_font.settings')
      ->set('font', $form_state->getValue('basics')['font'] ?? '')
      ->set('list', $form_state->getValue('basics')['list'] ?? '')
      ->set('main_classes', $form_state->getValue('basics')['main_classes'] ?? '')
      ->set('replacement', $form_state->getValue('basics')['replacement'] ?? '')
      ->save();
    parent::submitForm($form, $form_state);
  }

}
