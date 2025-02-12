<?php

namespace Drupal\b24_commerce\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\b24\Service\RestManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides form for mapping between commerce orders and Bitrix24 leads.
 */
class MappingForm extends ConfigFormBase {

  /**
   * Drupal\b24\Service\RestManager definition.
   *
   * @var \Drupal\b24\Service\RestManager
   */
  protected RestManager $b24RestManager;

  /**
   * The commerce order type.
   *
   * @var \Drupal\commerce_order\Entity\OrderType
   */
  protected $orderType;

  /**
   * Constructs a new MappingForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    RestManager $b24_rest_manager,
    RequestStack $request_stack,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->b24RestManager = $b24_rest_manager;
    $this->orderType = $request_stack->getCurrentRequest()->get('commerce_order_type');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('b24.rest_manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      "b24_commerce.mapping.{$this->orderType}",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($this->orderType)) {
      throw new NotFoundHttpException();
    }

    $token_link = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['commerce_order', 'profile', 'user'],
      '#show_restricted' => TRUE,
      '#global_types' => FALSE,
    ];

    $form['token_tree_top'] = $token_link;

    $form['mappings'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-leads',
    ];

    $form['token_tree_bottom'] = $token_link;

    $form['lead'] = [
      '#type' => 'details',
      '#title' => $this->t('Leads'),
      '#group' => 'mappings',
      '#tree' => TRUE,
    ];

    $b24_fields = $this->getFields($form, 'lead');

    $b24_fields = array_merge($b24_fields, $this->b24RestManager->getFields('contact'));
    $form_state->addBuildInfo('ext_fields', $b24_fields);

    $form['deal'] = [
      '#type' => 'details',
      '#title' => $this->t('Deals'),
      '#group' => 'mappings',
      '#tree' => TRUE,
    ];

    $this->getFields($form, 'deal');

    return parent::buildForm($form, $form_state);
  }

  /**
   * Returns fields available in Bitrix24 and changes form by appending fields.
   *
   * @param array $form
   *   The form array to be changed.
   * @param string $b24_entity
   *   The Bitrix24 entity type id.
   *
   * @return array|mixed
   *   The list of fields.
   */
  private function getFields(array &$form, $b24_entity = 'lead') {
    $config = $this->config("b24_commerce.mapping.{$this->orderType}");
    $b24_fields = $this->b24RestManager->getFields($b24_entity);
    // @todo The HONORIFIC (Обращение) field is documented as crm_status but returned by CRM as a string.
    foreach ($b24_fields as $b24_field_name => $b24_field_properties) {
      if (in_array($b24_field_properties['type'],
          [
            'string',
            'crm_multifield',
            'double',
          ]
        ) && !$b24_field_properties['isReadOnly']) {
        $form[$b24_entity][$b24_field_name] = [
          '#type' => $b24_field_name == 'COMMENTS' ? 'textarea' : 'textfield',
          '#title' => $b24_field_properties['title'],
          '#default_value' => $config->get("{$b24_entity}.{$b24_field_name}"),
          '#element_validate' => ['::tokenedFieldValidate'],
          '#b24_required' => $b24_field_properties['isRequired'],
          '#label_attributes' => $b24_field_properties['isRequired'] ? ['class' => ['form-required']] : [],
        ];
      }
    }

    return $b24_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    foreach ($form_state->cleanValues()->getValues() as $b24_entity => $values) {
      foreach ($values as $field_name => $value) {
        if (!$value && !empty($form[$b24_entity][$field_name]['#b24_required'])) {
          $form_state->setErrorByName("{$b24_entity}[{$field_name}]", $this->t('"@field_name" is required in "@b24_entity" tab.', [
            '@field_name' => $form[$b24_entity][$field_name]['#title'],
            '@b24_entity' => $form[$b24_entity]['#title'],
          ]));
        }
      }
    }
  }

  /**
   * Validate callback for tokenized field.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function tokenedFieldValidate(array &$element, FormStateInterface $form_state) {
    // @todo May be useful in future.
    // if ($element['#value']) {
    // $form_state->setError($element, $this->t('Error'));
    // }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->cleanValues();
    $values = $form_state->getValues();
    $config = $this->configFactory->getEditable("b24_commerce.mapping.{$this->orderType}");
    foreach ($values as $name => $value) {
      $config->set($name, $value);
    }
    $config->save();

    $this->configFactory->getEditable('b24_commerce.field_types')->setData($form_state->getBuildInfo()['ext_fields'])->save();
  }

}
