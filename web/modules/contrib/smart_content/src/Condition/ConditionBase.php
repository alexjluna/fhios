<?php

namespace Drupal\smart_content\Condition;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\smart_content\Condition\Group\ConditionGroupManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Smart condition plugins.
 */
abstract class ConditionBase extends PluginBase implements ContainerFactoryPluginInterface, ConditionInterface, ConfigurableInterface {

  /**
   * The condition group manager.
   *
   * @var \Drupal\smart_content\Condition\Group\ConditionGroupManager
   */
  protected $conditionGroupManager;

  /**
   * Condition is/is not.
   *
   * @var bool
   */
  protected $negate;

  /**
   * The condition weight.
   *
   * @var int
   */
  protected $weight;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConditionGroupManager $conditionGroupManager) {
    $this->conditionGroupManager = $conditionGroupManager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.smart_content.condition_group')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeId() {
    return 'plugin:' . $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return isset($this->weight) ? $this->weight : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isNegated() {
    return isset($this->negate) ? $this->negate : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setNegated($value = TRUE) {
    $this->negate = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedSettings() {
    // TODO: Potentially add a 'field_key' setting for non_unique that require
    // field settings to determine uniqueness.
    $definition = $this->getPluginDefinition();
    $settings = [
      'field' => [
        'pluginId' => $this->getPluginId(),
        'type' => $this->getTypeId(),
        'negate' => $this->isNegated(),
        'unique' => $definition['unique'],
      ],
    ];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'weight' => $this->getWeight(),
      'negate' => $this->isNegated(),
      'type' => $this->getTypeId(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'weight' => $this->getWeight(),
      'negate' => $this->isNegated(),
      'type' => $this->getTypeId(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration = $configuration + $this->defaultConfiguration();

    if (isset($configuration['weight'])) {
      $this->weight = (int) $configuration['weight'];
    }
    if (isset($configuration['negate'])) {
      $this->negate = (bool) $configuration['negate'];
    }
    return $this;
  }

  /**
   * Utility function to return the condition group label.
   *
   * @return string
   *   The group label.
   */
  public function getGroupLabel() {
    $group = $this->getPluginDefinition()['group'];
    if ($this->conditionGroupManager->hasDefinition($group)) {
      return $this->conditionGroupManager->getDefinition($group)['label'];
    }
    return '';
  }

  /**
   * Utility function to provide "If/If not" select element.
   *
   * @param array $form
   *   The form render array.
   * @param array $config
   *   The conditions configuration.
   *
   * @return array
   *   The form with negate attached.
   */
  public static function attachNegateElement(array $form, array $config) {
    $form['negate'] = [
      '#title' => t('Negate'),
      '#title_display' => 'hidden',
      '#type' => 'select',
      '#default_value' => isset($config['negate']) ? $config['negate'] : FALSE,
      '#empty_option' => t('If'),
      '#empty_value' => FALSE,
      '#options' => [TRUE => t('If Not')],
    ];
    return $form;
  }

}
