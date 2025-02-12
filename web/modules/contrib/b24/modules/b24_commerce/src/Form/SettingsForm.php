<?php

namespace Drupal\b24_commerce\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\state_machine\WorkflowManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure b24_commerce settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The workflow manager.
   *
   * @var \Drupal\state_machine\WorkflowManagerInterface
   */
  protected WorkflowManagerInterface $workflowManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'b24_commerce_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['b24_commerce.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('plugin.manager.workflow'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    WorkflowManagerInterface $workflow_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->workflowManager = $workflow_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['crm_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('CRM mode'),
      '#options' => [
        'simple' => $this->t('Simple'),
        'classic' => $this->t('Classic'),
      ],
      '#default_value' => $this->config('b24_commerce.settings')->get('crm_mode'),
    ];

    $form['convert_rules'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Leads convertation rules'),
      '#default_tab' => 'edit-convert_contact',
      '#description' => $this->t('In a «simple» mode of Bitrix24 leads are not automatically converted to deals and contacts. Here you can choose on what step you want them to be converted.'),
      '#states' => [
        'visible' => [
          '[name="crm_mode"]' => ['value' => 'classic'],
        ],
      ],
    ];

    $order_types = array_reverse($this->entityTypeBundleInfo->getBundleInfo('commerce_order'));

    foreach ($order_types as $order_type_id => $order_type) {
      $form[$order_type_id] = [
        '#type' => 'details',
        '#title' => $order_type['label'],
        '#group' => 'convert_rules',
        '#tree' => TRUE,
      ];

      $form[$order_type_id]['label'] = [
        '#type' => 'label',
        '#title' => $this->t('Lead conversion rules for orders of type «@type»:', ['@type' => $order_type['label']]),
      ];

      $options = [];

      $workflow_id = OrderType::load($order_type_id)->getWorkflowId();
      /**
       * @var \Drupal\state_machine\Plugin\Workflow\Workflow $workflow
       */
      $workflow = $this->workflowManager->createInstance($workflow_id);
      $states = $workflow->getStates();
      $optgroup_order = $this->t('Order transitions')->__toString();
      $options[$optgroup_order] = [];
      foreach ($states as $state) {
        $transitions = $workflow->getPossibleTransitions($state->getId());
        foreach ($transitions as $transition) {
          /**
           * @var \Drupal\state_machine\Plugin\Workflow\WorkflowTransition $transition
           */
          $options[$optgroup_order][$transition->getId()] = $transition->getLabel();
        }
      }

      // @todo Do we really need reacting on payments or completion of payment is always the same as placing of the order?
      $optgroup_payment = $this->t('Payment transitions')->__toString();
      $options[$optgroup_payment] = [];
      $options[$optgroup_payment]['paid'] = $this->t('Order paid');

      $output_entities = [
        'contact',
        'deal',
      ];

      foreach ($output_entities as $b24_entity) {
        $form[$order_type_id]["convert_{$b24_entity}"] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Convert leads to @b24_entity', ['@b24_entity' => $b24_entity]),
          '#default_value' => $this->config('b24_commerce.settings')->get("{$order_type_id}.convert_{$b24_entity}"),
        ];

        $form[$order_type_id]["convert_{$b24_entity}_step"] = [
          '#type' => 'select',
          '#title' => $this->t('Convert leads to @b24_entitys on the following transition', ['@b24_entity' => $b24_entity]),
          '#options' => $options,
          '#default_value' => $this->config('b24_commerce.settings')->get("{$order_type_id}.convert_{$b24_entity}_step"),
          '#states' => [
            'visible' => [
              "#edit-{$order_type_id}-convert-{$b24_entity}" => ['checked' => TRUE],
            ],
          ],
        ];
      }
    }

    $form['#attached']['library'] = [
      'b24_commerce/vertical-tabs',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('b24_commerce.settings');
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
