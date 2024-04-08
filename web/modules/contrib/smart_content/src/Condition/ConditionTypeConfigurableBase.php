<?php

namespace Drupal\smart_content\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ObjectWithPluginCollectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\smart_content\Condition\Group\ConditionGroupManager;
use Drupal\smart_content\Condition\Type\ConditionTypeManager;
use Drupal\smart_content\Condition\Type\ConditionTypePluginCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for conditions using the type plugin.
 *
 * @package Drupal\smart_content\Condition
 */
abstract class ConditionTypeConfigurableBase extends ConditionBase implements PluginFormInterface, ObjectWithPluginCollectionInterface {

  /**
   * The condition type plugin manager.
   *
   * @var \Drupal\smart_content\Condition\Type\ConditionTypeManager
   */
  protected $conditionTypeManager;

  /**
   * The plugin collection to lazy load the condition type plugin.
   *
   * @var \Drupal\smart_content\Condition\Type\ConditionTypeInterface
   */
  protected $conditionTypeCollection;

  /**
   * Constructs a ConditionBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\smart_content\Condition\Group\ConditionGroupManager $conditionGroupManager
   *   The condition group plugin manager.
   * @param \Drupal\smart_content\Condition\Type\ConditionTypeManager $conditionTypeManager
   *   The condition type plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConditionGroupManager $conditionGroupManager, ConditionTypeManager $conditionTypeManager) {
    $this->conditionTypeManager = $conditionTypeManager;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $conditionGroupManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.smart_content.condition_group'),
      $container->get('plugin.manager.smart_content.condition_type')
    );
  }

  /**
   * Helper function to return condition type.
   *
   * @return \Drupal\smart_content\Condition\Type\ConditionTypeInterface|object
   *   The condition type.
   */
  public function getConditionType() {
    return $this->getConditionTypePluginCollection()
      ->get($this->getPluginDefinition()['type']);
  }

  /**
   * Gets the plugin collections used by this object.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection[]
   *   An array of plugin collections, keyed by the property name they use to
   *   store their configuration.
   */
  public function getPluginCollections() {
    return [
      'condition_type_settings' => $this->getConditionTypePluginCollection(),
    ];
  }

  /**
   * Encapsulates the creation of the conditions's LazyPluginCollection.
   *
   * @return \Drupal\smart_content\Condition\Type\ConditionTypePluginCollection
   *   The condition's type plugin collection.
   */
  protected function getConditionTypePluginCollection() {
    $plugin_type_definition = $this->getPluginDefinition()['type'];
    if (!$this->conditionTypeCollection) {
      $configuration = [];
      if (!empty($this->configuration['condition_type_settings'])) {
        $configuration = $this->configuration['condition_type_settings'];
      }
      $this->conditionTypeCollection = new ConditionTypePluginCollection($this->conditionTypeManager, $plugin_type_definition, (array) $configuration, $this);
    }
    return $this->conditionTypeCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = ConditionBase::attachNegateElement($form, $this->configuration);
    $form['#attributes']['class'] = ['condition'];
    $definition = $this->getPluginDefinition();
    $label = $definition['label'];
    if ($group_label = $this->getGroupLabel()) {
      $label .= '(' . $group_label . ')';
    }
    $form['label'] = [
      '#type' => 'container',
      '#markup' => $label,
      '#attributes' => ['class' => ['condition-label']],
    ];
    return $this->getConditionType()
      ->buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->getConditionType()->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setNegated($form_state->getValue('negate'));
    $this->getConditionType()->submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return $this->getConditionType()->getLibraries();
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedSettings() {
    $settings = parent::getAttachedSettings();
    // Add the field 'settings' from the ConditionType Plugin.
    // @todo: do we need getFieldAttachedSettings() ?
    $settings['field']['settings'] = $this->getConditionType()
      ->getAttachedSettings();
    // Get the 'settings' from the ConditionType Plugin.
    $settings['settings'] = $this->getConditionType()->getAttachedSettings();
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'condition_type_settings' => $this->getConditionType()
        ->defaultConfiguration(),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();
    foreach ($this->getPluginCollections() as $plugin_config_key => $plugin_collection) {
      $configuration[$plugin_config_key] = $plugin_collection->getConfiguration();
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration = $configuration + $this->defaultConfiguration();
    $this->set('condition_type_settings', $configuration['condition_type_settings']);
    return parent::setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $keys_to_unset = [];
    if ($this instanceof ObjectWithPluginCollectionInterface) {
      // Get the plugin collections first, so that the properties are
      // initialized in $vars and can be found later.
      $plugin_collections = $this->getPluginCollections();
      $vars = get_object_vars($this);
      foreach ($plugin_collections as $plugin_config_key => $plugin_collection) {
        if ($plugin_collection) {
          $this->set($plugin_config_key, $plugin_collection->getConfiguration());
        }
        // Save any changes to the plugin configuration to the entity.
        // If the plugin collections are stored as properties on the entity,
        // mark them to be unset.
        $keys_to_unset += array_filter($vars, function ($value) use ($plugin_collection) {
          return $plugin_collection === $value;
        });
      }
    }

    $vars = parent::__sleep();

    if (!empty($keys_to_unset)) {
      $vars = array_diff($vars, array_keys($keys_to_unset));
    }
    return $vars;
  }

  /**
   * {@inheritdoc}
   */
  public function get($property_name) {
    return isset($this->{$property_name}) ? $this->{$property_name} : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    if ($this instanceof ObjectWithPluginCollectionInterface) {
      $plugin_collections = $this->getPluginCollections();
      if (isset($plugin_collections[$property_name])) {
        // If external code updates the settings, pass it along to the plugin.
        $plugin_collections[$property_name]->setConfiguration($value);
        $plugin_collections[$property_name]->clear();
      }
    }
    $this->{$property_name} = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeId() {
    return 'type:' . $this->getConditionType()->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getHtmlSummary() {
    $label = $this->getPluginDefinition()['label'];
    if ($group_label = $this->getGroupLabel()) {
      $label .= '(' . $group_label . ')';
    }
    $negate = $this->getConfiguration()['negate'] ? 'If not ' : 'If ';
    return [
      'negate' => [
        '#markup' => $negate,
        '#prefix' => '<span class="condition-op">',
        '#suffix' => '</span> ',
      ],
      'label' => [
        '#markup' => $label,
        '#prefix' => '<span class="condition-label">',
        '#suffix' => '</span> ',
      ],
      'condition_type' => $this->getConditionType()->getHtmlSummary(),
    ];
  }

}
