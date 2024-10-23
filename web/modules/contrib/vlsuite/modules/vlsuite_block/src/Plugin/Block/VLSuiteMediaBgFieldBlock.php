<?php

namespace Drupal\vlsuite_block\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to use as section background from entity media field.
 *
 * @Block(
 *   id = "vlsuite_block_media_bg_block",
 *   admin_label = @Translation("VLSuite: Media background field block"),
 *   category = @Translation("VLSuite: Media background field block"),
 *   deriver = "\Drupal\vlsuite_block\Plugin\Derivative\VLSuiteMediaBgFieldBlockDeriver",
 * )
 */
class VLSuiteMediaBgFieldBlock extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The bundle ID.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * Constructs new VLSuiteMediaBgFieldBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
    [, $entity_type_id, $bundle, $field_name] = explode(static::DERIVATIVE_SEPARATOR, $plugin_id, 4);
    $this->entityTypeId = $entity_type_id;
    $this->bundle = $bundle;
    $this->fieldName = $field_name;
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $type_indicator = (string) $this->t('VLSuite: Media background field block: @subtype', [
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
    $form['info'] = [
      '#markup' => $this->t('This will be used as section background'),
    ];
    return $form;
  }

  /**
   * Get media background identifier.
   *
   * @return int
   *   Media indentifier from entity value.
   */
  public function getMediaBgId() {
    $media_id = 0;
    if ($this->getEntity()->hasField($this->fieldName) && !$this->getEntity()->get($this->fieldName)->isEmpty()) {
      $media_id = (int) $this->getEntity()->get($this->fieldName)->first()->getString();
    }
    return $media_id;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#media_bg_id' => $this->getMediaBgId(),
      '#access' => FALSE,
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $entity = $this->getEntity();
    $access = $entity->access('view', $account, TRUE);
    if (!$access->isAllowed()) {
      return $access;
    }
    if (!$entity instanceof FieldableEntityInterface || !$entity->hasField($this->fieldName)) {
      return $access->andIf(AccessResult::forbidden());
    }
    $field = $entity->get($this->fieldName);
    $access = $access->andIf($field->access('view', $account, TRUE));
    if (!$access->isAllowed()) {
      return $access;
    }
    if ($field->isEmpty() && !$field->getFieldDefinition()->getDefaultValue($entity)) {
      return $access->andIf(AccessResult::forbidden());
    }
    return $access;
  }

  /**
   * Gets the field definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition.
   */
  protected function getFieldDefinition() {
    if (empty($this->fieldDefinition)) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($this->entityTypeId, $this->bundle);
      $this->fieldDefinition = $field_definitions[$this->fieldName];
    }
    return $this->fieldDefinition;
  }

  /**
   * Gets the entity that has the field.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity.
   */
  protected function getEntity() {
    return $this->getContextValue('entity');
  }

}
