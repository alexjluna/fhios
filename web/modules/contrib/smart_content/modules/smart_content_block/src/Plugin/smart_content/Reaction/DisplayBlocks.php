<?php

namespace Drupal\smart_content_block\Plugin\smart_content\Reaction;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\smart_content\Cache\CacheableAjaxResponse;
use Drupal\smart_content\Decision\PlaceholderDecisionInterface;
use Drupal\smart_content\Form\SegmentSetConfigEntityForm;
use Drupal\smart_content\Reaction\ReactionConfigurableBase;
use Drupal\smart_content_block\BlockPluginCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides 'Block' display reaction.
 *
 * @SmartReaction(
 *  id = "display_blocks",
 *  label = @Translation("Block"),
 * )
 */
class DisplayBlocks extends ReactionConfigurableBase implements ContainerFactoryPluginInterface {

  /**
   * Block plugin manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * A plugin collection for lazy loading blocks.
   *
   * @var \Drupal\Core\Plugin\DefaultLazyPluginCollection
   */
  protected $blocksPluginCollection;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The blocks that are a part of this reaction.
   *
   * @var array
   */
  protected $blocks;

  /**
   * Block constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block plugin manager service.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   The context repository service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user account.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockManagerInterface $blockManager, ContextRepositoryInterface $contextRepository, AccountInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockManager = $blockManager;
    $this->contextRepository = $contextRepository;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.block'),
      $container->get('context.repository'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // Build a list of blocks for the user to select from.
    $definitions = $this->blockManager->getDefinitionsForContexts($this->contextRepository->getAvailableContexts());
    $sorted_definitions = $this->blockManager->getSortedDefinitions($definitions);
    $options = [];

    // todo: determine how to support these.
    $omitted_blocks = [
      'extra_field_block',
      'inline_block',
      'page_title_block',
      'smart_content_decision_block',
    ];
    $omitted_blocks = array_combine($omitted_blocks, $omitted_blocks);
    foreach ($sorted_definitions as $id => $definition) {
      if (!isset($omitted_blocks[$definition['id']]) && $definition['provider'] != 'layout_builder') {
        $category = (string) $definition['category'];
        $options[$category][$id] = $definition['admin_label'];
      }
    }

    $wrapper_id = Html::getUniqueId('block-settings-wrapper');
    $form['label'] = [
      '#type' => 'markup',
      '#prefix' => '<div class="form-item-label">',
      '#suffix' => '</div>',
      '#markup' => $this->t('Display the following blocks'),
    ];

    $form['settings'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => [
        'id' => $wrapper_id . '-blocks',
        'class' => [
          'block-reaction',
          'reaction-settings-wrapper',
        ],
      ],
      '#title' => $this->t('Configure Blocks to Display'),
    ];

    $blocks = $this->getBlocks();

    if (empty($blocks)) {
      $form['settings']['blocks'] = [
        '#type' => 'markup',
        '#markup' => $this->t('No blocks set to display.'),
        '#prefix' => '<div class="messages messages--warning">',
        '#suffix' => '</div>',
      ];
    }
    else {
      $form['settings']['blocks'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Configuration'),
          $this->t('Operations'),
          $this->t('Weight'),
        ],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => $wrapper_id . '-order-weight',
          ],
        ],
        '#attributes' => [
          'class' => ['reaction-settings-table'],
        ],
      ];

      // Initialize default weight variable.
      $i = 0;

      // Loop through each block and add it to the form.
      foreach ($blocks as $block_id => $block) {
        // Configuration.
        $form['settings']['blocks'][$block_id]['plugin_form'] = $block->buildConfigurationForm([], $form_state);
        $form['settings']['blocks'][$block_id]['plugin_form']['#type'] = 'container';
        $form['settings']['blocks'][$block_id]['plugin_form']['#attributes']['class'][] = 'reaction-item-settings-wrapper';
        $form['settings']['blocks'][$block_id]['#attributes']['class'][] = 'draggable';
        $form['settings']['blocks'][$block_id]['#attributes']['class'][] = 'row-color-coded';

        // Operations.
        $form['settings']['blocks'][$block_id]['remove'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove block'),
          '#submit' => [[$this, 'removeElementBlock']],
          '#attributes' => [
            'class' => [
              'align-right',
              'button',
              'button--delete',
              'button--remove-reaction',
              'button--remove-reaction--block',
            ],
          ],
          '#limit_validation_errors' => [],
          '#ajax' => [
            'callback' => [$this, 'removeElementBlockAjax'],
            'wrapper' => $wrapper_id . '-blocks',
          ],
        ];

        // Weight.
        $form['settings']['blocks'][$block_id]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight'),
          '#title_display' => 'invisible',
          '#default_value' => $i,
          '#attributes' => ['class' => [$wrapper_id . '-order-weight']],
        ];
        $i++;
      }
    }

    $form['settings']['add_block'] = [
      '#type' => 'container',
      '#title' => $this->t('Add Block'),
      '#attributes' => [
        'class' => [
          'block-add-container',
          'add-container',
          'reaction-add-container',
        ],
      ],
    ];

    $form['settings']['add_block']['block_type'] = [
      '#title' => $this->t('Block Type'),
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => $options,
      '#empty_value' => '',
      '#empty_option' => $this->t('- Select a block -'),
    ];

    $form['settings']['add_block']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Block'),
      '#validate' => [[$this, 'addElementBlockValidate']],
      '#limit_validation_errors' => [],
      '#submit' => [[$this, 'addElementBlock']],
      '#ajax' => [
        'callback' => [$this, 'addElementBlockAjax'],
        'wrapper' => $wrapper_id . '-blocks',
      ],
    ];

    $form['#process'][] = [$this, 'buildWidget'];
    return $form;
  }

  /**
   * Process callback to attach unique id's based on parents.
   */
  public function buildWidget($element, FormStateInterface $form_state, array $form) {
    $unique_id = Html::getClass(implode('-', $element['#parents']));

    // Add unique names for the remove buttons.
    foreach ($this->getBlocks() as $block_id => $block) {
      if ($block instanceof PluginFormInterface) {
        $element['settings']['blocks'][$block_id]['remove']['#name'] = 'remove_block_' . $unique_id . '__' . $block_id;
      }
    }

    // Get the "Add block" button ready for AJAX.
    $element['settings']['add_block']['submit'] += [
      '#name' => 'add-block-' . $unique_id,
      '#limit_validation_errors' => [
        array_merge($element['#parents'], [
          'settings',
          'add_block',
          'block_type',
        ]),
      ],
    ];

    return $element;
  }

  /**
   * Provides a '#validate' callback for adding a Block.
   *
   * Validates that a valid block type is selected.
   */
  public function addElementBlockValidate(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $array_parents = array_slice($button['#array_parents'], 0, -1);
    $parents = array_slice($button['#parents'], 0, -1);
    $parents[] = 'block_type';
    $array_parents[] = 'block_type';
    if (!$value = NestedArray::getValue($form_state->getUserInput(), $parents)) {
      $form_state->setError(NestedArray::getValue($form, $array_parents), 'Block type required.');
    }
  }

  /**
   * Provides a '#submit' callback for adding a Block.
   */
  public function addElementBlock(array &$form, FormStateInterface $form_state) {
    if (!$form_state->isValidationComplete()) {
      return;
    }
    $button = $form_state->getTriggeringElement();

    // Map block weights to collection.
    $all_values = NestedArray::getValue($form_state->getUserInput(), array_slice($button['#parents'], 0, -3));
    $this->mapFormBlocksWeight($all_values);

    $type = NestedArray::getValue($form_state->getUserInput(), array_slice($button['#parents'], 0, -1))['block_type'];
    $block = $this->blockManager->createInstance($type);
    $this->getBlocksPluginCollection()->add($block);
    $form_state->setRebuild();
  }

  /**
   * Provides an '#ajax' callback for adding a Block.
   */
  public function addElementBlockAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
  }

  /**
   * Provides a submit callback for removing a block.
   *
   * @param array $form
   *   The plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function removeElementBlock(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Map block weights to collection.
    $all_values = NestedArray::getValue($form_state->getUserInput(), array_slice($button['#parents'], 0, -4));
    $this->mapFormBlocksWeight($all_values);

    [$action, $name] = explode('__', $button['#name']);
    $this->getBlocksPluginCollection()->removeInstanceId($name);
    $form_state->setRebuild();
  }

  /**
   * Provides an AJAX callback for removing a block.
   *
   * @param array $form
   *   The plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return mixed
   *   The AJAX response.
   */
  public function removeElementBlockAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getBlocks() as $block_id => $block) {
      SegmentSetConfigEntityForm::pluginFormValidate($block, $form, $form_state, [
        'settings',
        'blocks',
        $block_id,
        'plugin_form',
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getBlocks() as $block_id => $block) {
      SegmentSetConfigEntityForm::pluginFormSubmit($block, $form, $form_state, [
        'settings',
        'blocks',
        $block_id,
        'plugin_form',
      ]);
    }
    $values = $form_state->getValues();
    $this->mapFormBlocksWeight($values);
  }

  /**
   * Maps the block weight values to the block collection.
   *
   * @param array $values
   *   The base form values or input.
   */
  public function mapFormBlocksWeight(array $values) {
    if (!empty($values['settings']['blocks'])) {
      $block_values = $values['settings']['blocks'];
      if (count($block_values) > 1) {
        $keys = array_keys($block_values);
        $this->getBlocksPluginCollection()->mapWeightValues($keys);
      }
    }
  }

  /**
   * Creates a plugin collection for blocks that are a part of this reaction.
   *
   * @return \Drupal\Core\Plugin\DefaultLazyPluginCollection
   *   The lazy plugin collection.
   */
  protected function getBlocksPluginCollection() {
    if (!$this->blocksPluginCollection) {
      $this->blocksPluginCollection = new BlockPluginCollection($this->blockManager, (array) $this->blocks);
    }
    return $this->blocksPluginCollection;
  }

  /**
   * Get blocks from block collection.
   *
   * @return array
   *   An array of block instances.
   */
  protected function getBlocks() {
    $blocks = [];
    $blocks_collection = $this->getBlocksPluginCollection();
    $instance_ids = $blocks_collection->getInstanceIds();
    if (!empty($instance_ids)) {
      foreach ($instance_ids as $instance_id) {
        $blocks[$instance_id] = $blocks_collection->get($instance_id);
      }
    }
    return $blocks;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();
    if ($blocks_collection = $this->getBlocksPluginCollection()) {
      $configuration['blocks'] = $blocks_collection->getConfiguration();
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    $configuration = $configuration + $this->defaultConfiguration();
    if (isset($configuration['blocks'])) {
      // TODO: Can we just use the collection, since it takes care of both
      // config and instances? Initialize collection in the constructor and
      // here, just add config to it.
      $this->blocks = $configuration['blocks'];
      $this->blocksPluginCollection = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'blocks' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(PlaceholderDecisionInterface $decision) {
    $response = new CacheableAjaxResponse();
    $content = [];
    // Load all the blocks that are a part of this reaction.
    $blocks = $this->getBlocks();
    if (!empty($blocks)) {
      // Build the render array for each block.
      foreach ($blocks as $block_instance) {
        $access = $block_instance->access($this->currentUser, TRUE);
        $response->addCacheableDependency($access);
        if ($access) {
          $content[] = [
            '#theme' => 'block',
            '#attributes' => [],
            '#configuration' => $block_instance->getConfiguration(),
            '#plugin_id' => $block_instance->getPluginId(),
            '#base_plugin_id' => $block_instance->getBaseId(),
            '#derivative_plugin_id' => $block_instance->getDerivativeId(),
            '#id' => $this->getPluginId(),
            'content' => $block_instance->build(),
          ];
          $response->addCacheableDependency($block_instance);
        }
      }
    }
    // Build and return the AJAX response.
    $selector = '[data-smart-content-placeholder="' . $decision->getPlaceholderId() . '"]';
    $response->addCommand(new ReplaceCommand($selector, $content));
    return $response;
  }

  /**
   * {@inheritDoc}
   */
  public function getPlainTextSummary() {
    $blocks = $this->getBlocks();
    if (empty($blocks)) {
      return '';
    }
    $labels = array_map(function ($block) {
      return $block->getConfiguration()['label'];
    }, $blocks);
    return implode(', ', $labels);
  }

  /**
   * {@inheritDoc}
   */
  public function getHtmlSummary() {
    $blocks = $this->getBlocks();
    $blocks_summary = [];
    if (!empty($blocks)) {
      foreach ($blocks as $block) {
        $blocks_summary[] = [
          '#markup' => '<p>' . $block->getConfiguration()['label'] . '</p>',
        ];
      }
    }
    else {
      $blocks_summary[] = [
        '#markup' => '<p>No blocks selected</p>',
      ];
    }
    return [
      '#prefix' => '<div class="reaction--display-blocks">',
      '#suffix' => '</div>',
      'markup' => [
        '#markup' => '<p><em>Display the following blocks: </p></em>',
      ],
      'blocks' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $blocks_summary,
      ],
    ];
  }

}
