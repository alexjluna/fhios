<?php

namespace Drupal\vlsuite_block\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Block settings.
 */
final class VLSuiteBlockSettingsForm extends ConfigFormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The formatter manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterManager;

  /**
   * Constructs a VLSuiteBlockSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   Entity type bundle info.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_manager
   *   Formatter manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    FormatterPluginManager $formatter_manager) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->formatterManager = $formatter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.field.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vlsuite_block_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vlsuite_block.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $apply_to_enabled = $this->config('vlsuite_block.settings')->get('utility_classes_apply_to_enabled') ?? [];

    $form['utility_classes_apply_to_enabled'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $this->t('Utility classes apply to auto definitions'),
    ];
    $entity_info = $this->entityTypeManager->getDefinitions();
    foreach ($entity_info as $entity_type_key => $entity_type) {
      if (!$entity_type->entityClassImplements(FieldableEntityInterface::class)) {
        continue;
      }
      $form['utility_classes_apply_to_enabled'][$entity_type_key] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $entity_type->getLabel(),
      ];
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_key);
      foreach ($bundles as $bundle_key => $bundle) {
        $form['utility_classes_apply_to_enabled'][$entity_type_key][$bundle_key] = [
          '#type' => 'details',
          '#open' => FALSE,
          '#title' => $bundle['label'],
        ];
        if ($entity_type_key == 'block_content') {
          $form['utility_classes_apply_to_enabled'][$entity_type_key][$bundle_key]['inline_block'] = [
            '#type' => 'checkbox',
            '#default_value' => $apply_to_enabled[$entity_type_key][$bundle_key]['inline_block'] ?? NULL,
            '#title' => $this->t('Inline block (custom block)'),
          ];
        }
        $form['utility_classes_apply_to_enabled'][$entity_type_key][$bundle_key]['fields'] = [
          '#type' => 'details',
          '#open' => FALSE,
          '#title' => $entity_type_key == 'block_content' ? $this->t('Field block / inline block field') : $this->t('Block field'),
        ];

        foreach ($this->entityFieldManager->getFieldDefinitions($entity_type_key, $bundle_key) as $field_key => $field_definition) {
          $options = $this->formatterManager->getOptions($field_definition->getType());
          if (empty($options)) {
            continue;
          }
          $form['utility_classes_apply_to_enabled'][$entity_type_key][$bundle_key]['fields'][$field_key] = [
            '#type' => 'checkbox',
            '#default_value' => $apply_to_enabled[$entity_type_key][$bundle_key]['fields'][$field_key] ?? NULL,
            '#title' => $field_definition->getLabel(),
          ];
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $apply_to_enabled = $form_state->getValue('utility_classes_apply_to_enabled', []);
    foreach ($apply_to_enabled as $entity_type_key => $bundles) {
      foreach (array_keys($bundles) as $bundle_key) {
        $apply_to_enabled[$entity_type_key][$bundle_key]['fields'] = array_filter($apply_to_enabled[$entity_type_key][$bundle_key]['fields']);
        $apply_to_enabled[$entity_type_key][$bundle_key] = array_filter($apply_to_enabled[$entity_type_key][$bundle_key]);
      }
      $apply_to_enabled[$entity_type_key] = array_filter($apply_to_enabled[$entity_type_key]);
    }
    $this->config('vlsuite_block.settings')->set('utility_classes_apply_to_enabled', array_filter($apply_to_enabled))->save();
    parent::submitForm($form, $form_state);
  }

}
