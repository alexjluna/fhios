<?php

namespace Drupal\smart_content\Plugin\smart_content\Condition;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\smart_content\Condition\ConditionConfigurableBase;
use Drupal\smart_content\Condition\ConditionInterface;
use Drupal\smart_content\Condition\ConditionManager;
use Drupal\smart_content\Condition\ConditionsHelperTrait;
use Drupal\smart_content\Condition\Group\ConditionGroupManager;
use Drupal\smart_content\Condition\ObjectWithConditionPluginCollectionInterface;
use Drupal\smart_content\Form\SegmentSetConfigEntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'group' condition.
 *
 * Group conditions act as a composite condition that nests additional
 * conditions within them. All functionality is self contained and is
 * required to evaluate the same as any other condition during javascript
 * processing.
 *
 * @SmartCondition(
 *   id = "group",
 *   label = @Translation("Group"),
 *   group = "common",
 *   weight = 0,
 *   unique = true,
 * )
 */
class Group extends ConditionConfigurableBase implements ObjectWithConditionPluginCollectionInterface {

  use ConditionsHelperTrait;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\smart_content\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * Constructs a Group object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\smart_content\Condition\Group\ConditionGroupManager $conditionGroupManager
   *   The condition group plugin manager.
   * @param \Drupal\smart_content\Condition\ConditionManager $conditionManager
   *   The condition manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConditionGroupManager $conditionGroupManager, ConditionManager $conditionManager) {
    $this->conditionManager = $conditionManager;
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
      $container->get('plugin.manager.smart_content.condition')
    );
  }

  /**
   * AND/OR operator for conditions.
   *
   * @var string
   */
  public $op;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'op' => 'AND',
      'conditions' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $wrapper_id = Html::getUniqueId('conditions-wrapper');
    $form['#attributes']['class'][] = 'condition-group-wrapper';
    $form['op'] = [
      '#title' => $this->t('of the following conditions are true'),
      '#title_display' => 'after',
      '#type' => 'select',
      '#options' => [
        'AND' => $this->t('If all'),
        'OR' => $this->t('If any'),
      ],
      '#default_value' => $this->op,
      '#wrapper_attributes' => [
        'class' => [
          'form-item-label',
        ],
      ],
      '#attributes' => [
        'class' => [
          'condition-op',
          'condition-group-operator',
        ],
      ],
    ];
    $form['settings'] = [
      '#type' => 'container',
      '#title' => $this->t('Conditions'),
      '#tree' => TRUE,
      '#attributes' => [
        'id' => $wrapper_id . '-conditions',
        'class' => [
          'condition-settings-wrapper',
          'group-condition-settings-wrapper',
        ],
      ],
    ];
    $this->getConditionPluginCollection()->sort();
    if (count($this->getConditions()) == 0) {
      $form['settings']['conditions'] = [
        '#type' => 'markup',
        '#markup' => $this->t('No conditions set.'),
        '#prefix' => '<div class="messages messages--warning">',
        '#suffix' => '</div>',
      ];
    }
    else {
      $form['settings']['conditions'] = [
        '#type' => 'table',
        '#header' => [$this->t('Condition(s)'), $this->t('Weight'), ''],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => $wrapper_id . '-order-weight',
          ],
        ],
        '#attributes' => [
          'class' => ['condition-settings-table'],
        ],
      ];
      foreach ($this->getConditions() as $i => $condition) {
        SegmentSetConfigEntityForm::pluginForm($condition, $form, $form_state,
          ['settings', 'conditions', $i, 'plugin_form']);
        $form['settings']['conditions'][$i]['#attributes']['class'][] = 'draggable';
        $form['settings']['conditions'][$i]['#attributes']['class'][] = 'row-color-coded';

        $form['settings']['conditions'][$i]['plugin_form']['#type'] = 'container';

        $form['settings']['conditions'][$i]['plugin_form']['#attributes']['class'][] = Html::getClass('condition-type-' . $condition->getTypeId());
        $form['settings']['conditions'][$i]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight'),
          '#title_display' => 'invisible',
          '#default_value' => $condition->getWeight(),
          '#attributes' => ['class' => [$wrapper_id . '-order-weight']],
        ];

        $form['settings']['conditions'][$i]['remove_condition'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove Condition'),
          '#submit' => [[$this, 'removeElementCondition']],
          '#attributes' => [
            'class' => [
              'align-right',
              'button',
              'button--delete',
              'button--remove-condition',
            ],
          ],
          '#limit_validation_errors' => [],
          '#ajax' => [
            'callback' => [$this, 'removeElementConditionAjax'],
            'wrapper' => $wrapper_id . '-conditions',
          ],
        ];
      }
    }
    $add_container_classes = ['condition-add-container', 'add-container'];
    $form['settings']['add_condition'] = [
      '#type' => 'container',
      '#title' => $this->t('Add Condition'),
      '#attributes' => ['class' => $add_container_classes],
    ];

    $form['settings']['add_condition']['condition_type'] = [
      '#title' => $this->t('Condition Type'),
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => $this->conditionManager->getFormOptions(),
      '#empty_value' => '',
      '#empty_option' => '- Select a condition - ',
    ];

    $form['settings']['add_condition']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Condition'),
      '#validate' => [[$this, 'addElementConditionValidate']],
      '#submit' => [[$this, 'addElementCondition']],
      '#ajax' => [
        'callback' => [$this, 'addElementConditionAjax'],
        'wrapper' => $wrapper_id . '-conditions',
      ],
    ];

    $form['#process'][] = [$this, 'buildWidget'];
    return $form;
  }

  /**
   * Process callback for providing parents dependent elements.
   */
  public function buildWidget(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $unique_id = Html::getClass(implode('-', $element['#parents']));

    foreach ($this->getConditions() as $condition_id => $condition) {
      if ($condition instanceof PluginFormInterface) {
        $element['settings']['conditions'][$condition_id]['remove_condition']['#name'] = 'remove_condition_' . $unique_id . '__' . $condition_id;
      }
    }
    $element['settings']['add_condition']['submit'] += [
      '#name' => 'add_condition_' . $unique_id,
      '#limit_validation_errors' => [
        array_merge($element['#parents'], [
          'settings',
          'add_condition',
          'condition_type',
        ]),
      ],
    ];
    return $element;
  }

  /**
   * Provides a '#validate' callback for adding a Condition.
   *
   * Validates that a valid condition type is selected.
   */
  public function addElementConditionValidate(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $array_parents = array_slice($button['#array_parents'], 0, -1);
    $parents = array_slice($button['#parents'], 0, -1);
    $parents[] = 'condition_type';
    $array_parents[] = 'condition_type';
    if (!$value = NestedArray::getValue($form_state->getUserInput(), $parents)) {
      $form_state->setError(NestedArray::getValue($form, $array_parents), 'Condition type required.');
    }
  }

  /**
   * Provides a '#submit' callback for adding a Condition.
   */
  public function addElementCondition(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Get condition input.
    $settings = NestedArray::getValue($form_state->getUserInput(), array_slice($button['#parents'], 0, -2));

    if (!empty($settings['conditions'])) {
      // Set and sort conditions by weight.
      $this->getConditionPluginCollection()
        ->mapFormWeightValues($settings['conditions'])
        ->sort();
    }

    $type = NestedArray::getValue($form_state->getUserInput(), array_slice($button['#parents'], 0, -1))['condition_type'];

    $this->appendCondition($this->conditionManager->createInstance($type));

    $form_state->setRebuild();
  }

  /**
   * Provides an '#ajax' callback for adding a Condition.
   */
  public function addElementConditionAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
  }

  /**
   * Provides a '#submit' callback for removing a Condition.
   */
  public function removeElementCondition(array &$form, FormStateInterface $form_state) {

    $button = $form_state->getTriggeringElement();

    [$action, $name] = explode('__', $button['#name']);
    // Get condition input.
    $settings = NestedArray::getValue($form_state->getUserInput(), array_slice($button['#parents'], 0, -3));

    if (!empty($settings['conditions'])) {
      // Set and sort conditions by weight.
      $this->getConditionPluginCollection()
        ->mapFormWeightValues($settings['conditions'])
        ->sort();
    }

    $this->removeCondition($name);
    $form_state->setRebuild();
  }

  /**
   * Provides an '#ajax' callback for removing a Condition.
   */
  public function removeElementConditionAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getConditions() as $condition_id => $condition) {
      SegmentSetConfigEntityForm::pluginFormValidate($condition, $form, $form_state, [
        'settings',
        'conditions',
        $condition_id,
        'plugin_form',
      ]);
    }
    if ($this->getConditions()->count() === 0 && isset($form['settings'])) {
      $form_state->setError($form['settings'], $this->t('A minimum of 1 condition is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->op = $form_state->getValue('op');

    if (!empty($form_state->getValue('settings')['conditions'])) {
      // Set and sort conditions by weight.
      $this->getConditionPluginCollection()
        ->mapFormWeightValues($form_state->getValue('settings')['conditions'])
        ->sort();
    }

    foreach ($this->getConditions() as $condition_id => $condition) {
      SegmentSetConfigEntityForm::pluginFormSubmit($condition, $form, $form_state, [
        'settings',
        'conditions',
        $condition_id,
        'plugin_form',
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'conditions' => $this->getConditionPluginCollection(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);

    if (isset($configuration['conditions'])) {
      $this->set('conditions', $configuration['conditions']);
    }

    if (isset($configuration['op'])) {
      $this->op = $configuration['op'];
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();
    foreach ($this->getPluginCollections() as $plugin_config_key => $plugin_collection) {
      $configuration[$plugin_config_key] = $plugin_collection->getConfiguration();
    }
    $configuration['op'] = $this->op;
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    $libraries = array_map(function (ConditionInterface $condition) {
      return $condition->getLibraries();
    }, $this->getConditions()->getIterator()->getArrayCopy());

    return array_unique(array_merge(parent::getLibraries(), array_reduce($libraries, 'array_merge', []), [
      'smart_content/condition.common',
      'smart_content/condition_type.standard',
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedSettings() {
    $settings = parent::getAttachedSettings();
    $settings['settings']['op'] = $this->op;
    $settings['conditions'] = array_map(function (ConditionInterface $condition) {
      return $condition->getAttachedSettings();
    }, $this->getConditions()->getIterator()->getArrayCopy());
    return $settings;
  }

  /**
   * {@inheritDoc}
   */
  public function getHtmlSummary() {
    // Get conjunction and initialize group summary.
    $markup = '<p><em>';
    $configuration = $this->getConfiguration();
    $conjunction = $configuration['op'][0];
    $markup .= $conjunction == 'AND' ? 'If all ' : 'If any ';
    $markup .= 'of the following conditions are true:</em></p>';

    // Get individual condition summaries.
    $conditions_summary = [];
    foreach ($this->getConditions() as $condition) {
      $conditions_summary[] = $condition->getHtmlSummary();
    }

    return [
      '#prefix' => '<div class="condition--group>',
      '#suffix' => '</div>',
      'markup' => [
        '#markup' => $markup,
      ],
      'conditions' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $conditions_summary,
      ],
    ];
  }

}
