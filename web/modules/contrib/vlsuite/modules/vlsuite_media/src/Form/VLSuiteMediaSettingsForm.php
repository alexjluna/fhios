<?php

namespace Drupal\vlsuite_media\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Media settings.
 */
final class VLSuiteMediaSettingsForm extends ConfigFormBase {

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($config_factory);
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vlsuite_media_setting';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vlsuite_media.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $bg_types = $this->config('vlsuite_media.settings')->get('bg_types') ?? [];

    $form['bg_types'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Selectable media types to use as background'),
      '#description' => $this->t('Image or local video are compatible'),
    ];
    foreach ($this->entityTypeBundleInfo->getBundleInfo('media') as $bundle_key => $bundle) {
      $form['bg_types'][$bundle_key] = [
        '#type' => 'checkbox',
        '#default_value' => $bg_types[$bundle_key] ?? NULL,
        '#title' => $bundle['label'],
      ];
    }

    $media_types = $this->config('vlsuite_media.settings')->get('media_types') ?? [];

    $form['media_types'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Selectable media types to use as main media types'),
      '#description' => $this->t('Image & local or remote video are compatible'),
    ];
    foreach ($this->entityTypeBundleInfo->getBundleInfo('media') as $bundle_key => $bundle) {
      $form['media_types'][$bundle_key] = [
        '#type' => 'checkbox',
        '#default_value' => $media_types[$bundle_key] ?? NULL,
        '#title' => $bundle['label'],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vlsuite_media.settings')->set('bg_types', array_filter($form_state->getValue('bg_types', [])))->save();
    $this->config('vlsuite_media.settings')->set('media_types', array_filter($form_state->getValue('media_types', [])))->save();
    parent::submitForm($form, $form_state);
  }

}
