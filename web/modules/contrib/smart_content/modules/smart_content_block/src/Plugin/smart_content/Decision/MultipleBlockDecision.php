<?php

namespace Drupal\smart_content_block\Plugin\smart_content\Decision;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_content\Condition\ConditionManager;
use Drupal\smart_content\Decision\DecisionBase;
use Drupal\smart_content\Decision\PlaceholderDecisionInterface;
use Drupal\smart_content\Form\SegmentSetConfigEntityForm;
use Drupal\smart_content\Plugin\smart_content\SegmentSetStorage\Inline;
use Drupal\smart_content\Reaction\ReactionManager;
use Drupal\smart_content\Segment;
use Drupal\smart_content\SegmentSetStorage\SegmentSetStorageManager;
use Drupal\smart_content\WidgetStateHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a 'Multiple Block Decision' Decision plugin.
 *
 * @SmartDecision(
 *  id = "multiple_block_decision",
 *  label = @Translation("Multiple Block Decision"),
 * )
 */
class MultipleBlockDecision extends DecisionBase implements PlaceholderDecisionInterface {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\smart_content\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The default segment uuid.
   *
   * @var string
   */
  protected $savedDefault;

  /**
   * Constructs a MultipleBlockDecision object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\smart_content\SegmentSetStorage\SegmentSetStorageManager $segmentSetStorageManager
   *   The segment set storage plugin manager.
   * @param \Drupal\smart_content\Reaction\ReactionManager $reactionManager
   *   The reaction plugin manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Component\Uuid\UuidInterface $uuidGenerator
   *   The uuid generator.
   * @param \Drupal\smart_content\Condition\ConditionManager $conditionManager
   *   The condition plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SegmentSetStorageManager $segmentSetStorageManager, ReactionManager $reactionManager, EventDispatcherInterface $eventDispatcher, UuidInterface $uuidGenerator, ConditionManager $conditionManager) {
    $this->conditionManager = $conditionManager;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $segmentSetStorageManager, $reactionManager, $eventDispatcher, $uuidGenerator);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.smart_content.segment_set_storage'),
      $container->get('plugin.manager.smart_content.reaction'),
      $container->get('event_dispatcher'),
      $container->get('uuid'),
      $container->get('plugin.manager.smart_content.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = $this->buildSegmentSetSelectWidget($form, $form_state);

    $form['#element_validate'][] = [$this, 'validateConfigurationForm'];
    $form['#process'][] = [$this, 'processConfigurationForm'];

    $form['decision_settings'] = [
      '#type' => 'details',
      '#attributes' => [
        'class' => [
          'decision-settings-wrapper',
        ],
      ],
      '#title' => $this->t('Configure Reactions'),
    ];

    if ($this->getSegmentSetStorage()) {
      $this->stubDecision($form_state);
      if ($this->getSegmentSetStorage()->getPluginDefinition()['global']) {
        $form = $this->buildSelectedSegmentSetWidget($form, $form_state);
      }
      elseif ($this->getSegmentSetStorage() instanceof Inline) {
        $form = $this->buildInlineSegmentSetWidget($form, $form_state);
      }
    }
    return $form;
  }

  /**
   * Provides a '#process' callback for 'buildSegmentSetSelectWidget'.
   */
  public function processConfigurationForm(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $settings_wrapper_id = SegmentSetConfigEntityForm::getFormParentsUniqueId($element, 'decision-settings');
    $element['decision_settings']['#attributes']['id'] = $settings_wrapper_id;
    return $element;
  }

  /**
   * Stubs base structure of decision.
   *
   * This method stubs the basic structure of the decision.  It prevents less
   * than 1 segment existing, and provides a reaction for each segment.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function stubDecision(FormStateInterface $form_state) {
    if ($this->getSegmentSetStorage() instanceof Inline) {
      if (count($this->getSegmentSetStorage()->getSegmentSet()->getSegments()) === 0) {
        $segment = Segment::fromArray();
        $this->getSegmentSetStorage()
          ->getSegmentSet()
          ->setSegment($segment);
        WidgetStateHandler::setWidgetState($this, $form_state, $segment->getUuid(), WidgetStateHandler::OPEN);
      }
    }
    foreach ($this->getSegmentSetStorage()->getSegmentSet()->getSegments() as $segment) {
      if ($this->getSegmentSetStorage() instanceof Inline) {
        if (is_null($segment->getLabel())) {
          $segment->setLabel(SegmentSetConfigEntityForm::getUniqueSegmentLabel($this->getSegmentSetStorage()->getSegmentSet()));
        }
        if ($segment->getConditions()->count() === 0) {
          $segment->appendCondition($this->conditionManager->createInstance('group'));
        }
      }
      if (!$this->hasReaction($segment->getUuid())) {
        $reaction = $this->reactionManager->createInstance('display_blocks');
        $reaction->setSegmentDependency($segment);
        $this->appendReaction($reaction);
      }
    }
  }

  /**
   * A form for selecting a global segment set or using an inline segment set.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The altered form array.
   */
  public function buildSegmentSetSelectWidget(array &$form, FormStateInterface $form_state) {
    // @todo: Set default if no form value is set, but storage is set.
    // @todo: automatically assume inline if no other options.
    // @todo: add locking functionalitionality and warning on change.
    // @todo: if storage is inline, add checkbox to allow global save.
    $options = $this->segmentSetStorageManager->getFormOptions();

    $form['#process'][] = [$this, 'processSegmentSetSelectWidget'];

    $wrapper_id = 'decision-reaction-wrapper';
    $form['decision_select'] = [
      '#type' => 'details',
      '#title' => $this->t('Segment settings'),
      '#open' => TRUE,
    ];
    $form['decision_select']['list'] = [
      '#type' => 'select',
      '#title' => $this->t('Segment Sets'),
      '#title_display' => 'hidden',
      '#options' => $options,
      '#required' => TRUE,
      '#empty_option' => '-- Select a Segment Set --',
    ];

    $form['decision_select']['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Select Segment Set'),
      '#submit' => [[$this, 'updateSegmentSet']],
      '#ajax' => [
        'callback' => [$this, 'updateSegmentSetAjax'],
        'wrapper' => $wrapper_id,
      ],
    ];

    // Check if a value was set for the decision select element already. If yes,
    // set a default value for the list field and disable it.
    if ($this->getSegmentSetStorage()) {
      $form['decision_select']['list']['#default_value'] = $this->getSegmentSetStorage()
        ->getPluginId();
      $form['decision_select']['list']['#disabled'] = TRUE;
      $form['decision_select']['update']['#access'] = FALSE;
      $form['decision_select']['#open'] = FALSE;
      $storage = $this->getSegmentSetStorage();
      $label = $storage->getPluginDefinition()['label'];
      $form['decision_select']['#title'] = $this->t('Segment settings (%label)', ['%label' => $label]);
    }

    return $form;
  }

  /**
   * Provides a '#process' callback for 'buildSegmentSetSelectWidget'.
   */
  public function processSegmentSetSelectWidget(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $select_wrapper_id = SegmentSetConfigEntityForm::getFormParentsUniqueId($element, 'decision-select');
    $settings_wrapper_id = SegmentSetConfigEntityForm::getFormParentsUniqueId($element, 'decision-settings');
    $element['decision_select']['update']['#limit_validation_errors'] = [
      [
        'decision_select',
        'list',
      ],
    ];
    $element['#prefix'] = "<div id='$settings_wrapper_id'>";
    $element['#suffix'] = "</div>";
    $element['decision_select']['update']['#ajax']['wrapper'] = $settings_wrapper_id;
    $element['decision_select']['update']['#name'] = $this->getUniqueFormId('decision-select');
    return $element;
  }

  /**
   * Provides a '#process' callback for 'buildSelectedSegmentSetWidget'.
   */
  public function processSelectedSegmentSetWidget(array &$element, FormStateInterface $form_state, array &$complete_form) {
    foreach ($element['decision_settings']['segments'] as $segment_id => $settings) {
      $relative_parents = [
        'decision_settings',
        'segments',
        $segment_id,
        'settings',
      ];
      $element['decision_settings']['segments'][$segment_id]['settings']['edit_segment']['#limit_validation_errors'] = [
        array_merge($element['#parents'], $relative_parents),
      ];
    }

    return $element;
  }

  /**
   * Provides a '#submit' callback for adding a Variation.
   */
  public function updateSegmentSet(array &$form, FormStateInterface $form_state) {
    $parents = array_slice($form_state->getTriggeringElement()['#parents'],
      0, -1);
    $parents[] = 'list';

    $type = NestedArray::getValue($form_state->getUserInput(), $parents);
    if ($this->segmentSetStorageManager->hasDefinition($type)) {
      $segment_set_storage = $this->segmentSetStorageManager->createInstance($type);
      $this->setSegmentSetStorage($segment_set_storage);
      $this->stubDecision($form_state);
    }
    $form_state->setRebuild();
  }

  /**
   * Provides an '#ajax' callback for adding a Variation.
   */
  public function updateSegmentSetAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
  }

  /**
   * The inline segment set form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The altered form array.
   */
  public function buildInlineSegmentSetWidget(array &$form, FormStateInterface $form_state) {

    $form['#process'][] = [$this, 'processInlineSegmentSetWidget'];
    // @todo: Implement unique id for wrapper.
    $form['decision_settings'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#attributes' => [
        'class' => [
          'decision-settings-wrapper',
        ],
      ],
      '#title' => $this->t('Configure Segments and Reactions'),
    ];

    $form['decision_settings']['segments'] = [
      '#type' => 'table',
      '#header' => ['', $this->t('Weight'), ''],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
        ],
      ],
    ];

    $i = 0;

    $default = $this->getSegmentSetStorage()->getSegmentSet()->getDefaultSegment();

    foreach ($this->getSegmentSetStorage()->getSegmentSet()->getSegments() as $segment_id => $segment) {
      $i++;
      $form['decision_settings']['segments'][$segment_id]['#attributes']['class'][] = 'draggable';

      $form['decision_settings']['segments'][$segment_id]['settings']['uuid'] = [
        '#type' => 'value',
        '#value' => $segment_id,
      ];

      $form['decision_settings']['segments'][$segment_id]['settings'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Segment @count', ['@count' => ($i)]),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => [
            'segment-settings-wrapper',
          ],
        ],
        '#prefix' => '<div id="segment--' . $this->getToken() . '--' . $segment_id . '">',
        '#suffix' => '</div>',
      ];

      $disabled = ($default && $default->getUuid() != $segment->getUuid()) ? 'disabled' : '';

      $segment_state = WidgetStateHandler::getWidgetState($this, $form_state, $segment_id);
      if (is_null($segment_state)) {
        WidgetStateHandler::setWidgetState($this, $form_state, $segment_id, WidgetStateHandler::CLOSED);
        $segment_state = WidgetStateHandler::CLOSED;
      }
      if ($segment_state == WidgetStateHandler::OPEN) {
        // Edit mode: load forms and allow admin to edit.
        // Set button label.
        $button_label = 'Collapse';

        // Display label.
        $form['decision_settings']['segments'][$segment_id]['settings']['label'] = [
          '#title' => $this->t('Segment'),
          '#type' => 'textfield',
          '#default_value' => $segment->getLabel(),
          '#required' => TRUE,
          '#size' => 30,
          '#wrapper_attributes' => [
            'class' => [
              'segment-label-element',
            ],
          ],
        ];
        $form['decision_settings']['segments'][$segment_id]['settings']['additional_settings'] = [
          '#type'       => 'container',
          '#attributes' => [
            'class'    => ['segment-additional-settings-container'],
            'disabled' => [$disabled],
          ],
        ];
        $form['decision_settings']['segments'][$segment_id]['settings']['additional_settings']['default'] = [
          '#type' => 'checkbox',
          '#attributes' => [
            'class' => ['smart-variations-default-' . $segment->getUuid()],
            'disabled' => [$disabled],
          ],
          '#title' => $this->t('Set as default segment'),
          '#default_value' => $segment->isDefault(),
        ];
        // Load condition forms.
        foreach ($segment->getConditions() as $ii => $condition) {
          SegmentSetConfigEntityForm::pluginForm($condition, $form, $form_state, [
            'decision_settings',
            'segments',
            $segment_id,
            'settings',
            'condition_settings',
            $ii,
            'plugin_form',
          ]);
        }
        $form['decision_settings']['segments'][$segment_id]['settings']['condition_settings']['#type'] = 'container';
        $form['decision_settings']['segments'][$segment_id]['settings']['condition_settings']['#attributes']['class'][] = 'plugin-container-wrapper';

        // Load reaction forms.
        $reaction = $this->getReaction($segment->getUuid());
        SegmentSetConfigEntityForm::pluginForm($reaction, $form, $form_state, [
          'decision_settings',
          'segments',
          $segment_id,
          'settings',
          'reaction_settings',
          'plugin_form',
        ]);

        $form['decision_settings']['segments'][$segment_id]['settings']['reaction_settings']['#type'] = 'container';
        $form['decision_settings']['segments'][$segment_id]['settings']['reaction_settings']['#attributes']['class'][] = 'plugin-container-wrapper';
      }
      else {
        // Preview mode: show an HTML summary of the segment.
        // Add button label.
        $button_label = 'Edit';

        // Display label.
        $form['decision_settings']['segments'][$segment_id]['settings']['label'] = [
          '#markup' => '<strong>Segment: </strong>' . $segment->getLabel(),
        ];

        // Display additional settings.
        if ($segment->isDefault()) {
          $form['decision_settings']['segments'][$segment_id]['settings']['additional_settings']['default'] = [
            '#markup' => $this->t('Default'),
            '#prefix' => '<div class="segment-summary-default">',
            '#suffix' => '</div>',
          ];
        }

        // Add condition summaries.
        $conditions_summary = [];
        foreach ($segment->getConditions() as $condition) {
          $conditions_summary[] = $condition->getHtmlSummary();
        }
        $form['decision_settings']['segments'][$segment_id]['settings']['conditions_summary'] = [
          'conditions_summary' => $conditions_summary,
          '#prefix' => '<div class="segment-summary-wrapper clearfix condition-settings-wrapper">',
          '#suffix' => '</div>',
        ];

        // Add reaction summary.
        $reaction = $this->getReaction($segment_id);
        $form['decision_settings']['segments'][$segment_id]['settings']['reaction_summary'] = [
          'reaction_summary' => $reaction->getHtmlSummary(),
          '#prefix' => '<div class="segment-summary-wrapper clearfix>',
          '#suffix' => '</div>',
        ];
      }

      $form['decision_settings']['segments'][$segment_id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
      ];

      // TODO: Limit validation to parents up to the segment.
      $form['decision_settings']['segments'][$segment_id]['settings']['edit_segment'] = [
        '#type' => 'submit',
        '#value' => $this->t('@label', ['@label' => $button_label], ['context' => 'Smart Content segment']),
        '#submit' => [[$this, 'toggleSegmentDisplay']],
        '#validate' => [[$this, 'validateToggleSegmentDisplay']],
        '#name' => 'edit-segment--' . $this->getToken() . '--' . $segment_id,
        '#ajax' => [
          'callback' => [$this, 'toggleSegmentDisplayAjax'],
          'wrapper' => 'segment--' . $this->getToken() . '--' . $segment_id,
        ],
        '#attributes' => [
          'class' => [
            'edit-segment',
            'edit-button',
          ],
        ],
      ];

      $form['decision_settings']['segments'][$segment_id]['remove_variation'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove_variation__' . $segment_id,
        '#submit' => [[$this, 'removeElementSegment']],
        '#attributes' => [
          'class' => [
            'align-right',
            'button',
            'button--delete',
            'button--remove-segment',
          ],
        ],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [$this, 'removeElementSegmentAjax'],
        ],
      ];
    }
    // @todo: Trigger modal to configure this before saving.
    $form['decision_settings']['add_segment'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Another Segment'),
      '#submit' => [[$this, 'addElementSegment']],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [$this, 'addElementSegmentAjax'],
      ],
    ];

    $form['#attached']['library'][] = 'smart_content/form';
    return $form;
  }

  /**
   * Validate handler for collapsing a segment.
   *
   * @param array $form
   *   The segment form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateToggleSegmentDisplay(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    [$action, $token, $segment_id] = explode('--', $button['#name']);
    $segment_state = WidgetStateHandler::getWidgetState($this, $form_state, $segment_id);

    // Check if segment state is open, meaning it's attempting to collapse.
    if ($segment_state == WidgetStateHandler::OPEN) {
      $parents = array_slice($form_state->getTriggeringElement()['#array_parents'], 0, -1);
      $is_inline = $this->getSegmentSetStorage() instanceof Inline;
      $segment = $this->getSegmentSetStorage()->getSegmentSet()->getSegment($segment_id);
      if ($is_inline) {
        foreach ($segment->getConditions() as $condition_id => $condition) {
          $condition_parents = $parents;
          $condition_parents[] = 'condition_settings';
          $condition_parents[] = $condition_id;
          $condition_parents[] = 'plugin_form';
          SegmentSetConfigEntityForm::pluginFormValidate($condition, $form, $form_state, $condition_parents);
        }
      }
      $reaction = $this->getReaction($segment->getUuid());
      $reaction_parents = $parents;
      $reaction_parents[] = 'reaction_settings';
      $reaction_parents[] = 'plugin_form';
      SegmentSetConfigEntityForm::pluginFormValidate($reaction, $form, $form_state, $reaction_parents);
    }
  }

  /**
   * Submit handler for editing a segment.
   *
   * @param array $form
   *   The segment form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function toggleSegmentDisplay(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    [$action, $token, $segment_id] = explode('--', $button['#name']);
    $segment_state = WidgetStateHandler::getWidgetState($this, $form_state, $segment_id);
    if ($segment_state == WidgetStateHandler::OPEN) {
      $values = $form_state->getValues();
      $segment = $this->getSegmentSetStorage()->getSegmentSet()->getSegment($segment_id);

      // Get the parents with values to submit.
      $parents = array_slice($button['#array_parents'], 0, -1);
      $is_inline = $this->getSegmentSetStorage() instanceof Inline;
      if ($is_inline) {
        // Submit label.
        if ($values['settings']['decision']['decision_settings']['segments'][$segment_id]['settings']['label']) {
          $label = $values['settings']['decision']['decision_settings']['segments'][$segment_id]['settings']['label'];
          $segment->setLabel($label);
        }

        // Set default.
        if ($values['settings']['decision']['decision_settings']['segments'][$segment_id]['settings']['additional_settings']['default']) {
          $default = $values['settings']['decision']['decision_settings']['segments'][$segment_id]['settings']['additional_settings']['default'];
          $segment->setDefault($default);
        }
        else {
          $segment->setDefault(FALSE);
        }

        // Submit condition form.
        foreach ($segment->getConditions() as $condition_id => $condition) {
          SegmentSetConfigEntityForm::pluginFormSubmit($condition, $form, $form_state, array_merge($parents, [
            'condition_settings',
            $condition_id,
            'plugin_form',
          ]));
        }
      }

      // Submit reaction form.
      $reaction = $this->getReaction($segment->getUuid());
      $relative_parents = [
        'reaction_settings',
        'plugin_form',
      ];
      $plugin_parents = array_merge($parents, $relative_parents);
      SegmentSetConfigEntityForm::pluginFormSubmit($reaction, $form, $form_state, $plugin_parents);
    }
    WidgetStateHandler::toggleWidgetState($this, $form_state, $segment_id);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for editing a segment.
   *
   * @param array $form
   *   The segment form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array|mixed|null
   *   Response array.
   */
  public function toggleSegmentDisplayAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    return $element;
  }

  /**
   * Provides a '#process' callback for 'buildSegmentSetSelectWidget'.
   */
  public function processInlineSegmentSetWidget(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $settings_wrapper_id = SegmentSetConfigEntityForm::getFormParentsUniqueId($element, 'decision-settings');
    $segment_group_order = $this->getUniqueFormId('order-segment-weight');

    $element['decision_settings']['segments']['#tabledrag'][0]['group'] = $segment_group_order;
    foreach ($element['decision_settings']['segments'] as $segment_id => $settings) {
      if (!isset($element['decision_settings']['segments'][$segment_id]['weight'])) {
        continue;
      }
      $element['decision_settings']['segments'][$segment_id]['weight']['#attributes']['class'][] = $segment_group_order;
      $element['decision_settings']['segments'][$segment_id]['remove_variation']['#ajax']['wrapper'] = $settings_wrapper_id;
      $relative_parents = [
        'decision_settings',
        'segments',
        $segment_id,
        'settings',
      ];
      $element['decision_settings']['segments'][$segment_id]['settings']['edit_segment']['#limit_validation_errors'] = [
        array_merge($element['#parents'], $relative_parents),
      ];
    }

    $element['decision_settings']['add_segment']['#ajax']['wrapper'] = $settings_wrapper_id;
    $element['decision_settings']['add_segment']['#name'] = $settings_wrapper_id . '-add';
    return $element;
  }

  /**
   * Provides a '#submit' callback for adding a Variation.
   */
  public function addElementSegment(array &$form, FormStateInterface $form_state) {
    $parents = array_slice($form_state->getTriggeringElement()['#parents'], 0, -2);
    // Map segment weights.
    $values = NestedArray::getValue($form_state->getUserInput(), $parents);
    $this->mapFormSegmentWeights($values);
    // todo: $form_state->storage() refreshes on first ajax submit on layout
    // builder, so we may consider using form_values to append additional
    // segments.
    $segment = Segment::fromArray();
    $this->getSegmentSetStorage()->getSegmentSet()->setSegment($segment);

    // Set a default widget state of open for the new segment.
    WidgetStateHandler::setWidgetState($this, $form_state, $segment->getUuid(), WidgetStateHandler::OPEN);

    $form_state->setRebuild();
  }

  /**
   * Provides an '#ajax' callback for adding a Variation.
   */
  public function addElementSegmentAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
  }

  /**
   * Provides a '#submit' callback for removing a Variation.
   */
  public function removeElementSegment(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $parents = array_slice($button['#parents'], 0, -4);
    // Map segment weights.
    $values = NestedArray::getValue($form_state->getUserInput(), $parents);
    $this->mapFormSegmentWeights($values);

    [$action, $name] = explode('__', $button['#name']);
    // @todo: Fix issue with changing UUID causing issues here.
    $this->getSegmentSetStorage()->getSegmentSet()->removeSegment($name);
    $this->stubDecision($form_state);
    $form_state->setRebuild();
  }

  /**
   * Provides an '#ajax' callback for removing a Variation.
   */
  public function removeElementSegmentAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));
  }

  /**
   * Maps the block weight values to the block collection.
   *
   * @param array $values
   *   The base form values or input.
   */
  public function mapFormSegmentWeights(array $values) {
    foreach ($this->getSegmentSetStorage()->getSegmentSet()->getSegments() as $segment_id => $segment) {
      $segment->setWeight((int) $values['decision_settings']['segments'][$segment_id]['weight']);
    }
    $this->getSegmentSetStorage()->getSegmentSet()->sortSegments();
  }

  /**
   * The selected segment set form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The altered form array.
   */
  public function buildSelectedSegmentSetWidget(array &$form, FormStateInterface $form_state) {
    // @todo: Implement unique id for wrapper.
    $wrapper_id = 'decision-reaction-wrapper';

    $form['#process'][] = [$this, 'processSelectedSegmentSetWidget'];

    $form['decision_settings'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#attributes' => [
        'id' => $wrapper_id,
        'class' => [
          'decision-settings-wrapper',
        ],
      ],
      '#title' => $this->t('Configure Reactions'),
    ];

    $i = 0;
    foreach ($this->getSegmentSetStorage()->getSegmentSet()->getSegments() as $segment_id => $segment) {

      $segment_label = $segment->getLabel();
      if ($segment_label) {
        $segment_label = $this->t('Segment @label', ['@label' => $segment_label]);
      }
      else {
        $segment_label = $this->t('Segment @count', ['@count' => ($i + 1)]);
      }

      $form['decision_settings']['segments'][$segment_id]['settings'] = [
        '#type' => 'fieldset',
        '#title' => $segment_label,
      ];

      $form['decision_settings']['segments'][$segment_id]['settings']['#prefix'] = '<div id="segment--' . $this->getToken() . '--' . $segment_id . '">';
      $form['decision_settings']['segments'][$segment_id]['settings']['#suffix'] = '</div>';

      // Display additional settings.
      if ($segment->isDefault()) {
        $form['decision_settings']['segments'][$segment_id]['settings']['additional_settings']['default'] = [
          '#markup' => $this->t('Default'),
          '#prefix' => '<div class="segment-summary-default">',
          '#suffix' => '</div>',
        ];
      }

      $form['decision_settings']['segments'][$segment_id]['settings']['uuid'] = [
        '#type' => 'value',
        '#value' => $segment_id,
      ];

      $reaction = $this->getReaction($segment->getUuid());

      $segment_state = WidgetStateHandler::getWidgetState($this, $form_state, $segment_id);
      if (is_null($segment_state)) {
        WidgetStateHandler::setWidgetState($this, $form_state, $segment_id, WidgetStateHandler::CLOSED);
        $segment_state = WidgetStateHandler::CLOSED;
      }
      if ($segment_state == WidgetStateHandler::OPEN) {
        // Edit mode: load forms and allow admin to edit.
        // Set button label.
        $button_label = 'Collapse';
        SegmentSetConfigEntityForm::pluginForm($reaction, $form, $form_state, [
          'decision_settings',
          'segments',
          $segment_id,
          'settings',
          'reaction_settings',
          'plugin_form',
        ]);
      }
      else {
        // Preview mode: show an HTML summary of the segment.
        // Add button label.
        $button_label = 'Edit';
        $form['decision_settings']['segments'][$segment_id]['settings']['reaction_summary'] = [
          'reaction_summary' => $reaction->getHtmlSummary(),
          '#prefix' => '<div class="segment-summary-wrapper clearfix>',
          '#suffix' => '</div>',
        ];
      }

      $form['decision_settings']['segments'][$segment_id]['settings']['edit_segment'] = [
        '#type' => 'submit',
        '#value' => $this->t('@label', ['@label' => $button_label], ['context' => 'Smart Content segment']),
        '#submit' => [[$this, 'toggleSegmentDisplay']],
        '#validate' => [[$this, 'validateToggleSegmentDisplay']],
        '#name' => 'edit-segment--' . $this->getToken() . '--' . $segment_id,
        '#ajax' => [
          'callback' => [$this, 'toggleSegmentDisplayAjax'],
          'wrapper' => 'segment--' . $this->getToken() . '--' . $segment_id,
        ],
        '#attributes' => [
          'class' => [
            'edit-segment',
            'edit-button',
          ],
        ],
      ];

      $i++;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Same as below, check if collapsed or open.
    if (!$this->getSegmentSetStorage()) {
      $form_state->setError($form['decision_select']['list'], 'You must first select a Segment Set.');
    }
    else {
      $is_inline = $this->getSegmentSetStorage() instanceof Inline;
      foreach ($this->getSegmentSetStorage()->getSegmentSet()->getSegments() as $uuid => $segment) {
        $widget_state = WidgetStateHandler::getWidgetState($this, $form_state, $uuid);
        // No need to save if the segment is in its collapsed state; it was
        // already saved.
        if ($widget_state == WidgetStateHandler::OPEN) {
          if ($is_inline) {
            foreach ($segment->getConditions() as $condition_id => $condition) {
              SegmentSetConfigEntityForm::pluginFormValidate($condition, $form, $form_state, [
                'decision_settings',
                'segments',
                $uuid,
                'settings',
                'condition_settings',
                $condition_id,
                'plugin_form',
              ]);
            }
          }
          $reaction = $this->getReaction($segment->getUuid());
          SegmentSetConfigEntityForm::pluginFormValidate($reaction, $form, $form_state, [
            'decision_settings',
            'segments',
            $uuid,
            'settings',
            'reaction_settings',
            'plugin_form',
          ]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $is_inline = $this->getSegmentSetStorage() instanceof Inline;

    $has_default = FALSE;
    foreach ($this->getSegmentSetStorage()->getSegmentSet()->getSegments() as $uuid => $segment) {
      $widget_state = WidgetStateHandler::getWidgetState($this, $form_state, $uuid);
      // No need to save if the segment is in its collapsed state; it was
      // already saved.
      if ($widget_state == WidgetStateHandler::OPEN) {
        if ($is_inline) {
          if ($values['decision_settings']['segments'][$uuid]['settings']['label']) {
            $label = $values['decision_settings']['segments'][$uuid]['settings']['label'];
            $segment->setLabel($label);
          }
          if ($values['decision_settings']['segments'][$uuid]['settings']['additional_settings']['default']) {
            $has_default = TRUE;
            $this->getSegmentSetStorage()
              ->getSegmentSet()
              ->setDefaultSegment($segment->getUuid());
          }
          foreach ($segment->getConditions() as $condition_id => $condition) {
            SegmentSetConfigEntityForm::pluginFormSubmit($condition, $form, $form_state, [
              'decision_settings',
              'segments',
              $uuid,
              'settings',
              'condition_settings',
              $condition_id,
              'plugin_form',
            ]);
          }
        }

        $reaction = $this->getReaction($segment->getUuid());
        SegmentSetConfigEntityForm::pluginFormSubmit($reaction, $form, $form_state, [
          'decision_settings',
          'segments',
          $uuid,
          'settings',
          'reaction_settings',
          'plugin_form',
        ]);
      }
      else {
        if ($segment->isDefault()) {
          $has_default = TRUE;
        }
      }
    }
    if ($is_inline && !$has_default && $this->getSegmentSetStorage()) {
      $this->getSegmentSetStorage()->getSegmentSet()->unsetDefaultSegment();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaceholderId() {
    return 'decision-block-' . $this->getToken();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['default' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    if (isset($configuration['default'])) {
      $this->savedDefault = $configuration['default'];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();
    if ($this->getSegmentSetStorage()) {
      if ($default_segment = $this->getSegmentSetStorage()
        ->getSegmentSet()
        ->getDefaultSegment()) {
        $configuration['default'] = $default_segment->getUuid();
      }
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedSettings() {
    $settings = parent::getAttachedSettings();
    if ($default_segment = $this->getSegmentSetStorage()->getSegmentSet()->getDefaultSegment()) {
      if ($this->hasReaction($default_segment->getUuid())) {
        $settings['decisions'][$this->getToken()]['default'] = $default_segment->getUuid();
      }
      elseif ($this->savedDefault) {
        $settings['decisions'][$this->getToken()]['default'] = $this->savedDefault;
      }
    }
    return $settings;
  }

}
